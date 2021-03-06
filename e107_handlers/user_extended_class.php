<?php
/*
* e107 website system
*
* Copyright (C) 2008-2012 e107 Inc (e107.org)
* Released under the terms and conditions of the
* GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
*
* Extended user field handler
*
* $URL$
* $Id$
*
*/

if (!defined('e107_INIT')) { exit; }

/**
 *	@package     e107
 *	@subpackage	e107_handlers
 *	@version 	$Id$;
 *
 *	Extended user field handler
 *
 *	@todo: - change some routines to access the cached variables rather than DB
 *	@todo: Remove setting up of _FIELD_TYPES array (may be necessary, since UEF data structure not fixed)
 *	@todo: Consider changing field type constants to class constants
 *	@todo - cache field structure (already done in a different way in e107::user() in class2.php line 1387 or so)
 *	@todo - class variables - confirm whether public/protected assignments are correct
 *	@todo - consider whether to split system and non-system fields

Code uses two tables:
	user_extended_struct - individual field definitions, one record per field
	user_extended - actual field data, one record per user

@todo: Should user_extended_validate_entry() check DB for DB-type fields?

*/

e107::coreLan('user_extended');

class e107_user_extended
{
	public $user_extended_types;	// Text description corresponding to each field type
	private $extended_xml = FALSE;
	public $typeArray;				// Cross-reference between names of field types, and numeric ID (must be public)
	private $reserved_names;		// List of field names used in main user DB - not allowed in extended DB
	public $fieldDefinitions;		// Array initialised from DB by constructor - currently all fields
	public $catDefinitions;			// Categories
	private $nameIndex;				// Array for field name lookup - initialised by constructor
	public $systemCount = 0;		// Count of system fields - always zero ATM
	public $userCount = 0;			// Count of non-system fields

	public function __construct()
	{
		define('EUF_CATEGORY', 0);
		define('EUF_TEXT',1);
		define('EUF_RADIO',2);
		define('EUF_DROPDOWN',3);
		define('EUF_DB_FIELD',4);
		define('EUF_TEXTAREA',5);
		define('EUF_INTEGER',6);
		define('EUF_DATE',7);
		define('EUF_LANGUAGE',8);
		define('EUF_PREDEFINED',9); // should be EUF_LIST IMO
		define('EUF_CHECKBOX',10);
		define('EUF_PREFIELD',11); // should be EUF_PREDEFINED, useful when creating fields from e.g. plugin XML

		$this->typeArray = array(
			'text' => 1,
			'radio' => 2,
			'dropdown' => 3,
			'db field' => 4,
			'textarea' => 5,
			'integer' => 6,
			'date' => 7,
			'language' => 8,
			'list' => 9,
			'checkbox'	=> 10,
			'predefined' => 11, // DON'T USE IT IN PREDEFINED FIELD XML!!! Used in plugin installation routine.
		);

		$this->user_extended_types = array(
			1 => UE_LAN_1,
			2 => UE_LAN_2,
			3 => UE_LAN_3,
			4 => UE_LAN_4,
			5 => UE_LAN_5,
			6 => UE_LAN_6,
			7 => UE_LAN_7,
			8 => UE_LAN_8,
			9 => UE_LAN_9,
			10=> UE_LAN_10
		);

		//load array with field names from main user table, so we can disallow these
		// user_new, user_timezone deleted for 0.8
		$this->reserved_names = array (
		'id', 'name', 'loginname', 'customtitle', 'password',
		'sess', 'email', 'signature', 'image', 'hideemail',
		'join', 'lastvisit', 'currentvisit', 'chats',
		'comments', 'forums', 'ip', 'ban', 'prefs', 'viewed',
		'visits', 'admin', 'login', 'class', 'baseclasslist', 'perms', 'pwchange',
		'xup'
		);

		$sql = e107::getDB();

		// Read in all the field and category fields
		// At present we load all fields into common array - may want to split system and non-system
		$this ->catDefinitions = array();		// Categories array
		$this->fieldDefinitions = array();		// Field definitions array
		$this->nameIndex = array();				// Index of names => field IDs
		$this->systemCount = 0;
		$this->userCount = 0;

		if($sql->select('user_extended_struct', '*', "user_extended_struct_text != '_system_' ORDER BY user_extended_struct_order ASC"))
		{
			while($row = $sql->db_Fetch(MYSQL_ASSOC))
			{
				if ($row['user_extended_struct_type'] == 0)
				{	// Its a category
					$this->catDefinitions[$row['user_extended_struct_id']] = $row;
				}
				else
				{	// Its a field definition
					$this->fieldDefinitions[$row['user_extended_struct_id']] = $row;
					$this->nameIndex['user_'.$row['user_extended_struct_name']] = $row['user_extended_struct_id'];			// Create name to ID index
					if ($row['user_extended_struct_text'] == '_system_')
					{
						$this->systemCount++;
					}
					else
					{
						$this->userCount++;
					}
				}
			}
		}
	}



