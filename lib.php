<?php
defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/classes/quizcustom.php');

/**
 * Add public quiz settings to the quiz module form.
 *  
 * @param moodleform_mod $formwrapper The moodleform_mod instance
 * @param MoodleQuickForm $mform The form instance
 */
function local_publictestlink_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB;
    
    // Get current module info
    $current = $formwrapper->get_current();
    
    // Check if we're editing a quiz
    if (!isset($current->modulename) || $current->modulename !== 'quiz' || empty($current->instance)) {
        return;
    }

    $quizcustom = $DB->get_record('local_publictestlink_quizcustom', ['quizid' => $current->instance]);
    
    $ispublic = false;
    if ($quizcustom !== null) {
        $ispublic = $quizcustom->is_public();
    }

    
    // Create form element group
    $mform->addElement('header', 'publicquizheader', get_string('publicquizsettings', 'local_publictestlink'));
    $mform->setExpanded('publicquizheader');
    
    // Add checkbox
    $mform->addElement('advcheckbox', 'publicquiz', 
        get_string('makequizpublic', 'local_publictestlink'),
        get_string('makequizpublic_desc', 'local_publictestlink'),
        array('group' => 1),
        array(0, 1)
    );
    
    $mform->setDefault('publicquiz', $ispublic);
    $mform->setType('publicquiz', PARAM_INT);
    $mform->addHelpButton('publicquiz', 'makequizpublic', 'local_publictestlink');
    
    // Try to move it to a more visible position
    if ($mform->elementExists('name')) {
        $mform->insertElementBefore($mform->getElement('publicquizheader'), 'name');
    }
}

/**
 * Process the public quiz setting when quiz form is submitted.
 *
 * @param stdClass $data The form data
 * @param stdClass $course The course
 * @return stdClass Updated form data
 */
function local_publictestlink_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    
    if (!isset($data->modulename) || $data->modulename !== 'quiz' || empty($data->instance)) {
        return $data;
    }
    
    $quizid = $data->instance;
    
    // Get checkbox value
    $ispublic = optional_param('publicquiz', 0, PARAM_INT);
    
    // Also check $data object in case Moodle processed it
    if (isset($data->publicquiz)) {
        $ispublic = (int)$data->publicquiz;
    }

    $quizcustom = publictestlink_quizcustom::from_id($quizid);

    if ($quizcustom === null) {
        $quizcustom = publictestlink_quizcustom::create(
            $quizid, $ispublic
        );
    } else {
        $quizcustom->set_is_public($ispublic);
    }
    
    return $data;
}

/**
 * Delete public quiz records when a quiz is deleted.
 *
 * @param cm_info $cm The course module object
 */
function local_publictestlink_pre_course_module_delete($cm) {
    global $DB;
    
    if ($cm->modname !== 'quiz') {
        return;
    }

    $quizcustom = publictestlink_quizcustom::from_id($cm->id);
    if ($quizcustom === null) return;

    $quizcustom->delete();
}