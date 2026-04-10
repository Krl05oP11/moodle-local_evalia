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
 * Privacy API implementation for local_evalia (EVAL-IA).
 *
 * Tables that contain personal data
 * ----------------------------------
 * evalia_student_exams  userid, question_ids, answers, score, status
 * evalia_portfolio      userid, avg_grade, total_exams, last_activity
 * evalia_portfolio_notes userid (student observed), note_text
 * evalia_feedback_log   userid, message_text, channel, status
 *
 * Tables with only teacher-authored data (rubrics, exams) are NOT personal
 * data of the teacher in the GDPR sense for the purposes of subject-access
 * requests, but created_by is declared in metadata for completeness.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\helper;

/**
 * Privacy provider for local_evalia.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    // ──────────────────────────────────────────────────────────────────────
    // 1. Metadata declaration
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Returns metadata about personal data stored by this plugin.
     */
    public static function get_metadata(collection $collection): collection {

        // Teacher-authored data: rubrics and exams store created_by (teacher user ID).
        // Not personal data in the GDPR sense, but declared for Moodle table-coverage compliance.
        $collection->add_database_table('evalia_rubrics', [
            'created_by' => 'privacy:metadata:evalia_rubrics:created_by',
        ], 'privacy:metadata:evalia_rubrics');

        $collection->add_database_table('evalia_exams', [
            'created_by' => 'privacy:metadata:evalia_exams:created_by',
        ], 'privacy:metadata:evalia_exams');

        // Per-student exam instances.
        $collection->add_database_table('evalia_student_exams', [
            'userid'        => 'privacy:metadata:evalia_student_exams:userid',
            'question_ids'  => 'privacy:metadata:evalia_student_exams:question_ids',
            'answers'       => 'privacy:metadata:evalia_student_exams:answers',
            'score'         => 'privacy:metadata:evalia_student_exams:score',
            'status'        => 'privacy:metadata:evalia_student_exams:status',
            'timesubmitted' => 'privacy:metadata:evalia_student_exams:timesubmitted',
        ], 'privacy:metadata:evalia_student_exams');

        // Student performance summary.
        $collection->add_database_table('evalia_portfolio', [
            'userid'        => 'privacy:metadata:evalia_portfolio:userid',
            'avg_grade'     => 'privacy:metadata:evalia_portfolio:avg_grade',
            'total_exams'   => 'privacy:metadata:evalia_portfolio:total_exams',
            'last_activity' => 'privacy:metadata:evalia_portfolio:last_activity',
        ], 'privacy:metadata:evalia_portfolio');

        // Teacher observations about a student.
        $collection->add_database_table('evalia_portfolio_notes', [
            'userid'      => 'privacy:metadata:evalia_portfolio_notes:userid',
            'note_text'   => 'privacy:metadata:evalia_portfolio_notes:note_text',
            'created_by'  => 'privacy:metadata:evalia_portfolio_notes:created_by',
            'timecreated' => 'privacy:metadata:evalia_portfolio_notes:timecreated',
        ], 'privacy:metadata:evalia_portfolio_notes');

        // Feedback delivery log.
        $collection->add_database_table('evalia_feedback_log', [
            'userid'       => 'privacy:metadata:evalia_feedback_log:userid',
            'channel'      => 'privacy:metadata:evalia_feedback_log:channel',
            'message_text' => 'privacy:metadata:evalia_feedback_log:message_text',
            'timesent'     => 'privacy:metadata:evalia_feedback_log:timesent',
            'status'       => 'privacy:metadata:evalia_feedback_log:status',
        ], 'privacy:metadata:evalia_feedback_log');

        // External AI engine receives anonymised exam content (no PII).
        $collection->add_external_location_link('saipa_engine', [
            'question_stems' => 'privacy:metadata:saipa_engine:question_stems',
            'answers'        => 'privacy:metadata:saipa_engine:answers',
        ], 'privacy:metadata:saipa_engine');

        return $collection;
    }

    // ──────────────────────────────────────────────────────────────────────
    // 2. Context discovery
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Returns a contextlist of all course contexts in which the user has data.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        // Contexts via student exams (join exam → course).
        $sql = 'SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :ctxlevel
                  JOIN {evalia_exams} e ON e.courseid = c.id
                  JOIN {evalia_student_exams} se ON se.examid = e.id AND se.userid = :userid';
        $contextlist->add_from_sql($sql, ['ctxlevel' => CONTEXT_COURSE, 'userid' => $userid]);

        // Contexts via portfolio.
        $sql = 'SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :ctxlevel
                  JOIN {evalia_portfolio} p ON p.courseid = c.id AND p.userid = :userid';
        $contextlist->add_from_sql($sql, ['ctxlevel' => CONTEXT_COURSE, 'userid' => $userid]);

        // Contexts via portfolio notes (student is the subject).
        $sql = 'SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :ctxlevel
                  JOIN {evalia_portfolio_notes} pn ON pn.courseid = c.id AND pn.userid = :userid';
        $contextlist->add_from_sql($sql, ['ctxlevel' => CONTEXT_COURSE, 'userid' => $userid]);

        // Contexts via feedback log (join exam → course).
        $sql = 'SELECT ctx.id
                  FROM {context} ctx
                  JOIN {course} c ON c.id = ctx.instanceid AND ctx.contextlevel = :ctxlevel
                  JOIN {evalia_exams} e ON e.courseid = c.id
                  JOIN {evalia_feedback_log} fl ON fl.examid = e.id AND fl.userid = :userid';
        $contextlist->add_from_sql($sql, ['ctxlevel' => CONTEXT_COURSE, 'userid' => $userid]);

        return $contextlist;
    }

    /**
     * Returns the users with data in the given context (for site admin use).
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();

        if (!($context instanceof \context_course)) {
            return;
        }

        $courseid = $context->instanceid;

        // Users with student exams in this course.
        $sql = 'SELECT se.userid
                  FROM {evalia_student_exams} se
                  JOIN {evalia_exams} e ON e.id = se.examid
                 WHERE e.courseid = :courseid';
        $userlist->add_from_sql('userid', $sql, ['courseid' => $courseid]);

        // Users with portfolio entries.
        $sql = 'SELECT userid FROM {evalia_portfolio} WHERE courseid = :courseid';
        $userlist->add_from_sql('userid', $sql, ['courseid' => $courseid]);

        // Users who are subjects of portfolio notes.
        $sql = 'SELECT userid FROM {evalia_portfolio_notes} WHERE courseid = :courseid';
        $userlist->add_from_sql('userid', $sql, ['courseid' => $courseid]);

        // Users with feedback log entries for exams in this course.
        $sql = 'SELECT fl.userid
                  FROM {evalia_feedback_log} fl
                  JOIN {evalia_exams} e ON e.id = fl.examid
                 WHERE e.courseid = :courseid';
        $userlist->add_from_sql('userid', $sql, ['courseid' => $courseid]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 3. Data export
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Exports personal data for the specified user in the given approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!($context instanceof \context_course)) {
                continue;
            }

            $courseid = $context->instanceid;

            // ── Student exams ─────────────────────────────────────────────
            $sql = 'SELECT se.*, e.name AS exam_name
                      FROM {evalia_student_exams} se
                      JOIN {evalia_exams} e ON e.id = se.examid
                     WHERE e.courseid = :courseid AND se.userid = :userid
                  ORDER BY se.timecreated';
            $records = $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);

            $exams_export = [];
            foreach ($records as $r) {
                $exams_export[] = [
                    'exam_name'     => $r->exam_name,
                    'score'         => $r->score . ' / ' . $r->max_score,
                    'status'        => $r->status,
                    'answers'       => $r->answers,
                    'timesubmitted' => $r->timesubmitted ? userdate($r->timesubmitted) : '-',
                ];
            }
            if (!empty($exams_export)) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_evalia'), get_string('tab_exams', 'local_evalia')],
                    (object) ['exams' => $exams_export]
                );
            }

            // ── Portfolio ─────────────────────────────────────────────────
            $portfolio = $DB->get_record('evalia_portfolio',
                ['userid' => $userid, 'courseid' => $courseid]);
            if ($portfolio) {
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_evalia'), get_string('tab_portfolio', 'local_evalia')],
                    (object) [
                        'avg_grade'     => $portfolio->avg_grade,
                        'total_exams'   => $portfolio->total_exams,
                        'last_activity' => $portfolio->last_activity
                            ? userdate($portfolio->last_activity) : '-',
                    ]
                );
            }

            // ── Portfolio notes about this student ────────────────────────
            $notes = $DB->get_records('evalia_portfolio_notes',
                ['userid' => $userid, 'courseid' => $courseid], 'timecreated ASC');
            if (!empty($notes)) {
                $notes_export = [];
                foreach ($notes as $n) {
                    $notes_export[] = [
                        'note_text'   => $n->note_text,
                        'timecreated' => userdate($n->timecreated),
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_evalia'), get_string('portfolio_observations', 'local_evalia')],
                    (object) ['notes' => $notes_export]
                );
            }

            // ── Feedback log ──────────────────────────────────────────────
            $sql = 'SELECT fl.*
                      FROM {evalia_feedback_log} fl
                      JOIN {evalia_exams} e ON e.id = fl.examid
                     WHERE e.courseid = :courseid AND fl.userid = :userid
                  ORDER BY fl.timesent';
            $logs = $DB->get_records_sql($sql, ['courseid' => $courseid, 'userid' => $userid]);

            if (!empty($logs)) {
                $log_export = [];
                foreach ($logs as $l) {
                    $log_export[] = [
                        'channel'      => $l->channel,
                        'message_text' => $l->message_text,
                        'timesent'     => userdate($l->timesent),
                        'status'       => $l->status,
                    ];
                }
                writer::with_context($context)->export_data(
                    [get_string('pluginname', 'local_evalia'), 'Feedback Log'],
                    (object) ['log' => $log_export]
                );
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // 4. Data deletion — all users in a context
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Deletes all personal data for all users in the given course context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!($context instanceof \context_course)) {
            return;
        }

        $courseid = $context->instanceid;

        // Student exams: collect IDs first, then delete answers.
        $exam_ids = $DB->get_fieldset_select('evalia_exams', 'id', 'courseid = :cid', ['cid' => $courseid]);

        if (!empty($exam_ids)) {
            [$in_sql, $in_params] = $DB->get_in_or_equal($exam_ids, SQL_PARAMS_NAMED, 'eid');

            // Delete feedback logs for these exams.
            $DB->delete_records_select('evalia_feedback_log', "examid $in_sql", $in_params);

            // Delete student exam instances.
            $DB->delete_records_select('evalia_student_exams', "examid $in_sql", $in_params);
        }

        // Portfolio and notes are keyed by courseid directly.
        $DB->delete_records('evalia_portfolio',       ['courseid' => $courseid]);
        $DB->delete_records('evalia_portfolio_notes', ['courseid' => $courseid]);
    }

    // ──────────────────────────────────────────────────────────────────────
    // 5. Data deletion — single user
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Deletes personal data for the specified user in the given approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!($context instanceof \context_course)) {
                continue;
            }

            $courseid = $context->instanceid;

            // Student exams.
            $exam_ids = $DB->get_fieldset_select(
                'evalia_exams', 'id', 'courseid = :cid', ['cid' => $courseid]
            );
            if (!empty($exam_ids)) {
                [$in_sql, $in_params] = $DB->get_in_or_equal($exam_ids, SQL_PARAMS_NAMED, 'eid');
                $in_params['uid'] = $userid;

                $DB->delete_records_select(
                    'evalia_feedback_log', "examid $in_sql AND userid = :uid", $in_params
                );
                $DB->delete_records_select(
                    'evalia_student_exams', "examid $in_sql AND userid = :uid", $in_params
                );
            }

            // Portfolio.
            $DB->delete_records('evalia_portfolio',
                ['userid' => $userid, 'courseid' => $courseid]);

            // Portfolio notes where this user is the SUBJECT.
            $DB->delete_records('evalia_portfolio_notes',
                ['userid' => $userid, 'courseid' => $courseid]);
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // 6. Data deletion — multiple users (bulk)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Deletes personal data for a list of users within a single context.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!($context instanceof \context_course)) {
            return;
        }

        $courseid = $context->instanceid;
        $userids  = $userlist->get_userids();

        if (empty($userids)) {
            return;
        }

        [$uid_sql, $uid_params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');

        // Exams in this course.
        $exam_ids = $DB->get_fieldset_select(
            'evalia_exams', 'id', 'courseid = :cid', ['cid' => $courseid]
        );

        if (!empty($exam_ids)) {
            [$eid_sql, $eid_params] = $DB->get_in_or_equal($exam_ids, SQL_PARAMS_NAMED, 'eid');

            $params = array_merge($uid_params, $eid_params);

            $DB->delete_records_select(
                'evalia_feedback_log', "examid $eid_sql AND userid $uid_sql", $params
            );
            $DB->delete_records_select(
                'evalia_student_exams', "examid $eid_sql AND userid $uid_sql", $params
            );
        }

        // Portfolio.
        $DB->delete_records_select(
            'evalia_portfolio', "courseid = :cid AND userid $uid_sql",
            array_merge(['cid' => $courseid], $uid_params)
        );

        // Portfolio notes (student is subject).
        $DB->delete_records_select(
            'evalia_portfolio_notes', "courseid = :cid AND userid $uid_sql",
            array_merge(['cid' => $courseid], $uid_params)
        );
    }
}
