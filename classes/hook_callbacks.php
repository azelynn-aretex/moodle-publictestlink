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

        \core\notification::add('real', \core\output\notification::NOTIFY_SUCCESS);

        // Check setting from config.php - this is the primary source
        // If not set in config, default to true (hide UI)
        $hide_ui = isset($CFG->local_publictestlink_hide_ui) && $CFG->local_publictestlink_hide_ui;

        // Add current setting as an HTML comment into the footer (do not echo before DOCTYPE).
        $hook->add_html("<!-- PublicTestLink: hide_ui = " . ($hide_ui ? 'true' : 'false') . " -->\n");

        // Check if this is a quiz report page with 'grades' mode selected
        if (self::is_quiz_report_page_with_grades_mode()) {
            $table_html = self::get_quiz_results_table();
            if ($table_html) {
                // Inject the table before footer but after main content
                $hook->add_html($table_html);
            }
        }

        // Check if this is a quiz report page with 'responses' mode selected
        if (self::is_quiz_report_page_with_responses_mode()) {
            $table_html = self::get_quiz_responses_table();
            if ($table_html) {
                // Inject the table before footer but after main content
                $hook->add_html($table_html);
            }
        }

        // Inject JS to add "Public Responses" and "Public Grading" options
        // to the report mode select dropdowns on the quiz report page.
        // Place this before the hide-ui early return so it runs regardless of the setting.
        $js = '<script>(function(){try{if(!/mod\/quiz\/report.php/.test(location.pathname))return;var selects=document.querySelectorAll("select.urlselect");if(!selects.length)return;var u=new URL(location.href);var id=u.searchParams.get("id");if(!id)return;var items=[{mode:"public_responses",text:"Public Responses"},{mode:"public_grading",text:"Public Grading"}];items.forEach(function(it){var opt=document.createElement("option");opt.value="/mod/quiz/report.php?id="+id+"&mode="+it.mode;opt.text=it.text;selects.forEach(function(s){s.appendChild(opt.cloneNode(true));});});}catch(e){console.error(e);}})();</script>';

        $hook->add_html($js);

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

    /**
     * Check if we're on a quiz report page with 'responses' mode selected.
     *
     * @return bool
     */
    private static function is_quiz_report_page_with_responses_mode(): bool {
        global $PAGE;
        
        // Check if this is a quiz report page by page name
        if (strpos($PAGE->pagetype, 'mod-quiz-report') !== 0) {
            return false;
        }
        
        // Check if the report mode is 'responses' (which is the "Responses" form selection)
        // or the public responses mode we inject via JS.
        $mode = optional_param('mode', '', PARAM_ALPHA);

        return $mode === 'responses' || $mode === 'public_responses';
    }

    /**
     * Generate the quiz responses table HTML.
     *
     * @return string HTML table for quiz responses
     */
    private static function get_quiz_responses_table(): string {
        global $PAGE;
        
        // Create table with quiz responses columns
        $table = new \html_table();
        $table->head = array('Email', 'First name', 'Last name', 'Status', 'Started', 'Completed', 'Duration', 'Grade');
        
        // Sample rows for visual purposes (customize with actual quiz data as needed)
        $rows = array();
        $rows[] = array('shadow1@example.com', 'Shadow', 'One', 'Completed', '2026-02-10 09:00', '2026-02-10 09:20', '00:20', '85%');
        $rows[] = array('shadow2@example.com', 'Shadow', 'Two', 'Completed', '2026-02-09 14:10', '2026-02-09 14:30', '00:20', '92%');
        $rows[] = array('shadow3@example.com', 'Shadow', 'Three', 'In progress', '2026-02-10 10:05', '-', '-', '-');
        
        $table->data = $rows;
        
        // Wrap table with styling and heading
        $html = '<div id="publictestlink-responses-table" style="margin: 2rem 0;">' . "\n";
        $html .= '<h3>Quiz Responses</h3>' . "\n";
        $html .= \html_writer::table($table);
        $html .= '</div>' . "\n";
        
        return $html;
    }

    /**
     * Check if we're on a quiz report page with 'grades' (overview) mode selected.
     *
     * @return bool
     */
    private static function is_quiz_report_page_with_grades_mode(): bool {
        global $PAGE;
        
        // Check if this is a quiz report page by page name
        if (strpos($PAGE->pagetype, 'mod-quiz-report') !== 0) {
            return false;
        }
        
        // Check if the report mode is 'overview' (which is the "Grades" form selection)
        // or the public grading mode we inject via JS.
        $mode = optional_param('mode', '', PARAM_ALPHA);

        return $mode === 'overview' || $mode === 'public_grading';
    }

    /**
     * Generate the quiz results table HTML.
     *
     * @return string HTML table for quiz results
     */
    private static function get_quiz_results_table(): string {
        global $PAGE;
        
        // Create table with quiz results columns
        $table = new \html_table();
        $table->head = array('Email', 'First name', 'Last name', 'Status', 'Started', 'Completed', 'Duration', 'Grade');
        
        // Sample rows for visual purposes (customize with actual quiz data as needed)
        $rows = array();
        $rows[] = array('shadow1@example.com', 'Shadow', 'One', 'Completed', '2026-02-10 09:00', '2026-02-10 09:20', '00:20', '85%');
        $rows[] = array('shadow2@example.com', 'Shadow', 'Two', 'Completed', '2026-02-09 14:10', '2026-02-09 14:30', '00:20', '92%');
        $rows[] = array('shadow3@example.com', 'Shadow', 'Three', 'In progress', '2026-02-10 10:05', '-', '-', 'real');
        
        $table->data = $rows;
        
        // Wrap table with styling and heading
        $html = '<div id="publictestlink-results-table" style="margin: 2rem 0;">' . "\n";
        $html .= '<h3>Quiz Results</h3>' . "\n";
        $html .= \html_writer::table($table);
        $html .= '</div>' . "\n";
        
        return $html;
    }
}
