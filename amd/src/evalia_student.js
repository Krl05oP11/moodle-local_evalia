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
 * EVAL-IA Student Exam AMD module.
 *
 * Responsibilities:
 *  - Countdown timer (if time_limit_min > 0)
 *  - Collect answers from the form
 *  - Submit via local_evalia_submit_exam WS (AJAX)
 *  - Prevent double-submit
 *
 * Uses jQuery Deferreds (NOT native Promises) — Moodle 4.4 AMD pattern.
 *
 * @module     local_evalia/evalia_student
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/log', 'core/str'], function(Ajax, Log, Str) {

    'use strict';

    var _studentExamId = 0;
    var _timeLimitMin = 0;
    var _submitted = false; // Guard against double-submit
    var _timerInterval = null;
    var _secondsLeft = 0;
    var _stringsRequested = false;

    // ─── i18n ────────────────────────────────────────────────────────────────
    // Every UI string this module needs, keyed by its local_evalia lang key.
    // Values below are the English fallback (identical to lang/en) shown until
    // loadStrings() resolves, so every call site stays synchronous. Strings
    // containing "{$a}" are substituted at the call site with String.replace().
    var S = {
        'exam:submitting_auto':      '⏳ Submitting automatically...',
        'exam:submitting':           '⏳ Submitting...',
        'exam:submit_status_auto':   'Time is up — submitting your answers...',
        'exam:submit_status':        'Submitting your answers...',
        'exam:submit_success':       '✅ Exam submitted successfully.',
        'exam:btn_submit_exam':      '📤 Submit exam',
        'exam:submit_network_error': 'Network error while submitting the exam. Please try again.',
        'exam:submit_confirm':       'Are you sure you want to submit the exam? ' +
            'You will not be able to change your answers afterwards.',
        'exam:grade_confirm':        'Grade this student\'s exam with AI?',
        'exam:graded_heading':       '✅ Exam graded',
        'exam:grade_label':          'Grade:',
        'exam:connection_error':     'Connection error.',
        grading_in_progress:         '⏳ Grading...',
        error_rubric_save:           'Error: {$a}'
    };

    /**
     * Resolves every string in S against the current language pack, once, in a
     * single batch request. Until it resolves, S holds the English fallback.
     */
    function loadStrings() {
        if (_stringsRequested) { return; }
        _stringsRequested = true;
        var keys = Object.keys(S);
        Str.get_strings(keys.map(function(k) {
            return {key: k, component: 'local_evalia'};
        })).then(function(values) {
            keys.forEach(function(k, i) { S[k] = values[i]; });
            return values;
        }).catch(function(err) {
            Log.error('evalia_student: string load error', err);
        });
    }

    // ─── Toast ───────────────────────────────────────────────────────────────

    /**
     * Shows a coloured status toast with the given message.
     *
     * @param {string} message Text to display in the toast body.
     * @param {string} type Bootstrap contextual colour suffix (e.g. 'success', 'danger'); defaults to 'info'.
     */
    function showToast(message, type) {
        var toast = document.getElementById('evalia-toast');
        if (!toast) { return; }
        toast.className = 'toast align-items-center text-white border-0 bg-' + (type || 'info');
        document.getElementById('evalia-toast-body').textContent = message;
        // Moodle 4.x ships Bootstrap 4 without a global `bootstrap` object, so the
        // toast is shown and hidden by toggling its class (same as evalia_teacher.js).
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 5000);
    }

    // ─── Timer ───────────────────────────────────────────────────────────────

    /**
     * Starts the countdown timer display and auto-submits the exam when it reaches zero.
     *
     * @param {number} minutes Exam time limit in minutes.
     */
    function startTimer(minutes) {
        _secondsLeft = minutes * 60;
        var display = document.getElementById('evalia-timer-display');
        if (!display) { return; }

        /**
         * Updates the timer display once per second and triggers auto-submit at zero.
         */
        function tick() {
            if (_submitted) {
                clearInterval(_timerInterval);
                return;
            }
            var m = Math.floor(_secondsLeft / 60);
            var s = _secondsLeft % 60;
            display.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;

            if (_secondsLeft <= 300) { // Last 5 minutes — warning color
                var bar = document.getElementById('evalia-timer-bar');
                if (bar) { bar.className = bar.className.replace('alert-warning', 'alert-danger'); }
            }

            if (_secondsLeft <= 0) {
                clearInterval(_timerInterval);
                display.textContent = '00:00';
                if (!_submitted) {
                    Log.debug('evalia_student: time expired — auto-submitting');
                    doSubmit(true);
                }
                return;
            }
            _secondsLeft--;
        }

        tick();
        _timerInterval = setInterval(tick, 1000);
    }

    // ─── Collect answers ─────────────────────────────────────────────────────

    /**
     * Collects the student's answers from all answer inputs in the exam form.
     *
     * @returns {Object} Map of question ID to answer value.
     */
    function collectAnswers() {
        var answers = {};
        // Radio / checkbox inputs
        document.querySelectorAll('input.evalia-answer[type="radio"]:checked').forEach(function(el) {
            answers[el.dataset.qid] = el.value;
        });
        // Text / number inputs
        document.querySelectorAll('input.evalia-answer[type="text"], input.evalia-answer[type="number"]').forEach(function(el) {
            if (el.value.trim() !== '') {
                answers[el.dataset.qid] = el.value.trim();
            }
        });
        // Textarea inputs (essay questions)
        document.querySelectorAll('textarea.evalia-answer').forEach(function(el) {
            if (el.value.trim() !== '') {
                answers[el.dataset.qid] = el.value.trim();
            }
        });
        return answers;
    }

    // ─── Submit ──────────────────────────────────────────────────────────────

    /**
     * Submits the collected answers via the local_evalia_submit_exam web service, guarding against double submits.
     *
     * @param {boolean} isAutomatic True when triggered by the timer expiring, false for a manual submit.
     */
    function doSubmit(isAutomatic) {
        if (_submitted) { return; }
        _submitted = true;

        var btn = document.getElementById('evalia-btn-submit-exam');
        if (btn) {
            btn.disabled = true;
            btn.textContent = isAutomatic ? S['exam:submitting_auto'] : S['exam:submitting'];
        }

        var statusEl = document.getElementById('evalia-submit-status');
        if (statusEl) {
            statusEl.textContent = isAutomatic ? S['exam:submit_status_auto'] : S['exam:submit_status'];
        }

        var answers = collectAnswers();

        Ajax.call([{
            methodname: 'local_evalia_submit_exam',
            args: {
                student_examid: _studentExamId,
                answers:        JSON.stringify(answers)
            }
        }])[0].then(function(result) {
            if (result.success) {
                showToast(S['exam:submit_success'], 'success');
                // Reload after 2 seconds to show the submitted state.
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                _submitted = false; // Allow retry on error
                if (btn) { btn.disabled = false; btn.textContent = S['exam:btn_submit_exam']; }
                showToast(S.error_rubric_save.replace('{$a}', result.message), 'danger');
                Log.error('evalia_student: submit_exam returned failure', result.message);
            }
        }).fail(function(err) {
            _submitted = false;
            if (btn) { btn.disabled = false; btn.textContent = S['exam:btn_submit_exam']; }
            showToast(S['exam:submit_network_error'], 'danger');
            Log.error('evalia_student: submit_exam AJAX failed', err);
        });
    }

    // ─── Init ────────────────────────────────────────────────────────────────

    return {
        /**
         * Initialises the student exam page: timer, submit button and strings.
         *
         * @param {Object} config Page config ({student_examid, time_limit_min, already_submitted}).
         */
        init: function(config) {
            loadStrings();
            _studentExamId = config.student_examid;
            _timeLimitMin = config.time_limit_min || 0;

            if (config.already_submitted) {
                _submitted = true;
                return;
            }

            // Start timer if there's a time limit.
            if (_timeLimitMin > 0) {
                startTimer(_timeLimitMin);
            }

            // Manual submit button.
            var btn = document.getElementById('evalia-btn-submit-exam');
            if (btn) {
                btn.addEventListener('click', function() {
                    if (_submitted) { return; }
                    if (!window.confirm(S['exam:submit_confirm'])) {
                        return;
                    }
                    doSubmit(false);
                });
            }

            Log.debug('evalia_student: init complete, exam=' + _studentExamId + ' timer=' + _timeLimitMin + 'min');
        },

        /**
         * Teacher grading panel — called when a teacher views a submitted exam.
         *
         * @param {Object} config Page config ({student_examid, answers_json}).
         */
        initGradePanel: function(config) {
            loadStrings();
            var btn = document.getElementById('evalia-btn-grade');
            if (!btn) { return; }

            btn.addEventListener('click', function() {
                if (!window.confirm(S['exam:grade_confirm'])) { return; }
                btn.disabled = true;
                var statusEl = document.getElementById('evalia-grade-status');
                if (statusEl) { statusEl.textContent = S.grading_in_progress; }

                Ajax.call([{
                    methodname: 'local_evalia_grade_exam',
                    args: {
                        student_examid: config.student_examid,
                        answers:        config.answers_json
                    }
                }])[0].then(function(result) {
                    if (result.success) {
                        var panel = document.getElementById('evalia-grade-panel');
                        if (panel) {
                            panel.className = 'card border-success mt-3 mb-5';
                            panel.querySelector('.card-header').className = 'card-header bg-success text-white fw-bold';
                            panel.querySelector('.card-header').textContent = S['exam:graded_heading'];
                            panel.querySelector('.card-body').innerHTML =
                                '<p class="mb-0 fs-5">' + S['exam:grade_label'] + ' <strong>' +
                                result.score.toFixed(1) + ' / ' + result.max_score.toFixed(1) +
                                '</strong> &nbsp;(' + result.percent.toFixed(0) + '%)</p>';
                        }
                    } else {
                        btn.disabled = false;
                        if (statusEl) { statusEl.textContent = S.error_rubric_save.replace('{$a}', result.message); }
                    }
                }).fail(function(err) {
                    btn.disabled = false;
                    if (statusEl) { statusEl.textContent = S['exam:connection_error']; }
                    Log.error('evalia_student: grade_exam failed', err);
                });
            });
        }
    };
});
