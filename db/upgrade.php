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
 * Upgrade steps for local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for local_evalia.
 *
 * @param int $oldversion The previous version of the plugin.
 * @return bool True on success.
 */
function xmldb_local_evalia_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    // Fase 1 initial install — all tables are created via install.xml on first install.
    // This block runs only when upgrading from a pre-2026032901 installation.
    if ($oldversion < 2026032901) {
        // evalia_rubrics
        if (!$dbman->table_exists('evalia_rubrics')) {
            $table = new xmldb_table('evalia_rubrics');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'draft');
            $table->add_field('created_by', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('uq_courseid', XMLDB_KEY_UNIQUE, ['courseid']);
            $dbman->create_table($table);
        }

        // evalia_rubric_items
        if (!$dbman->table_exists('evalia_rubric_items')) {
            $table = new xmldb_table('evalia_rubric_items');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('rubricid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('topic', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('difficulty_weight', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'medium');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '5');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // evalia_question_bank
        if (!$dbman->table_exists('evalia_question_bank')) {
            $table = new xmldb_table('evalia_question_bank');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('rubricid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('rubric_item_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('question_type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
            $table->add_field('stem', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('difficulty', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, 'medium');
            $table->add_field('topic', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('correct_answer', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('tolerance', XMLDB_TYPE_NUMBER, '8', null, null, null, '0', 4);
            $table->add_field('source_chunks', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('status', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, 'draft');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('idx_courseid_difficulty_status', XMLDB_INDEX_NOTUNIQUE, ['courseid', 'difficulty', 'status']);
            $dbman->create_table($table);
        }

        // evalia_question_options
        if (!$dbman->table_exists('evalia_question_options')) {
            $table = new xmldb_table('evalia_question_options');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('questionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('option_text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('is_correct', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('feedback', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '5');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // evalia_exams
        if (!$dbman->table_exists('evalia_exams')) {
            $table = new xmldb_table('evalia_exams');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('rubricid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('instructions', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('basic_count', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '3');
            $table->add_field('medium_count', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '4');
            $table->add_field('advanced_count', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '2');
            $table->add_field('topic_coverage', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('time_limit_min', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '60');
            $table->add_field('status', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, 'draft');
            $table->add_field('created_by', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // evalia_student_exams
        if (!$dbman->table_exists('evalia_student_exams')) {
            $table = new xmldb_table('evalia_student_exams');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('examid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('question_ids', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('answers', XMLDB_TYPE_TEXT, null, null, null);
            $table->add_field('score', XMLDB_TYPE_NUMBER, '5', null, null, null, '0', 2);
            $table->add_field('max_score', XMLDB_TYPE_NUMBER, '5', null, XMLDB_NOTNULL, null, '10', 2);
            $table->add_field('status', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, 'assigned');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timesubmitted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('uq_exam_user', XMLDB_KEY_UNIQUE, ['examid', 'userid']);
            $dbman->create_table($table);
        }

        // evalia_portfolio (Fase 2 — created now, populated later)
        if (!$dbman->table_exists('evalia_portfolio')) {
            $table = new xmldb_table('evalia_portfolio');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('total_exams', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('avg_grade', XMLDB_TYPE_NUMBER, '5', null, XMLDB_NOTNULL, null, '0', 2);
            $table->add_field('last_activity', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('uq_user_course', XMLDB_KEY_UNIQUE, ['userid', 'courseid']);
            $dbman->create_table($table);
        }

        // evalia_portfolio_notes (Fase 2)
        if (!$dbman->table_exists('evalia_portfolio_notes')) {
            $table = new xmldb_table('evalia_portfolio_notes');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('note_text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('created_by', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // evalia_feedback_log (Fase 2)
        if (!$dbman->table_exists('evalia_feedback_log')) {
            $table = new xmldb_table('evalia_feedback_log');
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('examid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('channel', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
            $table->add_field('message_text', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL);
            $table->add_field('timesent', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
            $table->add_field('status', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'sent');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026032901, 'local', 'evalia');
    }

    if ($oldversion < 2026040101) {
        // Phase 2A: Portfolio Tab — 4 new WS, Tab 4 UI.
        // No DB schema changes: evalia_portfolio, evalia_portfolio_notes,
        // && evalia_feedback_log were already created in 2026032901.
        upgrade_plugin_savepoint(true, 2026040101, 'local', 'evalia');
    }

    if ($oldversion < 2026040102) {
        // Phase 2B: Student exam UI, Telegram feedback, CSV export.
        // No DB schema changes needed.
        upgrade_plugin_savepoint(true, 2026040102, 'local', 'evalia');
    }

    if ($oldversion < 2026040201) {
        // Add feedback_prompt column to evalia_exams.
        // Allows teachers to customise the AI feedback message per course/exam.
        $table = new xmldb_table('evalia_exams');
        $field = new xmldb_field('feedback_prompt', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026040201, 'local', 'evalia');
    }

    if ($oldversion < 2026040901) {
        // Fase 2.1: Scheduled availability + Moodle gradebook integration.
        // Add timeopen, timeclose, grade_itemid to evalia_exams.
        $table = new xmldb_table('evalia_exams');

        $fieldtimeopen = new xmldb_field('timeopen', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'time_limit_min');
        if (!$dbman->field_exists($table, $fieldtimeopen)) {
            $dbman->add_field($table, $fieldtimeopen);
        }

        $fieldtimeclose = new xmldb_field('timeclose', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timeopen');
        if (!$dbman->field_exists($table, $fieldtimeclose)) {
            $dbman->add_field($table, $fieldtimeclose);
        }

        $fieldgradeitemid = new xmldb_field('grade_itemid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timeclose');
        if (!$dbman->field_exists($table, $fieldgradeitemid)) {
            $dbman->add_field($table, $fieldgradeitemid);
        }

        upgrade_plugin_savepoint(true, 2026040901, 'local', 'evalia');
    }

    if ($oldversion < 2026040902) {
        // Fase 2.2: Teacher approval workflow.
        // No schema changes: evalia_student_exams.status CHAR(15) already accommodates 'published'.
        // Two new WS registered in services.php (local_evalia_publish_grade, local_evalia_publish_all_grades).
        upgrade_plugin_savepoint(true, 2026040902, 'local', 'evalia');
    }

    if ($oldversion < 2026040903) {
        // Fase 2.3: Notificaciones al alumno al publicar.
        // Telegram feedback moved from grade_exam to publish_grade/publish_all_grades.
        // No schema changes.
        upgrade_plugin_savepoint(true, 2026040903, 'local', 'evalia');
    }

    if ($oldversion < 2026040904) {
        // Fase 2.4: Moodle Calendar integration.
        // local_evalia_create_exam_calendar_event() added to lib.php.
        // Called from assign_exam.php when timeopen > 0.
        // No schema changes.
        upgrade_plugin_savepoint(true, 2026040904, 'local', 'evalia');
    }

    return true;
}
