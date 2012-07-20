<?php
/*******************************************************************************
	This is a simplified script to add settings into SMF.

	ATTENTION: If you are trying to INSTALL this package, please access
	it directly, with a URL like the following:
		http://www.yourdomain.tld/forum/add_settings.php (or similar.)

================================================================================

	This script can be used to add new settings into the database for use
	with SMF's $modSettings array.  It is meant to be run either from the
	package manager or directly by URL.

*******************************************************************************/

// Set the below to true to overwrite already existing settings with the defaults. (not recommended.)
$overwrite_old_settings = false;

// List settings here in the format: setting_key => default_value.  Escape any "s. (" => \")
$mod_settings = array(
	'example_setting' => '1',
	'example_setting2' => '0',
);

/******************************************************************************/

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif (!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	
	function tea_check_table($table, $columns)
	{
		$fields = tea_select("EXPLAIN ".$table, MYSQL_ASSOC, FALSE);

		if (!empty($fields))
		{
			foreach ($fields AS $field)
			{
				$fcolumns[$field['Field']] = TRUE;
			}
		}
		else
			Return(array(FALSE, FALSE));
		
		foreach($columns as $c => $vars)
		{
			if(!isset($fcolumns[$c]))
				$missing[] = $c;
		}
		if(!empty($missing))
		{
			Return(array(FALSE, $missing));
		}
		else
			Return(array(TRUE));
	}

	function tea_select($sql, $result_form=MYSQL_NUM, $error=TRUE)//MYSQL_ASSOC = field names
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
	function tea_query($sql)
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

$info[1]['old'] = 'eve_api';
$info[1]['name'] = 'tea_api';
$info[1]['primary'] = 'ID_MEMBER, userid';
$tables[1]["ID_MEMBER"] = "INT";
$tables[1]["userid"] = "INT DEFAULT NULL";
$tables[1]["api"] = "VARCHAR(64) DEFAULT NULL";
//$tables[1]["characters"] = "VARCHAR(150) DEFAULT NULL";
//$tables[1]["charid"] = "INT DEFAULT NULL";
$tables[1]["status"] = "VARCHAR(20) DEFAULT NULL";
$tables[1]["matched"] = "VARCHAR(20) DEFAULT NULL";
$tables[1]["errorid"] = "INT(5) DEFAULT NULL";
$tables[1]["error"] = "VARCHAR(254) DEFAULT NULL";
$tables[1]["status_change"] = "INT DEFAULT NULL";
//$tables[1]["auto"] = "INT(1) DEFAULT 1";

$info[2]['old'] = 'eve_characters';
$info[2]['name'] = 'tea_characters';
$info[2]['primary'] = 'userid, charid';
$tables[2]["userid"] = "INT DEFAULT NULL";
$tables[2]["charid"] = "INT DEFAULT NULL";
$tables[2]["name"] = "VARCHAR(50) DEFAULT NULL";
$tables[2]["corpid"] = "INT DEFAULT NULL";
$tables[2]["corp"] = "VARCHAR(50) DEFAULT NULL";
$tables[2]["corp_ticker"] = "VARCHAR(20) DEFAULT NULL";
$tables[2]["allianceid"] = "INT DEFAULT NULL";
$tables[2]["alliance"] = "VARCHAR(50) DEFAULT NULL";
$tables[2]["alliance_ticker"] = "VARCHAR(20) DEFAULT NULL";

$info[3]['old'] = 'eve_rules';
$info[3]['name'] = 'tea_rules';
$info[3]['primary'] = 'ruleid';
$tables[3]["ruleid"] = "INT DEFAULT NULL AUTO_INCREMENT";
$tables[3]["name"] = "VARCHAR(50) DEFAULT NULL";
$tables[3]["main"] = "INT(1) DEFAULT 0";
$tables[3]["andor"] = "VARCHAR(3) DEFAULT 'AND'";
$tables[3]["group"] = "INT DEFAULT NULL";
$tables[3]["enabled"] = "INT(1) DEFAULT 0";

$info[4]['old'] = 'eve_conditions';
$info[4]['name'] = 'tea_conditions';
$info[4]['primary'] = 'id';
$tables[4]["id"] = "INT DEFAULT NULL AUTO_INCREMENT";
$tables[4]["ruleid"] = "INT DEFAULT NULL";
$tables[4]["isisnt"] = "VARCHAR(4) DEFAULT 'is'";
$tables[4]["type"] = "VARCHAR(50) DEFAULT NULL";
$tables[4]["value"] = "VARCHAR(250) DEFAULT NULL";
$tables[4]["extra"] = "VARCHAR(250) DEFAULT NULL";

$info[5]['old'] = 'eve_groups';
$info[5]['name'] = 'tea_groups';
$info[5]['primary'] = 'id';
$tables[5]["id"] = "INT DEFAULT NULL";
$tables[5]["main"] = "INT(1) DEFAULT 1";
$tables[5]["additional"] = "INT(1) DEFAULT 1";

$info[6]['name'] = 'tea_cache';
$info[6]['primary'] = 'address, post';
$tables[6]["address"] = "VARCHAR(100) DEFAULT NULL";
$tables[6]["post"] = "VARCHAR(233) DEFAULT NULL";
$tables[6]["time"] = "INT DEFAULT 0";
$tables[6]["xml"] = "MEDIUMTEXT";

$info[7]['name'] = 'tea_ts_rules';
$info[7]['primary'] = 'id';
$tables[7]["id"] = "INT DEFAULT NULL AUTO_INCREMENT";
$tables[7]["smf"] = "INT DEFAULT 0";
$tables[7]["ts"] = "INT DEFAULT 0";
$tables[7]["tst"] = "VARCHAR(1) DEFAULT NULL";
$tables[7]["nf"] = "VARCHAR(255) DEFAULT NULL";

$info[8]['name'] = 'tea_ts_users';
$info[8]['primary'] = 'id';
$tables[8]["id"] = "INT";
$tables[8]["tsid"] = "VARCHAR(255)";
$tables[8]["dbid"] = "INT";
$tables[8]["name"] = "VARCHAR(255)";
$tables[8]["warnstart"] = "INT";
$tables[8]["lastwarn"] = "INT";

$info[9]['name'] = 'tea_ts_groups';
$info[9]['primary'] = 'id';
$tables[9]["id"] = "VARCHAR(50) DEFAULT NULL";
$tables[9]["value"] = "INT(1) DEFAULT 1";

$info[10]['name'] = 'tea_user_prefs';
$info[10]['primary'] = 'id';
$tables[10]["id"] = "VARCHAR(11) DEFAULT NULL";
$tables[10]["main"] = "INT(11) DEFAULT 0";

$info[11]['name'] = 'tea_jabber_users';
$info[11]['primary'] = 'id';
$tables[11]["id"] = "INT";
$tables[11]["username"] = "VARCHAR(255)";
$tables[11]["name"] = "VARCHAR(255)";

$info[12]['name'] = 'tea_jabber_rules';
$info[12]['primary'] = 'id';
$tables[12]["id"] = "INT DEFAULT NULL AUTO_INCREMENT";
$tables[12]["smf"] = "INT DEFAULT 0";
$tables[12]["jabber"] = "VARCHAR(255) DEFAULT NULL";
$tables[12]["nf"] = "VARCHAR(255) DEFAULT NULL";

$info[13]['name'] = 'tea_jabber_groups';
$info[13]['primary'] = 'id';
$tables[13]["id"] = "VARCHAR(50) DEFAULT NULL";
$tables[13]["value"] = "INT(1) DEFAULT 1";

Global $db_prefix;

require("esam_upgrade.php");

$esaminfo['old'] = 'eve_api';
$esaminfo['name'] = 'tea_api';
$esaminfo['esam'] = 'esam_api';
$checkold = esamup_check_table($db_prefix.$esaminfo['old']);
$check = esamup_check_table($db_prefix.$esaminfo['name']);
$esam = esamup_check_table($db_prefix.$esaminfo['esam']);
if(!$checkold && !$check && $esam) // tea never installed, esam has
{
	$esamupgrade = TRUE;
}

foreach($tables as $i => $table)
{
	$checkold = tea_check_table($db_prefix.$info[$i]['old'], $table);
	$check = tea_check_table($db_prefix.$info[$i]['name'], $table);
	if(($checkold[0] || (!$checkold[0] && $checkold[1])) && !$check[0] && !$check[1]) // if old table exists regardless of if needs changing and new doesnt then rename
		tea_query("RENAME TABLE ".$db_prefix.$info[$i]['old']." TO ".$db_prefix.$info[$i]['name']);
	$check = tea_check_table($db_prefix.$info[$i]['name'], $table);
	if(!$check[0])
	{
		if($check[1])
		{
			foreach($check[1] as $f)
			{
				tea_query("ALTER TABLE ".$db_prefix.$info[$i]['name']." ADD ".$f." ".$table[$f]);
			}
		}
		else
		{
			$sql = "CREATE TABLE ".$db_prefix.$info[$i]['name']." (";
			foreach($table as $c => $d)
				$sql .= " `".$c."` ".$d.",";
			$sql .= " PRIMARY KEY (".$info[$i]['primary']."))";
			tea_query($sql);
		}
	}
	$check = tea_check_table($db_prefix.$info[$i]['name'], $table);
	if(!$check[0])
	{
		if($check[1])
		{
			echo '<b>Error:</b> Database modifications failed!';
			$msg = "These Columns are missing: ";
			$msg .= implode(", ", $check[1]);
			echo $msg;
		}
		else
			echo '<b>Error:</b> Database modifications failed!';
	}
}

if($esamupgrade)
{
	run_upgrade();
}

// try to chmod the xmlhttp file as this is an issue for some
chmod($boarddir."/TEA_xmlhttp.php", 0644);
?>