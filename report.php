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
 * Find duplicate questions report page.
 *
 * @package    qbank_duplicatefinder
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

use qbank_duplicatefinder\helper;

global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;

// Check plugin is enabled.
\core_question\local\bank\helper::require_plugin_enabled('qbank_duplicatefinder');

// Standard question bank page parameters.
$cmid     = optional_param('cmid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);

// Report-specific parameters.
$categoryid       = optional_param('categoryid', 0, PARAM_INT);
$defaultthreshold = (int) get_config('qbank_duplicatefinder', 'defaultthreshold') ?: 70;
$threshold        = optional_param('threshold', $defaultthreshold, PARAM_INT);
$scope            = optional_param('scope', 'category', PARAM_ALPHA);
$dosearch   = optional_param('dosearch', 0, PARAM_BOOL);

if ($dosearch) {
    require_sesskey();
}

// Auth and context setup.

if ($cmid) {
    [$module, $cm] = get_module_from_cmid($cmid);
    require_login($cm->course, false, $cm);
    $thiscontext = context_module::instance($cmid);
} else if ($courseid) {
    require_login($courseid, false);
    $thiscontext = context_course::instance($courseid);
} else {
    require_login();
    $thiscontext = context_system::instance();
}

require_capability('moodle/question:viewall', $thiscontext);

// Page setup.

$baseurl = new \moodle_url('/question/bank/duplicatefinder/report.php', [
    'cmid'     => $cmid,
    'courseid' => $courseid,
]);

$PAGE->set_url($baseurl);
$PAGE->set_title(get_string('duplicatereport', 'qbank_duplicatefinder'));
$PAGE->set_heading($COURSE->fullname ?? get_string('duplicatereport', 'qbank_duplicatefinder'));
$PAGE->set_pagelayout('standard');
$PAGE->activityheader->disable();
$PAGE->set_secondary_active_tab('questionbank');

// Build category options for the form.

$allcategories = $DB->get_records_select(
    'question_categories',
    'contextid = :ctxid',
    ['ctxid' => $thiscontext->id],
    'name ASC',
    'id, name, parent'
);

$categoryoptions = [];
foreach ($allcategories as $cat) {
    $categoryoptions[$cat->id] = format_string($cat->name);
}

// Default to first category if none supplied.
if (!$categoryid && !empty($categoryoptions)) {
    $categoryid = array_key_first($categoryoptions);
}

// Run the duplicate search.

$groups    = [];
$questions = [];
$toomany   = false;

if ($dosearch && $categoryid) {
    if ($scope === 'context') {
        $catids = helper::get_context_category_ids($thiscontext);
    } else {
        $catids = helper::get_category_ids_recursive($categoryid);
    }

    $questions      = helper::load_questions($catids);
    $questioncount  = count($questions);

    if ($questioncount > helper::MAX_QUESTIONS) {
        $toomany   = true;
        $questions = array_slice($questions, 0, helper::MAX_QUESTIONS);
    }

    $threshold = max(1, min(100, $threshold));
    $groups    = helper::find_duplicate_groups($questions, (float) $threshold);
}

// Render.

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('duplicatereport', 'qbank_duplicatefinder'));

// Search form.
?>
<form method="post" action="<?php echo $baseurl->out(false); ?>" class="mb-4">
    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]); ?>
    <?php echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dosearch', 'value' => '1']); ?>

    <div class="row g-3 align-items-end">

        <div class="col-md-4">
            <label for="categoryid" class="form-label fw-semibold">
                <?php echo get_string('categoryid', 'qbank_duplicatefinder'); ?>
            </label>
            <?php
            echo html_writer::select(
                $categoryoptions,
                'categoryid',
                $categoryid,
                false,
                ['id' => 'categoryid', 'class' => 'form-select']
            );
            ?>
        </div>

        <div class="col-md-3">
            <label for="scope" class="form-label fw-semibold">
                <?php echo get_string('scope', 'qbank_duplicatefinder'); ?>
                <?php echo $OUTPUT->help_icon('scope', 'qbank_duplicatefinder'); ?>
            </label>
            <?php
            echo html_writer::select(
                [
                    'category' => get_string('scope_category', 'qbank_duplicatefinder'),
                    'context'  => get_string('scope_context', 'qbank_duplicatefinder'),
                ],
                'scope',
                $scope,
                false,
                ['id' => 'scope', 'class' => 'form-select']
            );
            ?>
        </div>

        <div class="col-md-2">
            <label for="threshold" class="form-label fw-semibold">
                <?php echo get_string('threshold', 'qbank_duplicatefinder'); ?>
                <?php echo $OUTPUT->help_icon('threshold', 'qbank_duplicatefinder'); ?>
            </label>
            <input type="number" id="threshold" name="threshold"
                   value="<?php echo (int) $threshold; ?>"
                   min="1" max="100" class="form-control">
        </div>

        <div class="col-md-2">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-primary w-100">
                <?php echo get_string('search', 'qbank_duplicatefinder'); ?>
            </button>
        </div>

    </div>
