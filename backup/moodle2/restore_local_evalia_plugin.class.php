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
 * Restore of EVAL-IA's course data.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restores what backup_local_evalia_plugin saved into the target course.
 *
 * A course has one rubric: when the target course already has its own, the backup's rubric,
 * questions, exams and students' exams are not restored (portfolios and notes still are).
 * Into an existing course Moodle restores this data only with "Overwrite course configuration";
 * with "delete contents first", local_evalia_delete_course_data() has already emptied the course.
 * Users are mapped; data of users that are not restored is skipped. A skipped element never
 * passes its children to another one (restore_structure_step::SKIP_ALL_CHILDREN).
 * Each student's drawn questions and answers are rewritten with the new question ids. Once the
 * gradebook and the calendar are restored, each exam's gradebook column (a manual grade item
 * with idnumber evalia_exam_<examid>) and calendar event are linked to the new exam.
 */
class restore_local_evalia_plugin extends restore_local_plugin {
    /**
     * Declares the paths to process inside the course.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        $elements = [
            'rubric' => '/rubric',
            'rubric_item' => '/rubric/rubric_items/rubric_item',
            'question' => '/questions/question',
            'option' => '/questions/question/options/option',
            'exam' => '/exams/exam',
        ];
        if ($this->get_setting_value('users')) {
            $elements += [
                'student_exam' => '/exams/exam/student_exams/student_exam',
                'portfolio' => '/portfolios/portfolio',
                'portfolio_note' => '/portfolio_notes/portfolio_note',
            ];
        }
        $paths = [];
        foreach ($elements as $name => $path) {
            $paths[] = new restore_path_element($this->get_namefor($name), $this->get_pathfor($path));
        }
        return $paths;
    }

    /**
     * Restored user id, or the user running the restore when the original author is not restored.
     *
     * @param int $olduserid User id in the backup.
     * @return int
     */
    private function author(int $olduserid): int {
        return (int) ($this->get_mappingid('user', $olduserid) ?: $this->task->get_userid());
    }

    /**
     * The course's rubric, unless the target course already has one.
     *
     * @param array $data Element data.
     * @return int|null restore_structure_step::SKIP_ALL_CHILDREN when it is not restored.
     */
    public function process_local_evalia_rubric($data) {
        global $DB;
        $data = (object) $data;
        $courseid = $this->task->get_courseid();
        if ($DB->record_exists('local_evalia_rubrics', ['courseid' => $courseid])) {
            return restore_structure_step::SKIP_ALL_CHILDREN;
        }
        $oldid = $data->id;
        unset($data->id);
        $data->courseid = $courseid;
        $data->created_by = $this->author((int) $data->created_by);
        $this->set_mapping('local_evalia_rubric', $oldid, $DB->insert_record('local_evalia_rubrics', $data));
    }

    /**
     * A topic of the restored rubric.
     *
     * @param array $data Element data.
     */
    public function process_local_evalia_rubric_item($data) {
        global $DB;
        $data = (object) $data;
        $data->rubricid = $this->get_new_parentid('local_evalia_rubric');
        if (!$data->rubricid) {
            return;
        }
        $oldid = $data->id;
        unset($data->id);
        $this->set_mapping('local_evalia_rubric_item', $oldid, $DB->insert_record('local_evalia_rubric_items', $data));
    }

    /**
     * A question of the bank, attached to the restored rubric topic.
     *
     * @param array $data Element data.
     * @return int|null restore_structure_step::SKIP_ALL_CHILDREN when it is not restored.
     */
    public function process_local_evalia_question($data) {
        global $DB;
        $data = (object) $data;
        $data->rubricid = $this->get_mappingid('local_evalia_rubric', $data->rubricid);
        $data->rubric_item_id = $this->get_mappingid('local_evalia_rubric_item', $data->rubric_item_id);
        if (!$data->rubricid || !$data->rubric_item_id) {
            // Also a question whose topic was deleted when the rubric was regenerated. Without
            // SKIP_ALL_CHILDREN its options would attach to the previous question restored.
            return restore_structure_step::SKIP_ALL_CHILDREN;
        }
        $oldid = $data->id;
        unset($data->id);
        $data->courseid = $this->task->get_courseid();
        $this->set_mapping('local_evalia_question', $oldid, $DB->insert_record('local_evalia_question_bank', $data));
    }

    /**
     * An option of a restored multiple-choice question.
     *
     * @param array $data Element data.
     */
    public function process_local_evalia_option($data) {
        global $DB;
        $data = (object) $data;
        $data->questionid = $this->get_new_parentid('local_evalia_question');
        if (!$data->questionid) {
            return;
        }
        unset($data->id);
        $DB->insert_record('local_evalia_question_options', $data);
    }

    /**
     * An exam of the restored rubric. Its dates follow the course's new start date.
     *
     * @param array $data Element data.
     * @return int|null restore_structure_step::SKIP_ALL_CHILDREN when it is not restored.
     */
    public function process_local_evalia_exam($data) {
        global $DB;
        $data = (object) $data;
        $data->rubricid = $this->get_mappingid('local_evalia_rubric', $data->rubricid);
        if (!$data->rubricid) {
            return restore_structure_step::SKIP_ALL_CHILDREN;
        }
        $oldid = $data->id;
        unset($data->id);
        $data->courseid = $this->task->get_courseid();
        $data->created_by = $this->author((int) $data->created_by);
        $data->timeopen = $data->timeopen ? $this->apply_date_offset($data->timeopen) : 0;
        $data->timeclose = $data->timeclose ? $this->apply_date_offset($data->timeclose) : 0;
        $data->grade_itemid = 0;   // Linked in after_restore_course(), once the gradebook is restored.
        $this->set_mapping('local_evalia_exam', $oldid, $DB->insert_record('local_evalia_exams', $data));
    }

