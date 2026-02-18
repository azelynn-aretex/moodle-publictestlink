<?php
/**
 * Public grading page - displays quiz grades without requiring authentication.
 * 
 * @package    local_publictestlink
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once('../classes/quizcustom.php');
require_once('../classes/attempt.php');

use core\exception\moodle_exception;
use core\output\actions\popup_action;
use core\output\html_writer;
use core\url as moodle_url;
use core\output\url_select;
use core_table\flexible_table;
use mod_quiz\quiz_settings;

// Page parameters
$cmid = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$attemptids = optional_param_array('attemptid', [], PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid);
if (!$cm) {
    throw new moodle_exception('invalidcoursemodule');
}

$course = get_course($cm->course);
if (!$course) {
    throw new moodle_exception('invalidcourseid');
}

$quizid = $cm->instance;
$quizcustom = publictestlink_quizcustom::from_quizid($quizid);

// If quiz isn't public, go back
if ($quizcustom === null || !$quizcustom->get_ispublic()) {
    redirect(new moodle_url('/mod/quiz/report.php', ['id' => $cmid, 'mode' => 'overview']));
}

// Prepare page context early so we can reuse the URL for redirects.
$pageurl = new moodle_url('/local/publictestlink/pages/public_grading.php', ['id' => $cmid]);
$PAGE->set_cm($cm, $course);
$PAGE->set_context(context_module::instance($cmid));
$PAGE->set_course($course);
$PAGE->set_url($pageurl);
$PAGE->set_title('Public Quiz Grades');
$PAGE->set_heading('Public Quiz Grades');
$PAGE->set_pagelayout('report');

// Handle delete action
if ($action === 'delete' && !empty($attemptids) && confirm_sesskey()) {
    $deletedcount = 0;
    $errors = 0;
    
    foreach ($attemptids as $attemptid) {
        $transaction = null;
        try {
            global $DB;

            $attempt = publictestlink_attempt::from_id($attemptid);
            $transaction = $DB->start_delegated_transaction();

            question_engine::delete_questions_usage_by_activity($attempt->get_questionusageid());
            $DB->delete_records('local_publictestlink_quizattempt', ['id' => $attempt->get_id()]);

            $dbman = $DB->get_manager();
            if ($dbman->table_exists('local_publictestlink_users')) {
                $DB->delete_records('local_publictestlink_users', ['attemptid' => $attempt->get_id()]);
            }

            $transaction->allow_commit();
            $deletedcount++;
        } catch (dml_missing_record_exception $e) {
            $errors++;
            debugging("Attempt record not found for ID: $attemptid", DEBUG_DEVELOPER);
        } catch (Throwable $e) {
            if ($transaction instanceof moodle_transaction) {
                $transaction->rollback($e);
            }
            $errors++;
            debugging("Error deleting attempt $attemptid: " . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
    
    // Set notifications
    if ($deletedcount > 0) {
        \core\notification::success(get_string('attemptsdeleted', 'local_publictestlink', $deletedcount));
    }
    if ($errors > 0) {
        \core\notification::warning(get_string('attemptsdeleteerrors', 'local_publictestlink', $errors));
    }
    
    // Redirect to refresh the page and show updated list
    redirect($pageurl);
}

$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

// Get name filters from URL parameters
$firstname_filter = optional_param('firstname', '', PARAM_ALPHA);
$lastname_filter = optional_param('lastname', '', PARAM_ALPHA);

// Get all attempts
$attempts = publictestlink_attempt::get_all_attempts($quizid);

// Filter attempts by first name and last name if filters are set
if (!empty($firstname_filter) || !empty($lastname_filter)) {
    $attempts = array_filter($attempts, function($attempt) use ($firstname_filter, $lastname_filter) {
        $shadowuser = $attempt->get_shadow_user();
        $firstname = strtolower($shadowuser->get_firstname());
        $lastname = strtolower($shadowuser->get_lastname());
        
        if (!empty($firstname_filter) && strpos($firstname, strtolower($firstname_filter)) !== 0) {
            return false;
        }
        if (!empty($lastname_filter) && strpos($lastname, strtolower($lastname_filter)) !== 0) {
            return false;
        }
        return true;
    });
}

echo $OUTPUT->header();

// Jump menu for report navigation.
$navoptions = [
    (new moodle_url('/mod/quiz/report.php', ['id' => $cmid, 'mode' => 'overview']))->out(false) => 'Grades',
    (new moodle_url('/mod/quiz/report.php', ['id' => $cmid, 'mode' => 'responses']))->out(false) => 'Responses',
    (new moodle_url('/mod/quiz/report.php', ['id' => $cmid, 'mode' => 'statistics']))->out(false) => 'Statistics',
    (new moodle_url('/mod/quiz/report.php', ['id' => $cmid, 'mode' => 'grading']))->out(false) => 'Manual grading',
    (new moodle_url('/local/publictestlink/pages/public_grading.php', ['id' => $cmid]))->out(false) => 'Public Link: Grading',
    (new moodle_url('/local/publictestlink/pages/public_responses.php', ['id' => $cmid]))->out(false) => 'Public Link: Responses',
];

$navselect = new url_select(
    $navoptions,
    (new moodle_url('/local/publictestlink/pages/public_grading.php', ['id' => $cmid]))->out(false),
    null,
    'publictestlink_report_nav'
);
$navselect->set_label('Report navigation', ['class' => 'visually-hidden']);
$navselect->class = 'urlselect mb-3';

echo $OUTPUT->render($navselect);

// Display name filter buttons
echo html_writer::start_tag('div', ['class' => 'mb-3']);
echo html_writer::tag('p', html_writer::tag('strong', 'Filter by first name:'), ['class' => 'mb-2']);
echo html_writer::start_tag('div', ['class' => 'd-flex flex-wrap gap-2 mb-3']);
echo html_writer::link(new moodle_url($PAGE->url, ['firstname' => '', 'lastname' => $lastname_filter]), 'All', ['class' => 'btn btn-sm btn-outline-secondary' . ($firstname_filter === '' ? ' active' : '')]);
foreach (range('A', 'Z') as $letter) {
    $params = ['firstname' => $letter, 'lastname' => $lastname_filter];
    echo html_writer::link(new moodle_url($PAGE->url, $params), $letter, ['class' => 'btn btn-sm btn-outline-secondary' . ($firstname_filter === $letter ? ' active' : '')]);
}
echo html_writer::end_tag('div');

echo html_writer::tag('p', html_writer::tag('strong', 'Filter by last name:'), ['class' => 'mb-2']);
echo html_writer::start_tag('div', ['class' => 'd-flex flex-wrap gap-2']);
echo html_writer::link(new moodle_url($PAGE->url, ['firstname' => $firstname_filter, 'lastname' => '']), 'All', ['class' => 'btn btn-sm btn-outline-secondary' . ($lastname_filter === '' ? ' active' : '')]);
foreach (range('A', 'Z') as $letter) {
    $params = ['firstname' => $firstname_filter, 'lastname' => $letter];
    echo html_writer::link(new moodle_url($PAGE->url, $params), $letter, ['class' => 'btn btn-sm btn-outline-secondary' . ($lastname_filter === $letter ? ' active' : '')]);
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Start form for batch operations
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl,
    'id' => 'attempts-bulk-form'
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// Start rendering table
$table = new flexible_table('publictestlink-grading');

// Get slots from the quiz structure
$slots = $quizobj->get_structure()->get_slots();

// Get maximum marks from the quiz
$max_mark = number_format((float)$quiz->grade, 2);

// Generate question columns and headers
$question_columns = [];
$question_headers = [];
$i = 1;
foreach ($slots as $slotnum => $slotdata) {
    $slot = $slotdata->slot;
    $question_columns[] = "q$slot";

    $slot_max_mark = number_format($slotdata->maxmark, 2);
    $question_headers[] = "Q. $i /$slot_max_mark";

    $i++;
}

// Define columns and headers
$table->define_columns([
    'select',
    'fullname',
    'email',
    'status',
    'timestart',
    'timefinish',
    'duration',
    'sumgrades',
    ...$question_columns
]);

$table->define_headers([
    html_writer::checkbox('selectall', 0, false, '', ['id' => 'select-all']),
    'First name / Last name',
    'Email address',
    'Status',
    'Started',
    'Completed',
    'Duration',
    "Grade/$max_mark",
    ...$question_headers
]);

$table->define_baseurl($PAGE->url);
$table->sortable(false);
$table->pageable(false);
$table->collapsible(false);

$table->setup();

// Start writing rows
foreach ($attempts as $attempt) {
    $attemptlink = new moodle_url('/local/publictestlink/pages/reviewteacher.php', ['attemptid' => $attempt->get_id()]);
    $shadowuser = $attempt->get_shadow_user();
    $quba = $attempt->get_quba();
    $row = [];
    
    $row['select'] = html_writer::checkbox('attemptid[]', $attempt->get_id(), false, '', ['class' => 'attempt-select']);
    
    $fullname = "{$shadowuser->get_firstname()} {$shadowuser->get_lastname()}";
    $row['fullname'] = html_writer::tag('p',
        html_writer::link($attemptlink, $fullname, ['class' => 'font-weight-bold']) . '<br>' .
        html_writer::link($attemptlink, get_string('reviewattempt', 'quiz'))
    );
    $row['email'] = $shadowuser->get_email();
    $row['status'] = $attempt->get_state_readable();
    
    $row['timestart'] = userdate($attempt->get_timestart());

    if (!$attempt->is_in_progress()) {
        $row['timefinish'] = userdate($attempt->get_timeend());
        $row['duration'] = format_time($attempt->get_timeend() - $attempt->get_timestart());
    } else {
        $row['timefinish'] = '-';
        $row['duration'] = '-';
    }
    
    $row['sumgrades'] = html_writer::link($attemptlink, number_format($attempt->get_scaled_grade(), 2), ['class' => 'font-weight-bold']);

    // Start writing per-question columns
    foreach ($quba->get_slots() as $slot) {
        $slot_grade = $quba->get_question_mark($slot);
        if ($slot_grade === null) continue;
        
        $fraction = $quba->get_question_fraction($slot);
    
        if ($fraction >= 0.99) {
            $icon = 'i/grade_correct';
        } else if ($fraction > 0) {
            $icon = 'i/grade_partiallycorrect';
        } else {
            $icon = 'i/grade_incorrect';
        }
        
        $icon_html = $OUTPUT->pix_icon($icon, '', 'moodle', ['class' => 'resourcelinkicon']);

        $url = new moodle_url('/local/publictestlink/pages/reviewquestion.php', ['attemptid' => $attempt->get_id(), 'slot' => $slot]);
        $row["q$slot"] = $OUTPUT->action_link(
            $url,
            html_writer::tag('p', $icon_html . ' ' . number_format((float)$slot_grade, 2)),
            new popup_action('click', $url, 'reviewquestion', ['height' => 450, 'width' => 650]),
            ['title' => get_string('reviewresponse', 'quiz')]
        );
    }

    $table->add_data_keyed($row);
}

// End table output
$table->finish_output();

// Bulk delete button with spacing
echo html_writer::start_div('mt-3 mb-4');
echo html_writer::start_div('d-flex align-items-center gap-2');
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'name' => 'submit',
    'value' => 'Delete selected attempts',
    'class' => 'btn btn-danger',
    'onclick' => 'return confirm("Are you sure you want to delete the selected attempts? This action cannot be undone.");'
]);
echo html_writer::empty_tag('input', [
    'type' => 'hidden',
    'name' => 'action',
    'value' => 'delete'
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');

// JavaScript for select all functionality
echo html_writer::script("
document.getElementById('select-all').addEventListener('change', function(e) {
    var checkboxes = document.getElementsByClassName('attempt-select');
    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = e.target.checked;
    }
});
");

echo $OUTPUT->footer();