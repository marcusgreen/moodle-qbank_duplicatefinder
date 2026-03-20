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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit tests for qbank_duplicatefinder\helper::normalise().
 *
 * @package    qbank_duplicatefinder
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \qbank_duplicatefinder\helper::normalise
 */
namespace qbank_duplicatefinder;

use advanced_testcase;

/**
 * Tests for helper::normalise().
 *
 * @coversDefaultClass \qbank_duplicatefinder\helper
 */
final class helper_test extends advanced_testcase {
    /**
     * Data provider for test_normalise().
     *
     * @return array[]
     */
    public static function normalise_provider(): array {
        return [
            'plain text unchanged' => [
                'input'    => 'hello world',
                'expected' => 'hello world',
            ],
            'strips html tags' => [
                'input'    => '<p>Hello <strong>World</strong></p>',
                'expected' => 'hello world',
            ],
            'decodes html entity amp' => [
                'input'    => 'cats &amp; dogs',
                'expected' => 'cats & dogs',
            ],
            'decodes html entity lt gt' => [
                'input'    => '&lt;html&gt;',
                'expected' => '<html>',
            ],
            'decodes numeric entity' => [
                'input'    => '&#169;',
                'expected' => '©',
            ],
            'lowercases ascii' => [
                'input'    => 'UPPER CASE TEXT',
                'expected' => 'upper case text',
            ],
            'lowercases unicode' => [
                'input'    => 'Ångström',
                'expected' => 'ångström',
            ],
            'collapses multiple spaces' => [
                'input'    => 'too   many   spaces',
                'expected' => 'too many spaces',
            ],
            'collapses tabs and newlines' => [
                'input'    => "line one\n\tline two",
                'expected' => 'line one line two',
            ],
            'trims leading and trailing whitespace' => [
                'input'    => '   trimmed   ',
                'expected' => 'trimmed',
            ],
            'empty string' => [
                'input'    => '',
                'expected' => '',
            ],
            'html with entities and mixed case' => [
                'input'    => '<p>What is 2 &gt; 1?</p>',
                'expected' => 'what is 2 > 1?',
            ],
            'nested html tags' => [
                'input'    => '<div><ul><li>Item one</li><li>Item two</li></ul></div>',
                'expected' => 'item oneitem two',
            ],
            'whitespace only becomes empty' => [
                'input'    => "   \n\t  ",
                'expected' => '',
            ],
        ];
    }

    /**
     * Tests that normalise() correctly strips HTML, decodes entities,
     * lowercases, and collapses whitespace.
     *
     * @covers \qbank_duplicatefinder\helper::normalise
     * @dataProvider normalise_provider
     * @param string $input
     * @param string $expected
     */
    public function test_normalise(string $input, string $expected): void {
        $this->assertEquals($expected, helper::normalise($input));
    }

    // Helpers for find_duplicate_groups tests.

    /**
     * Build a minimal question stdClass with just an id and questiontext.
     *
     * @param int $id The question ID.
     * @param string $text The question text.
     * @return \stdClass
     */
    private function make_question(int $id, string $text): \stdClass {
        $q = new \stdClass();
        $q->id           = $id;
        $q->questiontext = $text;
        return $q;
    }

    // Find_duplicate_groups tests.

