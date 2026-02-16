<?php
require_once('../../../config.php');
require_once('../locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once('../classes/attempt.php');
require_once('../classes/session.php');
require_once('../classes/access_manager.php');
require_once('../classes/link_token.php');
require_once('../classes/user_header_writer.php');

use core\exception\moodle_exception;
use core\url as moodle_url;
use core\output\html_writer;
use core\notification;
use mod_quiz\quiz_settings;

/** @var moodle_page $PAGE */


$PAGE->set_cacheable(false);

$token = required_param('token', PARAM_ALPHANUMEXT);

$linktoken = publictestlink_link_token::require_token($token);

$session = publictestlink_session::check_session();
if ($session === null) {
    redirect(new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token]));
    return;
}

$quizid = $linktoken->get_quizid();
$quizobj = quiz_settings::create($quizid);
$quiz = $quizobj->get_quiz();

$cm = get_coursemodule_from_id('quiz', $quizobj->get_cmid(), 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
if (!$context) throw new moodle_exception('invalidcontext', $MODULE);

$shadowuserid = $session->get_user()->get_id();
$attempt = publictestlink_attempt::require_attempt($quizid, $shadowuserid);

$timenow = time();
$accessmanager = new publictestlink_access_manager($quizobj, $timenow, $session->get_user(), $attempt);


$reasons = $accessmanager->get_formatted_reasons();
if ($reasons !== null) {
    redirect('/', $reasons, null, notification::ERROR);
    return;
}

if (
    $attempt->get_shadow_user()->get_id() !== $session->get_user()->get_id() ||
    !$attempt->is_in_progress()
) {
    redirect(
        new moodle_url($PLUGIN_URL . '/landing.php', ['token' => $token])
    );
    return;
}

$endtime = null;
$timeleft = null;
if ($quiz->timelimit > 0) {
    $endtime = $attempt->get_timestart() + $quiz->timelimit;
    $timeleft = max(0, $endtime - $timenow);
}

$quba = $attempt->get_quba();
$quba->set_preferred_behaviour($quiz->preferredbehaviour);


$PAGE->requires->js_init_code("
    if (!window.M) { window.M = {}; }
    if (!M.cfg) { M.cfg = {}; }
    M.cfg.quiz = {
        timeleft: {$timeleft},
        endtime: {$endtime},
        timerwarning: 60
    };
");


$PAGE->set_url($PLUGIN_URL . '/attempt.php', ['token' => $token]);
$PAGE->requires->css('/local/publictestlink/styles.css');
$PAGE->add_body_class('landing-body');

$PAGE->set_pagelayout('incourse');
$PAGE->set_blocks_editing_capability(false);
$PAGE->set_secondary_navigation(false);
$PAGE->set_show_course_index(false);
$PAGE->set_title($quiz->name);
$PAGE->set_heading($course->fullname);

$PAGE->set_course($quizobj->get_course());
$PAGE->set_cm($cm);
$PAGE->set_context($context);

$PAGE->navbar->ignore_active(true);
foreach ($PAGE->navbar->get_items() as $node) {
    $node->action = null;
}

$displayoptions = new question_display_options();
$displayoptions->marks = question_display_options::MARK_AND_MAX;
$displayoptions->feedback = question_display_options::HIDDEN;
$displayoptions->generalfeedback = question_display_options::HIDDEN;
$displayoptions->rightanswer = question_display_options::HIDDEN;
$displayoptions->readonly = false;
$displayoptions->flags = question_display_options::HIDDEN; // TODO add flags

echo $OUTPUT->header();

if ($timeleft !== null) {
    function format_time_left(int $seconds): string {
        $minutes = intdiv($seconds, 60);
        $seconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    echo html_writer::start_div('mb-2', ['id' => 'quiz-timer-wrapper', 'style' => 'display: flex;']);
        echo html_writer::start_div('quiz-timer-inner py-1 px-2 ms-auto', ['id' => 'quiz-timer', 'role' => 'timer']);
            echo 'Time left: ';
            echo html_writer::tag('span', '', ['id' => 'quiz-time-left']);
        echo html_writer::end_div();
    echo html_writer::end_div();

    $PAGE->requires->js_init_code(<<<JS
        (function() {
            const el = document.getElementById('quiz-time-left');
            if (!el) return;

            const end = parseInt($endtime, 10) * 1000;

            let timer = null;

            function tick() {
                const now = Date.now();
                let s = Math.max(0, Math.floor((end - now) / 1000));
                const m = Math.floor(s / 60);
                const r = s % 60;
                el.textContent = m + ':' + String(r).padStart(2, '0');

                if (s <= 0) {
                    clearInterval(timer);
                    document.getElementById('responseform')?.submit();
                }
            }

            tick();
            timer = setInterval(tick, 1000);
        })();
    JS);
}

user_header_writer::write($session);

echo html_writer::start_tag('form', [
    'method' => 'post',
    'id'     => 'responseform',
    'action' => new moodle_url($PLUGIN_URL . '/process.php', ['token' => $token]),
]);
    echo html_writer::start_div('publictestlink-attempt-wrapper');
        $i = 1;
        foreach ($quba->get_slots() as $slot) {
            echo $quba->render_question($slot, $displayoptions, $i++);
        }

        echo html_writer::start_div('ptl-attempt-actions d-flex flex-row gap-2 w-full justify-content-end');
            echo html_writer::tag('button', get_string('endtest', 'quiz'), [
                'type'  => 'submit',
                'class' => 'btn btn-primary',
            ]);
        echo html_writer::end_div();
    echo html_writer::end_div();
echo html_writer::end_tag('form');
echo $OUTPUT->footer();