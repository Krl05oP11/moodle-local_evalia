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
 * English language strings for local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin metadata
$string['pluginname']   = 'EVAL-IA';
$string['nav_my_exams'] = 'My Exams';
$string['plugindescription'] = 'Intelligent AI-powered evaluation for Moodle courses. Generates rubrics, question banks, and unique exams per student using RAG from course materials.';

// Capabilities
$string['evalia:manage'] = 'Manage EVAL-IA evaluations (teacher)';
$string['evalia:take']   = 'Take EVAL-IA exams (student)';

// Page titles
$string['teacher_page_title']   = 'EVAL-IA — Teacher Panel';
$string['teacher_page_heading'] = 'EVAL-IA: Intelligent Evaluation';

// Tabs
$string['tab_rubric']    = 'Rubric';
$string['tab_questions'] = 'Question Bank';
$string['tab_exams']     = 'Exams';
$string['tab_portfolio'] = 'Portfolios';

// Rubric section
$string['rubric_generate']         = 'Generate Rubric with AI';
$string['rubric_generating']       = 'Generating rubric from course materials...';
$string['rubric_save']             = 'Save Rubric';
$string['rubric_activate']         = 'Activate';
$string['rubric_status_draft']     = 'Draft';
$string['rubric_status_active']    = 'Active';
$string['rubric_status_archived']  = 'Archived';
$string['rubric_no_content']       = 'No course content indexed yet. Please index this course in SAIPA first.';
$string['rubric_saved']            = 'Rubric saved successfully.';
$string['rubric_activated']        = 'Rubric activated. You can now generate questions.';
$string['rubric_item_topic']       = 'Topic';
$string['rubric_item_description'] = 'Description';
$string['rubric_item_weight']      = 'Difficulty weight';
$string['weight_low']              = 'Low';
$string['weight_medium']           = 'Medium';
$string['weight_high']             = 'High';

// Question bank section
$string['questions_generate']         = 'Generate Questions';
$string['questions_generating']       = 'Generating questions...';
$string['questions_approve']          = 'Approve';
$string['questions_reject']           = 'Reject';
$string['questions_edit']             = 'Edit';
$string['questions_generate_more']    = 'Generate More';
$string['questions_filter_topic']     = 'Filter by topic';
$string['questions_filter_difficulty'] = 'Filter by difficulty';
$string['questions_filter_status']    = 'Filter by status';
$string['questions_status_draft']     = 'Pending review';
$string['questions_status_approved']  = 'Approved';
$string['questions_status_rejected']  = 'Rejected';
$string['difficulty_basic']           = 'Basic';
$string['difficulty_medium']          = 'Medium';
$string['difficulty_advanced']        = 'Advanced';
$string['qtype_multichoice']          = 'Multiple choice';
$string['qtype_truefalse']            = 'True/False';
$string['qtype_numerical']            = 'Numerical';
$string['qtype_shortanswer']          = 'Short answer';
$string['questions_none']             = 'No questions in the bank yet. Activate a rubric first, then generate questions per item.';

// Exams section
$string['exam_create']          = 'Create Exam';
$string['exam_name']            = 'Exam name';
$string['exam_instructions']    = 'Instructions for students';
$string['exam_basic_count']     = 'Basic questions';
$string['exam_medium_count']    = 'Medium questions';
$string['exam_advanced_count']  = 'Advanced questions';
$string['exam_time_limit']      = 'Time limit (minutes)';
$string['exam_assign_all']      = 'Assign to all students';
$string['exam_assigning']       = 'Assigning unique exams per student...';
$string['exam_assigned']        = 'Exams assigned. Each student received a unique question set.';
$string['exam_status_assigned'] = 'Assigned';
$string['exam_status_started']  = 'In progress';
$string['exam_status_submitted'] = 'Submitted';
$string['exam_status_graded']   = 'Graded';
$string['exam_not_enough_questions'] = 'Not enough approved questions in the bank to create this exam. Approve more questions first.';