</form>
<?php

// Results.
if ($dosearch) {
    if ($toomany) {
        echo $OUTPUT->notification(
            get_string('verylargequestionbank', 'qbank_duplicatefinder', $questioncount),
            'warning'
        );
    }

    if (empty($groups)) {
        echo $OUTPUT->notification(get_string('noduplicates', 'qbank_duplicatefinder'), 'info');
    } else {
        echo $OUTPUT->notification(
            get_string('duplicatesfound', 'qbank_duplicatefinder', count($groups)),
            'success'
        );

        $groupnum = 0;
        foreach ($groups as $group) {
            $groupnum++;
            echo html_writer::tag(
                'h5',
                get_string('similaritygroup', 'qbank_duplicatefinder', $groupnum),
                ['class' => 'mt-4']
            );

            $table              = new html_table();
            $table->attributes  = ['class' => 'table table-bordered table-sm generaltable mb-4'];
            $table->head        = [
                get_string('questionname', 'qbank_duplicatefinder'),
                get_string('questiontype', 'qbank_duplicatefinder'),
                get_string('category', 'qbank_duplicatefinder'),
                get_string('questionversion', 'qbank_duplicatefinder'),
                get_string('questiontext', 'qbank_duplicatefinder'),
                get_string('similarity', 'qbank_duplicatefinder'),
                get_string('actions', 'qbank_duplicatefinder'),
            ];

            foreach ($group as $member) {
                $q   = $member['question'];
                $sim = $member['similarity'];

                // Truncate question text preview.
                $preview = shorten_text(strip_tags($q->questiontext), 120);

                // Edit URL — uses the raw question.id (the specific version row).
                // This opens the question editor for that version.
                $editurl = new moodle_url('/question/bank/editquestion/question.php', [
                    'id'       => $q->id,
                    'courseid' => $courseid ?: ($cmid ? $cm->course : SITEID),
                    'cmid'     => $cmid,
                ]);

                // View-in-bank URL — navigates to the question bank filtered to
                // the category containing this question.
                if ($cmid) {
                    $bankurl = new moodle_url('/question/edit.php', [
                        'cmid' => $cmid,
                        'cat'  => $q->categoryid . ',' . $thiscontext->id,
                    ]);
                } else {
                    $bankurl = new moodle_url('/question/banks.php', [
                        'courseid' => $courseid ?: SITEID,
                    ]);
                }

                $simcell = ($sim === null)
                    ? html_writer::tag(
                        'span',
                        get_string('baseline', 'qbank_duplicatefinder'),
                        ['class' => 'badge bg-secondary']
                    )
                    : html_writer::tag(
                        'span',
                        get_string('percentmatch', 'qbank_duplicatefinder', number_format($sim, 1)),
                        ['class' => self_similarity_badge_class($sim)]
                    );

                $actions = html_writer::link(
                    $editurl,
                    get_string('editquestion', 'qbank_duplicatefinder'),
                    ['class' => 'btn btn-sm btn-outline-secondary me-1', 'target' => '_blank']
                )
                    . html_writer::link(
                        $bankurl,
                        get_string('viewinbank', 'qbank_duplicatefinder'),
                        ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank']
                    );

                $row   = new html_table_row();
                $row->cells = [
                    format_string($q->name),
                    $q->qtype,
                    format_string($q->categoryname),
                    get_string('versionnumber', 'qbank_duplicatefinder', $q->currentversion),
                    $preview,
                    $simcell,
                    $actions,
                ];
                $table->data[] = $row;
            }

            echo html_writer::table($table);
        }
    }
}

echo $OUTPUT->footer();

/**
 * Return a Bootstrap badge class based on the similarity value.
 *
 * @param float $sim Similarity score 0-100.
 * @return string
 * @package    qbank_duplicatefinder
 * @copyright  2026 Marcus Green
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function self_similarity_badge_class(float $sim): string {
    if ($sim >= 95) {
        return 'badge bg-danger';
    } else if ($sim >= 80) {
        return 'badge bg-warning text-dark';
    }
    return 'badge bg-info text-dark';
}
