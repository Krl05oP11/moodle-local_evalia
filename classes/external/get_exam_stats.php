<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * WS: get_exam_stats — aggregate statistics for a given exam.
 *
 * Returns:
 *   - counts per status (assigned / started / submitted / graded)
 *   - score distribution in 5 bands (0-2, 2-4, 4-6, 6-8, 8-10)
 *   - average score, min, max
 *   - top 5 most-failed questions (question stem + fail count)
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

class get_exam_stats extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'examid' => new external_value(PARAM_INT, 'evalia_exams ID'),
        ]);
    }

    public static function execute(int $examid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['examid' => $examid]);
        $examid = (int) $params['examid'];

        $exam    = $DB->get_record('evalia_exams', ['id' => $examid], '*', MUST_EXIST);
        $context = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // ── 1. Status counts ──────────────────────────────────────────────────
        $all = $DB->get_records('evalia_student_exams', ['examid' => $examid], '', 'id, status, score, answers, question_ids');

        $counts = ['assigned' => 0, 'started' => 0, 'submitted' => 0, 'graded' => 0];
        $graded_scores = [];

        foreach ($all as $row) {
            $status = $row->status ?? 'assigned';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            if ($status === 'graded' && $row->score !== null) {
                $graded_scores[] = (float) $row->score;
            }
        }

        // ── 2. Score distribution (5 bands: 0-2, 2-4, 4-6, 6-8, 8-10) ───────
        $bands = [
            ['label' => '0–2',  'min' => 0.0,  'max' => 2.0,  'count' => 0],
            ['label' => '2–4',  'min' => 2.0,  'max' => 4.0,  'count' => 0],
            ['label' => '4–6',  'min' => 4.0,  'max' => 6.0,  'count' => 0],
            ['label' => '6–8',  'min' => 6.0,  'max' => 8.0,  'count' => 0],
            ['label' => '8–10', 'min' => 8.0,  'max' => 10.01,'count' => 0],
        ];

        foreach ($graded_scores as $s) {
            foreach ($bands as &$band) {
                if ($s >= $band['min'] && $s < $band['max']) {
                    $band['count']++;
                    break;
                }
            }
            unset($band);
        }

        $avg_score = count($graded_scores) > 0
            ? round(array_sum($graded_scores) / count($graded_scores), 2)
            : 0.0;
        $min_score = count($graded_scores) > 0 ? (float) min($graded_scores) : 0.0;
        $max_score = count($graded_scores) > 0 ? (float) max($graded_scores) : 0.0;
        $pass_count = count(array_filter($graded_scores, fn($s) => $s >= 6.0));

        // ── 3. Most-failed questions ──────────────────────────────────────────
        // Scan all graded exams' answers and compare to correct answers.
        $fail_counts = [];   // question_id → fail count

        $graded_rows = array_filter((array) $all, fn($r) => $r->status === 'graded');

        foreach ($graded_rows as $row) {
            $answers = json_decode($row->answers ?? '{}', true);
            $q_ids   = json_decode($row->question_ids ?? '[]', true);
            if (empty($q_ids) || empty($answers)) {
                continue;
            }
            foreach ($q_ids as $qid) {
                $qid_str = (string) $qid;
                if (!isset($fail_counts[$qid])) {
                    $fail_counts[$qid] = 0;
                }
            }
        }

        // Load correct answers for all questions that appeared in this exam.
        $all_qids = [];
        foreach ($all as $row) {
            $ids = json_decode($row->question_ids ?? '[]', true);
            foreach ($ids as $id) {
                $all_qids[(int)$id] = true;
            }
        }

        $question_data   = [];
        $correct_options = [];
        if (!empty($all_qids)) {
            [$in_sql, $in_params] = $DB->get_in_or_equal(array_keys($all_qids), SQL_PARAMS_NAMED, 'q');
            $qs = $DB->get_records_select('evalia_question_bank', "id $in_sql", $in_params, '', 'id, stem, question_type, correct_answer, topic, difficulty');
            foreach ($qs as $q) {
                $question_data[$q->id] = $q;
            }
            // Load correct option texts for multichoice/truefalse.
            [$oin_sql, $oin_params] = $DB->get_in_or_equal(array_keys($all_qids), SQL_PARAMS_NAMED, 'oq');
            $opts = $DB->get_records_select('evalia_question_options', "questionid $oin_sql AND is_correct = 1", $oin_params, '', 'questionid, option_text');
            foreach ($opts as $opt) {
                $correct_options[$opt->questionid] = $opt->option_text;
            }
        }

        // Count failures per question.
        $fail_map = [];   // question_id → fail_count
        foreach ($graded_rows as $row) {
            $answers = json_decode($row->answers ?? '{}', true);
            $q_ids   = json_decode($row->question_ids ?? '[]', true);
            if (empty($q_ids)) { continue; }

            foreach ($q_ids as $qid) {
                $qid_int = (int) $qid;
                $q = $question_data[$qid_int] ?? null;
                if (!$q) { continue; }

                $student_ans = strtolower(trim($answers[(string)$qid] ?? ''));
                if (in_array($q->question_type, ['multichoice', 'truefalse'])) {
                    $correct = strtolower(trim($correct_options[$qid_int] ?? ''));
                    $wrong = ($student_ans !== $correct);
                } elseif ($q->question_type === 'numerical') {
                    $val   = (float) $student_ans;
                    $wrong = abs($val - (float)$q->correct_answer) > 0.01;
                } else {
                    $correct = strtolower(trim($q->correct_answer ?? ''));
                    $wrong = ($student_ans !== $correct);
                }
                if ($wrong) {
                    $fail_map[$qid_int] = ($fail_map[$qid_int] ?? 0) + 1;
                }
            }
        }

        $difficulty_labels = [
            'basic'    => 'Básica',
            'medium'   => 'Media',
            'advanced' => 'Avanzada',
        ];

        arsort($fail_map);
        $top_failed = [];
        foreach (array_slice($fail_map, 0, 5, true) as $qid => $fc) {
            $q = $question_data[$qid] ?? null;
            $top_failed[] = [
                'question_id' => $qid,
                'stem'        => $q ? format_text($q->stem, FORMAT_PLAIN) : '(pregunta eliminada)',
                'topic'       => $q ? ($q->topic      ?? '') : '',
                'difficulty'  => $q ? ($difficulty_labels[$q->difficulty ?? ''] ?? ucfirst($q->difficulty ?? '')) : '',
                'fail_count'  => $fc,
                'total'       => count($graded_rows),
            ];
        }

        return [
            'examid'      => $examid,
            'counts'      => $counts,
            'graded'      => count($graded_scores),
            'avg_score'   => $avg_score,
            'min_score'   => $min_score,
            'max_score'   => $max_score,
            'pass_count'  => $pass_count,
            'bands'       => array_values($bands),
            'top_failed'  => $top_failed,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'examid'     => new external_value(PARAM_INT,   'Exam ID'),
            'counts'     => new external_single_structure([
                'assigned'  => new external_value(PARAM_INT, 'Assigned'),
                'started'   => new external_value(PARAM_INT, 'Started'),
                'submitted' => new external_value(PARAM_INT, 'Submitted'),
                'graded'    => new external_value(PARAM_INT, 'Graded'),
            ]),
            'graded'     => new external_value(PARAM_INT,   'Total graded exams'),
            'avg_score'  => new external_value(PARAM_FLOAT, 'Average score (0-10)'),
            'min_score'  => new external_value(PARAM_FLOAT, 'Minimum score'),
            'max_score'  => new external_value(PARAM_FLOAT, 'Maximum score'),
            'pass_count' => new external_value(PARAM_INT,   'Count with score >= 6'),
            'bands'      => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Band label e.g. 0–2'),
                    'count' => new external_value(PARAM_INT,  'Students in this band'),
                ])
            ),
            'top_failed' => new external_multiple_structure(
                new external_single_structure([
                    'question_id' => new external_value(PARAM_INT,          'Question ID'),
                    'stem'        => new external_value(PARAM_TEXT,          'Question text'),
                    'topic'       => new external_value(PARAM_TEXT,          'Rubric topic / theme'),
                    'difficulty'  => new external_value(PARAM_TEXT,          'Difficulty label'),
                    'fail_count'  => new external_value(PARAM_INT,           'Times answered wrong'),
                    'total'       => new external_value(PARAM_INT,           'Total graded exams'),
                ])
            ),
        ]);
    }
}
