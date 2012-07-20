<?php

if (!defined('SMF'))
	die('Hacking attempt...');

Global $tea, $db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings;
loadLanguage('TEA');

require_once($sourcedir.'/TEAC.php');

class TEA extends TEAC
{
	var $corps;

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

		$this -> version = "1.3.0 r169";

		$permissions["tea_view_own"] = 1;
		$permissions["tea_view_any"] = 0;
		$permissions["tea_edit_own"] = 1;
		$permissions["tea_edit_any"] = 0;

		$groups = array();

		// Get all the non-postcount based groups.
		$request = $this -> smcFunc['db_query']('', "
		  SELECT ID_GROUP
		  FROM {db_prefix}membergroups
		  WHERE min_posts = {int:minposts}",
		  array('minposts' => -1));
		$request = $this -> select($request);

		// Get all the non-postcount based groups.
		$tgroupsq = $this -> smcFunc['db_query']('', "
		  SELECT id
		  FROM {db_prefix}tea_groups");
		$tgroupsq = $this -> select($tgroupsq);
		if(!empty($tgroupsq))
		{
			foreach($tgroupsq as $g)
			{
				$tgroups[$g[0]] = TRUE;
			}
		}

		// Add -1 to this array if you want to give guests the same permission
		$request[] = array(0);
		foreach($request as $g)
		{
			if(!isset($tgroups[$g[0]]))
			{
				$groups[] = $g[0];
			}
			else
			{
				unset($tgroups[$g[0]]);
			}
		}
		foreach($permissions as $p => $v)
		{
			if($v == 1)
				$psql[] = $p;
		}

		if(!empty($groups))
		{
			foreach($groups as $g)
			{
				if($g == 1)
					$v = 0;
				else
					$v = 1;
			   // Give them all their new permission.
				$request = $this -> smcFunc['db_query']('', "
					INSERT IGNORE INTO {db_prefix}permissions
						(permission, ID_GROUP, add_deny)
					VALUES
						('" . implode("', $g, 1),
						('", $psql) . "', $g, 1)");
				$request = $this -> smcFunc['db_query']('', "
					INSERT INTO {db_prefix}tea_groups
						(id, main, additional) VALUES ($g, $v, $v)");
			}
		}
		if(!empty($tgroups))
		{
			foreach($tgroups as $g => $v)
			{
				$request = $this -> smcFunc['db_query']('', "DELETE FROM {db_prefix}tea_groups WHERE id = $g");
			}
		}

		$dsets['tea_api_server'] = 'http://api.eve-online.com';
		$dsets['tea_lastpull'] = 0;
		$dsets['tea_nf'] = '[#ct#] #name#';
		$dsets['tea_tf'] = '#ct#';
		$dsets['tea_ts_nf'] = '[#ct#] #name#';
		$dsets['tea_jabber_unf'] = '#name#';
		$dsets['tea_jabber_nf'] = '#ct# #name#';
		$dsets['tea_ts_addrule_group'] = '-1';
		$dsets['tea_jabber_addrule_group'] = '-1';

		foreach($dsets as $s => $v)
		{
			if(empty($this -> settings[$s]))
			{
				$request = $this -> smcFunc['db_query']('', "
				  INSERT IGNORE INTO {db_prefix}settings
					 (variable, value)
				  VALUES
					  ('".$s."', '".$v."')");
				$this -> settings[$s] = $v;
			}
		}
		$this -> server = $this -> settings['tea_api_server'];
		$this -> undefined();
	}

	function undefined()
	{
		$ms[] = 'tea_enable';
		$ms[] = 'tea_userid';
		$ms[] = 'tea_api';
		$ms[] = 'tea_charid';
		$ms[] = 'tea_groupass_unknown';
		$ms[] = 'tea_avatar_enabled';
		$ms[] = 'tea_avatar_locked';
		$ms[] = 'tea_regreq';
		$ms[] = 'tea_ts_enable';
		$ms[] = 'tea_avatar_size';
		foreach($ms as $s)
		{
			if(!isset($this -> modSettings[$s]))
				$this -> modSettings[$s] = NULL;
		}
	}

	function update_api($apiuser=NULL, $force=FALSE)
	{
		if(!$this -> modSettings["tea_enable"])
			Return;
		$this -> file = "\n\n\nDate: ".gmdate("F jS, Y H:i", time())."\n";

		//.$this -> get_xml("", "", "")."</pre>";
		//echo "<pre>"; var_dump($this -> modSettings);die;
		$this -> alliance_list();
		$this -> get_standings();
		$this -> main($apiuser, $force);
	}

	function main($user, $force=FALSE)
	{
		if(!function_exists('curl_init'))
		{
			echo "Update Functions Require cURL extension for PHP";
			Return;
		}
		if(!empty($user))
			$this -> single($user);
		else
			$this -> all($force);
	}

	function get_standings()
	{
		$sfile = $this -> sourcedir."/../TEA/eve_standings.php";
		if(file_exists($sfile))
		{
			require($sfile);
			if($time > (time() - (60 * 60 * 24)))
			{
				$this -> blues = $blues;
				$this -> reds = $reds;
				Return;
			}
			unset($corps);
		}
		//$post = array('userID' => $this -> modSettings["tea_userid"], 'apiKey' => $this -> modSettings["tea_api"], 'characterID' => $this -> modSettings["tea_charid"]);
		$data = $this -> standings($this -> modSettings["tea_apiid"], $this -> modSettings["tea_vcode"]);
		$this -> blues = $data[0];
		$this -> reds = $data[1];
		$count = $data[2];

		if($count > 0)
		{
			$file = '<?php'."\n\n";
			$file .= '$time = '.time().';'."\n\n";
			foreach($this -> blues as $c => $a)
			{
				$file .= '$blues['.$c.'] = array(\''.str_replace("'", "\'", $a[0]).'\', '.$a[1].', '.$a[2].');'."\n";
			}
			foreach($this -> reds as $c => $a)
			{
				$file .= '$reds['.$c.'] = array(\''.str_replace("'", "\'", $a[0]).'\', '.$a[1].', '.$a[2].');'."\n";
			}
			$file .= '?>';
			$fp = fopen($sfile, 'w');
			fwrite($fp, $file);
			fclose($fp);
		}
	}

	function single($user)
	{
		if(isset($this -> modSettings["tea_groupass_unknown"]))
			$mongroups[$this -> modSettings["tea_groupass_unknown"]] = TRUE;
		else
			$mongroups[0] = TRUE;

	//	$this -> chars = array();

		$txt = $this -> txt;
		
		// prevent undefined errors
		$cr['main'] = NULL;
		$cr['additional'] = NULL;
		$ignore = FALSE;

		$cgq = $this -> smcFunc['db_query']('', "SELECT id, main, additional FROM {db_prefix}tea_groups ORDER BY id");
		$cgq = $this -> select($cgq);
		if(!empty($cgq))
		{
			foreach($cgq as $cgqs)
				$mongroups[$cgqs[0]] = array($cgqs[1], $cgqs[2]);
		}

		if(is_numeric($user))
		{
			$id = $this -> smcFunc['db_query']('', "SELECT ID_MEMBER, ID_GROUP, additional_groups FROM {db_prefix}members WHERE ID_MEMBER = {int:id}", array('id' => $user));
			$id = $this -> select($id);
		}
		if(empty($id))
		{
			$id = $this -> smcFunc['db_query']('', "SELECT ID_MEMBER, ID_GROUP, additional_groups FROM {db_prefix}members WHERE member_name = {string:user}", array('user' => $user));
			$id = $this -> select($id);
		}
		if(!empty($id))
		{
			$group = $id[0][1];
			$agroups = array();
			if(!empty($id[0][2]))
			{
				$id[0][2] = explode(',', $id[0][2]);
				foreach($id[0][2] as $g)
					$agroups[$g] = $g;
			}
			//	remove all monitored groups
			if(!empty($mongroups))
			{
				foreach($mongroups as $g => $m)
				{
					if($m[1] == 1)
						unset($agroups[$g]);
				}
			}
			$id = $id[0][0];
			if(!isset($mongroups[$group]) || $mongroups[$group][0] == 0)
			{
				$ignore = TRUE;
				$matched[0] = 'Not Monitored';
				$cr['main'] = 'Not Monitored';
			}

			$apiusers = $this -> smcFunc['db_query']('', "SELECT userid, api, status FROM {db_prefix}tea_api WHERE ID_MEMBER = {int:id}", array('id' => $id));
			$apiusers = $this -> select($apiusers);
			if(!empty($apiusers))
			{
				$mainmatch = FALSE;
				foreach($apiusers as $apiuser)
				{
					$apikey = $apiuser[1];
					$status = $apiuser[2];
					$apiuser = $apiuser[0];
					$error = FALSE;

					$matched = array('none', array());

					$chars = $this -> get_characters($apiuser, $apikey);
					if(empty($chars))
					{
						$error = $this -> get_error($this -> data);
						$this -> query("UPDATE {db_prefix}tea_api SET status = 'API Error', errorid = '".$error[0]."', error = '".$error[1]."', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND userid = ".$apiuser);
						if(($error[0] >= 500 && $error[0] < 600) || ($error[0] >= 900 && $error[0] < 1000)) // Api System is Down
							return $cr;
						else
							$chars[] = array('name' => NULL, 'charid' => NULL, 'corpname' => NULL, 'corpid' => NULL, 'ticker' => NULL, 'allianceid' => NULL, 'alliance' => NULL);
						$status = 'error';
						$error = TRUE;
					}
					if(!empty($chars))
					{
						if(!$error)
							$this -> query("UPDATE {db_prefix}tea_api SET status = 'OK', status_change = ".time()." WHERE ID_MEMBER = {int:id} AND userid = {int:userid}",
						array('id' => $id, 'userid' => $apiuser));
						// get main rules
						$rules = $this -> smcFunc['db_query']('', "SELECT ruleid, `group`, andor FROM {db_prefix}tea_rules WHERE main = 1 AND enabled = 1 ORDER BY ruleid");
						$rules = $this -> select($rules);
						if(!empty($rules) && !$ignore)
						{
							foreach($rules as $rule)
							{
								$andor = $rule[2];
								foreach($chars as $char)
								{
									if(empty($this -> matchedchar))
										$this -> matchedchar = $char; // just to make sure we get 1

									$conditions = $this -> smcFunc['db_query']('', "SELECT type, value, extra, isisnt FROM {db_prefix}tea_conditions WHERE ruleid = {int:id}",
									array('id' => $rule[0]));
									$conditions = $this -> select($conditions);
									if(!empty($conditions))
									{
										$match = TRUE;
										foreach($conditions as $cond)
										{
											//echo "<pre>"; var_dump($cond);die;
										//	$this -> chars[] = $char;
											Switch($cond[0])
											{
												case 'corp':
													if($cond[3] == 'is' && $char['corpid'] == $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && $char['corpid'] != $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												case 'alliance':
													if($cond[3] == 'is' && $char['allianceid'] == $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && $char['allianceid'] != $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												case 'blue':
													if($cond[3] == 'is' && (isset($this -> blues[$char['corpid']]) || isset($this -> blues[$char['allianceid']])))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && (!isset($this -> blues[$char['corpid']]) && !isset($this -> blues[$char['allianceid']])))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												case 'red':
													if($cond[3] == 'is' && (isset($this -> reds[$char['corpid']]) || isset($this -> reds[$char['allianceid']])))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && (!isset($this -> reds[$char['corpid']]) && !isset($this -> reds[$char['allianceid']])))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												case 'error':
													if($cond[3] == 'is' && $error)
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													if($cond[3] == 'isnt' && !$error)
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												case 'skill':
													if($cond[3] == 'is')
													{
														$skills = $this -> skill_list($apiuser, $apikey, $char['charid']);
														if(strstr($cond[1], '%'))
														{
															$cond[1] = str_replace('%', '(.+)', $cond[1]);
															foreach($skills as $skill => $level)
															{
																if(preg_match("/".$cond[1]."/i", $skill) && $level >= $cond[2])
																{
																	if($andor == 'OR')
																		Break 3;
																	Break 2;
																}
															}
														}
														if(isset($skills[strtolower($cond[1])]) && $skills[strtolower($cond[1])] >= $cond[2])
														{
															if($andor == 'OR')
																Break 2;
															Break;
														}
														else
														{
															$match = FALSE;
															Break 2;
														}
													}
													elseif($cond[3] == 'isnt')
													{
														$skills = $this -> skill_list($apiuser, $apikey, $char['charid']);
														if(strstr($cond[1], '%'))
														{
															$cond[1] = str_replace('%', '(.+)', $cond[1]);
															if(!empty($skills))
															{
																foreach($skills as $skill => $level)
																{
																	if(preg_match("/".$cond[1]."/i", $skill) && $level >= $cond[2])
																	{
																		$skillmatch = TRUE;
																	}
																}
															}
															if(!$skillmatch)
															{
																if($andor == 'OR')
																	Break 3;
																Break 2;
															}
														}
														if(!(isset($skills[strtolower($cond[1])]) && $skills[strtolower($cond[1])] >= $cond[2]))
														{
															if($andor == 'OR')
																Break 2;
															Break;
														}
														else
														{
															$match = FALSE;
															Break 2;
														}
													}
												case 'role':
													$roles = $this -> roles($apiuser, $apikey, $char['charid']);
													if($cond[3] == 'is' && isset($roles['role'.strtolower($cond[1])]))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && !isset($roles['role'.strtolower($cond[1])]))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												case 'title':
													$titles = $this -> titles($apiuser, $apikey, $char['charid']);
													if($cond[3] == 'is' && isset($titles[strtolower($cond[1])]))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && !isset($titles[strtolower($cond[1])]))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												case 'militia':
													$militia = $this -> militia($apiuser, $apikey, $char['charid']);
													if($cond[3] == 'is' && $militia == $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && $militia != $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												case 'valid':
													if($cond[3] == 'is')
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$match = FALSE;
														Break 2;
													}
												Default:
													$match = FALSE;
													Break 2;
											}
										}
										if($match)
										{
											$this -> matchedchar = $char;
											$this -> query("UPDATE {db_prefix}members SET ID_GROUP = {int:idg} WHERE ID_MEMBER = {int:id}",
											array('idg' => $rule[1], 'id' => $id));
											if(!$error)
												$this -> query("UPDATE {db_prefix}tea_api SET status = 'red', status_change = {int:time} WHERE ID_MEMBER = {int:id} AND status = 'OK'",
											array('time' => time(), 'id' => $id));
											$matched[0] = $rule[0];
											$cr['main'] = $rule[0];
											$mainmatch = TRUE;
											Break 2;
										}
									}
								}
							}
						}
						// get additional
						$rules = $this -> smcFunc['db_query']('', "SELECT ruleid, `group`, andor FROM {db_prefix}tea_rules WHERE main = 0 AND enabled = 1 ORDER BY ruleid");
						$rules = $this -> select($rules);
						if(!empty($rules))
						{
							foreach($rules as $rule)
							{
								//if(isset($agroups[$rule[1]])) // group already assigned no point checking
								//	Break;
								$andor = $rule[2];
								foreach($chars as $char)
								{
									$conditions = $this -> smcFunc['db_query']('', "SELECT type, value, extra, isisnt FROM {db_prefix}tea_conditions WHERE ruleid = {int:ruleid}", array('ruleid' => $rule[0]));
									$conditions = $this -> select($conditions);
									if(!empty($conditions))
									{
										$amatch = TRUE;
										foreach($conditions as $cond)
										{
										//	$this -> chars[] = $char;
											Switch($cond[0])
											{
												case 'corp':
													if($cond[3] == 'is' && $char['corpid'] == $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && $char['corpid'] != $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												case 'alliance':
													if($cond[3] == 'is' && $char['allianceid'] == $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && $char['allianceid'] != $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												case 'blue':
													if($cond[3] == 'is' && (isset($this -> blues[$char['corpid']]) || isset($this -> blues[$char['allianceid']])))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && (!isset($this -> blues[$char['corpid']]) && !isset($this -> blues[$char['allianceid']])))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												case 'red':
													if($cond[3] == 'is' && (isset($this -> reds[$char['corpid']]) || isset($this -> reds[$char['allianceid']])))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && (!isset($this -> reds[$char['corpid']]) && !isset($this -> reds[$char['allianceid']])))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												case 'error':
													if($cond[3] == 'is' && $error)
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													if($cond[3] == 'isnt' && !$error)
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												case 'skill':
													//	echo "<pre>"; var_dump($cond);die;
													if($cond[3] == 'is')
													{
														$skills = $this -> skill_list($apiuser, $apikey, $char['charid']);
														if(strstr($cond[1], '%'))
														{
															$cond[1] = str_replace('%', '(.+)', $cond[1]);
															if(!empty($skills))
															{
																foreach($skills as $skill => $level)
																{
																	if(preg_match("/".$cond[1]."/i", $skill) && $level >= $cond[2])
																	{
																		if($andor == 'OR')
																			Break 3;
																		Break 2;
																	}
																}
															}
														}
														if(isset($skills[strtolower($cond[1])]) && $skills[strtolower($cond[1])] >= $cond[2])
														{
															if($andor == 'OR')
																Break 2;
															Break;
														}
														else
														{
															$amatch = FALSE;
															Break 2;
														}
													}
													elseif($cond[3] == 'isnt')
													{
														$skills = $this -> skill_list($apiuser, $apikey, $char['charid']);
														if(strstr($cond[1], '%'))
														{
															$cond[1] = str_replace('%', '(.+)', $cond[1]);
															foreach($skills as $skill => $level)
															{
																if(preg_match("/".$cond[1]."/i", $skill) && $level >= $cond[2])
																{
																	$skillmatch = TRUE;
																}
															}
															if(!$skillmatch)
															{
																if($andor == 'OR')
																	Break 3;
																Break 2;
															}
														}
														if(!(isset($skills[strtolower($cond[1])]) && $skills[strtolower($cond[1])] >= $cond[2]))
														{
															if($andor == 'OR')
																Break 2;
															Break;
														}
														else
														{
															$amatch = FALSE;
															Break 2;
														}
													}
												case 'role':
													$roles = $this -> roles($apiuser, $apikey, $char['charid']);
													if($cond[3] == 'is' && isset($roles['role'.strtolower($cond[1])]))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && !isset($roles['role'.strtolower($cond[1])]))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												case 'title':
													$titles = $this -> titles($apiuser, $apikey, $char['charid']);
													if($cond[3] == 'is' && isset($titles[strtolower($cond[1])]))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && !isset($titles[strtolower($cond[1])]))
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												case 'militia':
													$militia = $this -> militia($apiuser, $apikey, $char['charid']);
													if($cond[3] == 'is' && $militia == $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													elseif($cond[3] == 'isnt' && $militia != $cond[1])
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												case 'valid':
													if($cond[3] == 'is')
													{
														if($andor == 'OR')
															Break 2;
														Break;
													}
													else
													{
														$amatch = FALSE;
														Break 2;
													}
												Default:
													$amatch = FALSE;
													Break 2;
											}
										}
										if($amatch)
										{
											$agroups[$rule[1]] = $rule[1];
											$matched[1][] = $rule[0];
											Break;
										}
									}
								}
							}
						}
						$cr['additional'] = $matched[1];
						$matched[1] = implode(',', $matched[1]);
						$matched = implode(';', $matched);
						if(!$error)
							$this -> query("UPDATE {db_prefix}tea_api SET status = 'checked', matched = '".$matched."', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND userid = ".$apiuser);
						else
							$this -> query("UPDATE {db_prefix}tea_api SET matched = '".$matched."', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND userid = ".$apiuser);
					}
				}
				if(!$mainmatch && !$ignore)
				{
					// doesnt match any rule, remove group
					$this -> query("UPDATE {db_prefix}members SET ID_GROUP = 0 WHERE ID_MEMBER = {int:id}",
					array('id' => $id));
					if(!$error)
						$this -> query("UPDATE {db_prefix}tea_api SET status = 'nomatch', status_change = {int:time} WHERE ID_MEMBER = {int:id} AND status = 'OK'",
						array('time' => time(), 'id' => $id));
					$cr['main'] = $txt['tea_nomatch'];
				}
			}
			else
			{	// no api on account, if monitored group change to unknown group
				if(!$ignore)
				{
					if($this -> modSettings["tea_groupass_unknown"])
						$group = $this -> modSettings["tea_groupass_unknown"];
					else
						$group = 0;
					$this -> query("UPDATE {db_prefix}members SET ID_GROUP = {int:group} WHERE ID_MEMBER = {int:id}", array('id' => $id, 'group' => $group));
					$cr['main'] = $txt['tea_noapi'];
				}
			}
			$agroups = implode(',', $agroups);
			// change additional groups
			$this -> query("UPDATE {db_prefix}members SET additional_groups = '".$agroups."' WHERE ID_MEMBER = {int:id}", array('id' => $id));
			return $cr;
			//$this -> query("UPDATE {db_prefix}members SET ID_GROUP = ".$rule[1]." WHERE ID_MEMBER = ".$id);
			//$this -> query("UPDATE {db_prefix}tea_api SET status = 'red', status_change = ".time()." WHERE ID_MEMBER = ".$id." AND status = 'OK'");
		}
	}

