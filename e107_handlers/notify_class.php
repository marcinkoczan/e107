<?php
/*
* e107 website system
*
* Copyright (C) 2008-2013 e107 Inc (e107.org)
* Released under the terms and conditions of the
* GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
*
*/

/**
 *	@package    e107
 *	@subpackage	e107_handlers
 *
 *	Handler for 'notify' events - sends email notifications to the appropriate user groups
 */

if (!defined('e107_INIT')) { exit; }

class notify
{
	var $notify_prefs;

	function notify()
	{
		global $e_event;
		
		$this->notify_prefs = e107::getConfig('notify')->getPref();
	
		if(varset($this->notify_prefs['event']))
		{
			foreach ($this->notify_prefs['event'] as $id => $status)
			{
				if ($status['class'] != 255)
				{
					if(varset($status['include'])) // Plugin 
					{
						$include 	= e_PLUGIN.$status['include']."/e_notify.php";
						
						if(varset($status['legacy']) != 1)
						{
							$class 		= $status['include']."_notify";
							$method 	= $id;
							$e_event->register($id, array($class, $method), $include);	
						}
						else
						{
							$e_event->register($id, 'notify_'.$id, $include);			
						}
					}
					else // core   
					{
						$e_event->register($id, 'notify_'.$id);			
					}
					
				
				}
			}
		}

		include_lan(e_LANGUAGEDIR.e_LANGUAGE.'/lan_notify.php');
	}



	/**
	 * Send an email notification following an event.
	 *
	 * The email is sent via a common interface, which will send immediately for small numbers of recipients, and queue for larger.
	 * 
	 * @param string $id - identifies event actions
	 * @param string $subject - subject for email
	 * @param string $message - email message body
	 * @return none
	 *
	 *	@todo handle 'everyone except' clauses (email address filter done)
	 *	@todo set up pref to not notify originator of event which caused notify (see $blockOriginator)
	 */
	function send($id, $subject, $message)
	{
		$e107 = e107::getInstance();
		$tp = e107::getParser();
		$sql = e107::getDb();

		$subject = $tp->toEmail(SITENAME.': '.$subject);
		$message = $tp->toEmail($message);
		$emailFilter = '';
		$notifyTarget = $this->notify_prefs['event'][$id]['class'];
		if ($notifyTarget == '-email')
		{
			$emailFilter = $this->notify_prefs['event'][$id]['email'];
		}
		$blockOriginator = FALSE;		// TODO: set this using a pref
		$recipients = array();

		if ($notifyTarget == 'email')
		{	// Single email address - that can always go immediately
			if (!$blockOriginator || ($this->notify_prefs['event'][$id]['email'] != USEREMAIL))
			{
				$recipients[] = array(
								 'mail_recipient_email' => $this->notify_prefs['event'][$id]['email']
								 );	
			}
		}
		elseif (is_numeric($this->notify_prefs['event'][$id]['class']))
		{
			switch ($notifyTarget)
			{
				case e_UC_MAINADMIN :
					$qry = "`user_admin` = 1 AND `user_perms` = '0' AND `user_ban` = 0";
					break;
				case e_UC_ADMIN :
					$qry = "`user_admin` = 1 AND `user_ban` = 0";
					break;
				case e_UC_MEMBER :
					$qry = "`user_ban` = 0";
					break;
				default :
					$qry = "user_ban = 0 AND user_class REGEXP '(^|,)(".$this->notify_prefs['event'][$id]['class'].")(,|$)'";
					break;
			}
			$qry = 'SELECT user_id,user_name,user_email FROM `#user` WHERE '.$qry;
			if ($blockOriginator)
			{
				$qry .= ' AND `user_id` != '.USERID;
			}
			if (FALSE !== ($count = $sql->gen($qry)))
			{
				// Now add email addresses to the list
				while ($row = $sql->fetch(MYSQL_ASSOC))
				{
					if ($row['user_email'] != $emailFilter)
					{
						$recipients[] = array('mail_recipient_id' => $row['user_id'],
										 'mail_recipient_name' => $row['user_name'],		// Should this use realname?
										 'mail_recipient_email' => $row['user_email']
										 );	
					}
				}
			}
		}

		if (count($recipients))
		{
			require_once(e_HANDLER.'mail_manager_class.php');
			$mailer = new e107MailManager;

			// Create the mail body
			$mailData = array(
				'mail_content_status' 	=> MAIL_STATUS_TEMP,
				'mail_create_app' 		=> 'notify',
				'mail_title' 			=> 'NOTIFY',
				'mail_subject' 			=> $subject,
				'mail_sender_email' 	=> e107::getPref('siteadminemail'),
				'mail_sender_name'		=> e107::getPref('siteadmin'),
			//	'mail_send_style'		=> 'textonly',
				'mail_notify_complete' 	=> 0,			// NEVER notify when this email sent!!!!!
				'mail_body' 			=> $message,
				'template'				=> 'notify'
			);
			
			$result = $mailer->sendEmails('NOTIFY_TEMPLATE', $mailData, $recipients);
			$e107->admin_log->e_log_event(10,-1,'NOTIFY',$subject,$message,FALSE,LOG_TO_ROLLING);
		}
	}
}




