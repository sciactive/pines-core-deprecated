<?php
/**
 * Common functions used in Pines.
 * 
 * These can be overriden by components, which is why this file starts with i01.
 * It's loaded along with the components' init files.
 *
 * @package Pines
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 * @author Hunter Perrin <hunter@sciactive.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
defined('P_RUN') or die('Direct access prohibited');

/*
 * These are very rudamentary security functions. If you are worried about
 * security, you should consider replacing them.
 */
if (!function_exists('clean_checkbox')) {
	/**
	 * Cleans an HTML checkbox's name, so that it can be parsed correctly by
	 * PHP.
	 *
	 * @param string $name Name to clean.
	 * @return string The cleaned name.
	 */
	function clean_checkbox($name) {
		return str_replace('.', 'dot', urlencode($name));
	}
}

if (!function_exists('clean_filename')) {
	/**
	 * Cleans a filename, so it doesn't refer to any parent directories.
	 *
	 * @param string $filename Filename to clean.
	 * @return string The cleaned filename.
	 */
	function clean_filename($filename) {
		return str_replace('..', 'fail-danger-dont-use-hack-attempt', $filename);
	}
}

if (!function_exists('is_clean_filename')) {
	/**
	 * Checks whether a filename refers to any parent directories.
	 *
	 * @param string $filename Filename to check.
	 * @return bool
	 */
	function is_clean_filename($filename) {
		if ( strpos($filename, '..') === false ) {
			return true;
		} else {
			return false;
		}
	}
}

/*
 * Some shortcuts, to make life easier.
 */

/**
 * Shortcut to $pines->action().
 *
 * @param string $component The component in which the action resides.
 * @param string $action The action to run.
 * @return mixed The value returned by the action, or 'error_404' if it doesn't exist.
 */
function action($component, $action) {
	global $pines;
	return $pines->action($component, $action);
}

/**
 * Shortcut to $pines->redirect().
 *
 * @uses pines::redirect() Forwards parameters and returns the result.
 * @param string $url The URL to send the user to.
 * @param int $code The HTTP code to send to the browser.
 */
function redirect($url, $code = 303) {
	global $pines;
	$pines->redirect($url, $code);
}

/**
 * Shortcut to $pines->page->error().
 *
 * @uses page::error() Forwards parameters and returns the result.
 * @param string $text Information to display to the user.
 */
function pines_error($text) {
	global $pines;
	$pines->page->error($text);
}

/**
 * Shortcut to $pines->page->notice().
 *
 * @uses page::notice() Forwards parameters and returns the result.
 * @param string $text Information to display to the user.
 */
function pines_notice($text) {
	global $pines;
	$pines->page->notice($text);
}

/**
 * Shortcut to $pines->user_manager->gatekeeper().
 *
 * The gatekeeper() function should be defined in whatever component is taking
 * over user management. gatekeeper() without arguments should return false if
 * the current user is not logged in, true if he is. If he is, gatekeeper()
 * should take an "ability" argument which returns true if the user has the
 * required permissions. gatekeeper() should also take a "user" argument to
 * check whether a different user has an ability. This helps user managers use a
 * "login" ability, which can be used to disable an account.
 *
 * @uses user_manager_inerface::gatekeeper() Forwards parameters and returns the result.
 * @param string $ability The ability to provide.
 * @param user $user The user to provide.
 * @return bool The result is returned if there is a user management component, otherwise it returns true.
 */
function gatekeeper($ability = null, $user = null) {
	global $pines;
	if (!isset($pines->user_manager))
		return true;
	return $pines->user_manager->gatekeeper($ability, $user);
}

/**
 * Shortcut to $pines->user_manager->punt_user().
 *
 * The punt_user() function should be defined in whatever component is taking
 * over user management. punt_user() must always end the execution of the
 * script. If there is no user management component, the user is directed to the
 * home page and the script terminates.
 *
 * @uses user_manager_inerface::punt_user() Forwards parameters and returns the result.
 * @param string $message An optional message to display to the user.
 * @param string $url An optional URL to be included in the query data of the redirection url.
 * @return bool The result is returned if there is a user management component, otherwise it returns true.
 */
function punt_user($message = null, $url = null) {
	global $pines;
	if (!isset($pines->user_manager)) {
		header('Location: '.pines_url());
		exit($message);
	}
	$pines->user_manager->punt_user($message, $url);
}

/**
 * Shortcut to $pines->depend->check().
 *
 * @uses depend::check() Forwards parameters and returns the result.
 * @return bool The result is returned from the dependency checker.
 */
function pines_depend() {
	global $pines;
	if (!isset($pines->depend))
		return true;
	$args = func_get_args();
	return call_user_func_array(array($pines->depend, 'check'), $args);
}

/**
 * Shortcut to $pines->log_manager->log().
 *
 * @uses log_manager_interface::log() Forwards parameters and returns the result.
 * @return bool The result is returned if there is a log management component, otherwise it returns true.
 */
function pines_log() {
	global $pines;
	if (!isset($pines->log_manager))
		return true;
	$args = func_get_args();
	return call_user_func_array(array($pines->log_manager, 'log'), $args);
}

/**
 * Shortcut to $pines->template->url().
 *
 * @uses template::url() Forwards parameters and returns the result.
 * @return bool The result is returned if there is a template, otherwise it returns null.
 */
function pines_url() {
	global $pines;
	if (!isset($pines->template))
		return null;
	$args = func_get_args();
	return call_user_func_array(array($pines->template, 'url'), $args);
}

/*
 * Some simple formatting functions.
 */

/**
 * Formats a date using the DateTime class.
 *
 * @param int $timestamp The timestamp to format.
 * @param DateTimeZone|string|null $timezone The timezone to use for formatting. Defaults to date_default_timezone_get().
 * @param string $format The format to use.
 * @return string The formatted date.
 */
function pines_date_format($timestamp, $timezone = null, $format = 'Y-m-d H:i T') {
	$date = new DateTime(gmdate('c', (int) $timestamp));
	if (isset($timezone)) {
		if (is_string($timezone))
			$timezone = new DateTimeZone($timezone);
		$date->setTimezone($timezone);
	} else {
		$date->setTimezone(new DateTimeZone(date_default_timezone_get()));
	}
	return $date->format($format);
}

/**
 * Formats a phone number.
 *
 * @param string $phone The phone number to format.
 * @return string The formatted phone number.
 */
function pines_phone_format($phone) {
	if (!isset($phone))
		return '';
	$return = preg_replace('/\D*0?1?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d)?\D*(\d*)\D*/', '($1$2$3) $4$5$6-$7$8$9$10 x$11', (string) $phone);
	return preg_replace('/\D*$/', '', $return);
}

?>