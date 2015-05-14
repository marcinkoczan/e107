<?php
/*
 * e107 website system
 *
 * Copyright (C) 2008-2013 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 * Administration - File inspector
 * 
 */
ini_set('zlib.output_compression', 0);
header('Content-Encoding: none'); // turn off gzip. 
ob_implicit_flush(true);
ob_end_flush();



require_once('../class2.php');

e107::lan('core','fileinspector', true);

if (!getperms('Y'))
{
	header('location:'.e_BASE.'index.php');
	exit;
}
$error_handler->debug = FALSE;
//require_once(e_HANDLER.'form_handler.php');
$DOCS_DIRECTORY = $HELP_DIRECTORY;		// Give a sensible, albeit probably invalid, value
if (substr($HELP_DIRECTORY,-5,5) == 'help/')
{
	$DOCS_DIRECTORY = substr($HELP_DIRECTORY,0,-5);		// Whatever $HELP_DIRECTORY is set to, assume docs are in a subdirectory called 'help' off it
}
$maindirs = array('admin' => $ADMIN_DIRECTORY, 'files' => $FILES_DIRECTORY, 'images' => $IMAGES_DIRECTORY, 'themes' => $THEMES_DIRECTORY, 'plugins' => $PLUGINS_DIRECTORY, 'handlers' => $HANDLERS_DIRECTORY, 'languages' => $LANGUAGES_DIRECTORY, 'downloads' => $DOWNLOADS_DIRECTORY, 'docs' => $DOCS_DIRECTORY);
foreach ($maindirs as $maindirs_key => $maindirs_value) {
	$coredir[$maindirs_key] = substr($maindirs_value, 0, -1);
}

require_once('core_image.php');

//$rs = new form;
set_time_limit(18000);
$e_sub_cat = 'fileinspector';


if(isset($_GET['scan']))
{
	
	session_write_close();
	while (@ob_end_clean()); 
	

	//header("Content-type: text/html; charset=".CHARSET, true);
	//$css_file = file_exists(e_THEME.$pref['admintheme'].'/'.$pref['admincss']) ? e_THEME.$pref['admintheme'].'/'.$pref['admincss'] : e_THEME.$pref['admintheme'].'/'.$pref['admincss'];
	$fi = new file_inspector;

	

	 echo "<!DOCTYPE html>
	 <html> 
	 <head>  	
	 <title>Results</title>  
	 ".$fi->headerCss()." ".headerjs()."
	 <body style='background-color:#EEEEEE'>\n";

//	define('e_IFRAME', true);
//	require_once(e_ADMIN."auth.php");
		 
	 
	
	 
		
	// echo "<br />loading..";
	
	// echo "..";
	//flush();

	$_POST = $_GET;
	
	if(vartrue($_GET['exploit']))
	{
		$fi->exploit();	
	}
	else
	{
		$fi->scan_results();	
	}
	
//	require_once(e_ADMIN."footer.php");
	
	echo "</body></html>";
	
	exit();
		
}
else
{
	$fi = new file_inspector;
	require_once(e_ADMIN.'auth.php');
	
	
//	if (e_QUERY) {
	// $fi -> snapshot_interface();
	//}

	if (varset($_POST['scan'])) 
	{
		 $fi->exploit_interface();
		 $fi->scan_config();
	} 
	elseif($_GET['mode'] == 'run')
	{
		$mes = e107::getMessage();
		$mes->addInfo("You need to run a scan first!");	
		echo $mes->render();
	}
	else 
	{
		$fi->scan_config();
	}
}




class file_inspector {
	
	var $root_dir;
	var $files = array();
	var $parent;
	var $count = array();
	var $results = 0;
	var $totalFiles = 0;
	var $coredir = array();
	var $progress_units = 0;
	
	function file_inspector()
	{
		global $e107,$core_image;
		
		//$this->totalFiles =  count($core_image,COUNT_RECURSIVE);
		$this->countFiles($core_image);
		
		$this -> root_dir = $e107 -> file_path;
		if (substr($this -> root_dir, -1) == '/') {
			$this -> root_dir = substr($this -> root_dir, 0, -1);
		}
		if ($_POST['core'] == 'fail') {
			$_POST['integrity'] = TRUE;
		}
		if (MAGIC_QUOTES_GPC && vartrue($_POST['regex'])) {
			$_POST['regex'] = stripslashes($_POST['regex']);
		}
		if ($_POST['regex']) {
			if ($_POST['core'] == 'fail') {
				$_POST['core'] = 'all';
			}
			$_POST['missing'] = 0;
			$_POST['integrity'] = 0;
		}
	}	
	
