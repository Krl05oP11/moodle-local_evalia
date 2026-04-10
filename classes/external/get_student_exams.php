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
 * WS: get_student_exams — enrolled students with their exam assignment status.
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

class get_student_exams extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'examid' => new external_value(PARAM_INT, 'Exam ID'),
        ]);
    }

    public static function execute(int $examid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['examid' => $examid]);

        $exam = $DB->get_record('evalia_exams', ['id' => $params['examid']], '*', MUST_EXIST);
        $context = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // Get enrolled students.
        $students = get_enrolled_users($context, 'local/evalia:take', 0,
            'u.id, u.firstname, u.lastname, u.email');

        if (empty($students)) {
            return ['total' => 0, 'students' => []];
        }

        $student_ids = array_keys($students);
        [$in_sql, $in_params] = $DB->get_in_or_equal($student_ids, SQL_PARAMS_NAMED, 'uid');

        // Load existing assignments for these students.
        $assignments = $DB->get_records_select(
            'evalia_student_exams',
            "examid = :examid AND userid $in_sql",
            array_merge(['examid' => $exam->id], $in_params),
            '',
            'id, userid, score, max_score, status, timesubmitted'
        );

        $assign_by_user = [];
        foreach ($assignments as $a) {
            $assign_by_user[$a->userid] = $a;
        }

        $result = [];
        foreach ($students as $u) {
            $a = $assign_by_user[$u->id] ?? null;
            $result[] = [
                'userid'         => (int) $u->id,
                'fullname'       => fullname($u),
                'lastname'       => $u->lastname,
                'firstname'      => $u->firstname,
                'student_examid' => $a ? (int) $a->id : 0,
                'status'         => $a ? $a->status : 'not_assigned',
                'score'          => ($a && $a->score !== null) ? (float) $a->score : null,
                'timesubmitted'  => $a ? (int) $a->timesubmitted : 0,
            ];
        }

        // Sort: submitted first (desc by time), then assigned, then not_assigned.
        // Within same status: alphabetical by lastname then firstname using Spanish locale
        // (Á=A, É=E, Ñ after N, etc.)
        $order    = ['submitted' => 0, 'graded' => 1, 'published' => 2, 'started' => 3, 'assigned' => 4, 'not_assigned' => 5];
        $collator = class_exists('Collator') ? (new \Collator('es_AR') ?: new \Collator('es')) : null;
        usort($result, function ($a, $b) use ($order, $collator) {
            $oa = $order[$a['status']] ?? 9;
            $ob = $order[$b['status']] ?? 9;
            if ($oa !== $ob) {
                return $oa <=> $ob;
            }
            $ka = $a['lastname'] . ' ' . $a['firstname'];
            $kb = $b['lastname'] . ' ' . $b['firstname'];
            return $collator ? $collator->compare($ka, $kb) : strcasecmp($ka, $kb);
        });

        return ['total' => count($students), 'students' => $result];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total'    => new external_value(PARAM_INT, 'Total enrolled students'),
            'students' => new external_multiple_structure(
                new external_single_structure([
                    'userid'         => new external_value(PARAM_INT,   'Student user ID'),
                    'fullname'       => new external_value(PARAM_TEXT,  'Student full name'),
                    'lastname'       => new external_value(PARAM_TEXT,  'Last name'),
                    'firstname'      => new external_value(PARAM_TEXT,  'First name'),
                    'student_examid' => new external_value(PARAM_INT,   'evalia_student_exams ID (0 if not assigned)'),
                    'status'         => new external_value(PARAM_TEXT,  'assigned|started|submitted|graded|not_assigned'),
                    'score'          => new external_value(PARAM_FLOAT, 'Score (null if not graded)', VALUE_OPTIONAL),
                    'timesubmitted'  => new external_value(PARAM_INT,   'Submission timestamp (0 if not submitted)'),
                ]),
                'Students', VALUE_DEFAULT, []
            ),
        ]);
    }
}
