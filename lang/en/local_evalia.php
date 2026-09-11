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
 * English language strings for local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin metadata
$string['difficulty_advanced']        = 'Advanced';
$string['difficulty_basic']           = 'Basic';
$string['difficulty_medium']          = 'Medium';
$string['error_already_assigned']  = 'This student already has an exam assigned.';
$string['error_engine_unreachable'] = 'Could not reach the AI engine. Please check the SAIPA engine configuration.';
$string['error_no_rubric']         = 'No active rubric found for this course.';
$string['error_not_enough_bank']   = 'The question bank does not have enough approved questions to fill the requested exam structure.';
$string['evalia:manage'] = 'Manage EVAL-IA evaluations (teacher)';
$string['evalia:take']   = 'Take EVAL-IA exams (student)';
$string['exam_advanced_count']  = 'Advanced questions';
$string['exam_assign_all']      = 'Assign to all students';
$string['exam_assign_partial']  = '{$a} student(s) were assigned before the engine became unreachable. Re-run this action once it is back to assign the rest.';
$string['exam_assigned']        = 'Exams assigned. Each student received a unique question set.';
$string['exam_assigning']       = 'Assigning unique exams per student...';
$string['exam_basic_count']     = 'Basic questions';
$string['exam_create']          = 'Create Exam';
$string['exam_expired_auto']     = 'Time expired — your exam was submitted automatically.';
$string['exam_instructions']    = 'Instructions for students';
$string['exam_medium_count']    = 'Medium questions';
$string['exam_name']            = 'Exam name';
$string['exam_not_enough_questions'] = 'Not enough approved questions in the bank to create this exam. Approve more questions first.';
$string['exam_status_assigned'] = 'Assigned';
$string['exam_status_graded']   = 'Graded';
$string['exam_status_started']  = 'In progress';
$string['exam_status_submitted'] = 'Submitted';
$string['exam_submit_btn']       = 'Submit exam';
$string['exam_submitted_ok']     = 'Exam submitted successfully. Your teacher will review your result.';
$string['exam_time_limit']      = 'Time limit (minutes)';
$string['exam_timer_label']      = 'Time remaining';
$string['nav_my_exams'] = 'My Exams';
$string['plugindescription'] = 'Intelligent AI-powered evaluation for Moodle courses. Generates rubrics, question banks, && unique exams per student using RAG from course materials.';
$string['pluginname']   = 'EVAL-IA';
$string['portfolio_avg_grade']      = 'Avg. grade';
$string['portfolio_coming_soon']    = 'Portfolios — Coming in Phase 2';
$string['portfolio_description']    = 'Student portfolios with exam history, grades, && teacher observations will be available here.';
$string['portfolio_exam_history']   = 'Exam History';
$string['portfolio_export_csv']  = 'Export CSV';
$string['portfolio_grade_now']      = 'Grade';
$string['portfolio_last_activity']  = 'Last activity';
$string['portfolio_loading']        = 'Loading portfolios...';
$string['portfolio_no_exams']       = 'No graded exams yet.';
$string['portfolio_no_students']    = 'No students enrolled in this course.';
$string['portfolio_note_empty']     = 'No observations recorded.';
$string['portfolio_note_saved']     = 'Observation saved.';
$string['portfolio_observations']   = 'Observations';
$string['portfolio_select_student'] = 'Select a student from the list to view their portfolio.';
$string['portfolio_students']       = 'Student Portfolios';
$string['portfolio_total_exams']    = 'Exams';
$string['privacy:metadata:evalia_exams']                            = 'Exam templates created by teachers. Only the teacher ID (created_by) is stored.';
$string['privacy:metadata:evalia_exams:created_by']                 = 'The user ID of the teacher who created the exam.';
$string['privacy:metadata:evalia_feedback_log']                     = 'Log of AI-generated feedback messages sent to students after grading.';
$string['privacy:metadata:evalia_feedback_log:channel']             = 'Delivery channel (telegram, moodle).';
$string['privacy:metadata:evalia_feedback_log:message_text']        = 'Summary of the feedback message sent.';
$string['privacy:metadata:evalia_feedback_log:status']              = 'Delivery status (sent, failed).';
$string['privacy:metadata:evalia_feedback_log:timesent']            = 'Unix timestamp when the message was sent.';
$string['privacy:metadata:evalia_feedback_log:userid']              = 'The ID of the student who received the feedback.';
$string['privacy:metadata:evalia_portfolio']                        = 'Stores a summary of exam performance per student per course.';
$string['privacy:metadata:evalia_portfolio:avg_grade']              = 'Average grade across all graded exams in the course.';
$string['privacy:metadata:evalia_portfolio:last_activity']          = 'Unix timestamp of the most recent exam activity.';
$string['privacy:metadata:evalia_portfolio:total_exams']            = 'Total number of exams taken in the course.';
$string['privacy:metadata:evalia_portfolio:userid']                 = 'The ID of the student.';
$string['privacy:metadata:evalia_portfolio_notes']                  = 'Stores teacher observations written about a specific student.';
$string['privacy:metadata:evalia_portfolio_notes:created_by']       = 'The ID of the teacher who wrote the observation.';
$string['privacy:metadata:evalia_portfolio_notes:note_text']        = 'The text of the observation written by the teacher.';
$string['privacy:metadata:evalia_portfolio_notes:timecreated']      = 'Unix timestamp when the observation was recorded.';
$string['privacy:metadata:evalia_portfolio_notes:userid']           = 'The ID of the student being observed.';
$string['privacy:metadata:evalia_rubrics']                          = 'Evaluation rubrics created by teachers. Only the teacher ID (created_by) is stored.';
$string['privacy:metadata:evalia_rubrics:created_by']               = 'The user ID of the teacher who created the rubric.';
$string['privacy:metadata:evalia_student_exams']                    = 'Stores exam instances assigned to each student, including their answers && score.';
$string['privacy:metadata:evalia_student_exams:answers']            = 'JSON object mapping question IDs to student answers (may include AI essay evaluations).';
$string['privacy:metadata:evalia_student_exams:question_ids']       = 'JSON array of question IDs assigned to this student.';
$string['privacy:metadata:evalia_student_exams:score']              = 'Final numeric score achieved.';
$string['privacy:metadata:evalia_student_exams:status']             = 'Exam status (assigned, started, submitted, graded, published).';
$string['privacy:metadata:evalia_student_exams:timesubmitted']      = 'Unix timestamp when the student submitted the exam.';
$string['privacy:metadata:evalia_student_exams:userid']             = 'The ID of the student.';
$string['privacy:metadata:saipa_engine']                            = 'Exam content is sent to the SAIPA AI engine for grading. No personally identifiable information is included — only question stems && anonymised student answers.';
$string['privacy:metadata:saipa_engine:answers']                    = 'Anonymised student answer text used for AI evaluation.';
$string['privacy:metadata:saipa_engine:question_stems']             = 'Question text used for AI evaluation.';
$string['qtype_multichoice']          = 'Multiple choice';
$string['qtype_numerical']            = 'Numerical';
$string['qtype_shortanswer']          = 'Short answer';
$string['qtype_truefalse']            = 'True/False';
$string['questions_approve']          = 'Approve';
$string['questions_edit']             = 'Edit';
$string['questions_filter_difficulty'] = 'Filter by difficulty';
$string['questions_filter_status']    = 'Filter by status';
$string['questions_filter_topic']     = 'Filter by topic';
$string['questions_generate']         = 'Generate Questions';
$string['questions_generate_more']    = 'Generate More';
$string['questions_generating']       = 'Generating questions...';
$string['questions_none']             = 'No questions in the bank yet. Activate a rubric first, then generate questions per item.';
$string['questions_reject']           = 'Reject';
$string['questions_status_approved']  = 'Approved';
$string['questions_status_draft']     = 'Pending review';
$string['questions_status_rejected']  = 'Rejected';
$string['rubric_activate']         = 'Activate';
$string['rubric_activated']        = 'Rubric activated. You can now generate questions.';
$string['rubric_generate']         = 'Generate Rubric with AI';
$string['rubric_generating']       = 'Generating rubric from course materials...';
$string['rubric_item_description'] = 'Description';
$string['rubric_item_topic']       = 'Topic';
$string['rubric_item_weight']      = 'Difficulty weight';
$string['rubric_no_content']       = 'No course content indexed yet. Please index this course in SAIPA first.';
$string['rubric_save']             = 'Save Rubric';
$string['rubric_saved']            = 'Rubric saved successfully.';
$string['rubric_status_active']    = 'Active';
$string['rubric_status_archived']  = 'Archived';
$string['rubric_status_draft']     = 'Draft';
$string['student_exam_title']    = 'EVAL-IA — Take Exam';
$string['tab_exams']     = 'Exams';
$string['tab_portfolio'] = 'Portfolios';
$string['tab_questions'] = 'Question Bank';
$string['tab_rubric']    = 'Rubric';
$string['teacher_page_heading'] = 'EVAL-IA: Intelligent Evaluation';
$string['teacher_page_title']   = 'EVAL-IA — Teacher Panel';
$string['weight_high']             = 'High';
$string['weight_low']              = 'Low';
$string['weight_medium']           = 'Medium';