	// Find the Total number of core files before scanning begins. 
	function countFiles($array)
	{
		foreach($array as $k=>$val)
		{
			if(is_array($val))
			{
				$this->countFiles($val);
			}
			elseif($val)
			{
				$this->totalFiles++;		
			}	
			
		}	
	}

	
	function scan_config() 
	{
		global $rs, $pref;
		$frm = e107::getForm();
		$ns = e107::getRender();
		
		if($_GET['mode'] == 'run')
		{
			return;	
		}
		

		$text = "<div style='text-align: center'>
		<form action='".e_SELF."?mode=run' method='post' id='scanform'>
		<table class='table table-striped adminform'>
		<tr>
		<td class='fcaption' colspan='2'>".FC_LAN_2."</td>
		</tr>";
		
		$coreOpts = array('full'=>FC_LAN_6, 'all'=>FC_LAN_4, 'none'=> FC_LAN_12);
		
		$text .= "<tr>
		<td style='width: 35%'>
		".FC_LAN_3." ".FC_LAN_5.":
		</td>
		<td colspan='2' style='width: 65%'>".$frm->select('core',$coreOpts,$_POST['core'])."	</td>
		</tr>";
		
		
		$dispOpt = array('tree'=>FC_LAN_15, 'list'=>FC_LAN_16);	
		$text .= "<tr>
		<td style='width: 35%'>
		".FC_LAN_14.":
		</td>
		<td colspan='2' style='width: 65%'>".$frm->select('type', $dispOpt, $_POST['type'])."	</td>
		</td>
		</tr>";
		
		
		
		$text .= "<tr>
		<td style='width: 35%'>
		".FC_LAN_3." ".FC_LAN_13.":
		</td>
		<td colspan='2' style='width: 65%'>
		<input type='radio' name='missing' value='1'".(($_POST['missing'] == '1' || !isset($_POST['missing'])) ? " checked='checked'" : "")." /> ".FC_LAN_9."&nbsp;&nbsp;
		<input type='radio' name='missing' value='0'".($_POST['missing'] == '0' ? " checked='checked'" : "")." /> ".FC_LAN_10."&nbsp;&nbsp;
		</td>
		</tr>";
		
		$text .= "<tr>
		<td style='width: 35%'>
		".FC_LAN_3." ".FC_LAN_7.":
		</td>
		<td colspan='2' style='width: 65%'>
		<input type='radio' name='noncore' value='1'".(($_POST['noncore'] == '1' || !isset($_POST['noncore'])) ? " checked='checked'" : "")." /> ".FC_LAN_9."&nbsp;&nbsp;
		<input type='radio' name='noncore' value='0'".($_POST['noncore'] == '0' ? " checked='checked'" : "")." /> ".FC_LAN_10."&nbsp;&nbsp;
		<input type='checkbox' name='nolang' value='1'".(($_POST['nolang'] == '1' || !isset($_POST['nolang'])) ? " checked='checked'" : "")." /> Exclude Language-Files&nbsp;&nbsp;
		
		</td>
		</tr>";
		
		$text .= "<tr>
		<td style='width: 35%'>
		".FC_LAN_3." ".FC_LAN_21.":
		</td>
		<td colspan='2' style='width: 65%'>
		<input type='radio' name='oldcore' value='1'".(($_POST['oldcore'] == '1' || !isset($_POST['oldcore'])) ? " checked='checked'" : "")." /> ".FC_LAN_9."&nbsp;&nbsp;
		<input type='radio' name='oldcore' value='0'".($_POST['oldcore'] == '0' ? " checked='checked'" : "")." /> ".FC_LAN_10."&nbsp;&nbsp;
		</td>
		</tr>";
		

		
		
		$text .= "<tr>
		<td style='width: 35%'>
		".FC_LAN_8.":
		</td>
		<td style='width: 65%; vertical-align: top'>
		<input type='radio' name='integrity' value='1'".(($_POST['integrity'] == '1' || !isset($_POST['integrity'])) ? " checked='checked'" : "")." /> ".FC_LAN_9."&nbsp;&nbsp;
		<input type='radio' name='integrity' value='0'".($_POST['integrity'] == '0' ? " checked='checked'" : "")." /> ".FC_LAN_10."&nbsp;&nbsp;
		</td></tr>";
		
	
		

		
		if ($pref['developer']) {
			$text .= "<tr>
			<td class='fcaption' colspan='2'>".FC_LAN_17."</td>
			</tr>";
			
			$text .= "<tr>
			<td style='width: 35%'>
			".FC_LAN_18.":
			</td>
			<td colspan='2' style='width: 65%'>
			#<input class='tbox' type='text' name='regex' size='40' value='".htmlentities($_POST['regex'], ENT_QUOTES)."' />#<input class='tbox' type='text' name='mod' size='5' value='".$_POST['mod']."' />
			</td>
			</tr>";
			
			$text .= "<tr>
			<td style='width: 35%'>
			".FC_LAN_19.":
			</td>
			<td colspan='2' style='width: 65%'>
			<input type='checkbox' name='num' value='1'".(($_POST['num'] || !isset($_POST['num'])) ? " checked='checked'" : "")." />
			</td>
			</tr>";
			
			$text .= "<tr>
			<td style='width: 35%'>
			".FC_LAN_20.":
			</td>
			<td colspan='2' style='width: 65%'>
			<input type='checkbox' name='line' value='1'".(($_POST['line'] || !isset($_POST['line'])) ? " checked='checked'" : "")." />
			</td>
			</tr>";
		}
		
		$text .= "
		</table>
		<div class='buttons-bar center'>
		".$frm->admin_button('scan', FC_LAN_11, 'other')."
		</div>
		</form>
		</div>";

		$ns -> tablerender(FC_LAN_1, $text);
		
	}
	
	function scan($dir, $image)
	{
		
		
		$handle = opendir($dir.'/');
		while (false !== ($readdir = readdir($handle)))
		{
			
			if ($readdir != '.' && $readdir != '..' && $readdir != '/' && $readdir != 'CVS' && $readdir != 'Thumbs.db' && (strpos('._', $readdir) === FALSE))
			{
				$path = $dir.'/'.$readdir;
				if (is_dir($path))
				{
					$dirs[$path] = $readdir;
				}
				elseif (!isset($image[$readdir]))
				{
					$files[$readdir] = $this -> checksum($path, TRUE);
				}
			}
		}
		closedir($handle);
		
		if (isset($dirs)) {
			ksort ($dirs);
			foreach ($dirs as $dir_path => $dir_list) {
				$list[$dir_list] = ($set = $this -> scan($dir_path, $image[$dir_list])) ? $set : array();
			}
		}
		
		if (isset($files)) {
			ksort ($files);
			foreach ($files as $file_name => $file_list) {
				$list[$file_name] = $file_list;
			}
		}
		
		return $list;
	}

