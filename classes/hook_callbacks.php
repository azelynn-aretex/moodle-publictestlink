<?php
namespace local_publictestlink;

defined('MOODLE_INTERNAL') || die();

use core\hook\output\before_footer_html_generation;

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
        global $CFG, $PAGE;

        if (strpos($PAGE->pagetype, 'mod-quiz-report') === 0) {
            $cmid = optional_param('id', 0, PARAM_INT);
            if ($cmid) {
                $js = '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var selects = document.querySelectorAll("select.urlselect");
                    if (selects.length > 0) {
                        var publicResponses = document.createElement("option");
                        publicResponses.value = "/local/publictestlink/pages/public_responses.php?id=' . $cmid . '";
                        publicResponses.text = "Public Responses";
                        
                        selects.forEach(function(select) {
                            select.appendChild(publicResponses.cloneNode(true));
                            select.appendChild(publicGrading.cloneNode(true));
                        });
                    }
                });
                </script>';
                
                $hook->add_html($js);
            }
        }
    }
}