	function get_characters($userid, $api)
	{
		$charlist = NULL;
		$chars = $this -> get_api_characters($userid, $api);
		if(!empty($chars))
		{
			$charlist = array();
			foreach($chars as $char)
			{
				$charlist[] = $char;
				$this -> chars[$char['name']] = $char;
				$this -> query("
					REPLACE INTO {db_prefix}tea_characters
						(userid, charid, name, corpid, corp, corp_ticker, allianceid, alliance, alliance_ticker)
					VALUES 
					('" . mysql_real_escape_string($userid) . "', '" . mysql_real_escape_string($char['charid']) . "', '" . mysql_real_escape_string($char['name']) . "', '" . mysql_real_escape_string($char['corpid']) . "', '" . mysql_real_escape_string($char['corpname']) . "', '" . mysql_real_escape_string($char['ticker']) . "', '".$char['allianceid']."', '" . mysql_real_escape_string($char['alliance']) . "', '" . mysql_real_escape_string($char['aticker']) . "')");
			}
		}
		Return $charlist;
	}

	function get_char_list($id=FALSE, $format=FALSE)
	{
		$ID_MEMBER = $this -> user_info['id'];
		// Did we get the user by name...
		if (isset($_REQUEST['user']))
			$memberResult = loadMemberData($_REQUEST['user'], true, 'profile');
		// ... or by ID_MEMBER?
		elseif (!empty($_REQUEST['u']))
			$memberResult = loadMemberData((int) $_REQUEST['u'], false, 'profile');
		// If it was just ?action=profile, edit your own profile.
		else
			$memberResult = loadMemberData($ID_MEMBER, false, 'profile');
		$memID = $memberResult[0];

		$user = $this -> smcFunc['db_query']('', "SELECT userid FROM {db_prefix}tea_api WHERE ID_MEMBER = {int:id}", array('id' => $memID));
		$user = $this -> select($user);
		if(!empty($user))
		{
			foreach($user as $acc)
			{
				$chars = $this -> get_acc_chars($acc[0]);
				if(!empty($chars))
				{
					foreach($chars as $cid => $char)
					{
						if($format)
						{
						//	$ticker = $this -> corp_info($corp);
							if(!empty($char[1]))
								$char[0] = $this -> format_name($memID, $char[0]);
						}

						if($id)
							$charlist[$cid] = $char[0];
						else
							$charlist[$char[0]] = $char[0];
					}
				}
			}
		}
		Return $charlist;
	}

	function get_all_chars($memID=FALSE)
	{
		if(!$memID)
		{
			$ID_MEMBER = $this -> user_info['id'];
			// Did we get the user by name...
			if (isset($_REQUEST['user']))
				$memberResult = loadMemberData($_REQUEST['user'], true, 'profile');
			// ... or by ID_MEMBER?
			elseif (!empty($_REQUEST['u']))
				$memberResult = loadMemberData((int) $_REQUEST['u'], false, 'profile');
			// If it was just ?action=profile, edit your own profile.
			else
				$memberResult = loadMemberData($ID_MEMBER, false, 'profile');
			$memID = $memberResult[0];
		}
		$user = $this -> smcFunc['db_query']('', "SELECT userid FROM {db_prefix}tea_api WHERE ID_MEMBER = {int:id}", array('id' => $memID));
		$user = $this -> select($user);
		if(!empty($user))
		{
			$all = array();
			foreach($user as $acc)
			{
				$chars = $this -> get_acc_chars($acc[0]);
				foreach($chars as $i => $c)
					$all[$i] = $c;
			}
		}
		return $all;
	}

	function get_acc_chars($userid)
	{
		$charlist = NULL;
		$chars = $this -> smcFunc['db_query']('', "SELECT charid, name, corp_ticker, corp, alliance, alliance_ticker FROM {db_prefix}tea_characters WHERE userid = {int:id}", array('id' => $userid));
		$chars = $this -> select($chars);
		if(!empty($chars))
		{
			foreach($chars as $char)
			{
				$charlist[$char[0]] = array($char[1], $char[2], $char[3], $char[4], $char[5]);
			}
		}
		Return $charlist;
	}

	function skill_list($id, $api, $charid)
	{
		require_once($this -> sourcedir.'/TEA_SkillDump.php');
		return $this -> skills($id, $api, $charid);
	}

	function all($force=FALSE)
	{
		//if($apiecho)
		//	echo "checking all...\n<br>";
		$next = $this -> modSettings['tea_nextpull'];
		if($next > time() && $force === FALSE)
			Return;
		$time = time() - 3600;
		$this -> smcFunc['db_query']('', "DELETE FROM {db_prefix}tea_cache WHERE time < ".$time);
		if($force !== FALSE && $force !== NULL)
			$lastid = $force;
		else
			$lastid = $this -> modSettings['tea_lastpull'];
		$limit = time() + 25;
		$users = $this -> smcFunc['db_query']('', "SELECT id_member, member_name, ID_GROUP FROM {db_prefix}members WHERE id_member > $lastid Order by id_member");
		$users = $this -> select($users);
		if(!empty($users))
		{
			$this -> log .= '<table border=1><tr><td><b>Name</b></td><td><b>Main Group</b></td><td><b>Additional Groups</b></td></tr>';
			foreach($users as $user)
			{
				$this -> log .= '<tr><td>'.$user[1].'</td>';
				$cr = $this -> single($user[0]);
				if(is_array($cr['additional']))
				{
					foreach($cr['additional'] as &$c)
					{
						if(is_numeric($c))
							$c = $this -> get_rule_name($c);
					}
					$cr['additional'] = implode(', ', $cr['additional']);
				}
				if(is_numeric($cr['main']))
					$cr['main'] = $this -> get_rule_name($cr['main']);
				$this -> log .= '<td>'.$cr['main'].'</td><td>'.$cr['additional'].'</td></tr>';
				if(time() > $limit)
				{
					$blastid = $user[0];
					Break;
				}
			}
			$this -> log .= '</table>';
		}
		
		if(!empty($blastid)) // maxed user pull record last id ready for next
		{
			$next = time() + 45;
			$this -> lastid = $blastid;
			$lastid = $blastid;
		}
		else
		{
			if(!empty($users))
				$this -> lastid = $users[count($users)-1][0];
			else
				$this -> lastid = $lastid;
			$lastid = 0;
			$next = time() + 3600;
		}
		$this -> smcFunc['db_query']('', "
		  REPLACE INTO {db_prefix}settings
			 (variable, value)
		  VALUES
			  ('tea_lastpull', $lastid)");
		$this -> smcFunc['db_query']('', "
		  REPLACE INTO {db_prefix}settings
			 (variable, value)
		  VALUES
			  ('tea_nextpull', $next)");
	//	$fp = fopen("api.log", 'a');
	//	fwrite($fp, $this -> file);
	//	fclose($fp);
	}

	function get_rule_name($id)
	{
		//$id = (int)$id;
		$name = $this -> smcFunc['db_query']('', "SELECT name FROM {db_prefix}tea_rules WHERE ruleid = {int:id}", array('id' => $id));
		$name = $this -> select($name);
		if(!empty($name))
			Return $name[0][0];
	}

	function get_cache($url, $post)
	{
		$time = time() - 3600;
		$db = $this -> smcFunc['db_query']('', "SELECT xml FROM {db_prefix}tea_cache WHERE address = '".mysql_real_escape_string($url)."' AND post = '".mysql_real_escape_string($post)."' AND time > ".$time);
		$db = $this -> select($db);
		if(!empty($db))
			return $db[0][0];
		else
			Return FALSE;
	}

	function set_cache($url, $post, $xml)
	{
		$this -> query("
			REPLACE INTO {db_prefix}tea_cache
				(address, post, time, xml)
			VALUES
				('$url', '".mysql_real_escape_string($post)."', ".time().", '".mysql_real_escape_string($xml)."')");
	}

	function select($result, $result_form=MYSQL_NUM, $error=TRUE)//MYSQL_ASSOC = field names
	{
		$data = "";
	//	$result = mysql_query($sql);
	//	$result = $this -> smcFunc['db_query']('', $sql, $params);
		if (!$result)
		{
			echo $sql;
			if($error)
				echo "<BR>".$this -> smcFunc['db_error']."<BR>";
			return false;
		}

		if (empty($result))
		{
			return false;
		}

		if($result_form == MYSQL_ASSOC)
		{
			while ($row = $this -> smcFunc['db_fetch_assoc']($result))
			{
				$data[] = $row;
			}
		}
		else
		{
			while ($row = $this -> smcFunc['db_fetch_row']($result))
			{
				$data[] = $row;
			}
		}

		$this -> smcFunc['db_free_result']($result);
		return $data;
	}

	function selectold($sql, $params=NULL, $result_form=MYSQL_NUM, $error=TRUE)//MYSQL_ASSOC = field names
	{
		$data = "";
	//	$result = mysql_query($sql);
		$result = $this -> smcFunc['db_query']('', $sql, $params);
		if (!$result)
		{
			echo $sql;
			if($error)
				echo "<BR>".$this -> smcFunc['db_error']."<BR>";
			return false;
		}

		if (empty($result))
		{
			return false;
		}

		if($result_form == MYSQL_ASSOC)
		{
			while ($row = $this -> smcFunc['db_fetch_assoc']($result))
			{
				$data[] = $row;
			}
		}
		else
		{
			while ($row = $this -> smcFunc['db_fetch_row']($result))
			{
				$data[] = $row;
			}
		}

		$this -> smcFunc['db_free_result']($result);
		return $data;
	}

	function query($sql, $params=NULL)
	{
		$return = $this -> smcFunc['db_query']('', $sql, $params);

		if (!$return)
		{
			echo $sql;
			echo "<BR>".$this -> smcFunc['db_error']."<BR>";
			return false;
		}
		else
		{
			return true;
		}
	}

	function xmlparse($xml, $tag)
	{
		$tmp = explode("<" . $tag . ">", $xml);
		if(isset($tmp[1]))
			$tmp = explode("</" . $tag . ">", $tmp[1]);
		else
			return NULL;
		return $tmp[0];
	}

	function rowset($xml, $tag)
	{
		$tmp = explode('<rowset name="'.$tag.'" key="toID" columns="toID,toName,standing">', $xml);
		if(!empty($tmp[1]))
			$tmp = explode("</rowset>", $tmp[1]);
		return $tmp[0];
	}

	function parse($xml)
	{
		$chars = NULL;
		$xml = explode("<row ", $xml);
		unset($xml[0]);
		if(!empty($xml))
		{
			foreach($xml as $char)
			{
				$char = explode('name="', $char, 2);
				$char = explode('" characterID="', $char[1], 2);
				$name = $char[0];
				$char = explode('" corporationName="', $char[1], 2);
				$charid = $char[0];
				$char = explode('" corporationID="', $char[1], 2);
				$corpname = $char[0];
				$char = explode('" />', $char[1], 2);
				$corpid = $char[0];
				$chars[] = array('name' => $name, 'charid' => $charid, 'corpname' => $corpname, 'corpid' => $corpid);
			}
		}
		return $chars;
	}

	function alliance_list($update=TRUE)
	{
		$sfile = $this -> sourcedir."/../TEA/eve_corplist.php";
		if(file_exists($sfile))
		{
			require($sfile);
			if(count($corps) > 5 && $time > (time() - (60 * 60 * 24)))
			{
				$this -> corps = $corps;
				Return $time;
			}
			unset($corps);
		}
		if(!$update)
			Return;
		$data = $this -> get_xml('alliances');
		//$data = $this -> rowset2($data);
		$data = explode("<row name=\"", $data);
		unset($data[0]);
		foreach($data as $a)
		{
			$a = explode("</rowset>", $a, 2);
			$a = explode('" shortName="', $a[0], 2);
			$name = $a[0];
			$a = explode('" allianceID="', $a[1], 2);
			$tag = $a[0];
			$a = explode('" executorCorpID="', $a[1], 2);
			$id = $a[0];
			$a = explode('<row corporationID="', $a[1]);
			unset($a[0]);
			foreach($a as $corp)
			{
				$corp = explode('" startDate="', $corp, 2);
				$corps[$corp[0]] = $id;
			}
			$this -> atags[$id] = $tag;
		}
		if(count($corps) > 5)
		{
			$time = time();
			$file = '<?php'."\n\n";
			$file .= '$time = '.$time.';'."\n\n";
			foreach($corps as $c => $a)
			{
				$file .= '$corps['.$c.'] = '.$a.';'."\n";
			}
			foreach($this -> atags as $id => $tag)
			{
				$file .= '$this -> atags['.$id.'] = "'.$tag.'";'."\n";
			}
			$file .= '?>';
			$fp = fopen($sfile, 'w');
			fwrite($fp, $file);
			fclose($fp);
			$this -> corps = $corps;
		}
		Return $time;
	}

	function rowset2($xml)
	{
		$tmp = explode('<rowset name="alliances" key="allianceID" columns="name,shortName,allianceID,executorCorpID,memberCount,startDate">', $xml, 2);
		return $tmp[1];
	}

	function Settings($scripturl)
	{
		$this -> context[$this -> context['admin_menu_name']]['tab_data'] = array(
			'title' => $this -> txt['tea_tea'],
		//	'help' => 'featuresettings',
			'description' => $this -> txt['tea_settings_message'],
			'tabs' => array(
				'info' => array(
				),
				'settings' => array(
				),
				'rules' => array(
	//				'description' => $this -> txt['signature_settings_desc'],
				),
				'ts' => array(
				),
				'jabber' => array(
				),
				'checks' => array(
				),
			),
		);

		if(isset($_GET['sa']) && strtolower($_GET['sa']) == "rules")
			$this -> settings_rules($scripturl);
		elseif(isset($_GET['sa']) && strtolower($_GET['sa']) == "ts")
			$this -> ts -> settings($scripturl);
		elseif(isset($_GET['sa']) && strtolower($_GET['sa']) == "jabber")
			$this -> jabber -> settings($scripturl);
		elseif(isset($_GET['sa']) && strtolower($_GET['sa']) == "checks")
			$this -> settings_checks($scripturl);
		elseif(isset($_GET['sa']) && strtolower($_GET['sa']) == "settings")
			$this -> settings_settings($scripturl);
		else
			$this -> settings_info($scripturl);
	}

	function settings_info($scripturl)
	{
		$info = array('smfv' => $this -> modSettings['smfVersion'],
					'teav' => $this -> version,
					'url' => $_SERVER['HTTP_HOST']);
		$latestv = $this -> get_site('http://tea.temar.me/version.php', $info);
		$latestv = explode("#", $latestv, 2);
		if(version_compare($latestv[0], $this -> version) > 0)
		{
			$vdown = explode('.', $latestv[0], 3);
			$vdown = implode('_', $vdown);
			$vdown = 'http://temars-eve-api.googlecode.com/files/TEA_'.$vdown.'.zip';
			$vdown = '<form action="'.$scripturl.'?action=admin;area=packages;get;sa=download;byurl" method="post" accept-charset="ISO-8859-1" name="Release Download">
			<input type="hidden" name="package" value="'.$vdown.'" />
			<input type="hidden" name="filename" value="" />
			<input type="submit" value="Download" /></form>';
		}
		if(version_compare($latestv[1], $this -> version) > 0)
		{
			$dvdown = explode('.', $latestv[1], 4);
			$fname = 'TEA-trunk.r'.$dvdown[3].'.tar.gz';
			$dvdown = 'http://tea.temar.me/svn/dl.php?repname=TEA&path=%2Ftrunk%2F&isdir=1&rev='.$dvdown[3].'&peg='.$dvdown[3];
			$dvdown = '<form action="'.$scripturl.'?action=admin;area=packages;get;sa=download;byurl;'.$this -> context['session_var'].'='.$this -> context['session_id'].'" method="post" accept-charset="ISO-8859-1" name="Dev Download">
			<input type="hidden" name="package" value="'.$dvdown.'" />
			<input type="hidden" name="filename" value="'.$fname.'" />
			<input type="submit" value="Download" /></form>';
		}
		$config_vars = array(
			'</form><dt>Your '.$this -> txt['tea_version'].': '.$this -> version.'</dt>',
			'<dt>Latest Released '.$this -> txt['tea_version'].': '.$latestv[0].$vdown.'</dt>',
			'<dt>Latest Dev '.$this -> txt['tea_version'].': '.$latestv[1].$dvdown.'</dt>',
			'',
			'<dt>Important Update Notes:</dt>',
			'<dt>1.3<br>- Cron files are now in forum_folder/TEA/</dt>',
			'<dt>1.3<br>- New API Supported, blocking new OLD apis being added but current stored still work</dt>',
			'<dt></dt>',
			'<dt>1.2.1</dt>',
			'<dt>- Custom Name Format options Changed, Check Settings Page</dt>',
			'<dt>- SMF Group Monitor List System Changed, Check Rules Page</dt>',
		);

		$this -> context['settings_save_dont_show'] = TRUE;

		prepareDBSettingContext($config_vars);
	}

	function settings_settings($scripturl)
	{
		$atime = $this -> alliance_list(FALSE);
		if($atime)
			$atime = gmdate("G:i D d M y", $atime).' (GMT)';
		else
			$atime = 'Never';
		if (isset($_GET['save']))
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
		$chars = $this -> get_characters($userid, $api);

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
		$blues = NULL;
		$reds = NULL;
		$time = FALSE;
		$file = $this -> sourcedir."/../TEA/eve_standings.php";
		if(file_exists($file))
			require($file);
		if($time)
			$time = gmdate("G:i D d M y", $time).' (GMT)';
		else
			$time = 'Never';
		$groups = $this -> MemberGroups();
		$options = '';
		if(!empty($charlist))
		{
			foreach($charlist as $i => $c)
			{
				$options .= '<option value="'.$i.'"';
				if($this -> modSettings["tea_charid"] == $i)
					$options .= ' selected="selected"';
				$options .= '>'.$c.'</option>
				';
			}
		}
		$config_vars = array(
			'</form>
			<form action="'.$scripturl.'?action=admin;area=tea;sa=settings;save" method="post" accept-charset="ISO-8859-1" name="tea_settings">',
			'<dt>'.$this -> txt['tea_version'].': '.$this -> version.'</dt>',
			'',
				// enable?
				array('check', 'tea_enable'),
			'',
			'<dt>'.$this -> txt['tea_settings_message'].'</dt>',
				// api info
				array('int', 'tea_apiid', 10),
				array('text', 'tea_vcode', 64),
			'<dt>'.$this -> txt['tea_standings_updated'].': '.$time.'</dt>',
			'<dt>'.$this -> txt['tea_standings_contains'].': '.count($blues).' '.$this -> txt['tea_standings_blue'].', '.count($reds).' '.$this -> txt['tea_standings_red'].'</dt>',
			'<dt>'.$this -> txt['tea_corpl_updated'].': '.$atime.'</dt>',
			'<dt>'.$this -> txt['tea_corpl_contains'].': '.count($this -> corps).'</dt>',
			'',
				array('check', 'tea_regreq'),
				array('check', 'tea_usecharname'),
				array('check', 'tea_avatar_enabled'),
				array('check', 'tea_avatar_locked'),
				array('select', 'tea_avatar_size', array(32 => '32', 64 => '64', 128 => '128', 256 => '256')),
			//	array('int', 'tea_corpid', 10),
			//	array('int', 'tea_allianceid', 10),
			//	array('check', 'tea_useapiabove'),
				array('check', 'tea_custom_name'),
				array('text', 'tea_nf', 15),
				array('check', 'tea_custom_title'),
				array('text', 'tea_tf', 15),
			'',
			'<dt>'.$this -> txt['tea_group_settings'].'</dt>',
			//	array('select', 'tea_groupass_red', $groups),
			//	array('select', 'tea_groupass_corp', $groups),
			//	array('select', 'tea_groupass_alliance', $groups),
			//	array('select', 'tea_groupass_blue', $groups),
			//	array('select', 'tea_groupass_neut', $groups),
				array('select', 'tea_groupass_unknown', $groups),
			'',
				array('text', 'tea_api_server', 40),
				// Who's online.
		//		array('check', 'who_enabled'),
		);

		// Saving?
		if (isset($_GET['save']))
		{
		//	if(isset($_POST['tea_useapiabove']))
		//	{
		//		$_POST['tea_corpid'] = $corp;
		//		$_POST['tea_allianceid'] = $alliance;
		//		unset($_POST['tea_useapiabove']);
		//	}
			$config_vars[] = array('select', 'tea_charid', $charlist);
			saveDBSettings($config_vars);
			redirectexit('action=admin;area=tea;sa=settings');

			loadUserSettings();
			writeLog();
		}

		$this -> context['post_url'] = $scripturl . '?action=admin;area=tea;save';
	//	$context['settings_title'] = $txt['tea_tea'];
	//	$context['settings_message'] = $txt['tea_settings_message'];

		prepareDBSettingContext($config_vars);
	}

	function settings_rules($scripturl)
	{
		$types['corp'] = $this -> txt['tea_corp'];
		$types['alliance'] = $this -> txt['tea_alliance'];
		$types['blue'] = $this -> txt['tea_blue'];
		$types['red'] = $this -> txt['tea_red'];
		$types['neut'] = $this -> txt['tea_neut'];
		$types['error'] = $this -> txt['tea_error'];
		$types['valid'] = $this -> txt['tea_valid'];
		$types['skill'] = $this -> txt['tea_skill'];
		$types['role'] = $this -> txt['tea_role'];
		$types['title'] = $this -> txt['tea_title'];
		$types['militia'] = $this -> txt['tea_militia'];
		$groups = $this -> MemberGroups();
		if(!empty($_POST))
		{
		//	echo '<pre>'; var_dump($_POST);die;
			if(isset($_POST['mong']))
			{
				foreach($_POST as $g => $v)
				{
					$g = explode("_", $g, 2);
					if($g[0] == "main")
						$gs[$g[1]][0] = 1;
					elseif($g[0] == "adit")
						$gs[$g[1]][1] = 1;
				}
				$this -> query("UPDATE {db_prefix}tea_groups SET main = 0, additional = 0");
				foreach($gs as $g => $v)
				{
					if($v[0] != 1) $v[0] = 0;
					if($v[1] != 1) $v[1] = 0;
					$this -> query("UPDATE {db_prefix}tea_groups SET main = {int:main}, additional = {int:adit} WHERE id = {int:id}", array('id' => $g, 'main' => $v[0], 'adit' => $v[1]));
		//			$this -> query("
		//				INSERT INTO {db_prefix}tea_groups
		//					(id, main, additional)
		//				VALUES 
		//					({int:id}, {int:main}, {int:adit})",
		//					array('id' => $g, 'main' => $v[0], 'adit' => $v[1]));
				}
			}
			elseif(isset($_POST['enr']))
			{
				$this -> query("UPDATE {db_prefix}tea_rules SET enabled = 0");
				foreach($_POST as $rule => $v)
				{
					$rule = explode("_", $rule);
					if(!empty($rule[1]) && is_numeric($rule[1]) && $v == 1)
					{
						$this -> query("UPDATE {db_prefix}tea_rules SET enabled = 1 WHERE ruleid = ".$rule[1]);
					}
				}
			}
			elseif(isset($_POST['minitype']))
			{
				if($_POST['minitype'] == 'delrule')
				{
					if(!is_numeric($_POST['value']))
						die("delete value must be number");
					$this -> query("DELETE FROM {db_prefix}tea_rules WHERE ruleid = ".$_POST['value']);
					$this -> query("DELETE FROM {db_prefix}tea_conditions WHERE ruleid = ".$_POST['value']);
				}
				elseif($_POST['minitype'] == 'up' || $_POST['minitype'] == 'down')
				{
					$id = $_POST['value'];
					if(!is_numeric($id))
						die("move id must be number");
					$rules = $this -> smcFunc['db_query']('', "SELECT ruleid, main FROM {db_prefix}tea_rules ORDER BY ruleid");
					$rules = $this -> select($rules);
					if(!empty($rules))
					{
						foreach($rules as $rule)
						{
							$rl[$rule[1]][$rule[0]] = $rule[0];
							if($rule[0] == $id)
								$main = $rule[1];
						}
						if(isset($main))
						{
							$rules = $rl[$main];
							sort($rules);
							foreach($rules as $i => $rule)
							{
								if($rule == $id)
								{
									if($_POST['minitype'] == 'up')
										$move = $rules[$i-1];
									elseif($_POST['minitype'] == 'down')
										$move = $rules[$i+1];
									$this -> query("UPDATE {db_prefix}tea_rules SET ruleid = -1 WHERE ruleid = ".$move);
									$this -> query("UPDATE {db_prefix}tea_conditions SET ruleid = -1 WHERE ruleid = ".$move);
									$this -> query("UPDATE {db_prefix}tea_rules SET ruleid = $move WHERE ruleid = ".$id);
									$this -> query("UPDATE {db_prefix}tea_conditions SET ruleid = $move WHERE ruleid = ".$id);
									$this -> query("UPDATE {db_prefix}tea_rules SET ruleid = $id WHERE ruleid = -1");
									$this -> query("UPDATE {db_prefix}tea_conditions SET ruleid = $id WHERE ruleid = -1");
									Break;
								}
							}
						}
					}
				}
				else
				{
					die("Unknown mini form type");
				}
			}
			elseif($_POST["submit"] == "EDIT")
			{
				if(is_numeric($_POST['id']))
				{
					$id = $_POST['id'];
					$exists = TRUE;
				}
				else
					die("error id");

				$andor = $_POST['andor'];
				if($andor != "AND" && $andor != "OR")
					die("andor must be AND or OR");

				$name = mysql_real_escape_string($_POST['name']);

				if($_POST['main'] == "main")
					$main = 1;
				else
					$main = 0;

				if(isset($groups[$_POST['group']]))
					$group = $_POST['group'];
				elseif(!$exists)
					die("Invalid Group");

				$this -> query("UPDATE {db_prefix}tea_rules SET name = '$name', main = $main, `group` = $group, andor = '$andor' WHERE ruleid = $id");
			}
			elseif($_POST["submit"] == "ADD")
			{
				if($_POST['id'] == "new")
				{
					$id = $this -> smcFunc['db_query']('', "SELECT ruleid FROM {db_prefix}tea_rules ORDER BY ruleid DESC LIMIT 1");
					$id = $this -> select($id);
					if(!empty($id))
						$id = $id[0][0]+1;
					else
						$id = 1;
					$ids[] = $id;
				}
				elseif(is_numeric($_POST['id']))
				{
					$id = $_POST['id'];
					$exists = TRUE;
				}
				else
					die("error id");

				$andor = $_POST['andor'];
				//if($andor != "AND" && $andor != "OR")
				//	die("andor must be AND or OR");

				$name = mysql_real_escape_string($_POST['name']);

				if($_POST['main'] == "main")
					$main = 1;
				else
					$main = 0;

				if(isset($types[$_POST['type']]))
					$type = $_POST['type'];
				else
					die("Unknown Type");

				if($_POST['isisnt'] == 'isnt')
					$isisnt = 'isnt';
				else
					$isisnt = 'is';

				if($type == "corp" || $type == "alliance")
				{
					$value = $_POST['value'];
					if(!is_numeric($value))
					{
						$post = array('names' => $value);
						$xml = $this -> get_xml('find', $post);
						$xml = new SimpleXMLElement($xml);
						$xml = (int)$xml -> result -> rowset -> row[0]['characterID'];
						if($type == "corp")
						{
							$check = $this -> corp_info($xml);
							if(!empty($check))
							{
								$value = $xml;
								$extra = $check['corpname'];
							}
							else
							{
								die($this -> txt['tea_cantfindcorp'].$value);
							}
						}
						else
						{
							$this -> alliance_list();
							$alliances = $this -> corps;
							$alliances = array_flip($alliances);
							if(isset($alliances[$xml]))
							{
								$extra = $value;
								$value = $xml;
							}
							else
							{
								die($this -> txt['tea_cantfindalliance'].$value);
							}
						}
					}
					else
					{
						if($type == "corp")
						{
							$check = $this -> corp_info($value);
							if(!empty($check))
							{
								$extra = $check['corpname'];
							}
							else
							{
								echo "Warning: Unable to find Corp with id: ".$value;
							}
						}
						else
						{
							$this -> alliance_list();
							$alliances = $this -> corps;
							$alliances = array_flip($alliances);
							if(isset($alliances[$value]))
							{
								$post = array('ids' => $value);
								$xml = $this -> get_xml('name', $post);
								$xml = new SimpleXMLElement($xml);
								$xml = (string)$xml -> result -> rowset -> row[0]['name'];
								$extra = $xml;
							}
							else
							{
								echo "Warning: Unable to find Alliance with id: ".$value;
							}
						}
					}
					$value = mysql_real_escape_string($value);
					$extra = mysql_real_escape_string($extra);
				}
				elseif($type == "skill" || $type == "role" || $type == "title" || $type == "militia")
					$value = mysql_real_escape_string($_POST['value']);

				elseif($type == "skill")
					$extra = (int)$_POST['extra'];

				if(isset($groups[$_POST['group']]))
					$group = $_POST['group'];
				elseif(!$exists)
					die("Invalid Group");

				if(!$exists)
					$this -> query("INSERT INTO {db_prefix}tea_rules (ruleid, name, main, `group`, andor) VALUES ($id, '$name', $main, $group, '$andor')");
				$this -> query("INSERT INTO {db_prefix}tea_conditions (ruleid, isisnt, type, value, extra) VALUES ($id, '$isisnt', '$type', '$value', '$extra')");
				//if(!isset($types[$_POST['type']]))
				//	error
				//elseif(!is_numeric($_POST['id']) && $_POST['id'] != "new")
				//	error
				//elseif(!is_numeric($_POST['group']))
				//	error
			}
		}
		$cgq = $this -> smcFunc['db_query']('', "SELECT id, main, additional FROM {db_prefix}tea_groups ORDER BY id");
		$cgq = $this -> select($cgq);
		if(!empty($cgq))
		{
			foreach($cgq as $cgqs)
				$cg[$cgqs[0]] = array($cgqs[1], $cgqs[2]);
		}
		$agroups = $this -> MemberGroups(TRUE);
		$out[0] = $this -> txt['tea_groupmon'].'<form name="groups" method="post" action="">
		<table><tr><td>Name</td><td>Main</td><td>Additional</td></tr>
		';
		foreach($agroups as $id => $g)
		{
			$mcheck = '';
			$acheck = '';
			if(isset($cg[$id]) && $cg[$id][0] == 1)
				$mcheck = 'checked';
			if(isset($cg[$id]) && $cg[$id][1] == 1)
				$acheck = 'checked';
			$out[0] .= '<tr><td>'.$g.'</td><td><input type="checkbox" name="main_'.$id.'" value="main" '.$mcheck.' /></td><td>';
			if($id != 0)
				$out[0] .= '<input type="checkbox" name="adit_'.$id.'" value="adit" '.$acheck.' /></td>';
			$out[0] .= '</tr>';
		}
		$out[0] .= '</table>
			<input type="submit" name="mong" value="UPDATE">
			</form></tr></table></dt>';
		$out[1] = '';
		$out[2] = '<dt>';

		$idl = $this -> smcFunc['db_query']('', "SELECT ruleid, name, main, `group`, andor, enabled FROM {db_prefix}tea_rules ORDER BY ruleid");
		$idl = $this -> select($idl);
		if(!empty($idl))
		{
			foreach($idl as $id)
			{
				$ids[] = $id[0];
				$list[$id[0]] = array('name' => $id[1], 'main' => $id[2], 'group' => $id[3], 'andor' => $id[4], 'enabled' => $id[5], 'conditions' => array());
			}
		}
		$idl = $this -> smcFunc['db_query']('', "SELECT id, ruleid, isisnt, type, value, extra FROM {db_prefix}tea_conditions ORDER BY ruleid");
		$idl = $this -> select($idl);
		if(!empty($idl))
		{
			foreach($idl as $id)
			{
				$list[$id[1]]['conditions'][] = array('id' => $id[0], 'isisnt' => $id[2], 'type' => $id[3], 'value' => $id[4], 'extra' => $id[5]);
			}
		}
	//	echo '<pre>'; var_dump($list);die;

		$out[2] .= $this -> txt['tea_rulesinfo'].'<br><br><b><u>Main Group Rules</b></u><form name="enablerules" method="post" action="">
		<table border="1">'.
				'<tr><td>ID</td><td>Name</td><td>Rule</td><td>Group</td><td>AND / OR</td><td>Enabled</td></tr>';
		if(!empty($list))
		{
			$first = TRUE;
			foreach($list as $id => $l)
			{
				if($l['main'] == 1)
					$last = $id;
			}
			$javalist = '';
			foreach($list as $id => $l)
			{
				if($l['main'] == 1)
				{
					$span = count($l['conditions']);
					if($l['enabled'] == 1)
					{
						$enabled = 'checked';
						$color = 'lightgreen';
					}
					else
					{
						$enabled = '';
						$color = 'red';
					}
					$out[2] .= '<tr bgcolor="'.$color.'"><td rowspan="'.$span.'">'.$id.'</td><td rowspan="'.$span.'">'.$l['name'].'</td>';
					$tr = '';
					foreach($l['conditions'] as $r)
					{
						$out[2] .= $tr.'<td>'.strtoupper($r['isisnt']).' => '.$types[$r['type']];
						if($r['type'] != 'red' && $r['type'] != 'blue' && $r['type'] != 'neut' && $r['type'] != 'error' && $r['type'] != 'valid')
							$out[2] .= ': '.$r['value'];
						if($r['extra'] != "")
							$out[2] .= " (".$r['extra'].")";
				//		if($span > 1)
				//			$out[2] .= '<a href="javascript:edit('.$id.')"><img src="'.$this -> settings['images_url'].'/icons/quick_remove.gif"></a>';
						$out[2] .= '</td>';
						if($tr == '')
						{
							$out[2] .= '<td rowspan="'.$span.'">'.$groups[$l['group']].'</td><td rowspan="'.$span.'">'.$l['andor'].'</td><td rowspan="'.$span.'">
							<table><tr><td><input type="checkbox" name="rule_'.$id.'" value="1" '.$enabled.' />';
							$out[2] .= '</td><td width="20">';
							if(!$first)
								$out[2] .= '<a href="javascript:move('.$id.', \'up\')"><img src="'.$this -> settings['images_url'].'/sort_up.gif"></a>';
							if($last != $id)
								$out[2] .= '<a href="javascript:move('.$id.', \'down\')"><img src="'.$this -> settings['images_url'].'/sort_down.gif"></a>';
							$out[2] .= '</td><td><a href="javascript:edit('.$id.')"><img src="'.$this -> settings['images_url'].'/icons/config_sm.gif"></a>
							<a href="javascript: delrule(\'delrule\', '.$id.')"><img src="'.$this -> settings['images_url'].'/icons/quick_remove.gif"></a>
							</td></tr></table>
							</td>';
						}
						$tr = '</tr><tr bgcolor="'.$color.'">';
					}
					$out[2] .= '</tr>';
					$javalist .= "rules[".$id."] = Array('".str_replace("'", "\'", $l['name'])."', 'true', '".$l['andor']."', '".str_replace("'", "\'", $l['group'])."');\n";
					$first = FALSE;
				}
			}
		}
		$out[2] .= '</tr></table><br><b><u>Additional Group Rules</b></u><table border="1"><tr><td>ID</td><td>Name</td><td>Rule</td><td>Group</td><td>AND / OR</td><td>Enabled</td></tr>';
		if(!empty($list))
		{
			foreach($list as $id => $l)
			{
				if($l['main'] == 0)
				{
					$span = count($l['conditions']);
					if($l['enabled'] == 1)
					{
						$enabled = 'checked';
						$color = 'lightgreen';
					}
					else
					{
						$enabled = '';
						$color = 'red';
					}
					$out[2] .= '<tr bgcolor="'.$color.'"><td rowspan="'.$span.'">'.$id.'</td><td rowspan="'.$span.'">'.$l['name'].'</td>';
					$tr = '';
					foreach($l['conditions'] as $r)
					{
						$out[2] .= $tr.'<td>'.strtoupper($r['isisnt']).' => '.$types[$r['type']];
						if($r['type'] != 'red' && $r['type'] != 'blue' && $r['type'] != 'neut' && $r['type'] != 'error' && $r['type'] != 'valid')
							$out[2] .= ': '.$r['value'];
						if($r['extra'] != "")
							$out[2] .= " (".$r['extra'].")";
						$out[2] .= '</td>';
						if($tr == '')
						{
							$out[2] .= '<td rowspan="'.$span.'">'.$groups[$l['group']].'</td><td rowspan="'.$span.'">'.$l['andor'].'</td><td rowspan="'.$span.'">
							<table><tr><td><input type="checkbox" name="rule_'.$id.'" value="1" '.$enabled.' />';
							$out[2] .= '</td><td width="20">';
							if(!$first)
								$out[2] .= '<a href="javascript:move('.$id.', \'up\')"><img src="'.$this -> settings['images_url'].'/sort_up.gif"></a>';
							if($last != $id)
								$out[2] .= '<a href="javascript:move('.$id.', \'down\')"><img src="'.$this -> settings['images_url'].'/sort_down.gif"></a>';
							$out[2] .= '</td><td><a href="javascript:edit('.$id.')"><img src="'.$this -> settings['images_url'].'/icons/config_sm.gif"></a>
							<a href="javascript: delrule(\'delrule\', '.$id.')"><img src="'.$this -> settings['images_url'].'/icons/quick_remove.gif"></a>
							</td></tr></table>
							</td>';
						}
						$tr = '</tr><tr bgcolor="'.$color.'">';
					}
					$out[2] .= '</tr>';
					$javalist .= "rules[".$id."] = Array('".str_replace("'", "\'", $l['name'])."', '', '".$l['andor']."', '".str_replace("'", "\'", $l['group'])."');\n";
				}
			}
		}
		$out[2] .= '</tr></table><br><input type="submit" name="enr" value="UPDATE"></form>';
		$out[2] .= '<form name="miniform" method="post" action="">
		<input type="hidden" name="minitype" value="" />
		<input type="hidden" name="value" value="" />
		</form></dt>';
		$out[3] = '';
		$out[4] = '<dt><div id="formtitle">Create Rule:</div><br>
					<form name="makerule" method="post" action="">
			<table>
			<tr>
				<td width="134"><div id="tea_nametxt">Name:</div></td>
				<td><div id="tea_name"><input type="text" name="name" value="" /> For reference only</div></td>
			</tr>
			<tr>
				<td width="134">Rule ID:</td>
				<td><select name="id" onchange="javascript: value_type(false)"><option value="new">new</option>';
		foreach($ids as $id)
		{
			$out[4] .= '<option value="'.$id.'">'.$id.'</option>';
		}

		$out[4] .= '</select></td>
			</tr>
						<tr>
				<td><div id="tea_maintxt">Main Group:</div></td>
				<td><div id="tea_main"><input type="checkbox" name="main" value="main" /></div></td>
			</tr>
			<tr>
				<td><div id="tea_linktxt">Condition link:</div></td>
				<td><div id="tea_link"><select name="andor">
						<option value="AND">AND</option>
						<option value="OR">OR</option>
					</select> should multiple conditions be treated as AND or OR</div></td>
			</tr>
			<tr>
				<td><div id="tea_isisnttxt">IS or ISNT:</div></td>
				<td><div id="tea_isisnt"><select name="isisnt">
						<option value="is">IS</option>
						<option value="isnt">ISNT</option>
					</select></div></td>
			</tr>
			<tr>
				<td><div id="tea_typetxt">Type:</div></td>
				<td><div id="tea_type"><select name="type" onchange="javascript: value_type(false)">';
		foreach($types as $value => $name)
		{
			$out[4] .= '<option value="'.$value.'">'.$name.'</option>';
		}
		$out[4] .= '</select></div></td>
			</tr>

				<tr>
				<td><div id="tea_valuetxt"></div></td>
				<td><div id="tea_value"></div></td>
			</tr><tr>
				<td><div id="tea_grouptxt">Group:</div></td>
				<td><div id="tea_group"><select name="group">
				<option value="-">-</option>';
		foreach($groups as $id => $group)
		{
			$out[4] .= '<option value="'.$id.'">'.$group.'</option>';
		}
		$out[4] .= '</select></div></td>
			</tr>
			<tr>
				<td width="134">&nbsp;</td>
				<td><input type="submit" name="submit" value="ADD"></td>
			</tr>
			</table>
			</form>
</dt>';
//			TODO: language file
$out[4] .= '
<script type="text/javascript">
var rules = new Array();
'.$javalist.'
function value_type(fromedit)
{
	type = document.makerule.type.value;
	id = document.makerule.id.value;
	name = document.makerule.name.value;
	group = document.makerule.group.value;
	main = document.makerule.main.checked;
	andor = document.makerule.andor.value;

	if(document.makerule.submit.value == "EDIT" && fromedit == false)
	{
		edit(id);
		return;
	}
	if(id == "new" || fromedit == true)
	{
		document.getElementById(\'tea_nametxt\').innerHTML="Name:";
		document.getElementById(\'tea_name\').innerHTML=\'<input type="text" name="name" value="\'+name+\'" /> For reference only\';
		document.getElementById(\'tea_maintxt\').innerHTML="Main Group:";
		document.getElementById(\'tea_main\').innerHTML=\'<input type="checkbox" name="main" value="main" />\';
		document.getElementById(\'tea_linktxt\').innerHTML="Condition link:";
		document.getElementById(\'tea_link\').innerHTML=\'<select name="andor"><option value="AND">AND</option><option value="OR">OR</option></select> should multiple conditions be treated as AND or OR\';
		document.getElementById(\'tea_grouptxt\').innerHTML="Group:";
		document.getElementById(\'tea_group\').innerHTML=\'<select name="group"><option value="-">-</option>';
		foreach($groups as $id => $group)
		{
			$out[4] .= '<option value="'.$id.'">'.str_replace("'", "\'", $group).'</option>';
		}
		$out[4] .= '</select>\';
		document.makerule.group.value = group;
		document.makerule.main.checked = main;
		document.makerule.andor.value = andor;
	}
	else
	{
		document.getElementById(\'tea_nametxt\').innerHTML="";
		document.getElementById(\'tea_name\').innerHTML="";
		document.getElementById(\'tea_maintxt\').innerHTML="";
		document.getElementById(\'tea_main\').innerHTML="";
		document.getElementById(\'tea_linktxt\').innerHTML="";
		document.getElementById(\'tea_link\').innerHTML="";
		document.getElementById(\'tea_grouptxt\').innerHTML="";
		document.getElementById(\'tea_group\').innerHTML="";
	}
	if(type == "corp")
	{
		document.getElementById(\'tea_valuetxt\').innerHTML="Corp Name or ID:";
		document.getElementById(\'tea_value\').innerHTML=\'<input type="text" name="value" value="" />\';
	}
	else if(type == "alliance")
	{
		document.getElementById(\'tea_valuetxt\').innerHTML="Alliance Name or ID:";
		document.getElementById(\'tea_value\').innerHTML=\'<input type="text" name="value" value="" />\';
	}
	else if(type == "blue" || type == "red" || type == "neut" || type == "error" || type == "valid")
	{
		document.getElementById(\'tea_valuetxt\').innerHTML="";
		document.getElementById(\'tea_value\').innerHTML="";
	}
	else if(type == "skill")
	{
		document.getElementById(\'tea_valuetxt\').innerHTML="Skill:";
		document.getElementById(\'tea_value\').innerHTML=\'<input type="text" name="value" value="" /> % wildcard Allowed<br>Level: <input type="radio" name="extra" value="1" /> 1 <input type="radio" name="extra" value="1" /> 2 <input type="radio" name="extra" value="1" /> 3 <input type="radio" name="extra" value="1" /> 4 <input type="radio" name="extra" value="1" /> 5\';
	}
	else if(type == "role")
	{
		document.getElementById(\'tea_valuetxt\').innerHTML="Role:";
		document.getElementById(\'tea_value\').innerHTML=\'<select name="value">';
		require($this -> sourcedir.'/TEA_Roles.php');
		foreach($roles as $role => $i)
		{
			$out[4] .= '<option value="'.$role.'">'.$role.'</option>';
		}
		$out[4] .= '</select>\';
	}
	else if(type == "title")
	{
		document.getElementById(\'tea_valuetxt\').innerHTML="Title:";
		document.getElementById(\'tea_value\').innerHTML=\'<input type="text" name="value" value="" />\';
	}
	else if(type == "militia")
	{
		document.getElementById(\'tea_valuetxt\').innerHTML="Militia:";
		document.getElementById(\'tea_value\').innerHTML=\'<select name="value"><option value="Amarr Empire">Amarr Empire</option><option value="Caldari State">Caldari State</option><option value="Gallente Federation">Gallente Federation</option><option value="Minmatar Republic">Minmatar Republic</option></select>\';
	}
}
newremoved = false;
function edit(id)
{
	document.makerule.type.value="error";
	value_type(true);
	document.makerule.submit.value="EDIT";
	document.getElementById(\'formtitle\').innerHTML="Edit Rule:";
	document.getElementById(\'tea_typetxt\').innerHTML="";
	document.getElementById(\'tea_type\').innerHTML="";
	document.getElementById(\'tea_isisnttxt\').innerHTML="";
	document.getElementById(\'tea_isisnt\').innerHTML="";
	if(!newremoved)
		document.makerule.id.remove("new");
	document.makerule.name.value=rules[id][0];
	document.makerule.id.value=id;
	document.makerule.main.checked=rules[id][1];
	document.makerule.andor.value=rules[id][2];
	document.makerule.group.value=rules[id][3];
	newremoved = true;
}
function delrule(type, value)
{
	if (confirm(rules[value][0]+"\nAre you sure you want Delete this?"))
		subform(type, value);
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
value_type();
</script>
';
		$config_vars = $out;
		$this -> context['post_url'] = $scripturl . '?action=admin;area=tea;sa=rules;save';
		$this -> context['settings_save_dont_show'] = TRUE;
		prepareDBSettingContext($config_vars);
	}

	function settings_checks($scripturl)
	{
		if (isset($_GET['update']))
		{
			if(!$this -> modSettings["tea_enable"])
				$file = "API Mod is Disabled";
			if(!$_GET['lastid'] || !is_numeric($_GET['lastid']))
				$lastid = "0";
			else
				$lastid = $_GET['lastid'];
			$this -> update_api(NULL, $lastid);
			$users1 = $this -> smcFunc['db_query']('', "SELECT id_member, member_name, ID_GROUP FROM {db_prefix}members WHERE id_member <= ".$this -> lastid);
			$users1 = $this -> select($users1);
			if(empty($users1))
				$users1 = 0;
			else
				$users1 = count($users1);
			$users2 = $this -> smcFunc['db_query']('', "SELECT id_member, member_name, ID_GROUP FROM {db_prefix}members WHERE id_member > ".$this -> lastid);
			$users2 = $this -> select($users2);
			if(empty($users2))
				$users2 = 0;
			else
				$users2 = count($users2);
			$echo = "<dt>Checked: $users1 / ".($users1+$users2)."<br></dt>";
			if($users2 > 0)
			{
				$echo .= '<dt><table><tr><td><a href="'.$scripturl.'?action=admin;area=tea;sa=checks;update;lastid='.$this -> lastid.'">Continue</a></td><td><div id="cdown"></div></td></tr></table></dt>';
				$echo .= '
				<script type="text/javascript">
					var num = 3;
					var interval = setInterval("count();" , 1000);
					function count()
					{
						document.getElementById(\'cdown\').innerHTML="("+num+")";
						if(num == 0)
						{
							clearInterval(interval);
							window.location = "'.$scripturl.'?action=admin;area=tea;sa=checks;update;lastid='.$this -> lastid.'";
						}
						num = num - 1;
					}
				</script>';
			}
			$file = str_replace("\n", "<br>", $this -> log);
			$config_vars = array(
			$echo,
			'<dt>'.$file.'</dt>'
			);
		}
		elseif (isset($_GET['reset']))
		{
			if(!$this -> modSettings["tea_enable"])
				$file = "API Mod is Disabled";
			$log = $this -> pref_reset();
			$config_vars = array(
			'<dt>'.$log.'</dt>'
			);
		}
		else
		{
			$config_vars = array(
				'<dt><a href="'.$scripturl.'?action=admin;area=tea;sa=checks;update">'.$this -> txt['tea_fullcheck'].'</a></dt>',
				'<dt><a href="'.$scripturl.'?action=admin;area=tea;sa=checks;reset">'.$this -> txt['tea_fullnamecheck'].'</a></dt>',
			);
		}

		//$context['post_url'] = $scripturl . '?action=admin;area=tea;sa=checks;save';
//		$context['settings_title'] = $txt['tea_tea'];
//		$context['settings_message'] = $txt['tea_settings_message'];
//		$this -> context['post_url'] = $scripturl . '?action=admin;area=tea;sa=checks;save';
		$this -> context['settings_save_dont_show'] = TRUE;
		prepareDBSettingContext($config_vars);
	}

	function MemberGroups($all = FALSE)
	{
		$list = $this -> smcFunc['db_query']('', 'SELECT id_group, group_name FROM {db_prefix}membergroups WHERE min_posts = -1 ORDER BY group_name');
		$list = $this -> select($list);
		if(!empty($list))
		{
			foreach($list as $l)
			{
				$groups[$l[0]] = $l[1];
			}
		}
		if(!$all)
		{
			unset($groups[1]);
		}
		unset($groups[3]);
		$groups[0] = "no membergroup";
		Return $groups;
	}

	function tea_set_api($memberID, $reg=FALSE)
	{
		if(!$this -> modSettings["tea_enable"])
			Return;
	//	echo $memberID." kk ".$db_prefix;
	//	var_dump($_POST);
		if(!is_numeric($memberID))
			return;

		//var_dump($_POST);die;
		if($reg)
		{
			//SMF passes through this page twice, the second time generates an error because the TEA variables don't come through the post
			if (!empty($_POST['tea_user_id']))
			{
				$userids = array($_POST['tea_user_id']);
				$apis = array($_POST['tea_user_api']);
			}
			else
			{
				$userids = array();
				$apis = array();
			}
		}
		else
		{
			$userids = $_POST['tea_user_id'];
			$apis = $_POST['tea_user_api'];
		}
		foreach($userids as $k => $userid)
		{
			if($userid == "")
				Continue;
			$api = $apis[$k];
			$duserid = NULL;
			$dapi = NULL;
			$user = $this -> smcFunc['db_query']('', "SELECT userid, api, status, status_change, ID_MEMBER FROM {db_prefix}tea_api WHERE userid = ".mysql_real_escape_string($userid));
			$user = $this -> select($user);
			if(!empty($user))
			{
				if($user[0][4] != $memberID)
				{
					$_SESSION['tea_error'][] = "API is already Attached to a Different Account";
					Continue;
				}
				$duserid = $user[0][0];
				$dapi = $user[0][1];
			}
			else
			{
				$check = $this -> is_new($_POST['tea_user_id'], $_POST['tea_user_api']);
				if(!$check) // old api
				{
					$_SESSION['tea_error'][] = "OLD API's are no Longer Accepted as new api's";
					Continue;
				}
			}
			if(!$userid || !$api)
				Continue;
			if($duserid != $userid || $dapi != $api)
			{
				$this -> query("
					REPLACE INTO {db_prefix}tea_api
						(ID_MEMBER, userid, api, status, status_change)
					VALUES 
					($memberID, '" . mysql_real_escape_string($userid) . "', '" . mysql_real_escape_string($api) . "', 'unchecked', ".time().")");
			}
		}
		if(isset($_POST['del_api']))
		{
			foreach($_POST['del_api'] as $userid)
			{
				$this -> query("DELETE FROM {db_prefix}tea_api WHERE ID_MEMBER = $memberID AND userid = '" . mysql_real_escape_string($userid) . "'");
			}
		}
		unset($_POST['del_api']);
		unset($_POST['tea_user_id']);
		unset($_POST['tea_user_api']);
		$this -> update_api($memberID);
		$user = $this -> smcFunc['db_query']('', "SELECT main FROM {db_prefix}tea_user_prefs WHERE id = ".$memberID);
		$user = $this -> select($user);
		if($reg || empty($user) || $user[0][0] != $_POST['tea_charid'])
		{
			//if($modSettings['tea_usecharname'])
			if($_POST['tea_charid'] == "-")
			{
				$user = $this -> smcFunc['db_query']('', "SELECT real_name FROM {db_prefix}members WHERE id_member = ".$memberID);
				$user = $this -> select($user);
				if(!empty($user))
				{
					$realname = $user[0][0];
					$match = FALSE;
					$chars = $this -> get_all_chars($memberID);
					if(!empty($chars))
					{
						foreach($chars as $char)
						{
							$comp[] = $char[0];
						}
						$best = $this -> best_match($realname, $comp);
						$best = $best[1];
						foreach($chars as $i => $char)
						{
							if($char[0] == $best)
								$match = $i;
						}
						if($match)
						{
							$_POST['tea_charid'] = $match;
						}
					}
				}
			}
			if($_POST['tea_charid'] != "-")
			{
				//$char = $this -> matchedchar;
				$charid = $_POST['tea_charid'];
				if(!is_numeric($charid))
					die('charid must be number if selected');
				$this -> query("REPLACE INTO {db_prefix}tea_user_prefs (id, main) VALUES (".$memberID.", ".$charid.")");
				$char = $this -> smcFunc['db_query']('', "SELECT name, corpid, corp, corp_ticker, allianceid, alliance, alliance_ticker FROM {db_prefix}tea_characters WHERE charid = ".$charid);
				$char = $this -> select($char);
				if(!empty($char))
				{
					$char = $char[0];
					$name = $char[0];
					if($this -> modSettings["tea_custom_title"])
					{
						$title = $this -> format_name($memberID, $name, 'title');
						$this -> query("UPDATE {db_prefix}members SET usertitle = '".$title."' WHERE ID_MEMBER = ".$memberID);
					}
					if($this -> modSettings["tea_custom_name"])
					{
						$name = $this -> format_name($memberID, $name);
						$this -> query("UPDATE {db_prefix}members SET real_name = '".mysql_real_escape_string($name)."' WHERE ID_MEMBER = ".$memberID);
					}

					if($this -> modSettings['tea_avatar_enabled'])
					{
					//	if($this -> modSettings["tea_corptag_options"] == 2) ? why is this here
					//	{
					//		$name = explode("] ", $name, 2);
					//		$name = $name[1];
					//	}
					//	if(isset($this -> chars[$name]['charid']))
					//	{
							require_once("Subs-Graphics.php");
							$tea_avatar_size = !empty($this -> modSettings['tea_avatar_size']) ? $this -> modSettings['tea_avatar_size'] : 64;
							downloadAvatar('http://image.eveonline.com/Character/'.$charid.'_'.$tea_avatar_size.'.jpg', $memberID, $tea_avatar_size, $tea_avatar_size);
					//	}
					}
				}
			}
		}
	}

	function format_name($memID, $char, $type='name')
	{
		$chars = $this -> get_all_chars($memID);

		$smfgroups = $this -> smf_groups($memID);
		if(!empty($chars))
		{
			// $rules = $this -> smcFunc['db_query']('', "SELECT id, smf, ts, tst, nf FROM {db_prefix}tea_ts_rules");
			// $rules = $this -> tea -> select($rules);
			// if(!empty($rules))
			// {
				// foreach($rules as $r)
				// {
					// if(!empty($smfgroups))
					// {
						// foreach($smfgroups as $g)
						// {
							// if($r[1] == $g)
							// {
								// if(!isset($nf))
									// $nf = $r[4];
							// }
						// }
					// }
				// }
			// }
			foreach($chars as $i => $ch)
			{
				if($ch[0] == $char)
					$charinfo = $ch;
			}
			if(!empty($charinfo))
			{
				// if($nf)
					// $name = $nf;
				// else
				if($type == 'title')
					$name = $this -> modSettings["tea_tf"];
				else
					$name = $this -> modSettings["tea_nf"];
				$name = str_replace('#at#', $charinfo[4], $name);
				$name = str_replace('#ct#', $charinfo[1], $name);
				$name = str_replace('#name#', $char, $name);
			}
		}
	//	if(strlen($name) > 30)
	//	{
	//		$name = substr($name, 0, 30);
	//	}
		return $name;
	}

	function smf_groups($memID)
	{
		$groups = array();
		$dbgs = $this -> smcFunc['db_query']('', "SELECT ID_MEMBER, ID_GROUP, additional_groups FROM {db_prefix}members WHERE ID_MEMBER = {int:id}", array('id' => $memID));
		$dbgs = $this -> select($dbgs);
		if(!empty($dbgs))
		{
			$groups[$dbgs[0][1]] = $dbgs[0][1];
			if(!empty($dbgs[0][2]))
			{
				$dbgs[0][2] = explode(',', $dbgs[0][2]);
				foreach($dbgs[0][2] as $g)
					$groups[$g] = $g;
			}
		}
		return $groups;
	}

	function pref_reset()
	{
		$user = $this -> smcFunc['db_query']('', "SELECT id, main FROM {db_prefix}tea_user_prefs");
		$user = $this -> select($user);
		if(!empty($user))
		{
			foreach($user as $u)
			{
				$memberID = $u[0];
				$charid = $u[1];
				$char = $this -> smcFunc['db_query']('', "SELECT name, corpid, corp, corp_ticker, allianceid, alliance, alliance_ticker FROM {db_prefix}tea_characters WHERE charid = ".$charid);
				$char = $this -> select($char);
				if(!empty($char))
				{
					$char = $char[0];
					$name = $char[0];
					if($this -> modSettings["tea_custom_title"])
					{
						$title = $this -> format_name($memberID, $name, 'title');
						$this -> query("UPDATE {db_prefix}members SET usertitle = '".$title."' WHERE ID_MEMBER = ".$memberID);
					}
					if($this -> modSettings["tea_custom_name"])
					{
						$name = $this -> format_name($memberID, $name);
						$this -> query("UPDATE {db_prefix}members SET real_name = '".mysql_real_escape_string($name)."' WHERE ID_MEMBER = ".$memberID);
					}

					if($this -> modSettings['tea_avatar_enabled'])
					{
					//	if($this -> modSettings["tea_corptag_options"] == 2)
					//	{
					//		$name = explode("] ", $name, 2);
					//		$name = $name[1];
					//	}
					//	if(isset($this -> chars[$name]['charid']))
					//	{
							require_once("Subs-Graphics.php");
							$tea_avatar_size = !empty($this -> modSettings['tea_avatar_size']) ? $this -> modSettings['tea_avatar_size'] : 64;
							downloadAvatar('http://image.eveonline.com/Character/'.$charid.'_'.$tea_avatar_size.'.jpg', $memberID, $tea_avatar_size, $tea_avatar_size);
					//	}
					}
				}
				$count++;
			}
		}
		return $count." User Prefs reset";
	}

	function setmains()
	{
		$user = $this -> smcFunc['db_query']('', "SELECT id_member, real_name FROM {db_prefix}members");
		$user = $this -> select($user);
		if(!empty($user))
		{
			foreach($user as $u)
			{
				$match = FALSE;
				$chars = $this -> get_all_chars($u[0]);
				if(!empty($chars))
				{
					foreach($chars as $char)
					{
						$comp[] = $char[0];
					}
					$best = $this -> best_match($u[1], $comp);
					$best = $best[1];
					foreach($chars as $i => $char)
					{
						if($char[0] == $best)
							$match = $i;
					}
					if($match)
					{
						$this -> query("REPLACE INTO {db_prefix}tea_user_prefs (id, main) VALUES (".$u[0].", ".$match.")");
						echo "User ID: ".$u[0]." (".$u[1].") Matched to Character $best ($match) <Br>";
					}
				}
				if(!$match)
				{
					echo "User ID: ".$u[0]." (".$u[1].") Failed<Br>";
				}
			}
		}
	}

	function best_match($find, $in, $perc=0)
	{
		$use = array(0);
		$percentage = 0;

		if(!empty($in))
		{
			foreach($in as $compare)
			{
				similar_text($find, $compare, $percentage);
				if ($percentage >= $perc
				&& $percentage > $use[0])
				{
					$use = array($percentage, $compare);
				}
			}
		}
		return $use;
	}

	function DisplayAPIinfo(&$context, &$modSettings, $db_prefix, &$txt)
	{
		if(!$this -> modSettings["tea_enable"])
			Return;
		return;
		loadLanguage('TEA');
		$ID_MEMBER = $context['user']['id'];
		// Did we get the user by name...
		if (isset($_REQUEST['user']))
			$memberResult = loadMemberData($_REQUEST['user'], true, 'profile');
		// ... or by ID_MEMBER?
		elseif (!empty($_REQUEST['u']))
			$memberResult = loadMemberData((int) $_REQUEST['u'], false, 'profile');
		// If it was just ?action=profile, edit your own profile.
		else
			$memberResult = loadMemberData($ID_MEMBER, false, 'profile');

		if(!is_numeric($memberResult[0]))
			die("Invalid User id");
		if($ID_MEMBER == $memberResult[0])
			$allow = AllowedTo(array('tea_view_own', 'tea_view_any'));
		else
			$allow = AllowedTo('tea_view_any');
		if($allow)
		{
			$api = $this -> smcFunc['db_query']('', "SELECT userid, api, charid, status, status_change FROM {db_prefix}tea_api WHERE ID_MEMBER = ".$memberResult[0]);
			$api = $this -> select($api);
			if(!empty($api))
			{
				$api = $api[0];
			}
			echo '
						</tr><tr>
						<td><b>' . $this -> txt['tea_userid_short'] . ': </b></td>
						<td>' . $api[0] . '</td>
						</tr><tr>
						<td><b>' . $this -> txt['tea_api_short'] . ': </b></td>
						<td>' . $api[1] . '</td>';
		}
	}

	function EveApi($txt, $scripturl, &$context, $settings, $sc)
	{ // old settings mod?
		die("old function?");
		if(!$this -> modSettings["tea_enable"])
			Return;
		$config_vars = array(
			'',
				// enable?
				array('check', 'tea_enable'),
			'',
				// api info
				array('int', 'tea_userid', 10),
				array('text', 'tea_api', 64),
		//		array('check', 'topbottomEnable'),
		//		array('check', 'onlineEnable'),
		//		array('check', 'enableVBStyleLogin'),
		//	'',
				// Pagination stuff.
		//		array('int', 'defaultMaxMembers'),
		//	'',
				// This is like debugging sorta.
		//		array('check', 'timeLoadPageEnable'),
		//		array('check', 'disableHostnameLookup'),
			'',
				// Who's online.
		//		array('check', 'who_enabled'),
		);

		// Saving?
		if (isset($_GET['save']))
		{
			saveDBSettings($config_vars);
			redirectexit('action=featuresettings;sa=tea');

			loadUserSettings();
			writeLog();
		}

		$context['post_url'] = $scripturl . '?action=featuresettings2;save;sa=tea';
		$context['settings_title'] = $this -> txt['mods_cat_layout'];
		$context['settings_message'] = $this -> txt['tea_settings_message'];

	//	prepareDBSettingContext($config_vars);
	}

	function UserModifyTEA($memID, &$teainfo)
	{
	//	if(!$this -> modSettings["tea_enable"])
	//		Return;

		//	isAllowedTo('tea_edit_any');
		if(!is_numeric($memID))
			die("Invalid User id");
		$this -> memid = $memID;
		$user = $this -> smcFunc['db_query']('', "SELECT userid, api, status, matched, error FROM {db_prefix}tea_api WHERE ID_MEMBER = ".$memID);
		$user = $this -> select($user);
		if(!empty($user))
		{
			foreach($user as $u)
			{
				$characters = $this -> get_acc_chars($u[0]);
				
			//	if($u[1] == "tnet")
			//		$checkt = "checked";
			//	else
			//		$checkb = "checked";
			//	if($u[3] == "unverified" && strlen($u[0]) < 3)
			//	{
			//		$msg = "Please set a Character 1st";
			//	}
			//	elseif($u[3] == "unverified")
			//	{
			//		$msg = "/tell ".ucfirst($u[1])." !cv $u[2]";
			//	}
			//	elseif($u[3] == "verified")
			//	{
			//		$msg = "You are Already Verified";
			//	}
				$adits = NULL;
				$matched = explode(";", $u[3], 2);
				if(is_numeric($matched[0]))
				{
					$mname = $this -> smcFunc['db_query']('', "SELECT name FROM {db_prefix}tea_rules WHERE ruleid = {int:id}", array('id' => $matched[0]));
					$mname = $this -> select($mname);
					if(!empty($mname))
						$mname = $mname[0][0];
				}
				else
					$mname = $matched[0];
				if(!empty($matched[1]))
					$adits = explode(',', $matched[1]);
				$anames = array();
				$prefs = $this -> smcFunc['db_query']('', "SELECT main FROM {db_prefix}tea_user_prefs WHERE id = {int:id}", array('id' => $memID));
				$prefs = $this -> select($prefs);
				if(!empty($prefs))
					$main = $prefs[0][0];
				else
					$main = "";
				if(!empty($adits) && $adits[0] != '')
				{
					foreach($adits as $a)
					{
						if(is_numeric($a))
						{
							$aname = $this -> smcFunc['db_query']('', "SELECT name FROM {db_prefix}tea_rules WHERE ruleid = {int:id}", array('id' => $a));
							$aname = $this -> select($aname);
							if(!empty($aname))
								$anames[] = $aname[0][0];
						}
					}
					$aname = implode(", ", $anames);
				}
				else
					$aname = 'none';
				$teainfo[] = array(
				"userid" => $u[0],
				"api" => $u[1],
			//	"msg" => $msg,
				'charnames' => $characters,
				'main' => $main,
				'status' => $u[2],
				'mainrule' => $mname,
				'aditrules' => $aname,
				'error' => $u[4]
				);
			}
		}
	}

	function RegistrationFields()
	{
		if(!$this -> modSettings["tea_enable"])
			Return;

		if(isset($_POST['tea_user_id']))
			$post['id'] = $_POST['tea_user_id'];
		else
			$post['id'] = '';
		if(isset($_POST['tea_user_api']))
			$post['api'] = $_POST['tea_user_api'];
		else
			$post['api'] = '';

//echo '							<table border="0" width="100%" cellpadding="3">';

//		echo '<tr><td>
//										<b>', $this -> txt['tea_userid'], ':</b></td>
//										<td><input type="text" name="tea_user_id[]" value="'.$api[0].'" size="10" />
//									</td>
//								</tr><tr>
//									<td width="40%">										<b>', $this -> txt['tea_api'], ':</b></td>
//										<td><input type="text" name="tea_user_api[]" value="'.$api[1].'" size="64" />
//									</td>
//								</tr>';
		echo '<dl class="register_form">
									<dt>
										<b>TEA Required Options:</b></dt>
									<dd>
										CharacterInfo, CharacterSheet, FacWarStats
									</dd>
									<dt>
										<b>', $this -> txt['tea_userid'], ':</b></dt>
									<dd>
										<input type="text" name="tea_user_id" value="'.$post['id'].'" size="10" />
									</dd>
								</dl><dl class="register_form">
									<dt>										<b>', $this -> txt['tea_api'], ':</b></dt>
									<dd>
										<input type="text" name="tea_user_api" value="'.$post['api'].'" size="64" />
									</dd>
								</dl>
								</dl><dl class="register_form">
									<dt>										<b>', $this -> txt['tea_charid'], ':</b></dt>
									<dd>
										<div id="chars"><select name="tea_char">
											<option value="-">-</option> 
										</select> <button type="button" onclick="javascript: getchars()">'.$this -> txt['tea_getchar'].'</button></div>
									</dd>
								</dl>
<script type="text/javascript">
function getchars()
{
	userid = document.registration.tea_user_id.value;
	api = document.registration.tea_user_api.value;
	include("TEA/TEA_xmlhttp.php?userid="+userid+"&api="+api);
}
if(auto)
	getchars();
function include(pURL)
{
	if (window.XMLHttpRequest) { // code for Mozilla, Safari, ** And Now IE 7 **, etc
		xmlhttp=new XMLHttpRequest();
	} else if (window.ActiveXObject) { //IE
		xmlhttp=new ActiveXObject(\'Microsoft.XMLHTTP\');
	}
	if (typeof(xmlhttp)==\'object\')
	{
		xmlhttp.onreadystatechange=postFileReady;
		xmlhttp.open(\'GET\', pURL, true);
		xmlhttp.send(null);
	}
}

function postFileReady()
{
	if (xmlhttp.readyState==4)
	{
		if (xmlhttp.status==200)
		{
			document.getElementById(\'chars\').innerHTML=xmlhttp.responseText;
		}
	}
}
</script>';
		// Show the standard "Save Settings" profile button.

	//	echo '
	//						</table>';
	}

	function reg_checks()
	{
		if(!empty($_POST['tea_user_id']))
		{
			$user = $this -> smcFunc['db_query']('', "SELECT userid, api, status, status_change, ID_MEMBER FROM {db_prefix}tea_api WHERE userid = ".mysql_real_escape_string($_POST['tea_user_id']));
			$user = $this -> select($user);
			if(!empty($user))
			{
				return("API is already Attached to an Account");
			}
		}
		if($this -> modSettings['tea_regreq'])
		{
			$chars = $this -> get_characters($_POST['tea_user_id'], $_POST['tea_user_api']);
			if(empty($chars)) // invalid api
			{
				$ret = $this -> txt['tea_regreq_error'];
				if(empty($ret))
					$ret = 'A Valid API is Required to Register on this Forum';
				Return $ret;
			}
		}
		if(!empty($_POST['tea_user_id']) && (empty($_POST['tea_charid']) || strlen($_POST['tea_charid']) < 3))
		{
			echo '
				<script type="text/javascript">
					auto = 1;
				</script>
			';
			$ret = $this -> txt['tea_regchar_error'];
			if(empty($ret))
				$ret = 'Please Select a Character';
			Return $ret;
		}
		$check = $this -> is_new($_POST['tea_user_id'], $_POST['tea_user_api']);
		if(!$check) // old api
		{
		//	$ret = $this -> txt['tea_regreq_error'];
		//	if(empty($ret))
				$ret = 'A NEW API is Required to Register on this Forum, OLD API\'s no longer Supported for Registration';
			Return $ret;
		}
	}

	function avatar_option()
	{
		echo '
			<script type="text/javascript">
				function getPortrait(id)
				{
					var maxHeight = ', !empty($this -> modSettings['avatar_max_height_external']) ? $this -> modSettings['avatar_max_height_external'] : 0, ';
					var maxWidth = ', !empty($this -> modSettings['avatar_max_width_external']) ? $this -> modSettings['avatar_max_width_external'] : 0, ';
					var tempImage = new Image();

					tempImage.src = \'http://image.eveonline.com/Character/\'+id+\'_', !empty($this -> modSettings['tea_avatar_size']) ? $this -> modSettings['tea_avatar_size'] : 64, '.jpg\';
					if (maxWidth != 0 && tempImage.width > maxWidth)
					{
						document.getElementById("eavatar").style.height = parseInt((maxWidth * tempImage.height) / tempImage.width) + "px";
						document.getElementById("eavatar").style.width = maxWidth + "px";
					}
					else if (maxHeight != 0 && tempImage.height > maxHeight)
					{
						document.getElementById("eavatar").style.width = parseInt((maxHeight * tempImage.width) / tempImage.height) + "px";
						document.getElementById("eavatar").style.height = maxHeight + "px";
					}
					document.getElementById("eavatar").src = \'http://image.eveonline.com/Character/\'+id+\'_', !empty($this -> modSettings['tea_avatar_size']) ? $this -> modSettings['tea_avatar_size'] : 64, '.jpg\';

				}
			</script>
								<div id="avatar_tea">
									<select name="attachment" value="', !empty($this -> context['member']['avatar']['tea']) ? $this -> context['member']['avatar']['tea'] : '', '"  onfocus="selectRadioByName(document.forms.creator.avatar_choice, \'tea\');" onchange="getPortrait(this.value);" >';
		$chars = $this -> get_char_list(TRUE);
	//	echo "\n<pre>"; var_dump($this -> context['member']['avatar']);die;
		if(!empty($chars))
		{
			foreach($chars as $id => $char)
			{
				echo '<option value="'.$id.'">'.$char.'</option>';
			}
		}
		echo '			</select>
<br><img name="eavatar" id="eavatar" src="', !empty($this -> modSettings["tea_enable"]) && $this -> context['member']['avatar']['choice'] == 'tea' ? $this -> context['member']['avatar']['tea'] : $this -> modSettings['avatar_url'] . '/blank.gif', '" />
								</div>';
	}

	function avatar_option_lock()
	{
		if($this -> modSettings["tea_avatar_locked"])
		{
			$this -> context['member']['avatar']["allow_upload"] = FALSE;
			$this -> context['member']['avatar']["allow_external"] = FALSE;
			$this -> context['member']['avatar']["allow_server_stored"] = FALSE;
		}
	}

	function avatar_save($memID, &$profile_vars, &$cur_profile)
	{
		// Remove any attached avatar...
		removeAttachments(array('id_member' => $memID));

		$profile_vars['avatar'] = $_POST['attachment'];

	//	if ($profile_vars['avatar'] == 'http://' || $profile_vars['avatar'] == 'http:///')
	//		$profile_vars['avatar'] = '';
		// Trying to make us do something we'll regret?
	//	var_dump($profile_vars['avatar']);die;
		if (!is_numeric($profile_vars['avatar']))
			return 'bad_avatar';

		// Should we check dimensions?
	//	elseif (!empty($modSettings['avatar_max_height_external']) || !empty($modSettings['avatar_max_width_external']))
	//	{
			// Now let's validate the avatar.
	//		$sizes = url_image_size($profile_vars['avatar']);

	//		if (is_array($sizes) && (($sizes[0] > $modSettings['avatar_max_width_external'] && !empty($modSettings['avatar_max_width_external'])) || ($sizes[1] > $modSettings['avatar_max_height_external'] && !empty($modSettings['avatar_max_height_external']))))
	//		{
				// Houston, we have a problem. The avatar is too large!!
	//			if ($modSettings['avatar_action_too_large'] == 'option_refuse')
	//				return 'bad_avatar';
	//			elseif ($modSettings['avatar_action_too_large'] == 'option_download_and_resize')
	//			{
					require_once($this -> sourcedir . '/Subs-Graphics.php');
					$tea_avatar_size = !empty($this -> modSettings['tea_avatar_size']) ? $this -> modSettings['tea_avatar_size'] : 64;
					if (downloadAvatar('http://image.eveonline.com/Character/'.$profile_vars['avatar'].'_'.$tea_avatar_size.'.jpg', $memID, $tea_avatar_size, $tea_avatar_size))
					{
						$profile_vars['avatar'] = '';
						$cur_profile['id_attach'] = $this -> modSettings['new_avatar_data']['id'];
						$cur_profile['filename'] = $this -> modSettings['new_avatar_data']['filename'];
						$cur_profile['attachment_type'] = $this -> modSettings['new_avatar_data']['type'];
					}
	//			}
	//		}
	//	}
	}
}

$tea = new TEA($db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings);

require_once($sourcedir.'/TEA_TS.php');
require_once($sourcedir.'/TEA_Jabber.php');
$tea -> ts = $teats;
$tea -> jabber = $teaj;
$teats -> tea = $tea;
$teaj -> tea = $tea;

Global $forum_copyright;
$forum_copyright .= '</span></li><li class="copyright"><span><a href="http://code.google.com/p/temars-eve-api/" target="_blank" class="new_win">TEA '.$tea -> version.' &copy; 2009-2011, Temars EVE API</a>';

function edittea($memID)
{
	global $tea, $teainfo, $sourcedir, $context, $settings, $options, $scripturl, $modSettings, $txt, $db_prefix;
	$tea -> UserModifyTEA($memID, $teainfo, $context, $settings, $options, $scripturl, $modSettings, $txt, $db_prefix);
}
function ModifyTEASettings()
{
	global $tea, $sourcedir, $scripturl, $context;
	// Will need the utility functions from here.
	require_once($sourcedir . '/ManageServer.php');

	$context['sub_template'] = 'show_settings';
	$tea -> Settings($scripturl);
}

function template_edittea()
{
	global $tea, $teainfo, $sourcedir, $context, $settings, $options, $scripturl, $modSettings, $txt;
	if($tea -> memid == $context['user']['id'])
	{
		if(allowedTo(array('tea_edit_own', 'tea_edit_any')))
			$edit = TRUE;
	}
	else
	{
		if(allowedTo(array('tea_edit_any')))
			$edit = TRUE;
	}
		
	if(isset($_GET['sa']) && strtolower($_GET['sa']) == "ts")
		return template_edit_tea_ts();
	elseif(isset($_GET['sa']) && strtolower($_GET['sa']) == "jabber")
		return template_edit_tea_jabber();

	echo '
		<form action="', $scripturl, '?action=profile;area=tea;save" method="post" accept-charset="', $context['character_set'], '" name="creator" id="creator">
			<table border="0" width="100%" cellspacing="1" cellpadding="4" align="center" class="bordercolor">
				<tr class="titlebg">
					<td height="26">
						&nbsp;<img src="', $settings['images_url'], '/icons/profile_sm.gif" alt="" border="0" align="top" />&nbsp;
						', $txt['tea_tea'], '
					</td>
				</tr><tr class="windowbg">
					<td class="smalltext" height="25" style="padding: 2ex;">
						', $txt['tea_userinfo'], '
					</td>
				</tr><tr>
					<td class="windowbg2" style="padding-bottom: 2ex;">
						<table border="0" width="100%" cellpadding="3">';
	if(!empty($_SESSION['tea_error']))
	{
		foreach($_SESSION['tea_error'] as $e)
			echo "<tr><td>[ERROR] ".$e."</td></tr>";
		unset($_SESSION['tea_error']);
	}
	if(!$modSettings["tea_enable"])
	{
		echo '<tr><td>'.$txt['tea_disabled'].'</td></tr>';
	}
	else
	{
		if(!empty($teainfo))
		{
			foreach($teainfo as $info)
			{
				foreach($info['charnames'] as $i => $char)
					$charlist[$i] = $char[0];
			}
		}
		echo '<tr><td>'.$txt['tea_charid'].'</td><td>';
		if($edit)
			echo '<select name="tea_charid" id="tea_charid" >';
		if(!empty($charlist))
		{
			if(!isset($charlist[$teainfo[0]['main']]))
				echo '<option value="-", SELECTED>-</option>';
			foreach($charlist as $i => $c)
			{
				if($edit)
					echo '<option value="'.$i.'"',$i == $teainfo[0]['main'] ? 'SELECTED' : '','>'.$c.'</option>';
			}
			if(!$edit)
				echo $charlist[$teainfo[0]['main']];
		}
		else
		{
			if($edit)
				echo '<option value="-", SELECTED>-</option>';
			else
				echo '-';
		}
		if($edit)
			echo '</select>';
		echo '</td></tr>';
		if($edit)
		{
			$teainfo[] = array(
				"userid" => '',
				"api" => '',
			//	"msg" => $msg,
				'charnames' => '',
				'status' => '',
				'mainrule' => '',
				'aditrules' => '',
				'error' => ''
				);
		}
		foreach($teainfo as $i => $info)
		{
			echo '<tr><td colspan="3"><hr class="hrcolor" width="100%" size="1"/></td></tr>';
			echo '<tr><td>
			<b>', $txt['tea_status'], ':</b></td><td>'.$info['status'];
			if($info['status'] == 'API Error')
				echo ' ('.$info['error'].')';
			echo '</td>
				</tr><tr><td><b>', $txt['tea_mainrule'], ':</b></td><td>'.$info['mainrule'].'</td>
				</tr><tr><td><b>', $txt['tea_aditrules'], ':</b></td><td>'.$info['aditrules'].'</td>
				</tr><tr><td>
				<b>', $txt['tea_characters'], ':</b></td><td>';
			if(!empty($info['charnames']))
			{
				echo '<style type="text/css">
					green {color:green}
					blue {color:blue}
					red {color:red}
					</style>';
				$echo = array();
				foreach($info['charnames'] as $char)
				{
					$char[3] = $char[3] != '' ? ' / <blue>'.$char[3].'</blue>' : '';
					$echo[] = '['.$char[1].'] '.$char[0].' (<green>'.$char[2].'</green>'.$char[3].')';
				}
				echo implode('<br>', $echo);
			}
			echo '</td></tr>
				<tr><td>
				<b>', $txt['tea_userid'], ':</b></td>
				<td>';
			if($edit)
			{
				if($info['userid'] == "")
					echo '<input type="text" name="tea_user_id[]" value="'.$info['userid'].'" size="20" />';
				else
				{
					echo '<input type="hidden" name="tea_user_id[]" value="'.$info['userid'].'" size="20" />';
					echo $info['userid'].'</td><td> <input type="checkbox" name="del_api[]" value="'.$info['userid'].'" /> Delete</td>';
				}
				echo '</td>
					</tr><tr>
						<td width="40%">										<b>', $txt['tea_api'], ':</b></td>
							<td><input type="text" name="tea_user_api[]" value="'.$info['api'].'" size="64" />
						</td>
					</tr>';
				if($info['userid'] == "")
					echo '<tr><td><b>TEA Required Options:</b></td>
									<td>
										CharacterInfo, CharacterSheet, FacWarStats
									</td></tr>';

			}
			else
			{
				echo $info['userid'];
				echo '</td>
					</tr><tr>
						<td width="40%">										<b>', $txt['tea_api'], ':</b></td>
							<td>'.$info['api'].'
						</td>
					</tr>';
			}
		}
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