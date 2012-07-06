<?php
// $Id: tableofcontents.pages.inc,v 1.1.2.20 2009/12/09 17:58:38 alexiswilke Exp $

/**
 * @file
 * Applies the filter functions.
 */

/**
 * The table of contents object used to save all the data as
 * we go along (i.e. anchors info, table of contents variables, etc.)
 */
class TOC {
};

/**
 * Each header get an object with its level and identifier. We reuse
 * those to generate the TOC.
 *
 * The title (i.e. what appears between the opening and closing header
 * tags) is taken as the title appearing in your TOC.
 */
class TOC_Header {
};

/**
 * Replace a <!-- tableofcontents ... --> comment with [toc ...]
 */
function _tableofcontents_replace_comments($matches) {
  return '[toc ' . $matches[1] . ']';
}

/**
 * This function changes a header attributes. It adds an identifier in case
 * there are none. It registers the identifier if there is already one.
 *
 * \note
 * Note that the attributes (2nd match) always starts with a space if it
 * exists.
 *
 * @param[in] $matches The matches (level, attributes and title)
 *
 * @return The header with an identifier.
 */
function _tableofcontents_replace_headers($matches) {
  global $toc;
  global $toc_translations;

  $h = new TOC_Header;
  $h->level = $matches[2];
  $h->attributes = $matches[3];
  $h->title = $matches[4];

  // increase this level and reset all the sub-levels
  ++$toc->counters[$h->level];
  for ($l = $h->level + 1; $l <= 6; ++$l) {
    $toc->counters[$l] = 0;
  }
  if ($toc->level_from > $h->level) {
    $toc->level_from = $h->level;
  }
  $toc->level_to = $h->level;

  // determine the min./max. on the spot
  if ($toc->header_min) {
    if ($toc->header_min > $h->level) {
      $toc->header_min = $h->level;
    }
  }
  else {
    $toc->header_min = $h->level;
  }
  if ($toc->header_max) {
    if ($toc->header_max < $h->level) {
      $toc->header_max = $h->level;
    }
  }
  else {
    $toc->header_max = $h->level;
  }

  // check for existing id and use that if found
  if (preg_match('/\sid=(?:"([^"]*?)"|\'([^\']*?)\'|([^\s"\'>]))/i', $h->attributes, $id)) {
    // id[1] is with "
    // id[2] is with '
    // id[3] is without any quotes
    $h->identifier = $id[1] ? $id[1] : ($id[2] ? $id[2] : $id[3]);
  }
  else {
    // no existing identifier, create one using the header title
    //
    // HTML 4.01
    // ID and NAME tokens must begin with a letter ([A-Za-z]) and
    // may be followed by any number of letters, digits ([0-9]),
    // hyphens ("-"), underscores ("_"), colons (":"), and periods (".").
    //
    // 1. convert &nbsp; and other spaces into underscores
    // 2. convert &mdash; to '--'
    // 3. convert &amp; to 'and'
    // 4. remove any other entities
    // 5. remove any incompatible character
    // 6. remove digits at the start of the name (we could also add a letter?)
    //
    // sanitize accents by luron & deviantintegral (e.g. é=>e)
    // thanks to pathauto module for i18n-ascii.txt file
    if (!$toc_translations) {
      $path = drupal_get_path('module', 'tableofcontents');
      $toc_translations = parse_ini_file($path . '/i18n-ascii.txt');
    }
    $title = strtr($h->title, $toc_translations);

    $id = preg_replace(
      array(
        '/&nbsp;|\s/',
        '/\'/',
        '/&mdash;/',
        '/&amp;/',
        '/&[a-z]+;/',
        '/[^-A-Za-z0-9._:]/',
        '/^[0-9]+/',
      ),
      array(
        '_',     // &nbsp; and spaces
        '-',     // apostrophe, so it makes things slightly more readable
        '--',    // &mdash;
        'and',   // &amp;
        '',      // any other entity
        '',      // any character that is invalid as an ID name
        '',      // any digits at the start of the name
      ),
      $title
    );
    // ensure unicity
    foreach ($toc->headers as $header) {
      if ($header->identifier == $id) {
        $id .= '-' . $toc->header_id++;
        break;
      }
    }
    if (!$id) {
      // no identifier (i.e. title is composed exclusively of digits, entities, etc.)
      $id = 'header-' . $toc->header_id++;
    }
    $h->identifier = $id;

    // create a new header including the generated identifier
    $h->attributes .= ' id="' . $id . '"';
  }

  $h->number = theme('tableofcontents_number', $toc);
  if ($toc->number_headers) {
    $number = $h->number;
  }
  else {
    $number = '';
  }

  $result = $matches[1] . '<h' . $h->level . $h->attributes . '>' . $number . $h->title . '</h' . $h->level . '>';

  // save that header
  $toc->headers[] = $h;

  // Add a back to top before the header?
  if ($toc->back_to_top) {
    switch ($toc->back_to_top_location) {
    case 'header':
      $result .= '<div class="toc-back-to-top"><a href="#' . $toc->back_to_top_anchor . '">' . $toc->back_to_top . '</a></div>';
      break;

    default: //case 'bottom':
      if (!$toc->first_header) {
        $result = '<div class="toc-back-to-top"><a href="#' . $toc->back_to_top_anchor . '">' . $toc->back_to_top . '</a></div>' . $result;
      }
      break;

    }
  }
  $toc->first_header = FALSE;

  return $result;
}