	// Given a full path and filename, looks it up in the list to determine valid actions; returns:
	//	  'check' - file is expected to be present, and validity is to be checked
	//	  'ignore' - file may or may not be present - check its validity if found, but not an error if missing
	//	  'uncalc' - file must be present, but its integrity cannot be checked.
	//	  'nocalc' - file may be present, but its integrity cannot be checked. Not an error if missing
	function check_action($dir, $name)
	{
		global $coredir;
	  
	  if ($name == 'e_inspect.php') { return 'nocalc'; }		// Special case for plugin integrity checking
	  
	  $filename = $dir.'/'.$name;
	  $admin_dir = $this->root_dir.'/'.$coredir['admin'].'/';
	  $image_dir  = $this->root_dir.'/'.$coredir['images'].'/';
	  $test_list = array();

	  // Files that are unable to be checked
	  $test_list[$admin_dir.'core_image.php'] = 'uncalc';
	  $test_list[$this->root_dir.'/e107_config.php'] = 'uncalc';

      // Files that are likely to be renamed by user
	  $test_list[$admin_dir.'filetypes_.php'] = 'ignore';
	  $test_list[$this->root_dir.'/e107.htaccess'] = 'ignore';
	  $test_list[$this->root_dir.'/e107.robots.txt'] = 'ignore';
	  
	  if (isset($test_list[$filename])) { return $test_list[$filename]; }
	  return 'check';
	}

	
	// This function does the real work
	//  $list -
	//	$deprecated
	// 	$level
	//	$dir
	//	&$tree_end
	//	&$parent_expand
	function inspect($list, $deprecated, $level, $dir, &$tree_end, &$parent_expand)
	{
	  global $coredir,$lng;
	  
	  $langs = explode(",",e_LANLIST);
	  $lang_short = array();
	  
	  foreach($langs as $k=>$val)
	  {
	 		if($val == "English") // Core release language. 
			{
				unset($langs[$k]);
				continue;
			}
			$lang_short[] = $lng->convert($val);
	  }
	  	
	
	  unset ($childOut);
	  $parent_expand = false;
	  if (substr($dir, -1) == '/')
	  {
		$dir = substr($dir, 0, -1);
	  }
	  $dir_id = dechex(crc32($dir));
	  $this -> files[$dir_id]['.']['level'] = $level;
	  $this -> files[$dir_id]['.']['parent'] = $this -> parent;
	  $this -> files[$dir_id]['.']['file'] = $dir;
	  $directory = $level ? basename($dir) : SITENAME;
	  $level++;
	  	
	//	print_a($list);
	
	
	
	$this->sendProgress(vartrue($this->count['core']['num']),$this->totalFiles,FR_LAN_1);	
			
	  foreach ($list as $key => $value)
	  {

		$this -> parent = $dir_id;
		if (is_array($value))
		{ // Entry is a subdirectory - recurse another level
		  $path = $dir.'/'.$key;
		  $child_open = false;
		  $child_end = true;
		  $sub_text .= $this -> inspect($value, $deprecated[$key], $level, $path, $child_end, $child_expand);
		  $tree_end = false;
		  if ($child_expand)
		  {
			$parent_expand = true;
			$last_expand = true;
		  }
		}
		else
		{
		  $this->sendProgress(vartrue($this->count['core']['num']),$this->totalFiles,FR_LAN_1);	
		  $path = $dir.'/'.$key;
		  
		  $fid = strtolower($key);
		  $this -> files[$dir_id][$fid]['file'] = ($_POST['type'] == 'tree') ? $key : $path;
		  if (($this -> files[$dir_id][$fid]['size'] = filesize($path)) !== FALSE)
		  {	// We're checking a file here
			if ($_POST['core'] != 'none')
			{		// Look at core files
			  $this -> count['core']['num']++;
			  $this -> count['core']['size'] += $this -> files[$dir_id][$fid]['size'];
			  if ($_POST['regex'])
			  {	// Developer prefs activated - search file contents according to regex
				$file_content = file($path);		// Get contents of file
				if (($this -> files[$dir_id][$fid]['size'] = filesize($path)) !== FALSE)
				{
				  if ($this -> files[$dir_id][$fid]['lines'] = preg_grep("#".$_POST['regex']."#".$_POST['mod'], $file_content))
				  {	// Search string found - add file to list
					$this -> files[$dir_id][$fid]['file'] = ($_POST['type'] == 'tree') ? $key : $path;
					$this -> files[$dir_id][$fid]['icon'] = 'file_core.png';
					$dir_icon = 'fileinspector.png';
					$parent_expand = TRUE;
					$this -> results++;
				  }
				  else
				  {	// Search string not found - discard from list
					unset($this -> files[$dir_id][$fid]);
					$known[$dir_id][$fid] = true;
					$dir_icon = ($dir_icon == 'fileinspector.png') ? $dir_icon : 'folder.png';
				  }
				}
			  }
			  else
			  {
				if ($_POST['integrity'])
				{	// Actually check file integrity
				  switch ($this_action = $this->check_action($dir,$key))
				  {
					case 'ignore' :
				    case 'check' :
					  if ($this -> checksum($path) != $value)
					  {
						$this -> count['fail']['num']++;
						$this -> count['fail']['size'] += $this -> files[$dir_id][$fid]['size'];
						$this -> files[$dir_id][$fid]['icon'] = 'file_fail.png';
						$dir_icon = 'folder_fail.png';
						$parent_expand = TRUE;
					  }
					  else
					  {
						$this -> count['pass']['num']++;
						$this -> count['pass']['size'] += $this -> files[$dir_id][$fid]['size'];
						if ($_POST['core'] != 'fail')
						{
						  $this -> files[$dir_id][$fid]['icon'] = 'file_check.png';
						  $dir_icon = ($dir_icon == 'folder_fail.png' || $dir_icon == 'folder_missing.png') ? $dir_icon : 'folder_check.png';
						}
						else
						{
						  unset($this -> files[$dir_id][$fid]);
						  $known[$dir_id][$fid] = true;
						}
					  }
					  break;
					case 'uncalc' :
					case 'nocalc' :
					  $this -> count['uncalculable']['num']++;
					  $this -> count['uncalculable']['size'] += $this -> files[$dir_id][$fid]['size'];
					  if ($_POST['core'] != 'fail')
					  {
						$this -> files[$dir_id][$fid]['icon'] = 'file_uncalc.png';
					  }
					  else
					  {
						unset($this -> files[$dir_id][$fid]);
						$known[$dir_id][$fid] = true;
					  }
					  break;
				  }
				}
				else
				{	// Just identify as core file
				  $this -> files[$dir_id][$fid]['icon'] = 'file_core.png';
				}
			  }
			}
			else
			{
			  unset ($this -> files[$dir_id][$fid]);
			  $known[$dir_id][$fid] = true;
			}
		  }
		  else if ($_POST['missing'])
		  {
			switch ($this_action = $this->check_action($dir,$key))
			{
			  case 'check' :
			  case 'uncalc' :
				$this -> count['missing']['num']++;
				$this -> files[$dir_id][$fid]['icon'] = 'file_missing.png';
				$dir_icon = ($dir_icon == 'folder_fail.png') ? $dir_icon : 'folder_missing.png';
				$parent_expand = TRUE;
				break;
			  case 'ignore' :
			  case 'nocalc' :
			    // These files can be missing without error - delete from the list
				unset ($this -> files[$dir_id][$fid]);
				$known[$dir_id][$fid] = true;
			    break;
			}
		  }
		  else
		  {
			unset ($this -> files[$dir_id][$fid]);
		  }
		}
	  }
		
		if ($_POST['noncore'] || $_POST['oldcore'])
		{
			$handle = opendir($dir.'/');
			
			while (is_resource($handle) && false !== ($readdir = readdir($handle)))
			{
				$prog_count = $this->count['unknown']['num'] + $this->count['deprecated']['num'];
				$this->sendProgress($prog_count,$this->totalFiles,FR_LAN_1);	
				
				if ($readdir != '.' && $readdir != '..' && $readdir != '/' && $readdir != '.svn' && $readdir != 'CVS' && $readdir != 'Thumbs.db' && (strpos('._', $readdir) === FALSE))
				{
					if (is_dir($dir.'/'.$readdir))
					{
						if (!isset($list[$readdir]) && ($level > 1 || $readdir == 'e107_install'))
						{
							$child_open = false;
							$child_end = true;
							$sub_text .= $this->inspect(array(), $deprecated[$readdir], $level, $dir.'/'.$readdir, $child_end, $child_expand);
							$tree_end = false;
							if ($child_expand)
							{
								$parent_expand = true;
								$last_expand = true;
							}
						}
					}
					else 
					{
												
						if($_POST['nolang']) // Hide Non-core Languages. 
						{
							
							// PHP Lang files. 		
							$lreg = "/[\/_](".implode("|",$langs).")/";						
							if(preg_match($lreg, $dir.'/'.$readdir))
							{								
								continue;
							}
							
							// TinyMce Lang files. 									
							$lregs = "/[\/_](".implode("|",$lang_short).")_dlg\.js/";
							if(preg_match($lregs, $dir.'/'.$readdir))
							{								
								continue;
							}	
							
							// PhpMailer Lang Files. 
							$lregsm = "/[\/_]phpmailer\.lang-(".implode("|",$lang_short).")\.php/";
							if(preg_match($lregsm, $dir.'/'.$readdir))
							{								
								continue;
							}	
						}
						
						$aid = strtolower($readdir);
												
						if (!isset($this -> files[$dir_id][$aid]['file']) && !$known[$dir_id][$aid]) {
							if (strpos($dir.'/'.$readdir, 'htmlarea') === false) {
								if (isset($deprecated[$readdir])) {
									if ($_POST['oldcore']) {
										$this -> files[$dir_id][$aid]['file'] = ($_POST['type'] == 'tree') ? $readdir : $dir.'/'.$readdir;
										$this -> files[$dir_id][$aid]['size'] = filesize($dir.'/'.$readdir);
										$this -> files[$dir_id][$aid]['icon'] = 'file_old.png';
										$this -> count['deprecated']['num']++;
										$this -> count['deprecated']['size'] += $this -> files[$dir_id][$aid]['size'];
									}
								} else {
									if ($_POST['noncore']) {
										$this -> files[$dir_id][$aid]['file'] = ($_POST['type'] == 'tree') ? $readdir : $dir.'/'.$readdir;
										$this -> files[$dir_id][$aid]['size'] = filesize($dir.'/'.$readdir);
										$this -> files[$dir_id][$aid]['icon'] = 'file_unknown.png';
										$this -> count['unknown']['num']++;
										$this -> count['unknown']['size'] += $this -> files[$dir_id][$aid]['size'];
									}
								}
							} else {
								$this -> files[$dir_id][$aid]['file'] = ($_POST['type'] == 'tree') ? $readdir : $dir.'/'.$readdir;
								$this -> files[$dir_id][$aid]['size'] = filesize($dir.'/'.$readdir);
								$this -> files[$dir_id][$aid]['icon'] = 'file_warning.png';
								$this -> count['warning']['num']++;
								$this -> count['warning']['size'] += $this -> files[$dir_id][$aid]['size'];
								$this -> count['deprecated']['num']++;
								$this -> count['deprecated']['size'] += $this -> files[$dir_id][$aid]['size'];
								$dir_icon = 'folder_warning.png';
								$parent_expand = TRUE;
							}
							if ($_POST['regex']) {
								$file_content = file($dir.'/'.$readdir);
								if ($this -> files[$dir_id][$aid]['lines'] = preg_grep("#".$_POST['regex']."#".$_POST['mod'], $file_content)) {
									$dir_icon = 'fileinspector.png';
									$parent_expand = TRUE;
									$this -> results++;
								} else {
									unset($this -> files[$dir_id][$aid]);
									$dir_icon = ($dir_icon == 'fileinspector.png') ? $dir_icon : 'folder.png';
								}
							} else {
								if (isset($deprecated[$readdir])) {
									if ($_POST['oldcore']) {
										$dir_icon = ($dir_icon == 'folder_warning.png' || $dir_icon == 'folder_fail.png' || $dir_icon == 'folder_missing.png' || $dir_icon == 'folder_old_dir.png') ? $dir_icon : 'folder_old.png';
										$parent_expand = TRUE;
									}
								} else {
									if ($_POST['noncore']) {
										$dir_icon = ($dir_icon == 'folder_warning.png' || $dir_icon == 'folder_fail.png' || $dir_icon == 'folder_missing.png' || $dir_icon == 'folder_old.png' || $dir_icon == 'folder_old_dir.png') ? $dir_icon : 'folder_unknown.png';
										$parent_expand = TRUE;
									}
								}
							}
						} else if ($_POST['core'] == 'none') {
							unset($this -> files[$dir_id][$aid]);
						}
					}
				}
			}
			closedir($handle);
			
		}

		$this->sendProgress($this->count['core']['num'],$this->totalFiles,FR_LAN_1);	
		
		$dir_icon = $dir_icon ? $dir_icon : 'folder.png';
		$icon = "<img src='".e_IMAGE."fileinspector/".$dir_icon."' class='i' alt='' />";
		$hide = ($last_expand && $dir_icon != 'folder_core.png') ? "" : "style='display: none'";
		$text = "<div class='d' style='margin-left: ".($level * 8)."px'>";
		$text .= $tree_end ? "<img src='".e_IMAGE."fileinspector/blank.png' class='e' alt='' />" : "<span onclick=\"ec('".$dir_id."')\"><img src='".e_IMAGE."fileinspector/".($hide ? 'expand.png' : 'contract.png')."' class='e' alt='' id='e_".$dir_id."' /></span>";
		$text .= "&nbsp;<span onclick=\"sh('f_".$dir_id."')\">".$icon."&nbsp;".$directory."</span>";
		$text .= $tree_end ? "" : "<div ".$hide." id='d_".$dir_id."'>".$sub_text."</div>";
		$text .= "</div>";
		
		$this -> files[$dir_id]['.']['icon'] = $dir_icon;
		
		return $text;
	}

