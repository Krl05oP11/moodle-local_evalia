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
 * Behat step definitions for local_evalia.
 *
 * @package    local_evalia
 * @category   test
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Page names for the 'I am on the "..." "local_evalia > ..." page' steps.
 *
 * @package    local_evalia
 * @category   test
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_evalia extends behat_base {
    /**
     * Converts page names without an identifier to URLs.
     *
     * Recognised page names are:
     * | Page name    | Description             |
     * | Setup wizard | local/evalia/setup.php  |
     *
     * @param string $page the page name.
     * @return moodle_url the corresponding URL.
     * @throws Exception if the page name is not recognised.
     */
    protected function resolve_page_url(string $page): moodle_url {
        switch (strtolower($page)) {
            case 'setup wizard':
                return new moodle_url('/local/evalia/setup.php');
            default:
                throw new Exception('Unrecognised local_evalia page "' . $page . '".');
        }
    }

    /**
     * Converts page names with a course identifier to URLs.
     *
     * Recognised page types are:
     * | Type          | identifier meaning  | Description                            |
     * | Teacher panel | course shortname/id | local/evalia/teacher.php?courseid=...  |
     * | My exams      | course shortname/id | local/evalia/student.php?courseid=...  |
     *
     * @param string $type the page type.
     * @param string $identifier the course shortname or id.
     * @return moodle_url the corresponding URL.
     * @throws Exception if the page type or course cannot be resolved.
     */
    protected function resolve_page_instance_url(string $type, string $identifier): moodle_url {
        $courseid = $this->get_course_id($identifier);
        if (!$courseid) {
            throw new Exception('The specified course "' . $identifier . '" does not exist');
        }
        switch (strtolower($type)) {
            case 'teacher panel':
                return new moodle_url('/local/evalia/teacher.php', ['courseid' => $courseid]);
            case 'my exams':
                return new moodle_url('/local/evalia/student.php', ['courseid' => $courseid]);
            default:
                throw new Exception('Unrecognised local_evalia page type "' . $type . '".');
        }
    }
}
