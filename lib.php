<?php

/**
 * @package local_publictestlink
 * @author azi-team
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Toggle: set to true to hide navbar and sidebar for guests/non-users.
$local_publictestlink_hide_ui = true;

function local_publictestlink_before_footer() {
	global $local_publictestlink_hide_ui;

	if (empty($local_publictestlink_hide_ui)) {
		return;
	}

	if (!function_exists('isloggedin') || !function_exists('isguestuser')) {
		return;
	}

	// Only apply for guests or not-logged-in users ("non-users").
	if (isloggedin() && !isguestuser()) {
		return;
	}

	// Inject CSS to hide navbar/sidebar and confirm modal.
	echo "<style id=\"local-publictestlink-hide-ui\">\n" .
		 ".navbar, .region-pre, .region-side-pre, .side-pre, .block-region-side-pre, #course-index, .course-index, .drawer {display:none !important;}\n" .
		 ".moodle-modal-alert, .modal, [role=\"dialog\"] {display:none !important; visibility:hidden !important;}\n" .
		 ".modal-backdrop, .modal-open {display:none !important; visibility:hidden !important;}\n" .
		 ".region-main, #region-main, .container, .container-fluid, .container-md {width:100% !important; margin:0 auto !important; padding:0 1rem !important;}\n" .
		 "body {padding-top:1rem !important;}\n" .
		 "#page-header-heading {display:block !important;}\n" .
		 "#page-navbar {display:none !important;}\n" .
		 ".page-context-header {display:block !important;}\n" .
		 "</style>\n";
}