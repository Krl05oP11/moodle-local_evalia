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
 * Library functions for local_evalia.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds EVAL-IA links to the course navigation.
 *
 * Teachers (manage): link to the teacher panel.
 * Students (take):   link to their own exam list.
 */
function local_evalia_extend_navigation_course(
    \navigation_node $parentnode,
    \stdClass $course,
    \context_course $context
): void {
    if (has_capability('local/evalia:manage', $context)) {
        $url = new \moodle_url('/local/evalia/teacher.php', ['courseid' => $course->id]);
        $parentnode->add(
            get_string('pluginname', 'local_evalia'),
            $url,
            \navigation_node::TYPE_SETTING,
            null,
            'local_evalia_teacher',
            new \pix_icon('i/grades', '')
        );
    } else if (has_capability('local/evalia:take', $context)) {
        $url = new \moodle_url('/local/evalia/student.php', ['courseid' => $course->id]);
        $parentnode->add(
            get_string('nav_my_exams', 'local_evalia'),
            $url,
            \navigation_node::TYPE_SETTING,
            null,
            'local_evalia_student',
            new \pix_icon('i/grades', '')
        );
    }
}

/**
 * Create a Moodle Calendar course event for a scheduled exam.
 *
 * Only creates the event when timeopen > 0. Skips silently if the event
 * already exists (safe to call multiple times).
 *
 * @param  \stdClass         $exam  Row from evalia_exams (must include timeopen, timeclose, time_limit_min)
 * @param  \moodle_database  $DB
 * @return int  calendar event ID (0 if not created)
 */
function local_evalia_create_exam_calendar_event(\stdClass $exam, \moodle_database $DB): int {
    global $CFG;

    if ((int) $exam->timeopen <= 0) {
        return 0;   // No scheduled window → nothing to put in the calendar.
    }

    require_once($CFG->dirroot . '/calendar/lib.php');

    // Avoid duplicates: check if an event for this exam already exists.
    $existing = $DB->get_record('event', [
        'courseid'  => (int) $exam->courseid,
        'eventtype' => 'course',
        'component' => 'local_evalia',
        'instance'  => (int) $exam->id,
    ]);
    if ($existing) {
        return (int) $existing->id;
    }

    // Duration: prefer the explicit window; fall back to time_limit_min.
    $duration = 0;
    if ((int) $exam->timeclose > (int) $exam->timeopen) {
        $duration = (int) $exam->timeclose - (int) $exam->timeopen;
    } else if ((int) $exam->time_limit_min > 0) {
        $duration = (int) $exam->time_limit_min * 60;
    }

    $eventdata = (object) [
        'name'         => get_string('calendar_exam_event', 'local_evalia', $exam->name),
        'description'  => strip_tags($exam->instructions ?? ''),
        'format'       => FORMAT_PLAIN,
        'courseid'     => (int) $exam->courseid,
        'groupid'      => 0,
        'userid'       => 0,
        'modulename'   => '',
        'instance'     => (int) $exam->id,
        'component'    => 'local_evalia',
        'eventtype'    => 'course',
        'timestart'    => (int) $exam->timeopen,
        'timeduration' => $duration,
        'timesort'     => (int) $exam->timeopen,
        'visible'      => 1,
        'timemodified' => time(),
    ];

    try {
        $event = \calendar_event::create($eventdata, false);
        return $event ? (int) $event->id : 0;
    } catch (\Throwable $e) {
        debugging('local_evalia: could not create calendar event for exam ' . $exam->id . ': ' . $e->getMessage());
        return 0;
    }
}

/**
 * Create or update a Moodle gradebook item for an EVAL-IA exam.
 *
 * Creates a manual grade item (idnumber = evalia_exam_{examid}) the first time.
 * Optionally records a student grade when $userid && $grade are provided.
 *
 * @param int        $examid    Row ID from evalia_exams
 * @param int        $courseid
 * @param string     $examname  Label shown in the gradebook column
 * @param int|null   $userid    If set, also push this student's grade
 * @param float|null $grade     Score 0–10; required when $userid is set
 * @return int  grade_items.id (0 on failure)
 */