	/**
	 *	Check for reserved field names.
	 *	(Names which clash with the 'normal' user table aren't allowed)
	 *
	 *	@param string $name - name of field bweing checked (no 'user_' prefix)
	 *
	 *	@return boolean TRUE if disallowed name
	 */
	public function user_extended_reserved($name)
	{
		return (in_array($name, $this->reserved_names));
	}



	// Adds the _FIELD_TYPES array to the data, ready for saving in the DB.
	function addFieldTypes(&$target)
	{
		$target['_FIELD_TYPES'] = array();		// We should always want to recreate the array, even if it exists
		foreach ($target['data'] as $k => $v)
		{
			if (isset($this->nameIndex[$k]))
			{
				switch ($this->fieldDefinitions[$this->nameIndex[$k]]['user_extended_struct_type'])
				{
					case EUF_TEXT :
					case EUF_DB_FIELD :
					case EUF_TEXTAREA :
					case EUF_DROPDOWN :
					case EUF_DATE :
					case EUF_LANGUAGE :
					case EUF_PREDEFINED :
					case EUF_CHECKBOX :
					case EUF_RADIO :
						$target['_FIELD_TYPES'][$k] = 'todb';
						break;
					
					case EUF_INTEGER :
						$target['_FIELD_TYPES'][$k] = 'int';
						break;
				}
			}
		}
	}



	/**
	 * For all UEFs not in the target array, adds the default value
	 * Also updates the _FIELD_TYPES array, so call this last thing before writing to the DB
	 *
	 *	@param $target - pointer to data array
	 */
	public function addDefaultFields(&$target)
	{
		//$target['_FIELD_TYPES'] = array();		// We should always want to recreate the array, even if it exists
		foreach ($this->fieldDefinitions as $k => $defs)
		{
			$f = 'user_'.$defs['user_extended_struct_name'];
			if (!isset($target['data'][$f]) && $this->fieldDefinitions[$k]['user_extended_struct_default'])
			{
				switch ($this->fieldDefinitions[$k]['user_extended_struct_type'])
				{
					case EUF_TEXT :
					case EUF_DB_FIELD :
					case EUF_TEXTAREA :
					case EUF_DROPDOWN :
					case EUF_DATE :
					case EUF_LANGUAGE :
					case EUF_PREDEFINED :

						$target['data'][$f] = $this->fieldDefinitions[$k]['user_extended_struct_default'];
						$target['_FIELD_TYPES'][$f] = 'todb';
						break;
					case EUF_RADIO :
					case EUF_INTEGER :
						$target['data'][$f] = $this->fieldDefinitions[$k]['user_extended_struct_default'];
						$target['_FIELD_TYPES'][$f] = 'int';
						break;
					case EUF_CHECKBOX :
                    	$target['data'][$f] = $this->fieldDefinitions[$k]['user_extended_struct_default'];
						$target['_FIELD_TYPES'][$f] = 'array';
						break;
				}
			}
		}
	}



	// Validate a single extended user field
	// $val is whatever the user entered.
	// $params is the field definition
	// Return FALSE if acceptable, TRUE if fail , error message on regex fail if the message is defined
	function user_extended_validate_entry($val, $params)
	{
		$tp = e107::getParser();
		
		$parms = explode('^,^', $params['user_extended_struct_parms']);
		$requiredField = $params['user_extended_struct_required'] == 1;
		$regex = $tp->toText($parms[1]);
		$regexfail = $tp->toText($parms[2]);
		if (defined($regexfail)) { $regexfail = constant($regexfail); }
		if($val == '' && $requiredField) return TRUE;
		switch ($type)
		{
			case EUF_DATE :
				if ($requiredField && ($val == '0000-00-00')) return TRUE;
				break;
		}
		if($regex != "" && $val != "")
		{
			if(!preg_match($regex, $val)) return $regexfail ? $regexfail : TRUE;
		}
		return FALSE;			// Pass by default here
	}