    /**
     * A student's exam, with its question ids and answers rewritten for the restored questions.
     *
     * @param array $data Element data.
     */
    public function process_local_evalia_student_exam($data) {
        global $DB;
        $data = (object) $data;
        $data->examid = $this->get_new_parentid('local_evalia_exam');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->examid || !$data->userid) {
            return;
        }
        unset($data->id);

        $newqid = fn($old) => $this->get_mappingid('local_evalia_question', (int) $old);
        $questionids = json_decode($data->question_ids ?? '', true);
        if (is_array($questionids)) {
            $data->question_ids = json_encode(array_values(array_filter(array_map($newqid, $questionids))));
        }
        $answers = json_decode($data->answers ?? '', true);
        if (is_array($answers)) {
            $data->answers = json_encode($this->remap_keys($answers, $newqid), JSON_UNESCAPED_UNICODE);
        }
        $DB->insert_record('local_evalia_student_exams', $data);
    }

    /**
     * Rewrites the question-id keys of an answers array, including the essay evaluations.
     *
     * @param array $answers Answers keyed by question id, plus '__essay_eval__' keyed the same way.
     * @param callable $newqid Maps an old question id to the new one (false if not restored).
     * @return array
     */
    private function remap_keys(array $answers, callable $newqid): array {
        $out = [];
        foreach ($answers as $key => $value) {
            if ($key === '__essay_eval__' && is_array($value)) {
                $out[$key] = $this->remap_keys($value, $newqid);
            } else if (is_numeric($key) && ($new = $newqid($key))) {
                $out[(string) $new] = $value;
            }
        }
        return $out;
    }

    /**
     * A student's portfolio in the course, unless the target course already has one for them.
     *
     * @param array $data Element data.
     */
    public function process_local_evalia_portfolio($data) {
        global $DB;
        $data = (object) $data;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->courseid = $this->task->get_courseid();
        $exists = $DB->record_exists('local_evalia_portfolio', ['userid' => $data->userid, 'courseid' => $data->courseid]);
        if (!$data->userid || $exists) {
            return;
        }
        unset($data->id);
        $DB->insert_record('local_evalia_portfolio', $data);
    }

    /**
     * A teacher's note about a student, kept only when both are restored.
     *
     * @param array $data Element data.
     */
    public function process_local_evalia_portfolio_note($data) {
        global $DB;
        $data = (object) $data;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->created_by = $this->get_mappingid('user', $data->created_by);
        if (!$data->userid || !$data->created_by) {
            return;
        }
        unset($data->id);
        $data->courseid = $this->task->get_courseid();
        $DB->insert_record('local_evalia_portfolio_notes', $data);
    }

    /**
     * Links each restored exam to its gradebook column and its calendar event.
     *
     * The gradebook restores the manual grade items with their idnumber, evalia_exam_<old exam id>.
     * They are renamed in two passes so that an old id equal to another exam's new id never collides.
     * The calendar restore brings the exam's event back without component or instance, so it is
     * found by course, start time and exam name and linked again; otherwise assigning the exam later
     * would create a second event.
     */
    public function after_restore_course() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');
        $courseid = $this->task->get_courseid();
        $exams = $DB->get_records('backup_ids_temp', [
            'backupid' => $this->task->get_restoreid(),
            'itemname' => 'local_evalia_exam',
        ], '', 'id, itemid, newitemid');

        $gradeitems = [];
        foreach ($exams as $map) {
            $gradeitem = grade_item::fetch(['courseid' => $courseid, 'idnumber' => 'evalia_exam_' . $map->itemid]);
            if ($gradeitem) {
                $gradeitem->idnumber = 'evalia_exam_restoring_' . $map->newitemid;
                $gradeitem->update('local/evalia');
                $gradeitems[$map->newitemid] = $gradeitem;
            }
        }
        foreach ($gradeitems as $examid => $gradeitem) {
            $gradeitem->idnumber = 'evalia_exam_' . $examid;
            $gradeitem->update('local/evalia');
            $DB->set_field('local_evalia_exams', 'grade_itemid', $gradeitem->id, ['id' => $examid]);
        }

        foreach ($exams as $map) {
            $exam = $DB->get_record('local_evalia_exams', ['id' => $map->newitemid], 'id, name, timeopen');
            if (!$exam || !$exam->timeopen) {
                continue;
            }
            $select = "courseid = :courseid AND eventtype = 'course' AND modulename = '' AND instance = 0
                       AND (component IS NULL OR component = '') AND timestart = :timestart AND "
                    . $DB->sql_like('name', ':name', false);
            $event = $DB->get_records_select('event', $select, [
                'courseid' => $courseid,
                'timestart' => $exam->timeopen,
                'name' => '%' . $DB->sql_like_escape($exam->name) . '%',
            ], 'id ASC', 'id', 0, 1);
            if ($event) {
                $DB->update_record('event', (object) [
                    'id' => reset($event)->id,
                    'component' => 'local_evalia',
                    'instance' => $exam->id,
                ]);
            }
        }
    }
}