function local_evalia_grade_item_update(
    int $examid,
    int $courseid,
    string $examname,
    ?int $userid = null,
    ?float $grade = null
): int {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $idnumber = 'evalia_exam_' . $examid;

    // Find or create the grade item.
    $gi = grade_item::fetch(['courseid' => $courseid, 'idnumber' => $idnumber]);

    if (!$gi) {
        $gi              = new grade_item(null, false);
        $gi->courseid    = $courseid;
        $gi->itemtype    = 'manual';
        $gi->itemname    = $examname;
        $gi->idnumber    = $idnumber;
        $gi->grademax    = 10.0;
        $gi->grademin    = 0.0;
        $gi->needsupdate = 0;
        $gi->insert('local/evalia');
    } else if ($gi->itemname !== $examname) {
        $gi->itemname = $examname;
        $gi->update('local/evalia');
    }

    if ($userid !== null && $grade !== null) {
        $raw = (float) max(0.0, min(10.0, $grade));
        $gg  = grade_grade::fetch(['itemid' => $gi->id, 'userid' => $userid]);
        if ($gg) {
            $gg->rawgrade   = $raw;
            $gg->finalgrade = $raw;
            $gg->update('local/evalia');
        } else {
            $gg = new grade_grade(['itemid' => $gi->id, 'userid' => $userid], false);
            $gg->rawgrade   = $raw;
            $gg->finalgrade = $raw;
            $gg->insert('local/evalia');
        }
    }

    return (int) $gi->id;
}

/**
 * Build the per-question breakdown for a student exam && send pedagogical
 * feedback to the student via Telegram (non-blocking — engine queues it).
 *
 * Called from publish_grade / publish_all_grades AFTER teacher approval.
 * Correctness is recomputed from stored answers since it is not persisted separately.
 *
 * @param  int               $studentexamid  evalia_student_exams.id
 * @param  \moodle_database  $DB
 * @return string  'sent' | 'failed' | 'skipped' | 'disabled'
 */
