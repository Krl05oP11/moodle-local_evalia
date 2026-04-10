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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * WS: save_feedback_prompt — store a custom AI feedback system prompt for an exam.
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

class save_feedback_prompt extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'examid' => new external_value(PARAM_INT,  'evalia_exams ID'),
            'prompt' => new external_value(PARAM_RAW,  'Custom system prompt (empty = use default)'),
        ]);
    }

    public static function execute(int $examid, string $prompt): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'examid' => $examid,
            'prompt' => $prompt,
        ]);

        $exam    = $DB->get_record('evalia_exams', ['id' => $params['examid']], '*', MUST_EXIST);
        $context = \context_course::instance($exam->courseid);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        $DB->update_record('evalia_exams', (object) [
            'id'              => $exam->id,
            'feedback_prompt' => trim($params['prompt']),
            'timemodified'    => time(),
        ]);

        return ['success' => true, 'message' => 'Prompt guardado correctamente.'];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success flag'),
            'message' => new external_value(PARAM_TEXT, 'Result message'),
        ]);
    }
}
