<?php

function wikiplugin_trackertimeline_info() {
	return array(
		'name' => tra( 'Tracker Timeline' ),
		'documentation' => 'PluginTrackerTimeline',
		'description' => tra('Timeline view of a tracker, can be used to display event schedules or gantt charts.'),
		'prefs' => array( 'wikiplugin_trackertimeline', 'feature_trackers' ),
		'params' => array(
			'tracker' => array(
				'required' => true,
				'name' => tra('Tracker ID'),
				'description' => tra('Numeric value'),
				'filter' => 'digits',
			),
			'title' => array(
				'required' => true,
				'name' => tra('Title Field'),
				'description' => tra('Tracker Field ID containing the item title.'),
				'filter' => 'digits',
			),
			'summary' => array(
				'required' => true,
				'name' => tra('Summary Field'),
				'description' => tra('Tracker Field ID containing the summary of the item. The summary will be displayed on the timeline when the item is focused.'),
				'filter' => 'digits',
			),
			'start' => array(
				'required' => true,
				'name' => tra('Start Date'),
				'description' => tra('Tracker Field ID containing the element start date. The field must be a datetime/jscalendar field.'),
				'filter' => 'digits',
			),
			'end' => array(
				'required' => true,
				'name' => tra('End Date'),
				'description' => tra('Tracker Field ID containing the element end date. The field must be a datetime/jscalendar field.'),
				'filter' => 'digits',
			),
			'group' => array(
				'required' => true,
				'name' => tra('Element Group'),
				'description' => tra('Tracker Field ID containing the element\'s group. Elements of a same group are displayed on the same row.'),
				'filter' => 'digits',
			),
			'lower' => array(
				'required' => true,
				'name' => tra('Lower Bound'),
				'description' => tra('Date from which element should be displayed. Date must be provided in YYYY-MM-DD HH:mm:ss format.'),
				'filter' => 'striptags',
			),
			'upper' => array(
				'required' => true,
				'name' => tra('Upper Bound'),
				'description' => tra('Date until which element should be displayed. Date must be provided in YYYY-MM-DD HH:mm:ss format.'),
				'filter' => 'striptags',
			),
			'scale1' => array(
				'required' => false,
				'name' => tra('Primary Scale Unit'),
				'description' => tra('hour, day, week, month or year (default to hour)'),
				'filter' => 'alpha',
			),
			'scale2' => array(
				'required' => false,
				'name' => tra('Secondary Scale Unit'),
				'description' => tra('hour, day, week, month, year or empty (default to empty)'),
				'filter' => 'alpha',
			),
			'link_group' => array(
				'required' => false,
				'name' => tra('Link Group Name'),
				'description' => tra('Convert the group name to a link. (y|n)'),
				'filter' => 'alpha',
			),
			'link_page' => array(
				'required' => false,
				'name' => tra('Page Link Field'),
				'description' => tra('Tracker Field ID containing the page name for item details.'),
				'filter' => 'digits',
			),
		),
	);
}

function wikiplugin_trackertimeline( $data, $params ) {
	global $trklib, $smarty, $tikilib;
	require_once 'lib/trackers/trackerlib.php';

	if( ! isset( $params['tracker'] ) )
		return "^" . tr("Missing parameter: %0", 'tracker') . "^";

	$start = strtotime( $params['lower'] );
	$end = strtotime( $params['upper'] );
	$size = $end - $start;

	if( $size <= 0 )
		return "^" . tr("Start date after end date.") . "^";
	
	$fieldIds = array( 
		$params['title'] => 'title', 
		$params['summary'] => 'summary', 
		$params['start'] => 'start', 
		$params['end'] => 'end', 
		$params['group'] => 'group',
	);

	if( isset($params['link_page']) ) {
		$fieldIds[ $params['link_page'] ] = 'link';
	}

	$fields = array();
	foreach( $fieldIds as $id => $label )
		$fields[$id] = $trklib->get_tracker_field( $id );

	$items = $trklib->list_items( $params['tracker'], 0, -1, '', $fields );

	$data = array();
	foreach( $items['data'] as $item ) {
		// Collect data
		$detail = array( 'item' => $item['itemId'] );
		foreach( $item['field_values'] as $field ) {
			$detail[ $fieldIds[$field['fieldId']] ] = $field['value'];
		}

		// Filter elements
		if( $detail['start'] >= $detail['end'] )
			continue;
		if( $detail['end'] <= $start || $detail['start'] > $end )
			continue;

		$detail['lstart'] = max( $start, $detail['start'] );
		$detail['lend'] = min( $end, $detail['end'] );
		$detail['lsize'] = round( ( $detail['lend'] - $detail['lstart'] ) / $size * 80 );

		$detail['fstart'] = date( 'H:i', $detail['start'] );
		$detail['fend'] = date( 'H:i', $detail['end'] );
		$detail['psummary'] = $tikilib->parse_data( $detail['summary'] );

		$detail['encoded'] = json_encode( $detail );

		// Add to data list
		if( ! array_key_exists( $detail['group'], $data ) )
			$data[$detail['group']] = array();
		$data[ $detail['group'] ][] = $detail;
	}

	$new = array();
	foreach( $data as $group => &$list ) {
		wp_ttl_organize( $group, $start, $size, $list, $new );
	}
	$data = array_merge( $data, $new );
	ksort($data);

	$smarty->assign( 'wp_ttl_data', $data );

	$layouts = array();

	if( isset( $params['scale2'] ) && $layout = wp_ttl_genlayout( $start, $end, $size, $params['scale2'] ) )
		$layouts[] = $layout;
	
	$layouts[] = wp_ttl_genlayout( $start, $end, $size, isset($params['scale1']) ? $params['scale1'] : 'hour' );

	$smarty->assign( 'layouts', $layouts );
	$smarty->assign( 'link_group_names', isset($params['link_group']) && $params['link_group'] == 'y' );

	return $smarty->fetch('wiki-plugins/wikiplugin_trackertimeline.tpl');
}

