<?php
/**
 * Public grading page - displays quiz grades without requiring authentication.
 * 
 * @package    local_publictestlink
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once('../forms/results_display.php');
require_once('../classes/quizcustom.php');
require_once('../classes/attempt.php');
require_once('../classes/filter_writer.php');

use core\exception\moodle_exception;
use core\output\actions\popup_action;
use core\output\html_writer;
use core\url as moodle_url;
use core\output\url_select;
use core_table\flexible_table;
use mod_quiz\quiz_settings;

// Page parameters
$cmid = required_param('id', PARAM_INT);
$firstname_filter = optional_param('tifirst', null, PARAM_ALPHA);
$lastname_filter  = optional_param('tilast', null, PARAM_ALPHA);
$pagesize  = optional_param('pagesize', 20, PARAM_INT);
$page  = optional_param('page', 0, PARAM_INT);

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

$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

// Get all attempts
$attempts = publictestlink_attempt::get_all_attempts($quizid, $firstname_filter, $lastname_filter);

// Calculate pagination
$totalattempts = count($attempts);
$offset = $page * $pagesize;
$attempts_paged = array_slice($attempts, $offset, $pagesize);


// Set up page context
$PAGE->set_cm($cm, $course);
$PAGE->set_context(context_module::instance($cmid));
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/publictestlink/pages/public_grading.php', [
    'id' => $cmid,
    'tifirst' => $firstname_filter,
    'tilast' => $lastname_filter,
    'pagesize' => $pagesize
]));
$PAGE->set_title('Public Quiz Grades');
$PAGE->set_heading('Public Quiz Grades');
$PAGE->set_pagelayout('report');


// Create display options form
$mform = new results_display_form($PAGE->url);
$data = $mform->get_data();

if ($data !== null) {
    $pagesize = ($data->pagesize > 0) ? $data->pagesize : 20;

    $currentparams = $PAGE->url->params();
    $currentparams['pagesize'] = $pagesize;

    redirect(new moodle_url($PAGE->url, $currentparams));
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


// Display pagination controls
$mform->display();


// Display name filter buttons
echo html_writer::start_tag('div', ['class' => 'mb-3']);

echo filter_writer::render_name_filters('tifirst', 'First name');
echo filter_writer::render_name_filters('tilast', 'Last name');

echo html_writer::end_tag('div');

// Start rendering table
$table = new flexible_table('publictestlink-responses');

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
$table->collapsible(false);

$table->pageable(true);
$table->pagesize($pagesize, $totalattempts);

$table->setup();

// Start writing rows
foreach ($attempts_paged as $attempt) {
	$attemptlink = new moodle_url(PLUGIN_URL . '/reviewteacher.php', ['attemptid' => $attempt->get_id()]);
	$shadowuser = $attempt->get_shadow_user();
	$quba = $attempt->get_quba();
    $row = [];
    
    $row['checkbox'] = html_writer::checkbox('attemptid[]', $attempt->get_id(), false);
    
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
	}
    
    $row['sumgrades'] = html_writer::link($attemptlink, number_format($attempt->get_scaled_grade(), 2), ['class' => 'font-weight-bold']);

	// Start writing per-question columns
    foreach ($quba->get_slots() as $slot) {
        $slot_grade = $quba->get_question_mark($slot);
		if ($slot_grade === null) continue;
        
        $fraction = $quba->get_question_fraction($slot);
	
		if ($fraction >= 0.99) {
			$icon = 'i/grade_correct';
			$class = 'text-success';
		} else if ($fraction > 0) {
			$icon = 'i/grade_partiallycorrect';
			$class = 'text-success';
		} else {
			$icon = 'i/grade_incorrect';
			$class = 'text-danger';
		}
        
        $icon_html = $OUTPUT->pix_icon($icon, '', 'moodle', ['class' => 'resourcelinkicon']);

		$url = new moodle_url(PLUGIN_URL . '/reviewquestion.php', ['attemptid' => $attempt->get_id(), 'slot' => $slot]);
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

echo $OUTPUT->footer();
