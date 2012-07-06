<?php

// $Id: wikiplugin_groupmailcore.php 20677 2009-08-07 18:24:16Z jonnybradley $

// Wiki plugin to display controls etc for GroupMail 
// Started: jonnybradley July 2009

function wikiplugin_groupmailcore_help() {
        return tra("Displays GroupMail functions on a wiki page").":<br />~np~{groupmail_core()}{groupmail}~/np~";
}

function wikiplugin_groupmailcore_info() {
	return array(
		'name' => tra('GroupMailCore'),
		'documentation' => '',
		'description' => tra('Displays GroupMail functions on a wiki page. Usually set up using a plugin alias created by the GroupMail profile.'),
		'prefs' => array('wikiplugin_groupmailcore', 'feature_trackers'),
		//'extraparams' => true,
		'params' => array(
			'fromEmail' => array(
				'required' => true,
				'name' => tra('From Email'),
				'description' => tra('Email address to report.'),
			),
			'trackerId' => array(
				'required' => true,
				'name' => tra('Tracker Id'),
				'description' => tra('Id of GroupMail Logs tracker (set up in alias by profile).'),
			),
			'fromFId' => array(
				'required' => true,
				'name' => tra('From Field Id'),
				'description' => tra('Id of GroupMail Logs tracker field (usually set up in alias by profile).'),
			),
			'operatorFId' => array(
				'required' => true,
				'name' => tra('operator Field Id'),
				'description' => tra('Id of GroupMail Logs tracker field (usually set up in alias by profile).'),
			),
			'subjectFId' => array(
				'required' => true,
				'name' => tra('subject Field Id'),
				'description' => tra('Id of GroupMail Logs tracker field (usually set up in alias by profile).'),
			),
			'messageFId' => array(
				'required' => true,
				'name' => tra('message Field Id'),
				'description' => tra('Id of GroupMail Logs tracker field (usually set up in alias by profile).'),
			),
			'contentFId' => array(
				'required' => true,
				'name' => tra('content Field Id'),
				'description' => tra('Id of GroupMail Logs tracker field (usually set up in alias by profile).'),
			),
			'accountFId' => array(
				'required' => true,
				'name' => tra('account Field Id'),
				'description' => tra('Id of GroupMail Logs tracker field (usually set up in alias by profile).'),
			),
			'datetimeFId' => array(
				'required' => true,
				'name' => tra('datetime Field Id'),
				'description' => tra('Id of GroupMail Logs tracker field (usually set up in alias by profile).'),
			),
		),
	);
}

function wikiplugin_groupmailcore($data, $params) {
	global $tikilib;
	require_once('lib/wiki-plugins/wikiplugin_trackerlist.php');
	
	$trackerparams = array();
	$trackerparams['trackerId'] = $params['trackerId'];
	$trackerparams['fields'] =  $params['fromFId'].':'.$params['operatorFId'].':'.$params['subjectFId'].':'.$params['datetimeFId'];
	$trackerparams['popup'] = $params['fromFId'].':'.$params['contentFId'];
	$trackerparams['filterfield'] = $params['fromFId'].':'.$params['accountFId'];
	$trackerparams['filtervalue'] = $params['fromEmail'].':'.$params['accountName'];
	$trackerparams['stickypopup'] = 'n';
	$trackerparams['showlinks'] ='y';
	$trackerparams['shownbitems'] ='n';
	$trackerparams['showinitials'] ='n';
	$trackerparams['showstatus'] ='n';
	$trackerparams['showcreated'] = 'n';
	$trackerparams['showlastmodif'] = 'n';
	
	$data = wikiplugin_trackerlist('', $trackerparams);
	
	return $data;
}