function wp_ttl_organize( $name, $base, $size, &$list, &$new ) {
	usort( $list, 'wp_ttl_sort_cb' );

	$first = $list;
	$list = array();
	$remaining = array();

	$pos = $base;
	foreach( $first as $item ) {
		if( $item['lstart'] < $pos ) {
			$remaining[] = $item;
			continue;
		}

		$item['lpad'] = round( ( $item['lstart'] - $pos ) / $size * 80 );
		$pos = $item['lend'];

		$list[] = $item;
	}

	if( count( $remaining ) ) {
		wp_ttl_organize( "$name ", $base, $size, $remaining, $new );
		$new["$name "] = $remaining;
	}
}

function wp_ttl_sort_cb( $a, $b ) {
	if( $a['start'] == $b['start'] )
		return 0;
	if( $a['start'] < $b['start'] )
		return -1;
	if( $a['start'] > $b['start'] )
		return 1;
}

function wp_ttl_genlayout( $start, $end, $full, $type ) {
	switch( $type ) {
	case 'empty': 
	case '':
		return;
	case 'hour': 
		$size = 3600;
		$pos = $start - ( $start + $size ) % $size;
		break;
	case 'day': 
		$size = 86400;

		if( date( 'H:i:s', $start ) == '00:00:00' ) {
			$pos = $start;
		} else {
			$pos = strtotime( date( 'Y-m-d 00:00:00', $start + $size ) );
		}
		break;
	case 'week':
		$size = 604800;

		if( date( 'H:i:sw', $start ) == '00:00:000' ) {
			$pos = $start;
		} else {
			$pos = strtotime( date( 'Y-m-d 00:00:00', $start + $size ) );
		}

		$pos += 86400 * ( 6 - date('w', $start) );

		break;
	case 'month':
		if( date( 'd H:i:s', $start ) == '01 00:00:00' ) {
			$pos = $start;
		} else {
			$pos = strtotime( date( 'Y-m-01 00:00:00', strtotime( 'next month', $start ) ) );
		}

		$size = date( 't', $pos ) * 86400;

		break;
	case 'year':
		if( date( 'm-d H:i:s', $start ) == '01-01 00:00:00' ) {
			$pos = $start;
		} else {
			$pos = strtotime( date( 'Y-01-01 00:00:00', strtotime( 'next year', $start ) ) );
		}

		$size = date( 'L', $pos ) * 86400 + 86400*365;
		break;
	}

	$layout = array(
		'size' => round( $size / $full * 80 ),
		'blocks' => array(
		),
	);

	$layout['pad'] = round( ($pos - $start) / $full * 80 );

	for( $i = $pos; $end > $i + $size; $i += $size ) {
		switch( $type ) {
		case 'hour': $layout['blocks'][] = date( 'H:i', $i ); break;
		case 'day': $layout['blocks'][] = date( 'j', $i ); break;
		case 'week': $layout['blocks'][] = date( 'j', $i ); break;
		case 'month': $layout['blocks'][] = date( 'M', $i ); break;
		case 'year': $layout['blocks'][] = date( 'Y', $i ); break;
		}

		switch( $type ) {
		case 'month':
			$size = date( 't', $i ) * 86400;
			break;
		case 'year':
			$size = date( 'L', $i ) * 86400 + 86400*365;
			break;
		}
	}

	return $layout;
}
