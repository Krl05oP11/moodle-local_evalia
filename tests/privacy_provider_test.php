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
 * PHPUnit tests for the local_evalia Privacy API provider.
 *
 * Verifies GDPR compliance: metadata declaration, context discovery,
 * user listing, data export, and data deletion (single user, all users,
 * and bulk user deletion).
 *
 * @package    local_evalia
 * @category   test
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_evalia\privacy\provider;

/**
 * Privacy provider tests for local_evalia.
 */
class privacy_provider_test extends \advanced_testcase {

    /** @var \stdClass */
    private \stdClass $course;

    /** @var \stdClass Teacher user. */
    private \stdClass $teacher;

    /** @var \stdClass Student user with data. */
    private \stdClass $student;

    /** @var \stdClass Second student for bulk tests. */
    private \stdClass $student2;

    /** @var int Rubric ID. */
    private int $rubricid;

    /** @var int Exam ID. */
    private int $examid;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        $gen = $this->getDataGenerator();

        $this->course   = $gen->create_course();
        $this->teacher  = $gen->create_user();
        $this->student  = $gen->create_user();
        $this->student2 = $gen->create_user();

        $gen->enrol_user($this->teacher->id,  $this->course->id, 'editingteacher');
        $gen->enrol_user($this->student->id,  $this->course->id, 'student');
        $gen->enrol_user($this->student2->id, $this->course->id, 'student');

