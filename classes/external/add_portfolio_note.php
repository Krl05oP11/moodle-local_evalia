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
 * WS: add_portfolio_note — add a teacher observation to a student's portfolio.
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

class add_portfolio_note extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid'    => new external_value(PARAM_INT,  'Target student user ID'),
            'courseid'  => new external_value(PARAM_INT,  'Course ID'),
            'note_text' => new external_value(PARAM_TEXT, 'Note text'),
        ]);
    }

    public static function execute(int $userid, int $courseid, string $note_text): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'userid'    => $userid,
            'courseid'  => $courseid,
            'note_text' => $note_text,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $text = trim($params['note_text']);
        if ($text === '') {
            return ['success' => false, 'noteid' => 0, 'timecreated' => 0,
                    'message' => 'La observación no puede estar vacía.'];
        }

        $now    = time();
        $noteid = $DB->insert_record('evalia_portfolio_notes', (object) [
            'userid'      => $params['userid'],
            'courseid'    => $params['courseid'],
            'note_text'   => $text,
            'created_by'  => $USER->id,
            'timecreated' => $now,
        ]);

        // Touch last_activity in portfolio if the record already exists.
        $portfolio = $DB->get_record('evalia_portfolio', [
            'userid'   => $params['userid'],
            'courseid' => $params['courseid'],
        ]);
        if ($portfolio) {
            $DB->update_record('evalia_portfolio', (object) [
                'id'            => $portfolio->id,
                'last_activity' => $now,
                'timemodified'  => $now,
            ]);
        }

        return [
            'success'     => true,
            'noteid'      => (int) $noteid,
            'timecreated' => $now,
            'message'     => 'Observación guardada.',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'     => new external_value(PARAM_BOOL,  'Success flag'),
            'noteid'      => new external_value(PARAM_INT,   'New note record ID'),
            'timecreated' => new external_value(PARAM_INT,   'Creation timestamp'),
            'message'     => new external_value(PARAM_TEXT,  'Result message'),
        ]);
    }
}
