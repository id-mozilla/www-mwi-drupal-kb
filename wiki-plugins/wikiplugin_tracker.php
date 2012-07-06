<?php
// $Id: /cvsroot/tikiwiki/tiki/lib/wiki-plugins/wikiplugin_tracker.php,v 1.85.2.26 2008-03-19 13:32:42 sylvieg Exp $
// Includes a tracker field
// Usage:
// {TRACKER()}{TRACKER}

function wikiplugin_tracker_help() {
	$help = tra("Displays an input form for tracker submit").":\n";
	$help.= "~np~{TRACKER(trackerId=1, fields=id1:id2:id3, action=Name of submit button, showtitle=n, showdesc=n, showmandatory=n, embedded=n, url=\"http://site.com\", values=val1:val2:val3, sort=n, preview=preview, view=user|page, tpl=x.tpl,wiki=page,newstatus=o|p|c, itemId=, colwidth=##|##%)}Thank you for submitting this information{TRACKER}~/np~";
	return $help;
}

function wikiplugin_tracker_info() {
	return array(
		'name' => tra('Tracker'),
		'documentation' => 'PluginTracker',
		'description' => tra("Displays an input form for tracker submit"),
		'prefs' => array( 'feature_trackers', 'wikiplugin_tracker' ),
		'body' => tra('Confirmation message after posting form'),
		'icon' => 'pics/icons/database.png',
		'params' => array(
			'trackerId' => array(
				'required' => true,
				'name' => tra('Tracker ID'),
				'description' => tra('Tracker ID'),
				'filter' => 'digits'
			),
			'fields' => array(
				'required' => true,
				'name' => tra('Fields'),
				'description' => tra('Colon-separated list of field IDs to be displayed. Example: 2:4:5'),
			),
			'action' => array(
				'required' => false,
				'name' => tra('Action'),
				'description' => tra('Label on the submit button'),
			),
			'showtitle' => array(
				'required' => false,
				'name' => tra('Show Title'),
				'description' => 'y|n',
				'filter' => 'alpha'
			),
			'showdesc' => array(
				'required' => false,
				'name' => tra('Show Description'),
				'description' => 'y|n',
				'filter' => 'alpha'
			),
			'showmandatory' => array(
				'required' => false,
				'name' => tra('Show Mandatory'),
				'description' => 'y|n',
				'filter' => 'alpha'
			),
			'embedded' => array(
				'required' => false,
				'name' => tra('Embedded'),
				'description' => 'y|n',
				'filter' => 'alpha'
			),
			'email' => array(
				'required' => false,
				'name' => tra('Email'),
				'description' => tra('from').'|'.tra('to').'|'.tra('template'),
			),
			'url' => array(
				'required' => false,
				'name' => tra('URL'),
				'description' => tra('URL used for the field links'),
				'filter' => 'url'
			),
			'target' => array(
				'required' => false,
				'name' => '_blank|_self|_parent|_top',
				'description' => tra('Url target'),
			),
			'values' => array(
				'required' => false,
				'name' => tra('Values'),
				'description' => tra('Colon-separated list of values.').' '.tra('Note that plugin arguments can be enclosed with double quotes "; this allows them to contain , or :'),
			),
			'sort' => array(
				'required' => false,
				'name' => tra('Sort'),
				'description' => 'y|n',
				'filter' => 'alpha'
			),
			'preview' => array(
				'required' => false,
				'name' => tra('Preview'),
				'description' => tra('preview'),
			),
			'view' => array(
				'required' => false,
				'name' => tra('View'),
				'description' => tra('user|page'),
			),
			'itemId' =>array(
				'required' => false,
				'name' => tra('itemId'),
				'description' => tra('itemId if you want to edit an item'),
				'filter' => 'digits'
			),
			'tpl' => array(
				'required' => false,
				'name' => tra('Template File'),
				'description' => tra('Name of the template used to display the tracker items.'),
			),
			'wiki' => array(
				'required' => false,
				'name' => tra('Wiki'),
				'description' => tra('Name of the wiki page containing the template to display the tracker items.'),
				'filter' => 'pagename'
			),
			'newstatus' => array(
				'required' => false,
				'name' => tra('New Status'),
				'description' => 'o|p|c'. ' '.tra('Default status applied to newly created items.'),
				'filter' => 'alpha'
			),
			'colwidth' => array(
				'required' => false,
				'name' => tra('Width of first column '),
				'description' => '## or ##% '. ' '.tra('Specify the width in pixels or percentage of the first column in the tracker form.'),
			),
		),
	);
}

