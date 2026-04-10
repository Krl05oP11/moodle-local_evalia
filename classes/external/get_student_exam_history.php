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
 * WS: get_student_exam_history — returns all exams for a student in a course.
 *
 * Used by the Legajos panel to show a student's exam history.
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

class get_student_exam_history extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid'   => new external_value(PARAM_INT, 'Student user ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function execute(int $userid, int $courseid): array {
        global $DB;

        $params  = self::validate_parameters(self::execute_parameters(), [
            'userid'   => $userid,
            'courseid' => $courseid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $sql = 'SELECT se.id, se.examid, e.name AS exam_name,
                       se.score, se.max_score, se.status,
                       se.timesubmitted, se.timemodified,
                       se.question_ids
                  FROM {evalia_student_exams} se
                  JOIN {evalia_exams} e ON e.id = se.examid
                 WHERE se.userid = :userid
                   AND e.courseid = :courseid
              ORDER BY se.timemodified DESC';

        $rows = $DB->get_records_sql($sql, [
            'userid'   => $params['userid'],
            'courseid' => $params['courseid'],
        ]);

        $status_labels = [
            'assigned'  => 'Asignado',
            'started'   => 'En progreso',
            'submitted' => 'Enviado',
            'graded'    => 'Calificado',
        ];

        $exams = [];
        foreach ($rows as $row) {
            $qids  = json_decode($row->question_ids ?? '[]', true);
            $score = ($row->status === 'graded' && $row->max_score > 0)
                   ? round((float) $row->score, 1)
                   : -1.0;   // -1 = not graded

            $exams[] = [
                'student_examid'  => (int) $row->id,
                'examid'          => (int) $row->examid,
                'exam_name'       => (string) $row->exam_name,
                'score'           => $score,
                'max_score'       => (float) ($row->max_score > 0 ? $row->max_score : 10.0),
                'status'          => (string) $row->status,
                'status_label'    => $status_labels[$row->status] ?? $row->status,
                'total_questions' => count($qids),
                'timesubmitted'   => (int) ($row->timesubmitted ?? 0),
                'timegraded'      => ($row->status === 'graded') ? (int) $row->timemodified : 0,
            ];
        }

        return ['exams' => $exams, 'total' => count($exams)];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Number of exams'),
            'exams' => new external_multiple_structure(
                new external_single_structure([
                    'student_examid'  => new external_value(PARAM_INT,   'Student exam record ID'),
                    'examid'          => new external_value(PARAM_INT,   'Exam ID'),
                    'exam_name'       => new external_value(PARAM_TEXT,  'Exam name'),
                    'score'           => new external_value(PARAM_FLOAT, 'Score 0-10, -1 if not graded'),
                    'max_score'       => new external_value(PARAM_FLOAT, 'Max score (10.0)'),
                    'status'          => new external_value(PARAM_TEXT,  'Status key'),
                    'status_label'    => new external_value(PARAM_TEXT,  'Status in Spanish'),
                    'total_questions' => new external_value(PARAM_INT,   'Number of questions'),
                    'timesubmitted'   => new external_value(PARAM_INT,   'Unix timestamp of submission'),
                    'timegraded'      => new external_value(PARAM_INT,   'Unix timestamp of grading, 0 if not graded'),
                ])
            ),
        ]);
    }
}
