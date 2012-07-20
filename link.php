<?php

// Jabber Details
	$db_server = 'localhost';
	$db_user = '';
	$db_passwd = '';
	$db_name = '';

	$locked = true; // change this to false to enable script


// ----------------- dont edit below ----------
	function connect($host, $user, $pw, $db)
	{
		$connection = mysql_connect($host, $user, $pw);
		if (!$connection)
		{
			echo 'MySQL Error:'.mysql_error();
			return false;
		}
		mysql_select_db($db);
	}

	function select ($sql, $result_form=MYSQL_NUM)//MYSQL_ASSOC = field names
	{
		$data = "";
		$result = mysql_query($sql);

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
		$return = mysql_query($sql);

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
if($locked)
	die("locked");

	connect($db_server, $db_user, $db_passwd, $db_name);
	$check = select("SELECT username, name FROM ofUser");
	if(!empty($check))
	{
		Require('Settings.php');
		connect($db_server, $db_user, $db_passwd, $db_name);
		foreach($check as $ch)
		{
			$name = str_replace('\20', ' ', $ch[0]);
			$name = str_replace('_', ' ', $name);
			echo $name." = ";
			$acc = select("SELECT userid FROM ".$db_prefix."tea_characters WHERE name = '".mysql_real_escape_string($name)."'");
			if(!empty($acc))
			{
				$id = select("SELECT ID_MEMBER FROM ".$db_prefix."tea_api WHERE userid = '".mysql_real_escape_string($acc[0][0])."'");
				if(!empty($id))
				{
					if(isset($_GET['run']))
						query("INSERT IGNORE INTO ".$db_prefix."tea_jabber_users (id, username, name) VALUES (".$id[0][0].", '".mysql_real_escape_string($ch[0])."', '".mysql_real_escape_string($ch[1])."')");
					echo "success<br>";
					Continue;
				}
			}
			echo "fail<br>";
		}
	}

?>