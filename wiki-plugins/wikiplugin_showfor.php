<?php
/*
 * $Header: /cvsroot/tikiwiki/tiki/lib/wiki-plugins/wikiplugin_div.php,v 1.10 2007/07/19 21:02:35 ricks99 Exp $
 *
 * DIV plugin. Creates a division block for the content. Forces the content 
 * to be aligned (left by default).
 * 
 * Syntax:
 * 
 *  {DIV([align=>left|right|center|justify][, bg=color][, width=>num[%]][, float=>left|right])}
 *   some content
 *  {DIV}
 * 
 */
function wikiplugin_showfor_help() {
	return tra("Insert a division block on wiki page").":<br />~np~{DIV(class=>class, type=>div|span|pre|i|b|tt|blockquote, align=>left|right|center|justify, bg=>color, width=>num[%], float=>left|right])}".tra("text")."{DIV}~/np~";
}

function wikiplugin_showfor($data, $params) {

	extract ($params,EXTR_SKIP);

    $all = join(" ", array_merge(explode("+",$params['browser']),explode("+",$params['os'])));
    
	$begin  = "<span class=\"".trim($all)."\">";
	$end = "</span>";
	return $begin . $data . $end;
}
?>
