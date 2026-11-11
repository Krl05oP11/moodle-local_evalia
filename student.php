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
$PAGE->set_title(get_string('student:page_title', 'local_evalia'));
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
       FROM {local_evalia_student_exams} se
       JOIN {local_evalia_exams} e ON e.id = se.examid
      WHERE se.userid   = :userid
        AND e.courseid  = :courseid
      ORDER BY e.timecreated DESC',
    ['userid' => $USER->id, 'courseid' => $courseid]
);

// Helpers.

/**
 * Returns true if the exam window is currently open for the student to take.
 * Always returns true when there is no window configured.
 *
 * @param object $ex Exam record (uses timeopen and timeclose).
 * @param int $now Current timestamp.
 */
function local_evalia_window_open(object $ex, int $now): bool {
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
 *
 * @param object $ex Exam record (uses timeopen and timeclose).
 * @param int $now Current timestamp.
 */
function local_evalia_window_note(object $ex, int $now): string {
    $dateformat = get_string('strftimedatetimeshort', 'langconfig');
    if ($ex->timeopen > 0 && $now < $ex->timeopen) {
        return get_string('student:window_opens', 'local_evalia', userdate($ex->timeopen, $dateformat));
    }
    if ($ex->timeclose > 0 && $now > $ex->timeclose) {
        return get_string('student:window_closed', 'local_evalia', userdate($ex->timeclose, $dateformat));
    }
    return '';
}


// Context of templates/student_dashboard.mustache; every value is escaped there except coursename,
// which is the output of format_string().
$dateformat = get_string('strftimedatetimeshort', 'langconfig');
$statusmap = [
    'assigned'  => ['bg-primary', 'student:status_assigned'],
    'started'   => ['bg-warning text-dark', 'student:status_started'],
    'submitted' => ['bg-secondary', 'student:status_submitted'],
    'graded'    => ['bg-info text-dark', 'student:status_graded'],
    'published' => ['bg-success', 'student:status_published'],
];
$cards = [];
foreach ($myexams as $ex) {
    $windowopen = local_evalia_window_open($ex, $now);
    $windownote = local_evalia_window_note($ex, $now);
    $detailurl = (new moodle_url('/local/evalia/student_exam.php', ['student_examid' => $ex->student_examid]))->out(false);
    [$statusclass, $statuskey] = $statusmap[$ex->status] ?? ['bg-light text-dark border', ''];

    $card = [
        'name'          => $ex->name,
        'statusclass'   => $statusclass,
        'statuslabel'   => $statuskey ? get_string($statuskey, 'local_evalia') : $ex->status,
        'bordercls'     => '',
        'timelimit'     => $ex->time_limit_min > 0
            ? get_string('student:meta_timelimit', 'local_evalia', (int) $ex->time_limit_min) : '',
        'from'          => $ex->timeopen > 0
            ? get_string('student:meta_from', 'local_evalia', userdate($ex->timeopen, $dateformat)) : '',
        'until'         => $ex->timeclose > 0
            ? get_string('student:meta_until', 'local_evalia', userdate($ex->timeclose, $dateformat)) : '',
        'submitted'     => '',
        'graded'        => false,
        'published'     => false,
        'score'         => '',
        'windownote'    => '',
        'showbtn'       => false,
        'btnlabel'      => '',
        'btncls'        => '',
        'detailurl'     => $detailurl,
    ];
    switch ($ex->status) {
        case 'assigned':
            $card['bordercls'] = $windowopen ? 'border-primary' : 'border-secondary';
            $card['showbtn']   = $windowopen;
            $card['btnlabel']  = get_string('student:btn_take', 'local_evalia');
            $card['btncls']    = 'btn-primary';
            break;
        case 'started':
            $card['bordercls'] = $windowopen ? 'border-warning' : 'border-secondary';
            $card['showbtn']   = $windowopen;
            $card['btnlabel']  = get_string('student:btn_continue', 'local_evalia');
            $card['btncls']    = 'btn-warning text-dark';
            break;
        case 'submitted':
            $card['bordercls'] = 'border-secondary';
            $card['submitted'] = get_string(
                'student:msg_submitted_on',
                'local_evalia',
                $ex->timesubmitted ? userdate($ex->timesubmitted, $dateformat) : '—'
            );
            break;
        case 'graded':
            $card['bordercls'] = 'border-info';
            $card['graded']    = true;
            break;
        case 'published':
            $card['bordercls'] = 'border-success';
            $card['published'] = true;
            $card['score']     = number_format((float) $ex->score, 1);
            break;
    }
    // Assigned/started with a closed or not-yet-open window: show the window note.
    if (in_array($ex->status, ['assigned', 'started']) && !$windowopen && $windownote !== '') {
        $card['windownote'] = $windownote;
    }
    $cards[] = $card;
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_evalia/student_dashboard', [
    'coursename' => format_string($course->fullname, true, ['context' => context_course::instance($courseid)]),
    'hasexams'   => !empty($cards),
    'exams'      => $cards,
]);
echo $OUTPUT->footer();