function wikiplugin_tracker_name($fieldId, $name, $field_errors) {
	foreach($field_errors['err_mandatory'] as $f) {
		if ($fieldId == $f['fieldId'])
			return '<span class="highlight">'.$name.'</span>';
	}
	foreach($field_errors['err_value'] as $f) {
		if ($fieldId == $f['fieldId'])
			return '<span class="highlight">'.$name.'</span>';
	}
	return $name;
}
function wikiplugin_tracker($data, $params) {
	global $tikilib, $userlib, $dbTiki, $user, $group, $page, $tiki_p_admin_trackers, $smarty, $prefs, $trklib, $tiki_p_view;
	static $iTRACKER = 0;
	++$iTRACKER;
	include_once('lib/trackers/trackerlib.php');
	
	//var_dump($_REQUEST);
	extract ($params,EXTR_SKIP);

	if ($prefs['feature_trackers'] != 'y') {
		return $smarty->fetch("wiki-plugins/error_tracker.tpl");
	}
	if (empty($trackerId) || !($tracker = $trklib->get_tracker($trackerId))) {
		return $smarty->fetch("wiki-plugins/error_tracker.tpl");
	}
	if ($t = $trklib->get_tracker_options($trackerId)) {
		$tracker = array_merge($tracker,$t);
	}
	if (empty($trackerId) && !empty($view) && $view == 'user' && $prefs['userTracker'] == 'y') { // the user tracker item
		$utid = $userlib->get_tracker_usergroup($user);
		if (!empty($utid) && !empty($utid['usersTrackerId'])) {
			$itemId = $trklib->get_item_id($utid['usersTrackerId'],$utid['usersFieldId'],$user);
			$trackerId = $utid['usersTrackerId'];
			$usertracker = true;
		}
	} elseif (!empty($trackerId) && !empty($view) && $view == 'user') {// the user item of a tracker
		$itemId = $trklib->get_user_item($trackerId, $tracker);
		$usertracker = true;
	} elseif (!empty($trackerId) && !empty($view) && $view == 'page' && !empty($_REQUEST['page']) && ($f = $trklib->get_field_id_from_type($trackerId, 'k', '1%'))) {// the page item
		$itemId = $trklib->get_item_id($trackerId, $f, $_REQUEST['page']);
	} elseif (!empty($trackerId) && !empty($_REQUEST['view_user'])) {
		$itemId = $trklib->get_user_item($trackerId, $tracker, $_REQUEST['view_user']);
	} elseif (!empty($_REQUEST['itemId'])) {
		$itemId = $_REQUEST['itemId'];
		$item = $trklib->get_tracker_item($itemId);
		$trackerId = $item['trackerId'];
	} elseif (!empty($view) && $view == 'group') {
		$gtid = $userlib->get_grouptrackerid($group);
		if(isset($gtid['groupTrackerId'])) {
			$trackerId = $gtid['groupTrackerId'];
			$itemId = $trklib->get_item_id($trackerId,$gtid['groupFieldId'],$group);
			$grouptracker = true;
		}
	}
	if (!isset($trackerId)) {
		return $smarty->fetch("wiki-plugins/error_tracker.tpl");
	}
	if (!isset($embedded)) {
		$embedded = "n";
	}
	if (!isset($showtitle)) {
		$showtitle = "n";
	}
	if (!isset($showdesc)) {
		$showdesc = "n";
	}
	if (!isset($sort)) {
		$sort = 'n';
	}
	if (!isset($action)) {
		$action = 'Save';
	}
	if (isset($preview)) {
		if (empty($preview)) {
			$preview = 'Preview';
		}
	} else {
		unset($_REQUEST['tr_preview']);
	}
	if (!isset($showmandatory)) {
		$showmandatory = 'y';
	}
	$smarty->assign('showmandatory', $showmandatory); 
	if (!empty($wiki)) $wiki = trim($wiki);

	if (isset($values)) {
		if (!is_array($values)) {
			$values = $tikilib->quotesplit(':', $values);
			foreach ($values as $i=>$v) {
				$values[$i] = preg_replace('/^"(.*)"$/', '$1', $v);
			}
		}
	}
	if (isset($_REQUEST['values'])) {
		if (is_array($_REQUEST['values'])) {
			foreach ($_REQUEST['values'] as $i=>$k) {
				$_REQUEST['values'][$i] = urldecode($k);
			}
		} else {
			$_REQUEST['values'] = urldecode($_REQUEST['values']);
		}
	}

	if (empty($_SERVER['SCRIPT_NAME']) || !strstr($_SERVER['SCRIPT_NAME'],'tiki-register.php')) {
		if (!empty($itemId) && $tracker['writerCanModify'] == 'y' && isset($usertracker) && $usertracker) { // user tracker he can modify
		} elseif (!empty($itemId) && $tracker['writerCanModify'] == 'y' && $user && (($itemUser = $trklib->get_item_creator($trackerId, $itemId)) == $user || ($tracker['userCanTakeOwnership'] == 'y' && empty($itemUser)))) {
		} elseif (!empty($itemId) && isset($grouptracker) && $grouptracker) {
		} else {
			$perms = $tikilib->get_perm_object($trackerId, 'tracker', $tracker, false);
			if ($perms['tiki_p_create_tracker_items'] == 'n' && empty($itemId)) {
				return '<b>'.tra("You do not have permission to insert an item").'</b>';
			} elseif (!empty($itemId)) {
				$item_info = $trklib->get_tracker_item($itemId);
				if (!(($perms['tiki_p_modify_tracker_items'] == 'y' and $item_info['status'] != 'p' and $item_info['status'] != 'c') || ($perms['tiki_p_modify_tracker_items_pending'] == 'y' and $item_info['status'] == 'p') ||  ($perms['tiki_p_modify_tracker_items_closed'] == 'y' and $item_info['status'] == 'c'))) { 
					if ($tracker['writerGroupCanModify'] == 'y' && in_array($trklib->get_item_group_creator($trackerId, $itemId), $tikilib->get_user_groups($user))) {
						global $group;
						$smarty->assign_by_ref('ours', $group);
					} else 
						return '<b>'.tra("You do not have permission to modify an item").'</b>';
				}
			}
		}
	}

	if (isset($_REQUEST['removeattach']) && $tracker['useAttachments'] == 'y') {
		$owner = $trklib->get_item_attachment_owner($_REQUEST['removeattach']);
		if ($perms['tiki_p_admin_trackers'] == 'y' || ($user && $user == $owner)) {
			$trklib->remove_item_attachment($_REQUEST["removeattach"]);
			unset($_REQUEST['removeattach']);
		}
	}
	if (isset($_REQUEST['removeImage']) && !empty($_REQUEST['trackerId']) && !empty($_REQUEST['itemId']) && !empty($_REQUEST['fieldId']) && !empty($_REQUEST['fieldName'])) {
		$img_field = array('data' => array());
		$img_field['data'][] = array('fieldId' => $_REQUEST['fieldId'], 'type' => 'i', 'name' => $_REQUEST['fieldName'], 'value' => 'blank');
		$trklib->replace_item($_REQUEST['trackerId'], $_REQUEST['itemId'], $img_field);
	}
	$back = '';
	$js = '';

	$thisIsThePlugin = isset($_REQUEST['iTRACKER']) && $_REQUEST['iTRACKER'] == $iTRACKER;

	if (!isset($_REQUEST["ok"]) || $_REQUEST["ok"]  == "n" || !$thisIsThePlugin || isset($_REQUEST['tr_preview'])) {
		$field_errors = array('err_mandatory'=>array(), 'err_value'=>array());
	
			global $notificationlib; include_once('lib/notifications/notificationlib.php');
			$tracker = $trklib->get_tracker($trackerId);
			$tracker = array_merge($tracker,$trklib->get_tracker_options($trackerId));
			if ((!empty($tracker['start']) && $tikilib->now < $tracker['start']) || (!empty($tracker['end']) && $tikilib->now > $tracker['end']))
				return;
			$flds = $trklib->list_tracker_fields($trackerId,0,-1,"position_asc","");
			if (empty($fields) && (!empty($wiki) || !empty($tpl))) {
				if (!empty($wiki)) {
					$outf = $trklib->get_pretty_fieldIds($wiki, 'wiki');
				} else {
					$outf = $trklib->get_pretty_fieldIds($tpl, 'tpl');
				}
				$ret = array();
				foreach($flds['data'] as $field) {
					if ($field['type'] == 'q' || $field['type'] == 'k' || $field['type'] == 'u' || $field['type'] == 'g' || in_array($field['fieldId'], $outf)) {
						$ret[] = $field;
					}
				}
				$flds['cant'] = sizeof($ret);
				$flds['data'] = $ret;
			}
			$bad = array();
			$embeddedId = false;
			$onemandatory = false;
			$full_fields = array();
			$mainfield = '';

			if ($thisIsThePlugin) {
				/* ------------------------------------- Recup all values from REQUEST -------------- */
				$cpt = 0;
				if (isset($fields)) {
					$fields_plugin = split(':', $fields);
				}
				foreach ($flds['data'] as $fl) {
					// store value to display it later if form
					// isn't fully filled.
					if ($flds['data'][$cpt]['type'] == 's' && $flds['data'][$cpt]['name'] == 'Rating') {
						if (isset($_REQUEST['track'][$fl['fieldId']])) {
							$newItemRate = $_REQUEST['track'][$fl['fieldId']];
							$newItemRateField = $fl['fieldId'];
						} else {
							$newItemRate = NULL;
						}
					} elseif (($flds['data'][$cpt]['type'] == 'u' || $flds['data'][$cpt]['type'] == 'g' || $flds['data'][$cpt]['type'] == 'I' || $flds['data'][$cpt]['type'] == 'k') && ($flds['data'][$cpt]['options_array'][0] == '1' || $flds['data'][$cpt]['options_array'][0] == '2') && empty($_REQUEST['track'][$fl['fieldId']])) {
						if (empty($itemId) && ($flds['data'][$cpt]['options_array'][0] == '1' || $flds['data'][$cpt]['options_array'][0] == '2')) {
							if ($flds['data'][$cpt]['type'] == 'u') {
								$_REQUEST['track'][$fl['fieldId']] = empty($user)?(empty($_REQUEST['name'])? '':$_REQUEST['name']):$user;
							} elseif ($flds['data'][$cpt]['type'] == 'g') {
								$_REQUEST['track'][$fl['fieldId']] = $group;
							} elseif ($flds['data'][$cpt]['type'] == 'I') {
								$_REQUEST['track'][$fl['fieldId']] = $tikilib->get_ip_address();
							} elseif ($flds['data'][$cpt]['type'] == 'k') {
								$_REQUEST['track'][$fl['fieldId']] = isset($_REQUEST['page'])?$_REQUEST['page']: '';
							}
						} elseif (!empty($itemId) && $flds['data'][$cpt]['options_array'][0] == '2') {
							if ($flds['data'][$cpt]['type'] == 'u')
								$_REQUEST['track'][$fl['fieldId']] = $user;
							elseif ($flds['data'][$cpt]['type'] == 'g')
								$_REQUEST['track'][$fl['fieldId']] = $group;
							elseif ($flds['data'][$cpt]['type'] == 'I')
								$_REQUEST['track'][$fl['fieldId']] = $tikilib->get_ip_address();
						}
					} elseif (($flds['data'][$cpt]['type'] == 'C' || $flds['data'][$cpt]['type'] == 'e') && empty($_REQUEST['track'][$fl['fieldId']])) {
						$_REQUEST['track'][$fl['fieldId']] = '';
					} elseif ($flds['data'][$cpt]['type'] == 'f') {
						$ins_id = 'track_'.$fl['fieldId'];
						if (isset($_REQUEST[$ins_id.'Day'])) {
							if (empty($_REQUEST['$ins_id'.'Hour'])) {
								$_REQUEST['$ins_id'.'Hour'] = 0;
							}
							if (empty($_REQUEST['$ins_id'.'Minute'])) {
								$_REQUEST['$ins_id'.'Minute'] = 0;
							}
							$_REQUEST['track'][$fl['fieldId']] = $tikilib->make_time($_REQUEST["$ins_id" . "Hour"], $_REQUEST["$ins_id" . "Minute"], 0, $_REQUEST["$ins_id" . "Month"], $_REQUEST["$ins_id" . "Day"], $_REQUEST["$ins_id" . "Year"]);
						} else {
							$_REQUEST['track'][$fl['fieldId']] = $tikilib->now;
						}
					} elseif ($f['type'] == 'N' && !empty($itemId)) {
						if (empty($itemUser)) {
							$itemUser = $this->get_item_creator($trackerId, $itemId);
						}
						$flds['data'][$i]['value'] = $trklib->in_group_value($flds['data'][$i], $itemUser);
					}
					if (isset($_REQUEST['ins_cat_'.$fl['fieldId']])) { // to remember if error
						$_REQUEST['track'][$fl['fieldId']] = $_REQUEST['ins_cat_'.$fl['fieldId']];
					}

					if(isset($_REQUEST['track'][$fl['fieldId']])) {
						$flds['data'][$cpt]['value'] = $_REQUEST['track'][$fl['fieldId']];
					} else {
						$flds['data'][$cpt]['value'] = '';
						if ($fl['type'] == 'c') {
							$_REQUEST['track'][$fl['fieldId']] = 'n';
						} elseif ($fl['type'] == 'R' && $fl['isMandatory'] == 'y') {
							// if none radio is selected, there will be no value and no error if mandatory
							$_REQUEST['track'][$fl['fieldId']] = '';
						}
					}
					if (!empty($_REQUEST['other_track'][$fl['fieldId']])) {
						$flds['data'][$cpt]['value'] = $_REQUEST['other_track'][$fl['fieldId']];
					}
					if ($flds['data'][$cpt]['isMultilingual'] == 'y') {
						foreach ($prefs['available_languages'] as $num=>$tmplang) {
							if (isset($_REQUEST['track'][$fl['fieldId']][$tmplang])) {
								$fl['lingualvalue'][$num]['value'] = $_REQUEST['track'][$fl['fieldId']][$tmplang];
								$fl['lingualvalue'][$num]['lang'] = $tmplang;
							}
						}
					}
					$full_fields[$fl['fieldId']] = $fl;
					
					if ($embedded == 'y' and $fl['name'] == 'page') {
						$embeddedId = $fl['fieldId'];
					}
					if ($fl['isMain'] == 'y')
						$mainfield = $flds['data'][$cpt]['value'];
					$cpt++;
				} /*foreach */

				if (isset($_REQUEST['track'])) {
					foreach ($_REQUEST['track'] as $fld=>$val) {
						//$ins_fields["data"][] = array('fieldId' => $fld, 'value' => $val, 'type' => 1);
						if (!empty($_REQUEST['other_track'][$fld])) {
							$val = $_REQUEST['other_track'][$fld];
						}
						$ins_fields["data"][] = array_merge(array('value' => $val), $full_fields[$fld]);
					}
				}

				if (isset($_FILES['track'])) {// image or attachment fields
					foreach ($_FILES['track'] as $label=>$w) {
						foreach ($w as $fld=>$val) {
							if ($label == 'tmp_name' && is_uploaded_file($val)) {
								$fp = fopen( $val, 'rb' );
								$data = '';
								while (!feof($fp)) {
									$data .= fread($fp, 8192 * 16);
								}
								fclose ($fp);
								$files[$fld]['old_value'] = $files[$fld]['value'];
								$files[$fld]['value'] = $data;
							} else {
								$files[$fld]['file_'.$label] = $val;
							}
						}
					}
					foreach ($files as $fld=>$file) {
						$ins_fields['data'][] = array_merge($file, $full_fields[$fld]);
					}
				}

				if ($embedded == 'y' && isset($_REQUEST['page'])) {
					$ins_fields["data"][] = array('fieldId' => $embeddedId, 'value' => $_REQUEST['page']);
				}
				$ins_categs = array();
				$categorized_fields = array();
				while (list($postVar, $postVal) = each($_REQUEST)) {
					if(preg_match("/^ins_cat_([0-9]+)/", $postVar, $m)) {
						foreach ($postVal as $v)
 	   						$ins_categs[] = $v;
						$categorized_fields[] = $m[1];
					}
		 		}
				/* ------------------------------------- End recup all values from REQUEST -------------- */

				/* ------------------------------------- Check field values for each type and presence of mandatory ones ------------------- */
				$field_errors = $trklib->check_field_values($ins_fields, $categorized_fields);

				if (empty($user) && $prefs['feature_antibot'] == 'y' && !$_SESSION['in_tracker']) {
					// in_tracker session var checking is for tiki-register.php
					if((!isset($_SESSION['random_number']) || $_SESSION['random_number'] != $_REQUEST['antibotcode'])) {
						$field_errors['err_antibot'] = 'y';
					}
				}

				if( count($field_errors['err_mandatory']) == 0  && count($field_errors['err_value']) == 0 && empty($field_errors['err_antibot']) && !isset($_REQUEST['tr_preview'])) {
					/* ------------------------------------- save the item ---------------------------------- */
					if (!isset($itemId) && $tracker['oneUserItem'] == 'y') {
						$itemId = $trklib->get_user_item($trackerId, $tracker);
					}
					if (isset($_REQUEST['status'])) {
						$status = $_REQUEST['status'];
					} elseif (isset($newstatus) && ($newstatus == 'o' || $newstatus == 'c'|| $newstatus == 'p')) {
						$status = $newstatus;
					} elseif (empty($itemId) && isset($tracker['newItemStatus'])) {
						$status = $tracker['newItemStatus'];
					} else {
						$status = '';
					}

					$rid = $trklib->replace_item($trackerId,$itemId,$ins_fields, $status, $ins_categs);
					$trklib->categorized_item($trackerId, $rid, $mainfield, $ins_categs);
					if (isset($newItemRate)) {
						$trklib->replace_rating($trackerId, $rid, $newItemRateField, $user, $newItemRate);
					}
					if (!empty($email)) {
						$emailOptions = split("\|", $email);
						if (is_numeric($emailOptions[0])) {
							$emailOptions[0] = $trklib->get_item_value($trackerId, $rid, $emailOptions[0]);
						}
						if (empty($emailOptions[0])) { // from
							$emailOptions[0] = $prefs['sender_email'];
						}
						if (empty($emailOptions[1])) { // to
							$emailOptions[1][0] = $prefs['sender_email'];
						} else {
							$emailOptions[1] = split(',', $emailOptions[1]);
							foreach ($emailOptions[1] as $key=>$email) {
								if (is_numeric($email))
									$emailOptions[1][$key] = $trklib->get_item_value($trackerId, $rid, $email);
							}
						}
						if (!empty($emailOptions[2])) { //tpl
							if (!preg_match('/\.tpl$/', $emailOptions[2]))
								$emailOptions[2] .= '.tpl';
							$tplSubject = str_replace('.tpl', '_subject.tpl', $emailOptions[2]);
						} else {
							$emailOptions[2] = 'tracker_changed_notification.tpl';
						}
						if (empty($tplSubject)) {
							$tplSubject = 'tracker_changed_notification_subject.tpl';
						}							
						include_once('lib/webmail/tikimaillib.php');
						$mail = new TikiMail();
						@$mail_data = $smarty->fetch('mail/'.$tplSubject);
						if (empty($mail_data))
							$mail_data = tra('Tracker was modified at '). $_SERVER["SERVER_NAME"];
						$mail->setSubject($mail_data);
						$mail_data = $smarty->fetch('mail/'.$emailOptions[2]);
						$mail->setText($mail_data);
						$mail->setHeader('From', $emailOptions[0]);
						$mail->send($emailOptions[1]);
					}
					if (empty($url)) {
						if (!empty($page)) {
							$url = "tiki-index.php?page=".urlencode($page)."&ok=y&iTRACKER=$iTRACKER";
							$url .= "#wikiplugin_tracker$iTRACKER";
							header("Location: $url");
							die;
						} else {
							return '';
						}
					} else {
						if (strstr($url, 'itemId')) {
							$url = str_replace('itemId', 'itemId='.$rid, $url);
						}
						header("Location: $url");
						die;
					}
					/* ------------------------------------- end save the item ---------------------------------- */
				} elseif (isset($_REQUEST['trackit']) and $_REQUEST['trackit'] == $trackerId) {
					$smarty->assign('wikiplugin_tracker', $trackerId);//used in vote plugin
				}

			} else if (empty($itemId) && !empty($values) || (!empty($_REQUEST['values']) and empty($_REQUEST['prefills']))) { // assign default values for each filedId specify
				if (empty($values)) { // url with values[]=x&values[] witouth the list of fields
					$values = $_REQUEST['values'];
				}
				if (!is_array($values)) {
					$values = array($values);
				}
				if (isset($fields)) {
					$fl = split(':', $fields);
					for ($j = 0, $count_fl = count($fl); $j < $count_fl; $j++) {
						for ($i = 0, $count_flds = count($flds['data']); $i < $count_flds; $i++) {
							if ($flds['data'][$i]['fieldId'] == $fl[$j]) { 
								$flds['data'][$i]['value'] = $values[$j];
							}	
						}
					}
				} else { // values contains all the fields value in the default order
					$i = 0;
					foreach ($values as $value) {
						$flds['data'][$i++]['value'] = $value;
					}
				}
			
			} elseif (!empty($itemId)) {
				if (isset($fields)) {
					$fl = split(':', $fields);
					$filter = '';
					foreach ($flds['data'] as $f) {
						if (in_array($f['fieldId'], $fl))
							$filter[] = $f;
					}
				} else {
					$filter = &$flds['data'];
				}
				if (!empty($filter)) {
					foreach ($filter as $f) {
						$filter2[$f['fieldId']] = $f;
					}
					$flds['data'] = $trklib->get_item_fields($trackerId, $itemId, $filter2, $itemUser);

				}

			} else {
				if (isset($_REQUEST['values']) && isset($_REQUEST['prefills'])) { //url:prefields=1:2&values[]=x&values[]=y
					if (!is_array($_REQUEST['values']))
						$_REQUEST['values'] = array($_REQUEST['values']);
					$fl = split(':', $_REQUEST['prefills']);
				} else {
					unset($fl);
				}
				for ($i = 0, $count_flds2 = count($flds['data']); $i < $count_flds2; $i++) {
					if (isset($fl) && ($j = array_search($flds['data'][$i]['fieldId'], $fl)) !== false) {
						$flds['data'][$i]['value'] = $_REQUEST['values'][$j];
					} else {
						$flds['data'][$i]['value'] = ''; // initialize fields with blank values
					}
				}
			}

			$optional = array();
			$outf = array();
			if (isset($fields) && !empty($fields)) {
				$fl = split(":",$fields);
				if ($sort == 'y')
					$flds = $trklib->sort_fields($flds, $fl);		
				foreach ($fl as $l) {
					if (substr($l,0,1) == '-') {
						$l = substr($l,1);
						$optional[] = $l;
					}
					$ok = false;
					foreach ($flds['data'] as $f) {
						if ($f['fieldId'] == $l) {
							$ok = true;
							break;
						}
					}
					if (!$ok) {
						$back .= tra('Incorrect fieldId:').' '.$l;
					}
					$outf[] = $l;
				}
			} elseif (empty($fields) && !empty($wiki)) {
				$wiki_info = $tikilib->get_page_info($wiki);
				preg_match_all('/\$f_([0-9]+)/', $wiki_info['data'], $matches);
				$outf = $matches[1];
			} elseif (empty($fields) && !empty($tpl)) {
				$f = $smarty->get_filename($tpl);
				if (!empty($f)) {
					$f = file_get_contents($f);
					preg_match_all('/\$f_([0-9]+)/', $f, $matches);
					$outf = $matches[1];
				}
			} elseif (empty($fields) && empty($wiki)) {
				foreach ($flds['data'] as $f) {
					if ($f['isMandatory'] == 'y')
						$optional[] = $f['fieldId'];
					$outf[] = $f['fieldId'];
				}
			}

			// Display warnings when needed
			if(count($field_errors['err_mandatory']) > 0) {
				$back.= '<div class="simplebox highlight"><img src="pics/icons/exclamation.png" alt=" '.tra('Error').'" style="vertical-align:middle" /> ';
				$back.= tra('Following mandatory fields are missing').'&nbsp;:<br/>';
				$coma_cpt = count($field_errors['err_mandatory']);
				foreach($field_errors['err_mandatory'] as $f) {
					$back.= $f['name'];
					$back.= --$coma_cpt > 0 ? ',&nbsp;' : '';
				}
				$back.= '</div><br />';
				$_REQUEST['error'] = 'y';
			}

			if(count($field_errors['err_value']) > 0) {
				$back.= '<div class="simplebox highlight">';
				$b = '';
				foreach($field_errors['err_value'] as $f) {
					if (!empty($f['errorMsg'])) {
						$back .= tra($f['errorMsg']).'<br>';
					} else {
						if (!empty($b))
							$b .= ' : ';
						$b .= $f['name'];
					}
				}
				if (!empty($b)) {
					$back.= tra('Following fields are incorrect').'&nbsp;:<br/>'.$b;
				}
				$back.= '</div><br />';
				$_REQUEST['error'] = 'y';
			}
			if (isset($field_errors['err_antibot'])) {
				$back.= '<div class="simplebox highlight"><img src="pics/icons/exclamation.png" alt=" '.tra('Error').'" style="vertical-align:middle" /> ';
				$back .= tra('You have mistyped the anti-bot verification code; please try again.');
				$back.= '</div><br />';
				$_REQUEST['error'] = 'y';
			}
			if (count($field_errors['err_mandatory']) > 0 || count($field_errors['err_value']) > 0 || isset($field_errors['err_antibot'])) {
				$smarty->assign('input_err', 'y');
			}
			if (!empty($page))
				$back .= '~np~';
			$smarty->assign_by_ref('tiki_p_admin_trackers', $perms['tiki_p_admin_trackers']);
			$back.= '<form enctype="multipart/form-data" method="post"'.(isset($target)?' target="'.$target.'"':'').' action="'. $_SERVER['REQUEST_URI'] .'"><input type="hidden" name="trackit" value="'.$trackerId.'" />';
			$back .= '<input type="hidden" name="iTRACKER" value="'.$iTRACKER.'" />';
			$back .= '<input type="hidden" name="refresh" value="1" />';
			if (isset($_REQUEST['page']))
				$back.= '<input type="hidden" name="page" value="'.$_REQUEST["page"].'" />';
			 // for registration
			if (isset($_REQUEST['name']))
				$back.= '<input type="hidden" name="name" value="'.$_REQUEST["name"].'" />';
			if (isset($_REQUEST['pass'])) {
				$back.= '<input type="hidden" name="pass" value="'.$_REQUEST["pass"].'" />';
				$back.= '<input type="hidden" name="passAgain" value="'.$_REQUEST["pass"].'" />';
			}
			if (isset($_REQUEST['email']))
				$back.= '<input type="hidden" name="email" value="'.$_REQUEST["email"].'" />';
			if (isset($_REQUEST['regcode']))
				$back.= '<input type="hidden" name="regcode" value="'.$_REQUEST["regcode"].'" />';
			if (isset($_REQUEST['chosenGroup'])) // for registration
				$back.= '<input type="hidden" name="chosenGroup" value="'.$_REQUEST["chosenGroup"].'" />';
			if (isset($_REQUEST['register']))
				$back.= '<input type="hidden" name="register" value="'.$_REQUEST["register"].'" />';
			if ($showtitle == 'y') {
				$back.= '<div class="titlebar">'.$tracker["name"].'</div>';
			}
			if ($showdesc == 'y' && $tracker['description']) {

				if ($tracker['descriptionIsParsed'] == 'y') {
					$back .= '<div class="wikitext">'.$tikilib->parse_data($tracker['description']).'</div><br />';
					} else {
					$back.= '<div class="wikitext">'.$tracker["description"].'</div><br />';
					}
			}
			if (isset($_REQUEST['tr_preview'])) { // use for the computed and join fields
				$assocValues = array();
				$assocNumerics = array();
				foreach ($flds['data'] as $f) {
					if (empty($f['value']) && ($f['type'] == 'u' || $f['type'] == 'g' || $f['type'] == 'I') && ($f['options_array'][0] == '1' || $f['options_array'][0] == '2')) { //need to fill the selector fields for the join
						$f['value'] = ($f['type'] == 'I')? $tikilib->get_ip_address(): (($f['type'] == 'g')? $group: $user);
					}
					$assocValues[$f['fieldId']] = $f['value'];
					$assocNumerics[$f['fieldId']] = preg_replace('/[^0-9\.\+]/', '', $f['value']); // get rid off the $ and such unit
				}
			}

			if (!empty($itemId)) {
				$item = array('itemId'=>$itemId, 'trackerId'=>$trackerId);
			}
			foreach ($flds['data'] as $i=>$f) { // collect additional infos
				if (in_array($f['fieldId'],$outf)) {
					$flds['data'][$i]['ins_id'] = ($f['type'] == 'e')?'ins_cat_'.$f['fieldId']: (($f['type'] == 'f')?'track_'.$f['fieldId']: 'track['.$f['fieldId'].']');
					if ($f['isHidden'] == 'c' && !empty($itemId) && !isset($item['creator'])) {
						$item['creator'] = $trklib->get_item_creator($trackerId, $itemId);
					}
					if ($f['type'] == 's' && ($f['name'] == 'Rating' || $f['name'] == tra('Rating')) && $perms['tiki_p_tracker_vote_ratings'] == 'y' && isset($item)) {
						$item['my_rate'] = $tikilib->get_user_vote("tracker$trackerId.$itemId", $user);
					}
					if ($f['isMultilingual'] == 'y') {
						$multi_languages = $prefs['available_languages'];
						foreach ($multi_languages as $num=>$tmplang){
							$flds['data'][$i]['lingualvalue'][$num]['lang'] = $tmplang;
						}
					}
					if ($f['type'] == 'r') {
						$flds['data'][$i]['list'] = array_unique($trklib->get_all_items($f['options_array'][0],$f['options_array'][1],'poc'));
						if (isset($f['options_array'][3])) {
							$flds['data'][$i]['displayedList'] = array_unique($trklib->concat_all_items_from_fieldslist($f['options_array'][0],$f['options_array'][3]));
						}
					} elseif ($f['type'] == 'y') {
						$flds['data'][$i]['flags'] = $tikilib->get_flags();
						if ($prefs['language'] != 'en') {
							foreach ($flags as $flag) {
								$flagsTranslated[] = $tikilib->take_away_accent(tra($flag));
							}
							array_multisort($flagsTranslated, $flds['data'][$i]['flags']);
						}
					} elseif ($f['type'] == 'u') {
						if ($perms['tiki_p_admin_trackers'] == 'y' || ($f['options_array'][0] != 1 && $f['options_array'][0] != 2))
							$flds['data'][$i]['list'] = $userlib->list_all_users();
						elseif ($f['options_array'][0] == 1)
							$flds['data'][$i]['value'] = $user;
					} elseif ($f['type'] == 'g') {
						if ($perms['tiki_p_admin_trackers'] == 'y' || ($f['options_array'][0] != 1 && $f['options_array'][0] != 2)) {
							$flds['data'][$i]['list'] = $userlib->list_all_groups();
						} elseif ($f['options_array'][0] == 1) {
							global $group;
							$flds['data'][$i]['value'] = $group;
						}
					} elseif ($f['type'] == 'k') {
						if ($f['options_array'][0] == 1) {
							if (isset($page)) {
								$flds['data'][$i]['value'] = $page;
							}
						}
					} elseif ($f['type'] == 'e') {
						global $categlib; include_once('lib/categories/categlib.php');
						$flds['data'][$i]['list'] = $categlib->get_viewable_child_categories($f["options_array"][0]);
					} elseif ($f['type'] == 'A') {
						if (!empty($f['value'])) {
							$flds['data'][$i]['info'] = $trklib->get_item_attachment($f['value']);
						}
					} elseif ($f['type'] == 'a') {
						if ($f['options_array'][0] == 1 && empty($toolbars)) {
							// all in the smarty object now
						}
					} elseif ($f['type'] == 'l' && isset($itemId)) {
						$opts[1] = split(':', $f['options_array'][1]);
						$finalFields = explode('|', $f['options_array'][3]);
						$flds['data'][$i]['value'] = $trklib->get_join_values($itemId, array_merge(array($f['options_array'][2]), array($f['options_array'][1]), array($finalFields[0])), $f['options_array'][0], $finalFields);
					} elseif ($f['type'] == 'w') {
						$refFieldId = $f['options_array'][2];
						foreach ($flds['data'] as $i=>$ff) {
							if ($ff['fieldId'] == $refFieldId) {
								$refFieldId = $i;
							}
						}
						if (!isset($flds['data'][$refFieldId]['http_request']))
							$flds['data'][$refFieldId]['http_request'] = array('','','','','','','','','');
						for ($i = 0; $i < 5; $i++) {
							$flds['data'][$refFieldId]['http_request'][$i] .= 
								($flds['data'][$refFieldId]['http_request'][$i] ? "," : "") .
								isset($f['options_array'][$i])?$f['options_array'][$i]:'';
						}
						$flds['data'][$refFieldId]['http_request'][5] .=
							($flds['data'][$refFieldId]['http_request'][5] ? ",":"") .
							$f['fieldId'];
						$flds['data'][$refFieldId]['http_request'][6] .=
							($flds['data'][$refFieldId]['http_request'][6] ? "," : "") .
							$f['isMandatory'];
						$flds['data'][$refFieldId]['http_request'][7] .= $flds['data'][$refFieldId]['value'];
						$flds['data'][$refFieldId]['http_request'][8] .= ($flds['data'][$refFieldId]['http_request'][8] ? "," : "") . $f['value'];
					}
				}
			}

			// Loop on tracker fields and display form
			if (empty($tpl) && empty($wiki)) {
				$back.= '<table class="wikiplugin_tracker">';
			} else {
				$back .= '<div class="wikiplugin_tracker">';
			}
			$backLength0 = strlen($back);
			foreach ($flds['data'] as $f) {
				if ($f['type'] == 'u' and $f['options_array'][0] == '1') {
					$back.= '<input type="hidden" name="authorfieldid" value="'.$f['fieldId'].'" />';
				}
				if ($f['type'] == 'I' and $f['options_array'][0] == '1') {
					$back.= '<input type="hidden" name="authoripid" value="'.$f['fieldId'].'" />';
				}
				if ($f['type'] == 'g' and $f['options_array'][0] == '1') {
					$back.= '<input type="hidden" name="authorgroupfieldid" value="'.$f['fieldId'].'" />';
				}
				if ($f['type'] == 'q') {
					$back .= '<input type="hidden" name="track['.$f['fieldId'].']" />';
				}
				if (in_array($f['fieldId'],$outf)) {
					if ($showmandatory == 'y' and $f['isMandatory'] == 'y') {
						$onemandatory = true;
					}
					if (!empty($tpl) || !empty($wiki)) {
						$smarty->assign_by_ref('field_value', $f);
						$smarty->assign('showmandatory', $showmandatory);
						if (isset($item)) {
							$smarty->assign_by_ref('item', $item);
						}
						$smarty->assign('f_'.$f['fieldId'], $smarty->fetch('tracker_item_field_input.tpl'));
					} else {
						if (in_array($f['fieldId'], $optional)) {
							$f['name'] = "<i>".$f['name']."</i>";
						}
						if ($f['type'] != 'h') {
						$back.= "<tr><td";
						if (!empty($colwidth)){
							$back .= " width='".$colwidth."'";
						}
						$back .= ">".wikiplugin_tracker_name($f['fieldId'], $f['name'], $field_errors);
						if ($showmandatory == 'y' and $f['isMandatory'] == 'y') {
							$back.= "&nbsp;<strong class='mandatory_star'>*</strong>&nbsp;";
						}
						$back.= "</td><td>";
						} else {
						$back .= "<tr><th colspan='2'>".wikiplugin_tracker_name($f['fieldId'], $f['name'], $field_errors);
						}
						$smarty->assign_by_ref('field_value', $f);
						if (isset($item)) {
							$smarty->assign_by_ref('item', $item);
						}
						$back .= $smarty->fetch('tracker_item_field_input.tpl');
					}

					if (!empty($f['description']) && $f['type'] != 'h' && $f['type'] != 'S') {
						$back .= '<br />';
						if ($f['descriptionIsParsed'] == 'y') {
							$back .= $tikilib->parse_data($f['description']);
						} else {
							$back .= '<i>'.$f['description'].'</i>';
						}
					}
					if (empty($tpl) && empty($wiki)) {
					if ($f['type'] != 'h'){
						$back.= "</td></tr>";
						} else {
						$back.= "</th></tr>";						
						}
					}
					if (!empty($f['http_request']) && !empty($itemId)) {
						$js .= 'selectValues("trackerIdList='.$f['http_request'][0].'&fieldlist='.$f['http_request'][3].'&filterfield='.$f['http_request'][1].'&status='.$f['http_request'][4].'&mandatory='.$f['http_request'][6].'&filtervalue='.$f['http_request'][7].'&selected='.$f['http_request'][8].'","'.$f['http_request'][5].'");';
					}
				}
			}
			if (!empty($tpl)) {
				$smarty->security = true;
				$back .= $smarty->fetch($tpl);
			} elseif (!empty($wiki)) {
				$smarty->security = true;
				$back .= $smarty->fetch('wiki:'.$wiki);
			}
			if ($prefs['feature_antibot'] == 'y' && empty($user) && !$_SESSION['in_tracker']) {
				// in_tracker session var checking is for tiki-register.php
				$back .= $smarty->fetch('antibot.tpl');
			}
			if (empty($tpl) && empty($wiki)) {
				$back.= "<tr><td></td><td>";
			}
			if (!empty($preview)) {
				$back .= "<input type='submit' name='tr_preview' value='".tra($preview)."' />";
			}
			$back .= "<input type='submit' name='action' value='".tra($action)."' />";
			if ($showmandatory == 'y' and $onemandatory) {
				$back.= "<em class='mandatory_note'>".tra("Fields marked with a * are mandatory.")."</em>";
			}
			if (empty($tpl) && empty($wiki)) {
				$back.= "</td></tr>";
				$back.= "</table>";
			} else {
				$back .= '</div>';
			}
			$back.= '</form>';
			if (!empty($js)) {
				$back .= '<script type="text/javascript">'.$js.'</script>';
			}
			if (!empty($page))
				$back .= '~/np~';
			$smarty->assign_by_ref('tiki_p_admin_trackers', $tiki_p_admin_trackers);
		return $back;
	}
	else {
		if (isset($_REQUEST['trackit']) and $_REQUEST['trackit'] == $trackerId)
			$smarty->assign('wikiplugin_tracker', $trackerId);//used in vote plugin
		$id = ' id="wikiplugin_tracker'.$iTRACKER.'"';
		if ($showtitle == 'y') {
			$back.= '<div class="titlebar"'.$id.'>'.$tracker["name"].'</div>';
			$id = '';
		}
		if ($showdesc == 'y') {
			$back.= '<div class="wikitext"'.$id.'>'.$tracker["description"].'</div><br />';
			$id = '';
		}
		$back.= "<div$id>".$data.'</div>';
		return $back;
	}
}
