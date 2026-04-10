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
    } elseif ((int) $exam->time_limit_min > 0) {
        $duration = (int) $exam->time_limit_min * 60;
    }

    $event_data = (object) [
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
        $event = \calendar_event::create($event_data, false);
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
 * Optionally records a student grade when $userid and $grade are provided.
 *
 * @param int        $examid    Row ID from evalia_exams
 * @param int        $courseid
 * @param string     $examname  Label shown in the gradebook column
 * @param int|null   $userid    If set, also push this student's grade
 * @param float|null $grade     Score 0–10; required when $userid is set
 * @return int  grade_items.id (0 on failure)
 */
function local_evalia_grade_item_update(int $examid, int $courseid, string $examname,
                                         ?int $userid = null, ?float $grade = null): int {
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
    } elseif ($gi->itemname !== $examname) {
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
 * Build the per-question breakdown for a student exam and send pedagogical
 * feedback to the student via Telegram (non-blocking — engine queues it).
 *
 * Called from publish_grade / publish_all_grades AFTER teacher approval.
 * Correctness is recomputed from stored answers since it is not persisted separately.
 *
 * @param  int               $student_examid  evalia_student_exams.id
 * @param  \moodle_database  $DB
 * @return string  'sent' | 'failed' | 'skipped' | 'disabled'
 */
function local_evalia_send_feedback(int $student_examid, \moodle_database $DB): string {
    if (get_config('local_evalia', 'feedback_telegram') === '0') {
        return 'disabled';
    }

    try {
        $se   = $DB->get_record('evalia_student_exams', ['id' => $student_examid], '*', MUST_EXIST);
        $exam = $DB->get_record('evalia_exams',         ['id' => $se->examid],     '*', MUST_EXIST);

        // Telegram chat_id for this student (may be 0 → engine will skip send).
        $tg_link = $DB->get_record('saipa_telegram_links', ['userid' => $se->userid], 'telegram_id');
        $tg_id   = ($tg_link && !empty($tg_link->telegram_id)) ? (int) $tg_link->telegram_id : 0;

        $course  = $DB->get_record('course', ['id' => $exam->courseid], 'fullname');
        $student = $DB->get_record('user',   ['id' => $se->userid],     'id, firstname, lastname');

        // ── Reconstruct per-question feedback data from stored answers ────────
        $question_ids = json_decode($se->question_ids ?? '[]', true);
        $answers_map  = json_decode($se->answers      ?? '{}', true);

        if (empty($question_ids)) {
            return 'skipped';
        }

        [$in_sql, $in_params] = $DB->get_in_or_equal($question_ids, SQL_PARAMS_NAMED, 'qid');
        $questions = $DB->get_records_select(
            'evalia_question_bank', "id $in_sql", $in_params, '',
            'id, question_type, correct_answer, tolerance, stem, topic'
        );

        // Load correct option texts (multichoice / truefalse).
        $correct_opts = [];
        if (!empty($questions)) {
            [$opt_sql, $opt_params] = $DB->get_in_or_equal(array_keys($questions), SQL_PARAMS_NAMED, 'oqid');
            $opts = $DB->get_records_select(
                'evalia_question_options', "questionid $opt_sql AND is_correct = 1",
                $opt_params, '', 'questionid, option_text'
            );
            foreach ($opts as $opt) {
                $correct_opts[$opt->questionid] = $opt->option_text;
            }
        }

        // Essay evaluations stored inside the answers JSON under __essay_eval__.
        $essay_evals = (array) ($answers_map['__essay_eval__'] ?? []);

        // Build questions array with correctness for each.
        $questions_fb = [];
        foreach ($question_ids as $qid) {
            $q = $questions[$qid] ?? null;
            if (!$q) { continue; }

            $student_ans  = strtolower(trim((string) ($answers_map[(string)$qid] ?? '')));
            $correct_text = in_array($q->question_type, ['multichoice', 'truefalse'])
                ? ($correct_opts[$qid] ?? '')
                : ($q->correct_answer ?? '');

            $ai_feedback = '';
            if ($q->question_type === 'essay') {
                $eval       = $essay_evals[(string)$qid] ?? [];
                $eval_score = (float) ($eval['score'] ?? 0.0);
                $is_correct = ($eval_score >= 0.6);
                $ai_feedback = $eval['feedback'] ?? '';
            } elseif (in_array($q->question_type, ['multichoice', 'truefalse'])) {
                $is_correct = ($student_ans === strtolower(trim($correct_text)));
            } elseif ($q->question_type === 'numerical') {
                $is_correct = (abs(floatval($student_ans) - floatval($q->correct_answer ?? 0))
                               <= (float) ($q->tolerance ?? 0));
            } else {
                $is_correct = ($student_ans === strtolower(trim($q->correct_answer ?? '')));
            }

            $questions_fb[] = [
                'question_id'    => (int) $qid,
                'stem'           => format_text($q->stem, FORMAT_PLAIN),
                'question_type'  => $q->question_type,
                'student_answer' => (string) ($answers_map[(string)$qid] ?? ''),
                'correct_answer' => $correct_text,
                'correct'        => $is_correct,
                'topic'          => $q->topic ?? '',
                'ai_feedback'    => $ai_feedback,
            ];
        }

        // Call engine — returns immediately, LLM runs as background task.
        $fb_resp = local_evalia_engine_request('/feedback/send', [
            'telegram_id'     => $tg_id,
            'userid'          => (int) $se->userid,
            'examid'          => (int) $se->id,
            'score'           => (float) $se->score,
            'max_score'       => 10.0,
            'course_name'     => $course  ? format_string($course->fullname)  : '',
            'course_id'       => (int) $exam->courseid,
            'student_name'    => $student ? fullname($student) : '',
            'questions'       => $questions_fb,
            'feedback_prompt' => $exam->feedback_prompt ?? '',
        ], 8);

        $status = (!isset($fb_resp['error']) && !empty($fb_resp['sent'])) ? 'sent' : 'failed';

    } catch (\Throwable $e) {
        $status = 'failed';
    }

    // Log attempt.
    try {
        $DB->insert_record('evalia_feedback_log', (object) [
            'userid'       => $se->userid ?? 0,
            'examid'       => $se->id     ?? 0,
            'channel'      => 'telegram',
            'message_text' => 'score=' . ($se->score ?? 0) . '/10 status=' . $status,
            'timesent'     => time(),
            'status'       => ($status === 'sent') ? 'sent' : 'failed',
        ]);
    } catch (\Throwable $e) {
        // Silently ignore log failures.
    }

    return $status;
}

/**
 * Low-level HTTP client for the SAIPA engine.
 *
 * Reads engine_url / engine_token from local_evalia's own admin settings.
 * Falls back to local_saipa settings when running in a joint deployment and
 * local_evalia's engine_url has not been explicitly configured.
 *
 * @param  string     $endpoint  Full path, e.g. '/index' or '/eval/rubric/generate'
 * @param  array|null $data      POST body as associative array; null = GET request
 * @param  int        $timeout   Seconds before giving up
 * @return array                 Decoded JSON response or ['error' => message]
 */
function local_evalia_raw_engine_request(string $endpoint, ?array $data = null, int $timeout = 60): array {
    // Own settings take priority; fall back to local_saipa when absent.
    $engine_url = get_config('local_evalia', 'engine_url');
    $token      = get_config('local_evalia', 'engine_token');

    if (empty($engine_url)) {
        $engine_url = get_config('local_saipa', 'engine_url');
        if (empty($token)) {
            $token = get_config('local_saipa', 'engine_token');
        }
    }

    if (empty($engine_url)) {
        return ['error' => 'EVAL-IA engine URL not configured. '
            . 'Set it under Site administration → Plugins → Local plugins → EVAL-IA.'];
    }

    $url = rtrim($engine_url, '/') . $endpoint;

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
        curl_setopt($ch, CURLOPT_POST,       true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response_str = curl_exec($ch);
    $errno        = curl_errno($ch);
    $error        = curl_error($ch);
    curl_close($ch);

    if ($errno) {
        return ['error' => 'cURL error (' . $errno . '): ' . $error];
    }

    if ($response_str === false || $response_str === '') {
        return ['error' => 'Empty response from engine'];
    }

    $decoded = json_decode($response_str, true);
    if ($decoded === null) {
        return ['error' => 'Invalid JSON from engine: ' . substr($response_str, 0, 300)];
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