/**
 * This function goes through all the headers found in the text.
 *
 * @bug
 * The pattern used assumes that you have at most ONE header per
 * line AND that the whole header is defined on ONE line.
 *
 * @param[in,out] $toc The table of content object
 * @param[in] $format The format being worked on
 * @param[in] $text The text to be parsed
 *
 * @return The text with headers transformed to include an identifier
 */
function _tableofcontents_headers(&$toc, $format, $text) {
  // initialize header variables
  $toc->headers = array();
  $toc->header_id = 1;
  $toc->header_min = 0;
  $toc->header_max = 0;
  $toc->back_to_top = check_plain(variable_get('tableofcontents_back_to_top_' . $format, ''));
  $toc->back_to_top_location = check_plain(variable_get('tableofcontents_back_to_top_location_' . $format, 'bottom'));
  $toc->back_to_top_anchor = check_plain(variable_get('tableofcontents_back_to_top_anchor_' . $format, 'toc'));
  $toc->scroll_back_to_top = check_plain(variable_get('tableofcontents_scroll_back_to_top_' . $format, FALSE));
  $toc->first_header = TRUE;
  $toc->numbering = variable_get('tableofcontents_numbering_' . $format, 0);
  $toc->number_mode = variable_get('tableofcontents_number_mode_' . $format, 0);
  $toc->number_start_letter = check_plain(variable_get('tableofcontents_number_start_letter_' . $format, ''));
  $toc->number_separator = check_plain(variable_get('tableofcontents_number_separator_' . $format, '.'));
  $toc->number_end_letter = check_plain(variable_get('tableofcontents_number_end_letter_' . $format, '.'));
  $toc->number_headers = variable_get('tableofcontents_number_headers_' . $format, FALSE);
  $toc->level_from = 6;
  $toc->level_to = 6;
  $toc->counters = array(0, 0, 0, 0, 0, 0, 0);  // used to generate a toc with advanced counters

  // note that the pattern below assumes that the headers
  // are properly defined in your HTML (i.e. a header cannot
  // include another)
  //
  // Note: we support having a [collapse] tag just before a header
  //       and even possibly a [/collapse] just before that!
  $result = preg_replace_callback(
    '%((?:(?:<p(?:\s[^>]*)?>\s*)?\[/collapse\](?:</p\s*>\s*)?)?'
      . '(?:<p(?:\s[^>]*)?>\s*)?\[collapse[^]]*?\](?:</p\s*>\s*)?)?'
      . '<h([1-6])(\s+[^>]*?)?>(.*?)</h[1-6]\s*>%si',
    '_tableofcontents_replace_headers',
    $text
  );

  return $result;
}

/**
 * Parse one option and save its value.
 *
 * The function returns NULL since we don't need to replace
 * anything in the source.
 *
 * @param[in] $opt An array with the option name and value.
 */
