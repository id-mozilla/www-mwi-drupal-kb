<?php
/* Tiki-Wiki Countdown plugin
 *
 * This is an example plugin to indicate a countdown to a date.
 * Plugins are called using the syntax
 * {COUNTDOWN(end=>string date)} to reach all targets!{COUNTDOWN}
 * Name must be in uppercase!
 * params is in the form: name=>value,name2=>value2 (don't use quotes!)
 * If the plugin doesn't use params use {NAME()}content{NAME}
 *
 * The function will receive the plugin content in $data and the params
 * in the asociative array $params (using extract to pull the arguments
 * as in the example is a good practice)
 * The function returns some text that will replace the content in the
 * wiki page.
 */
function wikiplugin_countdown_help() {
	return tra("Example").":<br />~np~{COUNTDOWN(enddate=>April 1 2004[,locatetime=>on])}".tra("text")."{COUNTDOWN}~/np~";
}

function wikiplugin_countdown_info() {
	return array(
		'name' => tra('Countdown'),
		'documentation' => 'PluginCountdown',
		'description' => tra('Displays a countdown from now until the specified date.'),
		'prefs' => array('wikiplugin_countdown'),
		'icon' => 'pics/icons/clock.png',
		'body' => tra('Text to append to the countdown.'),
		'params' => array(
			'enddate' => array(
				'required' => true,
				'name' => tra('End date'),
				'description' => tra('Target date. Multiple formats accepted.'),
			),
			'locatetime' => array(
				'required' => false,
				'name' => tra('Locate Time'),
				'description' => tra('on|off'),
			),
		),
	);
}

function wikiplugin_countdown($data, $params) {
	global $tikilib, $tikidate;
	extract ($params,EXTR_SKIP);

	if (!isset($enddate)) {
		return ("<b>COUNTDOWN: Missing 'enddate' parameter for plugin</b><br />");
	}

	// Parse the string and cancel the server environment's timezone adjustment
	$then = strtotime($enddate) + date('Z');

	// Calculate the real UTC timestamp
	//  (the string was specified using the user timezone)
	$tikidate->setTZbyID($tikilib->get_display_timezone());
	$tikidate->setDate($then);
	$tikidate->convertTZByID('UTC');
	$then = $tikidate->getTime();

	$difference = $then - $tikilib->now;
	$num = $difference/86400;
	$days = intval($num);
	$num2 = ($num - $days)*24;
	$hours = intval($num2);
	$num3 = ($num2 - $hours)*60;
	$mins = intval($num3);
	$num4 = ($num3 - $mins)*60;
	$secs = intval($num4);
	$ret = "$days ".tra("days").", $hours ".tra("hours").", $mins ".tra("minutes")." ".tra("and")." $secs ".tra("seconds")." $data";
	return $ret;
}
