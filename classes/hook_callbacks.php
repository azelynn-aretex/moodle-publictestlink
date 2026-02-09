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

        // Check setting from config.php - this is the primary source
        // If not set in config, default to true (hide UI)
        $hide_ui = isset($CFG->local_publictestlink_hide_ui) && $CFG->local_publictestlink_hide_ui;

        // Add current setting as an HTML comment into the footer (do not echo before DOCTYPE).
        $hook->add_html("<!-- PublicTestLink: hide_ui = " . ($hide_ui ? 'true' : 'false') . " -->\n");

        // If hiding is disabled, don't inject CSS
        if (!$hide_ui) {
            return;
        }

        // Inject CSS to hide UI elements (navbar, left drawers) while keeping modals and content visible.
        // This allows quiz submission modals and result pages to display properly.
        $css = "<style id=\"local-publictestlink-hide-ui\">\n" .
            ".navbar, #theme_boost-drawers-primary, #theme_boost-drawers-courseindex, .drawer.drawer-left, .region-pre, .region-side-pre, .side-pre, .block-region-side-pre, #course-index, .course-index {display:none !important;}\n" .
            ".region-main, #region-main, .container, .container-fluid, .container-md {width:100% !important; margin:0 auto !important; padding:0 1rem !important;}\n" .
            "body {padding-top:1rem !important;}\n" .
            "#page-header-heading {display:block !important;}\n" .
            "#page-navbar {display:none !important;}\n" .
            ".page-context-header {display:block !important;}\n" .
            ".modal.show, [role=\"dialog\"] {display:block !important; visibility:visible !important;}\n" .
            ".modal-backdrop.show {display:block !important; visibility:visible !important;}\n" .
            ".modal:not(.show) {display:none !important;}\n" .
            ".modal-backdrop:not(.show) {display:none !important;}\n" .
            "</style>\n";

        $hook->add_html($css);
    }
}