	/**
	 * Validate all user-modifable extended user fields which are presented.
	 *	Primarily intended to validate data entered by a user or admin
	 *
	 * @param array $inArray is the input data (usually from $_POST or $_POST['ue'], although doesn't have to be) - may have 'surplus' values
	 * @param array $hideArray is a set of possible 'hide' flags
	 * @param boolean $isSignup TRUE causes required fields to be specifically checked, else only data passed is checked
	 *
	 *	@return array with three potential subkeys:
	 *		'data' - valid data values (key is field name)
	 *			['data']['user_hidden_fields'] is the hidden fields
	 *		'errors' - data values in error
	 *		'errortext' - error message corresponding to erroneous value
	 *
	 *	@todo - does $hidden_fields need to be merged with values for fields not processed? (Probably not - should only relate to fields current user can see)
	 *	@todo - make sure admin can edit fields of other users
	 */
	public function userExtendedValidateAll($inArray, $hideArray, $isSignup=FALSE)
	{
		$tp = e107::getParser();
		
		$eufVals = array();		// 'Answer' array
		$hideFlags = array();
		
		foreach ($this->fieldDefinitions as $k => $defs)
		{
			$category = $defs['user_extended_struct_parent'];
			if (($category == 0) || ($isSignup && (int) $this->catDefinitions[$category]['user_extended_struct_applicable'] === (int) e_UC_MEMBER && (int) $this->catDefinitions[$category]['user_extended_struct_write'] === (int) e_UC_MEMBER) || (check_class($this->catDefinitions[$category]['user_extended_struct_applicable']) && check_class($this->catDefinitions[$category]['user_extended_struct_write'])))
			{	// Category applicable to user
				
				if (($isSignup && (int) $defs['user_extended_struct_applicable'] === (int) e_UC_MEMBER && (int) $defs['user_extended_struct_write'] === (int) e_UC_MEMBER) || (check_class($defs['user_extended_struct_applicable']) && check_class($defs['user_extended_struct_write'])))
				{	// User can also update field
					$f = 'user_'.$defs['user_extended_struct_name'];
					if (isset($inArray[$f]) || ($isSignup && ($defs['user_extended_struct_required'] == 1)))
					{	// Only allow valid keys
						$val = varset($inArray[$f], FALSE);
						$err = $this->user_extended_validate_entry($val, $defs); 
						if ($err === true)
						{  // General error - usually empty field; could be unacceptable value, or regex fail and no error message defined
							$eufVals['errortext'][$f] = str_replace('--SOMETHING--',$tp->toHtml(defset($defs['user_extended_struct_text'], $defs['user_extended_struct_text']),FALSE,'defs'),LAN_USER_75);
							$eufVals['errors'][$f] = ERR_GENERIC;
						}
						elseif ($err)
						{	// Specific error message returned - usually regex fail
							$eufVals['errortext'][$f] = $err;
							$eufVals['errors'][$f] = ERR_GENERIC;
						}
						elseif (!$err)
						{
							$eufVals['data'][$f] = $tp->toDB($val);
						}
						if (isset($hideArray[$f]))
						{
							$hideFlags[] = $f;
						}
					}
				}
			}
		}
		$hidden_fields = implode('^', $hideFlags);
		if ($hidden_fields != '')
		{
			$hidden_fields = '^'.$hidden_fields.'^';
		}
		$eufVals['data']['user_hidden_fields'] = $hidden_fields;

		return $eufVals;
	}

	/**
	 * alias of user_extended_get_categories();
	 * 
	 */
	function getCategories($byID = TRUE)
	{
		return $this->user_extended_get_categories($byID);	
	}


	function user_extended_get_categories($byID = TRUE)
	{
	   	$ret = array();
		$sql = e107::getDb('ue');
		
		if($sql->select("user_extended_struct", "*", "user_extended_struct_type = 0 ORDER BY user_extended_struct_order ASC"))
		{
			if($byID == TRUE)
			{
				while($row = $sql->fetch())
				{
					$ret[$row['user_extended_struct_id']][] = $row;
				}
			}
			else
			{
				$ret = $sql->db_getList();
			}
		}
		return $ret;
	}

