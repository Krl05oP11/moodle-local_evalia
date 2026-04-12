<?php
// This file is part of Moodle - https://moodle.org/
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


/**
 * Get_exam_stats.
 */
class get_exam_stats extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'examid' => new external_value(PARAM_INT, 'evalia_exams ID'),
        ]);
    }

    /**
     * Execute the web service.
     */
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
        $gradedscores = [];

        foreach ($all as $row) {
            $status = $row->status ?? 'assigned';
            if (isset($counts[$status])) {
                $counts[$status]++;
            }
            if ($status === 'graded' && $row->score !== null) {
                $gradedscores[] = (float) $row->score;
            }
        }

        // ── 2. Score distribution (5 bands: 0-2, 2-4, 4-6, 6-8, 8-10) ───────
        $bands = [
            ['label' => '0–2', 'min' => 0.0, 'max' => 2.0, 'count' => 0],
            ['label' => '2–4', 'min' => 2.0, 'max' => 4.0, 'count' => 0],
            ['label' => '4–6', 'min' => 4.0, 'max' => 6.0, 'count' => 0],
            ['label' => '6–8', 'min' => 6.0, 'max' => 8.0, 'count' => 0],
            ['label' => '8–10', 'min' => 8.0, 'max' => 10.01, 'count' => 0],
        ];

        foreach ($gradedscores as $s) {
            foreach ($bands as &$band) {
                if ($s >= $band['min'] && $s < $band['max']) {
                    $band['count']++;
                    break;
                }
            }
            unset($band);
        }

        $avgscore = count($gradedscores) > 0
            ? round(array_sum($gradedscores) / count($gradedscores), 2)
            : 0.0;
        $minscore = count($gradedscores) > 0 ? (float) min($gradedscores) : 0.0;
        $maxscore = count($gradedscores) > 0 ? (float) max($gradedscores) : 0.0;
        $passcount = count(array_filter($gradedscores, fn($s) => $s >= 6.0));

        // ── 3. Most-failed questions ──────────────────────────────────────────
        // Scan all graded exams' answers && compare to correct answers.
        $failcounts = [];   // question_id → fail count

        $gradedrows = array_filter((array) $all, fn($r) => $r->status === 'graded');

        foreach ($gradedrows as $row) {
            $answers = json_decode($row->answers ?? '{}', true);
            $qids   = json_decode($row->question_ids ?? '[]', true);
            if (empty($qids) || empty($answers)) {
                continue;
            }
            foreach ($qids as $qid) {
                $qidstr = (string) $qid;
                if (!isset($failcounts[$qid])) {
                    $failcounts[$qid] = 0;
                }
            }
        }

        // Load correct answers for all questions that appeared in this exam.
        $allqids = [];
        foreach ($all as $row) {
            $ids = json_decode($row->question_ids ?? '[]', true);
            foreach ($ids as $id) {
                $allqids[(int)$id] = true;
            }
        }

        $questiondata   = [];
        $correctoptions = [];
        if (!empty($allqids)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($allqids), SQL_PARAMS_NAMED, 'q');
            $qs = $DB->get_records_select('evalia_question_bank', "id $insql", $inparams, '', 'id, stem, question_type, correct_answer, topic, difficulty');
            foreach ($qs as $q) {
                $questiondata[$q->id] = $q;
            }
            // Load correct option texts for multichoice/truefalse.
            [$oinsql, $oinparams] = $DB->get_in_or_equal(array_keys($allqids), SQL_PARAMS_NAMED, 'oq');
            $opts = $DB->get_records_select('evalia_question_options', "questionid $oinsql AND is_correct = 1", $oinparams, '', 'questionid, option_text');
            foreach ($opts as $opt) {
                $correctoptions[$opt->questionid] = $opt->option_text;
            }
        }

        // Count failures per question.
        $failmap = [];   // question_id → fail_count
        foreach ($gradedrows as $row) {
            $answers = json_decode($row->answers ?? '{}', true);
            $qids   = json_decode($row->question_ids ?? '[]', true);
            if (empty($qids)) {
                continue;
            }

            foreach ($qids as $qid) {
                $qidint = (int) $qid;
                $q = $questiondata[$qidint] ?? null;
                if (!$q) {
                    continue;
                }

                $studentans = strtolower(trim($answers[(string)$qid] ?? ''));
                if (in_array($q->question_type, ['multichoice', 'truefalse'])) {
                    $correct = strtolower(trim($correctoptions[$qidint] ?? ''));
                    $wrong = ($studentans !== $correct);
                } else if ($q->question_type === 'numerical') {
                    $val   = (float) $studentans;
                    $wrong = abs($val - (float)$q->correct_answer) > 0.01;
                } else {
                    $correct = strtolower(trim($q->correct_answer ?? ''));
                    $wrong = ($studentans !== $correct);
                }
                if ($wrong) {
                    $failmap[$qidint] = ($failmap[$qidint] ?? 0) + 1;
                }
            }
        }

        $difficultylabels = [
            'basic'    => 'Básica',
            'medium'   => 'Media',
            'advanced' => 'Avanzada',
        ];

        arsort($failmap);
        $topfailed = [];
        foreach (array_slice($failmap, 0, 5, true) as $qid => $fc) {
            $q = $questiondata[$qid] ?? null;
            $topfailed[] = [
                'question_id' => $qid,
                'stem'        => $q ? format_text($q->stem, FORMAT_PLAIN) : '(pregunta eliminada)',
                'topic'       => $q ? ($q->topic ?? '') : '',
                'difficulty'  => $q ? ($difficultylabels[$q->difficulty ?? ''] ?? ucfirst($q->difficulty ?? '')) : '',
                'fail_count'  => $fc,
                'total'       => count($gradedrows),
            ];
        }

        return [
            'examid'      => $examid,
            'counts'      => $counts,
            'graded'      => count($gradedscores),
            'avg_score'   => $avgscore,
            'min_score'   => $minscore,
            'max_score'   => $maxscore,
            'pass_count'  => $passcount,
            'bands'       => array_values($bands),
            'top_failed'  => $topfailed,
        ];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'examid'     => new external_value(PARAM_INT, 'Exam ID'),
            'counts'     => new external_single_structure([
                'assigned'  => new external_value(PARAM_INT, 'Assigned'),
                'started'   => new external_value(PARAM_INT, 'Started'),
                'submitted' => new external_value(PARAM_INT, 'Submitted'),
                'graded'    => new external_value(PARAM_INT, 'Graded'),
            ]),
            'graded'     => new external_value(PARAM_INT, 'Total graded exams'),
            'avg_score'  => new external_value(PARAM_FLOAT, 'Average score (0-10)'),
            'min_score'  => new external_value(PARAM_FLOAT, 'Minimum score'),
            'max_score'  => new external_value(PARAM_FLOAT, 'Maximum score'),
            'pass_count' => new external_value(PARAM_INT, 'Count with score >= 6'),
            'bands'      => new external_multiple_structure(
                new external_single_structure([
                    'label' => new external_value(PARAM_TEXT, 'Band label e.g. 0–2'),
                    'count' => new external_value(PARAM_INT, 'Students in this band'),
                ])
            ),
            'top_failed' => new external_multiple_structure(
                new external_single_structure([
                    'question_id' => new external_value(PARAM_INT, 'Question ID'),
                    'stem'        => new external_value(PARAM_TEXT, 'Question text'),
                    'topic'       => new external_value(PARAM_TEXT, 'Rubric topic / theme'),
                    'difficulty'  => new external_value(PARAM_TEXT, 'Difficulty label'),
                    'fail_count'  => new external_value(PARAM_INT, 'Times answered wrong'),
                    'total'       => new external_value(PARAM_INT, 'Total graded exams'),
                ])
            ),
        ]);
    }
}
