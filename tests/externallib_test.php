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
 * PHPUnit tests for local_evalia web services (engine-free operations).
 *
 * Tests cover: rubric CRUD, exam creation, portfolio notes,
 * student exam listing, and capability enforcement.
 *
 * Engine-dependent WS (generate_rubric, generate_questions, grade_exam,
 * publish_grade, index_course) are excluded — they require a live AI
 * engine and are covered by integration tests.
 *
 * @package    local_evalia
 * @category   test
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/evalia/lib.php');

/**
 * Tests for local_evalia external web services.
 */
class externallib_test extends \advanced_testcase {

    /** @var \stdClass Course used in tests. */
    private \stdClass $course;

    /** @var \stdClass Teacher user enrolled in $course. */
    private \stdClass $teacher;

    /** @var \stdClass Student user enrolled in $course. */
    private \stdClass $student;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        $generator = $this->getDataGenerator();

        $this->course  = $generator->create_course();
        $this->teacher = $generator->create_user();
        $this->student = $generator->create_user();

        $generator->enrol_user($this->teacher->id, $this->course->id, 'editingteacher');
        $generator->enrol_user($this->student->id, $this->course->id, 'student');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Insert a rubric row directly and return its ID.
     */
    private function create_rubric(string $name = 'Test Rubric', string $status = 'draft'): int {
        global $DB;
        $now = time();
        return (int) $DB->insert_record('evalia_rubrics', (object) [
            'courseid'     => $this->course->id,
            'name'         => $name,
            'status'       => $status,
            'created_by'   => $this->teacher->id,
            'timecreated'  => $now,
            'timemodified' => $now,
        ]);
    }

    // ── get_rubric ────────────────────────────────────────────────────────────

    /**
     * get_rubric on a course with no rubric returns rubricid = 0.
     *
     * @covers \local_evalia\external\get_rubric::execute
     */
    public function test_get_rubric_no_rubric(): void {
        $this->setUser($this->teacher);
        $result = \local_evalia\external\get_rubric::execute($this->course->id);
        $this->assertSame(0, $result['rubricid']);
        $this->assertSame('draft', $result['status']);
        $this->assertEmpty($result['items']);
    }

    /**
     * get_rubric returns the rubric and its items.
     *
     * @covers \local_evalia\external\get_rubric::execute
     */
    public function test_get_rubric_returns_items(): void {
        global $DB;

        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric('Introduction to Graphs');

        $now = time();
        $DB->insert_record('evalia_rubric_items', (object) [
            'rubricid'          => $rubricid,
            'topic'             => 'Graph Theory Basics',
            'description'       => '',
            'difficulty_weight' => 'medium',
            'sortorder'         => 1,
            'timecreated'       => $now,
        ]);

        $result = \local_evalia\external\get_rubric::execute($this->course->id);
        $this->assertSame($rubricid, $result['rubricid']);
        $this->assertSame('Introduction to Graphs', $result['name']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('Graph Theory Basics', $result['items'][0]['topic']);
    }

    /**
     * get_rubric throws required_capability_exception for a student.
     *
     * @covers \local_evalia\external\get_rubric::execute
     */
    public function test_get_rubric_requires_manage_capability(): void {
        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        \local_evalia\external\get_rubric::execute($this->course->id);
    }

    // ── save_rubric ───────────────────────────────────────────────────────────

    /**
     * save_rubric updates the rubric name and inserts new items.
     *
     * @covers \local_evalia\external\save_rubric::execute
     */
    public function test_save_rubric_updates_name_and_inserts_items(): void {
        global $DB;

        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric('Old Name');

        $result = \local_evalia\external\save_rubric::execute(
            $rubricid,
            'New Name',
            false,
            [
                ['id' => 0, 'topic' => 'Topic A', 'description' => '', 'difficulty_weight' => 'low',    'sortorder' => 1],
                ['id' => 0, 'topic' => 'Topic B', 'description' => '', 'difficulty_weight' => 'medium', 'sortorder' => 2],
            ]
        );
        $this->resetDebugging();

        $this->assertTrue($result['success']);

        $rubric = $DB->get_record('evalia_rubrics', ['id' => $rubricid]);
        $this->assertSame('New Name', $rubric->name);
        $this->assertSame('draft', $rubric->status);

        $items = $DB->get_records('evalia_rubric_items', ['rubricid' => $rubricid]);
        $this->assertCount(2, $items);
    }

    /**
     * save_rubric with activate=true sets status to 'active'.
     *
     * @covers \local_evalia\external\save_rubric::execute
     */
    public function test_save_rubric_activates(): void {
        global $DB;

        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric('Draft Rubric', 'draft');

        \local_evalia\external\save_rubric::execute($rubricid, 'Active Rubric', true, []);
        $this->resetDebugging();

        $rubric = $DB->get_record('evalia_rubrics', ['id' => $rubricid]);
        $this->assertSame('active', $rubric->status);
    }

    /**
     * save_rubric throws required_capability_exception for a student.
     *
     * @covers \local_evalia\external\save_rubric::execute
     */
    public function test_save_rubric_requires_manage_capability(): void {
        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric();

        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        \local_evalia\external\save_rubric::execute($rubricid, 'Hacked', false, []);
    }

    // ── create_exam ───────────────────────────────────────────────────────────

    /**
     * create_exam inserts a row in evalia_exams and creates a grade item.
     *
     * @covers \local_evalia\external\create_exam::execute
     */
    public function test_create_exam_success(): void {
        global $DB;

        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric('Test Rubric', 'active');

        $result = \local_evalia\external\create_exam::execute(
            $this->course->id, $rubricid, 'Midterm', '', 3, 4, 2, 60
        );

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['examid']);

        $exam = $DB->get_record('evalia_exams', ['id' => $result['examid']]);
        $this->assertNotFalse($exam);
        $this->assertSame('Midterm', $exam->name);
        $this->assertSame('draft', $exam->status);
        $this->assertEquals($this->course->id, (int) $exam->courseid);
    }

