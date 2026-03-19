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
 * Language strings for qbank_duplicatefinder.
 *
 * @package    qbank_duplicatefinder
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['actions'] = 'Actions';
$string['baseline'] = 'Baseline';
$string['category'] = 'Category';
$string['categoryid'] = 'Category';
$string['duplicatefinder'] = 'Find duplicates';
$string['duplicatereport'] = 'Duplicate questions report';
$string['duplicatesfound'] = 'Found {$a} group(s) of potential duplicate questions.';
$string['editquestion'] = 'Edit';
$string['noduplicates'] = 'No potential duplicate questions found with the current settings.';
$string['percentmatch'] = '{$a}%';
$string['pluginname'] = 'Find duplicate questions';
$string['privacy:metadata'] = 'The Find duplicate questions plugin does not store any personal data.';
$string['questionname'] = 'Question name';
$string['questiontext'] = 'Question text (preview)';
$string['questiontype'] = 'Type';
$string['questionversion'] = 'Version';
$string['scope'] = 'Scope';
$string['scope_category'] = 'Current category only';
$string['scope_context'] = 'All categories in this context';
$string['scope_help'] = 'Choose whether to search for duplicates within the current category only, or across all categories in this context.';
$string['search'] = 'Find duplicates';
$string['similarity'] = 'Similarity to first';
$string['similaritygroup'] = 'Duplicate group {$a}';
$string['threshold'] = 'Similarity threshold (%)';
$string['threshold_help'] = 'Questions with similarity at or above this percentage will be flagged as potential duplicates. Lower values find more potential duplicates; higher values only flag near-identical questions.';
$string['versionnumber'] = 'v{$a}';
$string['verylargequestionbank'] = 'There are {$a} questions in scope. For performance, only the first 500 will be compared.';
$string['viewinbank'] = 'View in bank';