function _tableofcontents_parse_option($opt) {
  global $toc;

  $opt[1] = trim($opt[1]);
  $opt[2] = trim($opt[2]);
  switch (drupal_strtolower($opt[1])) {
  case 'hidden':
  case 'hide': // support bug from previous versions...
    switch (drupal_strtolower($opt[2])) {
    case '0':
    case 'false':
      $toc->hidden = FALSE;
      break;

    case '1':
    case 'true':
      $toc->hidden = TRUE;
      break;

    default:
      drupal_set_message(t("Table of contents error: !opt is not a valid boolean value for hidden. Expected 0, false, 1, or true.",
        array('!opt' => $opt[2])), 'error');
      break;

    }
    break;

  case 'title':
    $toc->$opt[1] = $opt[2];
    break;

  case 'list':
    switch (drupal_strtolower($opt[2])) {
    case 'ul':
    case 'ol':
      $toc->list = drupal_strtolower($opt[2]);
      break;

    case 'ordered':
      $toc->list = 'ol';
      break;

    case 'unordered':
      $toc->list = 'ul';
      break;

    default:
      drupal_set_message(t("Table of contents error: !opt is not a valid list type. Expected 'ul', 'unordered', 'ol', or 'ordered'",
        array('!opt' => $opt[2])), 'error');
      break;

    }
    break;

  case 'minlevel':
  case 'maxlevel':
    if (!is_numeric($opt[2]) || $opt[2] < 1 || $opt[2] > 6) {
      drupal_set_message(t("Table of contents error: !opt is not a valid level. Expected a number from 1 to 6",
        array('!opt' => $opt[2])), 'error');
    }
    $toc->$opt[1] = $opt[2];
    break;

  case 'attachments':
    switch (drupal_strtolower($opt[2])) {
    case '0':
    case 'false':
      $toc->attachments = FALSE;
      break;

    case '1':
    case 'true':
      $toc->attachments = TRUE;
      break;

    default:
      drupal_set_message(t("Table of contents error: !opt is not a valid boolean value for attachment. Expected 0, false, 1, or true.",
        array('!opt' => $opt[2])), 'error');
      break;

    }
    break;

  case 'hideshow':
    switch (drupal_strtolower($opt[2])) {
    case '0':
    case 'false':
      $toc->hideshow = FALSE;
      break;

    case '1':
    case 'true':
      $toc->hideshow = TRUE;
      break;

    default:
      drupal_set_message(t("Table of contents error: !opt is not a valid boolean value for hideshow. Expected 0, false, 1, or true.",
        array('!opt' => $opt[2])), 'error');
      break;

    }
    break;

  case 'collapsed':
    switch (drupal_strtolower($opt[2])) {
    case '0':
    case 'false':
      $toc->collapsed = FALSE;
      break;

    case '1':
    case 'true':
      $toc->collapsed = TRUE;
      break;

    default:
      drupal_set_message(t("Table of contents error: !opt is not a valid boolean value for collapsed. Expected 0, false, 1, or true.",
        array('!opt' => $opt[2])), 'error');
      break;

    }
    break;

  default:
    drupal_set_message(t("Table of contents error: !opt is not a valid option.",
        array('!opt' => $opt[1])), 'error');
    break;

  }
}

/**
 * Parse options and save them in your $toc variable.
 *
 * @warning
 * This function does NOT check whether you are authorized to parse
 * the options. It parses them. Period.
 *
 * @param[in,out] $toc The TOC object where the options are saved
 * @param[in] $options A string of options
 */
function _tableofcontents_get_options(&$toc, $options) {
  // Any options?
  if (!$options) {
    return;
  }
  // Make sure we don't miss the last parameter even if not properly ended
  $options .= ';';

  // We use a replace just so we get the callback
  preg_replace_callback(
    '/([A-Za-z0-9]+)[:=]\s*([^;]+);/',
    '_tableofcontents_parse_option',
    $options
  );

  if ($toc->minlevel > $toc->maxlevel) {
    drupal_set_message(t("Table of contents error: minlevel (!minlevel) is larger than maxlevel (!maxlevel), reverting to defaults.",
        array('!minlevel' => $toc->minlevel, '!maxlevel' => $toc->maxlevel)), 'error');
    $toc->minlevel = 2;
    $toc->maxlevel = 3;
  }

  // adjust the levels according to the available headers
  if ($toc->minlevel < $toc->header_min) { // avoid empty indentations
    $toc->minlevel = $toc->header_min;
    if ($toc->minlevel > $toc->maxlevel) {
      $toc->maxlevel = $toc->minlevel;
    }
  }
  if ($toc->maxlevel > $toc->header_max) { // this is much less important
    $toc->maxlevel = $toc->header_max;
  }
}

