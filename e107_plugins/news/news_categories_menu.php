<?php
/**
 * Copyright (C) 2008-2011 e107 Inc (e107.org), Licensed under GNU GPL (http://www.gnu.org/licenses/gpl.txt)
 * $Id$
 * 
 * News categories menu
 */

if (!defined('e107_INIT')) { exit; }

$cacheString = 'nq_news_categories_menu_'.md5($parm);
$cached = e107::getCache()->retrieve($cacheString);
if(false === $cached)
{
	e107::plugLan('news');

	parse_str($parm, $parms);
	$ctree = e107::getObject('e_news_category_tree', null, e_HANDLER.'news_class.php');

	$template = e107::getTemplate('news', 'news_menu', 'category');

	$cached = $ctree->loadActive()->render($template, $parms, true);
	e107::getCache()->set($cacheString, $cached);
}

echo $cached;