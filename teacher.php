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
 * EVAL-IA Teacher Panel — 4 tabs: Rubric, Question Bank, Exams, Portfolios.
 *
 * URL: /local/evalia/teacher.php?courseid=X
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/evalia/lib.php');

// ── First-run wizard redirect ─────────────────────────────────────────────────
// If the AI engine has never been configured && the current user is a site
// admin, redirect to the setup wizard automatically.
if (!get_config('local_evalia', 'setup_complete') && has_capability('moodle/site:config', context_system::instance())) {
    redirect(new moodle_url('/local/evalia/setup.php'));
}

$courseid  = required_param('courseid', PARAM_INT);
$action    = optional_param('action', '', PARAM_ALPHA);
$sectionid = optional_param('sectionid', 0, PARAM_INT);
$startdate = optional_param('startdate', '', PARAM_TEXT);
$enddate   = optional_param('enddate', '', PARAM_TEXT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/evalia:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/evalia/teacher.php', ['courseid' => $courseid]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('teacher_page_title', 'local_evalia'));
$PAGE->set_heading(get_string('teacher_page_heading', 'local_evalia'));

// Query the most recently created exam for this course (if any) so AMD can
// pre-populate Tab 3 without requiring a new exam to be created first.
$latestexam = $DB->get_records_select(
    'evalia_exams',
    'courseid = :courseid',
    ['courseid' => $courseid],
    'id DESC',
    'id, name, status, feedback_prompt',
    0,
    1
);
$latestexam = $latestexam ? reset($latestexam) : null;
$latestexamid = $latestexam ? (int) $latestexam->id : 0;

// Build exam list for the selector dropdown.
$allexams = $DB->get_records_select(
    'evalia_exams',
    'courseid = :courseid',
    ['courseid' => $courseid],
    'id DESC',
    'id, name'
);
$examlist = [];
foreach ($allexams as $ex) {
    $examlist[] = [
        'id'       => (int) $ex->id,
        'name'     => format_string($ex->name),
        'selected' => ((int) $ex->id === $latestexamid),
    ];
}

// Read plugin configuration (with sensible fallbacks).
$evaliacfg = [
    'rubric_default_items'    => (int)   (get_config('local_evalia', 'rubric_default_items') ?: 18),
    'exam_default_basic'      => (int)   (get_config('local_evalia', 'exam_default_basic') ?: 3),
    'exam_default_medium'     => (int)   (get_config('local_evalia', 'exam_default_medium') ?: 4),
    'exam_default_advanced'   => (int)   (get_config('local_evalia', 'exam_default_advanced') ?: 2),
    'exam_default_time_limit'    => (int) (get_config('local_evalia', 'exam_default_time_limit') ?: 60),
    'questions_default_count'    => (int) (get_config('local_evalia', 'questions_default_count') ?: 5),
];

// Load AMD module for the teacher dashboard.
$PAGE->requires->js_call_amd('local_evalia/evalia_teacher', 'init', [[
    'courseid'        => $courseid,
    'examid'          => $latestexamid,
    'feedback_prompt' => $latestexam ? ($latestexam->feedback_prompt ?? '') : '',
    'action'          => $action,
    'sectionid'       => (int) $sectionid,
    'startdate'       => $startdate,
    'enddate'         => $enddate,
    'defaults'        => $evaliacfg,
]]);

$templatedata = [
    'courseid'                  => $courseid,
    'exam_list'                 => $examlist,
    'coursename'                => format_string($course->fullname),
    'rubric_default_items'      => $evaliacfg['rubric_default_items'],
    'exam_default_basic'        => $evaliacfg['exam_default_basic'],
    'exam_default_medium'       => $evaliacfg['exam_default_medium'],
    'exam_default_advanced'     => $evaliacfg['exam_default_advanced'],
    'exam_default_time_limit'   => $evaliacfg['exam_default_time_limit'],
    'tab_rubric'                => get_string('tab_rubric', 'local_evalia'),
    'tab_questions'             => get_string('tab_questions', 'local_evalia'),
    'tab_exams'                 => get_string('tab_exams', 'local_evalia'),
    'tab_portfolio'             => get_string('tab_portfolio', 'local_evalia'),
    'portfolio_students'        => get_string('portfolio_students', 'local_evalia'),
    'portfolio_select_student'  => get_string('portfolio_select_student', 'local_evalia'),
    'portfolio_exam_history'    => get_string('portfolio_exam_history', 'local_evalia'),
    'portfolio_observations'    => get_string('portfolio_observations', 'local_evalia'),
    'portfolio_no_exams'        => get_string('portfolio_no_exams', 'local_evalia'),
    'portfolio_note_empty'      => get_string('portfolio_note_empty', 'local_evalia'),
    'portfolio_loading'         => get_string('portfolio_loading', 'local_evalia'),
    'sesskey'                   => sesskey(),
    'wwwroot'                   => $CFG->wwwroot,
    'student_page_url'          => (new moodle_url('/local/evalia/student.php', ['courseid' => $courseid]))->out(false),

    // Teacher panel static text (templates/evalia_teacher.mustache) — i18n pass.
    'btn_workflow'               => get_string('btn_workflow', 'local_evalia'),
    'material_title'             => get_string('material_title', 'local_evalia'),
    'material_subtitle'          => get_string('material_subtitle', 'local_evalia'),
    'btn_index_material'         => get_string('btn_index_material', 'local_evalia'),
    'material_how_title'         => get_string('material_how_title', 'local_evalia'),
    'material_how_desc'          => get_string('material_how_desc', 'local_evalia'),
    'material_step1'             => get_string('material_step1', 'local_evalia'),
    'material_step2'             => get_string('material_step2', 'local_evalia'),
    'material_step3'             => get_string('material_step3', 'local_evalia'),
    'material_indexing'          => get_string('material_indexing', 'local_evalia'),
    'sources_title'              => get_string('sources_title', 'local_evalia'),
    'sources_subtitle'           => get_string('sources_subtitle', 'local_evalia'),
    'sources_loading'            => get_string('sources_loading', 'local_evalia'),
    'sources_select_all'         => get_string('sources_select_all', 'local_evalia'),
    'sources_select_none'        => get_string('sources_select_none', 'local_evalia'),
    'rubric_scope_placeholder'   => get_string('rubric_scope_placeholder', 'local_evalia'),
    'rubric_item_count_title'    => get_string('rubric_item_count_title', 'local_evalia'),
    'rubric_generate'            => get_string('rubric_generate', 'local_evalia'),
    'rubric_save'                => get_string('rubric_save', 'local_evalia'),
    'rubric_activate'            => get_string('rubric_activate', 'local_evalia'),
    'rubric_generating'          => get_string('rubric_generating', 'local_evalia'),
    'questions_generate'         => get_string('questions_generate', 'local_evalia'),
    'filter_all_topics'          => get_string('filter_all_topics', 'local_evalia'),
    'filter_all_difficulties'    => get_string('filter_all_difficulties', 'local_evalia'),
    'difficulty_basic'           => get_string('difficulty_basic', 'local_evalia'),
    'difficulty_medium'          => get_string('difficulty_medium', 'local_evalia'),
    'difficulty_advanced'        => get_string('difficulty_advanced', 'local_evalia'),
    'filter_all_statuses'        => get_string('filter_all_statuses', 'local_evalia'),
    'questions_status_draft'     => get_string('questions_status_draft', 'local_evalia'),
    'questions_status_approved'  => get_string('questions_status_approved', 'local_evalia'),
    'questions_status_rejected'  => get_string('questions_status_rejected', 'local_evalia'),
    'questions_loading'          => get_string('questions_loading', 'local_evalia'),
    'exam_create'                => get_string('exam_create', 'local_evalia'),
    'exam_name'                  => get_string('exam_name', 'local_evalia'),
    'exam_name_placeholder'      => get_string('exam_name_placeholder', 'local_evalia'),
    'exam_instructions'          => get_string('exam_instructions', 'local_evalia'),
    'exam_instructions_placeholder' => get_string('exam_instructions_placeholder', 'local_evalia'),
    'exam_basic_count'           => get_string('exam_basic_count', 'local_evalia'),
    'exam_medium_count'          => get_string('exam_medium_count', 'local_evalia'),
    'exam_advanced_count'        => get_string('exam_advanced_count', 'local_evalia'),
    'exam_time_limit'            => get_string('exam_time_limit', 'local_evalia'),
    'exam_window_title'          => get_string('exam_window_title', 'local_evalia'),
    'label_optional'             => get_string('label_optional', 'local_evalia'),
    'exam_window_open_label'     => get_string('exam_window_open_label', 'local_evalia'),
    'exam_window_close_label'    => get_string('exam_window_close_label', 'local_evalia'),
    'exam_window_hint'           => get_string('exam_window_hint', 'local_evalia'),
    'student_link_label'         => get_string('student_link_label', 'local_evalia'),
    'btn_copy'                   => get_string('btn_copy', 'local_evalia'),
    'label_students'             => get_string('label_students', 'local_evalia'),
    'exam_selector_none'         => get_string('exam_selector_none', 'local_evalia'),
    'btn_publish_grades'         => get_string('btn_publish_grades', 'local_evalia'),
    'btn_grade_all'              => get_string('btn_grade_all', 'local_evalia'),
    'exam_assign_all'            => get_string('exam_assign_all', 'local_evalia'),
    'exam_assigning'             => get_string('exam_assigning', 'local_evalia'),
    'exam_none_created'          => get_string('exam_none_created', 'local_evalia'),
    'stats_title'                => get_string('stats_title', 'local_evalia'),
    'stats_distribution_title'   => get_string('stats_distribution_title', 'local_evalia'),
    'stats_failed_title'         => get_string('stats_failed_title', 'local_evalia'),
    'feedback_prompt_title'      => get_string('feedback_prompt_title', 'local_evalia'),
    'btn_reset_prompt'           => get_string('btn_reset_prompt', 'local_evalia'),
    'feedback_prompt_desc'       => get_string('feedback_prompt_desc', 'local_evalia'),
    'btn_save_prompt'            => get_string('btn_save_prompt', 'local_evalia'),
    'btn_download_csv_title'     => get_string('btn_download_csv_title', 'local_evalia'),
    'portfolio_export_csv'       => get_string('portfolio_export_csv', 'local_evalia'),
    'btn_reload_title'           => get_string('btn_reload_title', 'local_evalia'),
    'note_placeholder'           => get_string('note_placeholder', 'local_evalia'),
    'btn_save_note'              => get_string('btn_save_note', 'local_evalia'),
    'workflow_modal_title'       => get_string('workflow_modal_title', 'local_evalia'),
    'btn_close'                  => get_string('btn_close', 'local_evalia'),
    'workflow_step1_title'       => get_string('workflow_step1_title', 'local_evalia'),
    'workflow_step1_desc'        => get_string('workflow_step1_desc', 'local_evalia'),
    'workflow_step2_title'       => get_string('workflow_step2_title', 'local_evalia'),
    'workflow_step2_desc'        => get_string('workflow_step2_desc', 'local_evalia'),
    'workflow_step3_title'       => get_string('workflow_step3_title', 'local_evalia'),
    'workflow_step3_desc'        => get_string('workflow_step3_desc', 'local_evalia'),
    'workflow_step4_title'       => get_string('workflow_step4_title', 'local_evalia'),
    'workflow_step4_desc'        => get_string('workflow_step4_desc', 'local_evalia'),
    'workflow_step5_title'       => get_string('workflow_step5_title', 'local_evalia'),
    'workflow_step5_desc'        => get_string('workflow_step5_desc', 'local_evalia'),
    'workflow_step6_title'       => get_string('workflow_step6_title', 'local_evalia'),
    'workflow_step6_desc'        => get_string('workflow_step6_desc', 'local_evalia'),
    'workflow_step7_title'       => get_string('tab_portfolio', 'local_evalia'),
    'workflow_step7_desc'        => get_string('workflow_step7_desc', 'local_evalia'),
    'fab_title'                  => get_string('fab_title', 'local_evalia'),
    'fab_aria_label'             => get_string('fab_aria_label', 'local_evalia'),
    'chat_welcome_msg'           => get_string('chat_welcome_msg', 'local_evalia'),
    'chat_input_placeholder'     => get_string('chat_input_placeholder', 'local_evalia'),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_evalia/evalia_teacher', $templatedata);
echo $OUTPUT->footer();
