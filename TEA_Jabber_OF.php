<?php

if (!defined('SMF'))
	die('Hacking attempt...');

Global $teats, $db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings;
loadLanguage('TEA');

require_once($sourcedir.'/TEAC.php');

class TEA_Jabber_DB extends TEAC
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
	
	function db_connect()
	{
		$host = $this -> modSettings["tea_jabber_db_host"];
		$user = $this -> modSettings["tea_jabber_db_user"];
		$pw = $this -> modSettings["tea_jabber_db_pw"];
		$this -> connection = mysql_connect($host, $user, $pw, TRUE);
		if (!$this -> connection)
		{
			echo 'MySQL Error:'.mysql_error($this -> connection);
			return false;
		}
		mysql_select_db($this -> modSettings["tea_jabber_db_db"], $this -> connection);
	}

	function select ($sql, $result_form=MYSQL_NUM)//MYSQL_ASSOC = field names
	{
		$data = "";
		$result = mysql_query($sql, $this -> connection);

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

	function query ($sql)
	{
		$return = mysql_query($sql, $this -> connection);

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
	function get_groups()
	{
		$this -> db_connect();
		$list = $this -> select("SELECT groupName from ofGroup");
		if(!empty($list))
		{
			foreach($list as $l)
			{
				$ret[$l[0]] = $l[0];
			}
		}
		return $ret;
	}

	function get_user_groups($name)
	{
		$this -> db_connect();
		$list = $this -> select("SELECT groupName from ofGroupUser WHERE username = '".mysql_real_escape_string($name)."'");
		if(!empty($list))
		{
			foreach($list as $l)
			{
				$ret[$l[0]] = $l[0];
			}
		}
		return $ret;
	}

	function add_user($uname, $pw, $name, $email, $groups)
	{
		$secret = $this -> modSettings['tea_jabber_secret'];
		$groups = implode(",", $groups);
		$uname = str_replace("'", "_", $uname);
		$uname = str_replace(" ", "_", $uname);

		$url = $this -> modSettings['tea_jabber_admin_url'].'/plugins/userService/userservice?type=add&secret='.$secret.'&username='.$uname.'&password='.$pw.'&name='.$name.'&email='.$email.'&groups='.$groups;
		$url = str_replace(" ", "%20", $url);
		$site = $this -> get_site($url);
		return;
		$this -> db_connect();
		$check = $this -> select("SELECT username from ofUser WHERE username = '".mysql_real_escape_string($name)."'");
		if(!empty($check))
		{
			return("UserName Exists already");
		}
		$this -> query("INSERT INTO ofUser (username, plainPassword, email, creationDate, modificationDate) VALUES ('".mysql_real_escape_string($name)."', '".mysql_real_escape_string($pw)."', '".mysql_real_escape_string($email)."', ".time().", 0)");
	}

	function get_user($name)
	{
		$this -> db_connect();
		$get = $this -> select("SELECT username FROM ofUser WHERE username = '".mysql_real_escape_string($name)."'");
		if(!empty($get))
		{
			Return TRUE;
		}
		else
		{
			Return FALSE;
		}
	}

	function del_user($uname)
	{
		$secret = $this -> modSettings['tea_jabber_secret'];
		$uname = str_replace("'", "_", $uname);
		$uname = str_replace(" ", "_", $uname);

		$url = $this -> modSettings['tea_jabber_admin_url'].'/plugins/userService/userservice?type=delete&secret='.$secret.'&username='.$uname;
		$site = $this -> get_site($url);
		return;
		$this -> db_connect();
		$this -> query("DELETE FROM ofUser WHERE username = '".mysql_real_escape_string($name)."'");
		$this -> query("DELETE FROM ofGroupUser WHERE username = '".mysql_real_escape_string($name)."'");
		$this -> query("DELETE FROM ofVCard WHERE username = '".mysql_real_escape_string($name)."'");
	}

	function update_user($uname, $pw, $name, $email, $groups)
	{
		$secret = $this -> modSettings['tea_jabber_secret'];
		$groups = implode(",", $groups);
		$uname = str_replace("'", "_", $uname);
		$uname = str_replace(" ", "_", $uname);
		if($pw)
			$pws = '&password='.$pw;

		$url = $this -> modSettings['tea_jabber_admin_url'].'/plugins/userService/userservice?type=update&secret='.$secret.'&username='.$uname.$pws.'&name='.$name.'&email='.$email.'&groups='.$groups;
		$url = str_replace(" ", "%20", $url);
		$site = $this -> get_site($url);
		return;
		$this -> db_connect();
		$this -> query("UPDATE ofUser SET plainPassword = '".mysql_real_escape_string($pw)."' WHERE username = '".mysql_real_escape_string($name)."'");
	}

	function add_to_group($name, $group)
	{
		$this -> db_connect();
		$groups = $this -> get_groups();
		if(!isset($groups[$group]))
			Return FALSE;
		$this -> query("INSERT INTO ofGroupUser (groupName, username, administrator) VALUES ('".mysql_real_escape_string($group)."', '".mysql_real_escape_string($name)."', 0)");
		Return TRUE;
	}

	function vcard($uname, $name)
	{
		$this -> db_connect();
		$vcard = '<vCard xmlns="vcard-temp" version="2.0" prodid="-//HandGen//NONSGML vGen v1.0//EN">
<FN>'.$name.'</FN>
<NICKNAME>'.$name.'</NICKNAME>
</vCard>';
		$this -> query("REPLACE INTO ofVCard (username, vcard) VALUES ('".mysql_real_escape_string($uname)."', '".mysql_real_escape_string($vcard)."')");
	}
}

$jabber_db = new TEA_Jabber_DB($db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings);

?>