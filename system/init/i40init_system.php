<?php
/**
 * Initialize the Pines system.
 *
 * @package Pines
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 * @author Hunter Perrin <hunter@sciactive.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
defined('P_RUN') or die('Direct access prohibited');

/**
 * Load a class file.
 *
 * @param string $class_name The class name.
 */
function __autoload($class_name) {
	global $pines;
	// When session_start() tries to recover hooked objects, we need to make
	// sure their equivalent hooked classes exist.
	if (strpos($class_name, 'hook_override_') === 0) {
		$trace = debug_backtrace();
		// But the hook object will check if a hooked class exists before
		// hooking it, so we don't want to create an extra object each time.
		if ($trace[1]['function'] == 'class_exists') {
			if (P_SCRIPT_TIMING) pines_print_time("Checking Class [$class_name]");
			if (P_SCRIPT_TIMING) pines_print_time("Checking Class [$class_name]");
			return;
		}
		if (P_SCRIPT_TIMING) pines_print_time("Preparing Class [$class_name]");
		$new_class = substr($class_name, 14);
		$pines->hook->hook_object($new_class, "{$new_class}->", false);
		if (P_SCRIPT_TIMING) pines_print_time("Preparing Class [$class_name]");
		return;
	}
	if (key_exists($class_name, $pines->class_files)) {
		if (P_SCRIPT_TIMING) pines_print_time("Load [$class_name]");
		include($pines->class_files[$class_name]);
		if (P_SCRIPT_TIMING) pines_print_time("Load [$class_name]");
	}
}

if (P_SCRIPT_TIMING) pines_print_time('Hook $pines');
// Load the hooks for $pines.
$pines->hook->hook_object($pines, '$pines->');
if (P_SCRIPT_TIMING) pines_print_time('Hook $pines');

if (P_SCRIPT_TIMING) pines_print_time('Start Session');
// Now that all classes can be loaded, and system methods can be hooked, we can
// start the session manager. This allows variables to keep their classes over
// sessions.
session_start();

if (P_SCRIPT_TIMING) pines_print_time('Display Pending Notices');
// Check the session for notices and errors awaiting after a redirect.
if ($_SESSION['p_notices']) {
	foreach ((array) $_SESSION['p_notices'] as $_p_cur_notice) {
		$pines->page->notice($_p_cur_notice);
	}
	unset($_SESSION['p_notices'], $_p_cur_notice);
}
if ($_SESSION['p_errors']) {
	foreach ((array) $_SESSION['p_errors'] as $_p_cur_error) {
		$pines->page->error($_p_cur_error);
	}
	unset($_SESSION['p_errors'], $_p_cur_error);
}
if (P_SCRIPT_TIMING) pines_print_time('Display Pending Notices');

if (P_SCRIPT_TIMING) pines_print_time('Start Session');

?>