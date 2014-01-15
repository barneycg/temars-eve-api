<?php


if (!empty($argv[1]) && $argv[1]=='debug')
	$debug=TRUE;
else
	$debug=FALSE;
if (file_exists('../SSI.php') && !defined('SMF'))
{
	require_once('../SSI.php');
}
if (file_exists(dirname(dirname(__FILE__)) . '/SSI.php'))
	require_once(dirname(dirname(__FILE__)) . '/SSI.php');

Global $sourcedir;
require_once($sourcedir."/TEA.php");
$apis_done = $tea -> update_api(FALSE);
if ($debug)
	var_dump($apis_done);


?>
