<?php
// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

if(isset($_GET['force']))
	run_upgrade();
	
	function esamup_check_table($table)
	{
		$fields = esamup_select("EXPLAIN ".$table, MYSQL_ASSOC, FALSE);

		if (!empty($fields))
			Return(TRUE);
		else
			Return(FALSE);
		
		// foreach($columns as $c => $vars)
		// {
			// if(!isset($fcolumns[$c]))
				// $missing[] = $c;
		// }
		// if(!empty($missing))
		// {
			// Return(array(FALSE, $missing));
		// }
		// else
	}

	function esamup_select($sql, $result_form=MYSQL_NUM, $error=TRUE)//MYSQL_ASSOC = field names
	{
		$data = "";
		$result = mysql_query($sql);

		if (!$result)
		{
			//echo $sql;
			if($error)
				echo "<BR>".mysql_error()."<BR>";
			return false;
		}

		if (empty($result))
		{
			return false;
		}

		while ($row = mysql_fetch_array($result, $result_form))
		{
			$data[] = $row;
		}

		mysql_free_result($result);
		return $data;
	}
	function esamup_query($sql)
	{
		$return = mysql_query($sql);

		if (!$return)
		{
			//echo $sql;
			echo mysql_error();
			return false;
		}
		else
		{
			return true;
		}
	}

function run_upgrade()
{
	Global $db_prefix;

	$info['name'] = 'tea_api';
	$info['esam'] = 'esam_api';

	$cs['GLOBAL_REQUIRED_API'] = array('tea_regreq', 1);
	$cs['DISPLAY_EVE_NAME'] = array('tea_usecharname', 1);
	$cs['ADD_CORP_TICKER'] = array('tea_custom_name', 1);
	$cs['DISPLAY_EVE_PORTRAIT'] = array('tea_avatar_enabled', 1);
	//$cs['DISPLAY_CORP_TITLES'] = array('tea_regreq', 1);

	$check = esamup_check_table($db_prefix.$info['name']);
	$esam = esamup_check_table($db_prefix.$info['esam']);
	if($check && $esam)
	{
		// settings
		$settings = esamup_select("SELECT NAME, VALUE FROM ".$db_prefix."esam_settings");
		if(!empty($settings))
		{
			foreach($settings as $s)
			{
				if(isset($cs[$s[0]]) && $s[1] == 'true')
				{
					esamup_query("
						REPLACE INTO ".$db_prefix."settings
							(variable, value)
						VALUES 
						('".$cs[$s[0]][0]."', '".$cs[$s[0]][1]."')");
				}
			}
		}
		// api's
		$apis = esamup_select("SELECT ID_MEMBER, USER_ID, API_KEY FROM ".$db_prefix."esam_api");
		if(!empty($apis))
		{
			foreach($apis as $api)
			{
				esamup_query("
					REPLACE INTO ".$db_prefix."tea_api
						(ID_MEMBER, userid, api)
					VALUES 
					('".$api[0]."', '".$api[1]."', '".$api[2]."')");
			}
		}
		// rules
		$rules = esamup_select("SELECT ID_RULE, TYPE, ROLE, ROLE_ID, GROUP_ID, TITLE, SKILL_ID, SKILL_LVL FROM ".$db_prefix."esam_rules");
		if(!empty($rules))
		{
			Global $sourcedir;
			require_once($sourcedir.'/TEA_SkillDump.php');
			$skills = getSkillArray();
			foreach($rules as $rule)
			{
				$type = NULL;
				$value = NULL;
				$extra = NULL;
				$id = esamup_select("SELECT ruleid FROM ".$db_prefix."tea_rules ORDER BY ruleid DESC LIMIT 1");
				if(!empty($id))
					$id = $id[0][0]+1;
				else
					$id = 1;

				// elseif(is_numeric($_POST['id']))
				// {
					// $id = $_POST['id'];
					// $exists = TRUE;
				// }
				// else
					// die("error id");

				$andor = 'AND';
				//if($andor != "AND" && $andor != "OR")
				//	die("andor must be AND or OR");

				$name = 'ESAM RULE '.$rule[0];

			//	if($_POST['main'] == "main")
			//		$main = 1;
			//	else
					$main = 0;

				Switch(strtoupper($rule[1]))
				{
					case 'P': // pilot rule, ignore entire rule
						Continue 2;
					case 'C':
						$type = "corp";
						$value = $rule[3];
						Break;
					case 'A':
						$type = "alliance";
						$value = $rule[3];
						Break;
					case 'F':
						$type = "militia";
						Switch($rule[3])
						{
							case 500001:
								$value = 'Caldari State';
								Break;
							case 500002:
								$value = 'Minmatar Republic';
								Break;
							case 500003:
								$value = 'Amarr Empire';
								Break;
							case 500004:
								$value = 'Gallente Federation';
								Break;
						}
						Break;
					Default:
						continue 2;
				}
			//	if(isset($types[$_POST['type']]))
			//		$type = $_POST['type'];
			//	else
			//		die("Unknown Type");

			//	if($type == "corp" || $type == "alliance" || $type == "skill" || $type == "role" || $type == "title" || $type == "militia")
			//		$value = mysql_real_escape_string($_POST['value']);

			//	if($type == "skill")
					$extra = '';

			//	if(isset($groups[$_POST['group']]))
					$group = $rule[4];
			//	elseif(!$exists)
			//		die("Invalid Group");

				//if(!$exists)
				esamup_query("INSERT INTO ".$db_prefix."tea_rules (ruleid, name, main, `group`, andor) VALUES ($id, '$name', $main, $group, '$andor')");
				esamup_query("INSERT INTO ".$db_prefix."tea_conditions (ruleid, type, value, extra) VALUES ($id, '$type', '$value', '$extra')");
				//if(!isset($types[$_POST['type']]))
				//	error
				//elseif(!is_numeric($_POST['id']) && $_POST['id'] != "new")
				//	error
				//elseif(!is_numeric($_POST['group']))
				//	error

				if($rule[2] == 'roleDirector')
				{
					$type = 'role';
					$value = 'Director';
					$extra = '';
					esamup_query("INSERT INTO ".$db_prefix."tea_conditions (ruleid, type, value, extra) VALUES ($id, '$type', '$value', '$extra')");
				}
				if($rule[5] != 'null' && !empty($rule[5]))
				{
					$type = 'title';
					$value = $rule[5];
					$extra = '';
					esamup_query("INSERT INTO ".$db_prefix."tea_conditions (ruleid, type, value, extra) VALUES ($id, '$type', '$value', '$extra')");
				}
				if(!empty($rule[6]))
				{
					$type = 'skill';
					$value = $skills[$rule[6]];
					$extra = $rule[7];
					esamup_query("INSERT INTO ".$db_prefix."tea_conditions (ruleid, type, value, extra) VALUES ($id, '$type', '$value', '$extra')");
				}
			}
		}
	}
}	

?>