	function scan_results()
	{	
		global $ns, $rs, $core_image, $deprecated_image;
		$scan_text = $this -> inspect($core_image, $deprecated_image, 0, $this -> root_dir);

		$this->sendProgress($this->totalFiles,$this->totalFiles,' &nbsp; &nbsp; &nbsp;');
	
		echo "<div style='display:block;height:100px'>&nbsp;</div>";

		if ($_POST['type'] == 'tree') {
			$text = "<div style='text-align:center'>
			<table class='table adminlist'>
			<tr>
			<td class='fcaption' colspan='2'>".FR_LAN_2."</td>
			</tr>";

			$text .= "<tr style='display: none'><td style='width:60%'></td><td style='width:40%'></td></tr>";
		
			$text .= "<tr>
			<td style='width:60%;padding:0px'>
			<div style='height: 400px; width:101%; overflow: auto'>
			".$scan_text."
			</div>
			</td>
			<td style='width:40%; vertical-align: top'><div style='height: 400px; overflow: auto'>";
		} 
		else 
		{
			$text = "<div style='text-align:center'>
			<table class='table table-striped adminlist'>
			<tr>
			<td class='fcaption' colspan='2'>".FR_LAN_2."</td>
			</tr>";
			
			$text .= "<tr>
			<td colspan='2'>";
		}

		$text .= "<table class='table-striped table adminlist' id='initial'>";
		
		if ($_POST['type'] == 'tree') 
		{
			$text .= "<tr><th class='f' >".FR_LAN_3."</th>
			<th class='s' style='text-align: right; padding-right: 4px' onclick=\"sh('f_".dechex(crc32($this -> root_dir))."')\">
			<b class='caret'></b></th></tr>";
		} 
		else 
		{
			$text .= "<tr><th class='f' colspan='2'>".FR_LAN_3."</th></tr>";
		}

		if ($_POST['core'] != 'none') 
		{
			$text .= "<tr><td class='f'><img src='".e_IMAGE."fileinspector/file_core.png' class='i' alt='' />&nbsp;".FR_LAN_4.":&nbsp;".($this -> count['core']['num'] ? $this -> count['core']['num'] : FR_LAN_21)."&nbsp;</td>
			<td class='s'>".$this -> parsesize($this -> count['core']['size'], 2)."</td></tr>";
		}
		if ($_POST['missing']) {
			$text .= "<tr><td class='f' colspan='2'><img src='".e_IMAGE."fileinspector/file_missing.png' class='i' alt='' />&nbsp;".FR_LAN_22.":&nbsp;".($this -> count['missing']['num'] ? $this -> count['missing']['num'] : FR_LAN_21)."&nbsp;</td></tr>";
		}
		if ($_POST['noncore']) {
			$text .= "<tr><td class='f'><img src='".e_IMAGE."fileinspector/file_unknown.png' class='i' alt='' />&nbsp;".FR_LAN_5.":&nbsp;".($this -> count['unknown']['num'] ? $this -> count['unknown']['num'] : FR_LAN_21)."&nbsp;</td><td class='s'>".$this -> parsesize($this -> count['unknown']['size'], 2)."</td></tr>";
		}
		if ($_POST['oldcore']) {
			$text .= "<tr><td class='f'><img src='".e_IMAGE."fileinspector/file_old.png' class='i' alt='' />&nbsp;".FR_LAN_24.":&nbsp;".($this -> count['deprecated']['num'] ? $this -> count['deprecated']['num'] : FR_LAN_21)."&nbsp;</td><td class='s'>".$this -> parsesize($this -> count['deprecated']['size'], 2)."</td></tr>";
		}
		if ($_POST['core'] == 'all') {
			$text .= "<tr><td class='f'><img src='".e_IMAGE."fileinspector/file.png' class='i' alt='' />&nbsp;".FR_LAN_6.":&nbsp;".($this -> count['core']['num'] + $this -> count['unknown']['num'] + $this -> count['deprecated']['num'])."&nbsp;</td><td class='s'>".$this -> parsesize($this -> count['core']['size'] + $this -> count['unknown']['size'] + $this -> count['deprecated']['size'], 2)."</td></tr>";
		}
		
		if ($this -> count['warning']['num'])
		{
			$text .= "<tr><td colspan='2'>&nbsp;</td></tr>";
			$text .= "<tr><td style='padding-left: 4px' colspan='2'>
			<img src='".e_IMAGE."fileinspector/warning.png' class='i' alt='' />&nbsp;<b>".FR_LAN_26."</b></td></tr>";
		
			$text .= "<tr><td class='f'><img src='".e_IMAGE."fileinspector/file_warning.png' class='i' alt='' />&nbsp;".FR_LAN_28.":&nbsp;".($this -> count['warning']['num'] ? $this -> count['warning']['num'] : FR_LAN_21)."&nbsp;</td><td class='s'>".$this -> parsesize($this -> count['warning']['size'], 2)."</td></tr>";
			
			$text .= "<tr><td class='w' colspan='2'><img src='".e_IMAGE."fileinspector/info.png' class='i' alt='' />&nbsp;".FR_LAN_27."</td></tr>";

		}
		if ($_POST['integrity'] && $_POST['core'] != 'none')
		{
			$integrity_icon = $this -> count['fail']['num'] ? 'integrity_fail.png' : 'integrity_pass.png';
			$integrity_text = $this -> count['fail']['num'] ? '( '.$this -> count['fail']['num'].' '.FR_LAN_19.' )' : '( '.FR_LAN_20.' )';
			$text .= "<tr><td colspan='2'>&nbsp;</td></tr>";
			$text .= "<tr><th class='f' colspan='2'>".FR_LAN_7." ".$integrity_text."</th></tr>";
		
			$text .= "<tr><td class='f'><img src='".e_IMAGE."fileinspector/file_check.png' class='i' alt='' />&nbsp;".FR_LAN_8.":&nbsp;".($this -> count['pass']['num'] ? $this -> count['pass']['num'] : FR_LAN_21)."&nbsp;</td><td class='s'>".$this -> parsesize($this -> count['pass']['size'], 2)."</td></tr>";
			$text .= "<tr><td class='f'><img src='".e_IMAGE."fileinspector/file_fail.png' class='i' alt='' />&nbsp;".FR_LAN_9.":&nbsp;".($this -> count['fail']['num'] ? $this -> count['fail']['num'] : FR_LAN_21)."&nbsp;</td><td class='s'>".$this -> parsesize($this -> count['fail']['size'], 2)."</td></tr>";
			$text .= "<tr><td class='f'><img src='".e_IMAGE."fileinspector/file_uncalc.png' class='i' alt='' />&nbsp;".FR_LAN_25.":&nbsp;".($this -> count['uncalculable']['num'] ? $this -> count['uncalculable']['num'] : FR_LAN_21)."&nbsp;</td><td class='s'>".$this -> parsesize($this -> count['uncalculable']['size'], 2)."</td></tr>";
		
			$text .= "<tr><td colspan='2'>&nbsp;</td></tr>";

			$text .= "<tr><td class='f' colspan='2'><img src='".e_IMAGE."fileinspector/info.png' class='i' alt='' />&nbsp;".FR_LAN_10.":&nbsp;</td></tr>";

			$text .= "<tr><td style='padding-right: 4px' colspan='2'>
			<ul><li>
			<a href=\"javascript: expandit('i_corrupt')\">".FR_LAN_11."...</a><div style='display: none' id='i_corrupt'>
			".FR_LAN_12."<br /><br /></div>
			</li><li>
			<a href=\"javascript: expandit('i_date')\">".FR_LAN_13."...</a><div style='display: none' id='i_date'>
			".FR_LAN_14."<br /><br /></div>
			</li><li>
			<a href=\"javascript: expandit('i_edit')\">".FR_LAN_15."...</a><div style='display: none' id='i_edit'>
			".FR_LAN_16."<br /><br /></div>
			</li><li>
			<a href=\"javascript: expandit('i_cvs')\">".FR_LAN_17."...</a><div style='display: none' id='i_cvs'>
			".FR_LAN_18."<br /><br /></div>
			</li></ul>
			</td></tr>";
		}
		
		if ($_POST['type'] == 'tree' && !$this -> results && $_POST['regex'])
		{
			$text .= "</td></tr>
			<tr><td style='padding-right: 4px; text-align: center' colspan='2'><br />".FR_LAN_23."</td></tr>";
		}

		$text .= "</table>";
		
		if ($_POST['type'] != 'tree')
		{
			$text .= "<br /></td></tr><tr>
			<td colspan='2'>
			<table class='t table table-striped'>";
			if (!$this -> results && $_POST['regex']) {
				$text .= "<tr><td class='f' style='padding-left: 4px; text-align: center' colspan='2'>".FR_LAN_23."</td></tr>";
			}
		}

		foreach ($this -> files as $dir_id => $fid) 
		{
		
			// $this->sendProgress($cnt,$this->totalFiles,$path);
		
			ksort($fid);
			$text .= ($_POST['type'] == 'tree') ? "<table class='t' style='display: none' id='f_".$dir_id."'>" : "";
			$initial = FALSE;
			foreach ($fid as $key => $stext) {
				if (!$initial) {
					if ($_POST['type'] == 'tree') {
						$text .= "<tr><td class='f' style='padding-left: 4px' ".($stext['level'] ? "onclick=\"sh('f_".$stext['parent']."')\"" : "").">
						<img src='".e_IMAGE."fileinspector/".($stext['level'] ? "folder_up.png" : "folder_root.png")."' class='i' alt='' />".($stext['level'] ? "&nbsp;.." : "")."</td>
						<td class='s' style='text-align: right; padding-right: 4px' onclick=\"sh('initial')\"><img src='".e_IMAGE."fileinspector/close.png' class='i' alt='' /></td></tr>";
					}
				} else {
					if ($_POST['type'] != 'tree') {
						$stext['file'] = str_replace($this -> root_dir."/", "", $stext['file']);
					}
					$text .= "<tr>
					<td class='f'><img src='".e_IMAGE."fileinspector/".$stext['icon']."' class='i' alt='' />&nbsp;".$stext['file']."&nbsp;";
					if ($_POST['regex']) {
						if ($_POST['num'] || $_POST['line']) {
							$text .= "<br />";
						}
						foreach ($stext['lines'] as $rkey => $rvalue) {
							if ($_POST['num']) {
								$text .= "[".($rkey + 1)."] ";
							}
							if ($_POST['line']) {
								$text .= htmlspecialchars($rvalue)."<br />";
							}
						}
						$text .= "<br />";
					} else {
						$text .= "</td>
						<td class='s'>".$this -> parsesize($stext['size']);
					}
					$text .= "</td></tr>";
				}
				$initial = TRUE;
			}
			$text .= ($_POST['type'] == 'tree') ? "</table>" : "";
		}
		
		if ($_POST['type'] != 'tree') {
			$text .= "</td>
			</tr></table>";
		}

		$text .= "</td></tr>";
		
		$text .= "</table>
		</dit><br />";

		echo $text;
		
	 //$ns -> tablerender(FR_LAN_1.'...', $text);
		
	}
	
