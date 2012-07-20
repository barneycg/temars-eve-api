<?php

$txt['tea_getchar'] = 'Get Characters';

require_once("../Sources/TEAC.php");
$teac = new TEAC;
$chars = $teac -> get_api_characters($_GET['userid'], $_GET['api']);

if(!empty($chars))
{
	echo '<select name="tea_charid" id="tea_charid" >';
	foreach($chars as $char)
	{
		echo '<option value="'.$char['charid'].'">'.$char['name'].'</option>';
	}
}
else
{
	$error = $teac -> get_error($teac -> data);
	echo 'Error '.$error[0].' ('.$error[1].')<Br><select name="tea_char"><option value="-">-</option>';
}
echo '</select> <button type="button" onclick="javascript: getchars()">'.$txt['tea_getchar'].'</button>';
?>