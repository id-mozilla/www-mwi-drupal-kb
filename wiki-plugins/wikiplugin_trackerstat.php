<?php

// $Id: /cvsroot/tikiwiki/tiki/lib/wiki-plugins/wikiplugin_trackerstat.php,v 1.14.2.5 2007-12-18 23:03:15 sylvieg Exp $
/* to have some statistiques about a tracker
 * will returns a table with for each tracker field, the list of values and the number of times the values occurs
 * trackerId = the id of the tracker
 * fields = the iof of the fields you wnat the stat - the fields must be public
 * show_percent : optionnal - to show a percent
 * show_bar : optionnal to show a bar(length 100 pixels)
 * status : optionnal to filter on the status ( a combinaison of letters c:close, o:open, p:pending)
 */
function wikiplugin_trackerstat_help() {
	$help = tra("Displays some stat of a tracker content, fields are indicated with numeric ids.").":\n";
	$help.= "~np~{TRACKERSTAT(trackerId=>1,fields=>2:4:5,show_percent=>y,show_bar=>n,status=>o|c|p|op|oc|pc|opc,show_link=n)}Title{TRACKERSTAT}~/np~";
	return $help;
}

function wikiplugin_trackerstat_info() {
	return array(
		'name' => tra('Tracker Stats'),
		'documentation' => 'PluginTrackerStat',
		'description' => tra("Displays some stat of a tracker content, fields are indicated with numeric ids."),
		'prefs' => array( 'feature_trackers', 'wikiplugin_trackerstat' ),
		'body' => tra('Title'),
		'icon' => 'pics/icons/database_lightning.png',
		'params' => array(
			'trackerId' => array(
				'required' => true,
				'name' => tra('Tracker ID'),
				'description' => tra('Tracker ID'),
			),
			'fields' => array(
				'required' => true,
				'name' => tra('Fields'),
				'description' => tra('Colon-separated list of field IDs to be displayed. Example: 2:4:5'),
			),
			'show_percent' => array(
				'required' => false,
				'name' => tra('Show Percentage'),
				'description' => 'y|n',
			),
			'show_bar' => array(
				'required' => false,
				'name' => tra('Show Bar'),
				'description' => 'y|n',
			),
			'status' => array(
				'required' => false,
				'name' => tra('Status Filter'),
				'description' => 'o|p|c|op|oc|pc|opc'.' '.tra('Which item status to list. o = open, p = pending, c = closed.'),
			),
			'show_link' => array(
				'required' => false,
				'name' => tra('Show link to tiki-view_tracker'),
				'description' => 'y|n',
			),
		),
	);
}

