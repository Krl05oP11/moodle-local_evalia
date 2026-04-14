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
 * WS: get_portfolio_notes — teacher observations for a specific student.
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
 * Get_portfolio_notes.
 */
class get_portfolio_notes extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid'   => new external_value(PARAM_INT, 'Student user ID'),
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $userid, int $courseid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid'   => $userid,
            'courseid' => $courseid,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $notes = $DB->get_records('evalia_portfolio_notes', [
            'userid'   => $params['userid'],
            'courseid' => $params['courseid'],
        ], 'timecreated DESC');

        if (empty($notes)) {
            return ['total' => 0, 'notes' => []];
        }

        // Load author display names in one query.
        $authorids = array_unique(array_column((array) $notes, 'created_by'));
        $authors    = $DB->get_records_list('user', 'id', $authorids, '',
            'id,' . implode(',', \core_user\fields::get_name_fields()));

        $result = [];
        foreach ($notes as $n) {
            $author = $authors[$n->created_by] ?? null;
            $result[] = [
                'id'          => (int) $n->id,
                'note_text'   => $n->note_text,
                'author_name' => $author ? fullname($author) : 'Desconocido',
                'timecreated' => (int) $n->timecreated,
            ];
        }

        return ['total' => count($result), 'notes' => $result];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Total notes'),
            'notes' => new external_multiple_structure(
                new external_single_structure([
                    'id'          => new external_value(PARAM_INT, 'Note ID'),
                    'note_text'   => new external_value(PARAM_TEXT, 'Note text'),
                    'author_name' => new external_value(PARAM_TEXT, 'Author full name'),
                    'timecreated' => new external_value(PARAM_INT, 'Creation timestamp'),
                ]),
                'Notes',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }
}
