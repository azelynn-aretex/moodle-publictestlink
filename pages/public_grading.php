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
$firstname_filter = optional_param('tifirst', '', PARAM_ALPHA);
$lastname_filter  = optional_param('tilast', '', PARAM_ALPHA);

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

// Set up page context
$PAGE->set_cm($cm, $course);
$PAGE->set_context(context_module::instance($cmid));
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/publictestlink/pages/public_grading.php', ['id' => $cmid]));
$PAGE->set_title('Public Quiz Grades');
$PAGE->set_heading('Public Quiz Grades');
$PAGE->set_pagelayout('report');

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
// echo html_writer::tag('p', html_writer::tag('strong', 'Filter by first name:'), ['class' => 'mb-2']);
// echo html_writer::start_tag('div', ['class' => 'd-flex flex-wrap gap-2 mb-3']);
// echo html_writer::link(new moodle_url($PAGE->url, ['firstname' => '', 'lastname' => $lastname_filter]), 'All', ['class' => 'btn btn-sm btn-outline-secondary' . ($firstname_filter === '' ? ' active' : '')]);
// foreach (range('A', 'Z') as $letter) {
//     $params = ['firstname' => $letter, 'lastname' => $lastname_filter];
//     echo html_writer::link(new moodle_url($PAGE->url, $params), $letter, ['class' => 'btn btn-sm btn-outline-secondary' . ($firstname_filter === $letter ? ' active' : '')]);
// }
// echo html_writer::end_tag('div');

// echo html_writer::tag('p', html_writer::tag('strong', 'Filter by last name:'), ['class' => 'mb-2']);
// echo html_writer::start_tag('div', ['class' => 'd-flex flex-wrap gap-2']);
// echo html_writer::link(new moodle_url($PAGE->url, ['firstname' => $firstname_filter, 'lastname' => '']), 'All', ['class' => 'btn btn-sm btn-outline-secondary' . ($lastname_filter === '' ? ' active' : '')]);
// foreach (range('A', 'Z') as $letter) {
//     $params = ['firstname' => $firstname_filter, 'lastname' => $letter];
//     echo html_writer::link(new moodle_url($PAGE->url, $params), $letter, ['class' => 'btn btn-sm btn-outline-secondary' . ($lastname_filter === $letter ? ' active' : '')]);
// }
// echo html_writer::end_tag('div');

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
$table->pageable(false);
$table->collapsible(false);

$table->setup();

// Start writing rows
foreach ($attempts as $attempt) {
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
