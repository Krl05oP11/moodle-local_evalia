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
 * WS: grade_exam — grade a submitted student exam && update portfolio.
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


/**
 * Grade_exam.
 */
class grade_exam extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'student_examid' => new external_value(PARAM_INT, 'evalia_student_exams ID'),
            'answers'        => new external_value(PARAM_TEXT, 'JSON: {"question_id": "answer_text", ...}'),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $studentexamid, string $answers): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/local/evalia/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'student_examid' => $studentexamid,
            'answers'        => $answers,
        ]);

        $studentexam = $DB->get_record('evalia_student_exams', ['id' => $params['student_examid']], '*', MUST_EXIST);
        $exam         = $DB->get_record('evalia_exams', ['id' => $studentexam->examid], '*', MUST_EXIST);
        $context      = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        if ($studentexam->status === 'graded') {
            return [
                'success'   => false,
                'score'     => (float) $studentexam->score,
                'max_score' => 10.0,
                'percent'   => round((float) $studentexam->score * 10.0, 1),
                'message'   => 'Este examen ya fue calificado.',
            ];
        }

        // Load assigned question IDs.
        $questionids = json_decode($studentexam->question_ids ?? '[]', true);
        if (empty($questionids)) {
            return ['success' => false, 'score' => 0.0, 'max_score' => 10.0, 'percent' => 0.0,
                    'message' => 'El examen no tiene preguntas asignadas.'];
        }

        // Load question bank records (stem + topic needed for feedback message).
        [$insql, $inparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid');
        $questions = $DB->get_records_select(
            'evalia_question_bank',
            "id $insql",
            $inparams,
            '',
            'id, question_type, correct_answer, tolerance, stem, topic, difficulty'
        );

        // Load correct options (multichoice / truefalse).
        $correctoptions = [];
        if (!empty($questions)) {
            $optqids = array_keys($questions);
            [$optsql, $optparams] = $DB->get_in_or_equal($optqids, SQL_PARAMS_NAMED, 'oqid');
            $opts = $DB->get_records_select(
                'evalia_question_options',
                "questionid $optsql AND is_correct = 1",
                $optparams,
                'sortorder ASC',
                'questionid, option_text'
            );
            foreach ($opts as $opt) {
                $correctoptions[$opt->questionid] = $opt->option_text;
            }
        }

        // Build engine payload.
        $enginequestions = [];
        foreach ($questionids as $qid) {
            if (!isset($questions[$qid])) {
                continue;
            }
            $q = $questions[$qid];
            $enginequestions[] = [
                'id'                  => (int) $qid,
                'type'                => $q->question_type,
                'difficulty'          => $q->difficulty ?? 'medium',
                'correct_option_text' => $correctoptions[$qid] ?? '',
                'correct_answer'      => $q->correct_answer ?? '',
                'tolerance'           => (float) ($q->tolerance ?? 0.0),
                // essay-only fields (ignored by engine for other types)
                'stem'                => $q->question_type === 'essay' ? ($q->stem ?? '') : '',
                'topic'               => $q->question_type === 'essay' ? ($q->topic ?? '') : '',
            ];
        }

        // Decode student answers && normalize keys to strings.
        $studentanswersraw = json_decode($params['answers'], true) ?? [];
        $studentanswers = [];
        foreach ((array) $studentanswersraw as $k => $v) {
            $studentanswers[(string) $k] = (string) $v;
        }

        $difficultyweights = [
            'basic'    => (float) (get_config('local_evalia', 'weight_basic') ?: 1.0),
            'medium'   => (float) (get_config('local_evalia', 'weight_medium') ?: 2.0),
            'advanced' => (float) (get_config('local_evalia', 'weight_advanced') ?: 3.0),
        ];

        // Essay questions require LLM evaluation — allow more time.
        $hasessay = !empty(array_filter($enginequestions, fn($q) => $q['type'] === 'essay'));
        $timeout   = $hasessay ? 120 : 30;

        // Call engine; fall back to PHP grading if unreachable.
        $engineresp = local_evalia_engine_request('/exam/grade', [
            'questions' => $enginequestions,
            'answers'   => $studentanswers,
            'weights'   => $difficultyweights,
            'course_id' => $exam->courseid,
        ], $timeout);

        if (isset($engineresp['error'])) {
            // PHP fallback grading — uses difficulty weights.
            $totalcorrect = 0.0;
            $totalq       = 0.0;
            foreach ($enginequestions as $eq) {
                $weight       = $difficultyweights[$eq['difficulty']] ?? 1.0;
                $totalq     += $weight;
                $qidstr      = (string) $eq['id'];
                $studentans  = strtolower(trim($studentanswers[$qidstr] ?? ''));
                $iscorrect   = false;
                if ($eq['type'] === 'multichoice' || $eq['type'] === 'truefalse') {
                    $iscorrect = ($studentans === strtolower(trim($eq['correct_option_text'])));
                } else if ($eq['type'] === 'numerical') {
                    $val        = floatval($studentans);
                    $iscorrect = (abs($val - floatval($eq['correct_answer'])) <= $eq['tolerance']);
                } else if ($eq['type'] === 'shortanswer') {
                    $iscorrect = ($studentans === strtolower(trim($eq['correct_answer'])));
                } else if ($eq['type'] === 'essay') {
                    // Cannot grade essays without AI — 0 in PHP fallback.
                    $iscorrect = false;
                }
                if ($iscorrect) {
                    $totalcorrect += $weight;
                }
            }
        } else {
            $totalcorrect = (float) ($engineresp['total_score'] ?? 0.0);
            $totalq       = (float) ($engineresp['max_score'] ?? array_sum(
                array_map(fn($eq) => $difficultyweights[$eq['difficulty']] ?? 1.0, $enginequestions)
            ));
        }

        // Persist essay AI feedback inside the answers JSON so it survives to
        // the Telegram feedback && graded-view rendering steps.
        // Format: answers["__essay_eval__"] = {"qid": {"score": 0.8, "feedback": "..."}}
        $essayevals = [];
        if (!isset($engineresp['error']) && !empty($engineresp['details'])) {
            // Build a weight lookup keyed by question id.
            $weightbyqid = [];
            foreach ($enginequestions as $eq) {
                $weightbyqid[(int)$eq['id']] = $difficultyweights[$eq['difficulty']] ?? 1.0;
            }
            foreach ($engineresp['details'] as $d) {
                if (!empty($d['feedback'])) {
                    $w = $weightbyqid[(int)$d['question_id']] ?? 1.0;
                    $essayevals[(string)$d['question_id']] = [
                        'score'    => round((float)$d['score'] / max(0.001, (float)$w), 3),
                        'feedback' => $d['feedback'],
                    ];
                }
            }
        }

        // Build per-question correctness map (used below for feedback payload).
        $correctness = [];
        if (!isset($engineresp['error']) && !empty($engineresp['details'])) {
            foreach ($engineresp['details'] as $d) {
                $correctness[(int)$d['question_id']] = (bool)$d['correct'];
            }
        } else {
            // PHP fallback path: compute correctness ourselves.
            foreach ($enginequestions as $eq) {
                $qidstr    = (string) $eq['id'];
                $studentans = strtolower(trim($studentanswers[$qidstr] ?? ''));
                if ($eq['type'] === 'multichoice' || $eq['type'] === 'truefalse') {
                    $correctness[(int)$eq['id']] = ($studentans === strtolower(trim($eq['correct_option_text'])));
                } else if ($eq['type'] === 'numerical') {
                    $val = floatval($studentans);
                    $correctness[(int)$eq['id']] = (abs($val - floatval($eq['correct_answer'])) <= $eq['tolerance']);
                } else if ($eq['type'] === 'shortanswer') {
                    $correctness[(int)$eq['id']] = ($studentans === strtolower(trim($eq['correct_answer'])));
                } else {
                    // essay && unknown types: cannot grade without AI.
                    $correctness[(int)$eq['id']] = false;
                }
            }
        }

        // Convert to 0–10 scale.
        $score10  = ($totalq > 0) ? round($totalcorrect / $totalq * 10.0, 2) : 0.0;
        $percent   = ($totalq > 0) ? round($totalcorrect / $totalq * 100.0, 1) : 0.0;
        $now       = time();

        // Merge essay AI feedback into the stored answers JSON.
        if (!empty($essayevals)) {
            $answerstostore = json_decode($params['answers'], true) ?? [];
            $answerstostore['__essay_eval__'] = $essayevals;
            $answersjson = json_encode($answerstostore);
        } else {
            $answersjson = $params['answers'];
        }

        // Update student exam record.
        $DB->update_record('evalia_student_exams', (object) [
            'id'            => $studentexam->id,
            'score'         => $score10,
            'max_score'     => 10.0,
            'status'        => 'graded',
            'answers'       => $answersjson,
            'timemodified'  => $now,
            'timesubmitted' => $studentexam->timesubmitted ?: $now,
        ]);

        // Upsert evalia_portfolio.
        $portfolio = $DB->get_record('evalia_portfolio', [
            'userid'   => $studentexam->userid,
            'courseid' => $exam->courseid,
        ]);

        // Re-query all graded scores for this student+course to recompute avg.
        $gradedrows = $DB->get_records_sql(
            'SELECT se.score
               FROM {evalia_student_exams} se
               JOIN {evalia_exams} e ON e.id = se.examid
              WHERE se.userid = :userid AND e.courseid = :courseid AND se.status = :status',
            ['userid' => $studentexam->userid, 'courseid' => $exam->courseid, 'status' => 'graded']
        );
        $allscores = array_column((array) $gradedrows, 'score');
        $avggrade  = count($allscores) > 0
            ? round(array_sum($allscores) / count($allscores), 2)
            : $score10;

        if (!$portfolio) {
            $DB->insert_record('evalia_portfolio', (object) [
                'userid'        => $studentexam->userid,
                'courseid'      => $exam->courseid,
                'total_exams'   => count($allscores),
                'avg_grade'     => $avggrade,
                'last_activity' => $now,
                'timecreated'   => $now,
                'timemodified'  => $now,
            ]);
        } else {
            $DB->update_record('evalia_portfolio', (object) [
                'id'            => $portfolio->id,
                'total_exams'   => count($allscores),
                'avg_grade'     => $avggrade,
                'last_activity' => $now,
                'timemodified'  => $now,
            ]);
        }

        // Telegram feedback is sent at PUBLISH time (after teacher approval),
        // not here. See publish_grade.php → local_evalia_send_feedback().

        return [
            'success'   => true,
            'score'     => $score10,
            'max_score' => 10.0,
            'percent'   => $percent,
            'message'   => 'Examen calificado: ' . number_format($score10, 1) . ' / 10.0',
        ];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'   => new external_value(PARAM_BOOL, 'Success flag'),
            'score'     => new external_value(PARAM_FLOAT, 'Score on 0–10 scale'),
            'max_score' => new external_value(PARAM_FLOAT, 'Max score (always 10.0)'),
            'percent'   => new external_value(PARAM_FLOAT, 'Percentage correct (0–100)'),
            'message'   => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
