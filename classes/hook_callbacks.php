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
        global $CFG;

        // Enabled by default unless explicitly disabled in config.
        $enabled = true;
        if (isset($CFG->local_publictestlink_hide_ui) && $CFG->local_publictestlink_hide_ui === false) {
            $enabled = false;
        }
        // Backwards compatible global variable fallback: allow disabling via global var.
        if (!empty($GLOBALS['local_publictestlink_hide_ui']) && $GLOBALS['local_publictestlink_hide_ui'] === false) {
            $enabled = false;
        }

        if (empty($enabled)) {
            return;
        }

        if (!function_exists('isloggedin') || !function_exists('isguestuser')) {
            return;
        }

        // Only apply for guests or not-logged-in users ("non-users").
        if (isloggedin() && !isguestuser()) {
            return;
        }

        $css = "<style id=\"local-publictestlink-hide-ui\">\n" .
            ".navbar, .region-pre, .region-side-pre, .side-pre, .block-region-side-pre, #course-index, .course-index, .drawer {display:none !important;}\n" .
            ".moodle-modal-alert, .modal, [role=\"dialog\"] {display:none !important; visibility:hidden !important;}\n" .
            ".modal-backdrop, .modal-open {display:none !important; visibility:hidden !important;}\n" .
            ".region-main, #region-main, .container, .container-fluid, .container-md {width:100% !important; margin:0 auto !important; padding:0 1rem !important;}\n" .
            "body {padding-top:1rem !important;}\n" .
            "#page-header-heading {display:block !important;}\n" .
            "#page-navbar {display:none !important;}\n" .
            ".page-context-header {display:block !important;}\n" .
            "</style>\n";

        $hook->add_html($css);
    }
}
