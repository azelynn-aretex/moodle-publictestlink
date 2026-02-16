<?php
namespace local_publictestlink;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');
require_once(__DIR__ . '/quizcustom.php');
require_once(__DIR__ . '/link_token.php');

use core\hook\output\before_footer_html_generation;
use core\output\html_writer;
use core\url as moodle_url;
use mod_quiz\quiz_settings;

/**
 * Hook callbacks for local_publictestlink.
 */
class hook_callbacks {
    /**
     * Inject CSS to hide header/sidebar for guests or non-logged-in users.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;

        if (strpos($PAGE->pagetype, 'mod-quiz-report') !== 0) return;


        $cmid = optional_param('id', 0, PARAM_INT);
        if (!$cmid) return;

        $cm = get_coursemodule_from_id('quiz', $cmid);
        if (!$cm) return;

        $quizid = $cm->instance;
        $quizcustom = \publictestlink_quizcustom::from_quizid($quizid);
        if ($quizcustom === null || !$quizcustom->get_ispublic()) return;

        $js = '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var selects = document.querySelectorAll("select.urlselect");
            if (selects.length > 0) {
                var publicGrading = document.createElement("option");
                publicGrading.value = "/local/publictestlink/pages/public_grading.php?id=' . $cmid . '";
                publicGrading.text = "Public Link: Grading";

                var publicResponses = document.createElement("option");
                publicResponses.value = "/local/publictestlink/pages/public_responses.php?id=' . $cmid . '";
                publicResponses.text = "Public Link: Responses";
                
                selects.forEach(function(select) {
                    select.appendChild(publicGrading.cloneNode(true));
                    select.appendChild(publicResponses.cloneNode(true));
                });
            }
        });
        </script>';
        
        $hook->add_html($js);
    }

    public static function quiz_tab(before_footer_html_generation $hook): void {
        global $PAGE;

        if (strpos($PAGE->pagetype, 'mod-quiz-view') !== 0) return;

        $cmid = optional_param('id', 0, PARAM_INT);
        if (!$cmid) return;

        $cm = get_coursemodule_from_id('quiz', $cmid);
        if (!$cm) return;

        $quizid = $cm->instance;
        $quizcustom = \publictestlink_quizcustom::from_quizid($quizid);
        if ($quizcustom === null || !$quizcustom->get_ispublic()) return;
        $quizobj = quiz_settings::create($quizid);
        $quiz = $quizobj->get_quiz();

        $linktoken = \publictestlink_link_token::ensure_for_quiz($quizid);

        $publicurl = new moodle_url(\PLUGIN_URL . '/landing.php', [
            'token' => $linktoken->get_token()
        ]);

        $inputid  = 'publicquizlinkinput_' . $quiz->id;
        $buttonid = 'publicquizlinkbtn_' . $quiz->id;

        $linkhtml = html_writer::start_div('d-flex flex-row align-items-center gap-2 mb-3');
        $linkhtml .= html_writer::tag('p', get_string('public_url', 'local_publictestlink'), ['class' => 'text-nowrap m-0']);
        $linkhtml .= html_writer::empty_tag('input', [
            'type'     => 'text',
            'class'    => 'w-100 form-control',
            'id'       => $inputid,
            'value'    => $publicurl->out(false),
            'readonly' => 'readonly',
        ]);
        $linkhtml .= html_writer::tag('button', get_string('public_url_copy', 'local_publictestlink'), [
            'type' => 'button',
            'class' => 'btn btn-primary text-nowrap',
            'id' => $buttonid,
        ]);
        $linkhtml .= html_writer::end_div();

        // Optional: Add copy JS inline
        $linkhtml .= html_writer::script("
            (function() {
                const btn = document.getElementById('$buttonid');
                const input = document.getElementById('$inputid');
                if (!btn || !input) return;
                btn.addEventListener('click', function() {
                    input.select();
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(input.value);
                    } else {
                        document.execCommand('copy');
                    }
                    alert('Copied!');
                });
            })();
        ");

        $linkhtml;

        $hook->add_html($linkhtml);
    }
}
