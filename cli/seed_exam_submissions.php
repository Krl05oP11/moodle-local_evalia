<?php
/**
 * CLI seed script — simulates exam submissions for all assigned students.
 *
 * Usage (from inside moodle container):
 *   php /var/www/html/local/evalia/cli/seed_exam_submissions.php \
 *       --examid=1 --telegram_id=8591829566
 *
 * What it does:
 *   1. Finds all evalia_student_exams in status 'assigned' for the given exam.
 *   2. For each student, randomly answers all questions with a realistic mix
 *      (correct rate varies 30-85% per student to produce varied scores).
 *   3. Sets status → 'submitted'.
 *   4. Optionally links all enrolled students to a single Telegram chat_id
 *      so all notifications land on one phone (demo/test use only).
 *
 * Run: docker exec saipa-moodle php /var/www/html/local/evalia/cli/seed_exam_submissions.php --examid=1 --telegram_id=8591829566
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    ['examid' => 0, 'telegram_id' => 0, 'help' => false],
    ['h' => 'help']
);

if ($options['help'] || !$options['examid']) {
    echo "Usage: php seed_exam_submissions.php --examid=N [--telegram_id=CHATID]\n";
    exit(0);
}

$examid     = (int) $options['examid'];
$tg_chat_id = (int) $options['telegram_id'];

$exam = $DB->get_record('evalia_exams', ['id' => $examid], '*', MUST_EXIST);
echo "Exam: [{$exam->id}] {$exam->name} (course {$exam->courseid})\n";

// ── Reset previously graded/submitted rows back to assigned ──────────────────
$DB->execute(
    "UPDATE {evalia_student_exams}
        SET status = 'assigned', score = NULL, answers = NULL,
            timesubmitted = 0, timemodified = :now
      WHERE examid = :examid AND status IN ('submitted','graded')",
    ['examid' => $examid, 'now' => time()]
);
echo "Reset submitted/graded rows to 'assigned'.\n";

// ── Load all student exams in 'assigned' status ───────────────────────────────
$student_exams = $DB->get_records('evalia_student_exams',
    ['examid' => $examid, 'status' => 'assigned'],
    '', 'id, userid, question_ids'
);
echo "Assigned rows to seed: " . count($student_exams) . "\n";

if (empty($student_exams)) {
    echo "Nothing to seed.\n";
    exit(0);
}

// ── Collect all unique question IDs across all student exams ─────────────────
$all_qids = [];
foreach ($student_exams as $se) {
    $ids = json_decode($se->question_ids ?? '[]', true);
    foreach ($ids as $qid) {
        $all_qids[(int)$qid] = true;
    }
}

// ── Load question data ────────────────────────────────────────────────────────
[$in_sql, $in_params] = $DB->get_in_or_equal(array_keys($all_qids), SQL_PARAMS_NAMED, 'q');
$questions = $DB->get_records_select(
    'evalia_question_bank',
    "id $in_sql",
    $in_params,
    '',
    'id, question_type, correct_answer, tolerance'
);

// Load correct options (multichoice / truefalse).
// IMPORTANT: include `id` as first column so get_records_sql uses it as key
// instead of questionid — otherwise only one row per questionid is kept.
[$oin_sql, $oin_params] = $DB->get_in_or_equal(array_keys($all_qids), SQL_PARAMS_NAMED, 'oq');
$correct_opts = [];
$all_opts = $DB->get_records_sql(
    "SELECT o.id, o.questionid, o.option_text, o.is_correct
       FROM {evalia_question_options} o
      WHERE o.questionid $oin_sql
      ORDER BY o.sortorder ASC",
    $oin_params
);

$options_by_qid = [];
foreach ($all_opts as $opt) {
    $qid = (int) $opt->questionid;
    $options_by_qid[$qid][] = $opt->option_text;
    if ((int)$opt->is_correct === 1) {
        $correct_opts[$qid] = $opt->option_text;
    }
}

// Debug: verify options loaded correctly.
foreach (array_keys($all_qids) as $qid) {
    $cnt = count($options_by_qid[$qid] ?? []);
    $has_correct = isset($correct_opts[$qid]) ? 'YES' : 'NO';
    echo "  Q{$qid}: {$cnt} options, correct_opt={$has_correct}\n";
}

// ── Seed submissions ──────────────────────────────────────────────────────────
$now = time();
$seeded = 0;

foreach ($student_exams as $se) {
    $q_ids = json_decode($se->question_ids ?? '[]', true);
    if (empty($q_ids)) {
        continue;
    }

    // Each student gets a random correct rate between 30% and 85%.
    $correct_rate = mt_rand(30, 85) / 100.0;
    $answers = [];

    foreach ($q_ids as $qid) {
        $qid = (int) $qid;
        $q   = $questions[$qid] ?? null;
        if (!$q) {
            continue;
        }

        $be_correct = (mt_rand(0, 99) / 100.0) < $correct_rate;

        if (in_array($q->question_type, ['multichoice', 'truefalse'])) {
            $correct_text = $correct_opts[$qid] ?? '';
            if ($be_correct || empty($options_by_qid[$qid])) {
                $answers[(string)$qid] = $correct_text;
            } else {
                // Pick a random wrong option.
                $wrong = array_filter(
                    $options_by_qid[$qid] ?? [],
                    fn($o) => $o !== $correct_text
                );
                if ($wrong) {
                    $answers[(string)$qid] = $wrong[array_rand($wrong)];
                } else {
                    $answers[(string)$qid] = $correct_text;
                }
            }
        } elseif ($q->question_type === 'numerical') {
            $correct_val = (float)($q->correct_answer ?? 0);
            $tolerance   = (float)($q->tolerance ?? 0.5);
            if ($be_correct) {
                $answers[(string)$qid] = (string)$correct_val;
            } else {
                // Wrong: shift by 2× tolerance in a random direction.
                $delta = $tolerance > 0 ? $tolerance * 2 : 1.0;
                $answers[(string)$qid] = (string)($correct_val + (mt_rand(0,1) ? $delta : -$delta));
            }
        } else {
            // shortanswer
            $correct_text = strtolower(trim($q->correct_answer ?? ''));
            if ($be_correct) {
                $answers[(string)$qid] = $correct_text;
            } else {
                $answers[(string)$qid] = 'no sé';
            }
        }
    }

    $DB->update_record('evalia_student_exams', (object)[
        'id'            => $se->id,
        'answers'       => json_encode($answers),
        'status'        => 'submitted',
        'timesubmitted' => $now - mt_rand(0, 7200),  // random time in last 2h
        'timemodified'  => $now,
    ]);
    $seeded++;
}

echo "Seeded {$seeded} submissions.\n";

// Telegram redirection is handled by EVALIA_DEMO_TELEGRAM_ID in the engine env.
// No need to modify saipa_telegram_links — the engine redirects all feedback
// to the demo chat_id when that env var is set.
if ($tg_chat_id > 0) {
    echo "Demo mode: feedback will be redirected to chat_id {$tg_chat_id} via EVALIA_DEMO_TELEGRAM_ID.\n";
    echo "(Ensure EVALIA_DEMO_TELEGRAM_ID={$tg_chat_id} is set in saipa-engine.env)\n";
}

echo "Done. Now go to the teacher panel → Tab Exámenes and grade them all with IA.\n";
