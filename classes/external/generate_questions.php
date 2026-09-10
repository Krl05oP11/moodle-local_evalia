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
 * WS: generate_questions — calls /eval/questions/generate for a rubric item
 * && saves results to evalia_question_bank + evalia_question_options.
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
 * Generate_questions.
 */
class generate_questions extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'       => new external_value(PARAM_INT, 'Course ID'),
            'rubric_item_id' => new external_value(PARAM_INT, 'Rubric item ID'),
            'difficulty'     => new external_value(PARAM_TEXT, 'basic|medium|advanced'),
            'question_types' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Question type'),
                'Types: multichoice, truefalse, numerical, shortanswer'
            ),
            'count'          => new external_value(PARAM_INT, 'Number of questions to generate', VALUE_DEFAULT, 5),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(
        int $courseid,
        int $rubricitemid,
        string $difficulty,
        array $questiontypes,
        int $count
    ): array {
        global $CFG;
        require_once($CFG->dirroot . '/local/saipa/lib.php');
        require_once($CFG->dirroot . '/local/evalia/lib.php');
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'       => $courseid,
            'rubric_item_id' => $rubricitemid,
            'difficulty'     => $difficulty,
            'question_types' => $questiontypes,
            'count'          => $count,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // Load rubric item to get topic + description.
        $item = $DB->get_record('evalia_rubric_items', ['id' => $params['rubric_item_id']], '*', MUST_EXIST);

        // Verify item belongs to a rubric for this course.
        $rubric = $DB->get_record(
            'evalia_rubrics',
            ['id' => $item->rubricid, 'courseid' => $params['courseid']],
            'id',
            MUST_EXIST
        );

        // Call the AI engine.
        $payload = [
            'course_id'      => $params['courseid'],
            'topic'          => $item->topic,
            'description'    => $item->description ?? '',
            'difficulty'     => $params['difficulty'],
            'question_types' => $params['question_types'],
            'count'          => $params['count'],
        ];

        $response = local_evalia_engine_request('/questions/generate', $payload, 120);

        if (isset($response['error'])) {
            return ['success' => false, 'generated' => 0, 'message' => $response['error']];
        }

        $rawquestions = $response['questions'] ?? [];
        $now           = time();
        $saved          = 0;

        $validtypes = ['multichoice', 'truefalse', 'numerical', 'shortanswer', 'essay'];

        foreach ($rawquestions as $q) {
            $qtype = $q['type'] ?? 'multichoice';
            if (!in_array($qtype, $validtypes)) {
                $qtype = 'multichoice';
            }

            $questionid = (int) $DB->insert_record('evalia_question_bank', (object) [
                'courseid'       => $params['courseid'],
                'rubricid'       => $rubric->id,
                'rubric_item_id' => $item->id,
                'question_type'  => $qtype,
                'stem'           => $q['stem'] ?? '',
                'difficulty'     => $q['difficulty'] ?? $params['difficulty'],
                'topic'          => $item->topic, // always use rubric item topic for exact-match filtering
                'correct_answer' => $q['correct_answer'] ?? '',
                'tolerance'      => (float) ($q['tolerance'] ?? 0),
                'source_chunks'  => null,
                'status'         => 'draft',
                'timecreated'    => $now,
                'timemodified'   => $now,
            ]);

            // Insert options for multichoice && truefalse.
            if (in_array($qtype, ['multichoice', 'truefalse'])) {
                $options = $q['options'] ?? [];
                foreach ($options as $optidx => $opt) {
                    $DB->insert_record('evalia_question_options', (object) [
                        'questionid'  => $questionid,
                        'option_text' => $opt['text'] ?? '',
                        'is_correct'  => empty($opt['correct']) ? 0 : 1,
                        'feedback'    => $opt['feedback'] ?? '',
                        'sortorder'   => $optidx + 1,
                    ]);
                }
            }

            $saved++;
        }

        return [
            'success'   => true,
            'generated' => $saved,
            'message'   => 'Se generaron ' . $saved . ' pregunta(s) correctamente.',
        ];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'   => new external_value(PARAM_BOOL, 'Whether generation succeeded'),
            'generated' => new external_value(PARAM_INT, 'Number of questions saved'),
            'message'   => new external_value(PARAM_TEXT, 'Status || error message'),
        ]);
    }
}
