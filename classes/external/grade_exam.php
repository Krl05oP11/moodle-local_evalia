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
 * WS: grade_exam — grade a submitted student exam and update portfolio.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

defined('MOODLE_INTERNAL') || die();

class grade_exam extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'student_examid' => new external_value(PARAM_INT,  'evalia_student_exams ID'),
            'answers'        => new external_value(PARAM_TEXT, 'JSON: {"question_id": "answer_text", ...}'),
        ]);
    }

    public static function execute(int $student_examid, string $answers): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/local/evalia/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'student_examid' => $student_examid,
            'answers'        => $answers,
        ]);

        $student_exam = $DB->get_record('evalia_student_exams', ['id' => $params['student_examid']], '*', MUST_EXIST);
        $exam         = $DB->get_record('evalia_exams', ['id' => $student_exam->examid], '*', MUST_EXIST);
        $context      = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        if ($student_exam->status === 'graded') {
            return [
                'success'   => false,
                'score'     => (float) $student_exam->score,
                'max_score' => 10.0,
                'percent'   => round((float) $student_exam->score * 10.0, 1),
                'message'   => 'Este examen ya fue calificado.',
            ];
        }

        // Load assigned question IDs.
        $question_ids = json_decode($student_exam->question_ids ?? '[]', true);
        if (empty($question_ids)) {
            return ['success' => false, 'score' => 0.0, 'max_score' => 10.0, 'percent' => 0.0,
                    'message' => 'El examen no tiene preguntas asignadas.'];
        }

        // Load question bank records (stem + topic needed for feedback message).
        [$in_sql, $in_params] = $DB->get_in_or_equal($question_ids, SQL_PARAMS_NAMED, 'qid');
        $questions = $DB->get_records_select(
            'evalia_question_bank',
            "id $in_sql",
            $in_params,
            '',
            'id, question_type, correct_answer, tolerance, stem, topic, difficulty'
        );

        // Load correct options (multichoice / truefalse).
        $correct_options = [];
        if (!empty($questions)) {
            $opt_qids = array_keys($questions);
            [$opt_sql, $opt_params] = $DB->get_in_or_equal($opt_qids, SQL_PARAMS_NAMED, 'oqid');
            $opts = $DB->get_records_select(
                'evalia_question_options',
                "questionid $opt_sql AND is_correct = 1",
                $opt_params,
                'sortorder ASC',
                'questionid, option_text'
            );
            foreach ($opts as $opt) {
                $correct_options[$opt->questionid] = $opt->option_text;
            }
        }

        // Build engine payload.
        $engine_questions = [];
        foreach ($question_ids as $qid) {
            if (!isset($questions[$qid])) {
                continue;
            }
            $q = $questions[$qid];
            $engine_questions[] = [
                'id'                  => (int) $qid,
                'type'                => $q->question_type,
                'difficulty'          => $q->difficulty ?? 'medium',
                'correct_option_text' => $correct_options[$qid] ?? '',
                'correct_answer'      => $q->correct_answer ?? '',
                'tolerance'           => (float) ($q->tolerance ?? 0.0),
                // essay-only fields (ignored by engine for other types)
                'stem'                => $q->question_type === 'essay' ? ($q->stem ?? '') : '',
                'topic'               => $q->question_type === 'essay' ? ($q->topic ?? '') : '',
            ];
        }

        // Decode student answers and normalize keys to strings.
        $student_answers_raw = json_decode($params['answers'], true) ?? [];
        $student_answers = [];
        foreach ((array) $student_answers_raw as $k => $v) {
            $student_answers[(string) $k] = (string) $v;
        }

        $difficulty_weights = [
            'basic'    => (float) (get_config('local_evalia', 'weight_basic')    ?: 1.0),
            'medium'   => (float) (get_config('local_evalia', 'weight_medium')   ?: 2.0),
            'advanced' => (float) (get_config('local_evalia', 'weight_advanced') ?: 3.0),
        ];

        // Essay questions require LLM evaluation — allow more time.
        $has_essay = !empty(array_filter($engine_questions, fn($q) => $q['type'] === 'essay'));
        $timeout   = $has_essay ? 120 : 30;

        // Call engine; fall back to PHP grading if unreachable.
        $engine_resp = local_evalia_engine_request('/exam/grade', [
            'questions' => $engine_questions,
            'answers'   => $student_answers,
            'weights'   => $difficulty_weights,
            'course_id' => $exam->courseid,
        ], $timeout);

        if (isset($engine_resp['error'])) {
            // PHP fallback grading — uses difficulty weights.
            $total_correct = 0.0;
            $total_q       = 0.0;
            foreach ($engine_questions as $eq) {
                $weight       = $difficulty_weights[$eq['difficulty']] ?? 1.0;
                $total_q     += $weight;
                $qid_str      = (string) $eq['id'];
                $student_ans  = strtolower(trim($student_answers[$qid_str] ?? ''));
                $is_correct   = false;
                if ($eq['type'] === 'multichoice' || $eq['type'] === 'truefalse') {
                    $is_correct = ($student_ans === strtolower(trim($eq['correct_option_text'])));
                } elseif ($eq['type'] === 'numerical') {
                    $val        = floatval($student_ans);
                    $is_correct = (abs($val - floatval($eq['correct_answer'])) <= $eq['tolerance']);
                } elseif ($eq['type'] === 'shortanswer') {
                    $is_correct = ($student_ans === strtolower(trim($eq['correct_answer'])));
                } elseif ($eq['type'] === 'essay') {
                    // Cannot grade essays without AI — 0 in PHP fallback.
                    $is_correct = false;
                }
                if ($is_correct) {
                    $total_correct += $weight;
                }
            }
        } else {
            $total_correct = (float) ($engine_resp['total_score'] ?? 0.0);
            $total_q       = (float) ($engine_resp['max_score']   ?? array_sum(
                array_map(fn($eq) => $difficulty_weights[$eq['difficulty']] ?? 1.0, $engine_questions)
            ));
        }

        // Persist essay AI feedback inside the answers JSON so it survives to
        // the Telegram feedback and graded-view rendering steps.
        // Format: answers["__essay_eval__"] = {"qid": {"score": 0.8, "feedback": "..."}}
        $essay_evals = [];
        if (!isset($engine_resp['error']) && !empty($engine_resp['details'])) {
            // Build a weight lookup keyed by question id.
            $weight_by_qid = [];
            foreach ($engine_questions as $eq) {
                $weight_by_qid[(int)$eq['id']] = $difficulty_weights[$eq['difficulty']] ?? 1.0;
            }
            foreach ($engine_resp['details'] as $d) {
                if (!empty($d['feedback'])) {
                    $w = $weight_by_qid[(int)$d['question_id']] ?? 1.0;
                    $essay_evals[(string)$d['question_id']] = [
                        'score'    => round((float)$d['score'] / max(0.001, (float)$w), 3),
                        'feedback' => $d['feedback'],
                    ];
                }
            }
        }

        // Build per-question correctness map (used below for feedback payload).
        $correctness = [];
        if (!isset($engine_resp['error']) && !empty($engine_resp['details'])) {
            foreach ($engine_resp['details'] as $d) {
                $correctness[(int)$d['question_id']] = (bool)$d['correct'];
            }
        } else {
            // PHP fallback path: compute correctness ourselves.
            foreach ($engine_questions as $eq) {
                $qid_str    = (string) $eq['id'];
                $student_ans = strtolower(trim($student_answers[$qid_str] ?? ''));
                if ($eq['type'] === 'multichoice' || $eq['type'] === 'truefalse') {
                    $correctness[(int)$eq['id']] = ($student_ans === strtolower(trim($eq['correct_option_text'])));
                } elseif ($eq['type'] === 'numerical') {
                    $val = floatval($student_ans);
                    $correctness[(int)$eq['id']] = (abs($val - floatval($eq['correct_answer'])) <= $eq['tolerance']);
                } elseif ($eq['type'] === 'shortanswer') {
                    $correctness[(int)$eq['id']] = ($student_ans === strtolower(trim($eq['correct_answer'])));
                } else {
                    // essay and unknown types: cannot grade without AI.
                    $correctness[(int)$eq['id']] = false;
                }
            }
        }

        // Convert to 0–10 scale.
        $score_10  = ($total_q > 0) ? round($total_correct / $total_q * 10.0, 2) : 0.0;
        $percent   = ($total_q > 0) ? round($total_correct / $total_q * 100.0, 1) : 0.0;
        $now       = time();

        // Merge essay AI feedback into the stored answers JSON.
        if (!empty($essay_evals)) {
            $answers_to_store = json_decode($params['answers'], true) ?? [];
            $answers_to_store['__essay_eval__'] = $essay_evals;
            $answers_json = json_encode($answers_to_store);
        } else {
            $answers_json = $params['answers'];
        }

        // Update student exam record.
        $DB->update_record('evalia_student_exams', (object) [
            'id'            => $student_exam->id,
            'score'         => $score_10,
            'max_score'     => 10.0,
            'status'        => 'graded',
            'answers'       => $answers_json,
            'timemodified'  => $now,
            'timesubmitted' => $student_exam->timesubmitted ?: $now,
        ]);

        // Upsert evalia_portfolio.
        $portfolio = $DB->get_record('evalia_portfolio', [
            'userid'   => $student_exam->userid,
            'courseid' => $exam->courseid,
        ]);

        // Re-query all graded scores for this student+course to recompute avg.
        $graded_rows = $DB->get_records_sql(
            'SELECT se.score
               FROM {evalia_student_exams} se
               JOIN {evalia_exams} e ON e.id = se.examid
              WHERE se.userid = :userid AND e.courseid = :courseid AND se.status = :status',
            ['userid' => $student_exam->userid, 'courseid' => $exam->courseid, 'status' => 'graded']
        );
        $all_scores = array_column((array) $graded_rows, 'score');
        $avg_grade  = count($all_scores) > 0
            ? round(array_sum($all_scores) / count($all_scores), 2)
            : $score_10;

        if (!$portfolio) {
            $DB->insert_record('evalia_portfolio', (object) [
                'userid'        => $student_exam->userid,
                'courseid'      => $exam->courseid,
                'total_exams'   => count($all_scores),
                'avg_grade'     => $avg_grade,
                'last_activity' => $now,
                'timecreated'   => $now,
                'timemodified'  => $now,
            ]);
        } else {
            $DB->update_record('evalia_portfolio', (object) [
                'id'            => $portfolio->id,
                'total_exams'   => count($all_scores),
                'avg_grade'     => $avg_grade,
                'last_activity' => $now,
                'timemodified'  => $now,
            ]);
        }

        // Telegram feedback is sent at PUBLISH time (after teacher approval),
        // not here. See publish_grade.php → local_evalia_send_feedback().

        return [
            'success'   => true,
            'score'     => $score_10,
            'max_score' => 10.0,
            'percent'   => $percent,
            'message'   => 'Examen calificado: ' . number_format($score_10, 1) . ' / 10.0',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'   => new external_value(PARAM_BOOL,  'Success flag'),
            'score'     => new external_value(PARAM_FLOAT, 'Score on 0–10 scale'),
            'max_score' => new external_value(PARAM_FLOAT, 'Max score (always 10.0)'),
            'percent'   => new external_value(PARAM_FLOAT, 'Percentage correct (0–100)'),
            'message'   => new external_value(PARAM_TEXT,  'Result message'),
        ]);
    }
}