	function create_image($dir) {
		global $core_image, $deprecated_image,$coredir;
		
		foreach ($coredir as $trim_key => $trim_dirs) {
			$search[$trim_key] = "'".$trim_dirs."'";
			$replace[$trim_key] = "\$coredir['".$trim_key."']";
		}
		
		$data = "<?php\n";
		$data .= "/*\n";
		$data .= "+ ----------------------------------------------------------------------------+\n";
		$data .= "|     e107 website system\n";
		$data .= "|\n";
		$data .= "|     Copyright (C) 2001-2002 Steve Dunstan (jalist@e107.org)\n";
		$data .= "|     Copyright (C) 2008-2010 e107 Inc (e107.org)\n";
		$data .= "|\n";
		$data .= "|     Released under the terms and conditions of the\n";
		$data .= "|     GNU General Public License (http://gnu.org).\n";
		$data .= "|\n";
		$data .= "|     \$Source: /cvs_backup/e107_0.7/e107_admin/fileinspector.php,v $\n";
		$data .= "|     \$Revision$\n";
		$data .= "|     \$Id$\n";
		$data .= "|     \$Author$\n";
		$data .= "+----------------------------------------------------------------------------+\n";
		$data .= "*/\n\n";
		$data .= "if (!defined('e107_INIT')) { exit; }\n\n";
		
		$scan_current = ($_POST['snaptype'] == 'current') ? $this -> scan($dir) : $core_image;
		$image_array = var_export($scan_current, true);
		$image_array = str_replace($search, $replace, $image_array);
		$data .= "\$core_image = ".$image_array.";\n\n";
		
		$scan_deprecated = ($_POST['snaptype'] == 'deprecated') ? $this -> scan($dir, $core_image) : $deprecated_image;
		$image_array = var_export($scan_deprecated, true);
		$image_array = str_replace($search, $replace, $image_array);
		$data .= "\$deprecated_image = ".$image_array.";\n\n";

		$data .= "?>";
		$fp = fopen(e_ADMIN.'core_image.php', 'w');
		fwrite($fp, $data);
	}
	
