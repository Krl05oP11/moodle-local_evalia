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
 * EVAL-IA Student Exam Page.
 *
 * URL: /local/evalia/student_exam.php?student_examid=X
 * Renders the student's assigned questions && handles submission via AMD AJAX.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$studentexamid = required_param('student_examid', PARAM_INT);

$studentexam = $DB->get_record('evalia_student_exams', ['id' => $studentexamid], '*', MUST_EXIST);
$exam         = $DB->get_record('evalia_exams', ['id' => $studentexam->examid], '*', MUST_EXIST);
$context      = context_course::instance($exam->courseid);
$course       = $DB->get_record('course', ['id' => $exam->courseid], '*', MUST_EXIST);

require_login($course);

// Only the owner (or a teacher for preview) can access.
$isteacher = has_capability('local/evalia:manage', $context);
$isowner   = ((int)$USER->id === (int)$studentexam->userid);
if (!$isteacher && !$isowner) {
    throw new moodle_exception('nopermissions', 'error');
}
if (!$isteacher) {
    require_capability('local/evalia:take', $context);
}

$PAGE->set_context($context);
$PAGE->set_url('/local/evalia/student_exam.php', ['student_examid' => $studentexamid]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($exam->name . ' — EVAL-IA');
$PAGE->set_heading($exam->name);

// Load questions directly from DB (no WS overhead for server-side rendering).
$questionids = json_decode($studentexam->question_ids ?? '[]', true);

$questionsdata = [];
if (!empty($questionids)) {
    [$insql, $inparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid');
    $questions = $DB->get_records_select(
        'evalia_question_bank',
        "id $insql",
        $inparams,
        '',
        'id, stem, question_type, topic, difficulty, correct_answer, tolerance'
    );

    $optqids = array_keys($questions);
    $optionsbyq = [];
    if (!empty($optqids)) {
        [$optsql, $optparams] = $DB->get_in_or_equal($optqids, SQL_PARAMS_NAMED, 'oqid');
        $options = $DB->get_records_select(
            'evalia_question_options',
            "questionid $optsql",
            $optparams,
            'sortorder ASC',
            'id, questionid, option_text, sortorder, is_correct'
        );
        foreach ($options as $opt) {
            $optionsbyq[$opt->questionid][] = $opt;
        }
    }

    foreach ($questionids as $idx => $qid) {
        if (!isset($questions[$qid])) {
            continue;
        }
        $q = $questions[$qid];
        $questionsdata[] = [
            'num'            => $idx + 1,
            'id'             => (int) $qid,
            'stem'           => format_text($q->stem, FORMAT_HTML),
            'question_type'  => $q->question_type,
            'topic'          => $q->topic,
            'difficulty'     => $q->difficulty,
            'options'        => $optionsbyq[$qid] ?? [],
            'correct_answer' => $q->correct_answer ?? '',
            'tolerance'      => (float) ($q->tolerance ?? 0.0),
        ];
    }
}

// Mark as started if needed.
if ($studentexam->status === 'assigned' && $isowner) {
    $DB->update_record('evalia_student_exams', (object) [
        'id'           => $studentexam->id,
        'status'       => 'started',
        'timemodified' => time(),
    ]);
    $studentexam->status = 'started';
}

$alreadysubmitted = in_array($studentexam->status, ['submitted', 'graded', 'published']);
$previewmode      = $isteacher && !$isowner;  // teacher viewing someone else's exam

// Pre-load stored answers (needed for correctness maps && later rendering).
$storedanswers = [];
if ($previewmode && $alreadysubmitted) {
    $storedanswers = json_decode($studentexam->answers ?? '{}', true) ?? [];
}

// ── Correctness maps for teacher preview of graded exams ────────────────────
$correctoptionmap = [];   // qid → correct option_text (multichoice / truefalse)
$correctnessmap    = [];   // qid → bool
$essayevals        = [];   // qid → ['score' => float, 'feedback' => string]
if ($previewmode && in_array($studentexam->status, ['graded', 'published'])) {
    // Extract essay AI evals persisted in the answers JSON.
    $essayevals = (array) ($storedanswers['__essay_eval__'] ?? []);

    foreach ($optionsbyq as $qid => $opts) {
        foreach ($opts as $opt) {
            if ((int) $opt->is_correct === 1) {
                $correctoptionmap[(int) $qid] = $opt->option_text;
                break;
            }
        }
    }
    foreach ($questionsdata as $q) {
        $sa = trim($storedanswers[(string) $q['id']] ?? '');
        if ($q['question_type'] === 'multichoice' || $q['question_type'] === 'truefalse') {
            $ca = $correctoptionmap[$q['id']] ?? null;
            $correctnessmap[$q['id']] = ($ca !== null && strtolower($sa) === strtolower(trim($ca)));
        } else if ($q['question_type'] === 'numerical') {
            $correctnessmap[$q['id']] = (is_numeric($sa) && abs((float) $sa - (float) $q['correct_answer']) <= $q['tolerance']);
        } else if ($q['question_type'] === 'essay') {
            // Essay correctness: AI score >= 0.6 (normalised to 0–1).
            $evalscore = (float) ($essayevals[(string)$q['id']]['score'] ?? 0.0);
            $correctnessmap[$q['id']] = ($evalscore >= 0.6);
        } else {
            $correctnessmap[$q['id']] = (strtolower($sa) === strtolower(trim($q['correct_answer'])));
        }
    }
}
// ────────────────────────────────────────────────────────────────────────────

// Load AMD: student gets full timer+submit, teacher in submitted mode gets grade panel.
if (!$previewmode) {
    $PAGE->requires->js_call_amd('local_evalia/evalia_student', 'init', [[
        'student_examid'   => $studentexamid,
        'time_limit_min'   => (int) $exam->time_limit_min,
        'already_submitted' => $alreadysubmitted,
    ]]);
} else if ($previewmode && $studentexam->status === 'submitted') {
    $PAGE->requires->js_call_amd('local_evalia/evalia_student', 'initGradePanel', [[
        'student_examid' => $studentexamid,
        'answers_json'   => $studentexam->answers ?? '{}',
    ]]);
}

// Fetch the student's name for the teacher preview banner.
$studentuser = $previewmode
    ? $DB->get_record(
        'user',
        ['id' => $studentexam->userid],
        'id,' . implode(',', \core_user\fields::get_name_fields())
    )
    : null;

echo $OUTPUT->header();

// ── Teacher preview banner ────────────────────────────────────────────────────
if ($previewmode) {
    $studentname = $studentuser ? fullname($studentuser) : 'alumno';
    $statuslabel = [
        'assigned'  => 'Asignado (aún no iniciado)',
        'started'   => 'En progreso',
        'submitted' => 'Enviado — pendiente de calificación',
        'graded'    => 'Calificado',
    ][$studentexam->status] ?? $studentexam->status;
    echo '<div class="alert alert-secondary d-flex align-items-center gap-3 mt-3 mb-0">';
    echo '<span style="font-size:1.5rem;">🔍</span>';
    echo '<div>';
    echo '<strong>Vista docente — solo lectura</strong><br>';
    echo '<small>Alumno: <strong>' . htmlspecialchars($studentname) . '</strong> &nbsp;·&nbsp; ';
    echo 'Estado: ' . htmlspecialchars($statuslabel) . '</small>';
    echo '</div>';
    echo '</div>';
}

// ── Student submitted/graded view: simple banner, no questions ────────────────
if ($alreadysubmitted && !$previewmode) {
    echo '<div class="alert alert-success mt-3">';
    echo '<h4>✅ Examen enviado</h4>';
    if (in_array($studentexam->status, ['graded', 'published'])) {
        $score = number_format((float) $studentexam->score, 1);
        echo '<p class="mb-0">Tu nota: <strong>' . $score . ' / 10.0</strong>. El docente ya calificó tu examen.</p>';
    } else {
        echo '<p class="mb-0">Tu examen fue recibido correctamente. El docente lo revisará próximamente.</p>';
    }
    echo '</div>';
    echo $OUTPUT->footer();
    exit;
}

// ── Teacher preview of graded exam: show score banner then fall through to questions ──
if ($previewmode && in_array($studentexam->status, ['graded', 'published'])) {
    $score = number_format((float) $studentexam->score, 1);
    echo '<div class="alert alert-success mt-3 mb-2">';
    echo '✅ <strong>Calificado:</strong> ' . $score . ' / 10.0';
    echo '</div>';
}

// $storedanswers already loaded above (before HTML output).

// ── Active exam ───────────────────────────────────────────────────────────────

// Timer bar: only for the student, never for teacher preview.
if (!$previewmode && $exam->time_limit_min > 0) {
    echo '<div id="evalia-timer-bar" class="alert alert-warning d-flex justify-content-between align-items-center mt-3 mb-0">';
    echo '<span>⏱ Tiempo restante: <strong id="evalia-timer-display">--:--</strong></span>';
    echo '<span class="small text-muted">El examen se envía automáticamente al llegar a 00:00.</span>';
    echo '</div>';
}

if (!empty($exam->instructions)) {
    echo '<div class="alert alert-info mt-3">' . format_text($exam->instructions, FORMAT_HTML) . '</div>';
}

// In preview mode render a plain div (no form), with all inputs disabled.
if ($previewmode) {
    echo '<div class="mt-3">';
} else {
    echo '<form id="evalia-exam-form" class="mt-3">';
    echo '<input type="hidden" name="student_examid" value="' . $studentexamid . '">';
}

$diffweights = ['basic' => 1, 'medium' => 2, 'advanced' => 3];

foreach ($questionsdata as $q) {
    $diffmap   = ['basic' => 'info', 'medium' => 'warning', 'advanced' => 'danger'];
    $diffclass = $diffmap[$q['difficulty']] ?? 'secondary';
    $disabled   = $previewmode ? ' disabled' : '';
    $qweight   = $diffweights[$q['difficulty']] ?? 1;

    $cardborder = '';
    if ($previewmode && in_array($studentexam->status, ['graded', 'published'])) {
        $cardborder = ($correctnessmap[$q['id']] ?? false) ? ' border-success' : ' border-danger';
    }
    echo '<div class="card mb-3' . $cardborder . '" id="evalia-q-' . $q['id'] . '">';
    echo '<div class="card-header d-flex justify-content-between align-items-start">';
    echo '<span><strong>Pregunta ' . $q['num'] . '</strong> — <small class="text-muted">' . htmlspecialchars($q['topic']) . '</small></span>';
    echo '<span class="d-flex gap-1 align-items-center">';
    // Difficulty badge.
    echo '<span class="badge bg-' . $diffclass . '">' . htmlspecialchars($q['difficulty']) . '</span>';
    // Points badge: plain in active exam; score obtained in graded preview.
    if ($previewmode && in_array($studentexam->status, ['graded', 'published'])) {
        if ($q['question_type'] === 'essay') {
            $evalscore = (float) ($essayevals[(string)$q['id']]['score'] ?? 0.0);
            $obtained   = number_format($evalscore * $qweight, 1);
            $ptsclass  = ($evalscore >= 0.6) ? 'bg-success' : (($evalscore > 0) ? 'bg-warning text-dark' : 'bg-danger');
        } else {
            $qcorrect  = $correctnessmap[$q['id']] ?? false;
            $obtained   = $qcorrect ? $qweight : 0;
            $ptsclass  = $qcorrect ? 'bg-success' : 'bg-danger';
        }
        echo '<span class="badge ' . $ptsclass . '">' . $obtained . '/' . $qweight . ' pts</span>';
    } else {
        echo '<span class="badge bg-secondary">' . $qweight . ' pts</span>';
    }
    echo '</span>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<p class="mb-3">' . $q['stem'] . '</p>';

    // Stored answer for this question (used in teacher preview of submitted/graded exams).
    $storedanswer = $storedanswers[(string)$q['id']] ?? null;

    $gradedpreview = ($previewmode && in_array($studentexam->status, ['graded', 'published']));

    if ($q['question_type'] === 'multichoice' || $q['question_type'] === 'truefalse') {
        foreach ($q['options'] as $opt) {
            $optid       = 'q' . $q['id'] . '_opt' . $opt->id;
            $optval      = htmlspecialchars($opt->option_text);
            $isselected  = ($storedanswer !== null && $storedanswer === $opt->option_text);
            $iscorrecto = ($gradedpreview && (int) $opt->is_correct === 1);
            $checked      = $isselected ? ' checked' : '';
            if ($gradedpreview) {
                if ($iscorrecto) {
                    $highlight = ' fw-bold text-success';
                    $indicator = ' ✅';
                } else if ($isselected) {
                    $highlight = ' text-danger text-decoration-line-through';
                    $indicator = ' ❌';
                } else {
                    $highlight = '';
                    $indicator = '';
                }
            } else {
                $highlight = $isselected ? ' fw-bold text-primary' : '';
                $indicator = '';
            }
            echo '<div class="form-check mb-1">';
            echo '<input class="form-check-input evalia-answer" type="radio" '
                . 'name="answer_' . $q['id'] . '" id="' . $optid . '" '
                . 'value="' . $optval . '" data-qid="' . $q['id'] . '"' . $checked . $disabled . '>';
            echo '<label class="form-check-label' . $highlight . '" for="' . $optid . '">' . $optval . $indicator . '</label>';
            echo '</div>';
        }
    } else if ($q['question_type'] === 'numerical') {
        $val        = $storedanswer !== null ? ' value="' . htmlspecialchars($storedanswer) . '"' : '';
        $qcorrect  = $gradedpreview && ($correctnessmap[$q['id']] ?? false);
        $inpborder = $gradedpreview ? (' border-' . ($qcorrect ? 'success' : 'danger')) : '';
        echo '<div class="d-flex align-items-center gap-2">';
        echo '<input type="number" step="any" class="form-control evalia-answer' . $inpborder . '" '
            . 'name="answer_' . $q['id'] . '" data-qid="' . $q['id'] . '" '
            . 'placeholder="Ingresá un valor numérico" style="max-width:200px;"' . $val . $disabled . '>';
        if ($gradedpreview) {
            echo $qcorrect ? '<span class="text-success fw-bold fs-5">✅</span>' : '<span class="text-danger fw-bold fs-5">❌</span>';
        }
        echo '</div>';
        if ($gradedpreview && !$qcorrect) {
            echo '<small class="text-muted mt-1 d-block">Respuesta correcta: <strong>'
                . htmlspecialchars($q['correct_answer']) . '</strong>'
                . ($q['tolerance'] > 0 ? ' (±' . $q['tolerance'] . ')' : '') . '</small>';
        }
    } else if ($q['question_type'] === 'essay') {
        $taval     = $storedanswer !== null ? htmlspecialchars($storedanswer) : '';
        if ($gradedpreview) {
            $evalscore  = (float) ($essayevals[(string)$q['id']]['score'] ?? -1.0);
            $aifeedback = $essayevals[(string)$q['id']]['feedback'] ?? '';
            $bordercls  = ($evalscore < 0) ? '' : (($evalscore >= 0.6) ? ' border-success' : (($evalscore > 0) ? ' border-warning' : ' border-danger'));
            echo '<textarea class="form-control evalia-answer' . $bordercls . '" '
                . 'name="answer_' . $q['id'] . '" data-qid="' . $q['id'] . '" '
                . 'rows="5" placeholder="Redactá tu respuesta aquí" disabled>'
                . $taval . '</textarea>';
            if ($evalscore >= 0) {
                $pct = round($evalscore * 100);
                $badgecls = ($evalscore >= 0.6) ? 'bg-success' : (($evalscore > 0) ? 'bg-warning text-dark' : 'bg-danger');
                echo '<div class="mt-2 d-flex align-items-center gap-2">';
                echo '<span class="badge ' . $badgecls . '">IA: ' . $pct . '%</span>';
                if (!empty($aifeedback)) {
                    echo '<small class="text-muted">' . htmlspecialchars($aifeedback) . '</small>';
                }
                echo '</div>';
            }
        } else {
            echo '<textarea class="form-control evalia-answer" '
                . 'name="answer_' . $q['id'] . '" data-qid="' . $q['id'] . '" '
                . 'rows="5" placeholder="Redactá tu respuesta aquí"' . $disabled . '>'
                . $taval . '</textarea>';
        }
    } else {
        // shortanswer
        $val        = $storedanswer !== null ? ' value="' . htmlspecialchars($storedanswer) . '"' : '';
        $qcorrect  = $gradedpreview && ($correctnessmap[$q['id']] ?? false);
        $inpborder = $gradedpreview ? (' border-' . ($qcorrect ? 'success' : 'danger')) : '';
        echo '<div class="d-flex align-items-center gap-2">';
        echo '<input type="text" class="form-control evalia-answer' . $inpborder . '" '
            . 'name="answer_' . $q['id'] . '" data-qid="' . $q['id'] . '" '
            . 'placeholder="Escribí tu respuesta"' . $val . $disabled . '>';
        if ($gradedpreview) {
            echo $qcorrect ? '<span class="text-success fw-bold fs-5">✅</span>' : '<span class="text-danger fw-bold fs-5">❌</span>';
        }
        echo '</div>';
        if ($gradedpreview && !$qcorrect) {
            echo '<small class="text-muted mt-1 d-block">Respuesta correcta: <strong>'
                . htmlspecialchars($q['correct_answer']) . '</strong></small>';
        }
    }

    echo '</div>';  // card-body
    echo '</div>';  // card
}

if ($previewmode) {
    echo '</div>';
    // Grade panel: only for submitted status (graded already shows score above).
    if ($studentexam->status === 'submitted') {
        echo '<div class="card border-warning mt-3 mb-5" id="evalia-grade-panel">';
        echo '<div class="card-header bg-warning text-dark fw-bold">📝 Calificar examen</div>';
        echo '<div class="card-body">';
        echo '<p class="text-muted small mb-3">Al confirmar, el engine de IA evaluará las respuestas del alumno y asignará una nota automáticamente.</p>';
        echo '<div class="d-flex align-items-center gap-3">';
        echo '<button id="evalia-btn-grade" class="btn btn-warning btn-lg">⚡ Calificar con IA</button>';
        echo '<span id="evalia-grade-status" class="text-muted small"></span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
} else {
    echo '</form>';
    echo '<div class="d-flex justify-content-between align-items-center mb-5 mt-2">';
    echo '<div id="evalia-submit-status" class="text-muted small"></div>';
    echo '<button id="evalia-btn-submit-exam" class="btn btn-success btn-lg">📤 Enviar examen</button>';
    echo '</div>';
}

// Toast notification.
echo '<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;">';
echo '<div id="evalia-toast" class="toast align-items-center text-white border-0" role="alert">';
echo '<div class="d-flex"><div class="toast-body" id="evalia-toast-body"></div>';
echo '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>';
echo '</div></div></div>';

echo $OUTPUT->footer();
