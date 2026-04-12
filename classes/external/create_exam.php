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
 * WS: create_exam — creates an exam template with difficulty distribution.
 *
 * The exam template defines HOW many questions of each difficulty level
 * will be sampled per student. Actual sampling happens in assign_exam.
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



/**
 * Create_exam.
 */
class create_exam extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'       => new external_value(PARAM_INT, 'Course ID'),
            'rubricid'       => new external_value(PARAM_INT, 'Rubric ID'),
            'name'           => new external_value(PARAM_TEXT, 'Exam name'),
            'instructions'   => new external_value(PARAM_TEXT, 'Instructions for students', VALUE_DEFAULT, ''),
            'basic_count'    => new external_value(PARAM_INT, 'Number of basic questions', VALUE_DEFAULT, 3),
            'medium_count'   => new external_value(PARAM_INT, 'Number of medium questions', VALUE_DEFAULT, 4),
            'advanced_count' => new external_value(PARAM_INT, 'Number of advanced questions', VALUE_DEFAULT, 2),
            'time_limit_min' => new external_value(PARAM_INT, 'Time limit in minutes', VALUE_DEFAULT, 60),
            'timeopen'       => new external_value(PARAM_INT, 'Unix timestamp when exam opens (0=always)', VALUE_DEFAULT, 0),
            'timeclose'      => new external_value(PARAM_INT, 'Unix timestamp when exam closes (0=never)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(
        int $courseid,
        int $rubricid,
        string $name,
        string $instructions,
        int $basiccount,
        int $mediumcount,
        int $advancedcount,
        int $timelimitmin,
        int $timeopen = 0,
        int $timeclose = 0
    ): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/local/evalia/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'       => $courseid,
            'rubricid'       => $rubricid,
            'name'           => $name,
            'instructions'   => $instructions,
            'basic_count'    => $basiccount,
            'medium_count'   => $mediumcount,
            'advanced_count' => $advancedcount,
            'time_limit_min' => $timelimitmin,
            'timeopen'       => $timeopen,
            'timeclose'      => $timeclose,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // Verify rubric exists && belongs to this course.
        $DB->get_record(
            'evalia_rubrics',
            ['id' => $params['rubricid'], 'courseid' => $params['courseid']],
            'id',
            MUST_EXIST
        );

        // Validate counts.
        $total = $params['basic_count'] + $params['medium_count'] + $params['advanced_count'];
        if ($total < 1) {
            return ['success' => false, 'examid' => 0, 'message' => 'El examen debe tener al menos 1 pregunta.'];
        }

        $now    = time();
        $examid = (int) $DB->insert_record('evalia_exams', (object) [
            'courseid'       => $params['courseid'],
            'rubricid'       => $params['rubricid'],
            'name'           => $params['name'],
            'instructions'   => $params['instructions'],
            'basic_count'    => $params['basic_count'],
            'medium_count'   => $params['medium_count'],
            'advanced_count' => $params['advanced_count'],
            'topic_coverage' => null,
            'time_limit_min' => $params['time_limit_min'],
            'timeopen'       => $params['timeopen'],
            'timeclose'      => $params['timeclose'],
            'grade_itemid'   => 0,
            'status'         => 'draft',
            'created_by'     => (int) $USER->id,
            'timecreated'    => $now,
            'timemodified'   => $now,
        ]);

        // Register a grade item in the Moodle gradebook for this exam.
        $gradeitemid = local_evalia_grade_item_update($examid, $params['courseid'], $params['name']);
        if ($gradeitemid > 0) {
            $DB->set_field('evalia_exams', 'grade_itemid', $gradeitemid, ['id' => $examid]);
        }

        return ['success' => true, 'examid' => $examid, 'message' => 'Examen creado correctamente.'];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether creation succeeded'),
            'examid'  => new external_value(PARAM_INT, 'New exam ID (0 on failure)'),
            'message' => new external_value(PARAM_TEXT, 'Status || error message'),
        ]);
    }
}
