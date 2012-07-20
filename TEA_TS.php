<?php

if (!defined('SMF'))
	die('Hacking attempt...');

Global $teats, $db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings;
loadLanguage('TEA');

require_once($sourcedir.'/TEAC.php');

class TEA_TS extends TEAC
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
	}

	function undefined()
	{

	}

	function ts_connect()
	{
		$host = $this -> modSettings["tea_ts_db_host"];
		$user = $this -> modSettings["tea_ts_db_user"];
		$pw = $this -> modSettings["tea_ts_db_pw"];
		$this -> ts_connection = mysql_connect($host, $user, $pw, TRUE) or die(mysql_error());
		//mysql_select_db($sqldatabase) or die(mysql_error());
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
		if(isset($_GET['save']))
		{
			$charid = $_POST["tea_charid"];
			$userid = $_POST["tea_userid"];
			$api = $_POST["tea_api"];
		}
		else
		{
			$charid = $this -> modSettings["tea_charid"];
			$userid = $this -> modSettings["tea_userid"];
			$api = $this -> modSettings["tea_api"];
		}
		$chars = $this -> tea -> get_characters($userid, $api);

		$charlist = array();
		if(!empty($chars))
		{
			foreach($chars as $char)
			{
				$charlist[$char['charid']] = $char['name'];
				if($charid == $char['charid'])
				{
					$corp = $char['corpid'];
					$alliance = $char['allianceid'];
				}
			}
		}
		$groups2 = $this -> tea -> MemberGroups(TRUE);
		$groups[-1] = '-';
		foreach($groups2 as $i => $g)
		{
			$groups[$i] = $g;
		}
		$options = '';
		if(!empty($charlist))
		{
			foreach($charlist as $i => $c)
			{
				$options .= '<option value="'.$i.'"';
				if($this -> tea -> modSettings["tea_charid"] == $i)
					$options .= ' selected="selected"';
				$options .= '>'.$c.'</option>
				';
			}
		}
		if(!empty($this -> tea -> modSettings['tea_ts_host']))
		{
			require_once($this -> sourcedir . '/TS3_Class/TeamSpeak3.php');
			$tslv = TeamSpeak3::LIB_VERSION;

			TeamSpeak3::init();

			try
			{
				$ts3 = TeamSpeak3::factory("serverquery://".$this -> modSettings["tea_ts_username"].":".$this -> modSettings["tea_ts_password"]."@".$this -> modSettings["tea_ts_host"].":".$this -> modSettings["tea_ts_qport"]."/?server_port=".$this -> modSettings["tea_ts_port"]."&blocking=0");

				$sg = $ts3 -> serverGroupList();
				$gl[0] = '-';
				foreach($sg as $n)
				{
					if((int)$n -> type == 1 && (string)$n -> name != 'Guest')
					//	$gl['s'.(int)$n -> sgid] = 'Server - '.(string)$n -> name;
						$gl['s'.(int)$n -> sgid] = (string)$n -> name;
				}
		//		$cg = $ts3 -> channelGroupList();
		//		foreach($cg as $i => $n)
		//		{
		//			if((int)$n -> type == 1)
		//				$gl['c'.$i] = 'Channel - '.(string)$n -> name;
		//		}
			}
			catch(Exception $e)
			{
				echo("[ERROR] " . $e->getMessage() . "\n");
			}
		}

		$cgq = $this -> smcFunc['db_query']('', "SELECT id, value FROM {db_prefix}tea_ts_groups ORDER BY id");
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
			<form action="'.$scripturl.'?action=admin;area=tea;sa=ts;save" method="post" accept-charset="ISO-8859-1" name="tea_ts_settings">',
				// enable?
				array('check', 'tea_ts_enable'),
			'',
			$groupcheck,
			'',
				array('text', 'tea_ts_host', 30),
				array('int', 'tea_ts_qport', 6),
				array('int', 'tea_ts_port', 6),
				array('text', 'tea_ts_username', 15),
				array('text', 'tea_ts_password', 15),
				array('text', 'tea_ts_nf', 15),
				'<dt>TeamSpeak Info (HTML) will display on TS area of profile</dt>',
				'<dt><textarea name="tea_ts_info" cols=120 rows=6>'.$this -> modSettings['tea_ts_info'].'</textarea></dt>',
				'',
				$this -> txt['tea_ts_db_need'],
				array('text', 'tea_ts_db_host', 15),
				array('text', 'tea_ts_db_user', 15),
				array('text', 'tea_ts_db_pw', 15),
				array('text', 'tea_ts_db_pre', 15),
				array('int', 'tea_ts_dbid', 3),
				array('check', 'tea_ts_method_online'),
				array('check', 'tea_ts_method_create'),
				'',
				array('int', 'tea_ts_warnm', 4),
				array('int', 'tea_ts_kickm', 4),
				'',
		);
		$rules = $this -> smcFunc['db_query']('', "SELECT id, smf, ts, tst, nf FROM {db_prefix}tea_ts_rules ORDER BY id");
		$rules = $this -> tea -> select($rules);
		$ro = '<table border="1"><tr><td>Rule ID</td><td>SMF Group</td><td>TS Group</td><td>Name Format</td></tr>';
		$ids['new'] = 'new';
		if(!empty($rules))
		{
			$first = TRUE;
			foreach($rules as $i => $r)
			{
				$ids[$r[0]] = $r[0];
				$ro .= '<tr><td>'.$r[0].'</td><td>'.$groups[$r[1]].'</td><td>'.$gl[$r[3].$r[2]].'</td><td>'.$r[4].'</td>';
				$ro .= '<td>';
				if(!$first)
					$ro .= '<a href="javascript:move('.$r[0].', \'up\')"><img src="'.$this -> settings['images_url'].'/sort_up.gif"></a>';
				if($i != (count($rules)-1))
					$ro .= '<a href="javascript:move('.$r[0].', \'down\')"><img src="'.$this -> settings['images_url'].'/sort_down.gif"></a>';
				$ro .= '<a href="javascript:edit('.$r[0].', '.$r[1].', \''.$r[3].$r[2].'\', \''.$r[4].'\')"><img src="'.$this -> settings['images_url'].'/icons/config_sm.gif"></a>
				<a href="javascript: delrule('.$r[0].')"><img src="'.$this -> settings['images_url'].'/icons/quick_remove.gif"></a></td></tr>';
				$first = FALSE;
			}
		}
		$config_vars[] = '<dt>'.$ro.'</table></dt>';
		$config_vars[] = '';
		$config_vars[100] = array('select', 'tea_ts_addrule_id', $ids, 'Rule ID');
		$config_vars[101] = array('select', 'tea_ts_addrule_group', $groups);
		$config_vars[102] = array('select', 'tea_ts_addrule_tsg', $gl);
		$config_vars[103] = array('text', 'tea_ts_addrule_nf', 15);
		//$config_vars[103] = array('hidden', 'tea_ts_editrule_id');
		$config_vars[] = '
