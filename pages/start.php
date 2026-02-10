<?php

/**
 * This script deals with starting a new attempt at a quiz.
 * 
 * Most of the code here is directly copied over from the following files in /public/mod/quiz:
 * startattempt.php
 */

require_once('../../../config.php');
require_once('../../../question/engine/lib.php');
require_once('../locallib.php');
require_once('../classes/access_manager.php');
require_once('../classes/session.php');

use core\url as moodle_url;
use core\exception\moodle_exception;
use core\notification;
use mod_quiz\quiz_settings;

// Page parameters
$cmid = required_param('cmid', PARAM_INT);

$session = publictestlink_session::check_session();
if ($session == null) {
    redirect(
        new moodle_url($PLUGIN_URL . '/landing.php', ['cmid' => $cmid]),
        'You are not logged in.', null, notification::ERROR
    );
    return;
}

echo $session->get_user()->get_email();

// // Create the quiz object from the course module ID.
// $quizobj = quiz_settings::create_for_cmid($cmid, null);

// // Navigation buttons in the browser should cause a reload.
// $PAGE->set_cacheable(false);

// $PAGE->set_heading($quizobj->get_course()->fullname);

// // If no questions, throw.
// if (!$quizobj->has_questions()) {
//     if ($quizobj->has_capability('mod/quiz:manage')) {
//         redirect($quizobj->edit_url());
//     } else {
//         throw new moodle_exception('cannotstartnoquestions', $MODULE, new moodle_url($PLUGIN_URL + '/landing.php'));
//     }
// }

// $attempt = publictestlink_attempt::get_or_create($quizobj->get_quiz()->id, )

// // Validate access rules.
// $timenow = time();
// $accessmanager = new publictestlink_access_manager($quizobj, null, $timenow);

// $accessprevents = $accessmanager->prevent_access();
// if (!empty($accessprevents)) {
//     $output = $PAGE->get_renderer('mod_quiz');
//     throw new moodle_exception(
//         'attempterror',
//         $MODULE,
//         new moodle_url($PLUGIN_URL + '/landing.php'),
//         $output->access_messages($messages)
//     );
// }

// use core\exception\moodle_exception;
// use mod_quiz\quiz_settings;

// function quiz_prepare (quiz_settings $quizobj, )


// function quiz_start_new_attempt(quiz_settings $quizobj, question_usage_by_activity $quba, $attempt) {
//     $qubaids = new \mod_quiz\question\qubaids_for_users_attempts($quizobj->get_quizid(), $attempt->userid);
//     $quizobj->preload_questions();

//     $randomfound = false;
//     $slot = 0;
//     $questions = [];
//     $maxmark = [];
//     $page = [];
//     foreach ($quizobj->get_questions(null, false) as $questiondata) {
//         $slot += 1;
//         $maxmark[$slot] = $questiondata->maxmark;
//         $page[$slot] = $questiondata->page;
//         if ($questiondata->status == \core_question\local\bank\question_version_status::QUESTION_STATUS_DRAFT) {
//             throw new moodle_exception('questiondraftonly', 'mod_quiz', '', $questiondata->name);
//         }
//         if ($questiondata->qtype == 'random') {
//             $randomfound = true;
//             continue;
//         }
//         $questions[$slot] = question_bank::load_question($questiondata->questionid, $quizobj->get_quiz()->shuffleanswers);
//     }
// }

// // From quiz ID
// $quizid = required_param('quizid', PARAM_INT);
// $quiz = $DB->get_record('quiz', ['id' => $quizid], '*');

// $cm = get_coursemodule_from_instance('quiz', $quiz->id);

// // Create Question Usage By Activity
// $quba = question_engine::make_questions_usage_by_activity(
//     'local_publictestlink',
//     context_module::instance($cm->id)
// );
// $quba->set_preferred_behaviour('deferredfeedback');

// // Slot then question Loading
// $quizobj = quiz_settings::create($quiz->id);

// foreach ($quizobj->get_questions() as $question) {
//     $quba->add_question(
//         $question,
//         $slot->maxmark
//     );
// }

// $quba->start_all_questions();
// question_engine::save_questions_usage_by_activity($quba);

