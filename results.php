<?php

require_once('../../config.php');
// $cmid = required_param('id', PARAM_INT);
// $cm = get_coursemodule_from_id('mymodulename', $cmid, 0, false, MUST_EXIST);
// $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

// require_login($course, true, $cm);

$PAGE->requires->css('/local/publictestlink/styles.css');

$PAGE->set_url('/local/publictestlink/results.php');
$PAGE->set_context(context_system::instance());

$PAGE->set_title('Results');
$PAGE->set_heading('Results');
$PAGE->set_pagelayout('standard');

$PAGE->add_body_class('landing-body');

echo $OUTPUT->header();

// Columns mimic Moodle quiz results: email, first name, last name, status, started, completed, duration, grade.
$table = new html_table();
$table->head = array('Email', 'First name', 'Last name', 'Status', 'Started', 'Completed', 'Duration', 'Grade');

// Sample rows for visual purposes.
$rows = array();
$rows[] = array('shadow1@example.com', 'Shadow', 'One', 'Completed', '2026-02-10 09:00', '2026-02-10 09:20', '00:20', '85%');
$rows[] = array('shadow2@example.com', 'Shadow', 'Two', 'Completed', '2026-02-09 14:10', '2026-02-09 14:30', '00:20', '92%');
$rows[] = array('shadow3@example.com', 'Shadow', 'Three', 'In progress', '2026-02-10 10:05', '-', '-', '-');

// This page is visual-only; no login form or submissions are processed here.

$table->data = $rows;

echo html_writer::table($table);

// Back button navigating to the landing page (minimal, clickable).
echo '<div class="results-actions" style="text-align:center;margin-top:1rem;">'
	. '<a class="btn btn-primary" href="" role="button">Back</a>'
	. '</div>';

echo $OUTPUT->footer();