//DEPRECATED, BC, call the method only when needed, $e107->notify caught by __get()
global $nt;
$nt = e107::getNotify(); //TODO - find & replace $nt, $e107->notify


function notify_usersup($data)
{
	global $nt;
	foreach ($data as $key => $value)
	{
		if($key != "password1" && $key != "password2" && $key != "email_confirm" && $key != "register")
		{
			if(is_array($value))  // show user-extended values.
			{
				foreach($value as $k => $v)
				{
					$message .= str_replace("user_","",$k).': '.$v.'<br />';
				}
			}
			else
			{
				$message .=  $key.': '.$value.'<br />';
			}
		}
	}
	$nt->send('usersup', NT_LAN_US_1, $message);
}

function notify_userveri($data)
{
	global $nt, $e107;
	$msgtext = NT_LAN_UV_2.$data['user_id']."\n";
	$msgtext .= NT_LAN_UV_3.$data['user_loginname']."\n";
	$msgtext .= NT_LAN_UV_4.e107::getIPHandler()->getIP(FALSE);
	$nt->send('userveri', NT_LAN_UV_1, $msgtext);
}

function notify_login($data)
{
	global $nt;
	foreach ($data as $key => $value) {
		$message .= $key.': '.$value.'<br />';
	}
	$nt->send('login', NT_LAN_LI_1, $message);
}

function notify_logout()
{
	global $nt;
	$nt->send('logout', NT_LAN_LO_1, USERID.'. '.USERNAME.' '.NT_LAN_LO_2);
}

function notify_flood($data)
{
	global $nt;
	$nt->send('flood', NT_LAN_FL_1, NT_LAN_FL_2.': '.e107::getIPHandler()->ipDecode($data));
}

function notify_subnews($data)
{
	global $nt,$tp;
	foreach ($data as $key => $value) {
		$message .= $key.': '.$value.'<br />';
	}
	$nt->send('subnews', NT_LAN_SN_1, $message);
}

function notify_newspost($data)
{
	$message = '<b>'.$data['news_title'].'</b>';
	if (vartrue($data['news_summary'])) $message .= '<br /><br />'.$data['news_summary'];
	if (vartrue($data['news_body'])) $message .= '<br /><br />'.$data['news_body'];
	if (vartrue($data['news_extended'])) $message.= '<br /><br />'.$data['news_extended'];
	e107::getNotify()->send('newspost', $data['news_title'], e107::getParser()->text_truncate(e107::getParser()->toDB($message), 400, '...'));
}

function notify_newsupd($data)
{
	$message = '<b>'.$data['news_title'].'</b>';
	if (vartrue($data['news_summary'])) $message .= '<br /><br />'.$data['news_summary'];
	if (vartrue($data['news_body'])) $message .= '<br /><br />'.$data['news_body'];
	if (vartrue($data['news_extended'])) $message.= '<br /><br />'.$data['news_extended'];
	e107::getNotify()->send('newsupd', NT_LAN_NU_1.': '.$data['news_title'], e107::getParser()->text_truncate(e107::getParser()->toDB($message), 400, '...'));
}

function notify_newsdel($data)
{
	global $nt;
	$nt->send('newsdel', NT_LAN_ND_1, NT_LAN_ND_2.': '.$data);
}


function notify_maildone($data)
{
	$message = '<b>'.$data['mail_subject'].'</b><br /><br />'.$data['mail_body'];
	e107::getNotify()->send('maildone', NT_LAN_ML_1.': '.$data['mail_subject'], $message);
}


function notify_fileupload($data)
{
	global $nt;
	$message = '<b>'.$data['upload_name'].'</b><br /><br />'.$data['upload_description'].'<br /><br />'.$data['upload_size'].'<br /><br />'.$data['upload_user'];
	$nt->send('fileupload', $data['upload_name'], $message);
}


if (isset($nt->notify_prefs['plugins']) && e_PAGE != 'notify.php')
{
	foreach ($nt->notify_prefs['plugins'] as $plugin_id => $plugin_settings)
	{
		if(is_readable(e_PLUGIN.$plugin_id.'/e_notify.php'))
		{
			require_once(e_PLUGIN.$plugin_id.'/e_notify.php');
		}
	}
}

?>