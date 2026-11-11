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
 * Renders the student's assigned questions and handles submission via AMD AJAX.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$studentexamid = required_param('student_examid', PARAM_INT);

$studentexam = $DB->get_record('local_evalia_student_exams', ['id' => $studentexamid], '*', MUST_EXIST);
$exam         = $DB->get_record('local_evalia_exams', ['id' => $studentexam->examid], '*', MUST_EXIST);
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
$PAGE->set_title(get_string('exam:page_title', 'local_evalia', $exam->name));
$PAGE->set_heading($exam->name);

// Outside the exam window the student cannot open (or start) the exam: back to the exam list with the
// reason. get_student_exam and submit_exam enforce the same window.
if ($isowner && in_array($studentexam->status, ['assigned', 'started'])) {
    $now = time();
    $notyet = $exam->timeopen > 0 && $now < $exam->timeopen;
    $closed = $exam->timeclose > 0 && $now > $exam->timeclose;
    if ($notyet || $closed) {
        $dateformat = get_string('strftimedatetimeshort', 'langconfig');
        $message = $notyet
            ? get_string('student:window_opens', 'local_evalia', userdate($exam->timeopen, $dateformat))
            : get_string('student:window_closed', 'local_evalia', userdate($exam->timeclose, $dateformat));
        redirect(
            new moodle_url('/local/evalia/student.php', ['courseid' => $course->id]),
            $message,
            null,
            \core\output\notification::NOTIFY_WARNING
        );
    }
}

// Load questions directly from DB (no WS overhead for server-side rendering).
$questionids = json_decode($studentexam->question_ids ?? '[]', true);

