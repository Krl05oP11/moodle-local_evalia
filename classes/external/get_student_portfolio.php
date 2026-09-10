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
 * WS: get_student_portfolio — enrolled students with portfolio summary stats.
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
 * Get_student_portfolio.
 */
class get_student_portfolio extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $courseid): array {
        global $DB;

        $params  = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $namefields = 'u.' . implode(', u.', \core_user\fields::get_name_fields());
        $students = get_enrolled_users(
            $context,
            'local/evalia:take',
            0,
            'u.id, u.email, ' . $namefields
        );

        if (empty($students)) {
            return ['total' => 0, 'students' => []];
        }

        $studentids = array_keys($students);

        // Load portfolio records for all students at once.
        [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'uid');
        $portfolios = $DB->get_records_select(
            'evalia_portfolio',
            "userid $insql AND courseid = :courseid",
            array_merge($inparams, ['courseid' => $params['courseid']]),
            '',
            'userid, total_exams, avg_grade, last_activity'
        );

        $portfoliobyuser = [];
        foreach ($portfolios as $p) {
            $portfoliobyuser[$p->userid] = $p;
        }

        $result = [];
        foreach ($students as $u) {
            $p = $portfoliobyuser[$u->id] ?? null;
            $result[] = [
                'userid'        => (int) $u->id,
                'fullname'      => fullname($u),
                'total_exams'   => $p ? (int) $p->total_exams : 0,
                'avg_grade'     => $p ? (float) $p->avg_grade : 0.0,
                'last_activity' => $p ? (int) $p->last_activity : 0,
                'has_portfolio' => ($p !== null),
            ];
        }

        usort($result, function ($a, $b) {
            return strcmp($a['fullname'], $b['fullname']);
        });

        return ['total' => count($result), 'students' => $result];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total'    => new external_value(PARAM_INT, 'Total enrolled students'),
            'students' => new external_multiple_structure(
                new external_single_structure([
                    'userid'        => new external_value(PARAM_INT, 'Student user ID'),
                    'fullname'      => new external_value(PARAM_TEXT, 'Student full name'),
                    'total_exams'   => new external_value(PARAM_INT, 'Total graded exams'),
                    'avg_grade'     => new external_value(PARAM_FLOAT, 'Average grade (0–10)'),
                    'last_activity' => new external_value(PARAM_INT, 'Last activity timestamp (0 if none)'),
                    'has_portfolio' => new external_value(PARAM_BOOL, 'Whether a portfolio record exists'),
                ]),
                'Students',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }
}
