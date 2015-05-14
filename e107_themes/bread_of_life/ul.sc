#<?php
/*
+---------------------------------------------------------------+
|	e107 website system
|
|	©Steve Dunstan 2001-2005
|	http://e107.org
|	jalist@e107.org
|
|	Released under the terms and conditions of the
|	GNU General Public License (http://gnu.org).
|
|
|   Tema bread_of_life
|   by Alf - http://www.e107works.org
|   Released under the terms and conditions of the
|   Creative Commons "Attribution-Noncommercial-Share Alike 3.0"
|   http://creativecommons.org/licenses/by-nc-sa/3.0/
+---------------------------------------------------------------+
*/
global $sql, $link_class, $page,$tp;

$sql -> db_Select('links', '*', "link_category = 1 and link_parent =0 and link_class IN (".USERCLASS_LIST.") ORDER BY link_order ASC");
$ulmenu = PRELINK."<ul>";			


$r='1';		// Needs to be a character - used for access key
while (($row = $sql -> db_Fetch()) && ($r <= "7"))
{
  extract($row);
  $link_url = $tp->replaceConstants($link_url,TRUE);
  
// Check if current page is one of the links - Test from kubrick is better
  $ltest = (e_QUERY ? e_PAGE."?".e_QUERY : e_PAGE);
  $rtest=substr(strrchr($link_url, "/"), 1);
  if (strpos($link_url, '://') === FALSE) { $link_url = e_BASE.$link_url; }
  if($ltest == $link_url || $rtest == e_PAGE){ $ulclass = 'onpage'; } else { $ulclass = 'offpage'; }

  $link_append = '';
  switch ($link_open) 
  {
    case 0:		// Open in same window
	  break;
	case 1:		// Simple open in new window
	  $link_append = " rel='external'";
	  break;
	case 4:		// 600 x 400 window
	  $link_append = " onclick=\"javascript:open_window('{$link_url}',600,400); return false;\"";
	  break;
	case 5:		// 800 x 600 window
	  $link_append = " onclick=\"javascript:open_window('{$link_url}',800,600); return false;\"";
	  break;
  }
   $lname = (defined(trim($link_name))) ? constant(trim($link_name)) : $link_name;
  $ulmenu .= "<li class='nav".$r."$ulclass'><a title='".varsettrue($link_description,'add a text description to this link')."' ";
  
  if ($ulclass) $ulmenu .= " class='$ulclass' ";

  $ulmenu .= " href='{$link_url}'".$link_append." accesskey='".$r."' ><span>$lname</span></a></li>";	


  $r++;
}
$ulmenu .= "</ul>\n".POSTLINK;

return $ulmenu;