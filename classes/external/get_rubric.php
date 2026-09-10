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
 * WS: get_rubric — returns the active (or draft) rubric && its items for a course.
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
 * Get_rubric.
 */
class get_rubric extends external_api {
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

        $rubric = $DB->get_record('evalia_rubrics', ['courseid' => $params['courseid']]);

        if (!$rubric) {
            return [
                'rubricid'     => 0,
                'name'         => '',
                'status'       => 'draft',
                'timecreated'  => 0,
                'timemodified' => 0,
                'items'        => [],
            ];
        }

        $rawitems = $DB->get_records(
            'evalia_rubric_items',
            ['rubricid' => $rubric->id],
            'sortorder ASC, id ASC',
            'id, topic, description, difficulty_weight, sortorder'
        );

        $items = [];
        foreach ($rawitems as $it) {
            $items[] = [
                'id'                => (int) $it->id,
                'topic'             => $it->topic,
                'description'       => $it->description ?? '',
                'difficulty_weight' => $it->difficulty_weight,
                'sortorder'         => (int) $it->sortorder,
            ];
        }

        return [
            'rubricid'     => (int) $rubric->id,
            'name'         => $rubric->name,
            'status'       => $rubric->status,
            'timecreated'  => (int) $rubric->timecreated,
            'timemodified' => (int) $rubric->timemodified,
            'items'        => $items,
        ];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'rubricid'     => new external_value(PARAM_INT, 'Rubric ID (0 if none)'),
            'name'         => new external_value(PARAM_TEXT, 'Rubric name'),
            'status'       => new external_value(PARAM_TEXT, 'draft|active|archived'),
            'timecreated'  => new external_value(PARAM_INT, 'Creation timestamp'),
            'timemodified' => new external_value(PARAM_INT, 'Last modified timestamp'),
            'items'        => new external_multiple_structure(
                new external_single_structure([
                    'id'                => new external_value(PARAM_INT, 'Item ID'),
                    'topic'             => new external_value(PARAM_TEXT, 'Topic name'),
                    'description'       => new external_value(PARAM_TEXT, 'Topic description'),
                    'difficulty_weight' => new external_value(PARAM_TEXT, 'low|medium|high'),
                    'sortorder'         => new external_value(PARAM_INT, 'Display order'),
                ]),
                'Rubric items',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }
}
