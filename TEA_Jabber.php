<?php

if (!defined('SMF'))
	die('Hacking attempt...');

Global $teats, $db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings;
loadLanguage('TEA');

require_once($sourcedir.'/TEAC.php');

class TEA_Jabber extends TEAC
{
	function __construct(&$db_prefix, &$sourcedir, &$modSettings, &$user_info, &$context, &$txt, &$smcFunc, &$settings)
	{
	//	$this -> db_prefix = &$db_prefix;
		$this -> sourcedir = &$sourcedir;
		$this -> modSettings = &$modSettings;
		$this -> user_info = &$user_info;
		$this -> context = &$context;
		$this -> txt = &$txt;
		$this -> smcFunc = &$smcFunc;
		$this -> settings = &$settings;
		$this -> load_db_support();
	}

	function load_db_support()
	{
		require_once($this -> sourcedir.'/TEA_Jabber_OF.php');
		$this -> db = &$jabber_db;
		$this -> db -> jabber = $this;
	}

	function undefined()
	{

	}

	function ts_db_connect()
	{
		$host = $this -> modSettings["tea_ts_db_host"];
		$user = $this -> modSettings["tea_ts_db_user"];
		$pw = $this -> modSettings["tea_ts_db_pw"];
		$this -> ts_connection = mysql_connect($host, $user, $pw) or die(mysql_error());
		//mysql_select_db($sqldatabase) or die(mysql_error());
	}

	function jabber_connect()
	{ //  not used?
		$host = $this -> modSettings["tea_ts_db_host"];
		$user = $this -> modSettings["tea_ts_db_user"];
		$pw = $this -> modSettings["tea_ts_db_pw"];
		$db = $this -> modSettings["tea_ts_db_db"];
		$this -> ts_connection = mysql_connect($host, $user, $pw) or die(mysql_error());
		mysql_select_db($db, $this -> ts_connection) or die(mysql_error());
	}

	function ts_select ($sql, $result_form=MYSQL_NUM)//MYSQL_ASSOC = field names
	{
		$data = "";
		$result = mysql_query($sql, $this -> ts_connection);

		if (!$result)
		{
//			echo $sql;
			echo mysql_error();
			return false;
		}

		if (empty($result))
		{
			return false;
		}

		$count = 0;

		while ($row = mysql_fetch_array($result, $result_form))
		{
			$data[] = $row;
		}

		mysql_free_result($result);
		return $data;
	}

	function ts_query ($sql)
	{
		$return = mysql_query($sql, $this -> ts_connection);

		if (!$return)
		{
			echo mysql_error();
			return false;
		}
		else
		{
			return true;
		}
	}

