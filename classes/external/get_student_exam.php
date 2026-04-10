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
 * WS: get_student_exam — load a student's assigned exam (questions, no correct answers).
 *
 * Only the student who owns the exam can call this WS.
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

class get_student_exam extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'student_examid' => new external_value(PARAM_INT, 'evalia_student_exams ID'),
        ]);
    }

    public static function execute(int $student_examid): array {
        global $DB, $USER;

        $params       = self::validate_parameters(self::execute_parameters(), ['student_examid' => $student_examid]);
        $student_exam = $DB->get_record('evalia_student_exams', ['id' => $params['student_examid']], '*', MUST_EXIST);
        $exam         = $DB->get_record('evalia_exams', ['id' => $student_exam->examid], '*', MUST_EXIST);
        $context      = \context_course::instance($exam->courseid);
        self::validate_context($context);

        // Only the owner or a teacher can load this exam.
        $is_teacher = has_capability('local/evalia:manage', $context);
        $is_owner   = ((int) $USER->id === (int) $student_exam->userid);
        if (!$is_teacher && !$is_owner) {
            throw new \moodle_exception('nopermissions', 'error', '', 'get_student_exam');
        }
        if (!$is_teacher) {
            require_capability('local/evalia:take', $context);
        }

        if ($student_exam->status === 'not_assigned') {
            return [
                'success'        => false,
                'student_examid' => $params['student_examid'],
                'exam_name'      => '',
                'instructions'   => '',
                'time_limit_min' => 0,
                'status'         => 'not_assigned',
                'questions'      => [],
                'message'        => 'No tenés un examen asignado todavía.',
            ];
        }

        // Enforce exam time window for students (teachers bypass this check).
        if (!$is_teacher) {
            $now = time();
            if ((int)$exam->timeopen > 0 && $now < (int)$exam->timeopen) {
                $opens = userdate((int)$exam->timeopen, get_string('strftimedatetimeshort', 'langconfig'));
                return [
                    'success'        => false,
                    'student_examid' => (int)$student_exam->id,
                    'exam_name'      => $exam->name,
                    'instructions'   => '',
                    'time_limit_min' => (int)$exam->time_limit_min,
                    'status'         => $student_exam->status,
                    'questions'      => [],
                    'message'        => 'El examen aún no está disponible. Podés rendirlo desde: ' . $opens,
                ];
            }
            if ((int)$exam->timeclose > 0 && $now > (int)$exam->timeclose) {
                $closed = userdate((int)$exam->timeclose, get_string('strftimedatetimeshort', 'langconfig'));
                return [
                    'success'        => false,
                    'student_examid' => (int)$student_exam->id,
                    'exam_name'      => $exam->name,
                    'instructions'   => '',
                    'time_limit_min' => (int)$exam->time_limit_min,
                    'status'         => $student_exam->status,
                    'questions'      => [],
                    'message'        => 'El período de rendición ya cerró (cerró el ' . $closed . ').',
                ];
            }
        }

        // Load assigned question IDs.
        $question_ids = json_decode($student_exam->question_ids ?? '[]', true);
        if (empty($question_ids)) {
            return [
                'success'        => false,
                'student_examid' => $params['student_examid'],
                'exam_name'      => $exam->name,
                'instructions'   => $exam->instructions ?? '',
                'time_limit_min' => (int) $exam->time_limit_min,
                'status'         => $student_exam->status,
                'questions'      => [],
                'message'        => 'El examen no tiene preguntas asignadas.',
            ];
        }

        // Load questions — exclude correct_answer and tolerance (never send to student).
        [$in_sql, $in_params] = $DB->get_in_or_equal($question_ids, SQL_PARAMS_NAMED, 'qid');
        $questions = $DB->get_records_select(
            'evalia_question_bank',
            "id $in_sql",
            $in_params,
            '',
            'id, stem, question_type, topic, difficulty'
        );

        // Load options for each question — WITHOUT is_correct field.
        $options_by_q = [];
        if (!empty($questions)) {
            $opt_qids = array_keys($questions);
            [$opt_sql, $opt_params] = $DB->get_in_or_equal($opt_qids, SQL_PARAMS_NAMED, 'oqid');
            $options = $DB->get_records_select(
                'evalia_question_options',
                "questionid $opt_sql",
                $opt_params,
                'sortorder ASC',
                'id, questionid, option_text, sortorder'
            );
            foreach ($options as $opt) {
                $options_by_q[$opt->questionid][] = [
                    'id'          => (int) $opt->id,
                    'option_text' => $opt->option_text,
                    'sortorder'   => (int) $opt->sortorder,
                ];
            }
        }

        // Build output preserving the original question order.
        $result_questions = [];
        foreach ($question_ids as $qid) {
            if (!isset($questions[$qid])) {
                continue;
            }
            $q = $questions[$qid];
            $result_questions[] = [
                'id'            => (int) $qid,
                'stem'          => $q->stem,
                'question_type' => $q->question_type,
                'topic'         => $q->topic,
                'difficulty'    => $q->difficulty,
                'options'       => $options_by_q[$qid] ?? [],
            ];
        }

        // Mark as started on first load (if still in assigned state).
        if ($student_exam->status === 'assigned') {
            $DB->update_record('evalia_student_exams', (object) [
                'id'           => $student_exam->id,
                'status'       => 'started',
                'timemodified' => time(),
            ]);
            $student_exam->status = 'started';
        }

        return [
            'success'        => true,
            'student_examid' => (int) $student_exam->id,
            'exam_name'      => $exam->name,
            'instructions'   => $exam->instructions ?? '',
            'time_limit_min' => (int) $exam->time_limit_min,
            'status'         => $student_exam->status,
            'questions'      => $result_questions,
            'message'        => '',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'        => new external_value(PARAM_BOOL,  'Success flag'),
            'student_examid' => new external_value(PARAM_INT,   'Student exam ID'),
            'exam_name'      => new external_value(PARAM_TEXT,  'Exam name'),
            'instructions'   => new external_value(PARAM_RAW,   'Instructions HTML'),
            'time_limit_min' => new external_value(PARAM_INT,   'Time limit in minutes (0 = no limit)'),
            'status'         => new external_value(PARAM_TEXT,  'assigned|started|submitted|graded'),
            'questions'      => new external_multiple_structure(
                new external_single_structure([
                    'id'            => new external_value(PARAM_INT,  'Question ID'),
                    'stem'          => new external_value(PARAM_RAW,  'Question text'),
                    'question_type' => new external_value(PARAM_TEXT, 'multichoice|truefalse|numerical|shortanswer'),
                    'topic'         => new external_value(PARAM_TEXT, 'Topic name'),
                    'difficulty'    => new external_value(PARAM_TEXT, 'basic|medium|advanced'),
                    'options'       => new external_multiple_structure(
                        new external_single_structure([
                            'id'          => new external_value(PARAM_INT,  'Option ID'),
                            'option_text' => new external_value(PARAM_TEXT, 'Option text'),
                            'sortorder'   => new external_value(PARAM_INT,  'Display order'),
                        ]),
                        'Answer options', VALUE_DEFAULT, []
                    ),
                ]),
                'Questions', VALUE_DEFAULT, []
            ),
            'message'        => new external_value(PARAM_TEXT,  'Error or info message'),
        ]);
    }
}
