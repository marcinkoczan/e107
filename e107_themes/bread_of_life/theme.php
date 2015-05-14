<?php
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

// [multilanguage]
@include_once(e_THEME."bread_of_life/languages/".e_LANGUAGE.".php");
@include_once(e_THEME."bread_of_life/languages/Italian.php");
setlocale(LC_TIME, 'it_IT');


// [theme]
$themename = "bread_of_life";
$themeversion = "1.0";
$themeauthor = "Alf";
$themeemail = "info@e107works.org";
$themewebsite = "http://www.e107works.org";
$themedate = "1 dic 2010";
$themeinfo = "<a rel='license' href='http://creativecommons.org/licenses/by-nc-sa/3.0/deed.it'><img alt='Creative Commons License' style='border-width:0; float:left; margin-right:10px; margin-bottom:10px' src='http://i.creativecommons.org/l/by-nc-sa/3.0/80x15.png'/></a>This work is licensed under a <a rel='license' href='http://creativecommons.org/licenses/by-nc-sa/3.0/deed.it'>Creative Commons Licence</a>&nbsp;&nbsp;<b>Support us</b><a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=1539832' title=''>&nbsp;<img src='https://www.paypal.com/it_IT/i/btn/x-click-but04.gif' style='vertical-align:middle' /></a>";

define("STANDARDS_MODE", "");
$xhtmlcompliant = TRUE;
$csscompliant = TRUE;
define("IMODE", "lite");


$register_sc[] = 'UL';
$register_sc[] = 'FOOTER';


function theme_head() {

	return "


	";

}

// [page defines used for css controll on per page basis]
define("e_PAGE", substr(strrchr($_SERVER['PHP_SELF'], "/"), 1));
define("e_PAGECLASS", str_replace(substr(strrchr(e_PAGE, "."), 0), "", e_PAGE));



//== LAYOUT DEFAULT

$HEADER = "

<div id='contenitore_principale'>

	<div id='testata'>

		<div id='nome_sito'>
	
			<h1>{SITENAME}</h1>
			{SITEDESCRIPTION}
		
		</div>
		
		<div id='barra_menu'>
		
			{UL}
			
		</div>		
	
	</div>
	
	<div id='sub_contenitore'>
	
		<div id='contenuti'>
		
			{SETSTYLE=menu3}
			{MENU=3}
			{SETSTYLE}		

";

$FOOTER = "

			{SETSTYLE=menu4}
			{MENU=4}
			{SETSTYLE}

		</div>
		
		<div id='blocco_dx'>
		
			{SETSTYLE=menu1}
			{MENU=1}
			{SETSTYLE}
			
			{SETSTYLE=menu2}
			{MENU=2}
			{SETSTYLE}		
		
		</div>		
		
	</div>
	
	<div id='footer'>
	
		{FOOTER}
	
	</div>	

</div>
";

//== LAYOUT CUSTOM

$CUSTOMHEADER['my_full'] = "

<div id='contenitore_principale'>

	<div id='testata'>

		<div id='nome_sito'>
	
			<h1>{SITENAME}</h1>
			{SITEDESCRIPTION}
		
		</div>
		
		<div id='barra_menu'>
		
			{UL}
			
		</div>		
	
	</div>
	
	<div id='sub_contenitore'>
	
		<div id='contenuti_custom'>
		
			{SETSTYLE=menu3}
			{MENU=3}
			{SETSTYLE}		

";

$CUSTOMFOOTER['my_full'] = "

		
			{SETSTYLE=menu4}
			{MENU=4}
			{SETSTYLE}

		</div>
		
	</div>
	
	<div id='footer'>
	
		{FOOTER}
	
	</div>

</div>
";


$CUSTOMPAGES['my_full']= "forum signup submitnews signup usersettings";


// [news] _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ 