	function settings($scripturl)
	{
		// $chars = $this -> tea -> get_characters($userid, $api);

		// $charlist = array();
		// if(!empty($chars))
		// {
			// foreach($chars as $char)
			// {
				// $charlist[$char['charid']] = $char['name'];
				// if($charid == $char['charid'])
				// {
					// $corp = $char['corpid'];
					// $alliance = $char['allianceid'];
				// }
			// }
		// }
		if(!empty($this -> tea -> modSettings['tea_jabber_db_host']))
		{
			$gl = $this -> db -> get_groups();
			$groups2 = $this -> tea -> MemberGroups(TRUE);
			$groups[-1] = '-';
			foreach($groups2 as $i => $g)
			{
				$groups[$i] = $g;
			}
			// require_once($this -> sourcedir . '/TS3_Class/TeamSpeak3.php');
			// $tslv = TeamSpeak3::LIB_VERSION;

			// TeamSpeak3::init();

			// try
			// {
				// $ts3 = TeamSpeak3::factory("serverquery://".$this -> modSettings["tea_ts_username"].":".$this -> modSettings["tea_ts_password"]."@".$this -> modSettings["tea_ts_host"].":".$this -> modSettings["tea_ts_qport"]."/?server_port=".$this -> modSettings["tea_ts_port"]."&blocking=0");

				// $sg = $ts3 -> serverGroupList();
				// $gl[0] = '-';
				// foreach($sg as $n)
				// {
				//	if((int)$n -> type == 1 && (string)$n -> name != 'Guest')
					//	$gl['s'.(int)$n -> sgid] = 'Server - '.(string)$n -> name;
				//		$gl['s'.(int)$n -> sgid] = (string)$n -> name;
				//}
		//		$cg = $ts3 -> channelGroupList();
		//		foreach($cg as $i => $n)
		//		{
		//			if((int)$n -> type == 1)
		//				$gl['c'.$i] = 'Channel - '.(string)$n -> name;
		//		}
			//}
			//catch(Exception $e)
			//{
			//	echo("[ERROR] " . $e->getMessage() . "\n");
			//}
		}

		$cgq = $this -> smcFunc['db_query']('', "SELECT id, value FROM {db_prefix}tea_jabber_groups ORDER BY id");
		$cgq = $this -> tea -> select($cgq);
		if(!empty($cgq))
		{
			foreach($cgq as $cgqs)
				$cg[$cgqs[0]] = $cgqs[1];
		}
		$groupcheck = '<dt>'.$this -> txt['tea_groupmon'].'
		<table><tr><td>Name</td><td>Checked</td></tr>';
		foreach($gl as $id => $g)
		{
			if($id != '0')
			{
				$check = '';
				if($cg[$id] == 1)
					$check = 'checked';
				$groupcheck .= '<tr><td>'.$g.'</td><td><input type="checkbox" name="group['.$id.']" value="main" '.$check.' /></td><td></tr>';
			}
		}
		$groupcheck .= '<tr><td>
			</td></tr></table></dt>';


		$config_vars = array(
			'</form>
			<form name="miniform" method="post" action="">
				<input type="hidden" name="minitype" value="" />
				<input type="hidden" name="value" value="" />
			</form>
			<form action="'.$scripturl.'?action=admin;area=tea;sa=jabber;save" method="post" accept-charset="ISO-8859-1" name="tea_jabber_settings">',
				// enable?
				array('check', 'tea_jabber_enable'),
			'',
			$groupcheck,
			'',
				array('text', 'tea_jabber_db_host', 15),
				array('text', 'tea_jabber_db_user', 15),
				array('text', 'tea_jabber_db_pw', 15),
				array('text', 'tea_jabber_db_db', 15),
		//		array('text', 'tea_jabber_db_pre', 15),
				array('text', 'tea_jabber_unf', 15),
				array('text', 'tea_jabber_nf', 15),
				array('text', 'tea_jabber_admin_url', 30),
				array('text', 'tea_jabber_secret', 15),
				'<dt>Jabber Info (HTML) will display on Jabber area of profile</dt>',
				'<dt><textarea name="tea_jabber_info" cols=120 rows=6>'.$this -> modSettings['tea_jabber_info'].'</textarea></dt>',
				'',
		//		'',
		);
		$rules = $this -> smcFunc['db_query']('', "SELECT id, smf, jabber, nf FROM {db_prefix}tea_jabber_rules ORDER BY id");
		$rules = $this -> tea -> select($rules);
		$ro = '<table border="1"><tr><td>Rule ID</td><td>SMF Group</td><td>Jabber Group</td><td>Name Format</td></tr>';
		$ids['new'] = 'new';
		if(!empty($rules))
		{
			$first = TRUE;
			foreach($rules as $i => $r)
			{
				$ids[$r[0]] = $r[0];
				$ro .= '<tr><td>'.$r[0].'</td><td>'.$groups[$r[1]].'</td><td>'.$gl[$r[2]].'</td><td>'.$r[3].'</td>';
				$ro .= '<td>';
				if(!$first)
					$ro .= '<a href="javascript:move('.$r[0].', \'up\')"><img src="'.$this -> settings['images_url'].'/sort_up.gif"></a>';
				if($i != (count($rules)-1))
					$ro .= '<a href="javascript:move('.$r[0].', \'down\')"><img src="'.$this -> settings['images_url'].'/sort_down.gif"></a>';
				$ro .= '<a href="javascript:edit('.$r[0].', '.$r[1].', \''.$r[2].'\', \''.$r[3].'\')"><img src="'.$this -> settings['images_url'].'/icons/config_sm.gif"></a>
				<a href="javascript: delrule('.$r[0].')"><img src="'.$this -> settings['images_url'].'/icons/quick_remove.gif"></a></td></tr>';
				$first = FALSE;
			}
		}
		$config_vars[] = '<dt>'.$ro.'</table></dt>';
		$config_vars[] = '';
		$config_vars[100] = array('select', 'tea_jabber_addrule_id', $ids, 'Rule ID');
		$config_vars[101] = array('select', 'tea_jabber_addrule_group', $groups);
		$config_vars[102] = array('select', 'tea_jabber_addrule_jabber', $gl);
		$config_vars[103] = array('text', 'tea_jabber_addrule_nf', 15);
		//$config_vars[103] = array('hidden', 'tea_jabber_editrule_id');
		$config_vars[] = '
<script type="text/javascript">
function edit(id, smf, jabber, nf)
{
	document.tea_jabber_settings.tea_jabber_addrule_id.value=id;
	document.tea_jabber_settings.tea_jabber_addrule_group.value=smf;
	document.tea_jabber_settings.tea_jabber_addrule_jabberg.value=jabber;
	document.tea_jabber_settings.tea_jabber_addrule_nf.value=nf;
}
function delrule(value)
{
	if (confirm("\nAre you sure you want Delete rule "+value+"?"))
		subform(\'delrule\', value);
}
function subform(type, value)
{
	document.miniform.minitype.value=type;
	document.miniform.value.value=value;
	document.miniform.submit();
}
function move(id, value)
{
	subform(value, id);
}
</script>';
			if($_POST['minitype'] == 'delrule')
			{
				if(!is_numeric($_POST['value']))
					die("delete value must be number");
				$this -> tea -> query("DELETE FROM {db_prefix}tea_jabber_rules WHERE id = ".$_POST['value']);
				redirectexit('action=admin;area=tea;sa=jabber');
			}
			elseif($_POST['minitype'] == 'up' || $_POST['minitype'] == 'down')
			{
				$id = $_POST['value'];
				if(!is_numeric($id))
					die("move id must be number");
				$rules = $this -> smcFunc['db_query']('', "SELECT id FROM {db_prefix}tea_jabber_rules ORDER BY id");
				$rules = $this -> tea -> select($rules);
				if(!empty($rules))
				{
				//	foreach($rules as $rule)
				//	{
				//		$rl[$rule[1]][$rule[0]] = $rule[0];
				//		if($rule[0] == $id)
				//			$main = $rule[1];
				//	}
				//	if(isset($main))
				//	{
				//		$rules = $rl[$main];
				//		sort($rules);
					foreach($rules as $i => $rule)
					{
						if($rule[0] == $id)
						{
							if($_POST['minitype'] == 'up')
								$move = $rules[$i-1][0];
							elseif($_POST['minitype'] == 'down')
								$move = $rules[$i+1][0];
							$this -> tea -> query("UPDATE {db_prefix}tea_jabber_rules SET id = -1 WHERE id = ".$move);
							$this -> tea -> query("UPDATE {db_prefix}tea_jabber_rules SET id = $move WHERE id = ".$id);
							$this -> tea -> query("UPDATE {db_prefix}tea_jabber_rules SET id = $id WHERE id = -1");
							Break;
						}
					}
				}
				redirectexit('action=admin;area=tea;sa=jabber');
			}
			if(isset($_POST['group']))
			{
				$this -> tea -> query("DELETE FROM {db_prefix}tea_jabber_groups");
				foreach($_POST['group'] as $g => $v)
				{
					$this -> tea -> query("
						INSERT INTO {db_prefix}tea_jabber_groups
							(id, value)
						VALUES 
							({string:id}, {int:value})",
							array('id' => $g, 'value' => '1'));
				}
			}
		// Saving?
		if (isset($_GET['save']))
		{
			if($_POST['tea_jabber_addrule_group'] > 0 && !empty($_POST['tea_jabber_addrule_jabber']))
			{
				if(!is_numeric($_POST['tea_jabber_addrule_group']))
					die('Group must be Number');
				if($_POST['tea_jabber_addrule_id'] == 'new')
					$this -> tea -> query("INSERT INTO {db_prefix}tea_jabber_rules (smf, jabber, nf) VALUES (".$_POST['tea_jabber_addrule_group'].", '".$_POST['tea_jabber_addrule_jabber']."', '".mysql_real_escape_string($_POST['tea_jabber_addrule_nf'])."')");
				else
				{
					if(!is_numeric($_POST['tea_jabber_addrule_id']))
						die('ID must be Number or new');
					$this -> tea -> query("UPDATE {db_prefix}tea_jabber_rules SET smf = ".$_POST['tea_jabber_addrule_group'].", jabber = '".$_POST['tea_jabber_addrule_jabber']."', nf = '".mysql_real_escape_string($_POST['tea_jabber_addrule_nf'])."' WHERE id = ".$_POST['tea_jabber_addrule_id']);
				}
			}
			unset($config_vars[100], $config_vars[101], $config_vars[102], $config_vars[103]);
			$config_vars[] = array('select', 'tea_charid', $charlist);
			$config_vars[] = array('text', 'tea_jabber_info');
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=tea;sa=jabber');

			loadUserSettings();
			writeLog();
		}

		$this -> context['post_url'] = $scripturl . '?action=admin;area=tea;sa=jabber;save';
	//	$context['settings_title'] = $txt['tea_tea'];
	//	$context['settings_message'] = $txt['tea_settings_message'];

		prepareDBSettingContext($config_vars);
	}

	function tea_set_jabber($memberID, $reg=FALSE)
	{
		if(!$this -> modSettings["tea_enable"] || !$this -> modSettings["tea_jabber_enable"])
			Return;

	//	echo $memberID." kk ".$db_prefix;
	//	var_dump($_POST);
		if(!is_numeric($memberID))
			return;

		$rules = $this -> smcFunc['db_query']('', "SELECT id, smf, jabber, nf FROM {db_prefix}tea_jabber_rules");
		$rules = $this -> tea -> select($rules);
			
		$usergroupssql = $this -> smcFunc['db_query']('', "SELECT id_group, additional_groups, email_address FROM {db_prefix}members WHERE id_member = ".$memberID);
		$usergroupssql = $this -> tea -> select($usergroupssql);

		if(!empty($usergroupssql))
		{
			$email = $usergroupssql[0][2];
			$usergroups[$usergroupssql[0][0]] = true;
			if(!empty($usergroupssql[0][1]))
			{
				$usergroupssql[0][1] = explode(",", $usergroupssql[0][1]);
				foreach($usergroupssql[0][1] as $g)
				{
					$usergroups[$g] = true;
				}
			}
		}
		if(!empty($rules))
		{
			foreach($rules as $r)
			{
				if(isset($usergroups[$r[1]]))
				{
					$jabbergs[$r[2]] = $r[2];
				}
			}
		}

		$char = $_POST['tea_jabber_char'];
		$char = html_entity_decode($char, ENT_QUOTES);
		$name = $this -> format_jabber_name($memberID, $char);
		$nick = $this -> format_jabber_name($memberID, $char, FALSE, FALSE);

		$cgq = $this -> smcFunc['db_query']('', "SELECT id, value FROM {db_prefix}tea_jabber_groups ORDER BY id");
		$cgq = $this -> tea -> select($cgq);
		if(!empty($cgq))
		{
			foreach($cgq as $cgqs)
				$cg[$cgqs[0]] = $cgqs[1];
		}
		$userg = $this -> db -> get_user_groups($name);
		if(!empty($userg))
		{
			foreach($userg as $g)
			{
				if(!isset($jabbergs[$g]) && $cg[$g] != 1)
					$jabbergs[$g] = $g;
			}
		}

		if(!empty($jabbergs))
		{
			//require_once($this -> sourcedir . '/TS3_Class/TeamSpeak3.php');

			//$tslv = TeamSpeak3::LIB_VERSION;

			//TeamSpeak3::init();

			//try
			//{
			//	$ts3 = TeamSpeak3::factory("serverquery://".$this -> modSettings["tea_ts_username"].":".$this -> modSettings["tea_ts_password"]."@".$this -> modSettings["tea_ts_host"].":".$this -> modSettings["tea_ts_qport"]."/?server_port=".$this -> modSettings["tea_ts_port"]."&blocking=0");


					$dupcheck = $this -> smcFunc['db_query']('', "SELECT id, username, name FROM {db_prefix}tea_jabber_users WHERE username = '".mysql_real_escape_string($name)."'");
					$dupcheck = $this -> tea -> select($dupcheck);
					if(!empty($dupcheck))
					{
						if($dupcheck[0][0] != $memberID)
						{
							$_SESSION['tea_jabber_error'][] = 'UniqueID already attached to another forum Member';
							return;
						}
					}

					$pw = $_POST['tea_jabber_pw'];
					$scheck = $this -> smcFunc['db_query']('', "SELECT id, username, name FROM {db_prefix}tea_jabber_users WHERE id = ".$memberID);
					$scheck = $this -> tea -> select($scheck);
					if(!empty($scheck))
					{
						if($scheck[0][1] != $name)
						{
						//	try
						//	{
							$this -> db -> del_user($scheck[0][1]);
							$this -> tea -> query("DELETE FROM {db_prefix}tea_jabber_users WHERE username = '".mysql_real_escape_string($scheck[0][1])."'");
							if($get = $this -> db -> get_user($name))
							{
							//	if($pw != $get['pw'])
							//	{
									$_SESSION['tea_jabber_error'][] = 'Unlinked Jabber Account Found, Unable to Link';
									return;
							//	}
							}
							else
							{
								if(!empty($pw) && !strstr($pw, '*'))
									$this -> db -> add_user($name, $pw, $nick, $email, $jabbergs);
								else
								{
									$_SESSION['tea_jabber_error'][] = 'Invalid Password';
									return;
								}
							}
						//	}
						//	catch(Exception $e)
						//	{
								//maybe online?
						//	}
						}
						else
						{
							if(!empty($pw) && !strstr($pw, '*'))
								$this -> db -> update_user($name, $pw, $nick, $email, $jabbergs);
							else
							{
								$this -> db -> update_user($name, FALSE, $nick, $email, $jabbergs);
							}
						}
					}
					else
					{
						if($get = $this -> db -> get_user($name))
						{
							if($pw != $get['pw'])
							{
								$_SESSION['tea_jabber_error'][] = 'Unlinked Jabber Account Found';
								return;
							}
						}
						else
						{
							if(!empty($pw) && !strstr($pw, '*'))
								$this -> db -> add_user($name, $pw, $nick, $email, $jabbergs);
							else
							{
								$_SESSION['tea_jabber_error'][] = 'Invalid Password';
								return;
							}
						}
					}



		//	if(!empty($jabbergs))
		//	{

				// $cgq = $this -> smcFunc['db_query']('', "SELECT id, value FROM {db_prefix}tea_jabber_groups ORDER BY id");
				// $cgq = $this -> tea -> select($cgq);
				// if(!empty($cgq))
				// {
					// foreach($cgq as $cgqs)
						// $cg[$cgqs[0]] = $cgqs[1];
				// }
				// $userg = $this -> db -> get_user_groups($name);
				// if(!empty($userg))
				// {
					// foreach($userg as $g)
					// {
						// if(!isset($jabbergs[$g]) && $cg[$g] == 1)
							// $this -> db -> rem_from_group($name, $g);
					// }
				// }
				// foreach($jabbergs as $g => $v)
				// {
					// if(!isset($userg[$g]))
						// $this -> db -> add_to_group($name, $g);
				// }
		//	}
			//}
			//catch(Exception $e)
			//{
			//	$_SESSION['tea_jabber_error'][] = $e->getMessage();
			//	$error = TRUE;
			//}
			if(!$error)
				$this -> tea -> query("
					REPLACE INTO {db_prefix}tea_jabber_users
						(id, username, name)
					VALUES 
					($memberID, '".mysql_real_escape_string($name)."', '".mysql_real_escape_string($char)."')");
		}
		else
		{
			$_SESSION['tea_jabber_error'][] = 'Unable to Match any Groups';
		}
	}

	function check_access()
	{
		$this -> all_users();
	}

	function check_names()
	{
		$this -> online_users();
	}

	function all_users()
	{
		require_once($this -> sourcedir . '/TS3_Class/TeamSpeak3.php');

		$tslv = TeamSpeak3::LIB_VERSION;

		TeamSpeak3::init();

		try
		{
			$ts3 = TeamSpeak3::factory("serverquery://".$this -> modSettings["tea_ts_username"].":".$this -> modSettings["tea_ts_password"]."@".$this -> modSettings["tea_ts_host"].":".$this -> modSettings["tea_ts_qport"]."/?server_port=".$this -> modSettings["tea_ts_port"]."&blocking=0");

			$getclist = $ts3 -> clientListDb();
			$clist = $getclist;
			$next = 25;
			while(count($getclist) == 25)
			{
				$getclist = $ts3 -> clientListDb($next, 25);
				$next += 25;
				$clist = array_merge($clist, $getclist);
			}
			if(!empty($clist))
			{
				$rules = $this -> smcFunc['db_query']('', "SELECT id, smf, ts, tst, nf FROM {db_prefix}tea_ts_rules");
				$rules = $this -> tea -> select($rules);
				$cgq = $this -> smcFunc['db_query']('', "SELECT id, value FROM {db_prefix}tea_ts_groups ORDER BY id");
				$cgq = $this -> tea -> select($cgq);
				if(!empty($cgq))
				{
					foreach($cgq as $cgqs)
						$cg[$cgqs[0]] = $cgqs[1];
				}
				foreach($clist as $c)
				{
					sleep(0.2);
					$cldbid = (int)$c['cldbid'];
					$tsgs = array();
					$smf = $this -> smcFunc['db_query']('', "SELECT id, tsid, dbid, name FROM {db_prefix}tea_ts_users WHERE tsid = '".(string)$c['client_unique_identifier']."'");
					$smf = $this -> tea -> select($smf);
					if(!empty($smf))
					{
						$smfgroups = $this -> tea -> smf_groups($smf[0][0]);
						foreach($smfgroups as $g)
						{
							if(!empty($rules))
							{
								foreach($rules as $r)
								{
									if($r[1] == $g)
									{
										$tsgs[$r[3]][$r[2]] = TRUE;
									}
								}
							}
						}
						$sinfo = $ts3 -> clientGetServerGroupsByDbid($cldbid);
						if(!empty($sinfo))
						{
							foreach($sinfo as $s => $v)
							{
								if(!isset($tsgs['s'][$s]) && (string)$v['name'] != 'Guest' && $cg['s'.$s] == 1)
									$ts3 -> serverGroupClientDel($s, $cldbid);
							}
						}
						if(!empty($tsgs))
						{
							foreach($tsgs as $t => $v)
							{
								foreach($v as $g => $v2)
								{
									if($t == 's')
									{
										if(!isset($sinfo[$g]) && $cg['s'.$g] == 1)
											$ts3 -> serverGroupClientAdd($g, $cldbid);
									}
								}
							}
						}
					}
				//	$cinfo = $ts3 -> clientGetServerGroupsByDbid((int)$c['cldbid']);
				}
			}
		}
		catch(Exception $e)
		{
			die($e->getMessage());
			$_SESSION['tea_ts_error'][] = $e->getMessage();
		}
	}

	function online_users()
	{
		if($this -> modSettings["tea_ts_warnm"] == 0 && $this -> modSettings["tea_ts_kickm"] == 0)
			Return;

		require_once($this -> sourcedir . '/TS3_Class/TeamSpeak3.php');

		$tslv = TeamSpeak3::LIB_VERSION;

		TeamSpeak3::init();

		try
		{
			$ts3 = TeamSpeak3::factory("serverquery://".$this -> modSettings["tea_ts_username"].":".$this -> modSettings["tea_ts_password"]."@".$this -> modSettings["tea_ts_host"].":".$this -> modSettings["tea_ts_qport"]."/?server_port=".$this -> modSettings["tea_ts_port"]."&blocking=0");

			$clist = $ts3 -> clientList();
			foreach($clist as $c)
			{
				if($c -> client_type == 0)
				{
					$clid = (string)$c -> client_unique_identifier;
					$cnick = (string)$c -> client_nickname;
					$smf = $this -> smcFunc['db_query']('', "SELECT id, tsid, dbid, name, warnstart, lastwarn FROM {db_prefix}tea_ts_users WHERE tsid = '".$clid."'");
					$smf = $this -> tea -> select($smf);
					if(!empty($smf))
					{
						$warned = FALSE;
						$kick = FALSE;
						$time = time() - ($this -> modSettings["tea_ts_warnm"] * 60);
						if($smf[0][5] < $time)
						{
							$char = $smf[0][3];
						//	$chars = $this -> tea -> get_all_chars($smf[0][0]);
							$name = $this -> format_ts_name($smf[0][0], $char);
						//	$aid = NULL;
							if(!empty($name))
							{
						//		foreach($chars as $i => $ch)
						//		{
						//			if($ch[0] == $char)
						//				$aid = $i;
						//		}
								if($name != $cnick)
								{
									if($this -> modSettings["tea_ts_kickm"] != 0 && $smf[0][4] != 0 && $smf[0][4] < (time() - $this -> modSettings["tea_ts_kickm"] * 60))
									{
										$c -> kick(TeamSpeak3::KICK_SERVER, 'Incorrect Nickname, Expecting: '.$name);
										$this -> smcFunc['db_query']('', "UPDATE {db_prefix}tea_ts_users SET lastwarn = 0, warnstart = 0 WHERE tsid = '".$clid."'");
									}
									elseif($this -> modSettings["tea_ts_warnm"] != 0)
									{
										$c -> poke('Incorrect Nickname, Expecting: '.$name);
										$warned = TRUE;
									}
								}
								else
								{
									$this -> smcFunc['db_query']('', "UPDATE {db_prefix}tea_ts_users SET lastwarn = 0, warnstart = 0 WHERE tsid = '".$clid."'");
								}
							}
							else
							{
								$c -> message('Error Unable to Find Character');
								$warned = TRUE;
							}
						}
						if($warned)
						{
							$sql = '';
							if($smf[0][4] == 0)
								$sql = ', warnstart = '.time();
							$this -> smcFunc['db_query']('', "UPDATE {db_prefix}tea_ts_users SET lastwarn = ".time().$sql." WHERE tsid = '".$clid."'");
						}
					}
					else
					{
						$c -> message('Error: SMF Account not Found, Please Register on Forum and use Temars EVE API mod to link Teamspeak to forum');
					}
				}
			}
		}
		catch(Exception $e)
		{
			die($e->getMessage());
			$_SESSION['tea_ts_error'][] = $e->getMessage();
		}
	}

	function format_jabber_name($memID, $char, $username = true, $nospace = true)
	{
		$chars = $this -> tea -> get_all_chars($memID);

		$smfgroups = $this -> tea -> smf_groups($memID);
		if(!empty($chars))
		{
			if(!$username)
			{
				$rules = $this -> smcFunc['db_query']('', "SELECT id, smf, jabber, nf FROM {db_prefix}tea_jabber_rules");
				$rules = $this -> tea -> select($rules);
				if(!empty($rules))
				{
					foreach($rules as $r)
					{
						if(!empty($smfgroups))
						{
							foreach($smfgroups as $g)
							{
								if($r[1] == $g)
								{
									if(!isset($nf))
										$nf = $r[4];
								}
							}
						}
					}
				}
			}
			foreach($chars as $i => $ch)
			{
				if($ch[0] == $char)
					$charinfo = $ch;
			}
			if(!empty($charinfo))
			{
				if($nf)
					$name = $nf;
				else
				{
					if($username)
						$name = $this -> modSettings["tea_jabber_unf"];
					else
						$name = $this -> modSettings["tea_jabber_nf"];
				}
				$name = str_replace('#at#', $charinfo[4], $name);
				$name = str_replace('#ct#', $charinfo[1], $name);
				$name = str_replace('#name#', $char, $name);
			}
		}
	//	if(strlen($name) > 30)
	//	{
	//		$name = substr($name, 0, 30);
	//	}
		if($nospace)
			$name = str_replace(" ", "_", $name);
		if($username)
			$name = strtolower($name);
		return $name;
	}
}

Global $teaj;
$teaj = new TEA_Jabber($db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings);

function template_edit_tea_jabber()
{
	global $tea, $teaj, $teainfo, $sourcedir, $context, $settings, $options, $scripturl, $modSettings, $txt, $smcFunc;

	echo '
		<form action="', $scripturl, '?action=profile;area=tea;sa=jabber;save" method="post" accept-charset="', $context['character_set'], '" name="creator" id="creator">
			<table border="0" width="100%" cellspacing="1" cellpadding="4" align="center" class="bordercolor">
				<tr class="titlebg">
					<td height="26">
						&nbsp;<img src="', $settings['images_url'], '/icons/profile_sm.gif" alt="" border="0" align="top" />&nbsp;
						', $txt['tea_tea'], '
					</td>
				</tr><tr class="windowbg">
					<td class="smalltext" height="25" style="padding: 2ex;">
						', $txt['tea_jabber_userinfo'], '
					</td>
				</tr><tr>
					<td class="windowbg2" style="padding-bottom: 2ex;">
						<table border="0" width="100%" cellpadding="3">';
	if(!$modSettings["tea_jabber_enable"])
	{
		echo '<tr><td>'.$txt['tea_jabber_disabled'].'</td></tr>';
	}
	else
	{
		if(isset($modSettings['tea_jabber_info']))
			echo '<tr><td>'.str_replace("\n", "<br>", $modSettings['tea_jabber_info']).'</td></tr>';
		echo '<tr><td colspan="2"><hr></td></tr>';
		if(!empty($_SESSION['tea_jabber_error']))
		{
			foreach($_SESSION['tea_jabber_error'] as $e)
				echo "<tr><td>[ERROR] ".$e."</td></tr>";
			unset($_SESSION['tea_jabber_error']);
		}
		if(!empty($teainfo))
		{
			$memberID = $tea -> memid;
			$jabberinfo = $smcFunc['db_query']('', "SELECT username, name FROM {db_prefix}tea_jabber_users WHERE id = ".$memberID);
			$jabberinfo = $tea -> select($jabberinfo);
			if(!empty($jabberinfo))
			{
				$sname = $jabberinfo[0][1];
				$jlogin = $jabberinfo[0][0];
				$name = $teaj -> format_jabber_name($memberID, $sname, FALSE, FALSE);
				//$name = $sname;
				echo '<tr><td>Name</td><td>'.$name.'</td></tr>';
				echo '<tr><td>Jabber Login</td><td>'.$jlogin.'</td></tr>';
			}
			else
			{
				echo '<tr><td>Not Registered on Jabber</td></tr>';
			}
			echo '<tr><td colspan="2"><hr></td></tr>';
		//	if(isset($modSettings["tea_jabber_method_online"]) && $modSettings["tea_jabber_method_online"])
		//		$count[] = 'online';
		//	if(isset($modSettings["tea_jabber_method_create"]) && $modSettings["tea_jabber_method_create"])
		//		$count[] = 'create';
		//	if(isset($modSettings["tea_jabber_method_priv"]) && $modSettings["tea_jabber_method_priv"])
		//		$count[] = 'priv';
		//	if(count($count) == 1)
		//		echo '<tr><td><input type="hidden" name="method" value="'.$count[0].'">';
		//	if(isset($modSettings["tea_jabber_method_online"]) && $modSettings["tea_jabber_method_online"])
		//	{
		//		if(count($count) != 1)
		//			echo '<tr><td><input type="radio" name="method" value="online"> Online Name Check</td></tr>';
		//	}
			echo '<tr><td>Main Char</td></tr>';
			echo '<tr><td>';
			if(!$modSettings["tea_enable"])
				echo '<tr><td>Name: <input type="text" name="tea_jabber_char" value="">';
			elseif(!empty($teainfo))
			{
				echo '<select name="tea_jabber_char">';
				foreach($teainfo as $i => $info)
				{
					foreach($info['charnames'] as $i => $info)
					{
						$name = $teaj -> format_jabber_name($memberID, $info[0]);
						//$name = $info[0];
						echo '<option value="'.$info[0].'"', $info[0] == $sname ? ' SELECTED ' : '','>'.$name.'</option>';
					}
				}
				echo '</select>';
			}
			echo '</td></tr><tr><td>Password: <input type="password" name="tea_jabber_pw" value=""></td></tr>';
		//	if(isset($modSettings["tea_jabber_method_create"]) && $modSettings["tea_jabber_method_create"])
		//	{
		//		if(count($count) != 1)
		//			echo '<tr><td><input type="radio" name="method" value="create"> Use jabber Unique ID</td></tr>';
		//		echo '<tr><td>Register Using your jabber Unique ID</td></tr>';
		//		echo '<tr><td><input type="text" name="jabberid" value="'.$uniqueid.'"></td></tr>';
		//	}
		}
		else
		{
			echo "<tr><td>[ERROR] No API Info Found and is Required for Character Info</td></tr>";
		}
	//		echo '<tr><td colspan="3"><hr class="hrcolor" width="100%" size="1"/></td></tr>';
	//	echo '<tr><td>
	//				<b>', $txt['tea_status'], ':</b></td><td>'.$info['status'];
	//	if($info['status'] == 'API Error')
	//		echo ' ('.$info['error'].')';
	//	echo '</td>
	//		</tr><tr><td><b>', $txt['tea_mainrule'], ':</b></td><td>'.$info['mainrule'].'</td>
	//		</tr><tr><td><b>', $txt['tea_aditrules'], ':</b></td><td>'.$info['aditrules'].'</td>
	//		</tr><tr><td>
	//									<b>', $txt['tea_characters'], ':</b></td><td>';
	//	if(!empty($info['charnames']))
	//	{
	//		echo '<style type="text/css">
//green {color:green}
//blue {color:blue}
//red {color:red}
//</style>';
	//		$echo = array();
	//		foreach($info['charnames'] as $char)
	//		{
	//			$char[3] = $char[3] != '' ? ' / <blue>'.$char[3].'</blue>' : '';
	//			$echo[] = '['.$char[1].'] '.$char[0].' (<green>'.$char[2].'</green>'.$char[3].')';
	//		}
	//		echo implode('<br>', $echo);
	//	}
	//	echo '</td></tr>
	//	<tr><td>
	//									<b>', $txt['tea_userid'], ':</b></td>
	//									<td>';
	//				if($info['userid'] == "")
	//					echo '<input type="text" name="tea_user_id[]" value="'.$info['userid'].'" size="20" />';
					// else
					// {
						// echo '<input type="hidden" name="tea_user_id[]" value="'.$info['userid'].'" size="20" />';
						// echo $info['userid'].'</td><td> <input type="checkbox" name="del_api[]" value="'.$info['userid'].'" /> Delete</td>';
					// }
						// echo '			</td>
								// </tr><tr>
									// <td width="40%">										<b>', $txt['tea_api'], ':</b></td>
										// <td><input type="text" name="tea_user_api[]" value="'.$info['api'].'" size="64" />
									// </td>
								// </tr>';
			//	}
		template_profile_save();
	}
	echo '
						</table>
					</td>
				</tr>
			</table>
		</form>';
}

?>