	/**
	 * BC Alias of getFields();
	 */
	function getFields($cat = "")
	{
		return $this->user_extended_get_fieldList($cat);	
	}


	// Get the definition of all fields, or those in a specific category, grouped by category ID
	// Reads non-system fields only
	function user_extended_get_fields($cat = "")
	{
		$sql = e107::getDb('ue');
		$ret = array();
		$more = ($cat) ? " AND user_extended_struct_parent = ".intval($cat)." " : "";
		if($sql->select("user_extended_struct", "*", "user_extended_struct_type > 0 AND user_extended_struct_text != '_system_' {$more} ORDER BY user_extended_struct_order ASC"))
		{
			while($row = $sql->fetch(MYSQL_ASSOC))
			{
				$ret[$row['user_extended_struct_parent']][] = $row;
			}
		}
		return $ret;
	}

	/**
	 * Alias of getFieldList(). 
	 * 
	 */
	function getFieldList($cat = "", $indexField = 'user_extended_struct_id')
	{
		return $this->user_extended_get_fieldList($cat, $indexField); 	
	}
		

	/**
	 * Get the definition of all fields, or those in a specific category, indexed by field ID (or some other field by specifying $indexField)
	 * @param $cat
	 * @param $indexField;
	 * @param $system - include system fields. 
	 */
	function user_extended_get_fieldList($cat = "", $indexField = 'user_extended_struct_id', $system = false)
	{
		if(!$indexField)
		{
			 $indexField = 'user_extended_struct_id';	
		}
		
		$sql = e107::getDb('ue');
		
		$more = ($cat != '') ? " AND user_extended_struct_parent = ".intval($cat)." " : "";
		$sys = ($system == false) ? " AND user_extended_struct_text != '_system_' " : "";
		
		if($sql->select("user_extended_struct", "*", "user_extended_struct_type > 0 {$sys} {$more} ORDER BY user_extended_struct_order ASC"))
		{
			while($row = $sql->fetch(MYSQL_ASSOC))
			{
				$ret[$row[$indexField]] = $row;
			}
		}
		return $ret;
	}


	/**
	 * Return the list of user_extended fields. 
	 * @return array
	 */
	function getFieldNames()
	{
		$ret = array();
		
		$sql = e107::getDb('ue');
		
		if($sql->select("user_extended_struct", "*", "user_extended_struct_type > 0 ORDER BY user_extended_struct_order ASC"))
		{
			while($row = $sql->fetch(MYSQL_ASSOC))
			{
				$ret[] = 'user_'.$row['user_extended_struct_name'];
			}
		}
		return $ret;
		
	}
			
		



	// Return the field creation text for a definition
	function user_extended_type_text($type, $default)
	{
	  $tp = e107::getParser();
	  
	  if(!is_numeric($type))
	  {
	  	return false;
	  }
	  
	  switch ($type)
	  {
		case EUF_INTEGER :
		  $db_type = 'INT(11)';
		  break;

		case EUF_DATE :
		  $db_type = 'DATE NOT NULL';
		  break;

		case EUF_TEXTAREA:
		  $db_type = 'TEXT';
		 break;

		case EUF_TEXT :
		case EUF_RADIO :
		case EUF_DROPDOWN :
		case EUF_DB_FIELD :
		case EUF_LANGUAGE :
		case EUF_PREDEFINED :
		case EUF_CHECKBOX :
		  $db_type = 'VARCHAR(255)';
		 break;
		 
		case EUF_CATEGORY:
			return '';
		 break;
		 
		default:
			e107::getMessage()->addDebug("<strong>Unknown type '{$type}' for user extended field.</strong>"); 
			return false;
		break;

	  }
	  if($type != EUF_DB_FIELD && $type != EUF_TEXTAREA && $default != '')
	  {
		$default_text = " DEFAULT '".$tp -> toDB($default, true)."'";
	  }
	  else
	  {
		$default_text = '';
	  }
	  return $db_type.$default_text;
	}


	function user_extended_field_exist($name)
	{
	  	$sql = e107::getDb('sql2');
		$tp = e107::getParser();
		return $sql->count('user_extended_struct','(*)', "WHERE user_extended_struct_name = '".$tp -> toDB($name, true)."'");
	}

	function clear_cache()
	{
		$e107 = e107::getInstance();
		$e107->ecache->clear_sys('nomd5_extended_struct');
	}

