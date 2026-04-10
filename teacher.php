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
// If the AI engine has never been configured and the current user is a site
// admin, redirect to the setup wizard automatically.
if (!get_config('local_evalia', 'setup_complete') && has_capability('moodle/site:config', context_system::instance())) {
    redirect(new moodle_url('/local/evalia/setup.php'));
}

$courseid  = required_param('courseid', PARAM_INT);
$action    = optional_param('action',    '',  PARAM_ALPHA);
$sectionid = optional_param('sectionid', 0,   PARAM_INT);
$startdate = optional_param('startdate', '',  PARAM_TEXT);
$enddate   = optional_param('enddate',   '',  PARAM_TEXT);

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
$latest_exam = $DB->get_records_select(
    'evalia_exams',
    'courseid = :courseid',
    ['courseid' => $courseid],
    'id DESC',
    'id, name, status, feedback_prompt',
    0, 1
);
$latest_exam = $latest_exam ? reset($latest_exam) : null;
$latest_examid = $latest_exam ? (int) $latest_exam->id : 0;

// Build exam list for the selector dropdown.
$all_exams = $DB->get_records_select(
    'evalia_exams',
    'courseid = :courseid',
    ['courseid' => $courseid],
    'id DESC',
    'id, name'
);
$exam_list = [];
foreach ($all_exams as $ex) {
    $exam_list[] = [
        'id'       => (int) $ex->id,
        'name'     => format_string($ex->name),
        'selected' => ((int) $ex->id === $latest_examid),
    ];
}

// Read plugin configuration (with sensible fallbacks).
$evalia_cfg = [
    'rubric_default_items'    => (int)   (get_config('local_evalia', 'rubric_default_items')    ?: 18),
    'exam_default_basic'      => (int)   (get_config('local_evalia', 'exam_default_basic')      ?: 3),
    'exam_default_medium'     => (int)   (get_config('local_evalia', 'exam_default_medium')     ?: 4),
    'exam_default_advanced'   => (int)   (get_config('local_evalia', 'exam_default_advanced')   ?: 2),
    'exam_default_time_limit'    => (int) (get_config('local_evalia', 'exam_default_time_limit')    ?: 60),
    'questions_default_count'    => (int) (get_config('local_evalia', 'questions_default_count')    ?: 5),
];

// Load AMD module for the teacher dashboard.
$PAGE->requires->js_call_amd('local_evalia/evalia_teacher', 'init', [[
    'courseid'        => $courseid,
    'examid'          => $latest_examid,
    'feedback_prompt' => $latest_exam ? ($latest_exam->feedback_prompt ?? '') : '',
    'action'          => $action,
    'sectionid'       => (int) $sectionid,
    'startdate'       => $startdate,
    'enddate'         => $enddate,
    'defaults'        => $evalia_cfg,
]]);

$templatedata = [
    'courseid'                  => $courseid,
    'exam_list'                 => $exam_list,
    'coursename'                => format_string($course->fullname),
    'rubric_default_items'      => $evalia_cfg['rubric_default_items'],
    'exam_default_basic'        => $evalia_cfg['exam_default_basic'],
    'exam_default_medium'       => $evalia_cfg['exam_default_medium'],
    'exam_default_advanced'     => $evalia_cfg['exam_default_advanced'],
    'exam_default_time_limit'   => $evalia_cfg['exam_default_time_limit'],
    'tab_rubric'                => get_string('tab_rubric',    'local_evalia'),
    'tab_questions'             => get_string('tab_questions', 'local_evalia'),
    'tab_exams'                 => get_string('tab_exams',     'local_evalia'),
    'tab_portfolio'             => get_string('tab_portfolio', 'local_evalia'),
    'portfolio_students'        => get_string('portfolio_students',       'local_evalia'),
    'portfolio_select_student'  => get_string('portfolio_select_student', 'local_evalia'),
    'portfolio_exam_history'    => get_string('portfolio_exam_history',   'local_evalia'),
    'portfolio_observations'    => get_string('portfolio_observations',   'local_evalia'),
    'portfolio_no_exams'        => get_string('portfolio_no_exams',       'local_evalia'),
    'portfolio_note_empty'      => get_string('portfolio_note_empty',     'local_evalia'),
    'portfolio_loading'         => get_string('portfolio_loading',        'local_evalia'),
    'sesskey'                   => sesskey(),
    'wwwroot'                   => $CFG->wwwroot,
    'student_page_url'          => (new moodle_url('/local/evalia/student.php', ['courseid' => $courseid]))->out(false),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_evalia/evalia_teacher', $templatedata);
echo $OUTPUT->footer();
