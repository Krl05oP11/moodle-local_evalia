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
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
$tgchatid = (int) $options['telegram_id'];

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
$studentexams = $DB->get_records(
    'evalia_student_exams',
    ['examid' => $examid, 'status' => 'assigned'],
    '',
    'id, userid, question_ids'
);
echo "Assigned rows to seed: " . count($studentexams) . "\n";

if (empty($studentexams)) {
    echo "Nothing to seed.\n";
    exit(0);
}

// ── Collect all unique question IDs across all student exams ─────────────────
$allqids = [];
foreach ($studentexams as $se) {
    $ids = json_decode($se->question_ids ?? '[]', true);
    foreach ($ids as $qid) {
        $allqids[(int)$qid] = true;
    }
}

// ── Load question data ────────────────────────────────────────────────────────
[$insql, $inparams] = $DB->get_in_or_equal(array_keys($allqids), SQL_PARAMS_NAMED, 'q');
$questions = $DB->get_records_select(
    'evalia_question_bank',
    "id $insql",
    $inparams,
    '',
    'id, question_type, correct_answer, tolerance'
);

// Load correct options (multichoice / truefalse).
// IMPORTANT: include `id` as first column so get_records_sql uses it as key
// instead of questionid — otherwise only one row per questionid is kept.
[$oinsql, $oinparams] = $DB->get_in_or_equal(array_keys($allqids), SQL_PARAMS_NAMED, 'oq');
$correctopts = [];
$allopts = $DB->get_records_sql(
    "SELECT o.id, o.questionid, o.option_text, o.is_correct
       FROM {evalia_question_options} o
      WHERE o.questionid $oinsql
      ORDER BY o.sortorder ASC",
    $oinparams
);

$optionsbyqid = [];
foreach ($allopts as $opt) {
    $qid = (int) $opt->questionid;
    $optionsbyqid[$qid][] = $opt->option_text;
    if ((int)$opt->is_correct === 1) {
        $correctopts[$qid] = $opt->option_text;
    }
}

// Debug: verify options loaded correctly.
foreach (array_keys($allqids) as $qid) {
    $cnt = count($optionsbyqid[$qid] ?? []);
    $hascorrect = isset($correctopts[$qid]) ? 'YES' : 'NO';
    echo "  Q{$qid}: {$cnt} options, correct_opt={$hascorrect}\n";
}

// ── Seed submissions ──────────────────────────────────────────────────────────
$now = time();
$seeded = 0;

foreach ($studentexams as $se) {
    $qids = json_decode($se->question_ids ?? '[]', true);
    if (empty($qids)) {
        continue;
    }

    // Each student gets a random correct rate between 30% && 85%.
    $correctrate = mt_rand(30, 85) / 100.0;
    $answers = [];

    foreach ($qids as $qid) {
        $qid = (int) $qid;
        $q   = $questions[$qid] ?? null;
        if (!$q) {
            continue;
        }

        $becorrect = (mt_rand(0, 99) / 100.0) < $correctrate;

        if (in_array($q->question_type, ['multichoice', 'truefalse'])) {
            $correcttext = $correctopts[$qid] ?? '';
            if ($becorrect || empty($optionsbyqid[$qid])) {
                $answers[(string)$qid] = $correcttext;
            } else {
                // Pick a random wrong option.
                $wrong = array_filter(
                    $optionsbyqid[$qid] ?? [],
                    fn($o) => $o !== $correcttext
                );
                if ($wrong) {
                    $answers[(string)$qid] = $wrong[array_rand($wrong)];
                } else {
                    $answers[(string)$qid] = $correcttext;
                }
            }
        } else if ($q->question_type === 'numerical') {
            $correctval = (float)($q->correct_answer ?? 0);
            $tolerance   = (float)($q->tolerance ?? 0.5);
            if ($becorrect) {
                $answers[(string)$qid] = (string)$correctval;
            } else {
                // Wrong: shift by 2× tolerance in a random direction.
                $delta = $tolerance > 0 ? $tolerance * 2 : 1.0;
                $answers[(string)$qid] = (string)($correctval + (mt_rand(0, 1) ? $delta : -$delta));
            }
        } else {
            // shortanswer
            $correcttext = strtolower(trim($q->correct_answer ?? ''));
            if ($becorrect) {
                $answers[(string)$qid] = $correcttext;
            } else {
                $answers[(string)$qid] = 'no sé';
            }
        }
    }

    $DB->update_record('evalia_student_exams', (object)[
        'id'            => $se->id,
        'answers'       => json_encode($answers),
        'status'        => 'submitted',
        'timesubmitted' => $now - mt_rand(0, 7200), // random time in last 2h
        'timemodified'  => $now,
    ]);
    $seeded++;
}

echo "Seeded {$seeded} submissions.\n";

// Telegram redirection is handled by EVALIA_DEMO_TELEGRAM_ID in the engine env.
// No need to modify saipa_telegram_links — the engine redirects all feedback
// to the demo chat_id when that env var is set.
if ($tgchatid > 0) {
    echo "Demo mode: feedback will be redirected to chat_id {$tgchatid} via EVALIA_DEMO_TELEGRAM_ID.\n";
    echo "(Ensure EVALIA_DEMO_TELEGRAM_ID={$tgchatid} is set in saipa-engine.env)\n";
}

echo "Done. Now go to the teacher panel → Tab Exámenes && grade them all with IA.\n";