/**
 * This function replaces one [toc ...] tag.
 *
 * Note that all the toc tags receive the same headers, but each may have
 * a different set of parameters.
 *
 * @bug
 * Unfortunately, since we use the preg_replace_callback() functions
 * to increase speed we run in the problem of having to use a global
 * variable to hold all the TOC information. This function must make
 * a copy of it to avoid problems later.
 *
 * @param[in] $toc The TOC object
 * @param[in] $matches The matches of one [toc ...] tag
 *
 * @return The replacement for that [toc ...] tag
 */
function _tableofcontents_replace_toc_tags($matches) {
  global $toc;

  // we do not want options in this [toc ...] tag to affect the following [toc ...] tags
  $save_toc = $toc;

  // we always want to check the options because hidden:1 is always accepted!
  _tableofcontents_get_options($toc, $matches[1]);
  if ($toc->hidden) {
    $toc = $save_toc;

    // replace that table of contents with nothing (i.e. hiding it!)
    return '';
  }

  // Is user allowed to override options?
  if ($toc->allow_override) {
    // Nope, then restore!
    $toc = $save_toc;
  }

  // If attachments are enabled, prepare the $toc->files variable
  if ($toc->attachments && arg(0) == 'node' && is_numeric(arg(1))) {
    $node = node_load(arg(1));
    $toc->files = $node->files;
  }

  $result = theme('tableofcontents_toc', $toc);
  if ($result) {
    $toc->has_toc = TRUE;
  }

  $toc = $save_toc;

  return $result;
}

/**
 * This function reokaces the table of contents.
 *
 * It checks all the current flags such as whether the table should
 * be automatically added if no [toc] tag is found.
 *
 * @param[in] $text The text to work on.
 * @param[in] $format The format used to do the conversion.
 *
 * @return The $text with the [toc ...] replaced.
 */
function _tableofcontents_replace_toc($format, $text) {
  // we just don't have a way to pass that around our callbacks
  // (PHP is lacking on that one) but it is still a lot faster
  // that way.
  global $toc;
  static $remove_toc_pattern = '/(?:<p(?:\s[^>]*)?>)?\[toc(?:\s[^]]*?)?\](?:<\/p\s*>)?/';

  // if the user just asked to hide that, hide it and go away
  if (variable_get('tableofcontents_hide_table_' . $format, FALSE)) {
    return preg_replace($remove_toc_pattern, '', $text);
  }

  // keep a copy of the original just in case we bail out as is
  $org_text = $text;

  $toc = new TOC;

  // first make sure we want a table of contents
  if (strpos($text, "[toc") === FALSE) {
    $toc->automatic = variable_get('tableofcontents_automatic_' . $format, 0);
    switch ($toc->automatic) {
    default: // case 0
      return $org_text;

    case 1: // prepend
      $text = '[toc]' . $text;
      break;

    case 2: // append
      $text = $text . '[toc]';
      break;

    }
  }
  else {
    $toc->automatic = 0;
  }

  // Add an identifier in the existing headers and return info
  // about the tree
  $text = _tableofcontents_headers($toc, $format, $text);

  // Any headers? if not do nothing...
  $max = count($toc->headers);
  if ($max == 0) {
    // not even 1 header?!
    return preg_replace($remove_toc_pattern, '', $org_text);
  }

  // If automatic verify the limit, if not reached, bail out
  if ($toc->automatic) {
    $min_limit = variable_get('tableofcontents_min_limit_' . $format, 5);
    if ($max < $min_limit) {
      // Limit to reached!
      return preg_replace($remove_toc_pattern, '', $org_text);
    }
  }

  // get the default flags, these apply for each call to the
  // _tableofcontents_replace_toc_tags() function.
  // (i.e. that function should not be able to change $toc)
  $toc->hidden         = FALSE;
  $toc->title          = variable_get('tableofcontents_title_'          . $format, 'Table of contents');
  $toc->list           = variable_get('tableofcontents_list_type_'      . $format, 'ol');
  $toc->minlevel       = variable_get('tableofcontents_minlevel_'       . $format, 2);
  $toc->maxlevel       = variable_get('tableofcontents_maxlevel_'       . $format, 3);
  $toc->attachments    = variable_get('tableofcontents_attachments_'    . $format, FALSE);
  $toc->hideshow       = variable_get('tableofcontents_hide_show_'      . $format, TRUE);
  $toc->collapsed      = variable_get('tableofcontents_collapsed_'      . $format, FALSE);
  $toc->allow_override = variable_get('tableofcontents_allow_override_' . $format, TRUE);
  $toc->has_toc        = FALSE;

  if ($toc->minlevel < $toc->header_min) { // avoid empty indentations
    $toc->minlevel = $toc->header_min;
    if ($toc->minlevel > $toc->maxlevel) {
      $toc->maxlevel = $toc->minlevel;
    }
  }
  if ($toc->maxlevel > $toc->header_max) { // this is much less important
    if ($toc->minlevel > $toc->header_max) {
      $toc->maxlevel = $toc->minlevel;
    }
    else {
      $toc->maxlevel = $toc->header_max;
    }
  }

  $result = preg_replace_callback(
    '/(?:<p(?:\s[^>]*)?>)?\[toc(\s[^]]*?)?\](?:<\/p\s*>)?/',
    '_tableofcontents_replace_toc_tags',
    $text
  );

  if ($toc->has_toc) {
    // Add a back to top at the very end too
    if (!$toc->first_header && $toc->back_to_top && $toc->back_to_top_location == 'bottom') {
      $result .= '<div class="toc-back-to-top"><a href="#' . $toc->back_to_top_anchor . '">' . $toc->back_to_top . '</a></div>';
    }

    // If we have back to top and scroll on, advice script
    if ($toc->back_to_top && $toc->scroll_back_to_top) {
      $result .= '<script type="text/javascript">toc_scroll_back_to_top = 1;</script>';
    }
  }

  return $result;
}



