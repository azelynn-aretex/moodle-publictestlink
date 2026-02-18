<?php

require_once($CFG->libdir . '/formslib.php');

class results_display_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'pageheader', get_string('displayoptions', 'quiz'));
        $mform->setExpanded('pageheader', true);

        $mform->addElement('text', 'pagesize', get_string('pagesize', 'quiz'));
        $mform->setType('pagesize', PARAM_INT);
        $mform->setDefault('pagesize', 20);

        $mform->addElement('hidden', 'token');
        $mform->setType('token', PARAM_INT);
        $mform->setDefault('token', $this->_customdata['token']);

        $mform->addElement('submit', 'submitbutton', get_string('showreport', 'quiz'));
    }

    // Optional: validation
    function validation($data, $files) {
        if (empty($data['pagesize']) || $data['pagesize'] <= 0) {
            $data['pagesize'] = 20;
        }
        return [];
    }
}