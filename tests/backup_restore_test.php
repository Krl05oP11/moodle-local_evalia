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
 * PHPUnit tests: EVAL-IA's course data survives a course backup and restore.
 *
 * Same finding as Marketplace review MMR-462, issue 3 (local_saipa).
 *
 * Run from the Moodle root:
 *   vendor/bin/phpunit local/evalia/tests/backup_restore_test.php
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/local/evalia/lib.php');

/**
 * Tests for backup_local_evalia_plugin and restore_local_evalia_plugin.
 *
 * @covers \backup_local_evalia_plugin
 * @covers \restore_local_evalia_plugin
 */
final class backup_restore_test extends \advanced_testcase {
    /** @var \stdClass Course with EVAL-IA data. */
    private \stdClass $course;
    /** @var \stdClass Student who took the exam. */
    private \stdClass $student;
    /** @var \stdClass Teacher who wrote a note. */
    private \stdClass $teacher;
    /** @var int[] Question ids in the original course: [multiple choice, essay]. */
    private array $questionids;
    /** @var int Exam id in the original course. */
    private int $examid;

    /**
     * A rubric with one topic, two questions (one with options), an exam with its gradebook
     * column, a graded student exam, a portfolio and a note.
     */
    protected function setUp(): void {
        global $DB;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $gen = $this->getDataGenerator();
        $this->course = $gen->create_course();
        $this->student = $gen->create_and_enrol($this->course, 'student');
        $this->teacher = $gen->create_and_enrol($this->course, 'editingteacher');
        $now = time();

        $rubricid = $DB->insert_record('local_evalia_rubrics', (object) [
            'courseid' => $this->course->id, 'name' => 'Rubric', 'status' => 'active',
            'created_by' => $this->teacher->id, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $itemid = $DB->insert_record('local_evalia_rubric_items', (object) [
            'rubricid' => $rubricid, 'topic' => 'Entropy', 'difficulty_weight' => 1, 'sortorder' => 1, 'timecreated' => $now,
        ]);
        foreach (['mcq', 'essay'] as $type) {
            $this->questionids[] = $DB->insert_record('local_evalia_question_bank', (object) [
                'courseid' => $this->course->id, 'rubricid' => $rubricid, 'rubric_item_id' => $itemid,
                'question_type' => $type, 'stem' => "Question $type", 'difficulty' => 'basic', 'topic' => 'Entropy',
                'status' => 'approved', 'timecreated' => $now, 'timemodified' => $now,
            ]);
        }
        $DB->insert_record('local_evalia_question_options', (object) [
            'questionid' => $this->questionids[0], 'option_text' => 'Disorder', 'is_correct' => 1, 'sortorder' => 1,
        ]);
        $this->examid = $DB->insert_record('local_evalia_exams', (object) [
            'courseid' => $this->course->id, 'rubricid' => $rubricid, 'name' => 'Midterm', 'basic_count' => 2,
            'medium_count' => 0, 'advanced_count' => 0, 'time_limit_min' => 60, 'timeopen' => 0, 'timeclose' => 0,
            'grade_itemid' => 0, 'status' => 'published', 'created_by' => $this->teacher->id,
            'timecreated' => $now, 'timemodified' => $now,
        ]);
        $gradeitemid = local_evalia_grade_item_update($this->examid, $this->course->id, 'Midterm');
        $DB->set_field('local_evalia_exams', 'grade_itemid', $gradeitemid, ['id' => $this->examid]);

        [$mcq, $essay] = $this->questionids;
        $DB->insert_record('local_evalia_student_exams', (object) [
            'examid' => $this->examid, 'userid' => $this->student->id,
            'question_ids' => json_encode([$mcq, $essay]),
            'answers' => json_encode([$mcq => 'Disorder', $essay => 'Text', '__essay_eval__' => [$essay => ['score' => 1]]]),
            'score' => 8.5, 'max_score' => 10, 'status' => 'graded',
            'timecreated' => $now, 'timemodified' => $now, 'timesubmitted' => $now,
        ]);
        $DB->insert_record('local_evalia_portfolio', (object) [
            'userid' => $this->student->id, 'courseid' => $this->course->id, 'total_exams' => 1, 'avg_grade' => 8.5,
            'last_activity' => $now, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $DB->insert_record('local_evalia_portfolio_notes', (object) [
            'userid' => $this->student->id, 'courseid' => $this->course->id, 'note_text' => 'Works hard',
            'created_by' => $this->teacher->id, 'timecreated' => $now,
        ]);
    }

    /**
     * Backs up the course and restores it into a new course.
     *
     * @param bool $users Whether the backup includes user data.
     * @param int|null $targetcourseid Restore into this course after deleting its content, instead of a new course.
     * @return int Id of the course restored into.
     */
    private function backup_and_restore(bool $users, ?int $targetcourseid = null): int {
        global $CFG, $USER;
        $CFG->keeptempdirectoriesonbackup = true;

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $this->course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value($users);
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $bc->destroy();

        $newcourseid = $targetcourseid
            ?: \restore_dbops::create_new_course('Restored', 'restored' . (int) $users, $this->course->category);
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            $targetcourseid ? \backup::TARGET_EXISTING_DELETING : \backup::TARGET_NEW_COURSE
        );
        if ($targetcourseid) {
            // Into an existing course, Moodle restores course-level plugin data only with this setting.
            $rc->get_plan()->get_setting('overwrite_conf')->set_value(true);
        }
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
        return $newcourseid;
    }

    /**
     * With user data, the bank, the exam, the student's exam (with new question ids) and the notes come back.
     */
    public function test_restore_with_user_data(): void {
        global $DB;
        $newcourseid = $this->backup_and_restore(true);

        $rubric = $DB->get_record('local_evalia_rubrics', ['courseid' => $newcourseid], '*', MUST_EXIST);
        $this->assertEquals(1, $DB->count_records('local_evalia_rubric_items', ['rubricid' => $rubric->id]));
        $questions = $DB->get_records('local_evalia_question_bank', ['courseid' => $newcourseid], 'id ASC');
        $this->assertCount(2, $questions);
        [$mcq, $essay] = array_keys($questions);
        $this->assertEquals(1, $DB->count_records('local_evalia_question_options', ['questionid' => $mcq]));

        $exam = $DB->get_record('local_evalia_exams', ['courseid' => $newcourseid], '*', MUST_EXIST);
        $this->assertEquals($rubric->id, $exam->rubricid);
        $gradeitem = $DB->get_record('grade_items', ['id' => $exam->grade_itemid], '*', MUST_EXIST);
        $this->assertEquals($newcourseid, $gradeitem->courseid);
        $this->assertSame('evalia_exam_' . $exam->id, $gradeitem->idnumber);

        $attempt = $DB->get_record('local_evalia_student_exams', ['examid' => $exam->id], '*', MUST_EXIST);
        $this->assertEquals($this->student->id, $attempt->userid);
        $this->assertEquals([$mcq, $essay], json_decode($attempt->question_ids, true));
        $answers = json_decode($attempt->answers, true);
        $this->assertSame('Disorder', $answers[$mcq]);
        $this->assertSame('Text', $answers[$essay]);
        $this->assertSame(['score' => 1], $answers['__essay_eval__'][$essay]);

        $this->assertTrue($DB->record_exists('local_evalia_portfolio', ['courseid' => $newcourseid]));
        $note = $DB->get_record('local_evalia_portfolio_notes', ['courseid' => $newcourseid], '*', MUST_EXIST);
        $this->assertEquals($this->teacher->id, $note->created_by);
    }

    /**
     * Without user data, the rubric, bank and exams come back but no student work.
     */
    public function test_restore_without_user_data(): void {
        global $DB;
        $newcourseid = $this->backup_and_restore(false);

        $this->assertEquals(2, $DB->count_records('local_evalia_question_bank', ['courseid' => $newcourseid]));
        $exam = $DB->get_record('local_evalia_exams', ['courseid' => $newcourseid], '*', MUST_EXIST);
        $this->assertFalse($DB->record_exists('local_evalia_student_exams', ['examid' => $exam->id]));
        $this->assertFalse($DB->record_exists('local_evalia_portfolio', ['courseid' => $newcourseid]));
        $this->assertFalse($DB->record_exists('local_evalia_portfolio_notes', ['courseid' => $newcourseid]));
    }

    /**
     * A question whose rubric topic was deleted (the rubric was regenerated) is left out, and its
     * options do not end up on another question.
     */
    public function test_question_without_topic_does_not_lend_its_options(): void {
        global $DB;
        $rubricid = $DB->get_field('local_evalia_rubrics', 'id', ['courseid' => $this->course->id]);
        $ghost = $DB->insert_record('local_evalia_question_bank', (object) [
            'courseid' => $this->course->id, 'rubricid' => $rubricid, 'rubric_item_id' => 999999,
            'question_type' => 'mcq', 'stem' => 'Orphan', 'difficulty' => 'basic', 'topic' => 'Gone',
            'status' => 'approved', 'timecreated' => time(), 'timemodified' => time(),
        ]);
        $DB->insert_record('local_evalia_question_options', (object) [
            'questionid' => $ghost, 'option_text' => 'Ghost', 'is_correct' => 1, 'sortorder' => 1,
        ]);

        $newcourseid = $this->backup_and_restore(false);

        $this->assertEquals(2, $DB->count_records('local_evalia_question_bank', ['courseid' => $newcourseid]));
        $where = $DB->sql_compare_text('option_text') . ' = ?';
        $ghosts = $DB->count_records_select('local_evalia_question_options', $where, ['Ghost']);
        $this->assertEquals(1, $ghosts, 'Only the original Ghost option: none attached to a restored question.');
    }

    /**
     * The exam's calendar event is linked to the restored exam, so it is not created twice.
     */
    public function test_calendar_event_follows_the_exam(): void {
        global $DB;
        $DB->set_field('local_evalia_exams', 'timeopen', time() + DAYSECS, ['id' => $this->examid]);
        $this->assertNotEmpty(local_evalia_create_exam_calendar_event(
            $DB->get_record('local_evalia_exams', ['id' => $this->examid]),
            $DB
        ));

        $newcourseid = $this->backup_and_restore(false);

        $exam = $DB->get_record('local_evalia_exams', ['courseid' => $newcourseid], '*', MUST_EXIST);
        $this->assertTrue($DB->record_exists('event', [
            'courseid' => $newcourseid, 'component' => 'local_evalia', 'instance' => $exam->id,
        ]));
        $this->assertSame(
            (int) $DB->get_field('event', 'id', ['courseid' => $newcourseid, 'component' => 'local_evalia']),
            local_evalia_create_exam_calendar_event($exam, $DB)
        );
    }

    /**
     * Deleting the course's content removes EVAL-IA's data; restoring into it brings it back once.
     */
    public function test_delete_contents_and_restore(): void {
        global $DB;
        $this->backup_and_restore(true, $this->course->id);

        $this->assertEquals(1, $DB->count_records('local_evalia_rubrics', ['courseid' => $this->course->id]));
        $this->assertEquals(2, $DB->count_records('local_evalia_question_bank', ['courseid' => $this->course->id]));
        $this->assertEquals(1, $DB->count_records('local_evalia_exams', ['courseid' => $this->course->id]));
        $this->assertEquals(1, $DB->count_records('local_evalia_student_exams'));
        $this->assertEquals(1, $DB->count_records('local_evalia_portfolio_notes'));

        remove_course_contents($this->course->id, false);
        foreach (
            ['local_evalia_rubrics', 'local_evalia_question_bank', 'local_evalia_exams', 'local_evalia_portfolio',
                'local_evalia_portfolio_notes'] as $table
        ) {
            $this->assertFalse($DB->record_exists($table, ['courseid' => $this->course->id]), $table);
        }
        $this->assertEquals(0, $DB->count_records('local_evalia_student_exams'));
        $this->assertEquals(0, $DB->count_records('local_evalia_question_options'));
    }
}