function local_evalia_send_feedback(int $studentexamid, \moodle_database $DB): string {
    if (get_config('local_evalia', 'feedback_telegram') === '0') {
        return 'disabled';
    }

    try {
        $se   = $DB->get_record('evalia_student_exams', ['id' => $studentexamid], '*', MUST_EXIST);
        $exam = $DB->get_record('evalia_exams', ['id' => $se->examid], '*', MUST_EXIST);

        // Telegram chat_id for this student (may be 0 → engine will skip send).
        $tglink = $DB->get_record('saipa_telegram_links', ['userid' => $se->userid], 'telegram_id');
        $tgid   = ($tglink && !empty($tglink->telegram_id)) ? (int) $tglink->telegram_id : 0;

        $course  = $DB->get_record('course', ['id' => $exam->courseid], 'fullname');
        $student = $DB->get_record('user', ['id' => $se->userid], 'id, firstname, lastname');

        // ── Reconstruct per-question feedback data from stored answers ────────
        $questionids = json_decode($se->question_ids ?? '[]', true);
        $answersmap  = json_decode($se->answers ?? '{}', true);

        if (empty($questionids)) {
            return 'skipped';
        }

        [$insql, $inparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid');
        $questions = $DB->get_records_select(
            'evalia_question_bank',
            "id $insql",
            $inparams,
            '',
            'id, question_type, correct_answer, tolerance, stem, topic'
        );

        // Load correct option texts (multichoice / truefalse).
        $correctopts = [];
        if (!empty($questions)) {
            [$optsql, $optparams] = $DB->get_in_or_equal(array_keys($questions), SQL_PARAMS_NAMED, 'oqid');
            $opts = $DB->get_records_select(
                'evalia_question_options',
                "questionid $optsql AND is_correct = 1",
                $optparams,
                '',
                'questionid, option_text'
            );
            foreach ($opts as $opt) {
                $correctopts[$opt->questionid] = $opt->option_text;
            }
        }

        // Essay evaluations stored inside the answers JSON under __essay_eval__.
        $essayevals = (array) ($answersmap['__essay_eval__'] ?? []);

        // Build questions array with correctness for each.
        $questionsfb = [];
        foreach ($questionids as $qid) {
            $q = $questions[$qid] ?? null;
            if (!$q) {
                continue;
            }

            $studentans  = strtolower(trim((string) ($answersmap[(string)$qid] ?? '')));
            $correcttext = in_array($q->question_type, ['multichoice', 'truefalse'])
                ? ($correctopts[$qid] ?? '')
                : ($q->correct_answer ?? '');

            $aifeedback = '';
            if ($q->question_type === 'essay') {
                $eval       = $essayevals[(string)$qid] ?? [];
                $evalscore = (float) ($eval['score'] ?? 0.0);
                $iscorrect = ($evalscore >= 0.6);
                $aifeedback = $eval['feedback'] ?? '';
            } else if (in_array($q->question_type, ['multichoice', 'truefalse'])) {
                $iscorrect = ($studentans === strtolower(trim($correcttext)));
            } else if ($q->question_type === 'numerical') {
                $iscorrect = (abs(floatval($studentans) - floatval($q->correct_answer ?? 0))
                               <= (float) ($q->tolerance ?? 0));
            } else {
                $iscorrect = ($studentans === strtolower(trim($q->correct_answer ?? '')));
            }

            $questionsfb[] = [
                'question_id'    => (int) $qid,
                'stem'           => format_text($q->stem, FORMAT_PLAIN),
                'question_type'  => $q->question_type,
                'student_answer' => (string) ($answersmap[(string)$qid] ?? ''),
                'correct_answer' => $correcttext,
                'correct'        => $iscorrect,
                'topic'          => $q->topic ?? '',
                'ai_feedback'    => $aifeedback,
            ];
        }

        // Call engine — returns immediately, LLM runs as background task.
        $fbresp = local_evalia_engine_request('/feedback/send', [
            'telegram_id'     => $tgid,
            'userid'          => (int) $se->userid,
            'examid'          => (int) $se->id,
            'score'           => (float) $se->score,
            'max_score'       => 10.0,
            'course_name'     => $course ? format_string($course->fullname) : '',
            'course_id'       => (int) $exam->courseid,
            'student_name'    => $student ? fullname($student) : '',
            'questions'       => $questionsfb,
            'feedback_prompt' => $exam->feedback_prompt ?? '',
        ], 8);

        $status = (!isset($fbresp['error']) && !empty($fbresp['sent'])) ? 'sent' : 'failed';
    } catch (\Throwable $e) {
        $status = 'failed';
    }

    // Log attempt.
    try {
        $DB->insert_record('evalia_feedback_log', (object) [
            'userid'       => $se->userid ?? 0,
            'examid'       => $se->id ?? 0,
            'channel'      => 'telegram',
            'message_text' => 'score=' . ($se->score ?? 0) . '/10 status=' . $status,
            'timesent'     => time(),
            'status'       => ($status === 'sent') ? 'sent' : 'failed',
        ]);
    } catch (\Throwable $e) {
        debugging('evalia feedback log error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    return $status;
}

/**
 * Low-level HTTP client for the SAIPA engine.
 *
 * Reads engine_url / engine_token from local_evalia's own admin settings.
 * Falls back to local_saipa settings when running in a joint deployment && * local_evalia's engine_url has not been explicitly configured.
 *
 * @param  string     $endpoint  Full path, e.g. '/index' or '/eval/rubric/generate'
 * @param  array|null $data      POST body as associative array; null = GET request
 * @param  int        $timeout   Seconds before giving up
 * @return array                 Decoded JSON response or ['error' => message]
 */
function local_evalia_raw_engine_request(string $endpoint, ?array $data = null, int $timeout = 60): array {
    // Own settings take priority; fall back to local_saipa when absent.
    $engineurl = get_config('local_evalia', 'engine_url');
    $token      = get_config('local_evalia', 'engine_token');

    if (empty($engineurl)) {
        $engineurl = get_config('local_saipa', 'engine_url');
        if (empty($token)) {
            $token = get_config('local_saipa', 'engine_token');
        }
    }

    if (empty($engineurl)) {
        return ['error' => 'EVAL-IA engine URL not configured. '
            . 'Set it under Site administration → Plugins → Local plugins → EVAL-IA.'];
    }

    $url = rtrim($engineurl, '/') . $endpoint;

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . ($token ?? ''),
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_HTTPHEADER     => $headers,
    ]);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $responsestr = curl_exec($ch);
    $errno        = curl_errno($ch);
    $error        = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return ['error' => 'cURL error (' . $errno . '): ' . $error];
    }

    if ($responsestr === false || $responsestr === '') {
        return ['error' => 'Empty response from engine'];
    }

    $decoded = json_decode($responsestr, true);
    if ($decoded === null) {
        return ['error' => 'Invalid JSON from engine: ' . substr($responsestr, 0, 300)];
    }

    return $decoded;
}

/**
 * Convenience wrapper: calls saipa-engine under the /eval prefix.
 *
 * @param  string     $endpoint  Path relative to /eval, e.g. '/rubric/generate'
 * @param  array|null $data      POST body as associative array; null = GET request
 * @param  int        $timeout   Seconds before giving up (generation can be slow)
 * @return array                 Decoded JSON response or ['error' => message]
 */
function local_evalia_engine_request(string $endpoint, ?array $data = null, int $timeout = 60): array {
    return local_evalia_raw_engine_request('/eval' . $endpoint, $data, $timeout);
}
