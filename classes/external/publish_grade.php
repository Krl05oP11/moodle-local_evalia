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
 * WS: publish_grade — teacher approves a graded exam; pushes grade to Moodle gradebook.
 *
 * Transitions status: graded → published.
 * Optionally overrides the AI-generated score before publishing.
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

class publish_grade extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'student_examid' => new external_value(PARAM_INT,   'evalia_student_exams ID'),
            'override_score' => new external_value(PARAM_FLOAT, 'Optional teacher override (0–10)', VALUE_DEFAULT, -1),
        ]);
    }

    public static function execute(int $student_examid, float $override_score = -1): array {
        global $DB, $CFG;

        $params = self::validate_parameters(self::execute_parameters(), [
            'student_examid' => $student_examid,
            'override_score' => $override_score,
        ]);

        $se   = $DB->get_record('evalia_student_exams', ['id' => $params['student_examid']], '*', MUST_EXIST);
        $exam = $DB->get_record('evalia_exams', ['id' => $se->examid], '*', MUST_EXIST);

        $context = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        if ($se->status !== 'graded') {
            return ['success' => false, 'message' => 'El examen no está en estado "calificado" (estado actual: ' . $se->status . ').'];
        }

        $now = time();

        // Apply teacher override if provided (value >= 0 means intentional override).
        if ($params['override_score'] >= 0) {
            $override = max(0.0, min(10.0, (float) $params['override_score']));
            $DB->set_field('evalia_student_exams', 'score', $override, ['id' => $se->id]);
            $se->score = $override;
        }

        $score = (float) ($se->score ?? 0);

        // Push grade to Moodle gradebook.
        local_evalia_grade_item_update($exam->id, $exam->courseid, $exam->name, (int) $se->userid, $score);

        // Mark as published.
        $DB->update_record('evalia_student_exams', (object) [
            'id'           => $se->id,
            'status'       => 'published',
            'timemodified' => $now,
        ]);

        // Recalculate portfolio.
        self::recalculate_portfolio((int) $se->userid, (int) $exam->courseid, $now, $DB);

        // Send pedagogical feedback to student via Telegram (non-blocking).
        local_evalia_send_feedback((int) $se->id, $DB);

        return ['success' => true, 'message' => 'Nota publicada y alumno notificado por Telegram.'];
    }

    /**
     * Recalculate avg_grade and total_exams for a student's portfolio
     * based only on published exams.
     */
    public static function recalculate_portfolio(int $userid, int $courseid, int $now, \moodle_database $DB): void {
        $sql = 'SELECT AVG(se.score) AS avg_score, COUNT(se.id) AS total
                  FROM {evalia_student_exams} se
                  JOIN {evalia_exams} e ON se.examid = e.id
                 WHERE se.userid   = :userid
                   AND e.courseid  = :courseid
                   AND se.status   = :status';
        $row = $DB->get_record_sql($sql, ['userid' => $userid, 'courseid' => $courseid, 'status' => 'published']);

        $avg   = $row ? round((float) ($row->avg_score ?? 0), 2) : 0.0;
        $total = $row ? (int) ($row->total ?? 0) : 0;

        $existing = $DB->get_record('evalia_portfolio', ['userid' => $userid, 'courseid' => $courseid]);
        if ($existing) {
            $DB->update_record('evalia_portfolio', (object) [
                'id'            => $existing->id,
                'total_exams'   => $total,
                'avg_grade'     => $avg,
                'last_activity' => $now,
                'timemodified'  => $now,
            ]);
        } else {
            $DB->insert_record('evalia_portfolio', (object) [
                'userid'        => $userid,
                'courseid'      => $courseid,
                'total_exams'   => $total,
                'avg_grade'     => $avg,
                'last_activity' => $now,
                'timecreated'   => $now,
                'timemodified'  => $now,
            ]);
        }
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether publish succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Status or error message'),
        ]);
    }
}
