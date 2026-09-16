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
 * Shared parameter definition and lookup logic for WS classes that operate
 * on a single local_evalia_student_exams record (submit_exam, grade_exam).
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia\external;

use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Student_exam_lookup_trait.
 */
trait student_exam_lookup_trait {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'student_examid' => new external_value(PARAM_INT, 'local_evalia_student_exams ID'),
            'answers'        => new external_value(PARAM_TEXT, 'JSON: {"question_id": "answer_text", ...}'),
        ]);
    }

    /**
     * Validate parameters and load the student exam, its parent exam, and
     * the course context, validating that context along the way.
     *
     * @return array{0: array, 1: \stdClass, 2: \stdClass, 3: \context_course}
     */
    protected static function validate_and_load_student_exam(int $studentexamid, string $answers): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'student_examid' => $studentexamid,
            'answers'        => $answers,
        ]);

        $studentexam = $DB->get_record('local_evalia_student_exams', ['id' => $params['student_examid']], '*', MUST_EXIST);
        $exam        = $DB->get_record('local_evalia_exams', ['id' => $studentexam->examid], '*', MUST_EXIST);
        $context     = \context_course::instance($exam->courseid);
        self::validate_context($context);

        return [$params, $studentexam, $exam, $context];
    }
}