<script type="text/javascript">
function edit(id, smf, ts, nf)
{
	document.tea_ts_settings.tea_ts_addrule_id.value=id;
	document.tea_ts_settings.tea_ts_addrule_group.value=smf;
	document.tea_ts_settings.tea_ts_addrule_tsg.value=ts;
	document.tea_ts_settings.tea_ts_addrule_nf.value=nf;
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
		//	if(isset($_POST['tea_useapiabove']))
		//	{
		//		$_POST['tea_corpid'] = $corp;
		//		$_POST['tea_allianceid'] = $alliance;
		//		unset($_POST['tea_useapiabove']);
		//	}
		//	if(!empty($_POST['tea_ts_delrule']
			if($_POST['minitype'] == 'delrule')
			{
				if(!is_numeric($_POST['value']))
					die("delete value must be number");
				$this -> tea -> query("DELETE FROM {db_prefix}tea_ts_rules WHERE id = ".$_POST['value']);
				redirectexit('action=admin;area=tea;sa=ts');
			}
			elseif($_POST['minitype'] == 'up' || $_POST['minitype'] == 'down')
			{
				$id = $_POST['value'];
				if(!is_numeric($id))
					die("move id must be number");
				$rules = $this -> smcFunc['db_query']('', "SELECT id FROM {db_prefix}tea_ts_rules ORDER BY id");
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
							$this -> tea -> query("UPDATE {db_prefix}tea_ts_rules SET id = -1 WHERE id = ".$move);
							$this -> tea -> query("UPDATE {db_prefix}tea_ts_rules SET id = $move WHERE id = ".$id);
							$this -> tea -> query("UPDATE {db_prefix}tea_ts_rules SET id = $id WHERE id = -1");
							Break;
						}
					}
				}
				redirectexit('action=admin;area=tea;sa=ts');
			}
			if(isset($_POST['group']))
			{
				$this -> tea -> query("DELETE FROM {db_prefix}tea_ts_groups");
				foreach($_POST['group'] as $g => $v)
				{
					$this -> tea -> query("
						INSERT INTO {db_prefix}tea_ts_groups
							(id, value)
						VALUES 
							({string:id}, {int:value})",
							array('id' => $g, 'value' => '1'));
				}
			}
		// Saving?
		if (isset($_GET['save']))
		{
			if($_POST['tea_ts_addrule_group'] > -1 && !empty($_POST['tea_ts_addrule_tsg']))
			{
				if(!is_numeric($_POST['tea_ts_addrule_group']))
					die('Group must be Number');
				$l = $_POST['tea_ts_addrule_tsg'][0];
				$_POST['tea_ts_addrule_tsg'] = substr($_POST['tea_ts_addrule_tsg'], 1);
				if(!is_numeric($_POST['tea_ts_addrule_tsg']))
					die('Group must be Number');
				if($l != 's' && $l != 'c')
					die('Channel must be s or c');
				if($_POST['tea_ts_addrule_id'] == 'new')
					$this -> tea -> query('INSERT INTO {db_prefix}tea_ts_rules (smf, ts, tst, nf) VALUES ('.$_POST['tea_ts_addrule_group'].', '.$_POST['tea_ts_addrule_tsg'].', \''.$l.'\', \''.mysql_real_escape_string($_POST['tea_ts_addrule_nf']).'\')');
				else
				{
					if(!is_numeric($_POST['tea_ts_addrule_id']))
						die('ID must be Number or new');
					$this -> tea -> query("UPDATE {db_prefix}tea_ts_rules SET smf = ".$_POST['tea_ts_addrule_group'].", ts = ".$_POST['tea_ts_addrule_tsg'].", tst = '".$l."', nf = '".mysql_real_escape_string($_POST['tea_ts_addrule_nf'])."' WHERE id = ".$_POST['tea_ts_addrule_id']);
				}
			}
			unset($config_vars[100], $config_vars[101], $config_vars[102], $config_vars[103]);
			$config_vars[] = array('select', 'tea_charid', $charlist);
			$config_vars[] = array('text', 'tea_ts_info');
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=tea;sa=ts');

			loadUserSettings();
			writeLog();
		}

		$this -> context['post_url'] = $scripturl . '?action=admin;area=tea;sa=ts;save';
	//	$context['settings_title'] = $txt['tea_tea'];
	//	$context['settings_message'] = $txt['tea_settings_message'];

		prepareDBSettingContext($config_vars);
	}

	function tea_set_ts($memberID, $reg=FALSE)
	{
		if(!$this -> modSettings["tea_ts_enable"])
			Return;

	//	echo $memberID." kk ".$db_prefix;
	//	var_dump($_POST);
		if(!is_numeric($memberID))
			return;

		if($reg)
		{
			$userids = array($_POST['tea_user_id']);
			$apis = array($_POST['tea_user_api']);
		}
		else
		{
			$userids = $_POST['tea_user_id'];
			$apis = $_POST['tea_user_api'];
		}

		$rules = $this -> smcFunc['db_query']('', "SELECT id, smf, ts, tst, nf FROM {db_prefix}tea_ts_rules");
		$rules = $this -> tea -> select($rules);
			
		$usergroupssql = $this -> smcFunc['db_query']('', "SELECT id_group, additional_groups FROM {db_prefix}members WHERE id_member = ".$memberID);
		$usergroupssql = $this -> tea -> select($usergroupssql);

		if(!empty($usergroupssql))
		{
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
					$tsgs[$r[3]][$r[2]] = TRUE;
				}
			}
		}

		if(!empty($tsgs))
		{
			require_once($this -> sourcedir . '/TS3_Class/TeamSpeak3.php');

			$tslv = TeamSpeak3::LIB_VERSION;

			TeamSpeak3::init();

			try
			{
				$ts3 = TeamSpeak3::factory("serverquery://".$this -> modSettings["tea_ts_username"].":".$this -> modSettings["tea_ts_password"]."@".$this -> modSettings["tea_ts_host"].":".$this -> modSettings["tea_ts_qport"]."/?server_port=".$this -> modSettings["tea_ts_port"]."&blocking=0");

				$char = $_POST['tea_ts_char'];
				$char = html_entity_decode($char, ENT_QUOTES);
				if($_POST['method'] == 'online' && $this -> modSettings["tea_ts_method_online"])
				{
					$name = $this -> format_ts_name($memberID, $char);

					try
					{
						$client = $ts3 -> clientGetByName($name);
					}
					catch(Exception $e)
					{
						$_SESSION['tea_ts_error'][] = $e->getMessage();
						return;
					}
					$dbid = $client -> client_database_id;
					$cid = $client -> client_unique_identifier;

					$dupcheck = $this -> smcFunc['db_query']('', "SELECT id, name FROM {db_prefix}tea_ts_users WHERE tsid = '".mysql_real_escape_string($cid)."'");
					$dupcheck = $this -> tea -> select($dupcheck);
					if(!empty($dupcheck))
					{
						if($dupcheck[0][0] != $memberID)
						{
							$_SESSION['tea_ts_error'][] = 'UniqueID already attached to another forum Member';
							return;
						}
					}

					$scheck = $this -> smcFunc['db_query']('', "SELECT tsid, dbid, name FROM {db_prefix}tea_ts_users WHERE id = ".$memberID);
					$scheck = $this -> tea -> select($scheck);
					if(!empty($scheck))
					{
						if($scheck[0][0] != $cid)
						{
							try
							{
								$oldid = $ts3 -> clientGetNameByUid($scheck[0][0]);
								$oldid = (string)$oldid['cldbid'];
								$delc = $ts3 -> clientDeleteDb($oldid);
							}
							catch(Exception $e)
							{
								//maybe online?
								try
								{
									$delc = $ts3 -> clientGetByUid($scheck[0][0]);
									$delc -> kick(TeamSpeak3::KICK_SERVER, 'UniqueID Changed in SMF, Old Account Removed');
									$delc -> deleteDb();
								}
								catch(Exception $e)
								{
									$_SESSION['tea_ts_error'][] = $e->getMessage();
								}
							}
						}
					}

					$cgq = $this -> smcFunc['db_query']('', "SELECT id, value FROM {db_prefix}tea_ts_groups ORDER BY id");
					$cgq = $this -> tea -> select($cgq);
					if(!empty($cgq))
					{
						foreach($cgq as $cgqs)
							$cg[$cgqs[0]] = $cgqs[1];
					}
					$sinfo = $ts3 -> clientGetServerGroupsByDbid((int)$dbid);
					if(!empty($sinfo))
					{
						foreach($sinfo as $s => $v)
						{
							if(!isset($tsgs['s'][$s]) && (string)$v['name'] != 'Guest' && $cg['s'.$s] == 1)
								$ts3 -> serverGroupClientDel($s, $dbid);
						}
					}
					foreach($tsgs as $t => $v)
					{
						foreach($v as $tsg => $vv)
						{
							if($t == 's')
								$client -> addServerGroup($tsg);
						//	elseif($t == 'c')
						//		$ts3 -> clientGetByName($name) -> setChannelGroup($tsg);
						}
					}
				}
				elseif($_POST['method'] == 'create' && $this -> modSettings["tea_ts_method_create"])
				{
					$tsdb = $this -> modSettings["tea_ts_db_pre"];
					$dbid = $this -> modSettings["tea_ts_dbid"];
					$cid = $_POST['tsid'];
					if(strlen($cid) != 28 || $cid[27] != '=')
					{
						$_SESSION['tea_ts_error'][] = 'Invalid TeamSpeak UnqiueID';
						return;
					}
					$dupcheck = $this -> smcFunc['db_query']('', "SELECT id, name FROM {db_prefix}tea_ts_users WHERE tsid = '".mysql_real_escape_string($cid)."'");
					$dupcheck = $this -> tea -> select($dupcheck);
					if(!empty($dupcheck))
					{
						if($dupcheck[0][0] != $memberID)
						{
							$_SESSION['tea_ts_error'][] = 'UniqueID already attached to another forum Member';
							return;
						}
					}
					$this -> ts_connect();
					$check = $this -> ts_select("SELECT client_id FROM $tsdb WHERE client_unique_id = '".mysql_real_escape_string($cid)."'");
					$scheck = $this -> smcFunc['db_query']('', "SELECT tsid, dbid, name FROM {db_prefix}tea_ts_users WHERE id = ".$memberID);
					$scheck = $this -> tea -> select($scheck);
					if(!empty($scheck))
					{
						if($scheck[0][0] != $cid)
						{
							$del = $this -> ts_select("SELECT client_id FROM $tsdb WHERE client_unique_id = '".mysql_real_escape_string($scheck[0][0])."'");
							if(!empty($del))
							{
								try
								{
								//	$oldid = $ts3 -> clientGetNameByUid($scheck[0][0]);
								//	var_dump($oldid);
								//	die;
									$delc = $ts3 -> clientDeleteDb($del[0][0]);
								}
								catch(Exception $e)
								{
									//maybe online?
									try
									{
										$delc = $ts3 -> clientGetByUid($scheck[0][0]);
										$delc -> kick(TeamSpeak3::KICK_SERVER, 'UniqueID Changed in SMF, Old Account Removed');
										$delc -> deleteDb();
									}
									catch(Exception $e)
									{
										$_SESSION['tea_ts_error'][] = $e->getMessage();
									}
								}
							}
						}
					}
					if(!empty($check))
					{
						$cdbid = $check[0][0];
					}
					else
					{
						$this -> ts_query("INSERT INTO $tsdb (server_id, client_unique_id) VALUES ($dbid, '".mysql_real_escape_string($cid)."')");
						$cdbid = mysql_insert_id();
					}
					foreach($tsgs as $t => $v)
					{
						foreach($v as $tsg => $vv)
						{
							if($t == 's')
								$ts3 -> serverGroupClientAdd($tsg, $cdbid);
						//	elseif($t == 'c')
						//		$ts3 -> serverGroupClientAdd($tsg, $cdbid);
						}
					}
				}
				elseif($_POST['method'] == 'priv' && $this -> modSettings["tea_ts_method_priv"])
				{
				
				}
				else
				{
					$_SESSION['tea_ts_error'][] = 'Unknown method';
					$error = TRUE;
				}
			}
			catch(Exception $e)
			{
				$_SESSION['tea_ts_error'][] = $e->getMessage();
			//	$error = TRUE;
			}
			if(!$error)
				$this -> tea -> query("
					REPLACE INTO {db_prefix}tea_ts_users
						(id, tsid, dbid, name)
					VALUES 
					($memberID, '".mysql_real_escape_string($cid)."', '".mysql_real_escape_string($dbid)."', '".mysql_real_escape_string($char)."')");
		}
		else
		{
			$_SESSION['tea_ts_error'][] = 'Unable to Match any Groups';
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

	function format_ts_name($memID, $char)
	{
		$chars = $this -> tea -> get_all_chars($memID);

		$smfgroups = $this -> tea -> smf_groups($memID);
		if(!empty($chars))
		{
			$rules = $this -> smcFunc['db_query']('', "SELECT id, smf, ts, tst, nf FROM {db_prefix}tea_ts_rules");
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
					$name = $this -> modSettings["tea_ts_nf"];
				$name = str_replace('#at#', $charinfo[4], $name);
				$name = str_replace('#ct#', $charinfo[1], $name);
				$name = str_replace('#name#', $char, $name);
			}
		}
		if(strlen($name) > 30)
		{
			$name = substr($name, 0, 30);
		}
		return $name;
	}
}

