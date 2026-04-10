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
 * WS: update_question — edits a question stem/status.
 *
 * Called from the Question Bank tab to approve, reject, or edit a question.
 * The caller must have local/evalia:manage in the course context.
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

class update_question extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'questionid' => new external_value(PARAM_INT,  'Question ID'),
            'status'     => new external_value(PARAM_TEXT, 'New status: draft|approved|rejected', VALUE_OPTIONAL),
            'stem'       => new external_value(PARAM_TEXT, 'Updated question text', VALUE_OPTIONAL),
        ]);
    }

    public static function execute(int $questionid, string $status = '', string $stem = ''): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'questionid' => $questionid,
            'status'     => $status,
            'stem'       => $stem,
        ]);

        $question = $DB->get_record('evalia_question_bank', ['id' => $params['questionid']], '*', MUST_EXIST);

        // Verify the caller has manage capability in the question's course.
        $context = \context_course::instance($question->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $update = (object) [
            'id'           => $question->id,
            'timemodified' => time(),
        ];

        $valid_statuses = ['draft', 'approved', 'rejected'];
        if ($params['status'] !== '' && in_array($params['status'], $valid_statuses)) {
            $update->status = $params['status'];
        }

        if ($params['stem'] !== '') {
            $update->stem = $params['stem'];
        }

        $DB->update_record('evalia_question_bank', $update);

        return ['success' => true, 'message' => 'Pregunta actualizada.'];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether update succeeded'),
            'message' => new external_value(PARAM_TEXT, 'Status or error message'),
        ]);
    }
}
