<?php
/*
 * Tiki ANAME plugin.
 *
 * DESCRIPTION: Creates an anchor in a wiki page. Use in conjunction with the ALINK plugin, which specifies a link to the anchor.
 * 
 * INSTALLATION: Just put this file into your Tikiwiki site's lib/wiki-plugins folder.
 * 
 * USAGE SYNTAX:
 * 
 * 	{ANAME()}
 *	anchorname		the name of the anchor. Use this as the aname=> parameter in the ALINK plugin!
 *	{ANAME}
 *
 * EXAMPLE:  {ANAME()}anchorname{ANAME}
 * 
  */


function wikiplugin_aname_help() {
        return tra("Creates an anchor in a wiki page. Use in conjunction with the ALINK plugin, which specifies a link to the anchor").":<br />~np~{ANAME()}anchorname{ANAME}~/np~";
}

function wikiplugin_aname_info() {
	return array(
		'name' => tra('Anchor Name'),
		'documentation' => 'PluginAname',
		'description' => tra('Inserts an anchor in the wiki page. Anchors can be linked to using the ALINK plugin.'),
		'prefs' => array('wikiplugin_aname'),
		'body' => tra('The name of the anchor.'),
		'params' => array(),
		'icon' => 'pics/icons/anchor.png',
	);
}

function wikiplugin_aname($data, $params)
{
        global $tikilib;
        extract ($params, EXTR_SKIP);
        
    // the following replace is necessary to maintain compliance with XHTML 1.0 Transitional
	// and the same behavior as tikilib.php and ALINK. This will change when the world arrives at XHTML 1.0 Strict.
	$data = ereg_replace('[^a-zA-Z0-9]+', '_', $data);

	return "<a id=\"$data\"></a>";
}
