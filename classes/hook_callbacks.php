<?php
namespace local_publictestlink;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/quizcustom.php');

use core\hook\output\before_footer_html_generation;
use core\url as moodle_url;

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
}
