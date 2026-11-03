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
 * Backup of EVAL-IA's course data.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds EVAL-IA's course data to course backups.
 *
 * Always: the rubric with its items, the question bank with its options and the exams.
 * With user data: each student's exam (questions drawn, answers, score), portfolios and the
 * teachers' portfolio notes. The feedback log (messages already sent) is left out.
 */
class backup_local_evalia_plugin extends backup_local_plugin {
    /**
     * Defines the elements attached to the course.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        $plugin = $this->get_plugin_element();
        $evalia = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($evalia);

        $rubric = new backup_nested_element('rubric', ['id'], [
            'name', 'description', 'status', 'created_by', 'timecreated', 'timemodified',
        ]);
        $items = new backup_nested_element('rubric_items');
        $item = new backup_nested_element('rubric_item', ['id'], [
            'topic', 'description', 'difficulty_weight', 'sortorder', 'timecreated',
        ]);
        $questions = new backup_nested_element('questions');
        $question = new backup_nested_element('question', ['id'], [
            'rubricid', 'rubric_item_id', 'question_type', 'stem', 'difficulty', 'topic', 'correct_answer',
            'tolerance', 'source_chunks', 'status', 'timecreated', 'timemodified',
        ]);
        $options = new backup_nested_element('options');
        $option = new backup_nested_element('option', ['id'], ['option_text', 'is_correct', 'feedback', 'sortorder']);
        $exams = new backup_nested_element('exams');
        $exam = new backup_nested_element('exam', ['id'], [
            'rubricid', 'name', 'instructions', 'basic_count', 'medium_count', 'advanced_count', 'topic_coverage',
            'time_limit_min', 'timeopen', 'timeclose', 'status', 'created_by', 'timecreated', 'timemodified',
            'feedback_prompt',
        ]);

        $evalia->add_child($rubric);
        $rubric->add_child($items);
        $items->add_child($item);
        $evalia->add_child($questions);
        $questions->add_child($question);
        $question->add_child($options);
        $options->add_child($option);
        $evalia->add_child($exams);
        $exams->add_child($exam);

        $rubric->set_source_table('local_evalia_rubrics', ['courseid' => backup::VAR_COURSEID]);
        $item->set_source_table('local_evalia_rubric_items', ['rubricid' => backup::VAR_PARENTID], 'sortorder ASC');
        $question->set_source_table('local_evalia_question_bank', ['courseid' => backup::VAR_COURSEID], 'id ASC');
        $option->set_source_table('local_evalia_question_options', ['questionid' => backup::VAR_PARENTID], 'sortorder ASC');
        $exam->set_source_table('local_evalia_exams', ['courseid' => backup::VAR_COURSEID], 'id ASC');

        $rubric->annotate_ids('user', 'created_by');
        $exam->annotate_ids('user', 'created_by');

        if (!$this->get_setting_value('users')) {
            return $plugin;
        }

        $attempts = new backup_nested_element('student_exams');
        $attempt = new backup_nested_element('student_exam', ['id'], [
            'userid', 'question_ids', 'answers', 'score', 'max_score', 'status', 'timecreated', 'timemodified',
            'timesubmitted',
        ]);
        $portfolios = new backup_nested_element('portfolios');
        $portfolio = new backup_nested_element('portfolio', ['id'], [
            'userid', 'total_exams', 'avg_grade', 'last_activity', 'timecreated', 'timemodified',
        ]);
        $notes = new backup_nested_element('portfolio_notes');
        $note = new backup_nested_element('portfolio_note', ['id'], ['userid', 'note_text', 'created_by', 'timecreated']);

        $exam->add_child($attempts);
        $attempts->add_child($attempt);
        $evalia->add_child($portfolios);
        $portfolios->add_child($portfolio);
        $evalia->add_child($notes);
        $notes->add_child($note);

        $attempt->set_source_table('local_evalia_student_exams', ['examid' => backup::VAR_PARENTID]);
        $portfolio->set_source_table('local_evalia_portfolio', ['courseid' => backup::VAR_COURSEID]);
        $note->set_source_table('local_evalia_portfolio_notes', ['courseid' => backup::VAR_COURSEID], 'id ASC');

        $attempt->annotate_ids('user', 'userid');
        $portfolio->annotate_ids('user', 'userid');
        $note->annotate_ids('user', 'userid');
        $note->annotate_ids('user', 'created_by');

        return $plugin;
    }
}
