<?php

if (file_exists('../SSI.php') && !defined('SMF'))
{
        require_once('../SSI.php');
}
if (file_exists(dirname(dirname(__FILE__)) . '/SSI.php'))
        require_once(dirname(dirname(__FILE__)) . '/SSI.php');

Global $tea, $db_prefix, $sourcedir, $modSettings, $user_info, $context, $txt, $smcFunc, $settings;

$data = array();
$data2 = array();

$request1 = $smcFunc['db_query']('', 'SELECT a.ID_MEMBER,a.userid FROM {db_prefix}tea_api as a left join {db_prefix}members as b on a.ID_MEMBER=b.id_member where b.id_member is NULL',array());

while ($row = $smcFunc['db_fetch_row']($request1))
{
	$data[] = $row;
}

if (!empty($data))
{
	foreach ($data as $userid)
	{
		echo "deleting orphaned api key : ".$userid[1]."\n";
		$smcFunc['db_query']('', "DELETE FROM {db_prefix}tea_api where userid = {int:userid}",array('userid'=>$userid[1]));
	}
}


$request2 = $smcFunc['db_query']('', "SELECT a.userid FROM {db_prefix}tea_characters AS a LEFT JOIN {db_prefix}tea_api AS b ON a.userid = b.userid WHERE b.userid IS NULL",array());

while ($row = $smcFunc['db_fetch_row']($request2))
{
	$data2[] = $row;
}

if (!empty($data2))
{
	foreach ($data2 as $userid)
	{
		echo "deleting orphaned characters: ".$userid[0]."\n";
		$smcFunc['db_query']('', "DELETE FROM {db_prefix}tea_characters where userid = {int:userid}",array('userid'=>$userid[0]));
	}
}

//SELECT a.ID_MEMBER,a.userid FROM smf_tea_api as a left join smf_members as b on a.ID_MEMBER=b.id_member where b.id_member is NULL

//SELECT a.userid FROM smf_tea_characters AS a LEFT JOIN smf_tea_api AS b ON a.userid = b.userid WHERE b.userid IS NULL
?>
