<?php
/**
 * Current Weather plugin for CMS e107 v2
 *
 * @author OxigenO2 (oxigen.rg@gmail.com)
 * @copyright Copyright (C) 2014 OxigenO2 
 * @license GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 * @link http://oxigen.mablog.eu/
 */
require_once('../../class2.php');
if (!getperms('P')) 
{
	header('location:'.e_BASE.'index.php');
	exit;
}

class current_weather_admin extends e_admin_dispatcher
{
	protected $modes = array(	
		'main'		=> array('controller' => 'plugin_current_weather_admin_ui',
		'path' 		=> null, 
		'ui' 		=> 'plugin_current_weather_admin_form_ui', 'uipath' => null)	
	);	
		
	protected $adminMenu = array(
	
		'main/prefs' 		=> array('caption'=> LAN_PREFS, 'perm' => 'P'),	
    //'main/custom'		=> array('caption'=> 'About', 'perm' => 'P')
	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'				
	);	
	
	protected $menuTitle = 'Current Weather';
}

class plugin_current_weather_admin_ui extends e_admin_ui
{
		protected $pluginTitle = "Current Weather";
		protected $pluginName = 'current_weather';
		protected $tableJoin = array();
    protected $listQry = "";
		protected $pid = "blank_id";
		protected $perPage = 20;
		protected $batchDelete = true;
		protected $displaySettings = array();
		protected $disallowPages = array('main/create', 'main/prefs');

		protected $prefs = array(
			'current_weather_city'	   		=> array('title'=> LAN_WEATHER_ADMIN_ADMIN_CITY, 'type'=>'text', 'data' => 'text', 'validate' => true),
			'current_weather_unit' 				=> array('title'=> LAN_WEATHER_ADMIN_UNIT, 'type' => 'dropdown', 'data' => 'str'),
			'current_weather_scheme' 			=> array('title'=> LAN_WEATHER_ADMIN_COLOR, 'type' => 'method', 'data' => 'str'),
      'current_weather_deatils'			=> array('title'=> LAN_WEATHER_ADMIN_DETAILS, 'type' => 'boolean', 'data' => 'int'),
      'current_weather_link'			  => array('title'=> LAN_WEATHER_ADMIN_LINK, 'type' => 'boolean', 'data' => 'int', 'help' => LAN_WEATHER_ADMIN_LINK_H)			
		);

		public function init()
		{
    	$effects = array(
			'metric'	=> 'Celsium °C',
			'imperial'	=> 'Farenhiet °F', 
			);	
		  $this->prefs['current_weather_unit']['writeParms'] 		= $effects;	
		  $this->prefs['current_weather_unit']['readParms'] 		= $effects;	
  }
  /*	public function customPage()
	{
			$ns = e107::getRender();
			$text = "!";
			$ns->tablerender("Hello",$text);	
			
	}
  */
}

class plugin_current_weather_admin_form_ui extends e_admin_form_ui
{   
	function current_weather_scheme($curVal,$mode)
	{
		$frm = e107::getForm();
		switch($mode)
		{
			case 'write':
				return $frm->radio('current_weather_scheme', array(
					"white" 	=> "<img src='images/white/02d.png' class='img-responsive' width='32'> ".LAN_WEATHER_ADMIN_COLOR_1,
					"dark" 	=> "<img src='images/dark/02d.png' class='img-responsive' width='32'> ".LAN_WEATHER_ADMIN_COLOR_2,
					"color" 	=> "<img src='images/color/02d.png' class='img-responsive' width='32'> ".LAN_WEATHER_ADMIN_COLOR_3,  
					), 
					$curVal, array('sep' => '<br />'));
			break;
		}
	}	
}
		
new current_weather_admin();

require_once(e_ADMIN."auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN."footer.php");
exit;

?>