// Teacher panel (templates/evalia_teacher.mustache) — static template text.
$string['btn_close']                  = 'Close';
$string['btn_copy']                   = 'Copy';
$string['btn_download_csv_title']     = 'Download CSV';
$string['btn_grade_all']              = 'Grade all with AI';
$string['btn_index_material']         = 'Index Material';
$string['btn_publish_grades']         = 'Publish grades';
$string['btn_reload_title']           = 'Reload';
$string['btn_reset_prompt']           = 'Restore default';
$string['btn_save_note']              = 'Save';
$string['btn_save_prompt']            = 'Save prompt';
$string['btn_workflow']               = 'Workflow';
$string['chat_input_placeholder']     = 'Type a question...';
$string['chat_welcome_msg']           = "Hi! I'm your EVAL-IA assistant. I can guide you through each step of the process: indexing material, generating rubrics, building questions, creating and assigning exams. What step are you on, or how can I help?";
$string['exam_instructions_placeholder'] = 'Read each question carefully...';
$string['exam_name_placeholder']      = 'E.g.: Midterm 1 — Introduction to AI';
$string['exam_none_created']          = 'Create an exam to see the status per student.';
$string['exam_selector_none']         = '— No exams —';
$string['exam_window_close_label']    = 'Closes';
$string['exam_window_hint']           = 'Leave both empty for an always-available exam.';
$string['exam_window_open_label']     = 'Opens';
$string['exam_window_title']          = 'Submission window';
$string['fab_aria_label']             = 'Open EVAL-IA Assistant';
$string['fab_title']                  = 'EVAL-IA Assistant';
$string['feedback_prompt_desc']       = 'This text tells the AI how to write the feedback message the student receives after being graded. You can customize the tone, language, level of detail, or any pedagogical aspect.';
$string['feedback_prompt_title']      = 'AI Feedback Prompt';
$string['filter_all_difficulties']    = 'All difficulties';
$string['filter_all_statuses']        = 'All statuses';
$string['filter_all_topics']          = 'All topics';
$string['label_optional']             = '(optional)';
$string['label_students']             = 'Students';
$string['material_how_desc']          = "The AI reads the material the teacher uploaded to the course to generate relevant questions and explain students' mistakes using the real syllabus as context.";
$string['material_how_title']         = 'How does it work?';
$string['material_indexing']          = 'Indexing course pages, PDFs and presentations (PPTX) — this can take several minutes...';
$string['material_step1']             = 'Add content to the course: a <strong>Page</strong> (text/HTML) or a <strong>File</strong> (PDF) from the <em>Add an activity or resource</em> menu.';
$string['material_step2']             = 'Come back here and click <strong>🔍 Index Material</strong> — the system will read and process everything automatically.';
$string['material_step3']             = 'Repeat step 2 every time you upload new material.';
$string['material_subtitle']          = 'Knowledge base for the AI (rubric, questions and feedback)';
$string['material_title']             = 'Course Material';
$string['note_placeholder']           = 'Write an observation...';
$string['questions_loading']          = 'Loading questions...';
$string['rubric_item_count_title']    = 'Number of items in the rubric';
$string['rubric_scope_placeholder']   = 'Scope (e.g.: Relations)';
$string['sources_loading']            = 'Loading sources...';
$string['sources_select_all']         = 'Select all';
$string['sources_select_none']        = 'None';
$string['sources_subtitle']           = '— filter which chapters the AI uses';
$string['sources_title']              = 'Material sources';
$string['stats_distribution_title']   = 'Grade distribution';
$string['stats_failed_title']         = 'Most-missed questions';
$string['stats_title']                = 'Exam Statistics';
$string['student_link_label']         = 'Link for students:';
$string['workflow_modal_title']       = 'Workflow — EVAL-IA';
$string['workflow_step1_desc']        = 'Upload PDFs, PPTX files or Pages to the course in Moodle, then click <strong>"🔍 Index Material"</strong> (blue card, Rubric tab). Repeat every time new content is added.';
$string['workflow_step1_title']       = 'Index Course Material';
$string['workflow_step2_desc']        = 'Optional: enter a <strong>Scope</strong> (e.g.: <em>"Relations"</em>) for partial exams. Click <strong>"✨ Generate Rubric with AI"</strong> → review the items → <strong>"💾 Save"</strong> and then <strong>"✅ Activate"</strong>.';
$string['workflow_step2_title']       = 'Generate and Activate the Rubric';
$string['workflow_step3_desc']        = 'In the <strong>Question Bank tab</strong>: select a topic from the dropdown → click <strong>"✨ Generate Questions"</strong> → review and <strong>approve ✔</strong> or <strong>reject ✗</strong> each question. Repeat for every topic in the rubric.';
$string['workflow_step3_title']       = 'Question Bank';
$string['workflow_step4_desc']        = 'In the <strong>Exams tab</strong>: set the name, instructions and difficulty distribution → <strong>"Create Exam"</strong> → click <strong>"📤 Assign to all"</strong>. Each student gets a unique selection of questions (anti-copying).';
$string['workflow_step4_title']       = 'Create and Assign the Exam';
$string['workflow_step5_desc']        = 'Each student accesses it from Moodle or via a direct link, answers the questions and clicks <strong>"Submit exam"</strong>. The status updates in real time in the student table.';
$string['workflow_step5_title']       = 'Students Take the Exam';
$string['workflow_step6_desc']        = 'Click <strong>"⚡ Grade all with AI"</strong> — automatic grading of every submission. Each student receives a grade plus a pedagogical explanation via <strong>Telegram</strong>. Customize the message from the <em>"AI Feedback Prompt"</em> panel.';
$string['workflow_step6_title']       = 'AI Grading and Feedback';
$string['workflow_step7_desc']        = 'In the <strong>Portfolios tab</strong>: exam history, average and trend per student. Add custom observations and export to <strong>CSV</strong>.';
$string['qtype_essay']                = 'Essay';