	// For use by plugins to add extended user fields and won't be visible anywhere else
	function user_extended_add_system($name, $type, $default = '', $source = '_system_')
	{
	  return $this->user_extended_add($name, '_system_', $type, $source, '', $default, 0, 255, 255, 255, 0, 0);
	}

	function user_extended_add($name, $text, $type, $parms, $values, $default, $required, $read, $write, $applicable, $order='', $parent)
	{
		
		$sql = e107::getDb('ue');
		$tp = e107::getParser();
		
		$this->clear_cache();
		
		if(is_array($name))
	  	{
			extract($name);
		}
		
	 	 if(!is_numeric($type))
	  	{
			$type = $this->typeArray[$type];
	  	}

		if($this->user_extended_field_exist($name))
		{
			return TRUE;	
		}

		if (!$this->user_extended_reserved($name))
		{
			$field_info = $this->user_extended_type_text($type, $default);
		
			// wrong type
			if(false === $field_info) return false;
		
			if($order === '' && $field_info)
			{
			  if($sql->select('user_extended_struct','MAX(user_extended_struct_order) as maxorder','1'))
			  {
				$row = $sql->fetch();
				if(is_numeric($row['maxorder']))
				{
				  $order = $row['maxorder']+1;
				}
			  }
			}
			// field of type category
			if($field_info)
			{
				$sql->gen('ALTER TABLE #user_extended ADD user_'.$tp -> toDB($name, true).' '.$field_info);
			}
			
			$sql->insert('user_extended_struct',"null,'".$tp -> toDB($name, true)."','".$tp -> toDB($text, true)."','".intval($type)."','".$tp -> toDB($parms, true)."','".$tp -> toDB($values, true)."', '".$tp -> toDB($default, true)."', '".intval($read)."', '".intval($write)."', '".intval($required)."', '0', '".intval($applicable)."', '".intval($order)."', '".intval($parent)."'");
			
			if ($this->user_extended_field_exist($name))
			{
			  return TRUE;
			}
		}

		return FALSE;
	}



	function user_extended_modify($id, $name, $text, $type, $parms, $values, $default, $required, $read, $write, $applicable, $parent)
	{
		$sql = e107::getDb('ue');
		$tp = e107::getParser();
		
		if ($this->user_extended_field_exist($name))
		{
			$field_info = $this->user_extended_type_text($type, $default);
			// wrong type
			if(false === $field_info) return false;
			
			// field of type category
			if($field_info)
			{
				$sql->gen("ALTER TABLE #user_extended MODIFY user_".$tp -> toDB($name, true)." ".$field_info);
			}
			
			$newfield_info = "
				user_extended_struct_text = '".$tp -> toDB($text, true)."',
				user_extended_struct_type = '".intval($type)."',
				user_extended_struct_parms = '".$tp -> toDB($parms, true)."',
				user_extended_struct_values = '".$tp -> toDB($values, true)."',
				user_extended_struct_default = '".$tp -> toDB($default, true)."',
				user_extended_struct_required = '".intval($required)."',
				user_extended_struct_read = '".intval($read)."',
				user_extended_struct_write = '".intval($write)."',
				user_extended_struct_applicable = '".intval($applicable)."',
				user_extended_struct_parent = '".intval($parent)."'
				WHERE user_extended_struct_id = '".intval($id)."'
			";
			return $sql->update("user_extended_struct", $newfield_info);
		}
	}

	function user_extended_remove($id, $name)
	{
		$sql = e107::getDb('ue');
		$tp = e107::getParser();
		
		$this->clear_cache(); 
		if ($this->user_extended_field_exist($name))
		{
			// FIXME - no table structure changes for categories
			// but no good way to detect it right now - ignore the sql error for now, fix it asap
			$sql->gen("ALTER TABLE #user_extended DROP user_".$tp -> toDB($name, true));
			
			if(is_numeric($id))
			{
				$sql->db_Delete("user_extended_struct", "user_extended_struct_id = '".intval($id)."' ");
			}
			else
			{
				$sql->db_Delete("user_extended_struct", "user_extended_struct_name = '".$tp -> toDB($id, true)."' ");
			}
			return !($this->user_extended_field_exist($name));
		}
	}

