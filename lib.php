<?php
defined('MOODLE_INTERNAL') || die();

/**
 * @package local_publictestlink
 * @author azi-team
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Toggle: set to true to hide UI, false to show UI for all users
$CFG->local_publictestlink_hide_ui = false;

function local_publictestlink_before_footer() {
	// Note: This function is kept for backwards compatibility.
	// The main logic is now in classes/hook_callbacks.php via the hook system.
	// This function may not be called in newer Moodle versions.
}

/**
 * Add "Make quiz public" below the description editor.
 */
function local_publictestlink_coursemodule_standard_elements($formwrapper, $mform) {
    global $PAGE;
    
    $current = $formwrapper->get_current();

    // Only apply to quiz module
    if (empty($current->modulename) || $current->modulename !== 'quiz') {
        return;
    }


    $publicquiz = $mform->createElement(
        'advcheckbox',
        'publicquiz',
        get_string('makequizpublic', 'local_publictestlink')
    );
    $mform->insertElementBefore($publicquiz, 'name');
    
    $mform->setDefault('publicquiz', 0);
    $mform->addHelpButton('publicquiz', 'makequizpublic', 'local_publictestlink');
}

function local_publictestlink_extend_navigation(global_navigation $navigation) {
    global $PAGE, $CFG;

    if ($PAGE->context->contextlevel == CONTEXT_MODULE && $PAGE->cm->modname == 'quiz') {
        $url = new moodle_url('results.php', array('id' => $PAGE->cm->id));
        $node = $PAGE->navigation->add('Public Test Results', $url, navigation_node::TYPE_SETTING);
    }
}