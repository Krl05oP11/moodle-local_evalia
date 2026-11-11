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
 * EVAL-IA Teacher Dashboard AMD module.
 *
 * Handles all 4 tabs: Rubric, Question Bank, Exams, Portfolios.
 * Uses jQuery Deferreds (NOT native Promises) — Moodle 4.4 AMD pattern.
 * All Ajax calls use .then().fail() — never .catch() or .finally().
 *
 * @module     local_evalia/evalia_teacher
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/log', 'core/str', 'core/templates'], function(Ajax, Log, Str, Templates) {

    'use strict';

    // ─── Module state ────────────────────────────────────────────────────────
    var courseId = 0;
    var currentRubric = null; // { rubricid, status, items[] }
    var currentExamId = 0; // Active exam template ID
    var selectedPortfolioUserId = 0;
    var sourcesLoaded = false; // Lazy-load guard for source panel
    var allSources = []; // [{id, label, type}] from WS
    var defaultQuestionsCount = 5; // Overridden from config.defaults in init()

    // ─── i18n ────────────────────────────────────────────────────────────────
    // Every UI string this module needs, keyed by its local_evalia lang key.
    // Values below are the English fallback (identical to lang/en/local_evalia.php)
    // shown until loadStrings() resolves —
    // Str.get_strings() is fast (cached) but async, so init() does not block on it.
    // Strings containing "{$a}" / "{$a->prop}" are Moodle placeholders Moodle's
    // get_string() leaves untouched when called with no $a — resolved here via
    // core/str (no $a passed), then substituted manually with String.replace()
    // at each call site. This lets every render function stay synchronous.
    var S = {
        // Rubric tab (Tab 1)
        rubric_status_active:       'Active',
        rubric_status_draft:        'Draft',
        error_load_rubric:          'Error loading the rubric.',
        rubric_empty_state:         'No rubric for this course yet. Click <strong>Generate Rubric with AI</strong> to get started.',
        rubric_no_items:            'The rubric has no items.',
        rubric_item_topic:          'Topic',
        rubric_item_description:    'Description',
        rubric_item_weight_short:   'Weight',
        weight_low:                 'Low',
        weight_medium:              'Medium',
        weight_high:                'High',
        error_no_rubric_to_save:    'No rubric to save.',
        error_rubric_needs_item:    'The rubric must have at least one item.',
        default_rubric_name:        'Course {$a} rubric',
        rubric_generated_ok:        'Rubric generated. Review it and activate when ready.',
        error_rubric_generate_failed: 'Could not generate: {$a}',
        error_rubric_generate:      'Error generating the rubric.',
        error_rubric_save:          'Error: {$a}',
        error_rubric_save_generic:  'Error saving.',

        // Question bank (Tab 2)
        difficulty_basic:           'Basic',
        difficulty_medium:          'Medium',
        difficulty_advanced:        'Advanced',
        questions_status_draft:     'Pending review',
        questions_status_approved:  'Approved',
        questions_status_rejected:  'Rejected',
        qtype_table_multichoice:    'MC',
        qtype_short_truefalse:      'T/F',
        qtype_table_numerical:      'Num',
        qtype_table_shortanswer:    'Short',
        qtype_essay:                'Essay',
        qtype_multichoice:          'Multiple choice',
        qtype_shortanswer:          'Short answer',
        error_load_bank:            'Error loading the question bank.',
        table_question:             'Question',
        table_type:                 'Type',
        questions_approve:          'Approve',
        questions_reject:           'Reject',
        questions_empty_filtered:   'No questions match the selected filters. Activate a rubric and use <strong>Generate ' +
            'Questions</strong> per item.',
        question_approved_ok:       'Question approved.',
        question_rejected_ok:       'Question rejected.',
        error_question_update:      'Error updating the question.',
        error_rubric_first:         'First generate and activate a rubric.',
        error_rubric_must_be_active: 'Activate the rubric before generating questions.',
        gen_questions_title:        '✨ Generate Questions with AI',
        gen_questions_item_label:   'Rubric item',
        gen_questions_difficulty_label: 'Difficulty',
        gen_questions_count_label:  'Count',
        gen_questions_btn:          'Generate',
        gen_questions_types_label:  'Question types',
        gen_questions_loading:      'Generating questions from the course material...',
        error_select_qtype:         'Select at least one question type.',
        questions_generated_ok:     '{$a} question(s) generated. Approve the ones you consider correct.',
        error_questions_generate:   'Error generating questions.',

        // Exams (Tab 3)
        error_exam_name_required:   'Enter a name for the exam.',
        error_exam_min_questions:   'The exam must have at least 1 question.',
        error_exam_window_order:    'The close date must be after the open date.',
        exam_created_ok:            'Exam created. Assign it to students once the question bank is ready.',
        error_exam_create:          'Error creating the exam.',
        error_exam_first:           'First create an exam.',
        exam_assigned_result:       'Assigned: {$a->assigned} student(s). Skipped: {$a->skipped}.',
        error_exam_assign:          'Error assigning exams.',
        students_none_enrolled_exam: 'No students enrolled.',
        error_load_students:        'Error loading students.',
        btn_publish_grades_count:   '✅ Publish grades ({$a})',
        exam_status_not_assigned:   'Not assigned',
        'student:status_published': '✅ Published',
        exam_status_assigned:       'Assigned',
        exam_status_started:        'In progress',
        exam_status_submitted:      'Submitted',
        exam_status_graded:         'Graded',
        exam_preview_link_title:    'Preview assigned exam',
        exam_watching_link_title:   'Student is taking the exam',
        exam_grade_link_title:      'Grade submitted exam',
        exam_view_graded_title:     'View graded exam',
        exam_publish_single_title:  'Publish grade to the gradebook',
        exam_view_published_title:  'View published exam',
        link_view:                  '👁 View',
        link_grade:                 '📝 Grade',
        link_view_star:             '★ View',
        btn_publish_single:         '✅ Publish',
        table_student:              'Student',
        table_status:               'Status',
        table_grade:                'Grade',
        table_action:               'Action',
        confirm_grade_all:          'Grade {$a} exam(s) with AI? This will send pedagogical feedback to each student via Telegram.',
        grading_in_progress:        '⏳ Grading...',
        grade_all_result:           '✔ {$a->graded} graded',
        grade_all_skipped_suffix:   ' · {$a} skipped',
        btn_grade_all_with_ai:      '⚡ Grade all with AI',
        error_grade_all:            'Error grading.',
        error_grade_exams:          'Error grading the exams.',
        publishing_ellipsis:        '⏳...',
        grade_published_ok:         'Grade published to the gradebook.',
        error_publish_grade:        'Error publishing the grade.',
        confirm_publish_all:        'Publish the grades of {$a} student(s) to the gradebook?',
        publishing_all:             '⏳ Publishing...',
        grades_published_result:    '✅ {$a} grade(s) published to the gradebook.',
        error_publish_all:          'Error publishing.',
        error_publish_grades:       'Error publishing the grades.',

        // Exam stats (Tab 3)
        stat_total:                 'Total',
        stat_graded:                'Graded',
        stat_submitted:             'Submitted',
        stat_average:               'Average',
        stat_passed:                'Passed',
        stats_no_data:              'No data yet.',
        stat_topic_title:           'Topic',
        stat_pct_failed:            '{$a}% failed',

        // Portfolio (Tab 4)
        students_none_enrolled_course: 'No students enrolled in this course.',
        error_load_portfolio:       'Error loading portfolios.',
        tab_exams:                  'Exams',
        portfolio_avg_grade:        'Avg. grade',
        loading_ellipsis:           'Loading...',
        loading_exam_history:       'Loading history...',
        exam_count_singular:        '{$a} exam',
        exam_count_plural:          '{$a} exams',
        portfolio_average_label:    'Average: {$a}',
        no_exams_assigned_yet:      'This student has no exams assigned yet.',
        error_load_exam_history:    'Error loading the exam history.',
        table_exam:                 'Exam',
        table_date:                 'Date',
        table_questions:            'Questions',
        view_exam_title:            'View exam',
        loading_observations:       'Loading observations...',
        error_load_notes:           'Error loading observations.',
        portfolio_note_empty:       'No observations recorded.',
        error_note_required:        'Write an observation before saving.',
        error_note_save:            'Error saving the observation.',
        portfolio_note_saved:       'Observation saved.',

        // RAG indexing
        index_result_sources_chunks: '{$a->sources} source(s), {$a->chunks} chunks indexed',
        index_result_errors:        '{$a} error(s)',
        index_result_nothing:       'No material found to index',
        error_index_generic:        'Error indexing.',
        error_index_course:         'Error indexing the course material.',

        // Feedback prompt editor
        prompt_saving:              'Saving...',
        prompt_saved_ok:            '✔ Saved',
        error_prompt_save:          'Error saving.',
        confirm_reset_prompt:       'Restore the default prompt? Saved changes will be lost.',
        default_feedback_prompt:
            'You are SAIPA, a pedagogical assistant for university mentoring.\n' +
            'You have just learned a student\'s exam result, and your mission is to send them\n' +
            'a personal, warm, and educational message via Telegram.\n' +
            '\n' +
            'The message must:\n' +
            '1. Greet the student by their first name\n' +
            '2. Communicate the grade clearly and honestly\n' +
            '3. For each INCORRECT question: briefly explain what the student answered,\n' +
            '   what the correct answer was, and WHY that answer is correct\n' +
            '4. If everything was correct: congratulate them genuinely\n' +
            '5. Indicate which topics are worth reviewing based on the mistakes\n' +
            '6. Close with a motivating phrase: mistakes are learning opportunities\n' +
            '\n' +
            'Format: Telegram HTML (<b>bold</b>, <i>italics</i>). Maximum ~600 words.\n' +
            'Tone: warm, direct, university-level. Not patronizing.\n' +
            'Respond ONLY with the message, no JSON or comments.',

        // FAB chat assistant
        fab_no_response:            'No response.',
        fab_connect_error:          'Error connecting to the assistant.',

        // Sources panel
        sources_badge_count:        '{$a->checked}/{$a->total} sources',
        sources_none_indexed:       'No material indexed for this course.',
        error_load_sources:         'Error loading sources.'
    };

    /**
     * Resolves every string in S against the current language pack, in one
     * batch request. Until this resolves, S already holds the English
     * fallback text, so rendering never blocks on it.
     */
    function loadStrings() {
        var keys = Object.keys(S);
        Str.get_strings(keys.map(function(k) {
            return {key: k, component: 'local_evalia'};
        })).then(function(values) {
            keys.forEach(function(k, i) { S[k] = values[i]; });
            return values;
        }).catch(function(err) {
            Log.error('evalia_teacher: string load error', err);
        });
    }

    // ─── Toast helper ────────────────────────────────────────────────────────

    /**
     * Shows a coloured status toast with the given message for a few seconds.
     *
     * @param {string} message Text to display in the toast body.
     * @param {string} type Bootstrap contextual colour suffix (e.g. 'success', 'danger'); defaults to 'info'.
     */
    function showToast(message, type) {
        var toast = document.getElementById('evalia-toast');
        if (!toast) { return; }
        toast.className = 'toast align-items-center text-white border-0 bg-' + (type || 'info');
        document.getElementById('evalia-toast-body').textContent = message;
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 6000);
    }

    /**
     * Shows or hides a loading indicator element.
     *
     * @param {string} elementId ID of the loading indicator element.
     * @param {boolean} visible True to show it, false to hide it.
     */
    function setLoading(elementId, visible) {
        var el = document.getElementById(elementId);
        if (el) {
            el.hidden = !visible;
            el.style.display = visible ? '' : 'none';
        }
    }

    /**
     * Renders a local_evalia template into a container, replacing its contents.
     *
     * Every value in the context is escaped by Mustache, so data from the web services (rubric topics,
     * AI-generated question stems, names, notes, source labels) is never inserted as markup (MMR-463 #3).
     *
     * @param {HTMLElement} container Element whose contents are replaced.
     * @param {string} template Template name inside local_evalia.
     * @param {Object} context Template context.
     * @returns {Promise} jQuery promise resolved after the HTML is in the page.
     */
    function renderInto(container, template, context) {
        return Templates.render('local_evalia/' + template, context).then(function(html, js) {
            Templates.replaceNodeContents(container, html, js);
            return container;
        }).fail(function(err) {
            Log.error('evalia_teacher: could not render local_evalia/' + template, err);
        });
    }

    /**
     * Replaces the contents of an element with a single text node wrapped in a tag (a notice or an error).
     *
     * @param {HTMLElement} el Element whose contents are replaced.
     * @param {string} cls CSS classes of the wrapper.
     * @param {string} text Plain text; it is never parsed as HTML.
     * @param {string} [tag] Wrapper tag, 'p' by default.
     */
    function showNotice(el, cls, text, tag) {
        if (!el) { return; }
        var node = document.createElement(tag || 'p');
        node.className = cls;
        node.textContent = text;
        el.textContent = '';
        el.appendChild(node);
    }

    /**
     * Replaces the contents of an element with a spinner and an optional text.
     *
     * @param {HTMLElement} el Element whose contents are replaced.
     * @param {string} cls CSS classes of the wrapper.
     * @param {string} [text] Plain text shown after the spinner.
     */
    function showSpinner(el, cls, text) {
        if (!el) { return; }
        var node = document.createElement('p');
        node.className = cls;
        var spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm text-secondary' + (text ? ' me-2' : '');
        spinner.setAttribute('role', 'status');
        node.appendChild(spinner);
        if (text) { node.appendChild(document.createTextNode(text)); }
        el.textContent = '';
        el.appendChild(node);
    }

    /**
     * Enables or disables a button (or other form control) by ID.
     *
     * @param {string} elementId ID of the element.
     * @param {boolean} disabled True to disable it, false to enable it.
     */
    function setDisabled(elementId, disabled) {
        var el = document.getElementById(elementId);
        if (el) { el.disabled = disabled; }
    }

    // ─── Tab 1: Rubric ───────────────────────────────────────────────────────

    /**
     * Returns the localised label for a rubric item difficulty weight.
     *
     * @param {string} weight Weight value ('low', 'medium' or 'high').
     * @returns {string} Localised label, or the raw value when unknown.
     */
    function weightLabel(weight) {
        return S['weight_' + weight] || weight;
    }

    /**
     * Fetches the course rubric via web service and renders its status badge, items and action buttons.
     */
    function loadRubric() {
        var container = document.getElementById('evalia-rubric-items-container');
        if (!container) { return; }

        Ajax.call([{
            methodname: 'local_evalia_get_rubric',
            args: {courseid: courseId}
        }])[0].then(function(result) {
            currentRubric = result;

            var statusDiv = document.getElementById('evalia-rubric-status');
            if (statusDiv) {
                if (result.rubricid === 0) {
                    statusDiv.textContent = '';
                } else {
                    renderInto(statusDiv, 'rubric_status', {active: result.status === 'active', name: result.name});
                }
            }

            if (result.rubricid === 0) {
                renderInto(container, 'empty_state', {rubric: true});
                showRubricButtons(false, false);
                return;
            }

            renderRubricItems(result.items, result.status);
            showRubricButtons(result.status === 'draft', result.status === 'draft');

        }).fail(function(err) {
            Log.error('evalia_teacher: get_rubric failed', err);
            showNotice(container, 'text-danger', S.error_load_rubric);
        });
    }

    /**
     * Toggles visibility of the rubric Save and Activate buttons.
     *
     * @param {boolean} showSave Whether to show the Save button.
     * @param {boolean} showActivate Whether to show the Activate button.
     */
    function showRubricButtons(showSave, showActivate) {
        var btnSave = document.getElementById('evalia-btn-save-rubric');
        var btnActivate = document.getElementById('evalia-btn-activate-rubric');
        if (btnSave) { btnSave.style.display = showSave ? '' : 'none'; }
        if (btnActivate) { btnActivate.style.display = showActivate ? '' : 'none'; }
    }

    /**
     * Renders the rubric items table, editable when the rubric is still a draft.
     *
     * @param {Array} items Rubric items ({id, topic, description, difficulty_weight}).
     * @param {string} status Rubric status ('draft' or 'active').
     */
    function renderRubricItems(items, status) {
        var container = document.getElementById('evalia-rubric-items-container');
        if (!items || items.length === 0) {
            showNotice(container, 'text-muted', S.rubric_no_items);
            return;
        }

        renderInto(container, 'rubric_items', {
            editable: status === 'draft',
            items: items.map(function(item, idx) {
                return {
                    num: idx + 1,
                    id: item.id,
                    topic: item.topic,
                    description: item.description || '',
                    // Weight labels come from {{#str}} in the template: the rubric renders at page load,
                    // before loadStrings() has replaced the English fallbacks of S.
                    islow: item.difficulty_weight === 'low',
                    ismedium: item.difficulty_weight === 'medium' || ['low', 'high'].indexOf(item.difficulty_weight) < 0,
                    ishigh: item.difficulty_weight === 'high'
                };
            })
        });
    }

    /**
     * Reads the current values of the editable rubric table into item objects.
     *
     * @returns {Array} Rubric items ready to send to local_evalia_save_rubric.
     */
    function collectRubricItems() {
        // Read current values from editable fields in the rubric table.
        var items = [];
        var rows = document.querySelectorAll('#evalia-rubric-items-container tbody tr');
        rows.forEach(function(row) {
            var topicEl = row.querySelector('[data-field="topic"]');
            var descEl = row.querySelector('[data-field="description"]');
            var weightEl = row.querySelector('[data-field="difficulty_weight"]');
            if (!topicEl) { return; }
            items.push({
                id:                 parseInt(topicEl.dataset.itemId, 10) || 0,
                topic:              topicEl.value || '',
                description:        descEl ? descEl.value : '',
                difficulty_weight:  weightEl ? weightEl.value : 'medium',
                sortorder:          items.length + 1
            });
        });
        return items;
    }

    // ─── Source filter panel ─────────────────────────────────────────────────

    /**
     * Updates the badge showing how many course sources are selected out of the total.
     */
    function updateSourcesBadge() {
        var badge = document.getElementById('evalia-sources-badge');
        if (!badge || !allSources.length) { return; }
        var total = allSources.length;
        var checked = document.querySelectorAll('#evalia-sources-list input[type=checkbox]:checked').length;
        badge.style.display = '';
        badge.textContent = S.sources_badge_count.replace('{$a->checked}', checked).replace('{$a->total}', total);
        badge.className = 'badge me-2 ' + (checked < total ? 'bg-warning text-dark' : 'bg-secondary');
    }

    /**
     * Renders the list of indexed course sources as checkboxes (all checked) and wires the badge update.
     *
     * @param {Array} sources Course sources ({id, label, type}).
     */
    function renderSources(sources) {
        var list = document.getElementById('evalia-sources-list');
        if (!list) { return; }
        if (!sources.length) {
            showNotice(list, 'text-muted small', S.sources_none_indexed, 'span');
            return;
        }
        var icons = {page: '📄', resource: '📑', unknown: '📎'};
        renderInto(list, 'source_list', {
            sources: sources.map(function(src) {
                return {
                    eid: 'evalia-src-' + String(src.id).replace(/[^a-zA-Z0-9]/g, '_'),
                    id: src.id,
                    icon: icons[src.type] || '📎',
                    label: src.label
                };
            })
        }).then(function() {
            // Listen for changes to update the badge.
            list.querySelectorAll('input[type=checkbox]').forEach(function(cb) {
                cb.addEventListener('change', updateSourcesBadge);
            });
            updateSourcesBadge();
            return list;
        });
    }

    /**
     * Loads the indexed course sources via web service once (lazy) and renders them.
     */
    function loadSources() {
        if (sourcesLoaded) { return; }
        sourcesLoaded = true;
        Ajax.call([{
            methodname: 'local_evalia_get_course_sources',
            args: {courseid: courseId}
        }])[0].then(function(result) {
            allSources = result.sources || [];
            renderSources(allSources);
        }).fail(function(err) {
            Log.error('evalia_teacher: get_course_sources failed', err);
            var list = document.getElementById('evalia-sources-list');
            if (list) { showNotice(list, 'text-danger small', S.error_load_sources, 'span'); }
        });
    }

    /**
     * Returns the IDs of the checked sources, or an empty list when all (or none were loaded) are checked.
     *
     * @returns {Array} Selected source IDs; empty means no filter.
     */
    function getSelectedSources() {
        // Returns [] if all are checked (= no filter) or if panel was never opened.
        var cbs = document.querySelectorAll('#evalia-sources-list input[type=checkbox]');
        if (!cbs.length) { return []; }
        var all = Array.from(cbs);
        var selected = all.filter(function(c) { return c.checked; }).map(function(c) { return c.dataset.sourceId; });
        return (selected.length === all.length) ? [] : selected;
    }

    /**
     * Wires the toggle that expands/collapses the exam availability window panel.
     */
    function initExamWindowToggle() {
        var toggle = document.getElementById('evalia-exam-window-toggle');
        var panel = document.getElementById('evalia-exam-window-panel');
        var arrow = document.getElementById('evalia-exam-window-arrow');
        if (!toggle || !panel) { return; }
        toggle.addEventListener('click', function() {
            var open = panel.style.display !== 'none';
            panel.style.display = open ? 'none' : '';
            if (arrow) { arrow.textContent = open ? '▶' : '▼'; }
        });
    }

    /**
     * Wires the sources panel toggle (lazy-loading sources) and the select all/none links.
     */
    function initSourcesPanel() {
        var toggle = document.getElementById('evalia-sources-toggle');
        var panel = document.getElementById('evalia-sources-panel');
        var arrow = document.getElementById('evalia-sources-arrow');
        if (!toggle || !panel) { return; }

        toggle.addEventListener('click', function() {
            var open = panel.style.display !== 'none';
            panel.style.display = open ? 'none' : '';
            if (arrow) { arrow.textContent = open ? '▶' : '▼'; }
            if (!open) { loadSources(); }
        });

        var selAll = document.getElementById('evalia-sources-select-all');
        var selNone = document.getElementById('evalia-sources-select-none');
        if (selAll) {
            selAll.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('#evalia-sources-list input[type=checkbox]')
                    .forEach(function(c) { c.checked = true; });
                updateSourcesBadge();
            });
        }
        if (selNone) {
            selNone.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('#evalia-sources-list input[type=checkbox]')
                    .forEach(function(c) { c.checked = false; });
                updateSourcesBadge();
            });
        }
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Requests AI generation of a rubric for the course using the chosen scope, item count and sources.
     */
    function handleGenerateRubric() {
        setDisabled('evalia-btn-generate-rubric', true);
        setLoading('evalia-rubric-loading', true);

        var scopeEl = document.getElementById('evalia-rubric-scope');
        var countEl = document.getElementById('evalia-rubric-item-count');
        var scope = scopeEl ? scopeEl.value.trim() : '';
        var item_count = countEl ? (parseInt(countEl.value, 10) || 18) : 18;
        var selSources = getSelectedSources();
        var source_filter = selSources.length ? JSON.stringify(selSources) : '';

        Ajax.call([{
            methodname: 'local_evalia_generate_rubric',
            args: {courseid: courseId, scope: scope, item_count: item_count, source_filter: source_filter}
        }])[0].then(function(result) {
            setLoading('evalia-rubric-loading', false);
            setDisabled('evalia-btn-generate-rubric', false);
            if (result.success) {
                showToast(S.rubric_generated_ok, 'success');
                loadRubric();
            } else {
                showToast(S.error_rubric_generate_failed.replace('{$a}', result.message), 'danger');
            }
        }).fail(function(err) {
            setLoading('evalia-rubric-loading', false);
            setDisabled('evalia-btn-generate-rubric', false);
            Log.error('evalia_teacher: generate_rubric failed', err);
            showToast(S.error_rubric_generate, 'danger');
        });
    }

    /**
     * Saves the edited rubric items, optionally activating the rubric.
     *
     * @param {boolean} activate True to also activate the rubric, false to save it as draft.
     */
    function handleSaveRubric(activate) {
        if (!currentRubric || currentRubric.rubricid === 0) {
            showToast(S.error_no_rubric_to_save, 'danger');
            return;
        }
        var items = collectRubricItems();
        if (items.length === 0) {
            showToast(S.error_rubric_needs_item, 'danger');
            return;
        }

        var btnId = activate ? 'evalia-btn-activate-rubric' : 'evalia-btn-save-rubric';
        setDisabled(btnId, true);

        Ajax.call([{
            methodname: 'local_evalia_save_rubric',
            args: {
                rubricid: currentRubric.rubricid,
                name:     currentRubric.name || S.default_rubric_name.replace('{$a}', courseId),
                activate: activate ? 1 : 0,
                items:    items
            }
        }])[0].then(function(result) {
            setDisabled(btnId, false);
            if (result.success) {
                showToast(result.message, 'success');
                loadRubric();
                // Refresh topic filter in Tab 2 after rubric changes
                refreshTopicFilter();
            } else {
                showToast(S.error_rubric_save.replace('{$a}', result.message), 'danger');
            }
        }).fail(function(err) {
            setDisabled(btnId, false);
            Log.error('evalia_teacher: save_rubric failed', err);
            showToast(S.error_rubric_save_generic, 'danger');
        });
    }

    // ─── Tab 2: Question Bank ────────────────────────────────────────────────

    /**
     * Rebuilds the question bank topic filter options from the current rubric items.
     */
    function refreshTopicFilter() {
        // Rebuild topic dropdown from current rubric items.
        var sel = document.getElementById('evalia-filter-topic');
        if (!sel) { return; }
        var current = sel.value;
        // Keep only the "All topics" option, then re-add from rubric
        while (sel.options.length > 1) { sel.remove(1); }
        if (currentRubric && currentRubric.items) {
            currentRubric.items.forEach(function(it) {
                var opt = document.createElement('option');
                opt.value = it.topic;
                opt.textContent = it.topic;
                if (it.topic === current) { opt.selected = true; }
                sel.appendChild(opt);
            });
        }
    }

    /**
     * Loads the question bank with the current topic/difficulty/status filters and renders it.
     */
    function loadQuestionBank() {
        var topicEl = document.getElementById('evalia-filter-topic');
        var diffEl = document.getElementById('evalia-filter-difficulty');
        var statusEl = document.getElementById('evalia-filter-status');
        var container = document.getElementById('evalia-questions-table-container');

        var topic = topicEl ? topicEl.value : '';
        var difficulty = diffEl ? diffEl.value : '';
        var status = statusEl ? statusEl.value : '';

        setLoading('evalia-questions-loading', true);
        if (container) { container.textContent = ''; }

        Ajax.call([{
            methodname: 'local_evalia_get_question_bank',
            args: {courseid: courseId, topic: topic, difficulty: difficulty, status: status}
        }])[0].then(function(result) {
            setLoading('evalia-questions-loading', false);
            if (!result || result.total === 0) {
                renderInto(container, 'empty_state', {questions: true});
                return;
            }
            renderQuestionsTable(result.questions);
        }).fail(function(err) {
            setLoading('evalia-questions-loading', false);
            Log.error('evalia_teacher: get_question_bank failed', err);
            if (container) {
                showNotice(container, 'text-danger', S.error_load_bank);
            }
        });
    }

    /**
     * Renders the question bank table with approve/reject buttons.
     *
     * @param {Array} questions Questions returned by local_evalia_get_question_bank.
     */
    function renderQuestionsTable(questions) {
        var container = document.getElementById('evalia-questions-table-container');
        var statusMap = {
            'draft':    {cls: 'bg-secondary', label: S.questions_status_draft},
            'approved': {cls: 'bg-success', label: S.questions_status_approved},
            'rejected': {cls: 'bg-danger', label: S.questions_status_rejected}
        };
        var diffMap = {
            'basic':    {cls: 'bg-info text-dark', label: S.difficulty_basic},
            'medium':   {cls: 'bg-warning text-dark', label: S.difficulty_medium},
            'advanced': {cls: 'bg-danger', label: S.difficulty_advanced}
        };
        var typeMap = {
            'multichoice': S.qtype_table_multichoice,
            'truefalse':   S.qtype_short_truefalse,
            'numerical':   S.qtype_table_numerical,
            'shortanswer': S.qtype_table_shortanswer,
            'essay':       S.qtype_essay
        };

        renderInto(container, 'question_bank', {
            questions: questions.map(function(q) {
                var st = statusMap[q.status] || {cls: 'bg-light text-dark border', label: q.status};
                var df = diffMap[q.difficulty] || {cls: 'bg-light text-dark border', label: q.difficulty};
                return {
                    id: q.id,
                    topic: q.topic,
                    stem: q.stem.length > 70 ? q.stem.substring(0, 70) + '…' : q.stem,
                    typelabel: typeMap[q.question_type] || q.question_type,
                    difficultyclass: df.cls,
                    difficultylabel: df.label,
                    statusclass: st.cls,
                    statuslabel: st.label
                };
            })
        }).then(function() {
            container.querySelectorAll('.evalia-approve-btn').forEach(function(btn) {
                btn.addEventListener('click', function() { updateQuestion(+btn.dataset.id, 'approved'); });
            });
            container.querySelectorAll('.evalia-reject-btn').forEach(function(btn) {
                btn.addEventListener('click', function() { updateQuestion(+btn.dataset.id, 'rejected'); });
            });
            return container;
        });
    }

    /**
     * Approves or rejects a question and reloads the question bank.
     *
     * @param {number} questionId Question ID.
     * @param {string} status New status ('approved' or 'rejected').
     */
    function updateQuestion(questionId, status) {
        Ajax.call([{
            methodname: 'local_evalia_update_question',
            args: {questionid: questionId, status: status}
        }])[0].then(function(result) {
            if (result.success) {
                showToast(status === 'approved' ? S.question_approved_ok : S.question_rejected_ok, 'success');
                loadQuestionBank();
            } else {
                showToast(S.error_rubric_save.replace('{$a}', result.message), 'danger');
            }
        }).fail(function(err) {
            Log.error('evalia_teacher: update_question failed', err);
            showToast(S.error_question_update, 'danger');
        });
    }

    /**
     * Shows the inline form to generate questions with AI for an item of the active rubric.
     */
    function showGenerateQuestionsForm() {
        // Build inline form inside Tab 2, above the table.
        var container = document.getElementById('evalia-questions-table-container');
        if (!currentRubric || currentRubric.rubricid === 0) {
            showToast(S.error_rubric_first, 'danger');
            return;
        }
        if (currentRubric.status !== 'active') {
            showToast(S.error_rubric_must_be_active, 'danger');
            return;
        }

        renderInto(container, 'generate_questions_form', {
            items: currentRubric.items.map(function(it) {
                return {id: it.id, topic: it.topic, weightlabel: weightLabel(it.difficulty_weight)};
            }),
            defaultcount: defaultQuestionsCount
        }).then(function() {
            document.getElementById('evalia-gen-q-close').addEventListener('click', function() {
                loadQuestionBank();
            });
            document.getElementById('evalia-btn-do-generate-questions').addEventListener('click', function() {
                handleDoGenerateQuestions();
            });
            return container;
        });
    }

    /**
     * Submits the generate-questions form to the web service and reloads the bank showing new drafts.
     */
    function handleDoGenerateQuestions() {
        var itemId = parseInt(document.getElementById('gen-q-item').value, 10);
        var difficulty = document.getElementById('gen-q-difficulty').value;
        var count = parseInt(document.getElementById('gen-q-count').value, 10) || 5;
        var types = [];

        ['multichoice', 'truefalse', 'shortanswer', 'essay'].forEach(function(t) {
            var cb = document.getElementById('qt-' + t);
            if (cb && cb.checked) { types.push(t); }
        });

        if (types.length === 0) {
            showToast(S.error_select_qtype, 'danger');
            return;
        }

        setDisabled('evalia-btn-do-generate-questions', true);
        setLoading('gen-q-loading', true);

        Ajax.call([{
            methodname: 'local_evalia_generate_questions',
            args: {
                courseid:       courseId,
                rubric_item_id: itemId,
                difficulty:     difficulty,
                question_types: types,
                count:          count
            }
        }])[0].then(function(result) {
            setLoading('gen-q-loading', false);
            setDisabled('evalia-btn-do-generate-questions', false);
            if (result.success) {
                showToast(S.questions_generated_ok.replace('{$a}', result.generated), 'success');
                // Reset all filters so the new draft questions are visible
                var topicFilterEl = document.getElementById('evalia-filter-topic');
                var diffFilterEl = document.getElementById('evalia-filter-difficulty');
                var statusEl = document.getElementById('evalia-filter-status');
                if (topicFilterEl) { topicFilterEl.value = ''; }
                if (diffFilterEl) { diffFilterEl.value = ''; }
                if (statusEl) { statusEl.value = 'draft'; }
                // Restore the table container (the form replaced it) then load
                var container = document.getElementById('evalia-questions-table-container');
                if (container) { container.textContent = ''; }
                loadQuestionBank();
            } else {
                showToast(S.error_rubric_save.replace('{$a}', result.message), 'danger');
            }
        }).fail(function(err) {
            setLoading('gen-q-loading', false);
            setDisabled('evalia-btn-do-generate-questions', false);
            Log.error('evalia_teacher: generate_questions failed', err);
            showToast(S.error_questions_generate, 'danger');
        });
    }

    // ─── Tab 3: Exams ────────────────────────────────────────────────────────

    /**
     * Validates the exam form and creates a new exam template via web service.
     */
    function handleCreateExam() {
        var name = document.getElementById('evalia-exam-name');
        var instr = document.getElementById('evalia-exam-instructions');
        var basic = document.getElementById('evalia-exam-basic');
        var medium = document.getElementById('evalia-exam-medium');
        var advanced = document.getElementById('evalia-exam-advanced');
        var timelimit = document.getElementById('evalia-exam-timelimit');
        var toEl = document.getElementById('evalia-exam-timeopen');
        var tcEl = document.getElementById('evalia-exam-timeclose');

        if (!name || !name.value.trim()) {
            showToast(S.error_exam_name_required, 'danger');
            return;
        }

        if (!currentRubric || currentRubric.rubricid === 0) {
            showToast(S.error_rubric_first, 'danger');
            return;
        }

        var basicCount = parseInt(basic.value, 10) || 0;
        var mediumCount = parseInt(medium.value, 10) || 0;
        var advancedCount = parseInt(advanced.value, 10) || 0;

        if ((basicCount + mediumCount + advancedCount) < 1) {
            showToast(S.error_exam_min_questions, 'danger');
            return;
        }

        // Convert datetime-local values to Unix timestamps (0 = not set).
        var timeopen = 0;
        var timeclose = 0;
        if (toEl && toEl.value) {
            timeopen = Math.floor(new Date(toEl.value).getTime() / 1000);
        }
        if (tcEl && tcEl.value) {
            timeclose = Math.floor(new Date(tcEl.value).getTime() / 1000);
        }
        if (timeopen > 0 && timeclose > 0 && timeclose <= timeopen) {
            showToast(S.error_exam_window_order, 'danger');
            return;
        }

        setDisabled('evalia-btn-create-exam', true);

        Ajax.call([{
            methodname: 'local_evalia_create_exam',
            args: {
                courseid:       courseId,
                rubricid:       currentRubric.rubricid,
                name:           name.value.trim(),
                instructions:   instr ? instr.value : '',
                basic_count:    basicCount,
                medium_count:   mediumCount,
                advanced_count: advancedCount,
                time_limit_min: parseInt(timelimit.value, 10) || 60,
                timeopen:       timeopen,
                timeclose:      timeclose
            }
        }])[0].then(function(result) {
            setDisabled('evalia-btn-create-exam', false);
            if (result.success) {
                currentExamId = result.examid;
                showToast(S.exam_created_ok, 'success');
                // Add option to exam selector dropdown and select it
                var selector = document.getElementById('evalia-exam-selector');
                if (selector) {
                    var opt = document.createElement('option');
                    opt.value = result.examid;
                    opt.textContent = name.value.trim();
                    opt.selected = true;
                    selector.appendChild(opt);
                }
                // Show assign button and load empty student list
                var btnAssign = document.getElementById('evalia-btn-assign-exam');
                if (btnAssign) {
                    btnAssign.style.display = '';
                    btnAssign.dataset.examid = result.examid;
                }
                loadStudentExams(result.examid);
                loadExamStats(result.examid);
            } else {
                showToast(S.error_rubric_save.replace('{$a}', result.message), 'danger');
            }
        }).fail(function(err) {
            setDisabled('evalia-btn-create-exam', false);
            Log.error('evalia_teacher: create_exam failed', err);
            showToast(S.error_exam_create, 'danger');
        });
    }

    /**
     * Assigns the current exam to the enrolled students and refreshes the list and stats.
     */
    function handleAssignExam() {
        if (!currentExamId) {
            showToast(S.error_exam_first, 'danger');
            return;
        }

        setDisabled('evalia-btn-assign-exam', true);
        setLoading('evalia-exams-loading', true);

        Ajax.call([{
            methodname: 'local_evalia_assign_exam',
            args: {examid: currentExamId}
        }])[0].then(function(result) {
            setLoading('evalia-exams-loading', false);
            setDisabled('evalia-btn-assign-exam', false);
            if (result.success) {
                showToast(S.exam_assigned_result.replace('{$a->assigned}', result.assigned)
                    .replace('{$a->skipped}', result.skipped), 'success');
                loadStudentExams(currentExamId);
                loadExamStats(currentExamId);
            } else {
                showToast(S.error_rubric_save.replace('{$a}', result.message), 'danger');
            }
        }).fail(function(err) {
            setLoading('evalia-exams-loading', false);
            setDisabled('evalia-btn-assign-exam', false);
            Log.error('evalia_teacher: assign_exam failed', err);
            showToast(S.error_exam_assign, 'danger');
        });
    }

    /**
     * Loads the per-student status list for an exam and updates the grade/publish all buttons.
     *
     * @param {number} examId Exam template ID.
     */
    function loadStudentExams(examId) {
        var container = document.getElementById('evalia-student-exams-container');
        if (!container || !examId) { return; }

        showSpinner(container, 'text-center py-3');

        Ajax.call([{
            methodname: 'local_evalia_get_student_exams',
            args: {examid: examId}
        }])[0].then(function(result) {
            if (!result || result.total === 0) {
                showNotice(container, 'text-muted text-center p-4', S.students_none_enrolled_exam);
                return;
            }
            renderStudentExamsTable(result.students);

            var submittedCount = result.students.filter(function(s) {
                return s.status === 'submitted';
            }).length;
            var gradedCount = result.students.filter(function(s) {
                return s.status === 'graded';
            }).length;

            // Show "grade all" button only when there are submitted exams.
            var btnGradeAll = document.getElementById('evalia-btn-grade-all');
            if (btnGradeAll) {
                btnGradeAll.style.display = submittedCount > 0 ? '' : 'none';
                btnGradeAll.dataset.examid = examId;
                btnGradeAll.dataset.count = submittedCount;
            }
            // Show "publish all" button when there are graded-but-unpublished exams.
            var btnPublishAll = document.getElementById('evalia-btn-publish-all');
            if (btnPublishAll) {
                btnPublishAll.style.display = gradedCount > 0 ? '' : 'none';
                btnPublishAll.dataset.examid = examId;
                btnPublishAll.dataset.count = gradedCount;
                btnPublishAll.textContent = S.btn_publish_grades_count.replace('{$a}', gradedCount);
            }
        }).fail(function(err) {
            Log.error('evalia_teacher: get_student_exams failed', err);
            showNotice(container, 'text-danger p-3', S.error_load_students);
        });
    }

    /**
     * Renders the students table for an exam with status badges and view/grade/publish actions.
     *
     * @param {Array} students Student exam rows returned by local_evalia_get_student_exams.
     */
    function renderStudentExamsTable(students) {
        var container = document.getElementById('evalia-student-exams-container');
        var wwwroot = (typeof M !== 'undefined' && M.cfg) ? M.cfg.wwwroot : '';
        var statusMap = {
            'not_assigned': {cls: 'bg-light text-dark border', label: S.exam_status_not_assigned},
            'assigned':     {cls: 'evalia-badge-assigned', label: S.exam_status_assigned},
            'started':      {cls: 'bg-warning text-dark', label: S.exam_status_started},
            'submitted':    {cls: 'bg-info text-dark', label: S.exam_status_submitted},
            'graded':       {cls: 'bg-success', label: S.exam_status_graded},
            'published':    {cls: 'bg-success', label: S['student:status_published']}
        };

        renderInto(container, 'student_exams', {
            students: students.map(function(s) {
                var st = statusMap[s.status] || {cls: 'bg-light text-dark border', label: s.status};
                var hasexam = s.student_examid > 0;
                return {
                    fullname: s.fullname,
                    statusclass: st.cls,
                    statuslabel: st.label,
                    hasscore: s.score !== null && s.score !== undefined,
                    score: (s.score !== null && s.score !== undefined) ? s.score.toFixed(1) : '',
                    examurl: wwwroot + '/local/evalia/student_exam.php?student_examid=' + s.student_examid,
                    student_examid: s.student_examid,
                    isassigned: hasexam && s.status === 'assigned',
                    isstarted: hasexam && s.status === 'started',
                    issubmitted: hasexam && s.status === 'submitted',
                    isgraded: hasexam && s.status === 'graded',
                    ispublished: hasexam && s.status === 'published'
                };
            })
        }).then(function() {
            container.querySelectorAll('.evalia-btn-publish-single').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    handlePublishGrade(parseInt(btn.dataset.studentExamid, 10), btn);
                });
            });
            return container;
        });
    }

    // ─── Tab 4: Portfolios ───────────────────────────────────────────────────

    /**
     * Loads the course student portfolio list via web service and renders it.
     */
    function loadPortfolio() {
        var container = document.getElementById('evalia-portfolio-list-container');
        if (!container) { return; }

        setLoading('evalia-portfolio-loading', true);
        container.textContent = '';

        Ajax.call([{
            methodname: 'local_evalia_get_student_portfolio',
            args: {courseid: courseId}
        }])[0].then(function(result) {
            setLoading('evalia-portfolio-loading', false);
            if (!result || result.total === 0) {
                showNotice(container, 'text-muted text-center p-4', S.students_none_enrolled_course);
                return;
            }
            renderPortfolioTable(result.students);
        }).fail(function(err) {
            setLoading('evalia-portfolio-loading', false);
            Log.error('evalia_teacher: get_student_portfolio failed', err);
            if (container) {
                showNotice(container, 'text-danger p-3', S.error_load_portfolio);
            }
        });
    }

    /**
     * Renders the portfolio students table and wires row selection.
     *
     * @param {Array} students Students returned by local_evalia_get_student_portfolio.
     */
    function renderPortfolioTable(students) {
        var container = document.getElementById('evalia-portfolio-list-container');

        renderInto(container, 'portfolio_students', {
            students: students.map(function(s) {
                return {
                    userid: s.userid,
                    fullname: s.fullname,
                    active: s.userid === selectedPortfolioUserId,
                    hasportfolio: !!s.has_portfolio,
                    totalexams: s.total_exams,
                    avggrade: s.has_portfolio ? s.avg_grade.toFixed(1) : ''
                };
            })
        }).then(function() {
            container.querySelectorAll('.evalia-portfolio-row').forEach(function(row) {
                var open = function() {
                    showStudentDetail(parseInt(row.dataset.userid, 10), row.dataset.fullname);
                    container.querySelectorAll('.evalia-portfolio-row').forEach(function(r) {
                        r.classList.remove('table-active');
                    });
                    row.classList.add('table-active');
                };
                row.addEventListener('click', open);
                row.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        open();
                    }
                });
            });

            // Re-select previously chosen student if still in list.
            if (selectedPortfolioUserId > 0 &&
                    !container.querySelector('[data-userid="' + selectedPortfolioUserId + '"]')) {
                // Student gone: reset the detail panel.
                hideStudentDetail();
            }
            return container;
        });
    }

    /**
     * Opens the portfolio detail panel for a student and loads their exam history.
     *
     * @param {number} userid Student user ID.
     * @param {string} fullname Student full name.
     */
    function showStudentDetail(userid, fullname) {
        selectedPortfolioUserId = userid;

        var placeholder = document.getElementById('evalia-portfolio-detail-placeholder');
        var panel = document.getElementById('evalia-portfolio-detail-panel');
        if (placeholder) { placeholder.style.display = 'none'; }
        if (panel) { panel.style.display = ''; }

        var nameEl = document.getElementById('evalia-portfolio-student-name');
        if (nameEl) { nameEl.textContent = fullname; }

        // Reset notes panel
        var notesContainer = document.getElementById('evalia-portfolio-notes-container');
        if (notesContainer) {
            showNotice(notesContainer, 'text-muted small', S.loading_ellipsis);
        }
        var noteInput = document.getElementById('evalia-portfolio-note-input');
        if (noteInput) { noteInput.value = ''; }

        // Reset exams panel and load history
        var examsContainer = document.getElementById('evalia-portfolio-exams-container');
        if (examsContainer) {
            showSpinner(examsContainer, 'text-muted text-center py-3', S.loading_exam_history);
        }

        // Update stats header
        updatePortfolioStats(userid);

        // Switch to the exam history tab
        switchPortfolioTab('exams');

        // Load exam history
        loadPortfolioExams(userid);

        // Notes are loaded by switchPortfolioTab('notes') on demand.
    }

    /**
     * Clears the selected student and hides the portfolio detail panel.
     */
    function hideStudentDetail() {
        selectedPortfolioUserId = 0;
        var placeholder = document.getElementById('evalia-portfolio-detail-placeholder');
        var panel = document.getElementById('evalia-portfolio-detail-panel');
        if (placeholder) { placeholder.style.display = ''; }
        if (panel) { panel.style.display = 'none'; }
    }

    /**
     * Fills the detail header stats (exam count, average) from the student's row in the list.
     *
     * @param {number} userid Student user ID.
     */
    function updatePortfolioStats(userid) {
        var statsEl = document.getElementById('evalia-portfolio-student-stats');
        if (!statsEl) { return; }

        // Read from the already-rendered table row for this student
        var listContainer = document.getElementById('evalia-portfolio-list-container');
        var row = listContainer ? listContainer.querySelector('[data-userid="' + userid + '"]') : null;
        if (!row) {
            statsEl.textContent = '';
            return;
        }
        var cells = row.querySelectorAll('td');
        // Cells[1]=exams, cells[2]=avg grade
        var examsText = cells[1] ? cells[1].textContent.trim() : '0';
        var gradeText = cells[2] ? cells[2].textContent.trim() : '—';
        var examsLabel = (examsText !== '1')
            ? S.exam_count_plural.replace('{$a}', examsText)
            : S.exam_count_singular.replace('{$a}', examsText);
        renderInto(statsEl, 'portfolio_stats', {
            examslabel: examsLabel,
            averagelabel: S.portfolio_average_label.replace('{$a}', gradeText)
        });
    }

    /**
     * Switches the portfolio detail between the exams and notes sub-tabs, loading notes on demand.
     *
     * @param {string} tab Sub-tab to show ('exams' or 'notes').
     */
    function switchPortfolioTab(tab) {
        var examsPane = document.getElementById('pdt-exams');
        var notesPane = document.getElementById('pdt-notes');
        var examsBtn = document.getElementById('pdt-exams-btn');
        var notesBtn = document.getElementById('pdt-notes-btn');
        if (!examsPane || !notesPane) { return; }

        if (tab === 'exams') {
            examsPane.style.display = '';
            notesPane.style.display = 'none';
            if (examsBtn) { examsBtn.classList.add('active'); }
            if (notesBtn) { notesBtn.classList.remove('active'); }
        } else {
            examsPane.style.display = 'none';
            notesPane.style.display = '';
            if (notesBtn) { notesBtn.classList.add('active'); }
            if (examsBtn) { examsBtn.classList.remove('active'); }
            loadStudentNotes(selectedPortfolioUserId);
        }
    }

    /**
     * Wires the portfolio detail sub-tab buttons.
     */
    function initPortfolioTabButtons() {
        var examsBtn = document.getElementById('pdt-exams-btn');
        var notesBtn = document.getElementById('pdt-notes-btn');
        if (examsBtn) {
            examsBtn.addEventListener('click', function() { switchPortfolioTab('exams'); });
        }
        if (notesBtn) {
            notesBtn.addEventListener('click', function() { switchPortfolioTab('notes'); });
        }
    }

    /**
     * Loads a student's exam history in the course and renders it.
     *
     * @param {number} userid Student user ID.
     */
    function loadPortfolioExams(userid) {
        var container = document.getElementById('evalia-portfolio-exams-container');
        if (!container) { return; }
        if (userid !== selectedPortfolioUserId) { return; }

        Ajax.call([{
            methodname: 'local_evalia_get_student_exam_history',
            args: {userid: userid, courseid: courseId}
        }])[0].then(function(result) {
            if (userid !== selectedPortfolioUserId) { return; }
            if (!result || result.total === 0) {
                showNotice(container, 'text-muted text-center py-4', S.no_exams_assigned_yet);
                return;
            }
            renderPortfolioExams(result.exams);
        }).fail(function(err) {
            Log.error('evalia_teacher: get_student_exam_history failed', err);
            if (container) {
                showNotice(container, 'text-danger small p-3', S.error_load_exam_history);
            }
        });
    }

    /**
     * Renders the student's exam history table.
     *
     * @param {Array} exams Exams returned by local_evalia_get_student_exam_history.
     */
    function renderPortfolioExams(exams) {
        var container = document.getElementById('evalia-portfolio-exams-container');
        if (!container) { return; }

        var statusClasses = {
            'assigned':  'bg-secondary',
            'started':   'bg-warning text-dark',
            'submitted': 'bg-info text-dark',
            'graded':    'bg-success'
        };
        var locale = document.documentElement.lang || undefined;

        renderInto(container, 'portfolio_exams', {
            exams: exams.map(function(ex) {
                var graded = ex.status === 'graded';
                var pct = (graded && ex.max_score > 0) ? (ex.score / ex.max_score * 100) : 0;
                var canview = ex.status === 'graded' || ex.status === 'submitted';
                return {
                    name: ex.exam_name,
                    statusclass: statusClasses[ex.status] || 'bg-secondary',
                    statuslabel: ex.status_label,
                    graded: graded,
                    scoreclass: 'text-' + (pct >= 60 ? 'success' : (pct >= 40 ? 'warning' : 'danger')),
                    score: graded ? ex.score.toFixed(1) : '',
                    maxscore: graded ? ex.max_score.toFixed(1) : '',
                    date: ex.timesubmitted > 0 ? new Date(ex.timesubmitted * 1000).toLocaleDateString(locale) : '—',
                    totalquestions: ex.total_questions,
                    viewurl: canview
                        ? M.cfg.wwwroot + '/local/evalia/student_exam.php?student_examid=' + ex.student_examid : ''
                };
            })
        });
    }

    /**
     * Loads a student's portfolio observations and renders them.
     *
     * @param {number} userid Student user ID.
     */
    function loadStudentNotes(userid) {
        var container = document.getElementById('evalia-portfolio-notes-container');
        if (!container) { return; }
        if (userid !== selectedPortfolioUserId) { return; }

        showNotice(container, 'text-muted small', S.loading_observations);

        Ajax.call([{
            methodname: 'local_evalia_get_portfolio_notes',
            args: {userid: userid, courseid: courseId}
        }])[0].then(function(result) {
            if (userid !== selectedPortfolioUserId) { return; }
            if (!result || result.total === 0) {
                showNotice(container, 'text-muted small', S.portfolio_note_empty);
                return;
            }
            renderNotesList(result.notes);
        }).fail(function(err) {
            Log.error('evalia_teacher: get_portfolio_notes failed', err);
            if (container) {
                showNotice(container, 'text-danger small', S.error_load_notes);
            }
        });
    }

    /**
     * Renders the list of portfolio observations.
     *
     * @param {Array} notes Notes returned by local_evalia_get_portfolio_notes.
     */
    function renderNotesList(notes) {
        var container = document.getElementById('evalia-portfolio-notes-container');
        if (!container) { return; }

        var locale = document.documentElement.lang || undefined;
        renderInto(container, 'portfolio_notes', {
            notes: notes.map(function(n) {
                var date = new Date(n.timecreated * 1000);
                return {
                    author: n.author_name,
                    date: date.toLocaleDateString(locale) + ' ' +
                        date.toLocaleTimeString(locale, {hour: '2-digit', minute: '2-digit'}),
                    text: n.note_text
                };
            })
        });
    }

    /**
     * Saves a new portfolio observation for the selected student.
     */
    function handleAddNote() {
        if (!selectedPortfolioUserId) { return; }
        var input = document.getElementById('evalia-portfolio-note-input');
        var text = input ? input.value.trim() : '';
        if (!text) {
            showToast(S.error_note_required, 'danger');
            return;
        }

        setDisabled('evalia-btn-add-note', true);

        Ajax.call([{
            methodname: 'local_evalia_add_portfolio_note',
            args: {userid: selectedPortfolioUserId, courseid: courseId, note_text: text}
        }])[0].then(function(result) {
            setDisabled('evalia-btn-add-note', false);
            if (result.success) {
                if (input) { input.value = ''; }
                showToast(S.portfolio_note_saved, 'success');
                loadStudentNotes(selectedPortfolioUserId);
                // Reload portfolio list to reflect updated last_activity
                loadPortfolio();
            } else {
                showToast(S.error_rubric_save.replace('{$a}', result.message), 'danger');
            }
        }).fail(function(err) {
            setDisabled('evalia-btn-add-note', false);
            Log.error('evalia_teacher: add_portfolio_note failed', err);
            showToast(S.error_note_save, 'danger');
        });
    }

    // ─── Tab 3: Grade all with AI ────────────────────────────────────────────

    /**
     * Grades all submitted exams of the current exam with AI after confirmation.
     */
    function handleGradeAll() {
        var btn = document.getElementById('evalia-btn-grade-all');
        var statusEl = document.getElementById('evalia-grade-all-status');
        if (!btn || !currentExamId) { return; }

        var count = parseInt(btn.dataset.count, 10) || 0;
        if (count === 0) { return; }

        if (!window.confirm(S.confirm_grade_all.replace('{$a}', count))) {
            return;
        }

        btn.disabled = true;
        btn.textContent = S.grading_in_progress;
        if (statusEl) { statusEl.textContent = ''; }

        Ajax.call([{
            methodname: 'local_evalia_grade_all_exams',
            args: {examid: currentExamId}
        }])[0].then(function(result) {
            btn.disabled = false;
            btn.style.display = 'none';
            var msg = S.grade_all_result.replace('{$a->graded}', result.graded);
            if (result.skipped > 0) { msg += S.grade_all_skipped_suffix.replace('{$a}', result.skipped); }
            if (statusEl) {
                statusEl.textContent = msg;
                statusEl.className = 'text-success small';
            }
            showToast(msg, 'success');
            // Refresh table and stats.
            loadStudentExams(currentExamId);
            loadExamStats(currentExamId);
        }).fail(function(err) {
            btn.disabled = false;
            btn.textContent = S.btn_grade_all_with_ai;
            Log.error('evalia_teacher: grade_all_exams failed', err);
            if (statusEl) {
                statusEl.textContent = S.error_grade_all;
                statusEl.className = 'text-danger small';
            }
            showToast(S.error_grade_exams, 'danger');
        });
    }

    /**
     * Publishes one student's exam grade to the gradebook.
     *
     * @param {number} studentExamId Student exam ID.
     * @param {HTMLElement} btn Publish button that was clicked.
     */
    function handlePublishGrade(studentExamId, btn) {
        if (!studentExamId) { return; }
        if (btn) { btn.disabled = true; btn.textContent = S.publishing_ellipsis; }

        Ajax.call([{
            methodname: 'local_evalia_publish_grade',
            args: {student_examid: studentExamId, override_score: -1}
        }])[0].then(function(result) {
            if (result.success) {
                showToast(S.grade_published_ok, 'success');
                loadStudentExams(currentExamId);
            } else {
                if (btn) { btn.disabled = false; btn.textContent = S.btn_publish_single; }
                showToast(S.error_rubric_save.replace('{$a}', result.message), 'danger');
            }
        }).fail(function(err) {
            if (btn) { btn.disabled = false; btn.textContent = S.btn_publish_single; }
            Log.error('evalia_teacher: publish_grade failed', err);
            showToast(S.error_publish_grade, 'danger');
        });
    }

    /**
     * Publishes all graded exams of the current exam to the gradebook after confirmation.
     */
    function handlePublishAllGrades() {
        var btn = document.getElementById('evalia-btn-publish-all');
        var statusEl = document.getElementById('evalia-grade-all-status');
        if (!btn || !currentExamId) { return; }

        var count = parseInt(btn.dataset.count, 10) || 0;
        if (count === 0) { return; }

        if (!window.confirm(S.confirm_publish_all.replace('{$a}', count))) {
            return;
        }

        btn.disabled = true;
        btn.textContent = S.publishing_all;
        if (statusEl) { statusEl.textContent = ''; }

        Ajax.call([{
            methodname: 'local_evalia_publish_all_grades',
            args: {examid: currentExamId}
        }])[0].then(function(result) {
            btn.disabled = false;
            btn.style.display = 'none';
            var msg = S.grades_published_result.replace('{$a}', result.published);
            if (statusEl) { statusEl.textContent = msg; statusEl.className = 'text-success small'; }
            showToast(msg, 'success');
            loadStudentExams(currentExamId);
            loadExamStats(currentExamId);
        }).fail(function(err) {
            btn.disabled = false;
            btn.textContent = S.btn_publish_grades_count.replace('{$a}', count);
            Log.error('evalia_teacher: publish_all_grades failed', err);
            if (statusEl) { statusEl.textContent = S.error_publish_all; statusEl.className = 'text-danger small'; }
            showToast(S.error_publish_grades, 'danger');
        });
    }

    // ─── Tab 3: Exam statistics ─────────────────────────────────────────────

    /**
     * Loads the statistics of an exam and renders them.
     *
     * @param {number} examId Exam template ID.
     */
    function loadExamStats(examId) {
        var section = document.getElementById('evalia-stats-section');
        if (!section || !examId) { return; }
        section.style.display = '';

        Ajax.call([{
            methodname: 'local_evalia_get_exam_stats',
            args: {examid: examId}
        }])[0].then(function(r) {
            renderExamStats(r);
        }).fail(function(err) {
            Log.error('evalia_teacher: get_exam_stats failed', err);
        });
    }

    /**
     * Renders the exam statistics: summary cards, score bands and most failed questions.
     *
     * @param {Object} r Stats returned by local_evalia_get_exam_stats.
     */
    function renderExamStats(r) {
        // ── Summary cards ────────────────────────────────────────────────────
        var summaryEl = document.getElementById('evalia-stats-summary');
        if (summaryEl) {
            var total = r.counts.assigned + r.counts.started + r.counts.submitted + r.counts.graded;
            var passRate = r.graded > 0 ? Math.round(r.pass_count / r.graded * 100) : 0;
            renderInto(summaryEl, 'exam_stats_summary', {
                cards: [
                    {icon: '👥', label: S.stat_total, value: total, color: 'secondary'},
                    {icon: '✅', label: S.stat_graded, value: r.graded, color: 'success'},
                    {icon: '📤', label: S.stat_submitted, value: r.counts.submitted, color: 'info'},
                    {icon: '⭐', label: S.stat_average, value: r.graded > 0 ? r.avg_score.toFixed(1) + '/10' : '—',
                        color: 'primary'},
                    {icon: '🎯', label: S.stat_passed, value: r.graded > 0 ? passRate + '%' : '—',
                        color: passRate >= 50 ? 'success' : 'warning'}
                ]
            });
        }

        // ── Score bands ───────────────────────────────────────────────────────
        var bandsEl = document.getElementById('evalia-stats-bands');
        if (bandsEl && r.bands) {
            var maxCount = Math.max.apply(null, r.bands.map(function(b) { return b.count; })) || 1;
            var bandColors = ['danger', 'warning', 'warning', 'success', 'success'];
            renderInto(bandsEl, 'exam_stats_bands', {
                empty: r.bands.length === 0,
                bands: r.bands.map(function(b, i) {
                    return {label: b.label, count: b.count, pct: Math.round(b.count / maxCount * 100),
                        color: bandColors[i] || 'secondary'};
                })
            });
        }

        // ── Top failed questions ───────────────────────────────────────────────
        var failedEl = document.getElementById('evalia-stats-failed');
        if (failedEl) {
            // Difficulty badge classes, keyed by the resolved label (styles.css: teal, blue, orange).
            var diffClass = {};
            diffClass[S.difficulty_basic] = 'evalia-diff-basic';
            diffClass[S.difficulty_medium] = 'evalia-diff-medium';
            diffClass[S.difficulty_advanced] = 'evalia-diff-advanced';
            var failed = r.top_failed || [];
            renderInto(failedEl, 'exam_stats_failed', {
                empty: failed.length === 0,
                questions: failed.map(function(q, idx) {
                    return {
                        num: idx + 1,
                        topic: q.topic || '',
                        difficulty: q.difficulty || '',
                        difficultyclass: diffClass[q.difficulty] || 'evalia-diff-other',
                        pct: q.total > 0 ? Math.round(q.fail_count / q.total * 100) : 0,
                        stem: q.stem.length > 100 ? q.stem.substring(0, 97) + '…' : q.stem
                    };
                })
            });
        }
    }


    // ─── RAG: Index course material ─────────────────────────────────────────

    /**
     * Indexes the course material for RAG and reports the indexed sources, chunks and errors.
     */
    function handleIndexCourse() {
        var btn = document.getElementById('evalia-btn-index-course');
        var statusEl = document.getElementById('evalia-index-status');
        if (!btn) { return; }

        btn.disabled = true;
        setLoading('evalia-index-loading', true);
        if (statusEl) { statusEl.textContent = ''; }

        Ajax.call([{
            methodname: 'local_evalia_index_course',
            args: {courseid: courseId}
        }])[0].then(function(result) {
            btn.disabled = false;
            setLoading('evalia-index-loading', false);
            var indexed = result.indexed || [];
            var errors = result.errors || [];
            var chunks = result.total_chunks || 0;

            var parts = [];
            if (indexed.length > 0) {
                parts.push(S.index_result_sources_chunks.replace('{$a->sources}', indexed.length).replace('{$a->chunks}', chunks));
            }
            if (errors.length > 0) {
                parts.push(S.index_result_errors.replace('{$a}', errors.length));
            }
            if (parts.length === 0) {
                parts.push(S.index_result_nothing);
            }

            var msg = parts.join(' · ');
            if (statusEl) { statusEl.textContent = msg; }
            showToast(result.success ? ('✔ ' + msg) : ('⚠ ' + msg),
                      result.success ? 'success' : 'warning');
        }).fail(function(err) {
            btn.disabled = false;
            setLoading('evalia-index-loading', false);
            Log.error('evalia_teacher: index_course failed', err);
            if (statusEl) { statusEl.textContent = S.error_index_generic; }
            showToast(S.error_index_course, 'danger');
        });
    }

    // ─── Init ────────────────────────────────────────────────────────────────

    /**
     * Copy the student page URL to the clipboard; the button shows a tick for a moment.
     */
    function initCopyStudentUrl() {
        var btn = document.getElementById('evalia-btn-copy-student-url');
        var url = document.getElementById('evalia-student-url');
        if (!btn || !url || !navigator.clipboard) {
            return;
        }
        var label = btn.textContent;
        btn.addEventListener('click', function() {
            navigator.clipboard.writeText(url.textContent.trim()).then(function() {
                btn.textContent = '✅';
                setTimeout(function() {
                    btn.textContent = label;
                }, 1500);
                return null;
            }).catch(Log.error);
        });
    }

    return {
        init: function(config) {
            loadStrings();
            courseId = config.courseid;
            if (config.defaults && config.defaults.questions_default_count) {
                defaultQuestionsCount = parseInt(config.defaults.questions_default_count, 10) || 5;
            }

            // Tab 1 — Rubric + material indexing
            loadRubric();

            var btnIndex = document.getElementById('evalia-btn-index-course');
            if (btnIndex) { btnIndex.addEventListener('click', handleIndexCourse); }

            var btnGenerate = document.getElementById('evalia-btn-generate-rubric');
            if (btnGenerate) { btnGenerate.addEventListener('click', handleGenerateRubric); }

            var btnSave = document.getElementById('evalia-btn-save-rubric');
            if (btnSave) { btnSave.addEventListener('click', function() { handleSaveRubric(false); }); }

            var btnActivate = document.getElementById('evalia-btn-activate-rubric');
            if (btnActivate) { btnActivate.addEventListener('click', function() { handleSaveRubric(true); }); }

            // Tab 2 — Question bank (lazy load on first click)
            var questionsLoaded = false;
            var tabQuestionsBtn = document.getElementById('tab-questions-btn');
            if (tabQuestionsBtn) {
                tabQuestionsBtn.addEventListener('shown.bs.tab', function() {
                    if (!questionsLoaded) {
                        questionsLoaded = true;
                        refreshTopicFilter();
                        loadQuestionBank();
                    }
                });
            }

            ['evalia-filter-topic', 'evalia-filter-difficulty', 'evalia-filter-status'].forEach(function(id) {
                var el = document.getElementById(id);
                if (el) { el.addEventListener('change', loadQuestionBank); }
            });

            var btnGenQ = document.getElementById('evalia-btn-generate-questions');
            if (btnGenQ) { btnGenQ.addEventListener('click', showGenerateQuestionsForm); }

            // Tab 3 — Exams (lazy load on first click)
            // If teacher.php found an existing exam, pre-populate state so the
            // assign button and student list work without creating a new exam.
            if (config.examid > 0) {
                currentExamId = config.examid;
                var btnAssignPre = document.getElementById('evalia-btn-assign-exam');
                if (btnAssignPre) { btnAssignPre.style.display = ''; }
            }

            // Exam selector — switch between existing exams
            var examSelector = document.getElementById('evalia-exam-selector');
            if (examSelector) {
                examSelector.addEventListener('change', function() {
                    var selectedId = parseInt(this.value, 10);
                    if (selectedId > 0) {
                        currentExamId = selectedId;
                        var btnAssign = document.getElementById('evalia-btn-assign-exam');
                        if (btnAssign) { btnAssign.style.display = ''; }
                        loadStudentExams(currentExamId);
                        loadExamStats(currentExamId);
                        loadFeedbackPromptEditor(currentExamId);
                    }
                });
            }

            var examsLoaded = false;
            var tabExamsBtn = document.getElementById('tab-exams-btn');
            if (tabExamsBtn) {
                tabExamsBtn.addEventListener('shown.bs.tab', function() {
                    if (!examsLoaded) {
                        examsLoaded = true;
                        if (!currentRubric) { loadRubric(); }
                        // Auto-load student list if an exam already exists
                        if (currentExamId) {
                            loadStudentExams(currentExamId);
                            loadExamStats(currentExamId);
                            loadFeedbackPromptEditor(currentExamId);
                        }
                    }
                });
            }

            // ── Feedback prompt editor (Tab 3) ────────────────────────────────
            /**
             * Shows the feedback prompt editor for an exam and wires its save and reset buttons.
             *
             * @param {number} examId Exam template ID.
             */
            function loadFeedbackPromptEditor(examId) {
                var section = document.getElementById('evalia-feedback-prompt-section');
                var textarea = document.getElementById('evalia-feedback-prompt-textarea');
                if (!section || !textarea) { return; }
                section.style.display = '';

                // Load current prompt from DB via teacher.php config or fetch via WS.
                // Use the default prompt text when field is empty.
                var defaultPrompt = S.default_feedback_prompt;

                // Populate from config if available, otherwise use default.
                var saved = (config.feedback_prompt || '').trim();
                textarea.value = saved || defaultPrompt;

                // Save button
                var btnSave = document.getElementById('evalia-btn-save-prompt');
                var statusEl = document.getElementById('evalia-prompt-save-status');
                if (btnSave) {
                    btnSave.addEventListener('click', function() {
                        btnSave.disabled = true;
                        if (statusEl) { statusEl.textContent = S.prompt_saving; }
                        Ajax.call([{
                            methodname: 'local_evalia_save_feedback_prompt',
                            args: {examid: examId, prompt: textarea.value}
                        }])[0].then(function(result) {
                            btnSave.disabled = false;
                            if (statusEl) {
                                statusEl.textContent = result.success ? S.prompt_saved_ok : result.message;
                                setTimeout(function() { statusEl.textContent = ''; }, 3000);
                            }
                        }).fail(function() {
                            btnSave.disabled = false;
                            if (statusEl) { statusEl.textContent = S.error_prompt_save; }
                        });
                    });
                }

                // Reset button
                var btnReset = document.getElementById('evalia-btn-reset-prompt');
                if (btnReset) {
                    btnReset.addEventListener('click', function() {
                        if (window.confirm(S.confirm_reset_prompt)) {
                            textarea.value = defaultPrompt;
                        }
                    });
                }
            }

            var btnCreateExam = document.getElementById('evalia-btn-create-exam');
            if (btnCreateExam) { btnCreateExam.addEventListener('click', handleCreateExam); }

            var btnAssignExam = document.getElementById('evalia-btn-assign-exam');
            if (btnAssignExam) { btnAssignExam.addEventListener('click', handleAssignExam); }

            var btnGradeAll = document.getElementById('evalia-btn-grade-all');
            if (btnGradeAll) { btnGradeAll.addEventListener('click', handleGradeAll); }
            var btnPublishAll = document.getElementById('evalia-btn-publish-all');
            if (btnPublishAll) { btnPublishAll.addEventListener('click', handlePublishAllGrades); }

            var btnRefreshStats = document.getElementById('evalia-btn-refresh-stats');
            if (btnRefreshStats) {
                btnRefreshStats.addEventListener('click', function() {
                    if (currentExamId) { loadExamStats(currentExamId); }
                });
            }

            // Tab 4 — Portfolios (lazy load on first click)
            var portfolioLoaded = false;
            var tabPortfolioBtn = document.getElementById('tab-portfolio-btn');
            if (tabPortfolioBtn) {
                tabPortfolioBtn.addEventListener('shown.bs.tab', function() {
                    if (!portfolioLoaded) {
                        portfolioLoaded = true;
                        loadPortfolio();
                    }
                });
            }

            var btnReloadPortfolio = document.getElementById('evalia-btn-reload-portfolio');
            if (btnReloadPortfolio) {
                btnReloadPortfolio.addEventListener('click', function() {
                    portfolioLoaded = true;
                    loadPortfolio();
                });
            }

            initPortfolioTabButtons();
            initSourcesPanel();
            initCopyStudentUrl();
            initExamWindowToggle();

            var btnAddNote = document.getElementById('evalia-btn-add-note');
            if (btnAddNote) { btnAddNote.addEventListener('click', handleAddNote); }

            // Bootstrap 5 global not available in Moodle AMD context — manual tab switching.
            document.querySelectorAll('#evaliaTabs [data-bs-toggle="tab"]').forEach(function(el) {
                el.addEventListener('click', function(e) {
                    e.preventDefault();
                    var targetId = el.getAttribute('data-bs-target') || el.getAttribute('href');
                    // Deactivate all tabs and panes
                    document.querySelectorAll('#evaliaTabs [data-bs-toggle="tab"]').forEach(function(t) {
                        t.classList.remove('active');
                        t.setAttribute('aria-selected', 'false');
                    });
                    document.querySelectorAll('#evaliaTabContent .tab-pane').forEach(function(p) {
                        p.classList.remove('show', 'active');
                    });
                    // Activate clicked tab and its pane
                    el.classList.add('active');
                    el.setAttribute('aria-selected', 'true');
                    var pane = document.querySelector(targetId);
                    if (pane) { pane.classList.add('show', 'active'); }
                    // Fire shown.bs.tab so lazy-load listeners trigger
                    el.dispatchEvent(new CustomEvent('shown.bs.tab', {bubbles: true}));
                });
            });

            // Pre-select tab when arriving from block_evalia action buttons.
            if (config.action) {
                var tabMap = {
                    'questions': '#tab-questions-btn',
                    'exams':     '#tab-exams-btn',
                    'portfolio': '#tab-portfolio-btn'
                };
                var tabSelector = tabMap[config.action];
                if (tabSelector) {
                    setTimeout(function() {
                        var tabBtn = document.querySelector(tabSelector);
                        if (tabBtn) { tabBtn.click(); }
                    }, 150);
                }
            }

            // Pre-fill dates in Tab 3 form if passed from block.
            if (config.startdate || config.enddate) {
                setTimeout(function() {
                    var startEl = document.getElementById('evalia-start-date');
                    var endEl = document.getElementById('evalia-end-date');
                    if (startEl && config.startdate) { startEl.value = config.startdate; }
                    if (endEl && config.enddate) { endEl.value = config.enddate; }
                }, 300);
            }

            // ── Modal: Workflow ───────────────────────────────────────────────
            (function() {
                var modal = document.getElementById('evalia-workflow-modal');
                var btnWorkflow = document.getElementById('evalia-btn-workflow');
                var closeBtn = document.getElementById('evalia-workflow-modal-close');
                var header = document.getElementById('evalia-workflow-modal-header');
                if (!modal || !btnWorkflow) { return; }

                btnWorkflow.addEventListener('click', function() {
                    modal.style.display = modal.style.display === 'none' ? '' : 'none';
                });
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() { modal.style.display = 'none'; });
                }

                // Drag by header
                var isDragging = false,
startX, startY, origLeft, origTop;
                if (header) {
                    header.addEventListener('mousedown', function(e) {
                        if (e.target === closeBtn) { return; }
                        isDragging = true;
                        startX = e.clientX;
                        startY = e.clientY;
                        var rect = modal.getBoundingClientRect();
                        origLeft = rect.left;
                        origTop = rect.top;
                        modal.style.transform = 'none';
                        modal.style.left = origLeft + 'px';
                        modal.style.top = origTop + 'px';
                        e.preventDefault();
                    });
                }
                document.addEventListener('mousemove', function(e) {
                    if (!isDragging) { return; }
                    var dx = e.clientX - startX;
                    var dy = e.clientY - startY;
                    modal.style.left = (origLeft + dx) + 'px';
                    modal.style.top = (origTop + dy) + 'px';
                });
                document.addEventListener('mouseup', function() { isDragging = false; });
            }());

            // ── FAB: EVAL-IA Assistant ────────────────────────────────────────
            (function() {
                var fab = document.getElementById('evalia-fab');
                var panel = document.getElementById('evalia-chat-panel');
                var closeBtn = document.getElementById('evalia-chat-close');
                var sendBtn = document.getElementById('evalia-chat-send');
                var input = document.getElementById('evalia-chat-input');
                var messages = document.getElementById('evalia-chat-messages');
                if (!fab) { return; }

                /**
                 * Appends a message bubble to the chat panel and scrolls to it.
                 *
                 * @param {string} text Message text.
                 * @param {string} role Message author ('user' or 'assistant').
                 */
                function appendMsg(text, role) {
                    if (!messages) { return; }
                    var div = document.createElement('div');
                    div.className = 'evalia-msg evalia-msg--' + role;
                    div.textContent = text;
                    messages.appendChild(div);
                    messages.scrollTop = messages.scrollHeight;
                }

                /**
                 * Appends a typing indicator bubble to the chat panel.
                 *
                 * @returns {HTMLElement} The indicator element, so it can be removed later.
                 */
                function appendTyping() {
                    var div = document.createElement('div');
                    div.className = 'evalia-msg evalia-msg--assistant evalia-msg--typing';
                    div.textContent = '…';
                    messages.appendChild(div);
                    messages.scrollTop = messages.scrollHeight;
                    return div;
                }

                /**
                 * Sends the chat input text to the assistant web service and shows the reply.
                 */
                function sendMessage() {
                    if (!input) { return; }
                    var text = input.value.trim();
                    if (!text) { return; }
                    input.value = '';
                    appendMsg(text, 'user');
                    var typingEl = appendTyping();
                    if (sendBtn) { sendBtn.disabled = true; }

                    Ajax.call([{
                        methodname: 'local_saipa_admin_chat',
                        args: {message: text, context: 'evalia_teacher'}
                    }])[0].then(function(r) {
                        if (typingEl && typingEl.parentNode) { typingEl.parentNode.removeChild(typingEl); }
                        appendMsg(r.reply || S.fab_no_response, 'assistant');
                        return r;
                    }).fail(function(err) {
                        if (typingEl && typingEl.parentNode) { typingEl.parentNode.removeChild(typingEl); }
                        appendMsg(S.fab_connect_error, 'assistant');
                        Log.error('evalia_teacher: FAB chat error', err);
                    }).always(function() {
                        if (sendBtn) { sendBtn.disabled = false; }
                    });
                }

                // Toggle panel on FAB click (drag detection)
                var FAB_STORAGE_KEY = 'evalia_fab_pos';
                var isDragging = false,
dragMoved = false;
                var dragStartX, dragStartY, fabStartLeft, fabStartTop;

                /**
                 * Moves the FAB to a position clamped to the viewport and repositions the chat panel next to it.
                 *
                 * @param {number} left Desired left offset in pixels.
                 * @param {number} top Desired top offset in pixels.
                 */
                function applyFabPos(left, top) {
                    var w = window.innerWidth,
h = window.innerHeight;
                    var fw = fab.offsetWidth,
fh = fab.offsetHeight;
                    left = Math.max(0, Math.min(left, w - fw));
                    top = Math.max(0, Math.min(top, h - fh));
                    fab.style.left = left + 'px';
                    fab.style.top = top + 'px';
                    fab.style.right = 'auto';
                    fab.style.bottom = 'auto';
                    if (panel) {
                        panel.style.left = Math.max(0, Math.min(left + fw - 360, w - 364)) + 'px';
                        panel.style.top = Math.max(0, top - 470) + 'px';
                        panel.style.right = 'auto';
                        panel.style.bottom = 'auto';
                    }
                }

                /**
                 * Persists the FAB position in localStorage (errors ignored).
                 *
                 * @param {number} l Left offset in pixels.
                 * @param {number} t Top offset in pixels.
                 */
                function saveFabPos(l, t) {
                    try { localStorage.setItem(FAB_STORAGE_KEY, JSON.stringify({l: l, t: t})); } catch (e) {}
                }

                /**
                 * Restores the FAB position saved in localStorage, if any.
                 *
                 * @returns {boolean} True if a saved position was applied.
                 */
                function restoreFabPos() {
                    try {
                        var raw = localStorage.getItem(FAB_STORAGE_KEY);
                        if (!raw) { return false; }
                        var pos = JSON.parse(raw);
                        applyFabPos(pos.l, pos.t);
                        return true;
                    } catch (e) { return false; }
                }

                restoreFabPos();

                fab.addEventListener('mousedown', function(e) {
                    if (e.button !== 0) { return; }
                    isDragging = true;
                    dragMoved = false;
                    dragStartX = e.clientX;
                    dragStartY = e.clientY;
                    var rect = fab.getBoundingClientRect();
                    fabStartLeft = rect.left;
                    fabStartTop = rect.top;
                    fab.classList.add('evalia-fab--dragging');
                    e.preventDefault();
                });

                document.addEventListener('mousemove', function(e) {
                    if (!isDragging) { return; }
                    var dx = e.clientX - dragStartX;
                    var dy = e.clientY - dragStartY;
                    if (Math.abs(dx) > 4 || Math.abs(dy) > 4) { dragMoved = true; }
                    if (dragMoved) { applyFabPos(fabStartLeft + dx, fabStartTop + dy); }
                });

                document.addEventListener('mouseup', function() {
                    if (!isDragging) { return; }
                    isDragging = false;
                    fab.classList.remove('evalia-fab--dragging');
                    if (dragMoved) {
                        var rect = fab.getBoundingClientRect();
                        saveFabPos(rect.left, rect.top);
                    } else {
                        // Click: toggle panel
                        if (panel) {
                            var isVisible = panel.style.display !== 'none';
                            panel.style.display = isVisible ? 'none' : '';
                            if (!isVisible && input) { input.focus(); }
                            // Reposition panel relative to current FAB position
                            if (!isVisible) {
                                var r2 = fab.getBoundingClientRect();
                                applyFabPos(r2.left, r2.top);
                            }
                        }
                    }
                });

                if (closeBtn) {
                    closeBtn.addEventListener('click', function() {
                        if (panel) { panel.style.display = 'none'; }
                    });
                }

                if (sendBtn) { sendBtn.addEventListener('click', sendMessage); }
                if (input) {
                    input.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
                    });
                }
            }());

            Log.debug('evalia_teacher: init complete for course ' + courseId);
        }
    };
});