	function user_extended_hide($struct, $curval)
	{
		$chk = ($curval) ? " checked='checked' " : "";
		$name = "hide[user_".$struct['user_extended_struct_name']."]";
		return "<input type='checkbox' {$chk} value='1' name='{$name}' />&nbsp;".UE_LAN_HIDE;
	}



	function user_extended_edit($struct, $curval)
	{
		$tp = e107::getParser();
		$frm = e107::getForm();
		
		if(trim($curval) == "" && $struct['user_extended_struct_default'] != "")
		{
			$curval = $struct['user_extended_struct_default'];
		}
		
		$choices = explode(",",$struct['user_extended_struct_values']);
		
		foreach($choices as $k => $v)
		{
			$choices[$k] = str_replace("[E_COMMA]", ",", $v);
		}
		
		$parms 		= explode("^,^",$struct['user_extended_struct_parms']);
		$include 	= preg_replace("/\n/", " ", $tp->toHtml($parms[0]));
		$regex 		= $tp->toText($parms[1]);
		$regexfail 	= $tp->toText($parms[2]);
		$fname 		= "ue[user_".$struct['user_extended_struct_name']."]";
		$required	= vartrue($struct['user_extended_struct_required']) ? "required"  : "";
		$fid		= $frm->name2id($fname);
		
		if(strpos($include, 'class') === FALSE)
		{
			$include .= " class='form-control tbox' ";
		}


		switch($struct['user_extended_struct_type'])
		{
			case EUF_TEXT :  //textbox
			case EUF_INTEGER :  //integer
		 		$ret = "<input id='{$fid}' type='text' name='{$fname}' value='{$curval}' {$include} {$required} />";
			
		  		return $ret;
		  	break;

			case EUF_RADIO : //radio
			
				$ret = '';
			
				foreach($choices as $choice)
				{
					$choice = trim($choice);
					if(strpos($choice,"|")!==FALSE)
					{
		            	list($val,$label) = explode("|",$choice);
					}
					elseif(strpos($choice," => ")!==FALSE) // new in v2.x
					{
		            	list($val,$label) = explode(" => ",$choice);
					}
					else
					{
		            	$val = $choice;
						$label = $choice;
					}
					
					$label = deftrue($label, $label);
					
					if(deftrue('BOOTSTRAP'))
					{
						$ret .= $frm->radio($fname,$val,($curval == $val),array('label'=>$label, 'required'=> $struct['user_extended_struct_required']));	
					}
					else 
					{
						$chk = ($curval == $val)? " checked='checked' " : "";
						$ret .= "<input id='{$fid}' {$include} type='radio' name='{$fname}' value='{$val}' {$chk} {$required} /> {$label}";	
					}
					
				}
			
				return $ret;
				
		  break;

        case EUF_CHECKBOX : //checkboxes
			foreach($choices as $choice)
			{
				$choice = trim($choice);
				
				if(strpos($choice,"|")!==FALSE)
				{
	            	list($val,$label) = explode("|",$choice);
				}
				elseif(strpos($choice," => ")!==FALSE) // new in v2.x
				{
	            	list($val,$label) = explode(" => ",$choice);
				}
				else
				{
	            	$val = $choice;
					$label = $choice;
				}
				$label = deftrue($label, $label);
				
				if(deftrue('BOOTSTRAP'))
				{
					$ret .= $frm->checkbox($fname,$val,($curval == $val),array('label'=>$label, 'required'=> $struct['user_extended_struct_required']));	
				}
				else 
				{
					$chk = ($curval == $val)? " checked='checked' " : "";
					$ret .= "<input {$include} type='checkbox' name='{$fname}[]' value='{$val}' {$chk} /> {$label}<br />";
				}
			}
			
			return $ret;
		break;

		case EUF_DROPDOWN : //dropdown
		  $ret = "<select {$include} id='{$fid}' name='{$fname}' {$required} >\n";
		  $ret .= "<option value=''>&nbsp;</option>\n";  // ensures that the user chose it.
		  foreach($choices as $choice)
		  {
			$choice = trim($choice);
			$choice = deftrue($choice, $choice);
			$sel = ($curval == $choice) ? " selected='selected' " : "";
			$ret .= "<option value='{$choice}' {$sel}>{$choice}</option>\n";
		  }
		  $ret .= "</select>\n";
		  return $ret;
		  break;

		case EUF_PREDEFINED : // predefined list, shown in dropdown
			$listRoot = trim($struct['user_extended_struct_values']);			// Base list name
			$filename = e_CORE.'sql/extended_'.$listRoot.'.php';
			if (!is_readable($filename)) return 'No file: '.$filename;
			require_once($filename);
			$className = 'extended_'.$listRoot;
			if (!class_exists($className)) return '?????';
			$temp = new $className();
			if (!method_exists($className, 'getValue')) return '???-???';
			$temp->pointerReset();
			
			$ret = "<select id='{$fid}' {$include} name='{$fname}' {$required} >\n";
			$ret .= "<option value=''>&nbsp;</option>\n";  // ensures that the user chooses it.
			while (FALSE !== ($row = $temp->getValue(0, 'next')))
			{
				$val = key($row);
				$choice = $temp->getValue($val, 'display');
				$sel = ($curval == $val) ? " selected='selected' " : '';
				$ret .= "<option value='{$val}' {$sel}>{$choice}</option>\n";
			}
			$ret .= "</select>\n";
			return $ret;

		case EUF_DB_FIELD : //db_field
				
		
				$sql = e107::getDb('ue');
				$order = ($choices[3]) ? "ORDER BY ".$tp -> toDB($choices[3], true) : "";

				if($sql->select($tp -> toDB($choices[0], true), $tp -> toDB($choices[1], true).",".$tp -> toDB($choices[2], true), "1 $order")){
					$choiceList = $sql->db_getList('ALL',FALSE);
					$ret = "<select id='{$fid}' {$include} name='{$fname}' {$required}  >\n";
					$ret .= "<option value=''>&nbsp;</option>\n";  // ensures that the user chose it.
					foreach($choiceList as $cArray)
					{
						$cID = trim($cArray[$choices[1]]);
						$cText = trim($cArray[$choices[2]]);
						$sel = ($curval == $cID) ? " selected='selected' " : "";
						$ret .= "<option value='{$cID}' {$sel}>{$cText}</option>\n";
					}
					$ret .= "</select>\n";
					return $ret;
				} else {
					return "";
				}
				break;

			case EUF_TEXTAREA : //textarea
				return "<textarea id='{$fid}' {$include} name='{$fname}'  {$required} >{$curval}</textarea>";
				break;

			case EUF_DATE : //date
			
				if($curval == '0000-00-00') // Quick datepicker fix. 
				{
					$curval = '';
				}
			
				return e107::getForm()->datepicker($fname,$curval,'format=yyyy-mm-dd');	
				break;

			case EUF_LANGUAGE : // language
				$lanlist = e107::getLanguage()->installed();
				sort($lanlist);
				
            	$ret = "<select {$include} id='{$fid}' name='{$fname}' {$required} >\n";
				$ret .= "<option value=''>&nbsp;</option>\n";  // ensures that the user chose it.
				foreach($lanlist as $choice)
				{
					$choice = trim($choice);
					$sel = ($curval == $choice || (!USER && $choice == e_LANGUAGE))? " selected='selected' " : "";
					$ret .= "<option value='{$choice}' {$sel}>{$choice}</option>\n";
				}
				$ret .= "</select>\n";
				
           	break;

		}

		return $ret;
	}