/**
 * Prepare the text for the table of content.
 *
 * This function simply replaces the comment into a tag with square backets.
 *
 * @param[in] $delta The filter number
 * @param[in] $format The format number
 * @param[in] $text The text to filter
 */
function _tableofcontents_prepare($delta, $format, $text) {
  // support for PEAR wiki (http://drupal.org/node/150067#comment-937519)
  $text = str_replace('[[TOC]]', '[toc]', $text);
  return preg_replace_callback(
    array('%<!--\s*tableofcontents([^>]*?)-->%', '%\[\[TOC(.*?)\]\]%'),
    '_tableofcontents_replace_comments',
    $text
  );
}

/**
 * Transform the [toc ...] tags into the actual table of content.
 *
 * This function parses the headers to determine the content of the
 * table of content and then parses the [toc ...] tags.
 *
 * It uses sub-functions in order to make them reusable by other
 * parts such as the block implementation.
 *
 * @param[in] $delta The filter number
 * @param[in] $format The format number
 * @param[in] $text The text to filter
 */
function _tableofcontents_process($delta, $format, $text) {
  return _tableofcontents_replace_toc($format, $text);
}

/**
 * Theme the output of a table of contents.
 *
 * @param $toc
 *   A TOC object with the options, table of contents, headers,
 *   and files.
 *
 * @return
 *   Rendered HTML to be displayed.
 */
