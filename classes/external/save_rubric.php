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
 * WS: save_rubric — persists manual edits to a rubric && optionally activates it.
 *
 * Items with id > 0 are updated; items with id = 0 are inserted as new.
 * Activating a rubric sets its status to 'active' (prerequisite for question generation).
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
 * Save_rubric.
 */
class save_rubric extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'rubricid' => new external_value(PARAM_INT, 'Rubric ID to update'),
            'name'     => new external_value(PARAM_TEXT, 'Rubric name'),
            'activate' => new external_value(PARAM_BOOL, 'Set status to active', VALUE_DEFAULT, false),
            'items'    => new external_multiple_structure(
                new external_single_structure([
                    'id'                => new external_value(PARAM_INT, 'Item ID (0 for new items)'),
                    'topic'             => new external_value(PARAM_TEXT, 'Topic name'),
                    'description'       => new external_value(PARAM_TEXT, 'Description', VALUE_OPTIONAL),
                    'difficulty_weight' => new external_value(PARAM_TEXT, 'low|medium|high'),
                    'sortorder'         => new external_value(PARAM_INT, 'Display order', VALUE_DEFAULT, 5),
                ]),
                'Items to save'
            ),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $rubricid, string $name, bool $activate, array $items): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'rubricid' => $rubricid,
            'name'     => $name,
            'activate' => $activate,
            'items'    => $items,
        ]);

        $rubric = $DB->get_record('evalia_rubrics', ['id' => $params['rubricid']], '*', MUST_EXIST);
        $context = \context_course::instance($rubric->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $now    = time();
        $status = $params['activate'] ? 'active' : $rubric->status;

        $DB->update_record('evalia_rubrics', (object) [
            'id'           => $rubric->id,
            'name'         => $params['name'],
            'status'       => $status,
            'timemodified' => $now,
        ]);

        // Process items: update existing, insert new.
        foreach ($params['items'] as $idx => $it) {
            $description = $it['description'] ?? '';
            $weight      = in_array($it['difficulty_weight'], ['low', 'medium', 'high'])
                           ? $it['difficulty_weight'] : 'medium';
            $sortorder   = (int) ($it['sortorder'] ?? $idx + 1);

            if ($it['id'] > 0) {
                // Verify item belongs to this rubric before updating.
                $existing = $DB->get_record(
                    'evalia_rubric_items',
                    ['id' => $it['id'], 'rubricid' => $rubric->id]
                );
                if ($existing) {
                    $DB->update_record('evalia_rubric_items', (object) [
                        'id'                => $it['id'],
                        'topic'             => $it['topic'],
                        'description'       => $description,
                        'difficulty_weight' => $weight,
                        'sortorder'         => $sortorder,
                    ]);
                }
            } else {
                $DB->insert_record('evalia_rubric_items', (object) [
                    'rubricid'          => $rubric->id,
                    'topic'             => $it['topic'],
                    'description'       => $description,
                    'difficulty_weight' => $weight,
                    'sortorder'         => $sortorder,
                    'timecreated'       => $now,
                ]);
            }
        }

        $msg = $params['activate']
            ? get_string('rubric_activated', 'local_evalia')
            : get_string('rubric_saved', 'local_evalia');

        return ['success' => true, 'message' => $msg];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether save succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Status || error message'),
        ]);
    }
}