// Teacher panel (amd/src/evalia_teacher.js) — dynamic UI text.
$string['btn_grade_all_with_ai']      = '⚡ Grade all with AI';
$string['btn_publish_grades_count']   = '✅ Publish grades ({$a})';
$string['btn_publish_single']         = '✅ Publish';
$string['confirm_grade_all']          = 'Grade {$a} exam(s) with AI? This will send pedagogical feedback to each student via Telegram.';
$string['confirm_publish_all']        = 'Publish the grades of {$a} student(s) to the gradebook?';
$string['confirm_reset_prompt']       = 'Restore the default prompt? Saved changes will be lost.';
$string['default_feedback_prompt']    = 'You are SAIPA, a pedagogical assistant for university mentoring.
You have just learned a student\'s exam result, and your mission is to send them
a personal, warm, and educational message via Telegram.

The message must:
1. Greet the student by their first name
2. Communicate the grade clearly and honestly
3. For each INCORRECT question: briefly explain what the student answered,
   what the correct answer was, and WHY that answer is correct
4. If everything was correct: congratulate them genuinely
5. Indicate which topics are worth reviewing based on the mistakes
6. Close with a motivating phrase: mistakes are learning opportunities

Format: Telegram HTML (<b>bold</b>, <i>italics</i>). Maximum ~600 words.
Tone: warm, direct, university-level. Not patronizing.
Respond ONLY with the message, no JSON or comments.';
$string['default_rubric_name']        = 'Course {$a} rubric';
$string['error_exam_assign']          = 'Error assigning exams.';
$string['error_exam_create']          = 'Error creating the exam.';
$string['error_exam_first']           = 'First create an exam.';
$string['error_exam_min_questions']   = 'The exam must have at least 1 question.';
$string['error_exam_name_required']   = 'Enter a name for the exam.';
$string['error_exam_window_order']    = 'The close date must be after the open date.';
$string['error_grade_all']            = 'Error grading.';
$string['error_grade_exams']          = 'Error grading the exams.';
$string['error_index_course']         = 'Error indexing the course material.';
$string['error_index_generic']        = 'Error indexing.';
$string['error_load_bank']            = 'Error loading the question bank.';
$string['error_load_exam_history']    = 'Error loading the exam history.';
$string['error_load_notes']           = 'Error loading observations.';
$string['error_load_portfolio']       = 'Error loading portfolios.';
$string['error_load_rubric']          = 'Error loading the rubric.';
$string['error_load_sources']         = 'Error loading sources.';
$string['error_load_students']        = 'Error loading students.';
$string['error_no_rubric_to_save']    = 'No rubric to save.';
$string['error_note_required']        = 'Write an observation before saving.';
$string['error_note_save']            = 'Error saving the observation.';
$string['error_prompt_save']          = 'Error saving.';
$string['error_publish_all']          = 'Error publishing.';
$string['error_publish_grade']        = 'Error publishing the grade.';
$string['error_publish_grades']       = 'Error publishing the grades.';
$string['error_question_update']      = 'Error updating the question.';
$string['error_questions_generate']   = 'Error generating questions.';
$string['error_rubric_first']         = 'First generate and activate a rubric.';
$string['error_rubric_generate']      = 'Error generating the rubric.';
$string['error_rubric_generate_failed'] = 'Could not generate: {$a}';
$string['error_rubric_must_be_active'] = 'Activate the rubric before generating questions.';
$string['error_rubric_needs_item']    = 'The rubric must have at least one item.';
$string['error_rubric_save']          = 'Error: {$a}';
$string['error_rubric_save_generic']  = 'Error saving.';
$string['error_select_qtype']         = 'Select at least one question type.';
$string['exam_assigned_result']       = 'Assigned: {$a->assigned} student(s). Skipped: {$a->skipped}.';
$string['exam_count_plural']          = '{$a} exams';
$string['exam_count_singular']        = '{$a} exam';
$string['exam_created_ok']            = 'Exam created. Assign it to students once the question bank is ready.';
$string['exam_grade_link_title']      = 'Grade submitted exam';
$string['exam_preview_link_title']    = 'Preview assigned exam';
$string['exam_publish_single_title']  = 'Publish grade to the gradebook';
$string['exam_status_not_assigned']   = 'Not assigned';
$string['exam_view_graded_title']     = 'View graded exam';
$string['exam_view_published_title']  = 'View published exam';
$string['exam_watching_link_title']   = 'Student is taking the exam';
$string['fab_connect_error']          = 'Error connecting to the assistant.';
$string['fab_no_response']            = 'No response.';
$string['gen_questions_btn']          = 'Generate';
$string['gen_questions_count_label']  = 'Count';
$string['gen_questions_difficulty_label'] = 'Difficulty';
$string['gen_questions_item_label']   = 'Rubric item';
$string['gen_questions_loading']      = 'Generating questions from the course material...';
$string['gen_questions_title']        = '✨ Generate Questions with AI';
$string['gen_questions_types_label']  = 'Question types';
$string['grade_all_result']           = '✔ {$a->graded} graded';
$string['grade_all_skipped_suffix']   = ' · {$a} skipped';
$string['grade_published_ok']         = 'Grade published to the gradebook.';
$string['grades_published_result']    = '✅ {$a} grade(s) published to the gradebook.';
$string['grading_in_progress']        = '⏳ Grading...';
$string['index_result_errors']        = '{$a} error(s)';
$string['index_result_nothing']       = 'No material found to index';
$string['index_result_sources_chunks'] = '{$a->sources} source(s), {$a->chunks} chunks indexed';
$string['link_grade']                 = '📝 Grade';
$string['link_view']                  = '👁 View';
$string['link_view_star']             = '★ View';
$string['loading_ellipsis']           = 'Loading...';
$string['loading_exam_history']       = 'Loading history...';
$string['loading_observations']       = 'Loading observations...';
$string['no_exams_assigned_yet']      = 'This student has no exams assigned yet.';
$string['portfolio_average_label']    = 'Average: {$a}';
$string['prompt_saved_ok']            = '✔ Saved';
$string['prompt_saving']              = 'Saving...';
$string['publishing_all']             = '⏳ Publishing...';
$string['publishing_ellipsis']        = '⏳...';
$string['qtype_short_truefalse']      = 'T/F';
$string['qtype_table_multichoice']    = 'MC';
$string['qtype_table_numerical']      = 'Num';
$string['qtype_table_shortanswer']    = 'Short';
$string['question_approved_ok']       = 'Question approved.';
$string['question_rejected_ok']       = 'Question rejected.';
$string['questions_empty_filtered']   = 'No questions match the selected filters. Activate a rubric and use <strong>Generate Questions</strong> per item.';
$string['questions_generated_ok']     = '{$a} question(s) generated. Approve the ones you consider correct.';
$string['rubric_empty_state']         = 'No rubric for this course yet. Click <strong>Generate Rubric with AI</strong> to get started.';
$string['rubric_generated_ok']        = 'Rubric generated. Review it and activate when ready.';
$string['rubric_item_weight_short']   = 'Weight';
$string['rubric_no_items']            = 'The rubric has no items.';
$string['sources_badge_count']        = '{$a->checked}/{$a->total} sources';
$string['sources_none_indexed']       = 'No material indexed for this course.';
$string['stat_average']               = 'Average';
$string['stat_graded']                = 'Graded';
$string['stat_passed']                = 'Passed';
$string['stat_pct_failed']            = '{$a}% failed';
$string['stat_submitted']             = 'Submitted';
$string['stat_topic_title']           = 'Topic';
$string['stat_total']                 = 'Total';
$string['stats_no_data']              = 'No data yet.';
$string['students_none_enrolled_course'] = 'No students enrolled in this course.';
$string['students_none_enrolled_exam']   = 'No students enrolled.';
$string['table_action']               = 'Action';
$string['table_date']                 = 'Date';
$string['table_exam']                 = 'Exam';
$string['table_grade']                = 'Grade';
$string['table_question']             = 'Question';
$string['table_questions']            = 'Questions';
$string['table_status']               = 'Status';
$string['table_student']              = 'Student';
$string['table_type']                 = 'Type';
$string['view_exam_title']            = 'View exam';

