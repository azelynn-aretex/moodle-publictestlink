<?php

/**
 * @package local_publictestlink
 * @author azi-team
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Toggle: set to true to hide UI, false to show UI for all users
$CFG->local_publictestlink_hide_ui = true;

function local_publictestlink_before_footer() {
	// Note: This function is kept for backwards compatibility.
	// The main logic is now in classes/hook_callbacks.php via the hook system.
	// This function may not be called in newer Moodle versions.
}