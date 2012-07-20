<?php
if (file_exists('../SSI.php') && !defined('SMF'))
	require_once('../SSI.php');
if (file_exists(dirname(dirname(__FILE__)) . '/SSI.php'))
	require_once(dirname(dirname(__FILE__)) . '/SSI.php');

Global $sourcedir;
require_once($sourcedir."/TEA.php");
$tea -> update_api(FALSE);

?>