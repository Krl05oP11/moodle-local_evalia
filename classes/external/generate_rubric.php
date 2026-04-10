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
 * WS: generate_rubric — calls /eval/rubric/generate and saves to evalia_rubrics.
 *
 * One rubric per course (UNIQUE constraint on courseid).
 * If a rubric already exists it is updated in-place and reset to draft.
 * Existing items are wiped and replaced by the newly generated ones.
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

require_once($CFG->dirroot . '/local/saipa/lib.php');
require_once($CFG->dirroot . '/local/evalia/lib.php');

class generate_rubric extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'      => new external_value(PARAM_INT,  'Course ID'),
            'scope'         => new external_value(PARAM_TEXT, 'Optional topic scope for partial exams', VALUE_DEFAULT, ''),
            'item_count'    => new external_value(PARAM_INT,  'Number of rubric items to generate', VALUE_DEFAULT, 18),
            'source_filter' => new external_value(PARAM_TEXT, 'JSON array of source IDs to restrict RAG context', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(int $courseid, string $scope = '', int $item_count = 18, string $source_filter = ''): array {
        global $DB, $USER;

        $params  = self::validate_parameters(self::execute_parameters(), [
            'courseid'      => $courseid,
            'scope'         => $scope,
            'item_count'    => max(5, min(40, $item_count)),
            'source_filter' => $source_filter,
        ]);
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // Decode source filter (empty string or '[]' → no filter).
        $sources = [];
        if (!empty($params['source_filter'])) {
            $decoded = json_decode($params['source_filter'], true);
            if (is_array($decoded) && !empty($decoded)) {
                $sources = $decoded;
            }
        }

        // Call the AI engine.
        $engine_payload = [
            'course_id'  => $params['courseid'],
            'scope'      => $params['scope'],
            'item_count' => $params['item_count'],
        ];
        if (!empty($sources)) {
            $engine_payload['source_filter'] = $sources;
        }
        $response = local_evalia_engine_request('/rubric/generate', $engine_payload, 90);

        if (isset($response['error'])) {
            return ['success' => false, 'rubricid' => 0, 'message' => $response['error'], 'items' => []];
        }

        $now  = time();
        $name = $response['name'] ?? ('Rúbrica del curso ' . $params['courseid']);
        $desc = $response['description'] ?? '';
        $raw_items = $response['items'] ?? [];

        // Upsert evalia_rubrics (one per course).
        $existing = $DB->get_record('evalia_rubrics', ['courseid' => $params['courseid']]);

        if ($existing) {
            $rubricid = (int) $existing->id;
            $DB->update_record('evalia_rubrics', (object) [
                'id'           => $rubricid,
                'name'         => $name,
                'description'  => $desc,
                'status'       => 'draft',
                'timemodified' => $now,
            ]);
            // Remove old items — they will be replaced.
            $DB->delete_records('evalia_rubric_items', ['rubricid' => $rubricid]);
        } else {
            $rubricid = (int) $DB->insert_record('evalia_rubrics', (object) [
                'courseid'     => $params['courseid'],
                'name'         => $name,
                'description'  => $desc,
                'status'       => 'draft',
                'created_by'   => (int) $USER->id,
                'timecreated'  => $now,
                'timemodified' => $now,
            ]);
        }

        // Insert new items.
        $saved_items = [];
        foreach ($raw_items as $idx => $it) {
            $itemid = (int) $DB->insert_record('evalia_rubric_items', (object) [
                'rubricid'          => $rubricid,
                'topic'             => $it['topic'] ?? 'Tema ' . ($idx + 1),
                'description'       => $it['description'] ?? '',
                'difficulty_weight' => $it['difficulty_weight'] ?? 'medium',
                'sortorder'         => $it['sortorder'] ?? ($idx + 1),
                'timecreated'       => $now,
            ]);
            $saved_items[] = [
                'id'                => $itemid,
                'topic'             => $it['topic'] ?? '',
                'description'       => $it['description'] ?? '',
                'difficulty_weight' => $it['difficulty_weight'] ?? 'medium',
                'sortorder'         => (int) ($it['sortorder'] ?? $idx + 1),
            ];
        }

        return [
            'success'  => true,
            'rubricid' => $rubricid,
            'message'  => 'Rúbrica generada correctamente.',
            'items'    => $saved_items,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'  => new external_value(PARAM_BOOL, 'Whether generation succeeded'),
            'rubricid' => new external_value(PARAM_INT,  'ID of the created/updated rubric'),
            'message'  => new external_value(PARAM_TEXT, 'Status or error message'),
            'items'    => new external_multiple_structure(
                new external_single_structure([
                    'id'                => new external_value(PARAM_INT,  'Item ID'),
                    'topic'             => new external_value(PARAM_TEXT, 'Topic name'),
                    'description'       => new external_value(PARAM_TEXT, 'Topic description'),
                    'difficulty_weight' => new external_value(PARAM_TEXT, 'low|medium|high'),
                    'sortorder'         => new external_value(PARAM_INT,  'Display order'),
                ]),
                'Rubric items', VALUE_DEFAULT, []
            ),
        ]);
    }
}