function news_style($news) 
{
$mia_data  = strftime("%d", $news['news_datestamp']);
$mio_mese  = strftime("%b", $news['news_datestamp']);	
$mio_anno  = strftime("%y", $news['news_datestamp']);


$NEWSSTYLE = "

<div class='news'>

	<div class='data_news'>
	
		".$mia_data." ".$mio_mese." '".$mio_anno."
	
	</div>
	
	<div class='titolo_news'>
	
		<h2>{NEWSTITLE}</h2>
	
	</div>
	
	<div class='sommario_news'>
	
		{NEWSSUMMARY}	
	
	</div>
	
	<div class='corpo_news'>
	
		<div class='immagine_news'>
		
			{NEWSIMAGE}
		
		</div>
	
		{NEWSBODY}
		
		<div style='text-align:right'>
		
			{EXTENDED}
			
		</div>
	
	</div>
	
	<div class='accessori_news'>
	
	{NEWSCOMMENTS}&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;{EMAILICON}&nbsp;{PRINTICON}&nbsp;{PDFICON}&nbsp;&nbsp;&nbsp;{ADMINOPTIONS}
	
		<div style='float:right;padding-top:3px;'>
		
		{NEWSCATEGORY}&nbsp;{NEWSICON}
		
		</div>
	
	</div>



</div>

";


	return $NEWSSTYLE;
}


//lista news

$NEWSLISTSTYLE = "
<div class='new_list'>
		
	<div style='font-size:14px'>
		
		{NEWSDATE=short}
		
		<div style='float:right'>
		
			".LAN_THEME_3.":&nbsp;<span style='background:#ccc;padding:2px 4px;color:#fff'>{NEWSCOMMENTCOUNT}</span>
			
		</div>
			
	</div>
	<br />
	<div style='font-size:28px;color:#000'>
		
		{NEWSTITLELINK}
			
	</div>
	<br />
	<div style='font-style:italic'>
		
		{NEWSSUMMARY}
			
	</div>

</div>
";


define("ICONPRINTPDF", "acrobat.png");
define('ICONADMINOPTIONS', 'fileedit.gif');
define('ICONMAIL', 'email.png');
define('ICONPRINT', 'print.png');	
define("ICONSTYLE", "float:right; border:0");
define("COMMENTLINK", LAN_THEME_3);
define("COMMENTOFFSTRING", LAN_THEME_2);
define("PRE_EXTENDEDSTRING", "<br /> ");
define("EXTENDEDSTRING", LAN_THEME_4);
define("POST_EXTENDEDSTRING", " <br />");
define("TRACKBACKSTRING", LAN_THEME_5);
define("TRACKBACKBEFORESTRING", " | ");
define("CBWIDTH","96%");


// [linkstyle]
define(PRELINK, "");
define(POSTLINK, "");
define(LINKSTART, "");
define(LINKEND, "");
define(LINKDISPLAY, "");

//	[tablestyle]
function tablestyle($caption, $text, $mode)
{
	global $style;

	if($style == "menu1")//fatto
	{
		echo "<div class='menu1'><div class='caption1'><h3>$caption</h3></div><div class='padder1'>$text</div></div>";
	}
	
	else if($style == "menu2")
	{
		echo "<div class ='menu2'><div class='caption2'><h3>$caption</h3></div><div class='padder2'>$text</div></div>";
	}
	
	else if($style == "menu3")
	{
		echo "<div class='menu3'><div class='padder3'>$text</div></div>";
	}
	else if($style == "menu4")
	{
		echo "<div class='menu4'><div class='padder4'>$text</div></div>";
	}
	else
	{
		echo "<div class='caption'><h2>$caption</h2></div><div class='padder'>$text</div>";
	}
}


$COMMENTSTYLE = "
<div class='commenti'>

	<div class='avatar_commenti'>
		
		{AVATAR}
		{LEVEL}
		<span style='font-size:11px'>{COMMENTS}</span>
			
	</div>
	
	<span style='font-size:20px;font-weight:bold;'>
		{USERNAME}
	</span>
	
	&nbsp;{COMMENT}
	
	<div class='data_commenti'>
	
		{TIMEDATE}&nbsp;&nbsp;&nbsp;{REPLY}&nbsp;&nbsp;{COMMENTEDIT}
	
	</div>
	
</div>
";



?>