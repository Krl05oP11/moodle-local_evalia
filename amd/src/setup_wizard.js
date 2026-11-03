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
 * EVAL-IA setup wizard (setup.php): step navigation, engine health check and save.
 *
 * The page renders every step; this module shows one at a time. Strings come from the
 * data-config attribute of the .evwiz element (JSON written by setup.php), and the buttons
 * declare what they do with data-evwiz-goto="<step>" or data-evwiz-action.
 *
 * @module     local_evalia/setup_wizard
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/config'], function(Config) {

    'use strict';

    var L = {};
    var setupUrl = '';
    var currentStep = 1;

    /**
     * Escape a value for HTML.
     *
     * @param {*} s Value to escape.
     * @returns {string}
     */
    function he(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * Engine mode chosen in step 3.
     *
     * @returns {string}
     */
    function getSelectedMode() {
        var checked = document.querySelector('input[name="engine_mode"]:checked');
        return checked ? checked.value : 'local_ollama';
    }

    /**
     * Show the hint of the chosen engine mode in step 4.
     */
    function updateHints() {
        var mode = getSelectedMode();
        ['local_ollama', 'cloud_api', 'custom'].forEach(function(m) {
            var el = document.getElementById('hint-' + m);
            if (el) {
                el.style.display = (m === mode) ? '' : 'none';
            }
        });
    }

    /**
     * Show why the engine could not be reached, with the troubleshooting list.
     *
     * @param {string} msg Error message.
     * @param {string} url Engine URL that was tried.
     */
    function renderHealthError(msg, url) {
        document.getElementById('evwiz-health-result').innerHTML =
            '<div class="health-row">' +
            '<div class="health-icon">❌</div>' +
            '<div class="health-label"><strong>' + he(L.connection_failed) + '</strong></div>' +
            '<div class="health-value">' + he(url || '—') + '</div></div>' +
            '<div class="alert alert-danger py-2 px-3 mt-3 mb-0" style="font-size:.84rem;">' +
            '<strong>' + he(L.error_label) + '</strong> ' + he(msg) + '<br><br>' +
            '<strong>' + he(L.troubleshoot_header) + '</strong>' +
            '<ul class="mb-0 mt-1">' +
            '<li>' + L.troubleshoot_li1 + '</li>' +
            '<li>' + L.troubleshoot_li2 + '</li>' +
            '<li>' + L.troubleshoot_li3 + '</li>' +
            '<li>' + L.troubleshoot_li4 + '</li>' +
            '<li>' + L.troubleshoot_li5 + '</li>' +
            '</ul></div>';
        document.getElementById('evwiz-retry-btn').style.display = '';
    }

    /**
     * Check that the engine answers, then show the save button (step 5).
     */
    function runTest() {
        var resultEl = document.getElementById('evwiz-health-result');
        var saveBtn = document.getElementById('evwiz-save-btn');
        var retryBtn = document.getElementById('evwiz-retry-btn');

        saveBtn.style.display = 'none';
        retryBtn.style.display = 'none';
        resultEl.innerHTML =
            '<div class="d-flex align-items-center gap-2 text-muted py-2">' +
            '<div class="spinner-border spinner-border-sm" role="status"></div>' +
            '<span>' + he(L.connecting_engine) + '</span></div>';

        var url = ((document.getElementById('evwiz-url') || {}).value || '').trim();
        var token = (document.getElementById('evwiz-token') || {}).value || '';

        if (!url) {
            renderHealthError(L.url_empty, url);
            return;
        }

        var fd = new FormData();
        fd.append('action', 'health');
        fd.append('sesskey', Config.sesskey);
        fd.append('engine_url', url);
        fd.append('engine_token', token);

        fetch(setupUrl, {method: 'POST', credentials: 'same-origin', body: fd})
        .then(function(r) {
            return r.json();
        })
        .then(function(data) {
            if (data.ok) {
                resultEl.innerHTML =
                    '<div class="health-row">' +
                    '<div class="health-icon">✅</div>' +
                    '<div class="health-label"><strong>' + he(L.engine_reachable) + '</strong></div>' +
                    '<div class="health-value">' + he(url) + '</div></div>' +
                    '<div class="health-row">' +
                    '<div class="health-icon">🔢</div>' +
                    '<div class="health-label">' + he(L.engine_version) + '</div>' +
                    '<div class="health-value">' + he(data.version) + '</div></div>' +
                    '<div class="health-row">' +
                    '<div class="health-icon">⏱️</div>' +
                    '<div class="health-label">' + he(L.uptime) + '</div>' +
                    '<div class="health-value">' + he(data.uptime) + '</div></div>' +
                    '<div class="alert alert-success py-2 px-3 mt-3 mb-0" style="font-size:.85rem;">' +
                    L.success_msg + '</div>';
                saveBtn.style.display = '';
            } else {
                renderHealthError(data.error || L.unknown_error, url);
            }
            return data;
        })
        .catch(function(e) {
            renderHealthError(e.message || L.network_error, url);
        });
    }

    /**
     * Copy the wizard's choices into the hidden save form and submit it.
     */
    function save() {
        document.getElementById('sf-mode').value = getSelectedMode();
        document.getElementById('sf-url').value = (document.getElementById('evwiz-url') || {}).value || '';
        document.getElementById('sf-token').value = (document.getElementById('evwiz-token') || {}).value || '';
        document.getElementById('evwiz-save-form').submit();
    }

    /**
     * Show a step and update the progress bar.
     *
     * @param {number} step Step number (1-5).
     */
    function goTo(step) {
        var prev = document.getElementById('evwiz-step-' + currentStep);
        if (prev) {
            prev.classList.remove('active');
        }
        currentStep = step;
        var next = document.getElementById('evwiz-step-' + step);
        if (next) {
            next.classList.add('active');
        }

        document.querySelectorAll('#evwiz-progress .step').forEach(function(el) {
            var n = parseInt(el.dataset.step, 10);
            el.classList.remove('active', 'done');
            if (n === step) {
                el.classList.add('active');
            }
            if (n < step) {
                el.classList.add('done');
            }
        });

        if (step === 4) {
            updateHints();
        }
        if (step === 5) {
            runTest();
        }

        window.scrollTo({top: 0, behavior: 'smooth'});
    }

    return {
        /**
         * Wire up the wizard.
         */
        init: function() {
            var root = document.querySelector('.evwiz[data-config]');
            if (!root) {
                return;
            }
            var config = JSON.parse(root.dataset.config);
            L = config.strings;
            setupUrl = config.url;

            var actions = {runtest: runTest, save: save};
            root.addEventListener('click', function(e) {
                var go = e.target.closest('[data-evwiz-goto]');
                if (go) {
                    goTo(parseInt(go.dataset.evwizGoto, 10));
                    return;
                }
                var action = e.target.closest('[data-evwiz-action]');
                if (action && actions[action.dataset.evwizAction]) {
                    actions[action.dataset.evwizAction]();
                }
            });

            var reqCheck = document.getElementById('req-confirm');
            var reqNextBtn = document.getElementById('req-next-btn');
            if (reqCheck) {
                reqCheck.addEventListener('change', function() {
                    reqNextBtn.disabled = !reqCheck.checked;
                });
            }

            document.querySelectorAll('.mode-card').forEach(function(card) {
                card.addEventListener('click', function() {
                    document.querySelectorAll('.mode-card').forEach(function(c) {
                        c.classList.remove('selected');
                    });
                    card.classList.add('selected');
                    card.querySelector('input[type=radio]').checked = true;
                });
            });
        }
    };
});