// Setup wizard — steps 1 to 3.
$string['wizard_btn_back']              = '← Back';
$string['wizard_btn_next']              = 'Next →';
$string['wizard_feat_gradebook_desc']   = 'Results published directly to Moodle\'s native gradebook.';
$string['wizard_feat_gradebook_title']  = 'Gradebook Integration';
$string['wizard_feat_grading_desc']     = 'Objective questions graded instantly. Essays evaluated by the LLM with RAG context.';
$string['wizard_feat_grading_title']    = 'AI Grading';
$string['wizard_feat_qbank_desc']       = 'Creates multiple-choice, true/false, numerical, short-answer and essay questions per topic.';
$string['wizard_feat_qbank_title']      = 'Question Bank';
$string['wizard_feat_rubric_desc']      = 'Generates structured evaluation rubrics from indexed course materials in seconds.';
$string['wizard_feat_rubric_title']     = 'AI Rubric Generation';
$string['wizard_feat_telegram_desc']    = 'Students receive AI-generated pedagogical feedback via Telegram after grading.';
$string['wizard_feat_telegram_title']   = 'Telegram Feedback';
$string['wizard_feat_unique_desc']      = 'Each student receives a different question set, reducing collusion risk.';
$string['wizard_feat_unique_title']     = 'Unique Per-Student Exams';
$string['wizard_mode_cloud_desc']       = 'saipa-engine configured with an OpenAI-compatible API key. No local GPU required.';
$string['wizard_mode_cloud_title']      = 'Cloud API';
$string['wizard_mode_custom_desc']      = 'Any compatible engine at a custom URL. Full control for advanced deployments.';
$string['wizard_mode_custom_title']     = 'Custom / Enterprise';
$string['wizard_mode_intro']            = 'Select the option that matches your deployed AI infrastructure.';
$string['wizard_mode_local_desc']       = 'saipa-engine running on your server with Ollama as the LLM backend. Full data privacy.';
$string['wizard_mode_local_title']      = 'Local — Ollama';
$string['wizard_mode_saipa_desc']       = 'Fully managed engine by Schaller & Ponce. Subscribe and connect with a single API key.';
$string['wizard_mode_saipa_title']      = 'SAIPA Cloud';
$string['wizard_mode_title']            = 'Choose your AI provisioning mode';
$string['wizard_prov_cloud_desc']       = 'Use any OpenAI-compatible API provider (OpenAI, Azure OpenAI, Groq, Mistral, etc.) with your own API key.';
$string['wizard_prov_cloud_li1']        = 'No local GPU required';
$string['wizard_prov_cloud_li2']        = 'API key cost depends on usage and provider';
$string['wizard_prov_cloud_li3']        = 'Configure <code>OPENAI_API_KEY</code> in saipa-engine\'s <code>.env</code>';
$string['wizard_prov_cloud_title']      = 'Cloud API';
$string['wizard_prov_custom_desc']      = 'Point EVAL-IA at any engine URL that exposes a compatible REST API (e.g. your own FastAPI fork, on-premise deployment, or private cloud).';
$string['wizard_prov_custom_li1']       = 'Must implement <code>GET /health</code> returning <code>{"status":"ok"}</code>';
$string['wizard_prov_custom_li2']       = 'Must implement <code>POST /eval/rubric/generate</code> and related endpoints';
$string['wizard_prov_custom_title']     = 'Custom / Enterprise';
$string['wizard_prov_header']           = '🤖 AI service provisioning — choose one option';
$string['wizard_prov_intro']            = 'The LLM that powers EVAL-IA can come from three sources. You must have at least one option ready before proceeding.';
$string['wizard_prov_local_desc']       = 'Run the LLM on your own server using <a href="https://ollama.com" target="_blank">Ollama</a>. Full privacy — no data leaves your infrastructure.';
$string['wizard_prov_local_li1']        = 'Recommended model: <code>qwen2.5:14b</code> (requires ≥16 GB RAM)';
$string['wizard_prov_local_li2']        = 'Minimum: any 7B model with ≥8 GB RAM';
$string['wizard_prov_local_li3']        = 'saipa-engine must run on the same host or have network access to Ollama';
$string['wizard_prov_local_title']      = 'Local — Ollama';
$string['wizard_prov_saipa_desc']       = 'Fully managed engine hosted by Schaller &amp; Ponce. No Ollama, no ChromaDB installation. Subscribe and connect with a single API key.';
$string['wizard_prov_saipa_li1']        = 'Zero infrastructure to manage';
$string['wizard_prov_saipa_li2']        = 'Join the waitlist at <code>cloud.saipa.online</code>';
$string['wizard_prov_saipa_title']      = 'SAIPA Cloud';
$string['wizard_prov_warning']          = '<strong>⛔ Without an active AI service, EVAL-IA will not be able to:</strong> index course materials, generate rubrics, create questions, grade exams, or deliver feedback. All these functions depend exclusively on the AI engine. <strong>Do not continue</strong> unless you have one of the options above deployed and ready.';
$string['wizard_req_chroma_desc']       = 'Vector database that stores indexed course materials.';
$string['wizard_req_chroma_label']      = 'ChromaDB (embedded in saipa-engine)';
$string['wizard_req_chroma_value']      = 'Included in engine';
$string['wizard_req_confirm']           = 'I have read the requirements above. An AI service (saipa-engine + LLM) is deployed and reachable from this server.';
$string['wizard_req_curl_desc']         = 'Required to communicate with the AI engine.';
$string['wizard_req_curl_enabled']      = 'Enabled';
$string['wizard_req_curl_label']        = 'PHP cURL extension';
$string['wizard_req_curl_missing']      = 'Missing';
$string['wizard_req_db_desc']           = 'MySQL 8+ / MariaDB 10.6+ / PostgreSQL 13+';
$string['wizard_req_db_label']          = 'Database';
$string['wizard_req_engine_badge']      = 'Must be deployed separately';
$string['wizard_req_engine_desc']       = 'Handles all LLM inference, vector search (ChromaDB), and RAG retrieval.';
$string['wizard_req_engine_header']     = '⚠️ AI Engine — <em>Required. EVAL-IA will not function without this.</em>';
$string['wizard_req_engine_intro']      = 'EVAL-IA uses a companion Python service called <strong>saipa-engine</strong> to run all AI operations: rubric generation, question creation, exam grading, and feedback delivery. This service must be running and reachable from this Moodle server before you can use any EVAL-IA feature.';
$string['wizard_req_engine_label']      = 'saipa-engine (Python 3.11+ / FastAPI)';
$string['wizard_req_intro']             = 'Please verify that your environment meets all requirements before continuing. <strong>EVAL-IA will not work without an active AI service.</strong>';
$string['wizard_req_llm_badge']         = 'AI service required';
$string['wizard_req_llm_desc']          = 'Generates rubrics, questions, grades essays, and writes feedback. See provisioning options below.';
$string['wizard_req_llm_label']         = 'Large Language Model (LLM)';
$string['wizard_req_moodle_desc']       = 'Older versions are not supported.';
$string['wizard_req_moodle_label']      = 'Moodle 4.4 or 4.5';
$string['wizard_req_php_desc']          = 'PHP 7.x is not supported.';
$string['wizard_req_php_label']         = 'PHP 8.1+';
$string['wizard_req_platform_header']   = '🖥️ Platform';
$string['wizard_req_title']             = 'Minimum requirements';
$string['wizard_welcome_intro']         = 'EVAL-IA automates your evaluation workflow using AI and Retrieval-Augmented Generation (RAG) over your own course materials:';
$string['wizard_welcome_subtitle']      = 'This wizard will configure the AI engine connection in a few steps.';
$string['wizard_welcome_title']         = 'Welcome to EVAL-IA';

