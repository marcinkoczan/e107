<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2009 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 *
 *
 * $Source: /cvs_backup/e107_0.8/e107_admin/theme.php,v $
 * $Revision$
 * $Date$
 * $Author$
 */

require_once("../class2.php");
if (!getperms("1")) {
	header("location:".e_BASE."index.php");
	exit;
}

include_lan(e_LANGUAGEDIR.e_LANGUAGE.'/admin/lan_'.e_PAGE);

$e_sub_cat = 'theme_manage';

e107::css("inline","
h2 							{ text-align: right; margin-bottom: -30px; padding-right: 10px; }
.hide						{ display: none }
.admin-theme-thumb			{ height:130px;overflow:hidden;border:1px solid black; margin:0px; margin-bottom:10px; padding:0px;   }
.admin-theme-thumb:hover	{ opacity:0.4 }

.admin-theme-options		{ transition: opacity .20s ease-in-out;
							 -moz-transition: opacity .20s ease-in-out;
							 -webkit-transition: opacity .20s ease-in-out;
							 opacity:0; 
							 width:100%;
							 height:80px;
							 padding-top:50px;
							 white-space:nowrap;
							 background-color:black;
							 display:block;position:relative; text-align:center; vertical-align:middle; top:-141px;}

.admin-theme-options:hover	{ opacity:0.8; }
/*
.admin-theme-options button	{ background: none; color: white; border: none; padding:0px;  margin:3px; }
.admin-theme-options a {color: inherit; display:inline-block }
.admin-theme-options a span		{ color: inherit; padding-right: 5px; padding-left:0px; padding-top: 3px; margin-left:3px }
*/

.admin-theme-options .fa-check	{ color: #FAA732 }

.admin-theme-title			{ line-height: 18px; overflow:hidden; padding-left:5px; white-space:no-wrap; width:200px; position:relative; top:-132px; }

.admin-theme-select			{border:1px dotted silver;background-color:#DDDDDD;float:left }

.admin-theme-select-active	{ background-color:red;float:left }

.admin-theme-cell			{ width:202px; height:160px; padding:10px; -moz-border-radius: 5px; border-radius: 5px; margin:10px 10px 5px 0px}

.admin-theme-cell-default   { border:1px dotted silver; background-color:#DDDDDD }



.admin-theme-cell-site		{ background-color: #d9edf7;  border: 1px solid #bce8f1; }

.admin-theme-cell-admin	 	{ background-color:#FFFFD5; border: 1px solid #FFCC00; }


");


require_once(e_HANDLER."theme_handler.php");
$themec = new themeHandler;


$mode = varset($_GET['mode'],'main'); // (e_QUERY) ? e_QUERY :"main" ;


if(e_AJAX_REQUEST)
{
	define('e_IFRAME',true);
}

if(e_AJAX_REQUEST)
{
	switch ($_GET['action']) 
	{
		case 'login':	
			$mp = $themec->getMarketplace();	
			echo $mp->renderLoginForm();
			exit;	
		break;
		
		case 'download':
			$string =  base64_decode($_GET['src']);	
			parse_str($string, $p);
			$mp = $themec->getMarketplace();
			$mp->generateAuthKey($e107SiteUsername, $e107SiteUserpass);
			// Server flush useless. It's ajax ready state 4, we can't flush (sadly) before that (at least not for all browsers) 
			echo "<pre>Connecting...\n"; flush(); // FIXME change the modal default label, default is Loading...
			// download and flush
			$mp->download($p['id'], $p['mode'], $p['type']);
			echo "</pre>"; flush();
			exit;
		break;	
		
		case 'info':
			$string =  base64_decode($_GET['src']);	
			parse_str($string,$p);
			echo $themec->renderThemeInfo($p);
			exit;
		break;
		
		case 'preview':
			// Theme Info Ajax 
			$tm = (string) $_GET['id'];	
			$data = $themec->getThemeInfo($tm);
			echo $themec->renderThemeInfo($data);
			exit;	
		break;

	}
/*	
	if(vartrue($_GET['src'])) // Process Theme Download. 
	{					
		$string =  base64_decode($_GET['src']);	
		parse_str($string,$p);
		
		if(vartrue($_GET['info']))
		{		
			echo $themec->renderThemeInfo($p);
		//	print_a($p);
			exit;
		}
					
		$remotefile = $p['url'];
		
		e107::getFile()->download($remotefile,'theme');
		exit;
			
	}		
*/
	// Theme Info Ajax 
	// FIXME  addd action=preview to the url, remove this block
	$tm = (string) $_GET['id'];	
	$data = $themec->getThemeInfo($tm);
	echo $themec->renderThemeInfo($data);
	
	exit;	

}
else 
{
		require_once("auth.php");
	
	/*
		echo '

		 <div id="myModal" class="modal hide fade" tabindex="-1" role="dialog"  aria-hidden="true">
			    <div class="modal-header">
			    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			    &nbsp;
			    </div>
			    <div class="modal-body">
			    <p>Loading…</p>
			    </div>
			    <div class="modal-footer">
			    <a href="#" data-dismiss="modal" class="btn btn-primary">Close</a>
			    </div>
			    </div>';
	 */	
}				







if($mode == 'download' && !empty($_GET['src']))
{
		define('e_IFRAME', true);
		$frm = e107::getForm();
		$mes = e107::getMessage();		
		$string =  base64_decode($_GET['src']);	
		parse_str($string, $data);
		
		$mp = $themec->getMarketplace();	
	 	$mes->addSuccess("Connecting...");   

		if($mp->download($data['id'], $data['mode'], 'theme'))
		{
			// Auto install?
		//	$text = e107::getPlugin()->install($data['plugin_folder']); 
		//	$mes->addInfo($text); 
			echo $mes->render('default', 'success'); 
		}
		else
		{
			echo $mes->addError('Unable to continue')->render('default', 'error'); 
		}
		
		echo $mes->render('default', 'debug'); 
	
}
elseif(vartrue($_POST['selectadmin']))
{
	$mode = "admin";
}

if(vartrue($_POST['upload']))
{
	$mode = "upload";
}
elseif(vartrue($_POST['selectmain']) || varset($_POST['setUploadTheme']))
{
	$mode = "main";
}

if($mode == 'convert')
{
	new theme_builder;	
}
else 
{
	$themec -> showThemes($mode);	
}



// <a data-toggle="modal" href="'.e_SELF.'" data-target="#myModal" class="btn" >Launch demo modal</a>




require_once("footer.php");

function theme_adminmenu()
{
	//global $mode;
	
	$mode = varset($_GET['mode'],'main');
	
  // 	$e107 = &e107::getInstance();

		$var['main']['text'] = TPVLAN_33;
		$var['main']['link'] = e_SELF;

		$var['admin']['text'] = TPVLAN_34;
		$var['admin']['link'] = e_SELF."?mode=admin";

		$var['choose']['text'] = TPVLAN_51;
		$var['choose']['link'] = e_SELF."?mode=choose";
		
		$var['online']['text'] = "Find Themes";
		$var['online']['link'] = e_SELF."?mode=online";

		$var['upload']['text'] = TPVLAN_38;
		$var['upload']['link'] = e_SELF."?mode=upload";
		
		$var['convert']['text'] = "Convert";
		$var['convert']['link'] = e_SELF."?mode=convert";

      //  $selected = (e_QUERY) ? e_QUERY : "main";


		e107::getNav()->admin(TPVLAN_26, $mode, $var);
}

class theme_builder 
{
	var $themeName = "";
	var $remove = array();
	
		function __construct()
		{
			$this->themeName = $_GET['newtheme'];
			
			if(vartrue($_GET['step']) == 3)
			{	
				$this->step3();	
				return;
			}
			
			if(vartrue($_GET['step']) == 2)
			{
				$this->step2();	
			}
			else 
			{
				$this->step1();
			}
				
		}		
	
		function step1()
		{
			
			$fl = e107::getFile();
			$frm = e107::getForm();
			$ns = e107::getRender();
			$mes = e107::getMessage();
			
			$plugFolders = $fl->get_dirs(e_THEME);	
			foreach($plugFolders as $dir)
			{
				if(file_exists(e_THEME.$dir."/theme.xml") || $dir == 'templates')
				{
					continue;	
				}	
				$newDir[$dir] = $dir;
			}
			
			$mes->addInfo("This Wizard will build a theme.xml meta file for your theme.<br />
				Before you start: <ul>
						<li>Make sure your theme's directory is writable</b></li>
						<li>Select your theme's folder to begin.</li>
				</ul>
			");
			
			$text = $frm->open('createPlugin','get',e_SELF."?mode=convert");
			$text .= "<table class='table adminform'>
						<colgroup>
							<col class='col-label' />
							<col class='col-control' />
						</colgroup>
				<tr>
					<td>Select your theme's folder</td>
					<td>".$frm->select("newtheme",$newDir)."</td>
				</tr>";
				
				/*
				$text .= "
				<tr>
					<td>Create Files</td>
					<td>".$frm->checkbox('createFiles',1,1)."</td>
				</tr>";
				*/
				
			$text .= "				
				</table>
				<div class='buttons-bar center'>
				".$frm->admin_button('step', 2,'other','Go')."
				</div>";
			
			$text .= $frm->close();
			
			$ns->tablerender(TPVLAN_26.SEP."Converter".SEP."Step 1", $mes->render() . $text);			
			
		}	

		function step2()
		{
			$ns = e107::getRender();
			$mes = e107::getMessage();
			$frm = e107::getForm();
			
		
	
			
			$data = array(
				'main' 			=> array('name','lang','version','date', 'compatibility'),
				'author' 		=> array('name','url'),
				'summary' 		=> array('summary'),
				'description' 	=> array('description'),
				'keywords' 		=> array('one','two'),
				'category'		=> array('category'),
				'copyright'		=> array('copyright'),
				'stylesheets' 	=> array('stylesheets')
		//		'adminLinks'	=> array('url','description','icon','iconSmall','primary'),
		//		'sitelinks'		=> array('url','description','icon','iconSmall')
			);			
					
			$legacyFile = e_THEME.$this->themeName."/theme.php";		
			if(file_exists($legacyFile))
			{
				$legacyData = file_get_contents($legacyFile);	
				
				$regex = '/\$([\w]*)\s*=\s*("|\')([\w @.\/:<\>,\'\[\] !()]*)("|\');/im';
				preg_match_all($regex, $legacyData, $matches);
				
				$leg = array();
				
				foreach($matches[1] as $i => $m)
				{
					$leg[$m] = strip_tags($matches[3][$i]);	
					if(substr($m,0,5) == 'theme' || $m == "CUSTOMPAGES")
					{
						$search[] = $matches[0][$i];		
					}					
				}
				
				$defaults = array(
					"main-name"					=> vartrue($leg['themename']),
					"author-name"				=> vartrue($leg['themeauthor']),
					"author-url"				=> vartrue($leg['themewebsite']),
					"description-description"	=> '',
					"summary-summary"			=> vartrue($leg['themeinfo']),
					"custompages"				=> vartrue($leg['CUSTOMPAGES']),
				);
				
				$search[] = "Steve Dunstan";
				$search[] = "jalist@e107.org";
				
				$_SESSION['themebulder-remove'] = $search;
				
				$mes->addInfo("Loading theme.php file");						
			}	
			
			$text = $frm->open('newtheme-step3','post', e_SELF.'?mode=convert&newtheme='.$this->themeName.'&step=3');
			$text .= "<table class='table adminlist'>";
			foreach($data as $key=>$val)
			{
				$text.= "<tr><td>$key</td><td>
				<div class='controls'>";
				foreach($val as $type)
				{
					$nm = $key.'-'.$type;
					$name = "xml[$nm]";	
					$size = (count($val)==1) ? 'span7' : 'span2';
					$text .= "<div class='{$size}'>".$this->xmlInput($name, $key."-". $type, vartrue($defaults[$nm]))."</div>";	
				}	
			
				$text .= "</div></td></tr>";
				
				
			}
			
			
			$text .= "</table>";
			$text .= "
			<div class='buttons-bar center'>"
			.$frm->hidden('newtheme', $this->themeName)
			.$frm->hidden('xml[custompages]', trim(vartrue($leg['CUSTOMPAGES'])))
			.$frm->admin_button('step', 3,'other','Generate')."
			</div>";
			
			$text .= $frm->close();

			$ns->tablerender(TPVLAN_26.SEP."Converter".SEP."Step 2", $mes->render() . $text);		
		}
					
				
		function step3()
		{
			$ns = e107::getRender();
			$mes = e107::getMessage();
			
		//	print_a($_POST);
			
			if($_POST['xml'])
			{
				$xmlText =	$this->createXml($_POST['xml']);
			}	
			
			$ns->tablerender("theme.xml", $mes->render(). "<pre>".$xmlText."</pre>");
			
			$legacyFile = e_THEME.$this->themeName."/theme.php";		
			if(file_exists($legacyFile))
			{
				$legacyData = file_get_contents($legacyFile);	
			}
			
			$legacyData = $this->cleanUp($legacyData);
			
			$output = nl2br(htmlentities($legacyData));
			
			// $legacyData = str_replace("\n\n\n","\n",$legacyData);
			
			$ns->tablerender("theme.php (updated)",  $output);
		}	


		function cleanUp($text)
		{
			$search = array();
			$replace = array();
		
			$search[0] 	= '$HEADER ';
			$replace[0]	= '$HEADER["default"] ';

			$search[1] 	= '$FOOTER ';
			$replace[1]	= '$FOOTER["default"] ';	
			
			// Early 0.6 and 0.7 Themes 

			$search[2] 	= '$CUSTOMHEADER ';
			$replace[2]	= '$HEADER["custom"] ';

			$search[3] 	= '$CUSTOMFOOTER ';
			$replace[3]	= '$FOOTER["custom"] ';
			
			//TODO Handle v1.x style themes. eg. $CUSTOMHEADER['something'];

			$text = str_replace($_SESSION['themebulder-remove'],"",$text);
					
			$text = str_replace($search, $replace, $text);
			
			return $text;	
		}


		function createXml($data)
		{
			$ns = e107::getRender();
			$mes = e107::getMessage();
			$tp = e107::getParser();
			
			foreach($data as $key=>$val)
			{
				$key = strtoupper(str_replace("-","_",$key));
				$newArray[$key] = $val;			
			}	
				
			if(vartrue($newArray['CUSTOMPAGES']))
			{
				$newArray['CUSTOMPAGES'] = trim($newArray['CUSTOMPAGES']);			
				$LAYOUTS = "\n<layout name='custom' title='Custom'>\n";
				$LAYOUTS .= "			<custompages>{CUSTOMPAGES}</custompages>\n";
				$LAYOUTS .= "		</layout>";
			}
			else
			{		
				$LAYOUTS = "";
			}
			
			if(vartrue($newArray['STYLESHEETS_STYLESHEETS']))
			{
				$STYLESHEETS = "\n\t<stylesheets>\n";
				foreach($newArray['STYLESHEETS_STYLESHEETS'] as $val)
				{
					$STYLESHEETS .= "\t\t<css file=\"".$val['file']."\" name=\"".$val['name']."\" />\n";	
				}
				$STYLESHEETS .= "\t</stylesheets>";
				
				unset($newArray['STYLESHEETS_STYLESHEETS']);
			}
			else 
			{
				$STYLESHEETS = "";
			}
			
			$newArray['STYLESHEETS'] = $STYLESHEETS; 
			
		//	print_a($newArray);
			

$template = <<<TEMPLATE
<?xml version="1.0" encoding="utf-8"?>
<e107Theme name="{MAIN_NAME}" lan="{MAIN_LANG}" version="{MAIN_VERSION}" date="{MAIN_DATE}" compatibility="{MAIN_COMPATIBILITY}" >
	<author name="{AUTHOR_NAME}" url="{AUTHOR_URL}" />
	<summary lan="">{SUMMARY_SUMMARY}</summary>
	<description lan="">{DESCRIPTION_DESCRIPTION}</description>
	<keywords>
		<word>{KEYWORDS_ONE}</word>
		<word>{KEYWORDS_TWO}</word>
	</keywords>
	<category>{CATEGORY_CATEGORY}</category>
	<copyright>{COPYRIGHT_COPYRIGHT}</copyright>
	<screenshots>
		<image>preview.jpg</image>
		<image>fullpreview.jpg</image>
	</screenshots>{STYLESHEETS}
	<layouts>
		<layout name='default' title='Default' default='true' />{LAYOUTS}	
	</layouts>
</e107Theme>
TEMPLATE;

			
			$template = str_replace("{LAYOUTS}",$LAYOUTS, $template);
			
			$result = e107::getParser()->simpleParse($template, $newArray);
			$path = e_THEME.$this->themeName."/theme.xml";
			
			
			if(E107_DEBUG_LEVEL > 0)
			{
				$mes->addDebug("Debug Mode active - no file saved. ");
				return  htmlentities($result);	
			}
			
			
			
			if(file_put_contents($path,$result))
			{
				$mes->addSuccess("Saved: ".$path);
			}
			else 
			{
				$mes->addError("Couldn't Save: ".$path);
			}
			
			$mes->addWarning("Please update your theme.php file with the data below");
			
			return  htmlentities($result);


		}
				
	
	
		function xmlInput($name, $info, $default='')
		{
			$frm = e107::getForm();	
			list($cat,$type) = explode("-",$info);
			
			$size 		= 30;
			$help		= '';
			
			switch ($info)
			{
				
				case 'main-name':
					$help 		= "The name of your theme. (Must be written in English)";
					$required 	= true;
					$pattern 	= "[A-Za-z ]*";
				break;
		
				case 'main-lang':
					$help 		= "If you have a language file, enter the LAN_XXX value for the theme's name";
					$required 	= false;
					$placeholder= " ";
					$pattern 	= "[A-Z0-9_]*";
				break;
				
				case 'main-date':
					$help 		= "Creation date of your theme";
					$required 	= true;
				break;
				
				case 'main-version':
					$default 	= '1.0';
					$required 	= true;
					$help 		= "The version of your theme. Format: x.x";
					$pattern	= "^[\d]{1,2}\.[\d]{1,2}$";
				break;

				case 'main-compatibility':
					$default 	= '2.0';
					$required 	= true;
					$help 		= "Compatible with this version of e107";
					$pattern	= "^[\d]{1,2}\.[\d]{1,2}$";
				break;
				
				case 'author-name':
					$default 	= (vartrue($default)) ? $default : USERNAME;
					$required 	= true;
					$help 		= "Author Name";
					$pattern	= "[A-Za-z \.0-9]*";
				break;
				
				case 'author-url':
					$required 	= true;
					$help 		= "Author Website Url";
				//	$pattern	= "https?://.+";
				break;
				
				//case 'main-installRequired':
				//	return "Installation required: ".$frm->radio_switch($name,'',LAN_YES, LAN_NO);
				//break;	
				
				case 'summary-summary':
					$help 		= "A short one-line description of the plugin. (!@#$%^&* characters not permitted) <br />(Must be written in English)";
					$required 	= true;
					$size 		= 100;
					$placeholder= " ";
					$pattern	= "[A-Za-z,() \.0-9]*";
				break;	
				
				case 'keywords-one':
				case 'keywords-two':
					$help 		= "Keyword/Tag for this theme<br />(Must be written in English)";
					$required 	= true;
					$size 		= 20;
					$placeholder= " ";
					$pattern 	= '^[a-z]*$';
				break;	
				
				case 'description-description':
					$help 		= "A full description of the theme<br />(Must be written in English)";
					$required 	= true;
					$size 		= 100;
					$placeholder = " ";
					$pattern	= "[A-Za-z \.0-9]*";
				break;
				
					
				case 'category-category':
					$help 		= "What category of theme is this?";
					$required 	= true;
					$size 		= 20;
				break;
						
				default:
					
				break;
			}

			$req = ($required == true) ? "&required=1" : "";	
			$placeholder = (varset($placeholder)) ? $placeholder : $type;
			$pat = ($pattern) ? "&pattern=".$pattern : "";
			
			switch ($type) 
			{
				
				case 'stylesheets':
					$fl = e107::getFile();
			
					$fl->setMode('full');
					$stylesheets = $fl->get_files(e_THEME.$this->themeName."/", "\.css", $reject, 1);
					foreach($stylesheets as $key=>$path)
					{
						$file = str_replace(e_THEME.$this->themeName."/",'',$path);
						$text .= "<div class='row-fluid'>";
						$text .= "<div class='controls'>";
						$text .= "<div class='span3'>".$frm->checkbox($name.'['.$key.'][file]',$file, false, array('label'=>$file))."
						<div class='field-help'>Enable this stylesheet as a selectable option in the Theme Manager.</div></div>";
						$text .= "<div class='span3'>".$frm->text($name.'['.$key.'][name]', $default, $size, 'placeholder='.$file . $req. $pat)."
						<div class='field-help'>Give this stylesheet a name</div></div>";
					//	$text .= "<div class='span2'>".$frm->checkbox('css['.$key.'][file]',$file, false, array('label'=>$file))."</div>";
					//	$text .= "<div class='span2'>".$frm->text('css['.$key.'][name]', $default, $size, 'placeholder='.$placeholder . $req. $pat)."</div>";	
						$text .= "</div>";
						$text .= "</div>";
					}
						
					
					return $text;
				break;
				
				
				case 'date':
					$text = $frm->datepicker($name, time(), 'format=yyyy-mm-dd'.$req);		
				break;
				
				case 'description':
					$text = $frm->textarea($name,$default, 3, 100, $req);	// pattern not supported. 	
				break;
								
						
				case 'category':
										
				$allowedCategories = array(
					'generic', 'adult', 'blog', 'clan', 'children',
					'corporate', 'forum', 'gaming', 'gallery', 'news',
		 			'social', 'video', 'multimedia');	
					
				sort($allowedCategories);
				
					$text = $frm->select($name, $allowedCategories,'','useValues=1&required=1', true);	
				break;
				
				
				default:
					$text = $frm->text($name, $default, $size, 'placeholder='.$placeholder . $req. $pat);	
				break;
			}
	
			
			$text .= ($help) ? "<span class='field-help'>".$help."</span>" : "";
			return $text;
			
		}		
}



?>