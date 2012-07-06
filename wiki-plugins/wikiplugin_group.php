<?php
// $Id: wikiplugin_group.php 22214 2009-10-11 16:43:45Z jonnybradley $
// Display wiki text if user is in one of listed groups or group of friends
// Usage:
// {GROUP(groups=>Admins|Developers,friends=>foo|johndoe)}wiki text{GROUP}

function wikiplugin_group_help() {
	$help = tra("Display wiki text if user is in one of listed groups or group of friends").":\n";
	$help.= "~np~<br />{GROUP(groups=>Admins|Developers)}wiki text{GROUP}<br />
	{GROUP(notgroups=>Developers)}wiki text for other groups{GROUP}<br />	
	{GROUP(groups=>Registered,friends=johndoe)}wiki text{ELSE}alternate text for other groups{GROUP}~/np~";
	return $help;
}

function wikiplugin_group_info() {
	return array(
		'name' => tra('Group'),
		'documentation' => 'PluginGroup',
		'description' => tra("Display wiki text if user is in one of listed groups"),
		'body' => tra('Wiki text to display if conditions are met. The body may contain {ELSE}. Text after the marker will be displayed to users not matching the condition.'),
		'prefs' => array('wikiplugin_group'),
		'icon' => 'pics/icons/group.png',
		'filter' => 'wikicontent',
		'params' => array(
			'friends' => array(
				'required' => false,
				'name' => tra('Allowed User Friends'),
				'description' => tra('Pipe separated list of users whose friends are allowed to view the block. ex: admin|johndoe|foo'),
				'filter' => 'username',
			),
			'groups' => array(
				'required' => false,
				'name' => tra('Allowed Groups'),
				'description' => tra('Pipe separated list of groups allowed to view the block. ex: Admins|Developers'),
				'filter' => 'groupname',
			),
			'notgroups' => array(
				'required' => false,
				'name' => tra('Denied Groups'),
				'description' => tra('Pipe separated list of groups denied from viewing the block. ex: Anonymous|Managers'),
				'filter' => 'groupname',
			),
		),
	);
}

function wikiplugin_group($data, $params) {
	global $user, $prefs, $tikilib;
	$dataelse = '';
	if (strpos($data,'{ELSE}')) {
		$dataelse = substr($data,strpos($data,'{ELSE}')+6);
		$data = substr($data,0,strpos($data,'{ELSE}'));
	}

	if (!empty($params['friends']) && $prefs['feature_friends'] == 'y') {
		$friends = explode('|', $params['friends']);
	}
	if (!empty($params['groups'])) {
		$groups = explode('|', $params['groups']);
	}
	if (!empty($params['notgroups'])) {
		$notgroups = explode('|', $params['notgroups']);
	}
	if (empty($friends) && empty($groups) && empty($notgroups)) {
		return '';
	}

	$userGroups = $tikilib->get_user_groups($user);

	if (count($userGroups) > 1) { //take away the anonymous as everybody who is registered is anonymous
		foreach ($userGroups as $key=>$grp) {
			if ($grp == 'Anonymous') {
				$userGroups[$key] = '';
				break;
			}
		}
	}

	if (!empty($friends)) {
		$ok = false;

		foreach ($friends as $key=>$friend) {
		    if ($tikilib->verify_friendship($user, $friend)) {
			    $ok = true;
			    break;
		    }
		}
		if (!$ok)
			return $dataelse;
	}
	if (!empty($groups)) {
		$ok = false;

		foreach ($userGroups as $grp) {
		    if (in_array($grp, $groups)) {
				$ok = true;
				break;
			}
		}
		if (!$ok)
			return $dataelse;
	}
	if (!empty($notgroups)) {
		$ok = true;
		foreach ($userGroups as $grp) {
		    if (in_array($grp, $notgroups)) {
				$ok = false;
				break;
			}
		}
		if (!$ok)
			return $dataelse;
	}
		
	return $data;
}