// Setup wizard — steps 4 to 6 and JavaScript runtime strings.
$string['wizard_btn_retry']             = '↻ Retry';
$string['wizard_btn_save_finish']       = '✅ Save & Finish';
$string['wizard_btn_test']              = 'Test Connection →';
$string['wizard_connecting']            = 'Connecting…';
$string['wizard_done_admin_btn']        = '⚙️ Admin Settings';
$string['wizard_done_body']             = 'EVAL-IA is connected to the AI engine and ready to use.<br>Open any course and navigate to <strong>EVAL-IA → Teacher Panel</strong> to start.';
$string['wizard_done_courses_btn']      = 'Go to My Courses →';
$string['wizard_done_title']            = 'Configuration saved!';
$string['wizard_hint_cloud_body']       = 'Enter the URL where saipa-engine is deployed (with Cloud API configured), and the <code>SAIPA_API_TOKEN</code> token. The engine will use your cloud API key internally.';
$string['wizard_hint_cloud_title']      = '☁️ Cloud API:';
$string['wizard_hint_custom_body']      = 'Enter the base URL of your engine. The wizard will test <code>{url}/health</code>. Authentication uses a standard Bearer token.';
$string['wizard_hint_custom_title']     = '⚙️ Custom / Enterprise:';
$string['wizard_hint_local_body']       = 'The default port for saipa-engine is <code>8052</code>. If you are running it via Docker on the same host, use <code>http://localhost:8052</code>. Token is optional unless you configured <code>SAIPA_API_TOKEN</code> in <code>.env</code>.';
$string['wizard_hint_local_title']      = '🖥️ Local / Ollama:';
$string['wizard_hint_saipa_body']       = 'When launched, the engine URL will be <code>https://engine.saipa.online</code> and the token will be your subscription API key. For now, select another mode to continue.';
$string['wizard_hint_saipa_title']      = '🌐 SAIPA Cloud is not yet available.';
$string['wizard_js_connecting_engine']  = 'Connecting to engine…';
$string['wizard_js_connection_failed']  = 'Connection failed';
$string['wizard_js_engine_reachable']   = 'Engine reachable';
$string['wizard_js_engine_version']     = 'Engine version';
$string['wizard_js_error_label']        = 'Error:';
$string['wizard_js_network_error']      = 'Network error';
$string['wizard_js_success_msg']        = '🎉 <strong>Connection successful!</strong> Click <em>Save &amp; Finish</em> to store the configuration.';
$string['wizard_js_troubleshoot_header'] = 'Troubleshooting checklist:';
$string['wizard_js_troubleshoot_li1']   = 'Is saipa-engine running? Run: <code>docker compose ps</code>';
$string['wizard_js_troubleshoot_li2']   = 'Is the URL correct? (default: <code>http://localhost:8052</code>)';
$string['wizard_js_troubleshoot_li3']   = 'If using a token, does it match <code>SAIPA_API_TOKEN</code> in <code>.env</code>?';
$string['wizard_js_troubleshoot_li4']   = 'Is there a firewall or reverse proxy blocking port 8052?';
$string['wizard_js_troubleshoot_li5']   = 'If Moodle runs inside Docker, use the container hostname, not <code>localhost</code>.';
$string['wizard_js_unknown_error']      = 'Unknown error';
$string['wizard_js_uptime']             = 'Uptime';
$string['wizard_js_url_empty']          = 'Engine URL is empty. Go back and enter a URL.';
$string['wizard_step4_intro']           = 'Enter the URL and authentication token for the saipa-engine.';
$string['wizard_step4_title']           = 'Connection details';
$string['wizard_step5_intro']           = 'Verifying connectivity with the SAIPA Engine…';
$string['wizard_step5_title']           = 'Connection test';
$string['wizard_token_help']            = 'Value of <code>SAIPA_API_TOKEN</code> in the engine\'s <code>.env</code> file. Leave blank if you did not configure a secret.';
$string['wizard_token_label']           = 'Engine Token';
$string['wizard_token_placeholder']     = 'Leave blank if not configured';
$string['wizard_url_help']              = 'Base URL of the saipa-engine — without trailing slash.';
$string['wizard_url_label']             = 'Engine URL';

