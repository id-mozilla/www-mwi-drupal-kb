<?php

// Links to another page that provides a translation
// Use:
// {TRANSLATED()}url{TRANSLATED}
//  (flag=>France)       indicates the flag to use. default empty (no flag)
//  (lang=>xx)           iso code of the lang of the translated content
//
function wikiplugin_translated_help() {
	return tra("Links to a translated content").":<br />~np~{TRANSLATED(flag=>France,lang=>fr)}[url] or ((wikiname)) or ((inter:interwiki)) (use wiki syntax){TRANSLATED}~/np~";
}

function wikiplugin_translated_info() {
	return array(
		'name' => tra('Translated'),
		'documentation' => 'PluginTranslated',
		'description' => tra('Links to a translated content'),
		'prefs' => array( 'feature_multilingual', 'wikiplugin_translated' ),
		'body' => tra('[url] or ((wikiname)) or ((inter:interwiki)) (use wiki syntax)'),
		'params' => array(
			'lang' => array(
				'required' => true,
				'name' => tra('Language'),
				'description' => tra('Two letter language code of the language, ex: fr'),
			),
			'flag' => array(
				'required' => false,
				'name' => tra('Flag'),
				'description' => tra('Country name, ex: France'),
			),
		),
	);
}

function wikiplugin_translated($data, $params) {
	extract ($params,EXTR_SKIP);
	$img = '';

	$h = opendir("img/flags/");
	while ($file = readdir($h)) {
		if (substr($file,0,1) != '.' and substr($file,-4,4) == '.gif') {
			$avflags[] = substr($file,0,strlen($file)-4);
		}
	}
	if (isset($flag)) {
		if (in_array($flag,$avflags)) { 
			$img = "<img src='img/flags/$flag.gif' width='18' height='13' vspace='0' hspace='3' alt='$lang' align='baseline' /> "; 
		}
	}

	if (!$img) {
		$img = "( $lang ) ";
	}
	
	if (isset($data)) {
		$back = $img.$data;
	} else {
		$back = "''no data''";
	}

	return $back;
}
