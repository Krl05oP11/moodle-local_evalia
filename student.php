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
 * EVAL-IA Student Panel — lists assigned exams for the logged-in student.
 *
 * URL: /local/evalia/student.php?courseid=X
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);

$course  = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/evalia:take', $context);

$PAGE->set_context($context);
$PAGE->set_url('/local/evalia/student.php', ['courseid' => $courseid]);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title('Mis Exámenes — EVAL-IA');
$PAGE->set_heading(format_string($course->fullname));

$now = time();

$myexams = $DB->get_records_sql(
    'SELECT se.id         AS student_examid,
            se.status,
            se.score,
            se.timesubmitted,
            e.id          AS examid,
            e.name,
            e.time_limit_min,
            e.timeopen,
            e.timeclose,
            e.instructions
       FROM {evalia_student_exams} se
       JOIN {evalia_exams} e ON e.id = se.examid
      WHERE se.userid   = :userid
        AND e.courseid  = :courseid
      ORDER BY e.timecreated DESC',
    ['userid' => $USER->id, 'courseid' => $courseid]
);

// ── Helpers ──────────────────────────────────────────────────────────────────

/**
 * Returns true if the exam window is currently open for the student to take.
 * Always returns true when there is no window configured.
 */
function evalia_window_open(object $ex, int $now): bool {
    if ($ex->timeopen > 0 && $now < $ex->timeopen) {
        return false;
    }
    if ($ex->timeclose > 0 && $now > $ex->timeclose) {
        return false;
    }
    return true;
}

/**
 * Returns a human-readable window note, or '' if within (or without) window.
 */
function evalia_window_note(object $ex, int $now): string {
    if ($ex->timeopen > 0 && $now < $ex->timeopen) {
        return '🕐 Disponible a partir del ' .
            userdate($ex->timeopen, get_string('strftimedatetimeshort', 'langconfig'));
    }
    if ($ex->timeclose > 0 && $now > $ex->timeclose) {
        return '🔒 El período de entrega cerró el ' .
            userdate($ex->timeclose, get_string('strftimedatetimeshort', 'langconfig'));
    }
    return '';
}

// ─────────────────────────────────────────────────────────────────────────────

echo $OUTPUT->header();

// This is a mixed HTML/PHP template. MissingDocblock.File re-fires on every
// reopened PHP tag (the file docblock is present above); ScopeIndent expects PHP
// scope indentation inside markup that is indented as HTML.
// phpcs:disable moodle.Commenting.MissingDocblock.File, Generic.WhiteSpace.ScopeIndent
?>
<div class="container-fluid mt-4" style="max-width:800px;">

    <div class="d-flex align-items-baseline gap-3 mb-4">
        <h2 class="mb-0">📝 Mis Exámenes</h2>
        <span class="text-muted"><?php echo format_string($course->fullname); ?></span>
    </div>

<?php if (empty($myexams)) : ?>
    <div class="alert alert-info d-flex align-items-center gap-3">
        <span style="font-size:1.6rem;">📭</span>
        <div>
            <strong>No tenés exámenes asignados todavía.</strong><br>
            <small>El docente te notificará cuando haya un examen disponible para este curso.</small>
        </div>
    </div>