	// Alias of getStructure();
	function getStructure($orderby="user_extended_struct_order")
	{
		return $this->user_extended_getStruct($orderby);	
	}




	/**
	 * Preferred version of user_extended_getStruct();
	 */
	function user_extended_getStruct($orderby="user_extended_struct_order")
	{
		if($ueStruct = getcachedvars('ue_struct'))
		{
			return $ueStruct;
		}
		
		$tp 	= e107::getParser();
		$sql_ue = e107::getDb('ue'); // new db;		// Use our own db to avoid interference with other objects
		
		$ret = array();
		$parms = "";
		
		if($orderby != "")
		{
			$parms = "1 ORDER BY ".$tp -> toDB($orderby, true);
		}
		
		if($sql_ue->select('user_extended_struct','*',$parms))
		{
			while($row = $sql_ue->fetch())
			{
				$ret['user_'.$row['user_extended_struct_name']] = $row;
			}
		}
		
		cachevars('ue_struct',$ret);
		return $ret;
	}


	function parse_extended_xml($contents, $no_cache = FALSE)
	{
		if($no_cache == FALSE && $this->extended_xml)
		{
			return $this->extended_xml;
		}

		$xml = e107::getXml();
		$data = $xml->loadXMLfile(e_CORE."xml/user_extended.xml", true);
		$ret['version'] = $data['@attributes']['version'];
		unset($info);
		foreach($data['item'] as $item)
		{
			if(is_array($item['include_text']) && !count($item['include_text']))
			{
				$item['include_text'] = '';
			}
			$info = array(
						"name" 			=> $item['@attributes']['name'],
						"text" 			=> "UE_LAN_".strtoupper($item['@attributes']['name']),
						"type" 			=> $item['type'],
						"values" 		=> $item['values'],
						"default" 		=> $item['default'],
						"required" 		=> $item['required'],
						"read" 			=> $item['read'],
						"write"			=> $item['write'],
						"applicable" 	=> $item['applicable'],
						"include_text"	=> $item['include_text'],
						"parms"			=> $item['include_text'],
						"regex" 		=> $item['regex']
					);
			if(is_array($item['default']) && $item['default'] == '')
			{
				$info['default'] = 0;
			}
			if($item['regex'])
			{
				$info['parms'] .= $item['include_text']."^,^".$item['regex']."^,^LAN_UE_FAIL_".strtoupper($item['@attributes']['name']);
			}
			$ret[$item['@attributes']['name']] = $info;
		}
		$this->extended_xml = $ret;
		return $this->extended_xml;
	}