	function snapshot_interface() 
	{
		$ns = e107::getRender();
		$frm = e107::getRender();
		$text = "";
		
		if (isset($_POST['create_snapshot'])) 
		{
			$this -> create_image($_POST['snapshot_path']);
			$text = "<div style='text-align:center'>
			<form action='".e_SELF."' method='post' id='main_page'>
			<table class='table adminform'>
			<tr>
			<td class='fcaption'>Snapshot Created</td>
			</tr>";
		
			$text .= "<tr>
			<td style='text-align:center'>
			The snapshot (".e_ADMIN."core_image.php) was successfully created.
			</td>
			</tr>
			<tr>
			<td style='text-align:center' class='forumheader'>".$frm->admin_button('main_page', 'Return To Main Page', 'submit')."</td>
			</tr>
			</table>
			</form>
			</div><br />";
		}
		
		$text .= "<div style='text-align:center'>
		<form action='".e_SELF."?".e_QUERY."' method='post' id='snapshot'>
		<table class='table adminform'>
		<tr>
		<td ccolspan='2'>Create Snapshot</td>
		</tr>";
		
		$text .= "<tr>
		<td style='width:50%'>
		Absolute path of root directory to create image from:
		</td>
		<td style='width:50%'>
		<input class='tbox' type='text' name='snapshot_path' size='60' value='".(isset($_POST['snapshot_path']) ? $_POST['snapshot_path'] : $this -> root_dir)."' />
		</td></tr>
		
		<tr>
		<td style='width: 35%'>
		Create snapshot of current or deprecated core files:
		</td>
		<td colspan='2' style='width: 65%'>
		<input type='radio' name='snaptype' value='current'".($_POST['snaptype'] == 'current' || !isset($_POST['snaptype']) ? " checked='checked'" : "")." /> Current&nbsp;&nbsp;
		<input type='radio' name='snaptype' value='deprecated'".($_POST['snaptype'] == 'deprecated' ? " checked='checked'" : "")." /> Deprecated&nbsp;&nbsp;
		</td>
		</tr>
		
		<tr>
		<td class='forumheader' style='text-align:center' colspan='2'>".$frm->admin_button('create_snapshot', 'Create Snapshot', 'create')."</td>
		</tr>
		</table>
		</form>
		</div>";

		$ns -> tablerender('Snapshot', $text);

	}
	
