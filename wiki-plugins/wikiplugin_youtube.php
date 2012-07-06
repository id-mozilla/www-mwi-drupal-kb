<?php

function wikiplugin_youtube_help() {
        return tra("Display youtube video in a wiki page").":<br />~np~{YOUTUBE(movie=>\"url_to_youtube_video\")}{YOUTUBE}~/np~";
}

function wikiplugin_youtube_info() {
	return array(
		'name' => tra('Youtube'),
		'documentation' => 'PluginYouTube',
		'description' => tra('Display youtube video in a wiki page'),
		'prefs' => array( 'wikiplugin_youtube' ),
		'params' => array(
			'movie' => array(
				'required' => true,
				'name' => 'Movie',
				'description' => tra('URL to the Youtube video'),
			),
			'width' => array(
				'required' => false,
				'name' => tra('width'),
				'description' => tra('Width in pixels'),
				'default' => 425,
			),
			'height' => array(
				'required' => false,
				'name' => tra('height'),
				'description' => tra('Height in pixels'),
				'default' => 350,
			),
			'quality' => array(
				'required' => false,
				'name' => tra('quality'),
				'description' => tra('quality'),
				'default' => 'high',
			),
		),
	);
}

function wikiplugin_youtube($data, $params) {
	
	extract ($params,EXTR_SKIP);

	if (empty($movie)) {
		return tra('Missing parameter movie to the youtube plugin');
	}
	
	if (!isset($width)) {
	    $width = "425";
	}	

	if (!isset($height)) {
	    $height = "350";
	}	

	if (!isset($quality)) {
	    $quality = "high";
	}

	$movie = "http://www.youtube.com/v/" . preg_replace('/http:\/\/(\w+\.)?youtube\.com\/watch\?v=/', '', $movie);

	$asetup = "<OBJECT CLASSID=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,29,0\" WIDTH=\"$width\" HEIGHT=\"$height\">";
	$asetup .= "<PARAM NAME=\"movie\" VALUE=\"$movie\">";
	$asetup .= "<PARAM NAME=\"quality\" VALUE=\"$quality\">";
	$asetup .= "<PARAM NAME=\"wmode\" VALUE=\"transparent\">";
	$asetup .= "<embed src=\"$movie\" quality=\"$quality\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\" type=\"application/x-shockwave-flash\" width=\"$width\" height=\"$height\" wmode=\"transparent\"></embed></object>";

	return $asetup;
}