function theme_tableofcontents_toc($toc) {
  // TODO: at this time, the hideshow works with all TOCs at once
  if ($toc->hideshow) {
    //$hs = '<span class="toc-toggle-message"> [<a href="#" class="toc-toggle">' . $label . '</a>]</span>';
    $hs = '<span class="toc-toggle-message"> </span>';
    $js = '<script type="text/javascript">toc_collapse=' . ($toc->collapsed ? 1 : 0) . ';</script>';
  }
  else {
    $hs = '';
    $js = '';
  }
  // TODO: we should only define the id="toc" for the first TOC
  $output = $js . "<div class=\"toc\">\n"
    . "<div class=\"toc-title\">" . t($toc->title) . $hs . "</div>\n"
    . "<div class=\"toc-list\">\n"
      . "<" .  $toc->list . ">\n";

  $has_content = FALSE;

  // Process nested lists.
  $level = $toc->minlevel;
  foreach ($toc->headers as $index => $h) {

    $cur_level = $h->level;
    if ($cur_level >= $toc->minlevel && $cur_level <= $toc->maxlevel) {
      $has_content = TRUE;

      // Close list items but not before no items have been added.
      if ($cur_level == $level && $index != 0) {
        $output .= "</li>\n";
      }

      // Be sure to deal with skipping between non-adjacent h levels.
      for (; $cur_level > $level; ++$level) {
        $output .= "\n<" . $toc->list . ">\n";
      }
      for (; $cur_level < $level; --$level) {
        $output .= "</li>\n</" . $toc->list . ">\n";
        if ($cur_level == $level) {
          $output .= "</li>\n";
        }
      }

      if ($h->number) {
        $number = '<span class="toc-number">' . $h->number . '</span>';
      }
      else {
        $number = '';
      }
      $list_class = 'toc-level-' . ($level - $toc->minlevel + 1);

      $title = preg_replace('%</?a[^>]+>%', '', $h->title);

      // insert the li element
      $output .= "\t<li class=\"$list_class\"><a href=\"#" . $h->identifier . "\">" . $number . $title . "</a>";
    }
  }
  // Did we recurse back out? If not, close open lists.
  for (; $level > $toc->minlevel; --$level) {
    $output .= "</li>\n</" . $toc->list . ">\n";
  }
  $output .= "</li>\n";

  // If we've been passed an array of files, add them to the table of contents.
  if (!empty($toc->files)) {
    $has_files = FALSE;
    $files_output .= '<li><a href="#attachments">' . t("Attachments") . "</a>";
    $files_output .= "<" . $toc->list . ">";
    foreach($toc->files as $file) {
      if ($file->list && !$file->remove) {
        $has_files = TRUE;

        $url = strpos($file->fid, 'upload') === FALSE ? $file->filepath : file_create_filename($file->filename, file_create_path());
        $file_href = file_create_url($url);
        $file_text = $file->description ? $file->description : $file->filename;

        // If there is no function providing phptemplate_file_icon in
        // template.php or similar then the theme file_icon will fail gracefully.
        $files_output .= '<li>' . theme('file_icon', $file) . l($file_text, $file_href) . "</li>\n";
      }
    }
    if ($has_files) {
      $output .= $files_output . "</" . $toc->list . "></li>\n";
      $has_content = TRUE;
    }
  }

  if (!$has_content) {
    // this happens when all the levels are either too small or too large
    // and if there is no attachment either
    return '';
  }

  $output .= "</" . $toc->list . ">\n</div>\n</div>";

  return $output;
}

/*****************************************************************************/
/**
 * PHP Roman Numeral Library
 *
 * Copyright (c) 2008, reusablecode.blogspot.com; some rights reserved.
 *
 * This work is licensed under the Creative Commons Attribution License. To view
 * a copy of this license, visit http://creativecommons.org/licenses/by/3.0/ or
 * send a letter to Creative Commons, 559 Nathan Abbott Way, Stanford, California
 * 94305, USA.
 *
 * Roman numbers library from
 * http://snipplr.com/view/6314/roman-numerals/
 * http://reusablecode.blogspot.com/search/label/roman%20numerals
 */
 
