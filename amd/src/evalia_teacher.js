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
define(['core/ajax', 'core/log'], function(Ajax, Log) {

    'use strict';

    // ─── Module state ────────────────────────────────────────────────────────
    var courseId                   = 0;
    var currentRubric              = null;   // { rubricid, status, items[] }
    var currentExamId              = 0;      // active exam template ID
    var selectedPortfolioUserId    = 0;
    var selectedPortfolioFullname  = '';
    var sourcesLoaded              = false;  // lazy-load guard for source panel
    var allSources                 = [];     // [{id, label, type}] from WS
    var defaultQuestionsCount      = 5;      // overridden from config.defaults in init()

    // ─── Toast helper ────────────────────────────────────────────────────────

    function showToast(message, type) {
        var toast = document.getElementById('evalia-toast');
        if (!toast) { return; }
        toast.className = 'toast align-items-center text-white border-0 bg-' + (type || 'info');
        document.getElementById('evalia-toast-body').textContent = message;
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 6000);
    }

    function setLoading(elementId, visible) {
        var el = document.getElementById(elementId);
        if (el) { el.style.display = visible ? '' : 'none'; }
    }

    function setDisabled(elementId, disabled) {
        var el = document.getElementById(elementId);
        if (el) { el.disabled = disabled; }
    }

    // ─── Tab 1: Rúbrica ──────────────────────────────────────────────────────

    function loadRubric() {
        var container = document.getElementById('evalia-rubric-items-container');
        if (!container) { return; }

        Ajax.call([{
            methodname: 'local_evalia_get_rubric',
            args: { courseid: courseId }
        }])[0].then(function(result) {
            currentRubric = result;

            var statusDiv = document.getElementById('evalia-rubric-status');
            if (statusDiv) {
                if (result.rubricid === 0) {
                    statusDiv.innerHTML = '';
                } else {
                    var badgeClass = result.status === 'active' ? 'bg-success' : 'bg-secondary';
                    statusDiv.innerHTML = '<span class="badge ' + badgeClass + ' me-2">' +
                        (result.status === 'active' ? 'Activa' : 'Borrador') + '</span>' +
                        '<small class="text-muted">' + result.name + '</small>';
                }
            }

            if (result.rubricid === 0) {
                container.innerHTML = '<p class="text-muted">No hay rúbrica para este curso. ' +
                    'Haga clic en <strong>Generar Rúbrica con IA</strong> para comenzar.</p>';
                showRubricButtons(false, false);
                return;
            }

            renderRubricItems(result.items, result.status);
            showRubricButtons(result.status === 'draft', result.status === 'draft');

        }).fail(function(err) {
            Log.error('evalia_teacher: get_rubric failed', err);
            container.innerHTML = '<p class="text-danger">Error al cargar la rúbrica.</p>';
        });
    }

    function showRubricButtons(showSave, showActivate) {
        var btnSave     = document.getElementById('evalia-btn-save-rubric');
        var btnActivate = document.getElementById('evalia-btn-activate-rubric');
        if (btnSave)     { btnSave.style.display     = showSave     ? '' : 'none'; }
        if (btnActivate) { btnActivate.style.display = showActivate ? '' : 'none'; }
    }

    function renderRubricItems(items, status) {
        var container = document.getElementById('evalia-rubric-items-container');
        if (!items || items.length === 0) {
            container.innerHTML = '<p class="text-muted">La rúbrica no tiene ítems.</p>';
            return;
        }

        var editable = (status === 'draft');
        var rows = items.map(function(item, idx) {
            if (!editable) {
                return '<tr>' +
                    '<td>' + (idx + 1) + '</td>' +
                    '<td>' + item.topic + '</td>' +
                    '<td><small class="text-muted">' + (item.description || '') + '</small></td>' +
                    '<td><span class="badge bg-secondary">' + item.difficulty_weight + '</span></td>' +
                    '</tr>';
            }
            var weightOpts = ['low', 'medium', 'high'].map(function(w) {
                return '<option value="' + w + '"' + (item.difficulty_weight === w ? ' selected' : '') + '>' + w + '</option>';
            }).join('');
            return '<tr>' +
                '<td>' + (idx + 1) + '</td>' +
                '<td><input type="text" class="form-control form-control-sm" ' +
                    'data-item-id="' + item.id + '" data-field="topic" value="' + item.topic + '"></td>' +
                '<td><textarea class="form-control form-control-sm" rows="2" ' +
                    'data-item-id="' + item.id + '" data-field="description">' + (item.description || '') + '</textarea></td>' +
                '<td><select class="form-select form-select-sm" ' +
                    'data-item-id="' + item.id + '" data-field="difficulty_weight">' + weightOpts + '</select></td>' +
                '</tr>';
        }).join('');

        container.innerHTML = '<div class="table-responsive"><table class="table table-sm table-hover">' +
            '<thead class="table-light"><tr><th>#</th><th>Tema</th><th>Descripción</th><th>Peso</th></tr></thead>' +
            '<tbody>' + rows + '</tbody></table></div>';
    }

    function collectRubricItems() {
        // Read current values from editable fields in the rubric table.
        var items = [];
        var rows = document.querySelectorAll('#evalia-rubric-items-container tbody tr');
        rows.forEach(function(row) {
            var topicEl  = row.querySelector('[data-field="topic"]');
            var descEl   = row.querySelector('[data-field="description"]');
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

    function updateSourcesBadge() {
        var badge  = document.getElementById('evalia-sources-badge');
        if (!badge || !allSources.length) { return; }
        var total    = allSources.length;
        var checked  = document.querySelectorAll('#evalia-sources-list input[type=checkbox]:checked').length;
        badge.style.display = '';
        badge.textContent   = checked + '/' + total + ' fuentes';
        badge.className     = 'badge me-2 ' + (checked < total ? 'bg-warning text-dark' : 'bg-secondary');
    }

    function renderSources(sources) {
        var list = document.getElementById('evalia-sources-list');
        if (!list) { return; }
        if (!sources.length) {
            list.innerHTML = '<span class="text-muted small">No hay material indexado para este curso.</span>';
            return;
        }
        var icons = {page: '📄', resource: '📑', unknown: '📎'};
        var html  = '<div class="d-flex flex-wrap gap-2">';
        sources.forEach(function(s) {
            var icon = icons[s.type] || '📎';
            var eid  = 'evalia-src-' + s.id.replace(/[^a-zA-Z0-9]/g, '_');
            html += '<label class="d-flex align-items-center gap-1 small border rounded px-2 py-1" style="cursor:pointer;">' +
                    '<input type="checkbox" class="evalia-source-cb" id="' + eid + '"' +
                    ' data-source-id="' + s.id.replace(/"/g, '&quot;') + '" checked> ' +
                    icon + ' ' + s.label + '</label>';
        });
        html += '</div>';
        list.innerHTML = html;

        // Listen for changes to update the badge.
        list.querySelectorAll('input[type=checkbox]').forEach(function(cb) {
            cb.addEventListener('change', updateSourcesBadge);
        });
        updateSourcesBadge();
    }

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
            if (list) { list.innerHTML = '<span class="text-danger small">Error al cargar fuentes.</span>'; }
        });
    }

    function getSelectedSources() {
        // Returns [] if all are checked (= no filter) or if panel was never opened.
        var cbs = document.querySelectorAll('#evalia-sources-list input[type=checkbox]');
        if (!cbs.length) { return []; }
        var all      = Array.from(cbs);
        var selected = all.filter(function(c) { return c.checked; }).map(function(c) { return c.dataset.sourceId; });
        return (selected.length === all.length) ? [] : selected;
    }

    function initExamWindowToggle() {
        var toggle = document.getElementById('evalia-exam-window-toggle');
        var panel  = document.getElementById('evalia-exam-window-panel');
        var arrow  = document.getElementById('evalia-exam-window-arrow');
        if (!toggle || !panel) { return; }
        toggle.addEventListener('click', function() {
            var open = panel.style.display !== 'none';
            panel.style.display = open ? 'none' : '';
            if (arrow) { arrow.textContent = open ? '▶' : '▼'; }
        });
    }

    function initSourcesPanel() {
        var toggle = document.getElementById('evalia-sources-toggle');
        var panel  = document.getElementById('evalia-sources-panel');
        var arrow  = document.getElementById('evalia-sources-arrow');
        if (!toggle || !panel) { return; }

        toggle.addEventListener('click', function() {
            var open = panel.style.display !== 'none';
            panel.style.display = open ? 'none' : '';
            if (arrow) { arrow.textContent = open ? '▶' : '▼'; }
            if (!open) { loadSources(); }
        });

        var selAll  = document.getElementById('evalia-sources-select-all');
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

    function handleGenerateRubric() {
        setDisabled('evalia-btn-generate-rubric', true);
        setLoading('evalia-rubric-loading', true);

        var scopeEl       = document.getElementById('evalia-rubric-scope');
        var countEl       = document.getElementById('evalia-rubric-item-count');
        var scope         = scopeEl ? scopeEl.value.trim() : '';
        var item_count    = countEl ? (parseInt(countEl.value, 10) || 18) : 18;
        var selSources    = getSelectedSources();
        var source_filter = selSources.length ? JSON.stringify(selSources) : '';

        Ajax.call([{
            methodname: 'local_evalia_generate_rubric',
            args: { courseid: courseId, scope: scope, item_count: item_count, source_filter: source_filter }
        }])[0].then(function(result) {
            setLoading('evalia-rubric-loading', false);
            setDisabled('evalia-btn-generate-rubric', false);
            if (result.success) {
                showToast('Rúbrica generada. Revísela y active cuando esté lista.', 'success');
                loadRubric();
            } else {
                showToast('No se pudo generar: ' + result.message, 'danger');
            }
        }).fail(function(err) {
            setLoading('evalia-rubric-loading', false);
            setDisabled('evalia-btn-generate-rubric', false);
            Log.error('evalia_teacher: generate_rubric failed', err);
            showToast('Error al generar la rúbrica.', 'danger');
        });
    }

    function handleSaveRubric(activate) {
        if (!currentRubric || currentRubric.rubricid === 0) {
            showToast('No hay rúbrica para guardar.', 'danger');
            return;
        }
        var items = collectRubricItems();
        if (items.length === 0) {
            showToast('La rúbrica debe tener al menos un ítem.', 'danger');
            return;
        }

        var btnId = activate ? 'evalia-btn-activate-rubric' : 'evalia-btn-save-rubric';
        setDisabled(btnId, true);

        Ajax.call([{
            methodname: 'local_evalia_save_rubric',
            args: {
                rubricid: currentRubric.rubricid,
                name:     currentRubric.name || ('Rúbrica curso ' + courseId),
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
                showToast('Error: ' + result.message, 'danger');
            }
        }).fail(function(err) {
            setDisabled(btnId, false);
            Log.error('evalia_teacher: save_rubric failed', err);
            showToast('Error al guardar.', 'danger');
        });
    }

    // ─── Tab 2: Banco de Preguntas ───────────────────────────────────────────

    function refreshTopicFilter() {
        // Rebuild topic dropdown from current rubric items.
        var sel = document.getElementById('evalia-filter-topic');
        if (!sel) { return; }
        var current = sel.value;
        // Keep only the "Todos los temas" option, then re-add from rubric
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

    function loadQuestionBank() {
        var topicEl    = document.getElementById('evalia-filter-topic');
        var diffEl     = document.getElementById('evalia-filter-difficulty');
        var statusEl   = document.getElementById('evalia-filter-status');
        var loading    = document.getElementById('evalia-questions-loading');
        var container  = document.getElementById('evalia-questions-table-container');

        var topic      = topicEl    ? topicEl.value    : '';
        var difficulty = diffEl     ? diffEl.value     : '';
        var status     = statusEl   ? statusEl.value   : '';

        setLoading('evalia-questions-loading', true);
        if (container) { container.innerHTML = ''; }

        Ajax.call([{
            methodname: 'local_evalia_get_question_bank',
            args: { courseid: courseId, topic: topic, difficulty: difficulty, status: status }
        }])[0].then(function(result) {
            setLoading('evalia-questions-loading', false);
            if (!result || result.total === 0) {
                container.innerHTML = '<p class="text-muted text-center py-4">' +
                    'No hay preguntas con los filtros seleccionados. ' +
                    'Active una rúbrica y use <strong>Generar Preguntas</strong> por ítem.</p>';
                return;
            }
            renderQuestionsTable(result.questions);
        }).fail(function(err) {
            setLoading('evalia-questions-loading', false);
            Log.error('evalia_teacher: get_question_bank failed', err);
            if (container) {
                container.innerHTML = '<p class="text-danger">Error al cargar el banco.</p>';
            }
        });
    }

    function renderQuestionsTable(questions) {
        var container = document.getElementById('evalia-questions-table-container');
        var statusMap = {
            'draft':    '<span class="badge bg-secondary">Pendiente</span>',
            'approved': '<span class="badge bg-success">Aprobada</span>',
            'rejected': '<span class="badge bg-danger">Rechazada</span>'
        };
        var diffMap = {
            'basic':    '<span class="badge bg-info text-dark">Básica</span>',
            'medium':   '<span class="badge bg-warning text-dark">Media</span>',
            'advanced': '<span class="badge bg-danger">Avanzada</span>'
        };
        var typeMap = {
            'multichoice': 'OM',
            'truefalse':   'V/F',
            'numerical':   'Núm',
            'shortanswer': 'Corta',
            'essay':       'Ensayo'
        };

        var rows = questions.map(function(q) {
            var stem = q.stem.length > 70 ? q.stem.substring(0, 70) + '…' : q.stem;
            return '<tr>' +
                '<td><small class="text-muted">' + q.topic + '</small></td>' +
                '<td><small>' + stem + '</small></td>' +
                '<td><span class="badge bg-light text-dark border">' + (typeMap[q.question_type] || q.question_type) + '</span></td>' +
                '<td>' + (diffMap[q.difficulty] || q.difficulty) + '</td>' +
                '<td>' + (statusMap[q.status] || q.status) + '</td>' +
                '<td class="text-nowrap">' +
                    '<button class="btn btn-sm btn-outline-success py-0 me-1 evalia-approve-btn" ' +
                        'data-id="' + q.id + '" title="Aprobar">✓</button>' +
                    '<button class="btn btn-sm btn-outline-danger py-0 evalia-reject-btn" ' +
                        'data-id="' + q.id + '" title="Rechazar">✗</button>' +
                '</td>' +
                '</tr>';
        }).join('');

        container.innerHTML = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">' +
            '<thead class="table-light"><tr>' +
            '<th>Tema</th><th>Pregunta</th><th>Tipo</th><th>Dificultad</th><th>Estado</th><th></th>' +
            '</tr></thead><tbody>' + rows + '</tbody></table></div>';

        container.querySelectorAll('.evalia-approve-btn').forEach(function(btn) {
            btn.addEventListener('click', function() { updateQuestion(+btn.dataset.id, 'approved'); });
        });
        container.querySelectorAll('.evalia-reject-btn').forEach(function(btn) {
            btn.addEventListener('click', function() { updateQuestion(+btn.dataset.id, 'rejected'); });
        });
    }

    function updateQuestion(questionId, status) {
        Ajax.call([{
            methodname: 'local_evalia_update_question',
            args: { questionid: questionId, status: status }
        }])[0].then(function(result) {
            if (result.success) {
                showToast(status === 'approved' ? 'Pregunta aprobada.' : 'Pregunta rechazada.', 'success');
                loadQuestionBank();
            } else {
                showToast('Error: ' + result.message, 'danger');
            }
        }).fail(function(err) {
            Log.error('evalia_teacher: update_question failed', err);
            showToast('Error al actualizar la pregunta.', 'danger');
        });
    }

    function showGenerateQuestionsForm() {
        // Build inline form inside Tab 2, above the table.
        var container = document.getElementById('evalia-questions-table-container');
        if (!currentRubric || currentRubric.rubricid === 0) {
            showToast('Primero genere y active una rúbrica.', 'danger');
            return;
        }
        if (currentRubric.status !== 'active') {
            showToast('Active la rúbrica antes de generar preguntas.', 'danger');
            return;
        }

        var itemOptions = currentRubric.items.map(function(it) {
            return '<option value="' + it.id + '">' + it.topic + ' (' + it.difficulty_weight + ')</option>';
        }).join('');

        var formHtml = '<div class="card border-primary mb-3" id="evalia-gen-q-form">' +
            '<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">' +
            '<span>✨ Generar Preguntas con IA</span>' +
            '<button type="button" class="btn-close btn-close-white" id="evalia-gen-q-close"></button>' +
            '</div>' +
            '<div class="card-body">' +
            '<div class="row g-2">' +
            '<div class="col-md-4">' +
            '<label class="form-label form-label-sm">Ítem de rúbrica</label>' +
            '<select id="gen-q-item" class="form-select form-select-sm">' + itemOptions + '</select>' +
            '</div>' +
            '<div class="col-md-3">' +
            '<label class="form-label form-label-sm">Dificultad</label>' +
            '<select id="gen-q-difficulty" class="form-select form-select-sm">' +
            '<option value="basic">Básica</option>' +
            '<option value="medium" selected>Media</option>' +
            '<option value="advanced">Avanzada</option>' +
            '</select>' +
            '</div>' +
            '<div class="col-md-3">' +
            '<label class="form-label form-label-sm">Cantidad</label>' +
            '<input type="number" id="gen-q-count" class="form-control form-control-sm" value="' + defaultQuestionsCount + '" min="1" max="10">' +
            '</div>' +
            '<div class="col-md-2 d-flex align-items-end">' +
            '<button id="evalia-btn-do-generate-questions" class="btn btn-primary btn-sm w-100">Generar</button>' +
            '</div>' +
            '</div>' +
            '<div class="mt-2">' +
            '<label class="form-label form-label-sm">Tipos de pregunta</label><br>' +
            '<div class="form-check form-check-inline">' +
            '<input class="form-check-input" type="checkbox" id="qt-multichoice" value="multichoice" checked>' +
            '<label class="form-check-label" for="qt-multichoice">Opción múltiple</label></div>' +
            '<div class="form-check form-check-inline">' +
            '<input class="form-check-input" type="checkbox" id="qt-truefalse" value="truefalse" checked>' +
            '<label class="form-check-label" for="qt-truefalse">V/F</label></div>' +
            '<div class="form-check form-check-inline">' +
            '<input class="form-check-input" type="checkbox" id="qt-shortanswer" value="shortanswer">' +
            '<label class="form-check-label" for="qt-shortanswer">Respuesta corta</label></div>' +
            '<div class="form-check form-check-inline">' +
            '<input class="form-check-input" type="checkbox" id="qt-essay" value="essay">' +
            '<label class="form-check-label" for="qt-essay">Ensayo</label></div>' +
            '</div>' +
            '<div id="gen-q-loading" style="display:none;" class="mt-2">' +
            '<div class="d-flex align-items-center gap-2 text-primary">' +
            '<div class="spinner-border spinner-border-sm" role="status"></div>' +
            '<span>Generando preguntas desde el material del curso...</span>' +
            '</div></div>' +
            '</div></div>';

        container.innerHTML = formHtml;

        document.getElementById('evalia-gen-q-close').addEventListener('click', function() {
            loadQuestionBank();
        });

        document.getElementById('evalia-btn-do-generate-questions').addEventListener('click', function() {
            handleDoGenerateQuestions();
        });
    }

    function handleDoGenerateQuestions() {
        var itemId     = parseInt(document.getElementById('gen-q-item').value, 10);
        var difficulty = document.getElementById('gen-q-difficulty').value;
        var count      = parseInt(document.getElementById('gen-q-count').value, 10) || 5;
        var types      = [];

        ['multichoice', 'truefalse', 'shortanswer', 'essay'].forEach(function(t) {
            var cb = document.getElementById('qt-' + t);
            if (cb && cb.checked) { types.push(t); }
        });

        if (types.length === 0) {
            showToast('Seleccione al menos un tipo de pregunta.', 'danger');
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
                showToast('Se generaron ' + result.generated + ' pregunta(s). Apruebe las que considere correctas.', 'success');
                // Reset all filters so the new draft questions are visible
                var topicFilterEl = document.getElementById('evalia-filter-topic');
                var diffFilterEl  = document.getElementById('evalia-filter-difficulty');
                var statusEl      = document.getElementById('evalia-filter-status');
                if (topicFilterEl) { topicFilterEl.value = ''; }
                if (diffFilterEl)  { diffFilterEl.value  = ''; }
                if (statusEl)      { statusEl.value      = 'draft'; }
                // Restore the table container (the form replaced it) then load
                var container = document.getElementById('evalia-questions-table-container');
                if (container) { container.innerHTML = ''; }
                loadQuestionBank();
            } else {
                showToast('Error: ' + result.message, 'danger');
            }
        }).fail(function(err) {
            setLoading('gen-q-loading', false);
            setDisabled('evalia-btn-do-generate-questions', false);
            Log.error('evalia_teacher: generate_questions failed', err);
            showToast('Error al generar preguntas.', 'danger');
        });
    }

    // ─── Tab 3: Exámenes ─────────────────────────────────────────────────────

    function handleCreateExam() {
        var name      = document.getElementById('evalia-exam-name');
        var instr     = document.getElementById('evalia-exam-instructions');
        var basic     = document.getElementById('evalia-exam-basic');
        var medium    = document.getElementById('evalia-exam-medium');
        var advanced  = document.getElementById('evalia-exam-advanced');
        var timelimit = document.getElementById('evalia-exam-timelimit');
        var toEl      = document.getElementById('evalia-exam-timeopen');
        var tcEl      = document.getElementById('evalia-exam-timeclose');

        if (!name || !name.value.trim()) {
            showToast('Ingrese un nombre para el examen.', 'danger');
            return;
        }

        if (!currentRubric || currentRubric.rubricid === 0) {
            showToast('Primero genere y active una rúbrica.', 'danger');
            return;
        }

        var basicCount    = parseInt(basic.value, 10)    || 0;
        var mediumCount   = parseInt(medium.value, 10)   || 0;
        var advancedCount = parseInt(advanced.value, 10) || 0;

        if ((basicCount + mediumCount + advancedCount) < 1) {
            showToast('El examen debe tener al menos 1 pregunta.', 'danger');
            return;
        }

        // Convert datetime-local values to Unix timestamps (0 = not set).
        var timeopen  = 0;
        var timeclose = 0;
        if (toEl && toEl.value) {
            timeopen = Math.floor(new Date(toEl.value).getTime() / 1000);
        }
        if (tcEl && tcEl.value) {
            timeclose = Math.floor(new Date(tcEl.value).getTime() / 1000);
        }
        if (timeopen > 0 && timeclose > 0 && timeclose <= timeopen) {
            showToast('El cierre debe ser posterior a la apertura.', 'danger');
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
                showToast('Examen creado. Asígnelo a los alumnos cuando el banco esté listo.', 'success');
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
                showToast('Error: ' + result.message, 'danger');
            }
        }).fail(function(err) {
            setDisabled('evalia-btn-create-exam', false);
            Log.error('evalia_teacher: create_exam failed', err);
            showToast('Error al crear el examen.', 'danger');
        });
    }

    function handleAssignExam() {
        if (!currentExamId) {
            showToast('Primero cree un examen.', 'danger');
            return;
        }

        setDisabled('evalia-btn-assign-exam', true);
        setLoading('evalia-exams-loading', true);

        Ajax.call([{
            methodname: 'local_evalia_assign_exam',
            args: { examid: currentExamId }
        }])[0].then(function(result) {
            setLoading('evalia-exams-loading', false);
            setDisabled('evalia-btn-assign-exam', false);
            if (result.success) {
                showToast('Asignados: ' + result.assigned + ' alumno(s). Omitidos: ' + result.skipped + '.', 'success');
                loadStudentExams(currentExamId);
                loadExamStats(currentExamId);
            } else {
                showToast('Error: ' + result.message, 'danger');
            }
        }).fail(function(err) {
            setLoading('evalia-exams-loading', false);
            setDisabled('evalia-btn-assign-exam', false);
            Log.error('evalia_teacher: assign_exam failed', err);
            showToast('Error al asignar exámenes.', 'danger');
        });
    }

    function loadStudentExams(examId) {
        var container = document.getElementById('evalia-student-exams-container');
        if (!container || !examId) { return; }

        container.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm text-secondary"></div></div>';

        Ajax.call([{
            methodname: 'local_evalia_get_student_exams',
            args: { examid: examId }
        }])[0].then(function(result) {
            if (!result || result.total === 0) {
                container.innerHTML = '<p class="text-muted text-center p-4">No hay alumnos inscriptos.</p>';
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
                btnGradeAll.dataset.count  = submittedCount;
            }
            // Show "publish all" button when there are graded-but-unpublished exams.
            var btnPublishAll = document.getElementById('evalia-btn-publish-all');
            if (btnPublishAll) {
                btnPublishAll.style.display = gradedCount > 0 ? '' : 'none';
                btnPublishAll.dataset.examid = examId;
                btnPublishAll.dataset.count  = gradedCount;
                btnPublishAll.textContent = '✅ Publicar notas (' + gradedCount + ')';
            }
        }).fail(function(err) {
            Log.error('evalia_teacher: get_student_exams failed', err);
            container.innerHTML = '<p class="text-danger p-3">Error al cargar alumnos.</p>';
        });
    }

    function renderStudentExamsTable(students) {
        var container = document.getElementById('evalia-student-exams-container');
        var wwwroot   = (typeof M !== 'undefined' && M.cfg) ? M.cfg.wwwroot : '';
        var statusMap = {
            'not_assigned': '<span class="badge bg-light text-dark border">Sin asignar</span>',
            'assigned':     '<span class="badge" style="background-color:#0d6efd;color:#fff">Asignado</span>',
            'started':      '<span class="badge bg-warning text-dark">En progreso</span>',
            'submitted':    '<span class="badge bg-info text-dark">Enviado</span>',
            'graded':       '<span class="badge bg-success">Calificado</span>',
            'published':    '<span class="badge" style="background:#198754;color:#fff;">✅ Publicado</span>'
        };

        var rows = students.map(function(s) {
            var score = (s.score !== null && s.score !== undefined)
                ? '<strong>' + s.score.toFixed(1) + '</strong>'
                : '<span class="text-muted">—</span>';

            // Build exam link cell.
            var examCell = '';
            if (s.student_examid > 0) {
                var examUrl = wwwroot + '/local/evalia/student_exam.php?student_examid=' + s.student_examid;
                if (s.status === 'assigned') {
                    examCell = '<a href="' + examUrl + '" target="_blank" ' +
                        'class="btn btn-sm btn-outline-secondary py-0" title="Previsualizar examen asignado">' +
                        '👁 Ver</a>';
                } else if (s.status === 'started') {
                    examCell = '<a href="' + examUrl + '" target="_blank" ' +
                        'class="btn btn-sm btn-outline-primary py-0" title="El alumno está rindiendo">' +
                        '👁 Ver</a>';
                } else if (s.status === 'submitted') {
                    examCell = '<a href="' + examUrl + '" target="_blank" ' +
                        'class="btn btn-sm btn-warning py-0" title="Calificar examen enviado">' +
                        '📝 Calificar</a>';
                } else if (s.status === 'graded') {
                    examCell = '<a href="' + examUrl + '" target="_blank" ' +
                        'class="btn btn-sm btn-outline-success py-0 me-1" title="Ver examen calificado">' +
                        '★ Ver</a>' +
                        '<button class="btn btn-sm btn-success py-0 evalia-btn-publish-single" ' +
                        'data-student-examid="' + s.student_examid + '" title="Publicar nota en el gradebook">' +
                        '✅ Publicar</button>';
                } else if (s.status === 'published') {
                    examCell = '<a href="' + examUrl + '" target="_blank" ' +
                        'class="text-success small" title="Ver examen publicado">★ Ver</a>';
                }
            }

            return '<tr>' +
                '<td>' + s.fullname + '</td>' +
                '<td>' + (statusMap[s.status] || s.status) + '</td>' +
                '<td class="text-center">' + score + '</td>' +
                '<td class="text-center">' + examCell + '</td>' +
                '</tr>';
        }).join('');

        container.innerHTML = '<div class="table-responsive"><table class="table table-sm mb-0">' +
            '<thead class="table-light"><tr>' +
            '<th>Alumno</th><th>Estado</th><th class="text-center">Nota</th><th class="text-center">Acción</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody></table></div>';

        // Wire publish buttons (delegated — buttons created dynamically).
        container.querySelectorAll('.evalia-btn-publish-single').forEach(function(btn) {
            btn.addEventListener('click', function() {
                handlePublishGrade(parseInt(btn.dataset.studentExamid, 10), btn);
            });
        });
    }

    // ─── Tab 4: Legajos ──────────────────────────────────────────────────────

    function loadPortfolio() {
        var container = document.getElementById('evalia-portfolio-list-container');
        if (!container) { return; }

        setLoading('evalia-portfolio-loading', true);
        container.innerHTML = '';

        Ajax.call([{
            methodname: 'local_evalia_get_student_portfolio',
            args: { courseid: courseId }
        }])[0].then(function(result) {
            setLoading('evalia-portfolio-loading', false);
            if (!result || result.total === 0) {
                container.innerHTML = '<p class="text-muted text-center p-4">No hay alumnos inscriptos en este curso.</p>';
                return;
            }
            renderPortfolioTable(result.students);
        }).fail(function(err) {
            setLoading('evalia-portfolio-loading', false);
            Log.error('evalia_teacher: get_student_portfolio failed', err);
            if (container) {
                container.innerHTML = '<p class="text-danger p-3">Error al cargar legajos.</p>';
            }
        });
    }

    function renderPortfolioTable(students) {
        var container = document.getElementById('evalia-portfolio-list-container');

        var rows = students.map(function(s) {
            var gradeHtml = s.has_portfolio
                ? '<span class="fw-bold">' + s.avg_grade.toFixed(1) + '</span>'
                : '<span class="text-muted">—</span>';
            var examsHtml = s.has_portfolio
                ? String(s.total_exams)
                : '<span class="text-muted">0</span>';
            var isActive  = (s.userid === selectedPortfolioUserId) ? ' table-active' : '';
            return '<tr class="evalia-portfolio-row' + isActive + '" data-userid="' + s.userid + '"' +
                '    data-fullname="' + s.fullname.replace(/"/g, '&quot;') + '"' +
                '    style="cursor:pointer;">' +
                '<td><small>' + s.fullname + '</small></td>' +
                '<td class="text-center"><small>' + examsHtml + '</small></td>' +
                '<td class="text-center"><small>' + gradeHtml + '</small></td>' +
                '</tr>';
        }).join('');

        container.innerHTML = '<div class="table-responsive">' +
            '<table class="table table-sm table-hover mb-0">' +
            '<thead class="table-light"><tr>' +
            '<th>Alumno</th>' +
            '<th class="text-center">Exámenes</th>' +
            '<th class="text-center">Nota prom.</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table></div>';

        container.querySelectorAll('.evalia-portfolio-row').forEach(function(row) {
            row.addEventListener('click', function() {
                var uid      = parseInt(row.dataset.userid, 10);
                var fullname = row.dataset.fullname;
                showStudentDetail(uid, fullname);
                // Highlight row
                container.querySelectorAll('.evalia-portfolio-row').forEach(function(r) {
                    r.classList.remove('table-active');
                });
                row.classList.add('table-active');
            });
        });

        // Re-select previously chosen student if still in list
        if (selectedPortfolioUserId > 0) {
            var existingRow = container.querySelector('[data-userid="' + selectedPortfolioUserId + '"]');
            if (!existingRow) {
                // Student gone — reset detail panel
                hideStudentDetail();
            }
        }
    }

    function showStudentDetail(userid, fullname) {
        selectedPortfolioUserId   = userid;
        selectedPortfolioFullname = fullname;

        var placeholder = document.getElementById('evalia-portfolio-detail-placeholder');
        var panel       = document.getElementById('evalia-portfolio-detail-panel');
        if (placeholder) { placeholder.style.display = 'none'; }
        if (panel)       { panel.style.display = ''; }

        var nameEl = document.getElementById('evalia-portfolio-student-name');
        if (nameEl) { nameEl.textContent = fullname; }

        // Reset notes panel
        var notesContainer = document.getElementById('evalia-portfolio-notes-container');
        if (notesContainer) {
            notesContainer.innerHTML = '<p class="text-muted small">Cargando...</p>';
        }
        var noteInput = document.getElementById('evalia-portfolio-note-input');
        if (noteInput) { noteInput.value = ''; }

        // Reset exams panel and load history
        var examsContainer = document.getElementById('evalia-portfolio-exams-container');
        if (examsContainer) {
            examsContainer.innerHTML = '<p class="text-muted text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Cargando historial...</p>';
        }

        // Update stats header
        updatePortfolioStats(userid);

        // Switch to Historial tab
        switchPortfolioTab('exams');

        // Load exam history
        loadPortfolioExams(userid);

        // Notes are loaded by switchPortfolioTab('notes') on demand.
    }

    function hideStudentDetail() {
        selectedPortfolioUserId  = 0;
        selectedPortfolioFullname = '';
        var placeholder = document.getElementById('evalia-portfolio-detail-placeholder');
        var panel       = document.getElementById('evalia-portfolio-detail-panel');
        if (placeholder) { placeholder.style.display = ''; }
        if (panel)       { panel.style.display = 'none'; }
    }

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
        // cells[1]=exams, cells[2]=avg grade
        var examsText = cells[1] ? cells[1].textContent.trim() : '0';
        var gradeText = cells[2] ? cells[2].textContent.trim() : '—';
        statsEl.innerHTML =
            '<span class="fw-semibold me-3">📝 ' + examsText + ' examen' + (examsText !== '1' ? 'es' : '') + '</span>' +
            '<span class="fw-semibold text-primary">Promedio: ' + gradeText + '</span>';
    }

    function switchPortfolioTab(tab) {
        var examsPane = document.getElementById('pdt-exams');
        var notesPane = document.getElementById('pdt-notes');
        var examsBtn  = document.getElementById('pdt-exams-btn');
        var notesBtn  = document.getElementById('pdt-notes-btn');
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

    function loadPortfolioExams(userid) {
        var container = document.getElementById('evalia-portfolio-exams-container');
        if (!container) { return; }
        if (userid !== selectedPortfolioUserId) { return; }

        Ajax.call([{
            methodname: 'local_evalia_get_student_exam_history',
            args: { userid: userid, courseid: courseId }
        }])[0].then(function(result) {
            if (userid !== selectedPortfolioUserId) { return; }
            if (!result || result.total === 0) {
                container.innerHTML = '<p class="text-muted text-center py-4">Este alumno no tiene exámenes asignados aún.</p>';
                return;
            }
            renderPortfolioExams(result.exams);
        }).fail(function(err) {
            Log.error('evalia_teacher: get_student_exam_history failed', err);
            if (container) {
                container.innerHTML = '<p class="text-danger small p-3">Error al cargar el historial de exámenes.</p>';
            }
        });
    }

    function renderPortfolioExams(exams) {
        var container = document.getElementById('evalia-portfolio-exams-container');
        if (!container) { return; }

        var statusClasses = {
            'assigned':  'secondary',
            'started':   'warning',
            'submitted': 'info',
            'graded':    'success'
        };

        var rows = exams.map(function(ex) {
            var scoreHtml;
            if (ex.status === 'graded') {
                var pct   = ex.max_score > 0 ? (ex.score / ex.max_score * 100) : 0;
                var color = pct >= 60 ? 'success' : (pct >= 40 ? 'warning' : 'danger');
                scoreHtml = '<span class="fw-bold text-' + color + '">' + ex.score.toFixed(1) + ' / ' + ex.max_score.toFixed(1) + '</span>';
            } else {
                scoreHtml = '<span class="text-muted">—</span>';
            }

            var dateHtml = ex.timesubmitted > 0
                ? new Date(ex.timesubmitted * 1000).toLocaleDateString('es-AR')
                : '—';

            var sc = statusClasses[ex.status] || 'secondary';
            var viewLink = ex.status === 'graded' || ex.status === 'submitted'
                ? ' <a href="' + M.cfg.wwwroot + '/local/evalia/student_exam.php?student_examid=' + ex.student_examid + '" ' +
                  'class="btn btn-outline-secondary btn-sm py-0 ms-1" target="_blank" title="Ver examen">🔍</a>'
                : '';

            return '<tr>' +
                '<td>' + ex.exam_name + '</td>' +
                '<td class="text-center">' +
                    '<span class="badge bg-' + sc + '">' + ex.status_label + '</span>' +
                '</td>' +
                '<td class="text-center">' + scoreHtml + '</td>' +
                '<td class="text-center">' + dateHtml + '</td>' +
                '<td class="text-center">' + ex.total_questions + viewLink + '</td>' +
                '</tr>';
        }).join('');

        container.innerHTML =
            '<div class="table-responsive">' +
            '<table class="table table-sm table-hover mb-0">' +
            '<thead class="table-light"><tr>' +
            '<th>Examen</th>' +
            '<th class="text-center">Estado</th>' +
            '<th class="text-center">Nota</th>' +
            '<th class="text-center">Fecha</th>' +
            '<th class="text-center">Preguntas</th>' +
            '</tr></thead>' +
            '<tbody>' + rows + '</tbody>' +
            '</table></div>';
    }

    function loadStudentNotes(userid) {
        var container = document.getElementById('evalia-portfolio-notes-container');
        if (!container) { return; }
        if (userid !== selectedPortfolioUserId) { return; }

        container.innerHTML = '<p class="text-muted small">Cargando observaciones...</p>';

        Ajax.call([{
            methodname: 'local_evalia_get_portfolio_notes',
            args: { userid: userid, courseid: courseId }
        }])[0].then(function(result) {
            if (userid !== selectedPortfolioUserId) { return; }
            if (!result || result.total === 0) {
                container.innerHTML = '<p class="text-muted small">Sin observaciones registradas.</p>';
                return;
            }
            renderNotesList(result.notes);
        }).fail(function(err) {
            Log.error('evalia_teacher: get_portfolio_notes failed', err);
            if (container) {
                container.innerHTML = '<p class="text-danger small">Error al cargar observaciones.</p>';
            }
        });
    }

    function renderNotesList(notes) {
        var container = document.getElementById('evalia-portfolio-notes-container');
        if (!container) { return; }

        var items = notes.map(function(n) {
            var date = new Date(n.timecreated * 1000);
            var dateStr = date.toLocaleDateString('es-AR') + ' ' + date.toLocaleTimeString('es-AR', {hour: '2-digit', minute: '2-digit'});
            return '<div class="border rounded p-2 mb-2 bg-light">' +
                '<small class="text-muted d-block mb-1">' + n.author_name + ' · ' + dateStr + '</small>' +
                '<span class="small">' + n.note_text + '</span>' +
                '</div>';
        }).join('');

        container.innerHTML = items;
    }

    function handleAddNote() {
        if (!selectedPortfolioUserId) { return; }
        var input = document.getElementById('evalia-portfolio-note-input');
        var text  = input ? input.value.trim() : '';
        if (!text) {
            showToast('Escriba una observación antes de guardar.', 'danger');
            return;
        }

        setDisabled('evalia-btn-add-note', true);

        Ajax.call([{
            methodname: 'local_evalia_add_portfolio_note',
            args: { userid: selectedPortfolioUserId, courseid: courseId, note_text: text }
        }])[0].then(function(result) {
            setDisabled('evalia-btn-add-note', false);
            if (result.success) {
                if (input) { input.value = ''; }
                showToast('Observación guardada.', 'success');
                loadStudentNotes(selectedPortfolioUserId);
                // Reload portfolio list to reflect updated last_activity
                loadPortfolio();
            } else {
                showToast('Error: ' + result.message, 'danger');
            }
        }).fail(function(err) {
            setDisabled('evalia-btn-add-note', false);
            Log.error('evalia_teacher: add_portfolio_note failed', err);
            showToast('Error al guardar la observación.', 'danger');
        });
    }

    // ─── Tab 3: Calificar todos con IA ───────────────────────────────────────

    function handleGradeAll() {
        var btn      = document.getElementById('evalia-btn-grade-all');
        var statusEl = document.getElementById('evalia-grade-all-status');
        if (!btn || !currentExamId) { return; }

        var count = parseInt(btn.dataset.count, 10) || 0;
        if (count === 0) { return; }

        if (!window.confirm('¿Calificar ' + count + ' examen(es) con IA? Esto enviará feedback pedagógico a cada alumno por Telegram.')) {
            return;
        }

        btn.disabled = true;
        btn.textContent = '⏳ Calificando...';
        if (statusEl) { statusEl.textContent = ''; }

        Ajax.call([{
            methodname: 'local_evalia_grade_all_exams',
            args: { examid: currentExamId }
        }])[0].then(function(result) {
            btn.disabled = false;
            btn.style.display = 'none';
            var msg = '✔ ' + result.graded + ' calificado(s)';
            if (result.skipped > 0) { msg += ' · ' + result.skipped + ' omitido(s)'; }
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
            btn.textContent = '⚡ Calificar todos con IA';
            Log.error('evalia_teacher: grade_all_exams failed', err);
            if (statusEl) {
                statusEl.textContent = 'Error al calificar.';
                statusEl.className = 'text-danger small';
            }
            showToast('Error al calificar los exámenes.', 'danger');
        });
    }

    function handlePublishGrade(studentExamId, btn) {
        if (!studentExamId) { return; }
        if (btn) { btn.disabled = true; btn.textContent = '⏳...'; }

        Ajax.call([{
            methodname: 'local_evalia_publish_grade',
            args: { student_examid: studentExamId, override_score: -1 }
        }])[0].then(function(result) {
            if (result.success) {
                showToast('Nota publicada en el libro de calificaciones.', 'success');
                loadStudentExams(currentExamId);
            } else {
                if (btn) { btn.disabled = false; btn.textContent = '✅ Publicar'; }
                showToast('Error: ' + result.message, 'danger');
            }
        }).fail(function(err) {
            if (btn) { btn.disabled = false; btn.textContent = '✅ Publicar'; }
            Log.error('evalia_teacher: publish_grade failed', err);
            showToast('Error al publicar la nota.', 'danger');
        });
    }

    function handlePublishAllGrades() {
        var btn      = document.getElementById('evalia-btn-publish-all');
        var statusEl = document.getElementById('evalia-grade-all-status');
        if (!btn || !currentExamId) { return; }

        var count = parseInt(btn.dataset.count, 10) || 0;
        if (count === 0) { return; }

        if (!window.confirm('¿Publicar las notas de ' + count + ' alumno(s) en el libro de calificaciones?')) {
            return;
        }

        btn.disabled    = true;
        btn.textContent = '⏳ Publicando...';
        if (statusEl) { statusEl.textContent = ''; }

        Ajax.call([{
            methodname: 'local_evalia_publish_all_grades',
            args: { examid: currentExamId }
        }])[0].then(function(result) {
            btn.disabled       = false;
            btn.style.display  = 'none';
            var msg = '✅ ' + result.published + ' nota(s) publicada(s) en el gradebook.';
            if (statusEl) { statusEl.textContent = msg; statusEl.className = 'text-success small'; }
            showToast(msg, 'success');
            loadStudentExams(currentExamId);
            loadExamStats(currentExamId);
        }).fail(function(err) {
            btn.disabled       = false;
            btn.textContent    = '✅ Publicar notas (' + count + ')';
            Log.error('evalia_teacher: publish_all_grades failed', err);
            if (statusEl) { statusEl.textContent = 'Error al publicar.'; statusEl.className = 'text-danger small'; }
            showToast('Error al publicar las notas.', 'danger');
        });
    }

    // ─── Tab 3: Estadísticas del examen ─────────────────────────────────────

    function loadExamStats(examId) {
        var section = document.getElementById('evalia-stats-section');
        if (!section || !examId) { return; }
        section.style.display = '';

        Ajax.call([{
            methodname: 'local_evalia_get_exam_stats',
            args: { examid: examId }
        }])[0].then(function(r) {
            renderExamStats(r);
        }).fail(function(err) {
            Log.error('evalia_teacher: get_exam_stats failed', err);
        });
    }

    function renderExamStats(r) {
        // ── Summary cards ────────────────────────────────────────────────────
        var summaryEl = document.getElementById('evalia-stats-summary');
        if (summaryEl) {
            var total = r.counts.assigned + r.counts.started + r.counts.submitted + r.counts.graded;
            var passRate = r.graded > 0 ? Math.round(r.pass_count / r.graded * 100) : 0;
            summaryEl.innerHTML =
                statCard('👥', 'Total', total,           'secondary') +
                statCard('✅', 'Calificados', r.graded,   'success') +
                statCard('📤', 'Enviados',   r.counts.submitted, 'info') +
                statCard('⭐', 'Promedio',   r.graded > 0 ? r.avg_score.toFixed(1) + '/10' : '—', 'primary') +
                statCard('🎯', 'Aprobados',  r.graded > 0 ? passRate + '%' : '—', passRate >= 50 ? 'success' : 'warning');
        }

        // ── Score bands ───────────────────────────────────────────────────────
        var bandsEl = document.getElementById('evalia-stats-bands');
        if (bandsEl && r.bands) {
            var maxCount = Math.max.apply(null, r.bands.map(function(b) { return b.count; })) || 1;
            var bandColors = ['danger', 'warning', 'warning', 'success', 'success'];
            var rows = r.bands.map(function(b, i) {
                var pct = Math.round(b.count / maxCount * 100);
                return '<div class="d-flex align-items-center gap-2 mb-1">' +
                    '<span class="text-muted small" style="width:35px;">' + b.label + '</span>' +
                    '<div class="flex-grow-1 bg-light rounded" style="height:18px;">' +
                    '<div class="bg-' + bandColors[i] + ' rounded h-100" style="width:' + pct + '%"></div>' +
                    '</div>' +
                    '<span class="small fw-bold" style="width:20px;">' + b.count + '</span>' +
                    '</div>';
            }).join('');
            bandsEl.innerHTML = rows || '<p class="text-muted small">Sin datos aún.</p>';
        }

        // ── Top failed questions ───────────────────────────────────────────────
        var failedEl = document.getElementById('evalia-stats-failed');
        if (failedEl) {
            if (!r.top_failed || r.top_failed.length === 0) {
                failedEl.innerHTML = '<p class="text-muted small">Sin datos aún.</p>';
            } else {
                // Difficulty: Básica=teal, Media=blue, Avanzada=orange — all with white text
                var diffStyle = {
                    'Básica':   'background:#0d9488;color:#fff;',
                    'Media':    'background:#2563eb;color:#fff;',
                    'Avanzada': 'background:#ea580c;color:#fff;'
                };
                var items = r.top_failed.map(function(q, idx) {
                    var pct  = q.total > 0 ? Math.round(q.fail_count / q.total * 100) : 0;
                    var stem = q.stem.length > 100 ? q.stem.substring(0, 97) + '…' : q.stem;
                    var badges = '';
                    if (q.topic) {
                        badges += '<span class="badge me-1" style="background:#6366f1;color:#fff;" title="Tema">' + q.topic + '</span>';
                    }
                    if (q.difficulty) {
                        var ds = diffStyle[q.difficulty] || 'background:#6b7280;color:#fff;';
                        badges += '<span class="badge" style="' + ds + '">' + q.difficulty + '</span>';
                    }
                    return '<div class="mb-2 border rounded p-2">' +
                        '<div class="d-flex justify-content-between align-items-start mb-1">' +
                        '<span class="text-muted small fw-bold me-2">#' + (idx + 1) + '</span>' +
                        '<div class="flex-grow-1">' + badges + '</div>' +
                        '<span class="small text-danger fw-bold ms-2">' + pct + '% fallaron</span>' +
                        '</div>' +
                        '<div class="small text-dark mb-1">' + stem + '</div>' +
                        '<div class="progress" style="height:6px;">' +
                        '<div class="progress-bar bg-danger" style="width:' + pct + '%"></div>' +
                        '</div>' +
                        '</div>';
                }).join('');
                failedEl.innerHTML = items;
            }
        }
    }

    function statCard(icon, label, value, color) {
        return '<div class="col">' +
            '<div class="card text-center border-' + color + '">' +
            '<div class="card-body py-2 px-1">' +
            '<div class="fs-5">' + icon + '</div>' +
            '<div class="fw-bold text-' + color + '">' + value + '</div>' +
            '<div class="text-muted small">' + label + '</div>' +
            '</div></div></div>';
    }

    // ─── RAG: Indexar material del curso ────────────────────────────────────

    function handleIndexCourse() {
        var btn      = document.getElementById('evalia-btn-index-course');
        var statusEl = document.getElementById('evalia-index-status');
        if (!btn) { return; }

        btn.disabled = true;
        setLoading('evalia-index-loading', true);
        if (statusEl) { statusEl.textContent = ''; }

        Ajax.call([{
            methodname: 'local_evalia_index_course',
            args: { courseid: courseId, token: '' }
        }])[0].then(function(result) {
            btn.disabled = false;
            setLoading('evalia-index-loading', false);
            var indexed  = result.indexed  || [];
            var errors   = result.errors   || [];
            var chunks   = result.total_chunks || 0;

            var parts = [];
            if (indexed.length > 0) {
                parts.push(indexed.length + ' fuente(s), ' + chunks + ' fragmentos indexados');
            }
            if (errors.length > 0) {
                parts.push(errors.length + ' error(es)');
            }
            if (parts.length === 0) {
                parts.push('No se encontró material para indexar');
            }

            var msg = parts.join(' · ');
            if (statusEl) { statusEl.textContent = msg; }
            showToast(result.success ? ('✔ ' + msg) : ('⚠ ' + msg),
                      result.success ? 'success' : 'warning');
        }).fail(function(err) {
            btn.disabled = false;
            setLoading('evalia-index-loading', false);
            Log.error('evalia_teacher: index_course failed', err);
            if (statusEl) { statusEl.textContent = 'Error al indexar.'; }
            showToast('Error al indexar el material del curso.', 'danger');
        });
    }

    // ─── Init ────────────────────────────────────────────────────────────────

    return {
        init: function(config) {
            courseId = config.courseid;
            if (config.defaults && config.defaults.questions_default_count) {
                defaultQuestionsCount = parseInt(config.defaults.questions_default_count, 10) || 5;
            }

            // Tab 1 — Rúbrica + indexación de material
            loadRubric();

            var btnIndex = document.getElementById('evalia-btn-index-course');
            if (btnIndex) { btnIndex.addEventListener('click', handleIndexCourse); }

            var btnGenerate = document.getElementById('evalia-btn-generate-rubric');
            if (btnGenerate) { btnGenerate.addEventListener('click', handleGenerateRubric); }

            var btnSave = document.getElementById('evalia-btn-save-rubric');
            if (btnSave) { btnSave.addEventListener('click', function() { handleSaveRubric(false); }); }

            var btnActivate = document.getElementById('evalia-btn-activate-rubric');
            if (btnActivate) { btnActivate.addEventListener('click', function() { handleSaveRubric(true); }); }

            // Tab 2 — Banco de preguntas (lazy load on first click)
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

            // Tab 3 — Exámenes (lazy load on first click)
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
            function loadFeedbackPromptEditor(examId) {
                var section  = document.getElementById('evalia-feedback-prompt-section');
                var textarea = document.getElementById('evalia-feedback-prompt-textarea');
                if (!section || !textarea) { return; }
                section.style.display = '';

                // Load current prompt from DB via teacher.php config or fetch via WS.
                // Use the default prompt text when field is empty.
                var defaultPrompt = 'Eres SAIPA, asistente pedagógico de acompañamiento universitario.\n' +
                    'Acabas de conocer el resultado del examen de un alumno y tu misión es enviarle\n' +
                    'un mensaje personal, cálido y educativo por Telegram.\n\n' +
                    'El mensaje debe:\n' +
                    '1. Saludar al alumno por su nombre de pila\n' +
                    '2. Comunicar la nota de forma clara y honesta\n' +
                    '3. Por cada pregunta INCORRECTA: explicar brevemente qué respondió el alumno,\n' +
                    '   cuál era la respuesta correcta y POR QUÉ esa respuesta es la correcta\n' +
                    '4. Si todas fueron correctas: felicitarlo genuinamente\n' +
                    '5. Indicar en qué temas conviene profundizar según los errores\n' +
                    '6. Cerrar con una frase motivadora: los errores son oportunidades de aprendizaje\n\n' +
                    'Formato: HTML de Telegram (<b>negrita</b>, <i>cursiva</i>). Máximo ~600 palabras.\n' +
                    'Tono: cálido, directo, universitario. No paternalista.\n' +
                    'Responde ÚNICAMENTE con el mensaje, sin JSON ni comentarios.';

                // Populate from config if available, otherwise use default.
                var saved = (config.feedback_prompt || '').trim();
                textarea.value = saved || defaultPrompt;

                // Save button
                var btnSave   = document.getElementById('evalia-btn-save-prompt');
                var statusEl  = document.getElementById('evalia-prompt-save-status');
                if (btnSave) {
                    btnSave.addEventListener('click', function() {
                        btnSave.disabled = true;
                        if (statusEl) { statusEl.textContent = 'Guardando...'; }
                        Ajax.call([{
                            methodname: 'local_evalia_save_feedback_prompt',
                            args: { examid: examId, prompt: textarea.value }
                        }])[0].then(function(result) {
                            btnSave.disabled = false;
                            if (statusEl) {
                                statusEl.textContent = result.success ? '✔ Guardado' : result.message;
                                setTimeout(function() { statusEl.textContent = ''; }, 3000);
                            }
                        }).fail(function() {
                            btnSave.disabled = false;
                            if (statusEl) { statusEl.textContent = 'Error al guardar.'; }
                        });
                    });
                }

                // Reset button
                var btnReset = document.getElementById('evalia-btn-reset-prompt');
                if (btnReset) {
                    btnReset.addEventListener('click', function() {
                        if (window.confirm('¿Restaurar el prompt predeterminado? Se perderán los cambios guardados.')) {
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

            // Tab 4 — Legajos (lazy load on first click)
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
                    var endEl   = document.getElementById('evalia-end-date');
                    if (startEl && config.startdate) { startEl.value = config.startdate; }
                    if (endEl   && config.enddate)   { endEl.value   = config.enddate;   }
                }, 300);
            }

            // ── Modal: Flujo de trabajo ───────────────────────────────────────
            (function() {
                var modal       = document.getElementById('evalia-workflow-modal');
                var btnWorkflow = document.getElementById('evalia-btn-workflow');
                var closeBtn    = document.getElementById('evalia-workflow-modal-close');
                var header      = document.getElementById('evalia-workflow-modal-header');
                if (!modal || !btnWorkflow) { return; }

                btnWorkflow.addEventListener('click', function() {
                    modal.style.display = modal.style.display === 'none' ? '' : 'none';
                });
                if (closeBtn) {
                    closeBtn.addEventListener('click', function() { modal.style.display = 'none'; });
                }

                // Drag by header
                var isDragging = false, startX, startY, origLeft, origTop;
                if (header) {
                    header.addEventListener('mousedown', function(e) {
                        if (e.target === closeBtn) { return; }
                        isDragging = true;
                        startX = e.clientX;
                        startY = e.clientY;
                        var rect = modal.getBoundingClientRect();
                        origLeft = rect.left;
                        origTop  = rect.top;
                        modal.style.transform = 'none';
                        modal.style.left = origLeft + 'px';
                        modal.style.top  = origTop  + 'px';
                        e.preventDefault();
                    });
                }
                document.addEventListener('mousemove', function(e) {
                    if (!isDragging) { return; }
                    var dx = e.clientX - startX;
                    var dy = e.clientY - startY;
                    modal.style.left = (origLeft + dx) + 'px';
                    modal.style.top  = (origTop  + dy) + 'px';
                });
                document.addEventListener('mouseup', function() { isDragging = false; });
            }());

            // ── FAB: Asistente EVAL-IA ────────────────────────────────────────
            (function() {
                var fab      = document.getElementById('evalia-fab');
                var panel    = document.getElementById('evalia-chat-panel');
                var closeBtn = document.getElementById('evalia-chat-close');
                var sendBtn  = document.getElementById('evalia-chat-send');
                var input    = document.getElementById('evalia-chat-input');
                var messages = document.getElementById('evalia-chat-messages');
                if (!fab) { return; }

                function appendMsg(text, role) {
                    if (!messages) { return; }
                    var div = document.createElement('div');
                    div.className = 'evalia-msg evalia-msg--' + role;
                    div.textContent = text;
                    messages.appendChild(div);
                    messages.scrollTop = messages.scrollHeight;
                }

                function appendTyping() {
                    var div = document.createElement('div');
                    div.className = 'evalia-msg evalia-msg--assistant evalia-msg--typing';
                    div.textContent = '…';
                    messages.appendChild(div);
                    messages.scrollTop = messages.scrollHeight;
                    return div;
                }

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
                        args: { message: text, context: 'evalia_teacher' }
                    }])[0].then(function(r) {
                        if (typingEl && typingEl.parentNode) { typingEl.parentNode.removeChild(typingEl); }
                        appendMsg(r.reply || 'Sin respuesta.', 'assistant');
                        return r;
                    }).fail(function(err) {
                        if (typingEl && typingEl.parentNode) { typingEl.parentNode.removeChild(typingEl); }
                        appendMsg('Error al conectar con el asistente.', 'assistant');
                        Log.error('evalia_teacher: FAB chat error', err);
                    }).always(function() {
                        if (sendBtn) { sendBtn.disabled = false; }
                    });
                }

                // Toggle panel on FAB click (drag detection)
                var FAB_STORAGE_KEY = 'evalia_fab_pos';
                var isDragging = false, dragMoved = false;
                var dragStartX, dragStartY, fabStartLeft, fabStartTop;

                function applyFabPos(left, top) {
                    var w = window.innerWidth, h = window.innerHeight;
                    var fw = fab.offsetWidth, fh = fab.offsetHeight;
                    left = Math.max(0, Math.min(left, w - fw));
                    top  = Math.max(0, Math.min(top,  h - fh));
                    fab.style.left   = left + 'px';
                    fab.style.top    = top  + 'px';
                    fab.style.right  = 'auto';
                    fab.style.bottom = 'auto';
                    if (panel) {
                        panel.style.left   = Math.max(0, Math.min(left + fw - 360, w - 364)) + 'px';
                        panel.style.top    = Math.max(0, top - 470) + 'px';
                        panel.style.right  = 'auto';
                        panel.style.bottom = 'auto';
                    }
                }

                function saveFabPos(l, t) {
                    try { localStorage.setItem(FAB_STORAGE_KEY, JSON.stringify({l:l, t:t})); } catch(e) {}
                }

                function restoreFabPos() {
                    try {
                        var raw = localStorage.getItem(FAB_STORAGE_KEY);
                        if (!raw) { return false; }
                        var pos = JSON.parse(raw);
                        applyFabPos(pos.l, pos.t);
                        return true;
                    } catch(e) { return false; }
                }

                restoreFabPos();

                fab.addEventListener('mousedown', function(e) {
                    if (e.button !== 0) { return; }
                    isDragging = true;
                    dragMoved  = false;
                    dragStartX = e.clientX;
                    dragStartY = e.clientY;
                    var rect   = fab.getBoundingClientRect();
                    fabStartLeft = rect.left;
                    fabStartTop  = rect.top;
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

                document.addEventListener('mouseup', function(e) {
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

                if (sendBtn)  { sendBtn.addEventListener('click', sendMessage); }
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