// Setup wizard — page chrome, progress bar, completion screen and PHP-side messages.
$string['wizard_completion_body']       = 'The AI engine has been configured. You can now generate rubrics,<br>create question banks, and assign exams to your students.';
$string['wizard_completion_title']      = 'EVAL-IA is ready!';
$string['wizard_err_connection']        = 'Connection failed: {$a}';
$string['wizard_err_http_status']       = 'Engine returned HTTP {$a}. Check the URL and token.';
$string['wizard_err_unexpected']        = 'Unexpected engine response: {$a}';
$string['wizard_err_url_required']      = 'Engine URL is required.';
$string['wizard_page_heading']          = 'EVAL-IA Setup Wizard';
$string['wizard_page_title']            = 'EVAL-IA — Setup Wizard';
$string['wizard_save_success']          = 'Configuration saved successfully.';
$string['wizard_step_ai_mode']          = 'AI Mode';
$string['wizard_step_connect']          = 'Connect';
$string['wizard_step_done']             = 'Done';
$string['wizard_step_requirements']     = 'Requirements';
$string['wizard_step_test']             = 'Test';
$string['wizard_step_welcome']          = 'Welcome';

// Admin settings.
$string['settings:engine_token']               = 'Engine authentication token';
$string['settings:engine_token_desc']          = 'Bearer token configured on the engine. Leave blank to inherit SAIPA\'s token.';
$string['settings:engine_url']                 = 'AI Engine URL';
$string['settings:engine_url_desc']            = 'Base URL of the engine, e.g. <code>http://localhost:8052</code> or '
    . '<code>https://engine.saipa.online</code>. Leave blank to inherit SAIPA\'s configuration.';
