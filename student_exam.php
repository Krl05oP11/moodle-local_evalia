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
 * EVAL-IA Student Exam Page.
 *
 * URL: /local/evalia/student_exam.php?student_examid=X
 * Renders the student's assigned questions and handles submission via AMD AJAX.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$student_examid = required_param('student_examid', PARAM_INT);

$student_exam = $DB->get_record('evalia_student_exams', ['id' => $student_examid], '*', MUST_EXIST);
$exam         = $DB->get_record('evalia_exams', ['id' => $student_exam->examid], '*', MUST_EXIST);
$context      = context_course::instance($exam->courseid);
$course       = $DB->get_record('course', ['id' => $exam->courseid], '*', MUST_EXIST);

require_login($course);

// Only the owner (or a teacher for preview) can access.
$is_teacher = has_capability('local/evalia:manage', $context);
$is_owner   = ((int)$USER->id === (int)$student_exam->userid);
if (!$is_teacher && !$is_owner) {
    throw new moodle_exception('nopermissions', 'error');
}
if (!$is_teacher) {
    require_capability('local/evalia:take', $context);
}

$PAGE->set_context($context);
$PAGE->set_url('/local/evalia/student_exam.php', ['student_examid' => $student_examid]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title($exam->name . ' — EVAL-IA');
$PAGE->set_heading($exam->name);

// Load questions directly from DB (no WS overhead for server-side rendering).
$question_ids = json_decode($student_exam->question_ids ?? '[]', true);

$questions_data = [];
if (!empty($question_ids)) {
    [$in_sql, $in_params] = $DB->get_in_or_equal($question_ids, SQL_PARAMS_NAMED, 'qid');
    $questions = $DB->get_records_select(
        'evalia_question_bank',
        "id $in_sql",
        $in_params,
        '',
        'id, stem, question_type, topic, difficulty, correct_answer, tolerance'
    );

    $opt_qids = array_keys($questions);
    $options_by_q = [];
    if (!empty($opt_qids)) {
        [$opt_sql, $opt_params] = $DB->get_in_or_equal($opt_qids, SQL_PARAMS_NAMED, 'oqid');
        $options = $DB->get_records_select(
            'evalia_question_options',
            "questionid $opt_sql",
            $opt_params,
            'sortorder ASC',
            'id, questionid, option_text, sortorder, is_correct'
        );
        foreach ($options as $opt) {
            $options_by_q[$opt->questionid][] = $opt;
        }
    }

    foreach ($question_ids as $idx => $qid) {
        if (!isset($questions[$qid])) {
            continue;
        }
        $q = $questions[$qid];
        $questions_data[] = [
            'num'            => $idx + 1,
            'id'             => (int) $qid,
            'stem'           => format_text($q->stem, FORMAT_HTML),
            'question_type'  => $q->question_type,
            'topic'          => $q->topic,
            'difficulty'     => $q->difficulty,
            'options'        => $options_by_q[$qid] ?? [],
            'correct_answer' => $q->correct_answer ?? '',
            'tolerance'      => (float) ($q->tolerance ?? 0.0),
        ];
    }
}

// Mark as started if needed.
if ($student_exam->status === 'assigned' && $is_owner) {
    $DB->update_record('evalia_student_exams', (object) [
        'id'           => $student_exam->id,
        'status'       => 'started',
        'timemodified' => time(),
    ]);
    $student_exam->status = 'started';
}

$already_submitted = in_array($student_exam->status, ['submitted', 'graded']);
$preview_mode      = $is_teacher && !$is_owner;  // teacher viewing someone else's exam

// Pre-load stored answers (needed for correctness maps and later rendering).
$stored_answers = [];
if ($preview_mode && $already_submitted) {
    $stored_answers = json_decode($student_exam->answers ?? '{}', true) ?? [];
}

// ── Correctness maps for teacher preview of graded exams ────────────────────
$correct_option_map = [];   // qid → correct option_text (multichoice / truefalse)
$correctness_map    = [];   // qid → bool
$essay_evals        = [];   // qid → ['score' => float, 'feedback' => string]
if ($preview_mode && $student_exam->status === 'graded') {
    // Extract essay AI evals persisted in the answers JSON.
    $essay_evals = (array) ($stored_answers['__essay_eval__'] ?? []);

    foreach ($options_by_q as $qid => $opts) {
        foreach ($opts as $opt) {
            if ((int) $opt->is_correct === 1) {
                $correct_option_map[(int) $qid] = $opt->option_text;
                break;
            }
        }
    }
    foreach ($questions_data as $q) {
        $sa = trim($stored_answers[(string) $q['id']] ?? '');
        if ($q['question_type'] === 'multichoice' || $q['question_type'] === 'truefalse') {
            $ca = $correct_option_map[$q['id']] ?? null;
            $correctness_map[$q['id']] = ($ca !== null && strtolower($sa) === strtolower(trim($ca)));
        } elseif ($q['question_type'] === 'numerical') {
            $correctness_map[$q['id']] = (is_numeric($sa) && abs((float) $sa - (float) $q['correct_answer']) <= $q['tolerance']);
        } elseif ($q['question_type'] === 'essay') {
            // Essay correctness: AI score >= 0.6 (normalised to 0–1).
            $eval_score = (float) ($essay_evals[(string)$q['id']]['score'] ?? 0.0);
            $correctness_map[$q['id']] = ($eval_score >= 0.6);
        } else {
            $correctness_map[$q['id']] = (strtolower($sa) === strtolower(trim($q['correct_answer'])));
        }
    }
}
// ────────────────────────────────────────────────────────────────────────────

// Load AMD: student gets full timer+submit, teacher in submitted mode gets grade panel.
if (!$preview_mode) {
    $PAGE->requires->js_call_amd('local_evalia/evalia_student', 'init', [[
        'student_examid'   => $student_examid,
        'time_limit_min'   => (int) $exam->time_limit_min,
        'already_submitted'=> $already_submitted,
    ]]);
} else if ($preview_mode && $student_exam->status === 'submitted') {
    $PAGE->requires->js_call_amd('local_evalia/evalia_student', 'initGradePanel', [[
        'student_examid' => $student_examid,
        'answers_json'   => $student_exam->answers ?? '{}',
    ]]);
}

// Fetch the student's name for the teacher preview banner.
$student_user = $preview_mode
    ? $DB->get_record('user', ['id' => $student_exam->userid], 'id, firstname, lastname')
    : null;

echo $OUTPUT->header();

// ── Teacher preview banner ────────────────────────────────────────────────────
if ($preview_mode) {
    $student_name = $student_user ? fullname($student_user) : 'alumno';
    $status_label = [
        'assigned'  => 'Asignado (aún no iniciado)',
        'started'   => 'En progreso',
        'submitted' => 'Enviado — pendiente de calificación',
        'graded'    => 'Calificado',
    ][$student_exam->status] ?? $student_exam->status;
    echo '<div class="alert alert-secondary d-flex align-items-center gap-3 mt-3 mb-0">';
    echo '<span style="font-size:1.5rem;">🔍</span>';
    echo '<div>';
    echo '<strong>Vista docente — solo lectura</strong><br>';
    echo '<small>Alumno: <strong>' . htmlspecialchars($student_name) . '</strong> &nbsp;·&nbsp; ';
    echo 'Estado: ' . htmlspecialchars($status_label) . '</small>';
    echo '</div>';
    echo '</div>';
}

// ── Student submitted/graded view: simple banner, no questions ────────────────
if ($already_submitted && !$preview_mode) {
    echo '<div class="alert alert-success mt-3">';
    echo '<h4>✅ Examen enviado</h4>';
    if ($student_exam->status === 'graded') {
        $score = number_format((float) $student_exam->score, 1);
        echo '<p class="mb-0">Tu nota: <strong>' . $score . ' / 10.0</strong>. El docente ya calificó tu examen.</p>';
    } else {
        echo '<p class="mb-0">Tu examen fue recibido correctamente. El docente lo revisará próximamente.</p>';
    }
    echo '</div>';
    echo $OUTPUT->footer();
    exit;
}

// ── Teacher preview of graded exam: show score banner then fall through to questions ──
if ($preview_mode && $student_exam->status === 'graded') {
    $score = number_format((float) $student_exam->score, 1);
    echo '<div class="alert alert-success mt-3 mb-2">';
    echo '✅ <strong>Calificado:</strong> ' . $score . ' / 10.0';
    echo '</div>';
}

// $stored_answers already loaded above (before HTML output).

// ── Active exam ───────────────────────────────────────────────────────────────

// Timer bar: only for the student, never for teacher preview.
if (!$preview_mode && $exam->time_limit_min > 0) {
    echo '<div id="evalia-timer-bar" class="alert alert-warning d-flex justify-content-between align-items-center mt-3 mb-0">';
    echo '<span>⏱ Tiempo restante: <strong id="evalia-timer-display">--:--</strong></span>';
    echo '<span class="small text-muted">El examen se envía automáticamente al llegar a 00:00.</span>';
    echo '</div>';
}

if (!empty($exam->instructions)) {
    echo '<div class="alert alert-info mt-3">' . format_text($exam->instructions, FORMAT_HTML) . '</div>';
}

// In preview mode render a plain div (no form), with all inputs disabled.
if ($preview_mode) {
    echo '<div class="mt-3">';
} else {
    echo '<form id="evalia-exam-form" class="mt-3">';
    echo '<input type="hidden" name="student_examid" value="' . $student_examid . '">';
}

$diff_weights = ['basic' => 1, 'medium' => 2, 'advanced' => 3];

foreach ($questions_data as $q) {
    $diff_map   = ['basic' => 'info', 'medium' => 'warning', 'advanced' => 'danger'];
    $diff_class = $diff_map[$q['difficulty']] ?? 'secondary';
    $disabled   = $preview_mode ? ' disabled' : '';
    $q_weight   = $diff_weights[$q['difficulty']] ?? 1;

    $card_border = '';
    if ($preview_mode && $student_exam->status === 'graded') {
        $card_border = ($correctness_map[$q['id']] ?? false) ? ' border-success' : ' border-danger';
    }
    echo '<div class="card mb-3' . $card_border . '" id="evalia-q-' . $q['id'] . '">';
    echo '<div class="card-header d-flex justify-content-between align-items-start">';
    echo '<span><strong>Pregunta ' . $q['num'] . '</strong> — <small class="text-muted">' . htmlspecialchars($q['topic']) . '</small></span>';
    echo '<span class="d-flex gap-1 align-items-center">';
    // Difficulty badge.
    echo '<span class="badge bg-' . $diff_class . '">' . htmlspecialchars($q['difficulty']) . '</span>';
    // Points badge: plain in active exam; score obtained in graded preview.
    if ($preview_mode && $student_exam->status === 'graded') {
        if ($q['question_type'] === 'essay') {
            $eval_score = (float) ($essay_evals[(string)$q['id']]['score'] ?? 0.0);
            $obtained   = number_format($eval_score * $q_weight, 1);
            $pts_class  = ($eval_score >= 0.6) ? 'bg-success' : (($eval_score > 0) ? 'bg-warning text-dark' : 'bg-danger');
        } else {
            $q_correct  = $correctness_map[$q['id']] ?? false;
            $obtained   = $q_correct ? $q_weight : 0;
            $pts_class  = $q_correct ? 'bg-success' : 'bg-danger';
        }
        echo '<span class="badge ' . $pts_class . '">' . $obtained . '/' . $q_weight . ' pts</span>';
    } else {
        echo '<span class="badge bg-secondary">' . $q_weight . ' pts</span>';
    }
    echo '</span>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<p class="mb-3">' . $q['stem'] . '</p>';

    // Stored answer for this question (used in teacher preview of submitted/graded exams).
    $stored_answer = $stored_answers[(string)$q['id']] ?? null;

    $graded_preview = ($preview_mode && $student_exam->status === 'graded');

    if ($q['question_type'] === 'multichoice' || $q['question_type'] === 'truefalse') {
        foreach ($q['options'] as $opt) {
            $opt_id       = 'q' . $q['id'] . '_opt' . $opt->id;
            $opt_val      = htmlspecialchars($opt->option_text);
            $is_selected  = ($stored_answer !== null && $stored_answer === $opt->option_text);
            $is_correct_o = ($graded_preview && (int) $opt->is_correct === 1);
            $checked      = $is_selected ? ' checked' : '';
            if ($graded_preview) {
                if ($is_correct_o) {
                    $highlight = ' fw-bold text-success';
                    $indicator = ' ✅';
                } elseif ($is_selected) {
                    $highlight = ' text-danger text-decoration-line-through';
                    $indicator = ' ❌';
                } else {
                    $highlight = '';
                    $indicator = '';
                }
            } else {
                $highlight = $is_selected ? ' fw-bold text-primary' : '';
                $indicator = '';
            }
            echo '<div class="form-check mb-1">';
            echo '<input class="form-check-input evalia-answer" type="radio" '
                . 'name="answer_' . $q['id'] . '" id="' . $opt_id . '" '
                . 'value="' . $opt_val . '" data-qid="' . $q['id'] . '"' . $checked . $disabled . '>';
            echo '<label class="form-check-label' . $highlight . '" for="' . $opt_id . '">' . $opt_val . $indicator . '</label>';
            echo '</div>';
        }
    } elseif ($q['question_type'] === 'numerical') {
        $val        = $stored_answer !== null ? ' value="' . htmlspecialchars($stored_answer) . '"' : '';
        $q_correct  = $graded_preview && ($correctness_map[$q['id']] ?? false);
        $inp_border = $graded_preview ? (' border-' . ($q_correct ? 'success' : 'danger')) : '';
        echo '<div class="d-flex align-items-center gap-2">';
        echo '<input type="number" step="any" class="form-control evalia-answer' . $inp_border . '" '
            . 'name="answer_' . $q['id'] . '" data-qid="' . $q['id'] . '" '
            . 'placeholder="Ingresá un valor numérico" style="max-width:200px;"' . $val . $disabled . '>';
        if ($graded_preview) {
            echo $q_correct ? '<span class="text-success fw-bold fs-5">✅</span>' : '<span class="text-danger fw-bold fs-5">❌</span>';
        }
        echo '</div>';
        if ($graded_preview && !$q_correct) {
            echo '<small class="text-muted mt-1 d-block">Respuesta correcta: <strong>'
                . htmlspecialchars($q['correct_answer']) . '</strong>'
                . ($q['tolerance'] > 0 ? ' (±' . $q['tolerance'] . ')' : '') . '</small>';
        }
    } elseif ($q['question_type'] === 'essay') {
        $ta_val     = $stored_answer !== null ? htmlspecialchars($stored_answer) : '';
        if ($graded_preview) {
            $eval_score  = (float) ($essay_evals[(string)$q['id']]['score'] ?? -1.0);
            $ai_feedback = $essay_evals[(string)$q['id']]['feedback'] ?? '';
            $border_cls  = ($eval_score < 0) ? '' : (($eval_score >= 0.6) ? ' border-success' : (($eval_score > 0) ? ' border-warning' : ' border-danger'));
            echo '<textarea class="form-control evalia-answer' . $border_cls . '" '
                . 'name="answer_' . $q['id'] . '" data-qid="' . $q['id'] . '" '
                . 'rows="5" placeholder="Redactá tu respuesta aquí" disabled>'
                . $ta_val . '</textarea>';
            if ($eval_score >= 0) {
                $pct = round($eval_score * 100);
                $badge_cls = ($eval_score >= 0.6) ? 'bg-success' : (($eval_score > 0) ? 'bg-warning text-dark' : 'bg-danger');
                echo '<div class="mt-2 d-flex align-items-center gap-2">';
                echo '<span class="badge ' . $badge_cls . '">IA: ' . $pct . '%</span>';
                if (!empty($ai_feedback)) {
                    echo '<small class="text-muted">' . htmlspecialchars($ai_feedback) . '</small>';
                }
                echo '</div>';
            }
        } else {
            echo '<textarea class="form-control evalia-answer" '
                . 'name="answer_' . $q['id'] . '" data-qid="' . $q['id'] . '" '
                . 'rows="5" placeholder="Redactá tu respuesta aquí"' . $disabled . '>'
                . $ta_val . '</textarea>';
        }
    } else {
        // shortanswer
        $val        = $stored_answer !== null ? ' value="' . htmlspecialchars($stored_answer) . '"' : '';
        $q_correct  = $graded_preview && ($correctness_map[$q['id']] ?? false);
        $inp_border = $graded_preview ? (' border-' . ($q_correct ? 'success' : 'danger')) : '';
        echo '<div class="d-flex align-items-center gap-2">';
        echo '<input type="text" class="form-control evalia-answer' . $inp_border . '" '
            . 'name="answer_' . $q['id'] . '" data-qid="' . $q['id'] . '" '
            . 'placeholder="Escribí tu respuesta"' . $val . $disabled . '>';
        if ($graded_preview) {
            echo $q_correct ? '<span class="text-success fw-bold fs-5">✅</span>' : '<span class="text-danger fw-bold fs-5">❌</span>';
        }
        echo '</div>';
        if ($graded_preview && !$q_correct) {
            echo '<small class="text-muted mt-1 d-block">Respuesta correcta: <strong>'
                . htmlspecialchars($q['correct_answer']) . '</strong></small>';
        }
    }

    echo '</div>';  // card-body
    echo '</div>';  // card
}

if ($preview_mode) {
    echo '</div>';
    // Grade panel: only for submitted status (graded already shows score above).
    if ($student_exam->status === 'submitted') {
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
