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
 * Web service function definitions for local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [

    // ---- RUBRIC ----

    'local_evalia_generate_rubric' => [
        'classname'     => 'local_evalia\external\generate_rubric',
        'methodname'    => 'execute',
        'description'   => 'Calls saipa-engine /eval/rubric/generate and saves the result to evalia_rubrics',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_get_rubric' => [
        'classname'     => 'local_evalia\external\get_rubric',
        'methodname'    => 'execute',
        'description'   => 'Returns the active rubric and its items for a given course',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_save_rubric' => [
        'classname'     => 'local_evalia\external\save_rubric',
        'methodname'    => 'execute',
        'description'   => 'Saves manual edits to a rubric and its items, optionally activating it',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    // ---- QUESTION BANK ----

    'local_evalia_generate_questions' => [
        'classname'     => 'local_evalia\external\generate_questions',
        'methodname'    => 'execute',
        'description'   => 'Calls saipa-engine /eval/questions/generate for a rubric item and saves results',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_get_question_bank' => [
        'classname'     => 'local_evalia\external\get_question_bank',
        'methodname'    => 'execute',
        'description'   => 'Returns questions from the bank with optional filters (topic, difficulty, status)',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_update_question' => [
        'classname'     => 'local_evalia\external\update_question',
        'methodname'    => 'execute',
        'description'   => 'Edits a question stem/options or changes its status (approved/rejected)',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    // ---- EXAMS ----

    'local_evalia_create_exam' => [
        'classname'     => 'local_evalia\external\create_exam',
        'methodname'    => 'execute',
        'description'   => 'Creates an exam template (difficulty distribution + topic coverage)',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_assign_exam' => [
        'classname'     => 'local_evalia\external\assign_exam',
        'methodname'    => 'execute',
        'description'   => 'Samples unique question sets per student via saipa-engine and creates evalia_student_exams rows',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_get_student_exams' => [
        'classname'     => 'local_evalia\external\get_student_exams',
        'methodname'    => 'execute',
        'description'   => 'Returns enrolled students with their exam assignment status for a course',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    // ---- PORTFOLIOS (Fase 2) ----

    'local_evalia_get_student_portfolio' => [
        'classname'     => 'local_evalia\external\get_student_portfolio',
        'methodname'    => 'execute',
        'description'   => 'Returns enrolled students with portfolio summary stats for Tab 4',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_get_student_exam_history' => [
        'classname'     => 'local_evalia\external\get_student_exam_history',
        'methodname'    => 'execute',
        'description'   => 'Returns all exams for a student in a course (for Legajos detail panel)',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_get_portfolio_notes' => [
        'classname'     => 'local_evalia\external\get_portfolio_notes',
        'methodname'    => 'execute',
        'description'   => 'Returns teacher observations for a specific student in a course',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_add_portfolio_note' => [
        'classname'     => 'local_evalia\external\add_portfolio_note',
        'methodname'    => 'execute',
        'description'   => 'Adds a teacher observation to a student portfolio',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_get_student_exam' => [
        'classname'     => 'local_evalia\external\get_student_exam',
        'methodname'    => 'execute',
        'description'   => 'Returns a student\'s assigned exam questions (no correct answers exposed)',
        'type'          => 'write',   // marks exam as started on first load
        'ajax'          => true,
        'capabilities'  => 'local/evalia:take',
        'loginrequired' => true,
    ],

    'local_evalia_submit_exam' => [
        'classname'     => 'local_evalia\external\submit_exam',
        'methodname'    => 'execute',
        'description'   => 'Student submits their exam answers; status → submitted',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:take',
        'loginrequired' => true,
    ],

    'local_evalia_grade_exam' => [
        'classname'     => 'local_evalia\external\grade_exam',
        'methodname'    => 'execute',
        'description'   => 'Grades a submitted student exam via saipa-engine and updates portfolio',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_grade_all_exams' => [
        'classname'     => 'local_evalia\external\grade_all_exams',
        'methodname'    => 'execute',
        'description'   => 'Batch-grades all submitted student exams for a given exam ID',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_save_feedback_prompt' => [
        'classname'     => 'local_evalia\external\save_feedback_prompt',
        'methodname'    => 'execute',
        'description'   => 'Save custom AI feedback system prompt for an exam',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    // ---- GRADE PUBLISHING ----

    'local_evalia_publish_grade' => [
        'classname'     => 'local_evalia\external\publish_grade',
        'methodname'    => 'execute',
        'description'   => 'Teacher approves a graded exam: pushes grade to Moodle gradebook and marks as published',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    'local_evalia_publish_all_grades' => [
        'classname'     => 'local_evalia\external\publish_all_grades',
        'methodname'    => 'execute',
        'description'   => 'Batch-publishes all graded student exams for a given exam ID',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    // ---- STATISTICS ----

    'local_evalia_get_exam_stats' => [
        'classname'     => 'local_evalia\external\get_exam_stats',
        'methodname'    => 'execute',
        'description'   => 'Returns aggregate statistics for an exam: score distribution, most-failed questions',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    // ---- RAG SOURCES ----

    'local_evalia_get_course_sources' => [
        'classname'     => 'local_evalia\external\get_course_sources',
        'methodname'    => 'execute',
        'description'   => 'Returns indexed RAG source identifiers with human-readable labels for a course',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

    // ---- RAG INDEXING ----

    'local_evalia_index_course' => [
        'classname'     => 'local_evalia\external\index_course',
        'methodname'    => 'execute',
        'description'   => 'Crawls course pages and PDF resources, indexes them in ChromaDB for RAG',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'local/evalia:manage',
        'loginrequired' => true,
    ],

];

$services = [
    'evalia_service' => [
        'functions'       => array_keys($functions),
        'restrictedusers' => 0,
        'enabled'         => 1,
        'shortname'       => 'evalia_service',
    ],
];
