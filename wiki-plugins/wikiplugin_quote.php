<?php
/*
 * $Id: /cvsroot/tikiwiki/tiki/lib/wiki-plugins/wikiplugin_quote.php,v 1.8.2.2 2008-03-20 19:32:20 sylvieg Exp $
 *
 * Tikiwiki QUOTE plugin.
 * 
 * Syntax:
 * 
 *  {QUOTE([replyto=>name])}
 *   Content inside box
 *  {QUOTE}
 * 
 */
function wikiplugin_quote_help() {
	return tra("Quote text by surrounding the text with a box, like the [QUOTE] BBCode").":<br />~np~{QUOTE(replyto=>name)}".tra("text")."{QUOTE}~/np~";
}

function wikiplugin_quote_info() {
	return array(
		'name' => tra('Quote'),
		'documentation' => 'PluginQuote',
		'description' => tra("Quote text by surrounding the text with a box, like the [QUOTE] BBCode"),
		'prefs' => array( 'wikiplugin_quote' ),
		'body' => tra('Quoted text'),
		'icon' => 'pics/icons/quotes.png',
		'filter' => 'text',
		'params' => array(
			'replyto' => array(
				'required' => false,
				'name' => tra('Reply to'),
				'description' => tra('Name of the quoted person.'),
				'filter' => 'text',
			),
		),
	);
}

function wikiplugin_quote($data, $params) {
	/* set default values for some args */
	
	// Remove first <ENTER> if exists...
//	if (substr($data, 0, 2) == "\r\n") $data = substr($data, 2);
	// trim space/returns from beginning and end
	$data = trim($data);
    
	extract ($params, EXTR_SKIP);
	if (!empty($replyto)) {
		$caption = $replyto .' '.tra('wrote:');
	} else {
		$caption = tra('Quote:');
	}
    
	$begin  = "<div class='quoteheader'>";
    $begin .= "$caption</div><div class='quotebody'>";
	$end = "</div>";
		// Prepend any newline char with br
		$data = preg_replace("/\\n/", "<br />", $data);
    // Insert "\n" at data begin if absent (so start-of-line-sensitive syntaxes will be parsed OK)
//    if (substr($data, 0, 1) != "\n") $data = "\n".$data;
	return $begin . $data . $end;
}