<?php else : ?>
<?php foreach ($myexams as $ex) :
    $windowopen = evalia_window_open($ex, $now);
    $windownote = evalia_window_note($ex, $now);

    // Decide card appearance and CTA per status.
    $alreadyclosed = ($ex->timeclose > 0 && $now > $ex->timeclose);
    $detailurl = (new moodle_url(
        '/local/evalia/student_exam.php',
        ['student_examid' => $ex->student_examid]
    ))->out(false);

    $showbtn      = false;
    $btnlabel     = '';
    $btncls       = '';
    $statecontent = '';

    switch ($ex->status) {
        case 'assigned':
            $bordercls  = $windowopen ? 'border-primary' : 'border-secondary';
            $statushtml = '<span class="badge bg-primary">Pendiente</span>';
            $showbtn    = $windowopen;
            $btnlabel   = '📝 Rendir →';
            $btncls     = 'btn-primary';
            break;
        case 'started':
            $bordercls  = $windowopen ? 'border-warning' : 'border-secondary';
            $statushtml = '<span class="badge bg-warning text-dark">En progreso</span>';
            $showbtn    = $windowopen;
            $btnlabel   = '▶️ Continuar →';
            $btncls     = 'btn-warning text-dark';
            break;
        case 'submitted':
            $bordercls    = 'border-secondary';
            $statushtml   = '<span class="badge bg-secondary">Enviado</span>';
            $submittedon  = $ex->timesubmitted
                ? userdate($ex->timesubmitted, get_string('strftimedatetimeshort', 'langconfig'))
                : '—';
            $statecontent = '<p class="text-muted small mb-0">Examen enviado el ' . $submittedon
                . '. El docente lo revisará pronto.</p>';
            break;
        case 'graded':
            $bordercls    = 'border-info';
            $statushtml   = '<span class="badge bg-info text-dark">Calificado</span>';
            $statecontent = '<p class="text-muted small mb-0">Tu examen ya fue calificado. '
                . 'La nota estará disponible en cuanto el docente la publique.</p>';
            break;
        case 'published':
            $bordercls    = 'border-success';
            $statushtml   = '<span class="badge bg-success">✅ Publicado</span>';
            $statecontent = '<div class="d-flex align-items-center gap-3 mt-1">'
                . '<span class="fs-3 fw-bold text-success">' . number_format((float) $ex->score, 1) . ' / 10.0</span>'
                . '<a href="' . $detailurl . '" class="btn btn-sm btn-outline-success">Ver detalle →</a></div>';
            break;
        default:
            $bordercls  = '';
            $statushtml = '<span class="badge bg-light text-dark border">' . htmlspecialchars($ex->status) . '</span>';
    }

    // Assigned/started with a closed or not-yet-open window: show the window note.
    if ($statecontent === '' && !$windowopen && $windownote !== '') {
        $statecontent = '<p class="text-danger small mb-0">' . htmlspecialchars($windownote) . '</p>';
    }
?>
    <div class="card <?php echo $bordercls; ?> mb-3 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <span class="fw-bold"><?php echo htmlspecialchars($ex->name); ?></span>
            <?php echo $statushtml; ?>
        </div>
        <div class="card-body py-3">

            <?php // ── Meta info row ────────────────────────────────────── ?>
            <div class="d-flex flex-wrap gap-3 mb-2 text-muted small">
                <?php if ($ex->time_limit_min > 0) : ?>
                    <span>⏱ Tiempo límite: <strong><?php echo (int) $ex->time_limit_min; ?> min</strong></span>
                <?php endif; ?>
                <?php if ($ex->timeopen > 0) : ?>
                    <span>📅 Desde: <?php echo userdate($ex->timeopen, get_string('strftimedatetimeshort', 'langconfig')); ?></span>
                <?php endif; ?>
                <?php if ($ex->timeclose > 0) : ?>
                    <span>⏰ Hasta: <?php echo userdate($ex->timeclose, get_string('strftimedatetimeshort', 'langconfig')); ?></span>
                <?php endif; ?>
            </div>

            <?php // ── State-specific content ────────────────────────────── ?>
            <?php echo $statecontent; ?>

            <?php // ── CTA button ─────────────────────────────────────────── ?>
            <?php if ($showbtn) : ?>
                <div class="mt-3">
                    <a href="<?php echo $detailurl; ?>"
                       class="btn <?php echo $btncls; ?> px-4">
                        <?php echo $btnlabel; ?>
                    </a>
                </div>
            <?php endif; ?>

        </div><!-- card-body -->
    </div><!-- card -->
<?php endforeach; ?>
<?php endif; ?>

</div>

<?php
// phpcs:enable moodle.Commenting.MissingDocblock.File, Generic.WhiteSpace.ScopeIndent
echo $OUTPUT->footer();
