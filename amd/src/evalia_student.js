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
define(['core/ajax', 'core/log'], function(Ajax, Log) {

    'use strict';

    var _studentExamId  = 0;
    var _timeLimitMin   = 0;
    var _submitted      = false;   // guard against double-submit
    var _timerInterval  = null;
    var _secondsLeft    = 0;

    // ─── Toast ───────────────────────────────────────────────────────────────

    function showToast(message, type) {
        var toast = document.getElementById('evalia-toast');
        if (!toast) { return; }
        toast.className = 'toast align-items-center text-white border-0 bg-' + (type || 'info');
        document.getElementById('evalia-toast-body').textContent = message;
        var bsToast = bootstrap.Toast.getOrCreateInstance(toast, {delay: 5000});
        bsToast.show();
    }

    // ─── Timer ───────────────────────────────────────────────────────────────

    function startTimer(minutes) {
        _secondsLeft = minutes * 60;
        var display  = document.getElementById('evalia-timer-display');
        if (!display) { return; }

        function tick() {
            if (_submitted) {
                clearInterval(_timerInterval);
                return;
            }
            var m = Math.floor(_secondsLeft / 60);
            var s = _secondsLeft % 60;
            display.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;

            if (_secondsLeft <= 300) {   // last 5 minutes — warning color
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

    function doSubmit(isAutomatic) {
        if (_submitted) { return; }
        _submitted = true;

        var btn = document.getElementById('evalia-btn-submit-exam');
        if (btn) {
            btn.disabled = true;
            btn.textContent = isAutomatic ? '⏳ Enviando automáticamente...' : '⏳ Enviando...';
        }

        var statusEl = document.getElementById('evalia-submit-status');
        if (statusEl) {
            statusEl.textContent = isAutomatic
                ? 'Tiempo agotado — enviando tus respuestas...'
                : 'Enviando tus respuestas...';
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
                showToast('✅ Examen enviado correctamente.', 'success');
                // Reload after 2 seconds to show the submitted state.
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            } else {
                _submitted = false;   // allow retry on error
                if (btn) { btn.disabled = false; btn.textContent = '📤 Enviar examen'; }
                showToast('Error: ' + result.message, 'danger');
                Log.error('evalia_student: submit_exam returned failure', result.message);
            }
        }).fail(function(err) {
            _submitted = false;
            if (btn) { btn.disabled = false; btn.textContent = '📤 Enviar examen'; }
            showToast('Error de red al enviar el examen. Intentá de nuevo.', 'danger');
            Log.error('evalia_student: submit_exam AJAX failed', err);
        });
    }

    // ─── Init ────────────────────────────────────────────────────────────────

    return {
        init: function(config) {
            _studentExamId = config.student_examid;
            _timeLimitMin  = config.time_limit_min || 0;

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
                    if (!window.confirm('¿Estás seguro de que querés enviar el examen? No podrás modificar tus respuestas después.')) {
                        return;
                    }
                    doSubmit(false);
                });
            }

            Log.debug('evalia_student: init complete, exam=' + _studentExamId + ' timer=' + _timeLimitMin + 'min');
        },

        // Teacher grading panel — called when teacher views a submitted exam.
        initGradePanel: function(config) {
            var btn = document.getElementById('evalia-btn-grade');
            if (!btn) { return; }

            btn.addEventListener('click', function() {
                if (!window.confirm('¿Calificar el examen de este alumno con IA?')) { return; }
                btn.disabled = true;
                var statusEl = document.getElementById('evalia-grade-status');
                if (statusEl) { statusEl.textContent = 'Calificando...'; }

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
                            panel.querySelector('.card-header').textContent = '✅ Examen calificado';
                            panel.querySelector('.card-body').innerHTML =
                                '<p class="mb-0 fs-5">Nota: <strong>' +
                                result.score.toFixed(1) + ' / ' + result.max_score.toFixed(1) +
                                '</strong> &nbsp;(' + result.percent.toFixed(0) + '%)</p>';
                        }
                    } else {
                        btn.disabled = false;
                        if (statusEl) { statusEl.textContent = 'Error: ' + result.message; }
                    }
                }).fail(function(err) {
                    btn.disabled = false;
                    if (statusEl) { statusEl.textContent = 'Error de conexión.'; }
                    Log.error('evalia_student: grade_exam failed', err);
                });
            });
        }
    };
});