    /**
     * create_exam returns success=false when question total is zero.
     *
     * @covers \local_evalia\external\create_exam::execute
     */
    public function test_create_exam_zero_questions_fails(): void {
        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric();

        $result = \local_evalia\external\create_exam::execute(
            $this->course->id, $rubricid, 'Empty Exam', '', 0, 0, 0, 60
        );

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['examid']);
    }

    /**
     * create_exam throws required_capability_exception for a student.
     *
     * @covers \local_evalia\external\create_exam::execute
     */
    public function test_create_exam_requires_manage_capability(): void {
        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric();

        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        \local_evalia\external\create_exam::execute(
            $this->course->id, $rubricid, 'Hack', '', 1, 1, 1, 60
        );
    }

    // ── add_portfolio_note ────────────────────────────────────────────────────

    /**
     * add_portfolio_note inserts a note and returns its ID.
     *
     * @covers \local_evalia\external\add_portfolio_note::execute
     */
    public function test_add_portfolio_note_success(): void {
        global $DB;

        $this->setUser($this->teacher);

        $result = \local_evalia\external\add_portfolio_note::execute(
            $this->student->id, $this->course->id, 'Student shows great progress.'
        );

        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['noteid']);

        $note = $DB->get_record('evalia_portfolio_notes', ['id' => $result['noteid']]);
        $this->assertNotFalse($note);
        $this->assertSame('Student shows great progress.', $note->note_text);
        $this->assertEquals($this->student->id, (int) $note->userid);
        $this->assertEquals($this->teacher->id, (int) $note->created_by);
    }

    /**
     * add_portfolio_note returns success=false for an empty note.
     *
     * @covers \local_evalia\external\add_portfolio_note::execute
     */
    public function test_add_portfolio_note_empty_fails(): void {
        $this->setUser($this->teacher);

        $result = \local_evalia\external\add_portfolio_note::execute(
            $this->student->id, $this->course->id, '   '
        );

        $this->assertFalse($result['success']);
        $this->assertSame(0, $result['noteid']);
    }

    /**
     * add_portfolio_note throws required_capability_exception for a student.
     *
     * @covers \local_evalia\external\add_portfolio_note::execute
     */
    public function test_add_portfolio_note_requires_manage_capability(): void {
        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        \local_evalia\external\add_portfolio_note::execute(
            $this->student->id, $this->course->id, 'Should fail'
        );
    }

    // ── get_student_exams ─────────────────────────────────────────────────────

    /**
     * get_student_exams returns students with not_assigned status when no exam is assigned.
     *
     * @covers \local_evalia\external\get_student_exams::execute
     */
    public function test_get_student_exams_no_assignments(): void {
        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric('Rubric', 'active');

        $exam = \local_evalia\external\create_exam::execute(
            $this->course->id, $rubricid, 'Test Exam', '', 1, 1, 1, 60
        );
        $this->assertTrue($exam['success']);

        $result = \local_evalia\external\get_student_exams::execute($exam['examid']);

        $this->assertGreaterThanOrEqual(1, $result['total']);

        // The enrolled student should appear as not_assigned.
        $found = false;
        foreach ($result['students'] as $s) {
            if ((int) $s['userid'] === (int) $this->student->id) {
                $this->assertSame('not_assigned', $s['status']);
                $this->assertSame(0, $s['student_examid']);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Enrolled student not found in result');
    }

    /**
     * get_student_exams throws required_capability_exception for a student.
     *
     * @covers \local_evalia\external\get_student_exams::execute
     */
    public function test_get_student_exams_requires_manage_capability(): void {
        $this->setUser($this->teacher);
        $rubricid = $this->create_rubric();
        $exam = \local_evalia\external\create_exam::execute(
            $this->course->id, $rubricid, 'Restricted Exam', '', 1, 1, 1, 60
        );

        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        \local_evalia\external\get_student_exams::execute($exam['examid']);
    }
}
