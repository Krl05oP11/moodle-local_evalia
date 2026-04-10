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
 * WS: publish_all_grades — batch-publish all graded student exams for an exam.
 *
 * Efficiently pushes all grades to Moodle gradebook in a single batch call,
 * then marks every qualifying student_exam as published and updates portfolios.
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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/evalia/lib.php');

class publish_all_grades extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'examid' => new external_value(PARAM_INT, 'Exam ID'),
        ]);
    }

    public static function execute(int $examid): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), ['examid' => $examid]);

        $exam = $DB->get_record('evalia_exams', ['id' => $params['examid']], '*', MUST_EXIST);

        $context = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // Load all graded student exams for this exam.
        $graded = $DB->get_records('evalia_student_exams',
            ['examid' => $exam->id, 'status' => 'graded'],
            '',
            'id, userid, score'
        );

        if (empty($graded)) {
            return ['success' => true, 'published' => 0, 'message' => 'No hay exámenes calificados pendientes de publicación.'];
        }

        // Push each student's grade to the Moodle gradebook.
        // local_evalia_grade_item_update creates the grade item on first call,
        // then updates the individual student grade via update_raw_grade().
        foreach ($graded as $se) {
            local_evalia_grade_item_update(
                $exam->id, $exam->courseid, $exam->name,
                (int) $se->userid,
                (float) max(0, min(10, $se->score ?? 0))
            );
        }

        // Batch-update student_exam status to published.
        $ids = array_keys($graded);
        [$in_sql, $in_params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'seid');
        $DB->execute(
            "UPDATE {evalia_student_exams} SET status = 'published', timemodified = :now WHERE id $in_sql",
            array_merge(['now' => time()], $in_params)
        );

        // Recalculate portfolio for each affected student.
        $now = time();
        $affected_users = array_unique(array_column((array) $graded, 'userid'));
        foreach ($affected_users as $uid) {
            publish_grade::recalculate_portfolio((int) $uid, (int) $exam->courseid, $now, $DB);
        }

        // Send pedagogical feedback to each student (non-blocking — each call returns immediately).
        foreach ($graded as $se) {
            local_evalia_send_feedback((int) $se->id, $DB);
        }

        $count = count($graded);
        return [
            'success'   => true,
            'published' => $count,
            'message'   => $count . ' nota(s) publicada(s) y alumnos notificados por Telegram.',
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success'   => new external_value(PARAM_BOOL, 'Whether publish succeeded'),
            'published' => new external_value(PARAM_INT,  'Number of grades published'),
            'message'   => new external_value(PARAM_TEXT, 'Status or error message'),
        ]);
    }
}