// Portfolio (Phase 2)
$string['portfolio_coming_soon']    = 'Portfolios — Coming in Phase 2';
$string['portfolio_description']    = 'Student portfolios with exam history, grades, and teacher observations will be available here.';
$string['portfolio_students']       = 'Student Portfolios';
$string['portfolio_select_student'] = 'Select a student from the list to view their portfolio.';
$string['portfolio_exam_history']   = 'Exam History';
$string['portfolio_observations']   = 'Observations';
$string['portfolio_no_students']    = 'No students enrolled in this course.';
$string['portfolio_no_exams']       = 'No graded exams yet.';
$string['portfolio_note_saved']     = 'Observation saved.';
$string['portfolio_note_empty']     = 'No observations recorded.';
$string['portfolio_avg_grade']      = 'Avg. grade';
$string['portfolio_total_exams']    = 'Exams';
$string['portfolio_last_activity']  = 'Last activity';
$string['portfolio_grade_now']      = 'Grade';
$string['portfolio_loading']        = 'Loading portfolios...';

// Student exam (Phase 2B)
$string['student_exam_title']    = 'EVAL-IA — Take Exam';
$string['exam_submit_btn']       = 'Submit exam';
$string['exam_submitted_ok']     = 'Exam submitted successfully. Your teacher will review your result.';
$string['exam_timer_label']      = 'Time remaining';
$string['exam_expired_auto']     = 'Time expired — your exam was submitted automatically.';
$string['portfolio_export_csv']  = 'Export CSV';

// Privacy API metadata strings
$string['privacy:metadata:evalia_student_exams']                    = 'Stores exam instances assigned to each student, including their answers and score.';
$string['privacy:metadata:evalia_student_exams:userid']             = 'The ID of the student.';
$string['privacy:metadata:evalia_student_exams:question_ids']       = 'JSON array of question IDs assigned to this student.';
$string['privacy:metadata:evalia_student_exams:answers']            = 'JSON object mapping question IDs to student answers (may include AI essay evaluations).';
$string['privacy:metadata:evalia_student_exams:score']              = 'Final numeric score achieved.';
$string['privacy:metadata:evalia_student_exams:status']             = 'Exam status (assigned, started, submitted, graded, published).';
$string['privacy:metadata:evalia_student_exams:timesubmitted']      = 'Unix timestamp when the student submitted the exam.';

$string['privacy:metadata:evalia_portfolio']                        = 'Stores a summary of exam performance per student per course.';
$string['privacy:metadata:evalia_portfolio:userid']                 = 'The ID of the student.';
$string['privacy:metadata:evalia_portfolio:avg_grade']              = 'Average grade across all graded exams in the course.';
$string['privacy:metadata:evalia_portfolio:total_exams']            = 'Total number of exams taken in the course.';
$string['privacy:metadata:evalia_portfolio:last_activity']          = 'Unix timestamp of the most recent exam activity.';

$string['privacy:metadata:evalia_portfolio_notes']                  = 'Stores teacher observations written about a specific student.';
$string['privacy:metadata:evalia_portfolio_notes:userid']           = 'The ID of the student being observed.';
$string['privacy:metadata:evalia_portfolio_notes:note_text']        = 'The text of the observation written by the teacher.';
$string['privacy:metadata:evalia_portfolio_notes:created_by']       = 'The ID of the teacher who wrote the observation.';
$string['privacy:metadata:evalia_portfolio_notes:timecreated']      = 'Unix timestamp when the observation was recorded.';

$string['privacy:metadata:evalia_feedback_log']                     = 'Log of AI-generated feedback messages sent to students after grading.';
$string['privacy:metadata:evalia_feedback_log:userid']              = 'The ID of the student who received the feedback.';
$string['privacy:metadata:evalia_feedback_log:channel']             = 'Delivery channel (telegram, moodle).';
$string['privacy:metadata:evalia_feedback_log:message_text']        = 'Summary of the feedback message sent.';
$string['privacy:metadata:evalia_feedback_log:timesent']            = 'Unix timestamp when the message was sent.';
$string['privacy:metadata:evalia_feedback_log:status']              = 'Delivery status (sent, failed).';

$string['privacy:metadata:saipa_engine']                            = 'Exam content is sent to the SAIPA AI engine for grading. No personally identifiable information is included — only question stems and anonymised student answers.';
$string['privacy:metadata:saipa_engine:question_stems']             = 'Question text used for AI evaluation.';
$string['privacy:metadata:saipa_engine:answers']                    = 'Anonymised student answer text used for AI evaluation.';

// Errors
$string['error_no_rubric']         = 'No active rubric found for this course.';
$string['error_engine_unreachable'] = 'Could not reach the AI engine. Please check the SAIPA engine configuration.';
$string['error_not_enough_bank']   = 'The question bank does not have enough approved questions to fill the requested exam structure.';
$string['error_already_assigned']  = 'This student already has an exam assigned.';
