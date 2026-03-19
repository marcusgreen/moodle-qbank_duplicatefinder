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

namespace qbank_duplicatefinder;

use core_question\local\bank\navigation_node_base;

/**
 * Navigation node for the Find Duplicates report tab.
 *
 * @package    qbank_duplicatefinder
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class navigation_node extends navigation_node_base {
    /**
     * Return the display title for this navigation tab.
     *
     * @return string
     */
    public function get_navigation_title(): string {
        return get_string('duplicatefinder', 'qbank_duplicatefinder');
    }

    /**
     * Return the unique key used to identify this navigation node.
     *
     * @return string
     */
    public function get_navigation_key(): string {
        return 'duplicatefinder';
    }

    /**
     * Return the URL for the report page.
     *
     * @return \moodle_url
     */
    public function get_navigation_url(): \moodle_url {
        return new \moodle_url('/question/bank/duplicatefinder/report.php');
    }

    /**
     * Return the capabilities required to see this navigation node.
     *
     * @return array|null
     */
    public function get_navigation_capabilities(): ?array {
        return ['moodle/question:viewall'];
    }
}