	function checksum($filename) 
	{
		$checksum = md5(str_replace(array(chr(13),chr(10)), "", file_get_contents($filename)));
		return $checksum;
	}
	
	function parsesize($size, $dec = 0) {
		$size = $size ? $size : 0;
		$kb = 1024;
		$mb = 1024 * $kb;
		$gb = 1024 * $mb;
		$tb = 1024 * $gb;
		if ($size < $kb) {
			return $size." b";
		} else if($size < $mb) {
			return round($size/$kb)." kb";
		} else if($size < $gb) {
			return round($size/$mb, $dec)." mb";
		} else if($size < $tb) {
			return round($size/$gb, $dec)." gb";
		} else {
			return round($size/$tb, $dec)." tb";
		}
	}
	
	function regex_match($file) {
		$file_content = file_get_contents($file);
		$match = preg_match($_POST['regex'], $file_content);
		
		return $match;
	}
	
	
	function sendProgress($rand,$total,$diz)
	{
		if($this->progress_units <40 && ($rand != $total))
		{
			$this->progress_units++;
			return;
		}
		else
		{
			$this->progress_units = 0;		
		}
		
		$inc = round(($rand / $total) * 100);
		
		if($inc == 0)
		{
			return;
		}
		
		
		
		
		echo "<div class='{$disp}' style='display:block;position:absolute;top:20px;width:100%;'>
		<div style='width:700px;position:relative;margin-left:auto;margin-right:auto;text-align:center'>";
		
		$active = "active";
		
		if($inc >= 100)
		{
			$inc = 100;
			$active = "";
		}

		
		
		echo '<div class="progress progress-striped '.$active.'">
    			<div class="bar" style="width: '.$inc.'%"></div>
   		 </div>';
		
	//	exit;
		/*	
	    echo "<div style='margin-left:auto;margin-right:auto;border:2px inset black;height:20px;width:700px;overflow:hidden;text-align:left'>    
		<img src='".THEME."images/bar.jpg' style='width:".$inc."%;height:20px;vertical-align:top' />
		</div>";
		*/
		
		
		echo "<div style='width:100%;background-color:#EEEEEE'>".$diz."</div>";
		
		
		if($total > 0)
		{
			echo "<div style='width:100%;background-color:#EEEEEE;text-align:center'>".$inc ."%</div>";	
		}
		
		echo "</div>
		</div>";
		
	}
	
	
	function exploit_interface()
	{
	//	global $ns;
		$ns = e107::getRender();
		
		$query = http_build_query($_POST);
		
		$text = "<iframe src='".e_SELF."?$query' width='96%' style='margin-left:0px;width: 96%; height: 100%; min-height: 800px; max-height:1100px; border: 0px' frameborder='0' scrolling='auto' ></iframe>";
		 $ns -> tablerender(FR_LAN_1, $text);
	}
		
	
	function headerCss()
	{
		$pref = e107::getPref();
				
		echo "<!-- *CSS* -->\n";
		$e_js =  e107::getJs();
		
		// Core CSS - XXX awaiting for path changes
		if (!isset($no_core_css) || !$no_core_css)
		{
			//echo "<link rel='stylesheet' href='".e_FILE_ABS."e107.css' type='text/css' />\n";
			$e_js->otherCSS('{e_WEB_CSS}e107.css');
		}
				
					
				
		if (!defsettrue('e_IFRAME') && isset($pref['admincss']) && $pref['admincss'])
		{
			$css_file = file_exists(THEME.'admin_'.$pref['admincss']) ? 'admin_'.$pref['admincss'] : $pref['admincss'];
			//echo "<link rel='stylesheet' href='".$css_file."' type='text/css' />\n";
			$e_js->themeCSS($css_file);
		}
		elseif (isset($pref['themecss']) && $pref['themecss'])
		{
			$css_file = file_exists(THEME.'admin_'.$pref['themecss']) ? 'admin_'.$pref['themecss'] : $pref['themecss'];
			//echo "<link rel='stylesheet' href='".$css_file."' type='text/css' />\n";
			$e_js->themeCSS($css_file);
		}
		else
		{
			$css_file = file_exists(THEME.'admin_style.css') ? 'admin_style.css' : 'style.css';
			//echo "<link rel='stylesheet' href='".$css_file."' type='text/css' />\n";
			$e_js->themeCSS($css_file);
		}
		
						
		$e_js->renderJs('other_css', false, 'css', false);
		echo "\n<!-- footer_other_css -->\n";
		
		// Core CSS
		$e_js->renderJs('core_css', false, 'css', false);
		echo "\n<!-- footer_core_css -->\n";
		
		// Plugin CSS
		$e_js->renderJs('plugin_css', false, 'css', false);
		echo "\n<!-- footer_plugin_css -->\n";
		
		// Theme CSS
		//echo "<!-- Theme css -->\n";
		$e_js->renderJs('theme_css', false, 'css', false);
		echo "\n<!-- footer_theme_css -->\n";
		
		// Inline CSS - not sure if this should stay at all!
		$e_js->renderJs('inline_css', false, 'css', false);
		echo "\n<!-- footer_inline_css -->\n";			
				
			
		/*
		echo "<!-- Theme css -->\n";
		if (strpos(e_SELF.'?'.e_QUERY, 'menus.php?configure') === FALSE && isset($pref['admincss']) && $pref['admincss'] && file_exists(THEME.$pref['admincss'])) {
			$css_file = file_exists(THEME.'admin_'.$pref['admincss']) ? THEME_ABS.'admin_'.$pref['admincss'] : THEME_ABS.$pref['admincss'];
			echo "<link rel='stylesheet' href='".$css_file."' type='text/css' />\n";
		} else if (isset($pref['themecss']) && $pref['themecss'] && file_exists(THEME.$pref['themecss']))
		{
			$css_file = file_exists(THEME.'admin_'.$pref['themecss']) ? THEME_ABS.'admin_'.$pref['themecss'] : THEME_ABS.$pref['themecss'];
			echo "<link rel='stylesheet' href='".$css_file."' type='text/css' />\n";
		
		
		} 
		else 
		{
			$css_file = file_exists(THEME.'admin_style.css') ? THEME_ABS.'admin_style.css' : THEME_ABS.'style.css';
			echo "<link rel='stylesheet' href='".$css_file."' type='text/css' />\n";
		}
		if (!isset($no_core_css) || !$no_core_css) {
			echo "<link rel='stylesheet' href='".e_WEB_CSS."e107.css' type='text/css' />\n";
		}
		 * */
		 
	}
	
	
	
	
}

