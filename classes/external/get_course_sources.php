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
 * WS: get_course_sources — returns indexed RAG sources for a course with human-readable labels.
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

require_once($CFG->dirroot . '/local/evalia/lib.php');

class get_course_sources extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function execute(int $courseid): array {
        global $DB;

        $params  = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $resp = local_evalia_engine_request('/sources/' . $params['courseid'], null, 10);

        if (isset($resp['error']) || empty($resp['sources'])) {
            return ['sources' => []];
        }

        $sources = [];
        foreach ($resp['sources'] as $raw_source) {
            $sources[] = self::parse_source($raw_source, $params['courseid'], $DB);
        }

        // Sort: pages first, then by label
        usort($sources, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'page' ? -1 : 1;
            }
            return strcmp($a['label'], $b['label']);
        });

        return ['sources' => $sources];
    }

    /**
     * Parse a ChromaDB source string into a human-readable label.
     *
     * Source formats:
     *   page:<page_id>                                → query mdl_page.name
     *   resource:<cmid>:<filename>                    → show filename
     *   resource:<cmid>:<filename>:<chapter_label>    → show chapter_label
     */
    private static function parse_source(string $raw, int $courseid, \moodle_database $DB): array {
        $parts = explode(':', $raw, 4);
        $type  = $parts[0] ?? 'unknown';

        if ($type === 'page' && isset($parts[1])) {
            $page_id = (int) $parts[1];
            $page    = $DB->get_record('page', ['id' => $page_id], 'name', IGNORE_MISSING);
            $label   = $page ? format_string($page->name) : "Página $page_id";
            return ['id' => $raw, 'label' => $label, 'type' => 'page'];
        }

        if ($type === 'resource' && isset($parts[1])) {
            // parts[3] = chapter label (optional), parts[2] = filename
            if (!empty($parts[3])) {
                $label = $parts[3];   // "Cap5-Grafos"
            } elseif (!empty($parts[2])) {
                $label = pathinfo($parts[2], PATHINFO_FILENAME);  // strip extension
            } else {
                $label = $raw;
            }
            return ['id' => $raw, 'label' => $label, 'type' => 'resource'];
        }

        return ['id' => $raw, 'label' => $raw, 'type' => 'unknown'];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'sources' => new external_multiple_structure(
                new external_single_structure([
                    'id'    => new external_value(PARAM_TEXT, 'Raw ChromaDB source string'),
                    'label' => new external_value(PARAM_TEXT, 'Human-readable label'),
                    'type'  => new external_value(PARAM_TEXT, 'page | resource | unknown'),
                ]),
                'Indexed sources', VALUE_DEFAULT, []
            ),
        ]);
    }
}