$teats = new TEA_TS($db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings);

function template_edit_tea_ts()
{
	global $tea, $teats, $teainfo, $sourcedir, $context, $settings, $options, $scripturl, $modSettings, $txt, $smcFunc;

	echo '
		<form action="', $scripturl, '?action=profile;area=tea;sa=ts;save" method="post" accept-charset="', $context['character_set'], '" name="creator" id="creator">
			<table border="0" width="100%" cellspacing="1" cellpadding="4" align="center" class="bordercolor">
				<tr class="titlebg">
					<td height="26">
						&nbsp;<img src="', $settings['images_url'], '/icons/profile_sm.gif" alt="" border="0" align="top" />&nbsp;
						', $txt['tea_tea'], '
					</td>
				</tr><tr class="windowbg">
					<td class="smalltext" height="25" style="padding: 2ex;">
						', $txt['tea_ts_userinfo'], '
					</td>
				</tr><tr>
					<td class="windowbg2" style="padding-bottom: 2ex;">
						<table border="0" width="100%" cellpadding="3">';
	if(!$modSettings["tea_ts_enable"])
	{
		echo '<tr><td>'.$txt['tea_ts_disabled'].'</td></tr>';
	}
	else
	{
		if(isset($modSettings['tea_ts_info']))
			echo '<tr><td>'.str_replace("\n", "<br>", $modSettings['tea_ts_info']).'</td></tr>';
		echo '<tr><td colspan="2"><hr></td></tr>';
		if(!empty($_SESSION['tea_ts_error']))
		{
			foreach($_SESSION['tea_ts_error'] as $e)
				echo "<tr><td>[ERROR] ".$e."</td></tr>";
			unset($_SESSION['tea_ts_error']);
		}
		if(!empty($teainfo) || !$modSettings["tea_enable"])
		{
			$memberID = $tea -> memid;
			$tsinfo = $smcFunc['db_query']('', "SELECT tsid, dbid, name FROM {db_prefix}tea_ts_users WHERE id = ".$memberID);
			$tsinfo = $tea -> select($tsinfo);
			if(!empty($tsinfo))
			{
				$sname = $tsinfo[0][2];
				$uniqueid = $tsinfo[0][0];
				$tsname = $teats -> format_ts_name($memberID, $sname);
				echo '<tr><td>Name</td><td>'.$tsname.'</td></tr>';
				echo '<tr><td>UniqueID</td><td>'.$uniqueid.'</td></tr>';
			}
			else
			{
				echo '<tr><td>Not Registered on TS</td></tr>';
			}
			echo '<tr><td colspan="2"><hr></td></tr>';
			if(isset($modSettings["tea_ts_method_online"]) && $modSettings["tea_ts_method_online"])
				$count[] = 'online';
			if(isset($modSettings["tea_ts_method_create"]) && $modSettings["tea_ts_method_create"])
				$count[] = 'create';
			if(isset($modSettings["tea_ts_method_priv"]) && $modSettings["tea_ts_method_priv"])
				$count[] = 'priv';
			if(count($count) == 1)
				echo '<tr><td><input type="hidden" name="method" value="'.$count[0].'">';
			if(isset($modSettings["tea_ts_method_online"]) && $modSettings["tea_ts_method_online"])
			{
				if(count($count) != 1)
					echo '<tr><td><input type="radio" name="method" value="online"> Online Name Check</td></tr>';
			}
			echo '<tr><td>Main Char</td></tr>';
			echo '<tr><td>';
			if(!$modSettings["tea_enable"])
				echo '<tr><td>Name: <input type="text" name="tea_ts_char" value="">';
			elseif(!empty($teainfo))
			{
				echo '<select name="tea_ts_char">';
				foreach($teainfo as $i => $info)
				{
					foreach($info['charnames'] as $i => $info)
					{
						$name = $teats -> format_ts_name($memberID, $info[0]);
						echo '<option value="'.$info[0].'"', $info[0] == $sname ? ' SELECTED ' : '','>'.$name.'</option>';
					}
				}
				echo '</select>';
			}
			echo '</td></tr>';
			if(isset($modSettings["tea_ts_method_create"]) && $modSettings["tea_ts_method_create"])
			{
				if(count($count) != 1)
					echo '<tr><td><input type="radio" name="method" value="create"> Use TS Unique ID</td></tr>';
				echo '<tr><td>Register Using your TS Unique ID</td></tr>';
				echo '<tr><td><input type="text" name="tsid" value="'.$uniqueid.'"></td></tr>';
			}
			echo '<tr><td>';
			$txt['change_profile'] = 'Register';
			template_profile_save();
			echo '</td></tr>';
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
	}
	echo '
						</table>
					</td>
				</tr>
			</table>
		</form>';
}

?>