function wikiplugin_trackerstat($data, $params) {
	global $smarty, $prefs, $tiki_p_admin_trackers, $trklib, $tikilib;
	include_once('lib/trackers/trackerlib.php');
	extract ($params,EXTR_SKIP);

	if ($prefs['feature_trackers'] != 'y' || !isset($trackerId) || !($tracker_info = $trklib->get_tracker($trackerId))) {
		return $smarty->fetch("wiki-plugins/error_tracker.tpl");
	}
	$perms = Perms::get(array('type'=>'tracker', 'object'=>$trackerId));
	if (!$perms->view_trackers) {
		return tra('Permission denied');
	}

	if (!isset($status)) {
		$status = 'o';
	} elseif (!$trklib->valid_status($status)) {
		return "invalid status";
	}
	if (isset($show_percent) && $show_percent == 'y') {
		$average = 'y';
		$smarty->assign('show_percent', 'y');
	} else {
		$smarty->assign('show_percent', 'n');
	}
	if (isset($show_bar) && $show_bar == 'y') {
		$average = 'y';
		$smarty->assign('show_bar', 'y');
	} else {
		$smarty->assign('show_bar', 'n');
	}
	if (isset($show_link) && $show_link == 'y') {
		$smarty->assign('show_link', 'y');
	} else {
		$smarty->assign('show_link', 'n');
	}
	
	$allFields = $trklib->list_tracker_fields($trackerId, 0, -1, 'position_asc', '');
	for ($iUser = count($allFields['data']) - 1; $iUser >= 0; $iUser--) {
		if ($allFields['data'][$iUser]['type'] == 'u') { // this tracker has a user field - can look for the value the user sets
			break;
		}
	}
	if ($iUser <= -1) {
		for ($iIp = count($allFields['data']) - 1; $iIp >= 0; $iIp--) {
			if ($allFields['data'][$iIp]['type'] == 'I') { // this tracker has a IP field - can look for the value the user sets
				break;
			}
		}
	}
	if (!empty($fields)) {
		$listFields = split(':',$fields);
	} else {
		foreach($allFields['data'] as $f) {
			$listFields[] = $f['fieldId'];
		}
	}

	if ($t = $trklib->get_tracker_options($trackerId)) {
		$tracker_info = array_merge($tracker_info, $t);
	}

	$status_types = $trklib->status_types();

	foreach ($listFields as $fieldId) {
		for ($i = count($allFields['data']) - 1; $i >= 0; $i--) {
			if ($allFields['data'][$i]['fieldId'] == $fieldId) {
				break;
			}
		}
		if ($i < 0 ) {
			return tra("incorrect fieldId")." ".$fieldId;
		}
		if ($allFields["data"][$i]['type'] == 'u' || $allFields["data"][$i]['type'] == 'I' || $allFields["data"][$i]['type'] == 's') {
			continue;
		}
		if (!($allFields["data"][$i]['isHidden'] == 'n' || $allFields["data"][$i]['isHidden'] == 'p' || ($allFields["data"][$i]['isHidden'] == 'y' && $tiki_p_admin_trackers == 'y'))) {
			continue;
		}
		if ($allFields["data"][$i]['type'] == 'e') {
			global $categlib; include_once('lib/categories/categlib.php');
			$listCategs = $categlib->get_child_categories($allFields["data"][$i]['options']);
			$itemId = $trklib->get_user_item($trackerId, $tracker_info);
			for ($j = 0; $j < count($listCategs); ++$j) {
				$objects = $categlib->get_category_objects($listCategs[$j]['categId'], "tracker $trackerId");
				if ($status == 'opc' || $tracker_info['showStatus'] == 'n') {
					$v[$j]['count'] = count($objects);
				} else {
					$v[$j]['count'] = 0;
					foreach ($objects as $o) {
						$s = $trklib->get_item_info($o['itemId']);
						if (strstr($status, $s['status']) !== false)
							++$v[$j]['count'];
					}
				}
				$v[$j]['value'] = $listCategs[$j]['name'];
				foreach($objects as $o) {
					if ($o['itemId'] == $itemId) {
						$v[$j]['me'] = 'y';
						break;
					}
				}
				$v[$j]['href'] = "trackerId=$trackerId&amp;filterfield=$fieldId&amp;filtervalue[$fieldId][]=".$listCategs[$j]['categId'];
			}
		} else	if ($allFields["data"][$i]['type'] == 'h') {//header
			$stat['name'] = $allFields["data"][$i]['name'];
			$stat['values'] = array();
			$stats[] = $stat;
			continue;
		} else {
			if ($iUser >= 0) {
				global $user;
				$userValues = $trklib->get_filtered_item_values($allFields["data"][$iUser]['fieldId'], $user, $allFields["data"][$i]['fieldId']);
			} else if ($iIp >= 0) {
				$userValues = $trklib->get_filtered_item_values($allFields["data"][$iIp]['fieldId'],  $tikilib->get_ip_address(), $allFields["data"][$i]['fieldId']);
			}
			
			$allValues = $trklib->get_all_items($trackerId, $fieldId, $status, $allFields);
			$j = -1;
			foreach ($allValues as $value) {
				$value = trim($value);
				if ($j < 0 || $value != $v[$j]['value']) {
					++$j;
					$v[$j]['value'] = $value;
					$v[$j]['count'] = 1;
					if (isset($userValues) && in_array($value, $userValues)) {
						$v[$j]['me'] = 'y';
					}
					$v[$j]['href'] = "trackerId=$trackerId&amp;filterfield=$fieldId&amp;filtervalue[$fieldId]=".urlencode($value);
				} else {
					++$v[$j]['count'];
				}
			}
		}
		if (isset($average)) {
			$total = $trklib->get_nb_items($trackerId);
			for (; $j >= 0; --$j) {
				$v[$j]['average'] = 100*$v[$j]['count']/$total;
				if ($tracker_info['showStatus'] == 'y') {
					$v[$j]['href'] .= "&amp;status=$status";
				}
			}
		}
		if (!empty($v)) {
			$stat['name'] = $allFields["data"][$i]['name'];
			$stat['values'] = $v;
			$stats[] = $stat;
		}
		unset($v);
	}
	$smarty->assign_by_ref('stats', $stats);
	return "~np~".$smarty->fetch('wiki-plugins/wikiplugin_trackerstat.tpl')."~/np~";
}
