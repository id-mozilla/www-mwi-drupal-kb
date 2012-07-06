<?php
// $Id: /cvsroot/tikiwiki/tiki/lib/wiki-plugins/wikiplugin_articles.php,v 1.27.2.1 2007-12-27 21:46:42 pkdille Exp $
// Includes articles listing in a wiki page
// Usage:
// {ARTICLES(max=>3,topic=>topicId)}{ARTICLES}
//

function wikiplugin_articles_help() {
        $help = tra("Includes articles listing into a wiki page");
        $help .= "<br />";
        $help .= tra("~np~{ARTICLES(max=>3, topic=>topicName, topicId=>id, type=>type, categId=>Category parent ID, lang=>en, sort=>columnName_asc|columnName_desc), quiet=>y|n, titleonly=>y|n}{ARTICLES}~/np~");

        return $help;
}

function wikiplugin_articles_info() {
	return array(
		'name' => tra('Article List'),
		'documentation' => 'PluginArticles',
		'description' => tra('Inserts a list of articles in the page.'),
		'prefs' => array( 'feature_articles', 'wikiplugin_articles' ),
		'params' => array(
			'max' => array(
				'required' => false,
				'name' => tra('Articles displayed'),
				'description' => tra('The number of articles to display in the list.'),
				'filter' => 'int',
			),
			'topic' => array(
				'required' => false,
				'name' => tra('Topics expression'),
				'description' => '[!]topic+topic+topic',
			),
			'topicId' => array(
				'required' => false,
				'name' => tra('Topic ID expression'),
				'description' => '[!]topicId+topicId+topicId',
			),
			'type' => array(
				'required' => false,
				'name' => tra('Type expression'),
				'description' => '[!]type+type+type',
			),
			'categId' => array(
				'required' => false,
				'name' => tra('Category ID'),
				'description' => tra('The ID of the category to list from.'),
				'filter' => 'digits',
			),
			'lang' => array(
				'required' => false,
				'name' => tra('Language'),
				'description' => tra('The article language to list.'),
				'filter' => 'lang',
			),
			'sort' => array(
				'required' => false,
				'name' => tra('Sort order'),
				'description' => tra('The column and order of the sort in columnName_asc or columnName_desc format.'),
				'filter' => 'word',
			),
			'quiet' => array(
				'required' => false,
				'name' => tra('Quiet'),
				'description' => tra('Whether to not report when there are no articles.'),
			),
			'titleonly' => array(
				'required' => false,
				'name' => tra('Title only'),
				'description' => tra('Whether to only show the title of the articles.'),
			),
		),
	);
}

function wikiplugin_articles($data,$params) {
	global $smarty, $tikilib, $prefs, $tiki_p_read_article, $tiki_p_articles_read_heading, $dbTiki, $pageLang;

	extract($params,EXTR_SKIP);
	if (($prefs['feature_articles'] !=  'y') || (($tiki_p_read_article != 'y') && ($tiki_p_articles_read_heading != 'y'))) {
		//	the feature is disabled or the user can't read articles, not even article headings
		return("");
	}
	if(!isset($max)) {$max='3';}
	if(!isset($start)) {$start='0';}

	if(!isset($topicId))
		$topicId='';
	if(!isset($topic))
		$topic='';

	if (!isset($sort))
		$sort = 'publishDate_desc';

	// Adds filtering by type if type is passed
	if(!isset($type)) 
		$type='';

	if (!isset($categId))
		$categId = '';

	if (!isset($lang))
		$lang = '';

	if (!isset($quiet))
		$quiet = 'n';
	$smarty->assign_by_ref('quiet', $quiet);

	include_once("lib/commentslib.php");
	$commentslib = new Comments($dbTiki);
	
	$listpages = $tikilib->list_articles($start, $max, $sort, '', 0, $tikilib->now, 'admin', $type, $topicId, 'y', $topic, $categId, '', '', $lang);
 	if ($prefs['feature_multilingual'] == 'y') {
		global $multilinguallib;
		include_once("lib/multilingual/multilinguallib.php");
		$listpages['data'] = $multilinguallib->selectLangList('article', $listpages['data'], $pageLang);
	}

	for ($i = 0; $i < count($listpages["data"]); $i++) {
		$listpages["data"][$i]["parsed_heading"] = $tikilib->parse_data($listpages["data"][$i]["heading"]);
		$comments_prefix_var='article:';
		$comments_object_var=$listpages["data"][$i]["articleId"];
		$comments_objectId = $comments_prefix_var.$comments_object_var;
		$listpages["data"][$i]["comments_cant"] = $commentslib->count_comments($comments_objectId);
		//print_r($listpages["data"][$i]['title']);
	}
	global $artlib; require_once ('lib/articles/artlib.php');

	$topics = $artlib->list_topics();
	$smarty->assign_by_ref('topics', $topics);

	if (!empty($topic) && !strstr($topic, '!') && !strstr($topic, '+')) {
		$smarty->assign_by_ref('topic', $topic);
	} elseif (!empty($topicId) &&  is_numeric($topicId)) {
		if (!empty($listpages['data'][0]['topicName']))
			$smarty->assign_by_ref('topic', $listpages['data'][0]['topicName']);
		else {
			$topic_info = $artlib->get_topic($topicId);
			if (isset($topic_info['name']))
				$smarty->assign_by_ref('topic', $topic_info['name']);
		}
	}
	if (!empty($type) && !strstr($type, '!') && !strstr($type, '+')) {
		$smarty->assign_by_ref('type', $type);
	}

	$smarty->assign_by_ref('listpages', $listpages["data"]);

	if (isset($titleonly) && $titleonly == 'y') {
		return "~np~ ".$smarty->fetch('tiki-view_articles-titleonly.tpl')." ~/np~";
	} else {
		return "~np~ ".$smarty->fetch('tiki-view_articles.tpl')." ~/np~";
	}
	//return str_replace("\n","",$smarty->fetch('tiki-view_articles.tpl')); // this considers the hour in the header like a link
}
