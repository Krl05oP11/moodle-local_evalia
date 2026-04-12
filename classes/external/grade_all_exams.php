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
 * WS: grade_all_exams — batch-grade all submitted student exams for an exam.
 *
 * Reads stored answers from DB (set during submit_exam) && grades each one.
 * Calls the same engine grading + Telegram feedback pipeline as grade_exam.
 * Returns an array of per-student results.
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
 * Grade_all_exams.
 */
class grade_all_exams extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'examid' => new external_value(PARAM_INT, 'evalia_exams ID'),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $examid): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/local/evalia/lib.php');

        // Allow long execution — grading 30+ exams takes time.
        \core_php_time_limit::raise(600);

        $params = self::validate_parameters(self::execute_parameters(), ['examid' => $examid]);
        $examid = (int) $params['examid'];

        $exam    = $DB->get_record('evalia_exams', ['id' => $examid], '*', MUST_EXIST);
        $context = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // Find all submitted (not yet graded) exams.
        $submitted = $DB->get_records(
            'evalia_student_exams',
            ['examid' => $examid, 'status' => 'submitted'],
            'id ASC',
            'id, userid, answers, question_ids'
        );

        if (empty($submitted)) {
            return ['graded' => 0, 'skipped' => 0, 'results' => []];
        }

        $graded  = 0;
        $skipped = 0;
        $results = [];

        foreach ($submitted as $se) {
            // Reuse grade_exam::execute() — it reads answers from its param
            // (and overwrites DB answers field, which is idempotent here).
            $answersjson = $se->answers ?? '{}';
            if (empty($answersjson) || $answersjson === '{}' || $answersjson === '[]') {
                $skipped++;
                $results[] = [
                    'userid'  => (int) $se->userid,
                    'success' => false,
                    'score'   => 0.0,
                    'message' => 'Sin respuestas guardadas.',
                ];
                continue;
            }

            try {
                $result = grade_exam::execute((int) $se->id, $answersjson);
                $results[] = [
                    'userid'  => (int) $se->userid,
                    'success' => (bool) ($result['success'] ?? false),
                    'score'   => (float) ($result['score'] ?? 0.0),
                    'message' => $result['message'] ?? '',
                ];
                if (!empty($result['success'])) {
                    $graded++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $skipped++;
                $results[] = [
                    'userid'  => (int) $se->userid,
                    'success' => false,
                    'score'   => 0.0,
                    'message' => 'Error: ' . $e->getMessage(),
                ];
            }
        }

        return ['graded' => $graded, 'skipped' => $skipped, 'results' => $results];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'graded'  => new external_value(PARAM_INT, 'Number successfully graded'),
            'skipped' => new external_value(PARAM_INT, 'Number skipped / failed'),
            'results' => new external_multiple_structure(
                new external_single_structure([
                    'userid'  => new external_value(PARAM_INT, 'Moodle user ID'),
                    'success' => new external_value(PARAM_BOOL, 'Graded successfully'),
                    'score'   => new external_value(PARAM_FLOAT, 'Score 0–10'),
                    'message' => new external_value(PARAM_TEXT, 'Result message'),
                ])
            ),
        ]);
    }
}