    /**
     * Fewer than two questions always returns an empty array.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_empty_input(): void {
        $this->assertSame([], helper::find_duplicate_groups([], 80.0));
    }

    /**
     * A single question cannot form a duplicate group.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_single_question(): void {
        $questions = [$this->make_question(1, 'What is the capital of France?')];
        $this->assertSame([], helper::find_duplicate_groups($questions, 80.0));
    }

    /**
     * Two completely different questions produce no groups.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_no_duplicates(): void {
        $questions = [
            $this->make_question(1, 'What is the capital of France?'),
            $this->make_question(2, 'How many legs does a spider have?'),
        ];
        $result = helper::find_duplicate_groups($questions, 80.0);
        $this->assertSame([], $result);
    }

    /**
     * Two identical questions are grouped together.
     * The reference member has similarity === null; the second has a float.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_identical_questions(): void {
        $text = 'What is the boiling point of water?';
        $questions = [
            $this->make_question(1, $text),
            $this->make_question(2, $text),
        ];
        $result = helper::find_duplicate_groups($questions, 80.0);

        $this->assertCount(1, $result, 'Expect exactly one duplicate group');
        $group = $result[0];
        $this->assertCount(2, $group);

        // First member is the reference (similarity null).
        $this->assertNull($group[0]['similarity']);
        $this->assertSame(1, $group[0]['question']->id);

        // Second member has a similarity score of 100.
        $this->assertSame(100.0, $group[1]['similarity']);
        $this->assertSame(2, $group[1]['question']->id);
    }

    /**
     * Two near-identical questions (only capitalisation differs) are grouped
     * because normalise() lowercases before comparison.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_case_insensitive_match(): void {
        $questions = [
            $this->make_question(1, 'What is the boiling point of water?'),
            $this->make_question(2, 'WHAT IS THE BOILING POINT OF WATER?'),
        ];
        $result = helper::find_duplicate_groups($questions, 80.0);

        $this->assertCount(1, $result);
        $this->assertNull($result[0][0]['similarity']);
        $this->assertSame(100.0, $result[0][1]['similarity']);
    }

    /**
     * HTML formatting differences are ignored because normalise() strips tags.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_html_formatting_ignored(): void {
        $questions = [
            $this->make_question(1, '<p>What is the boiling point of water?</p>'),
            $this->make_question(2, 'What is the boiling point of water?'),
        ];
        $result = helper::find_duplicate_groups($questions, 80.0);

        $this->assertCount(1, $result);
        $this->assertSame(100.0, $result[0][1]['similarity']);
    }

    /**
     * Questions below the threshold are not grouped.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_below_threshold(): void {
        $questions = [
            $this->make_question(1, 'What is the boiling point of water?'),
            $this->make_question(2, 'How many planets are in the solar system?'),
        ];
        // Similarity will be well below 80 %.
        $result = helper::find_duplicate_groups($questions, 80.0);
        $this->assertSame([], $result);
    }

    /**
     * A low threshold catches loosely related questions.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_low_threshold_groups_more(): void {
        $questions = [
            $this->make_question(1, 'What is the boiling point of water in Celsius?'),
            $this->make_question(2, 'What is the freezing point of water in Celsius?'),
        ];
        // At a very low threshold these should cluster together.
        $result = helper::find_duplicate_groups($questions, 10.0);
        $this->assertCount(1, $result);
    }

    /**
     * Three questions: two are near-duplicates, the third is unrelated.
     * Only one group of two should be returned.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_one_group_among_three(): void {
        $questions = [
            $this->make_question(1, 'What is the boiling point of water?'),
            $this->make_question(2, 'What is the boiling point of water in Celsius?'),
            $this->make_question(3, 'How many sides does a hexagon have?'),
        ];
        $result = helper::find_duplicate_groups($questions, 80.0);

        $this->assertCount(1, $result);
        $ids = array_column(array_column($result[0], 'question'), 'id');
        $this->assertContains(1, $ids);
        $this->assertContains(2, $ids);
        $this->assertNotContains(3, $ids);
    }

    /**
     * Three mutually similar questions form a single group of three (transitive
     * union-find clustering).
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_transitive_cluster(): void {
        // All three are identical so they will all be merged into one group.
        $text = 'What is the speed of light?';
        $questions = [
            $this->make_question(1, $text),
            $this->make_question(2, $text),
            $this->make_question(3, $text),
        ];
        $result = helper::find_duplicate_groups($questions, 80.0);

        $this->assertCount(1, $result);
        $this->assertCount(3, $result[0]);
    }

    /**
     * Two independent duplicate pairs produce two separate groups.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_two_independent_groups(): void {
        $questions = [
            $this->make_question(1, 'What is the boiling point of water?'),
            $this->make_question(2, 'What is the boiling point of water in Celsius?'),
            $this->make_question(3, 'How many sides does a triangle have?'),
            $this->make_question(4, 'How many sides does a triangle possess?'),
        ];
        $result = helper::find_duplicate_groups($questions, 80.0);

        $this->assertCount(2, $result, 'Expected two independent duplicate groups');
    }

    /**
     * Within a group the first entry always has similarity === null and
     * remaining entries are sorted in descending similarity order.
     *
     * @covers \qbank_duplicatefinder\helper::find_duplicate_groups
     */
    public function test_find_duplicate_groups_sort_order(): void {
        // Q1 = reference; Q2 identical; Q3 slightly different.
        $questions = [
            $this->make_question(1, 'What is the boiling point of water?'),
            $this->make_question(2, 'What is the boiling point of water?'),
            $this->make_question(3, 'What is the boiling point of water in Celsius?'),
        ];
        $result = helper::find_duplicate_groups($questions, 50.0);

        $this->assertCount(1, $result);
        $group = $result[0];

        // Reference is first with null similarity.
        $this->assertNull($group[0]['similarity']);

        // Remaining entries are in descending similarity order.
        for ($i = 1; $i < count($group) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $group[$i + 1]['similarity'],
                $group[$i]['similarity'],
                'Group members should be sorted by descending similarity'
            );
        }
    }
}