$string['settings:exam_default_advanced']      = 'Default advanced questions';
$string['settings:exam_default_advanced_desc'] = 'Initial number of advanced questions when creating an exam.';
$string['settings:exam_default_basic']         = 'Default basic questions';
$string['settings:exam_default_basic_desc']    = 'Initial number of basic questions when creating an exam.';
$string['settings:exam_default_medium']        = 'Default medium questions';
$string['settings:exam_default_medium_desc']   = 'Initial number of medium questions when creating an exam.';
$string['settings:exam_default_time_limit']      = 'Default time limit (minutes)';
$string['settings:exam_default_time_limit_desc'] = 'Initial value of the time-limit field. Use 0 for no limit.';
$string['settings:feedback_telegram']          = 'Send feedback via Telegram';
$string['settings:feedback_telegram_desc']     = 'When grading an exam, sends the student their result and pedagogical '
    . 'analysis via Telegram. Requires the student to have linked their Telegram account in SAIPA.';
$string['settings:heading_engine']             = '🤖 AI Engine (SAIPA Engine)';
$string['settings:heading_engine_desc']        = 'URL and token for the AI engine. If left blank, EVAL-IA uses the SAIPA '
    . 'plugin\'s configuration (if installed). Set these for standalone deployments where SAIPA is not installed.';
