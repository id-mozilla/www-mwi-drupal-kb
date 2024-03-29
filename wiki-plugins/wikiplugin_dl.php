<?php

function wikiplugin_dl_help() {
	return tra("Creates a definition list").":<br />~np~{DL()}".tra("term").":".tra("definition")."{DL}~/np~ - ''".tra("one definition per line")."''";
}

function wikiplugin_dl_info() {
	return array(
		'name' => tra('Definition List'),
		'documentation' => 'PluginDL',
		'description' => tra("Creates a definition list"),
		'prefs' => array('wikiplugin_dl'),
		'body' => tra('One entry per line. Each line is in "Term: Definition" format.'),
		'params' => array(
		),
	);
}

function wikiplugin_dl($data, $params) {
	global $tikilib;

	global $replacement;
	if (isset($param))
		extract ($params,EXTR_SKIP);
	$result = '<dl>';
	$lines = split("\n", $data);

	foreach ($lines as $line) {
		$parts = explode(":", $line);

		if (isset($parts[0]) && isset($parts[1])) {
			$result .= '<dt>' . $parts[0] . '</dt><dd>' . $parts[1] . '</dd>';
		}
	}

	$result .= '</dl>';
	return $result;
}