// Convert Arabic numerals into Roman numerals.
function _tableofcontents_roman($arabic)
{
  //static $fractions = Array("", "¿", "¿¿", "¿¿¿", "¿¿¿¿", "¿¿¿¿¿", "S", "S¿", "S¿¿", "S¿¿¿", "S¿¿¿¿", "S¿¿¿¿¿", "I");
  static $ones = Array("", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX");
  static $tens = Array("", "X", "XX", "XXX", "XL", "L", "LX", "LXX", "LXXX", "XC");
  static $hundreds = Array("", "C", "CC", "CCC", "CD", "D", "DC", "DCC", "DCCC", "CM");
  static $thousands = Array("", "M", "MM", "MMM", "MMMM");
   
  if ($arabic > 4999) {
    // For large numbers (five thousand and above), a bar is placed above a base numeral to indicate multiplication by 1000.
    // Since it is not possible to illustrate this in plain ASCII, this function will refuse to convert numbers above 4999.
    //die("Cannot represent numbers larger than 4999 in plain ASCII.");
    return $arabic;
  }
  elseif ($arabic == 0) {
    // About 725, Bede or one of his colleagues used the letter N, the initial of nullae,
    // in a table of epacts, all written in Roman numerals, to indicate zero.
    return "N";
  }
  else {
    // Handle fractions that will round up to 1.
    //if (round(fmod($arabic, 1) * 12) == 12) {
    //  $arabic = round($arabic);
    //}
     
    // With special cases out of the way, we can proceed.
    // NOTE: modulous operator (%) only supports integers, so fmod() had to be used instead to support floating point.
    $roman = $thousands[($arabic - fmod($arabic, 1000)) / 1000];
    $arabic = fmod($arabic, 1000);
    $roman .= $hundreds[($arabic - fmod($arabic, 100)) / 100];
    $arabic = fmod($arabic, 100);
    $roman .= $tens[($arabic - fmod($arabic, 10)) / 10];
    $arabic = fmod($arabic, 10);
    $roman .= $ones[($arabic - fmod($arabic, 1)) / 1];
    $arabic = fmod($arabic, 1);
     
    // Handling for fractions.
    //if ($arabic > 0) {
    //  $roman .= $fractions[round($arabic * 12)];
    //}
     
    return $roman;
  }
}
 
// Expand subtractive notation in Roman numerals.
//function _tableofcontents_roman_expand($roman)
//{
//  $roman = str_replace("CM", "DCCCC", $roman);
//  $roman = str_replace("CD", "CCCC", $roman);
//  $roman = str_replace("XC", "LXXXX", $roman);
//  $roman = str_replace("XL", "XXXX", $roman);
//  $roman = str_replace("IX", "VIIII", $roman);
//  $roman = str_replace("IV", "IIII", $roman);
//  return $roman;
//}
 
// Compress Roman numerals using subtractive notation.
//function _tableofcontents_roman_compress($roman)
//{
//  $roman = str_replace("DCCCC", "CM", $roman);
//  $roman = str_replace("CCCC", "CD", $roman);
//  $roman = str_replace("LXXXX", "XC", $roman);
//  $roman = str_replace("XXXX", "XL", $roman);
//  $roman = str_replace("VIIII", "IX", $roman);
//  $roman = str_replace("IIII", "IV", $roman);
//  return $roman;
//}
 
// Convert Roman numerals into Arabic numerals.
//function _tableofcontents_arabic($roman)
//{
//  $result = 0;
//
//  // Remove subtractive notation.
//  $roman = roman_expand($roman);
//
//  // Calculate for each numeral.
//  $result += substr_count($roman, 'M') * 1000;
//  $result += substr_count($roman, 'D') * 500;
//  $result += substr_count($roman, 'C') * 100;
//  $result += substr_count($roman, 'L') * 50;
//  $result += substr_count($roman, 'X') * 10;
//  $result += substr_count($roman, 'V') * 5;
//  $result += substr_count($roman, 'I');
//  return $result;
//}
/*****************************************************************************/

/**
 * Transform a decimal number into a set of letters
 */
function _tableofcontents_letter($number) {
  while ($number > 0) {
    $result = chr($number % 26 + 64) . $result;
    $number = (int) ($number / 26);
  }

  return $result;
}

/**
 * Transform a decimal number in one of:
 *
 * @li decimal number
 * @li roman number
 * @li letters
 *
 * @param[in] $mode The type of number to transform into
 * @param[in] $number The number to transform
 *
 * @return The result
 */
function _tableofcontents_convert_number($mode, $number) {
  switch ($mode) {
  default: //case 0:
    return $number;

  case 1:
    return _tableofcontents_roman($number);

  case 2:
    return drupal_strtolower(_tableofcontents_roman($number));

  case 3:
    return _tableofcontents_letter($number);

  case 4:
    return drupal_strtolower(_tableofcontents_letter($number));

  case 5:
    return sprintf("0x%02x", $number);

  }
}

/**
 * Theme the output of a multi-level number.
 *
 * @param $toc
 *   A TOC object with the options and levels.
 *
 * @return
 *   Rendered HTML to be displayed.
 */
function theme_tableofcontents_number($toc) {
  $result = '';
  switch ($toc->numbering) {
  case 0:
    // no numbering
    return '';

  case 1:
    // "regular" (like <ol>)
    $result = _tableofcontents_convert_number($toc->number_mode, $toc->counters[$toc->level_to]);
    break;

  case 2:
  case 3:
    // 1., 1.1, 1.2, 2., 2.1, 2.2, ...
    $mode = $toc->number_mode;
    $result = _tableofcontents_convert_number($mode, $toc->counters[$toc->level_from]);
    for ($idx = $toc->level_from + 1; $idx <= $toc->level_to; ++$idx) {
      $result .= $toc->number_separator . _tableofcontents_convert_number($mode, $toc->counters[$idx]);
    }
    if ($toc->numbering == 3 && $toc->level_from == $toc->level_to) {
      $result .= $toc->number_separator . _tableofcontents_convert_number($mode, 0);
    }
    break;

  }

  // we add a space at the end (before the title)
  return $toc->number_start_letter . $result . $toc->number_end_letter . ' ';
}
