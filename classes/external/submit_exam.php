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
 * WS: submit_exam — student submits their answers; status → submitted.
 *
 * Idempotent: if already submitted/graded, returns success without overwriting.
 * Only the student who owns the exam can call this WS.
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
 * Submit_exam.
 */
class submit_exam extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'student_examid' => new external_value(PARAM_INT, 'evalia_student_exams ID'),
            'answers'        => new external_value(PARAM_TEXT, 'JSON: {"question_id": "answer_text", ...}'),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $studentexamid, string $answers): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/local/evalia/lib.php');

        $params = self::validate_parameters(self::execute_parameters(), [
            'student_examid' => $studentexamid,
            'answers'        => $answers,
        ]);

        $studentexam = $DB->get_record('evalia_student_exams', ['id' => $params['student_examid']], '*', MUST_EXIST);
        $exam         = $DB->get_record('evalia_exams', ['id' => $studentexam->examid], '*', MUST_EXIST);
        $context      = \context_course::instance($exam->courseid);
        self::validate_context($context);

        // Only the owner can submit.
        if ((int) $USER->id !== (int) $studentexam->userid) {
            throw new \moodle_exception('nopermissions', 'error', '', 'submit_exam');
        }
        require_capability('local/evalia:take', $context);

        // Idempotent guard — already submitted or graded.
        if (in_array($studentexam->status, ['submitted', 'graded'])) {
            return [
                'success' => true,
                'message' => 'El examen ya fue enviado anteriormente.',
            ];
        }

        // Validate answers JSON.
        $decoded = json_decode($params['answers'], true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => 'El formato de respuestas no es válido.',
            ];
        }

        $now = time();
        $DB->update_record('evalia_student_exams', (object) [
            'id'            => $studentexam->id,
            'answers'       => $params['answers'],
            'status'        => 'submitted',
            'timesubmitted' => $now,
            'timemodified'  => $now,
        ]);

        // ── Notify course teachers via Telegram (non-blocking, best-effort) ──
        try {
            $student  = $DB->get_record('user', ['id' => $studentexam->userid], 'id, firstname, lastname');
            $course   = $DB->get_record('course', ['id' => $exam->courseid], 'id, fullname');
            $studentname = $student ? fullname($student) : 'Un alumno';
            $coursename  = $course ? format_string($course->fullname) : '';
            $examname    = format_string($exam->name);
            $timestr     = userdate($now, get_string('strftimedatetime', 'core_langconfig'));

            $msg = "<b>📝 EVAL-IA — Examen enviado</b>\n\n" .
                   "<b>Alumno:</b> {$studentname}\n" .
                   "<b>Curso:</b> {$coursename}\n" .
                   "<b>Examen:</b> {$examname}\n" .
                   "<b>Hora:</b> {$timestr}\n\n" .
                   "Ingresá al panel docente para calificarlo con IA.";

            // Find all users with manage capability in this course.
            $teacherids = array_keys(get_users_by_capability($context, 'local/evalia:manage', 'u.id'));

            foreach ($teacherids as $tid) {
                $tglink = $DB->get_record('saipa_telegram_links', ['userid' => $tid], 'telegram_id');
                if ($tglink && !empty($tglink->telegram_id)) {
                    local_evalia_engine_request('/notify', [
                        'telegram_id' => (int) $tglink->telegram_id,
                        'message'     => $msg,
                        'parse_mode'  => 'HTML',
                    ], 5);
                }
            }
        } catch (\Throwable $e) {
            // Never block the student's submission over a notification failure.
            debugging("submit_exam notification error: " . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return [
            'success' => true,
            'message' => 'Examen enviado correctamente. El docente revisará tu resultado.',
        ];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'message' => new external_value(PARAM_TEXT, 'Confirmation || error message'),
        ]);
    }
}
