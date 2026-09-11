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
