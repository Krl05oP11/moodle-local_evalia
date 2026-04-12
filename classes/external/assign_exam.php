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
 * WS: assign_exam — samples a unique question set per student via saipa-engine
 * && inserts one row in evalia_student_exams per student.
 *
 * Anti-copying guarantee: each student gets a different random sample drawn
 * independently by the engine's pure-Python sampler.
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
 * Assign_exam.
 */
class assign_exam extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'examid' => new external_value(PARAM_INT, 'Exam template ID to assign'),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $examid): array {
        global $CFG;
        require_once($CFG->dirroot . '/local/saipa/lib.php');
        require_once($CFG->dirroot . '/local/evalia/lib.php');
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), ['examid' => $examid]);

        $exam = $DB->get_record('evalia_exams', ['id' => $params['examid']], '*', MUST_EXIST);
        $context = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // Load all APPROVED questions for this course with metadata needed by the sampler.
        $bank = $DB->get_records_select(
            'evalia_question_bank',
            'courseid = :courseid AND status = :status',
            ['courseid' => $exam->courseid, 'status' => 'approved'],
            '',
            'id, difficulty, topic'
        );

        if (empty($bank)) {
            return [
                'success'  => false,
                'assigned' => 0,
                'skipped'  => 0,
                'message'  => get_string('error_not_enough_bank', 'local_evalia'),
            ];
        }

        // Build question pool for the engine sampler.
        $questionpool = [];
        foreach ($bank as $q) {
            $questionpool[] = [
                'id'         => (int) $q->id,
                'difficulty' => $q->difficulty,
                'topic'      => $q->topic,
            ];
        }

        // Get enrolled students.
        $students = get_enrolled_users($context, 'local/evalia:take', 0, 'u.id');

        if (empty($students)) {
            return [
                'success'  => false,
                'assigned' => 0,
                'skipped'  => 0,
                'message'  => 'No hay alumnos inscriptos con capacidad de rendir exámenes.',
            ];
        }

        // Check which students already have an assignment for this exam.
        $studentids = array_keys($students);
        [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'uid');
        $existing = $DB->get_records_select(
            'evalia_student_exams',
            "examid = :examid AND userid $insql",
            array_merge(['examid' => $exam->id], $inparams),
            '',
            'userid'
        );
        $alreadyassigned = array_keys($existing);

        $now       = time();
        $assigned  = 0;
        $skipped   = 0;
        $maxscore = (float) ($exam->basic_count + $exam->medium_count + $exam->advanced_count);

        // Topic coverage from exam template (may be null).
        $topiccoverage = json_decode($exam->topic_coverage ?? 'null', true) ?? [];

        $samplepayload = [
            'questions'      => $questionpool,
            'basic_count'    => (int) $exam->basic_count,
            'medium_count'   => (int) $exam->medium_count,
            'advanced_count' => (int) $exam->advanced_count,
            'topic_coverage' => (object) $topiccoverage, // JSON object, not array
        ];

        foreach ($studentids as $userid) {
            if (in_array($userid, $alreadyassigned)) {
                $skipped++;
                continue;
            }

            // Each call to the engine produces an independent random sample.
            $sample = local_evalia_engine_request('/exam/sample', $samplepayload, 30);

            if (isset($sample['error']) || empty($sample['selected_ids'])) {
                // Log && skip this student — don't abort the whole batch.
                debugging('assign_exam: sampling failed for userid=' . $userid .
                          ': ' . ($sample['error'] ?? 'empty result'), DEBUG_DEVELOPER);
                $skipped++;
                continue;
            }

            $DB->insert_record('evalia_student_exams', (object) [
                'examid'        => $exam->id,
                'userid'        => $userid,
                'question_ids'  => json_encode($sample['selected_ids']),
                'answers'       => null,
                'score'         => null,
                'max_score'     => $maxscore,
                'status'        => 'assigned',
                'timecreated'   => $now,
                'timemodified'  => $now,
                'timesubmitted' => 0,
            ]);

            $assigned++;
        }

        // Activate the exam template once at least one student was assigned.
        if ($assigned > 0 && $exam->status === 'draft') {
            $DB->set_field('evalia_exams', 'status', 'active', ['id' => $exam->id]);
            $DB->set_field('evalia_exams', 'timemodified', $now, ['id' => $exam->id]);
        }

        // Create a course calendar event if the exam has a scheduled time window.
        if ($assigned > 0 && (int) $exam->timeopen > 0) {
            local_evalia_create_exam_calendar_event($exam, $DB);
        }

        $msg = 'Asignados: ' . $assigned . '. Omitidos (ya tenían examen): ' . $skipped . '.';

        return [
            'success'  => $assigned > 0,
            'assigned' => $assigned,
            'skipped'  => $skipped,
            'message'  => $msg,
        ];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'  => new external_value(PARAM_BOOL, 'Whether assignment succeeded'),
            'assigned' => new external_value(PARAM_INT, 'Students who received a new exam'),
            'skipped'  => new external_value(PARAM_INT, 'Students skipped (already had exam)'),
            'message'  => new external_value(PARAM_TEXT, 'Status || error message'),
        ]);
    }
}