function fileinspector_adminmenu() //FIXME - has problems when navigation is on the LEFT instead of the right. 
{
	$var['setup']['text'] = "Setup";
	$var['setup']['link'] = e_SELF."?mode=setup";
	
	$var['run']['text'] = "Results";
	$var['run']['link'] = e_SELF."?mode=run";

	e107::getNav()->admin(FC_LAN_1, $_GET['mode'], $var);
}

require_once(e_ADMIN.'footer.php');

function headerjs() {
global $e107;
$text = "<script type='text/javascript'>
<!--
c = new Image(); c = '".SITEURLBASE.e_IMAGE_ABS."fileinspector/contract.png';
e = '".SITEURLBASE.e_IMAGE_ABS."fileinspector/expand.png';
function ec(ecid) {
	icon = document.getElementById('e_' + ecid).src;
	if (icon == e) {
		document.getElementById('e_' + ecid).src = c;
	} else {
		document.getElementById('e_' + ecid).src = e;
	}

	div = document.getElementById('d_' + ecid).style;
	if (div.display == 'none') {
		div.display = '';
	} else {
		div.display = 'none';
	}
}

var hideid = 'initial';
function sh(showid) {
	if (hideid != showid) {
		show = document.getElementById(showid).style;
		hide = document.getElementById(hideid).style;
		show.display = '';
		hide.display = 'none';
		hideid = showid;
	}
}
//-->
</script>
<style type='text/css'>
<!--\n";
if (vartrue($_POST['regex'])) {
	$text .= ".f { padding: 1px 0px 1px 8px; vertical-align: bottom; width: 90% }\n";
} else {
	$text .= ".f { padding: 1px 0px 1px 8px; vertical-align: bottom; width: 90%; white-space: nowrap }\n";
}
$text .= ".d { margin: 2px 0px 1px 8px; cursor: default; white-space: nowrap }
.s { padding: 1px 8px 1px 0px; vertical-align: bottom; width: 10%; white-space: nowrap }
.t { margin-top: 1px; width: 100%; border-collapse: collapse; border-spacing: 0px }
.w { padding: 1px 0px 1px 8px; vertical-align: bottom; width: 90% }
.i { width: 16px; height: 16px }
.e { width: 9px; height: 9px }
-->
</style>\n";
		
return $text;
}

?>