$questionsdata = [];
if (!empty($questionids)) {
    [$insql, $inparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid');
    $questions = $DB->get_records_select(
        'local_evalia_question_bank',
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
            'local_evalia_question_options',
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
    $DB->update_record('local_evalia_student_exams', (object) [
        'id'           => $studentexam->id,
        'status'       => 'started',
        'timemodified' => time(),
    ]);
    $studentexam->status = 'started';
}

$alreadysubmitted = in_array($studentexam->status, ['submitted', 'graded', 'published']);
$previewmode      = $isteacher && !$isowner;  // Teacher viewing someone else's exam.

// Pre-load stored answers (needed for correctness maps and later rendering).
$storedanswers = [];
if ($previewmode && $alreadysubmitted) {
    $storedanswers = json_decode($studentexam->answers ?? '{}', true) ?? [];
}

// Correctness maps for teacher preview of graded exams.
$correctoptionmap = [];   // Qid → correct option_text (multichoice / truefalse).
$correctnessmap    = [];   // Qid → bool.
$essayevals        = [];   // Qid → ['score' => float, 'feedback' => string].
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

// Context of templates/exam_page.mustache. Every value is escaped there except the instructions and the
// question stems, which are the output of format_text().
$gradedpreview = $previewmode && in_array($studentexam->status, ['graded', 'published']);
$templatecontext = [
    'preview'        => $previewmode,
    'studentexamid'  => $studentexamid,
    'submittedview'  => $alreadysubmitted && !$previewmode,
    // The student sees the grade once the teacher publishes it, as on student.php.
    'submittedscore' => $studentexam->status === 'published'
        ? number_format((float) $studentexam->score, 1) : '',
    'gradedpreview'  => $gradedpreview,
    'previewscore'   => $gradedpreview ? number_format((float) $studentexam->score, 1) : '',
    'showtimer'      => !$previewmode && $exam->time_limit_min > 0,
    'instructions'   => !empty($exam->instructions) ? format_text($exam->instructions, FORMAT_HTML) : '',
    'showgradepanel' => $previewmode && $studentexam->status === 'submitted',
    'questions'      => [],
];
if ($previewmode) {
    $templatecontext['studentname'] = $studentuser ? fullname($studentuser) : get_string('exam:preview_student_fallback', 'local_evalia');
    $templatecontext['statuslabel'] = [
        'assigned'  => get_string('exam:status_assigned', 'local_evalia'),
        'started'   => get_string('exam:status_started', 'local_evalia'),
        'submitted' => get_string('exam:status_submitted_review', 'local_evalia'),
        'graded'    => get_string('exam:status_graded', 'local_evalia'),
        'published' => get_string('student:status_published', 'local_evalia'),
    ][$studentexam->status] ?? $studentexam->status;
}

$diffweights = ['basic' => 1, 'medium' => 2, 'advanced' => 3];
$diffmap = ['basic' => 'info', 'medium' => 'warning', 'advanced' => 'danger'];
foreach ($templatecontext['submittedview'] ? [] : $questionsdata as $q) {
    $qid = $q['id'];
    $qweight = $diffweights[$q['difficulty']] ?? 1;
    $storedanswer = $storedanswers[(string) $qid] ?? null;
    $qcorrect = $gradedpreview && ($correctnessmap[$qid] ?? false);

    $item = [
        'id'          => $qid,
        'num'         => $q['num'],
        'topic'       => $q['topic'],
        'cardborder'  => $gradedpreview ? (($correctnessmap[$qid] ?? false) ? 'border-success' : 'border-danger') : '',
        'diffclass'   => 'bg-' . ($diffmap[$q['difficulty']] ?? 'secondary'),
        'difflabel'   => isset($diffmap[$q['difficulty']])
            ? get_string('difficulty_' . $q['difficulty'], 'local_evalia') : $q['difficulty'],
        'stem'        => $q['stem'],
        'disabled'    => $previewmode,
        'choice'      => in_array($q['question_type'], ['multichoice', 'truefalse']),
        'numerical'   => $q['question_type'] === 'numerical',
        'essay'       => $q['question_type'] === 'essay',
        'shortanswer' => !in_array($q['question_type'], ['multichoice', 'truefalse', 'numerical', 'essay']),
        'value'       => $storedanswer ?? '',
        'graded'      => $gradedpreview,
        'correct'     => $qcorrect,
        'inputborder' => $gradedpreview ? ($qcorrect ? 'border-success' : 'border-danger') : '',
        'showcorrect' => $gradedpreview && !$qcorrect,
        'correctanswer' => $q['correct_answer'],
        'tolerance'   => (!empty($q['tolerance']) && $q['tolerance'] > 0) ? '(±' . $q['tolerance'] . ')' : '',
        'options'     => [],
    ];

    // Points badge: plain in the active exam, points obtained in the graded preview.
    if ($gradedpreview) {
        if ($q['question_type'] === 'essay') {
            $evalscore = (float) ($essayevals[(string) $qid]['score'] ?? 0.0);
            $obtained = number_format($evalscore * $qweight, 1);
            $item['ptsclass'] = ($evalscore >= 0.6) ? 'bg-success' : (($evalscore > 0) ? 'bg-warning text-dark' : 'bg-danger');
        } else {
            $obtained = $qcorrect ? $qweight : 0;
            $item['ptsclass'] = $qcorrect ? 'bg-success' : 'bg-danger';
        }
        $item['ptstext'] = get_string('exam:points_of', 'local_evalia', (object) ['obtained' => $obtained, 'total' => $qweight]);
    } else {
        $item['ptsclass'] = 'bg-secondary';
        $item['ptstext'] = get_string('exam:points_plain', 'local_evalia', $qweight);
    }

    if ($item['choice']) {
        foreach ($q['options'] as $opt) {
            $isselected = ($storedanswer !== null && $storedanswer === $opt->option_text);
            $iscorrect = ($gradedpreview && (int) $opt->is_correct === 1);
            if ($gradedpreview) {
                $labelclass = $iscorrect ? 'fw-bold text-success' : ($isselected ? 'text-danger text-decoration-line-through' : '');
                $indicator = $iscorrect ? '✅' : ($isselected ? '❌' : '');
            } else {
                $labelclass = $isselected ? 'fw-bold text-primary' : '';
                $indicator = '';
            }
            $item['options'][] = [
                'optid'      => 'q' . $qid . '_opt' . $opt->id,
                'value'      => $opt->option_text,
                'checked'    => $isselected,
                'labelclass' => $labelclass,
                'indicator'  => $indicator,
            ];
        }
    }

    if ($item['essay'] && $gradedpreview) {
        $evalscore = (float) ($essayevals[(string) $qid]['score'] ?? -1.0);
        $item['essayborder'] = ($evalscore < 0)
            ? '' : (($evalscore >= 0.6) ? 'border-success' : (($evalscore > 0) ? 'border-warning' : 'border-danger'));
        $item['essayscored'] = $evalscore >= 0;
        $item['essaybadge'] = ($evalscore >= 0.6) ? 'bg-success' : (($evalscore > 0) ? 'bg-warning text-dark' : 'bg-danger');
        $item['essayscore'] = $evalscore >= 0 ? get_string('exam:ai_score_label', 'local_evalia', round($evalscore * 100)) : '';
        $item['essayfeedback'] = $essayevals[(string) $qid]['feedback'] ?? '';
    }

    $templatecontext['questions'][] = $item;
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_evalia/exam_page', $templatecontext);
echo $OUTPUT->footer();
