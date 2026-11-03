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
 * Event observers for local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia\event;

/**
 * Observer.
 */
class observer {
    /**
     * Handles course_content_deleted: the course is being deleted, or emptied before a restore.
     *
     * @param \core\event\course_content_deleted $event The triggered event.
     */
    public static function course_content_deleted(\core\event\course_content_deleted $event): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/evalia/lib.php');
        local_evalia_delete_course_data((int) $event->courseid);
    }
}