        $this->create_base_records();
    }

    /**
     * Insert rubric + exam + personal data for both students.
     */
    private function create_base_records(): void {
        global $DB;
        $now = time();

        // Rubric.
        $this->rubricid = (int) $DB->insert_record('evalia_rubrics', (object) [
            'courseid'     => $this->course->id,
            'name'         => 'Privacy Test Rubric',
            'status'       => 'active',
            'created_by'   => $this->teacher->id,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);

        // Exam.
        $this->examid = (int) $DB->insert_record('evalia_exams', (object) [
            'courseid'       => $this->course->id,
            'rubricid'       => $this->rubricid,
            'name'           => 'Privacy Test Exam',
            'basic_count'    => 2,
            'medium_count'   => 2,
            'advanced_count' => 1,
            'time_limit_min' => 30,
            'timeopen'       => 0,
            'timeclose'      => 0,
            'grade_itemid'   => 0,
            'status'         => 'active',
            'created_by'     => $this->teacher->id,
            'timecreated'    => $now,
            'timemodified'   => $now,
        ]);

        // Student exam instances.
        foreach ([$this->student, $this->student2] as $u) {
            $DB->insert_record('evalia_student_exams', (object) [
                'examid'        => $this->examid,
                'userid'        => $u->id,
                'question_ids'  => '[1,2,3]',
                'answers'       => '{"1":"A","2":"B","3":"C"}',
                'score'         => 7.50,
                'max_score'     => 10.00,
                'status'        => 'graded',
                'timecreated'   => $now,
                'timemodified'  => $now,
                'timesubmitted' => $now,
            ]);
        }

        // Portfolios.
        foreach ([$this->student, $this->student2] as $u) {
            $DB->insert_record('evalia_portfolio', (object) [
                'userid'        => $u->id,
                'courseid'      => $this->course->id,
                'total_exams'   => 1,
                'avg_grade'     => 7.50,
                'last_activity' => $now,
                'timecreated'   => $now,
                'timemodified'  => $now,
            ]);
        }

        // Portfolio notes.
        foreach ([$this->student, $this->student2] as $u) {
            $DB->insert_record('evalia_portfolio_notes', (object) [
                'userid'      => $u->id,
                'courseid'    => $this->course->id,
                'note_text'   => 'Observation for ' . $u->username,
                'created_by'  => $this->teacher->id,
                'timecreated' => $now,
            ]);
        }

        // Feedback logs.
        foreach ([$this->student, $this->student2] as $u) {
            $DB->insert_record('evalia_feedback_log', (object) [
                'userid'       => $u->id,
                'examid'       => $this->examid,
                'channel'      => 'telegram',
                'message_text' => 'Your grade is 7.5/10',
                'timesent'     => $now,
                'status'       => 'sent',
            ]);
        }
    }

    // ── Metadata ──────────────────────────────────────────────────────────────

    /**
     * Metadata declares 4 database tables and 1 external location.
     *
     * @covers \local_evalia\privacy\provider::get_metadata
     */
    public function test_get_metadata(): void {
        $collection = new collection('local_evalia');
        $collection = provider::get_metadata($collection);

        $items = $collection->get_collection();
        $this->assertNotEmpty($items);

        // Extract names from metadata items.
        $names = [];
        foreach ($items as $item) {
            $names[] = $item->get_name();
        }

        $this->assertContains('evalia_student_exams', $names);
        $this->assertContains('evalia_portfolio', $names);
        $this->assertContains('evalia_portfolio_notes', $names);
        $this->assertContains('evalia_feedback_log', $names);
        $this->assertContains('saipa_engine', $names);
    }

    // ── Context discovery ─────────────────────────────────────────────────────

    /**
     * User with no data returns empty context list.
     *
     * @covers \local_evalia\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid_no_data(): void {
        $other = $this->getDataGenerator()->create_user();
        $contextlist = provider::get_contexts_for_userid($other->id);
        $this->assertEmpty($contextlist);
    }

    /**
     * User with student exam data returns the correct course context.
     *
     * @covers \local_evalia\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid_with_data(): void {
        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $this->assertCount(1, $contextlist);

        $expected = \context_course::instance($this->course->id);
        $contextids = array_map('intval', $contextlist->get_contextids());
        $this->assertContains((int) $expected->id, $contextids);
    }

    // ── User listing ──────────────────────────────────────────────────────────

    /**
     * Course context with data lists both students.
     *
     * @covers \local_evalia\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context(): void {
        $context  = \context_course::instance($this->course->id);
        $userlist = new userlist($context, 'local_evalia');
        provider::get_users_in_context($userlist);

        $userids = array_map('intval', $userlist->get_userids());
        $this->assertContains((int) $this->student->id, $userids);
        $this->assertContains((int) $this->student2->id, $userids);
    }

    /**
     * Non-course context returns no users.
     *
     * @covers \local_evalia\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context_system(): void {
        $context  = \context_system::instance();
        $userlist = new userlist($context, 'local_evalia');
        provider::get_users_in_context($userlist);

        $this->assertEmpty($userlist->get_userids());
    }

    // ── Delete for single user ────────────────────────────────────────────────

    /**
     * delete_data_for_user removes all data for that user only.
     *
     * @covers \local_evalia\privacy\provider::delete_data_for_user
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $user = \core_user::get_user($this->student->id);
        $approved = new approved_contextlist($user, 'local_evalia', $contextlist->get_contextids());

        provider::delete_data_for_user($approved);

        // Student 1 data is gone.
        $se = $DB->get_records_select('evalia_student_exams',
            'examid = :eid AND userid = :uid',
            ['eid' => $this->examid, 'uid' => $this->student->id]);
        $this->assertEmpty($se);

        $p = $DB->get_record('evalia_portfolio',
            ['userid' => $this->student->id, 'courseid' => $this->course->id]);
        $this->assertFalse($p);

        $pn = $DB->get_records('evalia_portfolio_notes',
            ['userid' => $this->student->id, 'courseid' => $this->course->id]);
        $this->assertEmpty($pn);

        $fl = $DB->get_records_select('evalia_feedback_log',
            'examid = :eid AND userid = :uid',
            ['eid' => $this->examid, 'uid' => $this->student->id]);
        $this->assertEmpty($fl);

        // Student 2 data is untouched.
        $se2 = $DB->get_records_select('evalia_student_exams',
            'examid = :eid AND userid = :uid',
            ['eid' => $this->examid, 'uid' => $this->student2->id]);
        $this->assertCount(1, $se2);

        $p2 = $DB->get_record('evalia_portfolio',
            ['userid' => $this->student2->id, 'courseid' => $this->course->id]);
        $this->assertNotFalse($p2);
    }

    // ── Delete all users in context ───────────────────────────────────────────

    /**
     * delete_data_for_all_users_in_context removes everything in the course.
     *
     * @covers \local_evalia\privacy\provider::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        $context = \context_course::instance($this->course->id);
        provider::delete_data_for_all_users_in_context($context);

        // Both students' data is gone.
        $se = $DB->count_records_select('evalia_student_exams',
            'examid = :eid', ['eid' => $this->examid]);
        $this->assertSame(0, $se);

        $p = $DB->count_records('evalia_portfolio', ['courseid' => $this->course->id]);
        $this->assertSame(0, $p);

        $pn = $DB->count_records('evalia_portfolio_notes', ['courseid' => $this->course->id]);
        $this->assertSame(0, $pn);

        $fl = $DB->count_records_select('evalia_feedback_log',
            'examid = :eid', ['eid' => $this->examid]);
        $this->assertSame(0, $fl);
    }

    // ── Delete for multiple users (bulk) ──────────────────────────────────────

    /**
     * delete_data_for_users removes data for specified users only.
     *
     * @covers \local_evalia\privacy\provider::delete_data_for_users
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $context = \context_course::instance($this->course->id);

        // Delete only student 1.
        $approved = new approved_userlist($context, 'local_evalia', [$this->student->id]);
        provider::delete_data_for_users($approved);

        // Student 1 gone.
        $se1 = $DB->count_records_select('evalia_student_exams',
            'examid = :eid AND userid = :uid',
            ['eid' => $this->examid, 'uid' => $this->student->id]);
        $this->assertSame(0, $se1);

        // Student 2 still there.
        $se2 = $DB->count_records_select('evalia_student_exams',
            'examid = :eid AND userid = :uid',
            ['eid' => $this->examid, 'uid' => $this->student2->id]);
        $this->assertSame(1, $se2);
    }

    // ── Export ─────────────────────────────────────────────────────────────────

    /**
     * export_user_data writes data to the privacy writer.
     *
     * @covers \local_evalia\privacy\provider::export_user_data
     */
    public function test_export_user_data(): void {
        $contextlist = provider::get_contexts_for_userid($this->student->id);
        $user = \core_user::get_user($this->student->id);
        $approved = new approved_contextlist($user, 'local_evalia', $contextlist->get_contextids());

        // Enable the writer.
        writer::reset();
        writer::with_context(\context_system::instance());
        $this->setUser($this->student);

        provider::export_user_data($approved);

        // Verify data was written to the course context.
        $context = \context_course::instance($this->course->id);
        $writer  = writer::with_context($context);

        $exams = $writer->get_data([
            get_string('pluginname', 'local_evalia'),
            get_string('tab_exams', 'local_evalia'),
        ]);
        $this->assertNotEmpty($exams);
        $this->assertNotEmpty($exams->exams);
        $this->assertSame('Privacy Test Exam', $exams->exams[0]['exam_name']);
    }
}