$string['settings:heading_exam']               = '📝 Exams';
$string['settings:heading_feedback']           = '💬 Feedback';
$string['settings:heading_pdf']                = '📄 PDF indexing';
$string['settings:heading_questions']          = '❓ Question generation';
$string['settings:heading_rubric']             = '📋 Rubrics';
$string['settings:heading_weights']            = '⚖️ Difficulty weighting';
$string['settings:heading_weights_desc']       = 'Points assigned to each question according to its difficulty. The '
    . 'final grade is calculated as (correct weights / total weights) × 10.';
$string['settings:heading_wizard']             = '🚀 Setup Wizard';
$string['settings:heading_wizard_desc']        = 'Use the wizard to configure the AI engine step by step, with a '
    . 'real-time connection test.';
$string['settings:launch_wizard']              = '▶ Launch Setup Wizard';
$string['settings:pdf_page_ranges']            = 'Page ranges per PDF file';
$string['settings:pdf_page_ranges_desc']       = 'JSON mapping filenames to chapters. Allows indexing each chapter as '
    . 'an independent source.<br>Format: <code>{"file.pdf": [{"from": 1, "to": 50, "label": "Ch1-Topic"}, ...]}</code>'
    . '<br>Leave empty to index the whole PDF without splitting.';
$string['settings:questions_default_count']      = 'Questions per rubric item (default)';
$string['settings:questions_default_count_desc'] = 'Initial value in the "Generate questions" dialog per rubric item. '
    . 'Recommended range: 3–10.';
$string['settings:rubric_default_items']       = 'Default number of items';
$string['settings:rubric_default_items_desc']  = 'Initial value of the field when opening the generation form (range: 5–40).';
$string['settings:weight_advanced']            = 'Weight — advanced questions';
$string['settings:weight_basic']               = 'Weight — basic questions';
$string['settings:weight_medium']              = 'Weight — medium questions';

// Student panel (student.php).
$string['student:btn_continue']         = '▶️ Continue →';
$string['student:btn_detail']           = 'View detail →';
$string['student:btn_take']             = '📝 Take exam →';
$string['student:heading']              = '📝 My Exams';
$string['student:meta_from']            = '📅 From: {$a}';
$string['student:meta_timelimit']       = '⏱ Time limit: <strong>{$a} min</strong>';
$string['student:meta_until']           = '⏰ Until: {$a}';
$string['student:msg_graded']           = 'Your exam has already been graded. The grade will be available once the teacher publishes it.';
$string['student:msg_submitted_on']     = 'Exam submitted on {$a}. The teacher will review it soon.';
$string['student:none_assigned']        = 'You don\'t have any exams assigned yet.';
$string['student:none_assigned_desc']   = 'Your teacher will notify you when an exam becomes available for this course.';
$string['student:page_title']           = 'My Exams — EVAL-IA';
$string['student:status_assigned']      = 'Pending';
$string['student:status_graded']        = 'Graded';
$string['student:status_published']     = '✅ Published';
$string['student:status_started']       = 'In progress';
$string['student:status_submitted']     = 'Submitted';
$string['student:window_closed']        = '🔒 The submission period closed on {$a}';
$string['student:window_opens']         = '🕐 Available from {$a}';

// Exam-taking page (student_exam.php).
$string['exam:ai_score_label']          = 'AI: {$a}%';
$string['exam:btn_grade_ai']            = '⚡ Grade with AI';
$string['exam:btn_submit_exam']         = '📤 Submit exam';
$string['exam:correct_answer_label']    = 'Correct answer:';
$string['exam:grade_panel_desc']        = 'On confirming, the AI engine will evaluate the student\'s answers and assign a grade automatically.';
$string['exam:grade_panel_title']       = '📝 Grade exam';
$string['exam:page_title']              = '{$a} — EVAL-IA';
$string['exam:placeholder_essay']       = 'Write your answer here';
$string['exam:placeholder_numerical']   = 'Enter a numeric value';
$string['exam:placeholder_shortanswer'] = 'Write your answer';
$string['exam:points_of']               = '{$a->obtained}/{$a->total} pts';
$string['exam:points_plain']            = '{$a} pts';
$string['exam:preview_banner_title']    = 'Teacher view — read only';
$string['exam:preview_graded_msg']      = '<strong>Graded:</strong> {$a} / 10.0';
$string['exam:preview_status_label']    = 'Status:';
$string['exam:preview_student_fallback'] = 'student';
$string['exam:preview_student_label']   = 'Student:';
$string['exam:question_num']            = 'Question {$a}';
$string['exam:status_assigned']         = 'Assigned (not started yet)';
$string['exam:status_graded']           = 'Graded';
$string['exam:status_started']          = 'In progress';
$string['exam:status_submitted_review'] = 'Submitted — pending grading';
$string['exam:submitted_grade_msg']     = 'Your grade: <strong>{$a} / 10.0</strong>. The teacher has already graded your exam.';
$string['exam:submitted_heading']       = '✅ Exam submitted';
$string['exam:submitted_pending_msg']   = 'Your exam was received successfully. The teacher will review it soon.';
$string['exam:timer_autosubmit']        = 'The exam is submitted automatically when it reaches 00:00.';
$string['exam:timer_remaining']         = '⏱ Time remaining:';
