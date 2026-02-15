<?php
/**
 * Public grading page - displays quiz grades without requiring authentication.
 * 
 * @package    local_publictestlink
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

use core\exception\moodle_exception;
use core\output\html_writer;
use core\url as moodle_url;
use core\output\url_select;
use core_table\output\html_table;

// Get quiz ID from URL parameter
$cmid = required_param('id', PARAM_INT);

// Get the course module and course information
$cm = get_coursemodule_from_id('quiz', $cmid);
if (!$cm) {
    throw new moodle_exception('invalidcoursemodule');
}

$course = get_course($cm->course);
if (!$course) {
    throw new moodle_exception('invalidcourseid');
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

// Create table with quiz grading columns
$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = array('Email', 'First name', 'Last name', 'Status', 'Started', 'Completed', 'Duration', 'Grade');

// Sample rows for visual purposes (customize with actual quiz data as needed)
$rows = array();
$rows[] = array('shadow1@example.com', 'Shadow', 'One', 'Completed', '2026-02-10 09:00', '2026-02-10 09:20', '00:20', '85%');
$rows[] = array('shadow2@example.com', 'Shadow', 'Two', 'Completed', '2026-02-09 14:10', '2026-02-09 14:30', '00:20', '92%');
$rows[] = array('shadow3@example.com', 'Shadow', 'Three', 'In progress', '2026-02-10 10:05', '-', '-', 'graded');

$table->data = $rows;

echo html_writer::tag('h3', 'Quiz Results');
echo html_writer::table($table);

echo $OUTPUT->footer();
