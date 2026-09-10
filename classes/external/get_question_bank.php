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
 * WS: get_question_bank — returns questions with optional filters, including answer options.
 *
 * @package    local_evalia
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;


/**
 * Get_question_bank.
 */
class get_question_bank extends external_api {
    /**
     * Define the parameters for this web service.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid'   => new external_value(PARAM_INT, 'Course ID'),
            'topic'      => new external_value(PARAM_TEXT, 'Filter by topic (empty = all)', VALUE_DEFAULT, ''),
            'difficulty' => new external_value(PARAM_TEXT, 'Filter by difficulty (empty = all)', VALUE_DEFAULT, ''),
            'status'     => new external_value(PARAM_TEXT, 'Filter by status (empty = all)', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the web service.
     */
    public static function execute(int $courseid, string $topic, string $difficulty, string $status): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid'   => $courseid,
            'topic'      => $topic,
            'difficulty' => $difficulty,
            'status'     => $status,
        ]);

        $context = \context_course::instance($params['courseid']);
        self::validate_context($context);
        require_capability('local/evalia:manage', $context);

        // Build WHERE conditions.
        $where  = 'courseid = :courseid';
        $qparams = ['courseid' => $params['courseid']];

        if ($params['topic'] !== '') {
            $where .= ' AND topic = :topic';
            $qparams['topic'] = $params['topic'];
        }
        if ($params['difficulty'] !== '') {
            $where .= ' AND difficulty = :difficulty';
            $qparams['difficulty'] = $params['difficulty'];
        }
        if ($params['status'] !== '') {
            $where .= ' AND status = :status';
            $qparams['status'] = $params['status'];
        }

        $questions = $DB->get_records_select(
            'evalia_question_bank',
            $where,
            $qparams,
            'timecreated DESC',
            'id, question_type, stem, difficulty, topic, status, correct_answer'
        );

        if (empty($questions)) {
            return ['total' => 0, 'questions' => []];
        }

        // Load options for all questions in one query.
        $qids = array_keys($questions);
        [$insql, $inparams] = $DB->get_in_or_equal($qids, SQL_PARAMS_NAMED, 'qid');
        $alloptions = $DB->get_records_select(
            'evalia_question_options',
            "questionid $insql",
            $inparams,
            'questionid ASC, sortorder ASC',
            'id, questionid, option_text, is_correct, feedback, sortorder'
        );

        // Group options by questionid.
        $optionsbyq = [];
        foreach ($alloptions as $opt) {
            $optionsbyq[$opt->questionid][] = [
                'id'          => (int) $opt->id,
                'option_text' => $opt->option_text,
                'is_correct'  => (bool) $opt->is_correct,
                'feedback'    => $opt->feedback ?? '',
                'sortorder'   => (int) $opt->sortorder,
            ];
        }

        $result = [];
        foreach ($questions as $q) {
            $result[] = [
                'id'             => (int) $q->id,
                'question_type'  => $q->question_type,
                'stem'           => $q->stem,
                'difficulty'     => $q->difficulty,
                'topic'          => $q->topic,
                'status'         => $q->status,
                'correct_answer' => $q->correct_answer ?? '',
                'options'        => $optionsbyq[$q->id] ?? [],
            ];
        }

        return ['total' => count($result), 'questions' => $result];
    }

    /**
     * Define the return structure for this web service.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total'     => new external_value(PARAM_INT, 'Total questions matching filters'),
            'questions' => new external_multiple_structure(
                new external_single_structure([
                    'id'             => new external_value(PARAM_INT, 'Question ID'),
                    'question_type'  => new external_value(PARAM_TEXT, 'Type'),
                    'stem'           => new external_value(PARAM_TEXT, 'Question text'),
                    'difficulty'     => new external_value(PARAM_TEXT, 'basic|medium|advanced'),
                    'topic'          => new external_value(PARAM_TEXT, 'Topic'),
                    'status'         => new external_value(PARAM_TEXT, 'draft|approved|rejected'),
                    'correct_answer' => new external_value(PARAM_TEXT, 'Correct answer (plain text)', VALUE_OPTIONAL),
                    'options'        => new external_multiple_structure(
                        new external_single_structure([
                            'id'          => new external_value(PARAM_INT, 'Option ID'),
                            'option_text' => new external_value(PARAM_TEXT, 'Option text'),
                            'is_correct'  => new external_value(PARAM_BOOL, 'Is correct answer'),
                            'feedback'    => new external_value(PARAM_TEXT, 'Feedback text', VALUE_OPTIONAL),
                            'sortorder'   => new external_value(PARAM_INT, 'Display order'),
                        ]),
                        'Answer options',
                        VALUE_DEFAULT,
                        []
                    ),
                ]),
                'Questions',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }
}