	/**
	 * Proxy Method to retrieve the value of an extended field
	 * @param int $uid
	 * @param var $field_name
	 * @param object $ifnotset [optional]
	 * @return mixed
	 */
	function get($uid, $field_name, $ifnotset=false)
	{
		return user_extended_getvalue($uid, $field_name, $ifnotset);
	}



	/**
	 * Proxy method for setting the value of an extended field
	 * (inserts or updates)
	 *
	 * @param integer $uid
	 * @param string $field_name eg. location
	 * @param string $newvalue eg. USA
	 * @param string $fieldType [optional] default 'todb' |
	 * @return boolean;
	 */
	function set($uid, $field_name, $newvalue, $fieldType = 'todb')
	{
		return $this->user_extended_setvalue($uid, $field_name, $newvalue, $fieldType);
	}


	/**
	* Set the value of an extended field
	*
	*  $ue = new e107_user_extended;
	*	 $result = $ue->user_extended_setvalue(1, 'location', 'Pittsburgh');
	*
	*
	*/
	function user_extended_setvalue($uid, $field_name, $newvalue, $fieldType = 'todb')
	{
		$sql = e107::getDb();
		$tp = e107::getParser();

		$uid = (int)$uid;
		switch($fieldType)
		{
			case 'int':
				$newvalue = (int)$newvalue;
				break;

			case 'escape':
				$newvalue = "'".mysql_real_escape_string($newvalue)."'";
				break;

			default:
				$newvalue = "'".$tp->toDB($newvalue)."'";
				break;
		}
		if(substr($field_name, 0, 5) != 'user_')
		{
			$field_name = 'user_'.$field_name;
		}
		$qry = "
		INSERT INTO `#user_extended` (user_extended_id, {$field_name})
		VALUES ({$uid}, {$newvalue})
		ON DUPLICATE KEY UPDATE {$field_name} = {$newvalue}
		";
		return $sql->gen($qry);
	}


	/**
	* Retrieve the value of an extended field
	*
	*  $ue = new e107_user_extended;
	*	$value = $ue->user_extended_getvalue(2, 'location');
	*
	*/
	function user_extended_getvalue($uid, $field_name, $ifnotset=false)
	{
		$uid = intval($uid);
		if(substr($field_name, 0, 5) != 'user_')
		{
			$field_name = 'user_'.$field_name;
		}
		$uinfo = e107::user($uid);
		if (!isset($uinfo[$field_name])) return $ifnotset;
		return $uinfo[$field_name];
	}


	/**
	 *	Given a predefined list field, returns the display text corresponding to the passed value
	 *
	 *	@TODO: consider whether to cache the class object
	 */
	function user_extended_display_text($table, $value)
	{
		$filename = e_CORE.'sql/extended_'.$table.'.php';
		if (!is_readable($filename)) return 'No file: '.$filename;
		require_once($filename);
		$className = 'extended_'.$table;
		if (!class_exists($className)) return '?????';
		$temp = new $className();
		if (!method_exists($className, 'getValue')) return '???-???';
		return $temp->getValue($value);
	}

}
?>