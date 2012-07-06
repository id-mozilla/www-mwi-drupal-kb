<?php
/*
 * Tiki ALINK plugin. 
 *
 * DESCRIPTION: Creates a link to an anchor in a wiki page. Use in conjunction with the ANAME plugin, which specifies the location and name of the anchor.
 * 
 * INSTALLATION: Just put this file into your Tikiwiki site's lib/wiki-plugins folder.
 * 
 * USAGE SYNTAX:
 * 
 * 	{ALINK(
 *		aname=>anchorname	the name of the anchor you created using the ANAME plugin!
 * 	)}
 *	 	yourlinktext		the text of the link
 *	 {ALINK}
 *
 * EXAMPLE:  {ALINK(aname=myanchor)}click here{ALINK}
 * 
  */


function wikiplugin_alink_help() {
        return tra("Creates a link to an anchor in a wiki page. Use in conjunction with the ANAME plugin, which specifies the location and name of the anchor").":<br />~np~{ALINK(aname=>anchorname,pagename=>Wiki Page Name)}".tra("linktext")."{ALINK}~/np~<br />pagename is optional; if it is not present, links into the current file.";
}

function wikiplugin_alink_info() {
	return array(
		'name' => tra('Anchor Link'),
		'documentation' => 'PluginAlink',
		'description' => tra('Creates a link to an anchor within a page. Anchors can be created using the ANAME plugin.'),
		'prefs' => array('wikiplugin_alink'),
		'body' => tra('Anchor link label.'),
		'icon' => 'pics/icons/world_link.png',
		'params' => array(
			'aname' => array(
				'required' => true,
				'name' => 'Anchor name',
				'description' => tra('The anchor name as defined in the ANAME plugin.'),
			),
			'pagename' => array(
				'required' => false,
				'name' => tra('Page name'),
				'description' => tra('The name of the wiki page containing the anchor.'),
				'filter' => 'pagename',
			),
		),
	);
}

function wikiplugin_alink($data, $params)
{
        global $multilinguallib, $tikilib, $prefs;

	if( ! isset( $multilinguallib ) || !is_object($multilinguallib) ) {
		include_once('lib/multilingual/multilinguallib.php');// must be done even in feature_multilingual not set
	}
        extract ($params, EXTR_SKIP);

	if (!isset($aname)) {
		return ("<b>missing parameter for aname</b><br />");
	}

	// the following replace is necessary to maintain compliance with XHTML 1.0 Transitional
	// and the same behavior as tikilib.php. This will change when the world arrives at XHTML 1.0 Strict.
	$aname = ereg_replace('[^a-zA-Z0-9]+', '_', $aname);
		
	if( isset($pagename) && $pagename ) {
	    // Stolen, with some modifications, from tikilib.php line 4717-4723
	    if( $desc = $tikilib->page_exists_desc($pagename) )
	    {
		// to choose the best page language
		$bestLang = ($prefs['feature_multilingual'] == 'y' && $prefs['feature_best_language'] == 'y')? "&amp;bl" : ""; 
		// $bestLang = $prefs['feature_best_language'] == 'y' ? "&amp;bl" : ""; 

		return "<a title=\"$desc\" href='tiki-index.php?page=" . urlencode($pagename) . 
			$bestLang .  "#" . $aname .  "' class='wiki'>$data</a>";
	    } else {
		return $data . '<a href="tiki-editpage.php?page=' . urlencode($pagename) . 
			'" title="' . tra("Create page:") . ' ' . urlencode($pagename) . 
			'"  class="wiki wikinew">?</a>';
	    }
	} else {
	    return "<a href=\"#$aname\">$data</a>";
	}
}
