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
 * EVAL-IA Portfolio CSV export.
 *
 * URL: /local/evalia/export_portfolio.php?courseid=X
 * Direct download — no AJAX. Outputs UTF-8 BOM CSV for Excel compatibility.
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
require_capability('local/evalia:manage', $context);

// Load enrolled students.
$students = get_enrolled_users(
    $context,
    'local/evalia:take',
    0,
    'u.id, u.firstname, u.lastname, u.email'
);

// Load portfolio records in one query.
$portfoliobyuser = [];
if (!empty($students)) {
    $studentids = array_keys($students);
    [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'uid');
    $portfolios = $DB->get_records_select(
        'evalia_portfolio',
        "userid $insql AND courseid = :courseid",
        array_merge($inparams, ['courseid' => $courseid]),
        '',
        'userid, total_exams, avg_grade, last_activity'
    );
    foreach ($portfolios as $p) {
        $portfoliobyuser[$p->userid] = $p;
    }
}

// Load the most recent docente note per student.
$lastnotebyuser = [];
if (!empty($students)) {
    $studentids = array_keys($students);
    [$insql, $inparams] = $DB->get_in_or_equal($studentids, SQL_PARAMS_NAMED, 'nuid');
    $notes = $DB->get_records_select(
        'evalia_portfolio_notes',
        "userid $insql AND courseid = :courseid",
        array_merge($inparams, ['courseid' => $courseid]),
        'timecreated DESC',
        'userid, note_text, timecreated'
    );
    foreach ($notes as $n) {
        // get_records_select returns first match; since we order DESC the first = most recent.
        if (!isset($lastnotebyuser[$n->userid])) {
            $lastnotebyuser[$n->userid] = $n->note_text;
        }
    }
}

// ── Emit CSV ─────────────────────────────────────────────────────────────────
$safecoursename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', format_string($course->shortname));
$filename        = 'evalia_legajos_' . $safecoursename . '_' . date('Ymd') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

$out = fopen('php://output', 'w');

// UTF-8 BOM — required for correct encoding in Excel on Windows.
fwrite($out, "\xEF\xBB\xBF");

// Header row.
fputcsv($out, [
    'Apellido y Nombre',
    'Email',
    'Exámenes Tomados',
    'Nota Promedio (0-10)',
    'Última Actividad',
    'Última Observación del Docente',
]);

// Data rows — sorted alphabetically.
$studentlist = array_values((array) $students);
usort($studentlist, function ($a, $b) {
    return strcmp($a->lastname . ' ' . $a->firstname, $b->lastname . ' ' . $b->firstname);
});

foreach ($studentlist as $u) {
    $p             = $portfoliobyuser[$u->id] ?? null;
    $totalexams   = $p ? (int) $p->total_exams : 0;
    $avggrade     = $p ? number_format((float) $p->avg_grade, 2, '.', '') : '';
    $lastactivity = ($p && $p->last_activity > 0)
        ? date('d/m/Y H:i', $p->last_activity)
        : '';
    $lastnote     = $lastnotebyuser[$u->id] ?? '';

    fputcsv($out, [
        $u->lastname . ', ' . $u->firstname,
        $u->email,
        $totalexams,
        $avggrade,
        $lastactivity,
        $lastnote,
    ]);
}

fclose($out);
exit;
