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
use core_external\external_single_structure;
use core_external\external_value;


/**
 * Submit_exam.
 */
class submit_exam extends external_api {
    use student_exam_lookup_trait;

    /**
     * Execute the web service.
     *
     * @param int $studentexamid Student exam ID (local_evalia_student_exams).
     * @param string $answers JSON object of answers keyed by question ID.
     */
    public static function execute(int $studentexamid, string $answers): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/local/evalia/lib.php');

        [$params, $studentexam, $exam, $context] = self::validate_and_load_student_exam($studentexamid, $answers);

        // Only the owner can submit.
        if ((int) $USER->id !== (int) $studentexam->userid) {
            throw new \moodle_exception('nopermissions', 'error', '', 'submit_exam');
        }
        require_capability('local/evalia:take', $context);

        // Idempotent guard — already submitted or graded.
        if (in_array($studentexam->status, ['submitted', 'graded'])) {
            return [
                'success' => true,
                'message' => get_string('exam:already_submitted', 'local_evalia'),
            ];
        }

        // Validate answers JSON.
        $decoded = json_decode($params['answers'], true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'message' => get_string('exam:invalid_answers', 'local_evalia'),
            ];
        }

        $now = time();
        $DB->update_record('local_evalia_student_exams', (object) [
            'id'            => $studentexam->id,
            'answers'       => $params['answers'],
            'status'        => 'submitted',
            'timesubmitted' => $now,
            'timemodified'  => $now,
        ]);

        // Notify course teachers via Telegram (non-blocking, best-effort).
        try {
            $student  = $DB->get_record(
                'user',
                ['id' => $studentexam->userid],
                'id,' . implode(',', \core_user\fields::get_name_fields())
            );
            $course   = $DB->get_record('course', ['id' => $exam->courseid], 'id, fullname');
            $coursename  = $course ? format_string($course->fullname) : '';
            $examname    = format_string($exam->name);
            $strman      = get_string_manager();

            // Find all users with manage capability in this course.
            $teachers = get_users_by_capability($context, 'local/evalia:manage', 'u.id, u.lang, u.timezone');

            foreach ($teachers as $teacher) {
                $tglink = $DB->get_record('local_saipa_telegram_links', ['userid' => $teacher->id], 'telegram_id');
                if (!$tglink || empty($tglink->telegram_id)) {
                    continue;
                }

                // Build the message in the recipient's language and timezone, not the student's.
                $lang = (!empty($teacher->lang) && $strman->translation_exists($teacher->lang))
                    ? $teacher->lang
                    : $CFG->lang;
                // Month and day names in userdate() follow the current language, so switch to the
                // recipient's language just for the date, then restore it.
                $prevlang = force_current_language($lang);
                try {
                    $timestr = userdate($now, get_string('strftimedatetime', 'core_langconfig'), $teacher->timezone);
                } finally {
                    force_current_language($prevlang);
                }

                $msg = $strman->get_string('notify_exam_submitted', 'local_evalia', (object) [
                    'student' => $student
                        ? fullname($student)
                        : $strman->get_string('notify_student_fallback', 'local_evalia', null, $lang),
                    'course'  => $coursename,
                    'exam'    => $examname,
                    'time'    => $timestr,
                ], $lang);

                local_evalia_engine_request('/notify', [
                    'telegram_id' => (int) $tglink->telegram_id,
                    'message'     => $msg,
                    'parse_mode'  => 'HTML',
                ], 5);
            }
        } catch (\Throwable $e) {
            // Never block the student's submission over a notification failure.
            debugging("submit_exam notification error: " . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return [
            'success' => true,
            'message' => get_string('exam_submitted_ok', 'local_evalia'),
        ];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'message' => new external_value(PARAM_TEXT, 'Confirmation or error message'),
        ]);
    }
}
