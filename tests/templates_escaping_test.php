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
 * The teacher panel templates escape the data they show (MMR-463 #3).
 *
 * evalia_teacher.js renders rubric topics, AI-generated question stems, names, notes and source labels
 * with these templates. Moodle renders a template the same way in PHP and in JavaScript, so rendering
 * them here with markup in every text field checks that none of it reaches the page as HTML.
 *
 * @package    local_evalia
 * @category   test
 * @copyright  2026 Schaller & Ponce <dev@schaller-ponce.com.ar>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_evalia;

/**
 * Escaping tests for the teacher panel templates.
 */
final class templates_escaping_test extends \advanced_testcase {
    /** @var string Markup that would run script if it were inserted as HTML. */
    private const PAYLOAD = '"><img src=x onerror=alert(1)>';

    /**
     * Templates and contexts with the payload in every text field the web services provide.
     *
     * @return array
     */
    public static function template_provider(): array {
        $p = self::PAYLOAD;
        return [
            'rubric_status' => ['rubric_status', ['active' => true, 'name' => $p]],
            'rubric_items editable' => ['rubric_items', ['editable' => true, 'items' => [[
                'num' => 1, 'id' => 1, 'topic' => $p, 'description' => $p, 'islow' => true,
            ]]]],
            'rubric_items read-only' => ['rubric_items', ['editable' => false, 'items' => [[
                'num' => 1, 'id' => 1, 'topic' => $p, 'description' => $p, 'ishigh' => true,
            ]]]],
            'source_list' => ['source_list', ['sources' => [['eid' => 'evalia-src-1', 'id' => $p, 'icon' => 'x',
                'label' => $p]]]],
            'question_bank' => ['question_bank', ['questions' => [['id' => 1, 'topic' => $p, 'stem' => $p,
                'typelabel' => 'MC', 'difficultyclass' => 'bg-info', 'difficultylabel' => 'Basic',
                'statusclass' => 'bg-secondary', 'statuslabel' => 'Draft']]]],
            'generate_questions_form' => ['generate_questions_form', ['items' => [['id' => 1, 'topic' => $p,
                'weightlabel' => 'Low']], 'defaultcount' => 5]],
            'student_exams' => ['student_exams', ['students' => [['fullname' => $p, 'statusclass' => 'bg-success',
                'statuslabel' => 'Graded', 'hasscore' => true, 'score' => '8.0', 'examurl' => 'https://example.edu/x',
                'student_examid' => 1, 'isgraded' => true]]]],
            'portfolio_students' => ['portfolio_students', ['students' => [['userid' => 1, 'fullname' => $p,
                'active' => false, 'hasportfolio' => true, 'totalexams' => 1, 'avggrade' => '7.0']]]],
            'portfolio_exams' => ['portfolio_exams', ['exams' => [['name' => $p, 'statusclass' => 'bg-success',
                'statuslabel' => $p, 'graded' => true, 'scoreclass' => 'text-success', 'score' => '1.0',
                'maxscore' => '2.0', 'date' => '1/1/2026', 'totalquestions' => 2, 'viewurl' => '']]]],
            'portfolio_notes' => ['portfolio_notes', ['notes' => [['author' => $p, 'date' => '1/1/2026',
                'text' => $p]]]],
            'exam_stats_failed' => ['exam_stats_failed', ['empty' => false, 'questions' => [['num' => 1,
                'topic' => $p, 'difficulty' => $p, 'difficultyclass' => 'evalia-diff-other', 'pct' => 50,
                'stem' => $p]]]],
        ];
    }

    /**
     * No template lets the payload through as markup.
     *
     * @dataProvider template_provider
     * @param string $template Template name in local_evalia.
     * @param array $context Template context.
     * @coversNothing
     */
    public function test_template_escapes_data(string $template, array $context): void {
        global $OUTPUT, $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/local/evalia/teacher.php');
        $PAGE->set_context(\context_system::instance());

        $html = $OUTPUT->render_from_template('local_evalia/' . $template, $context);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
    }
}
