<?php
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
        require_once(dirname(__FILE__) . '/SSI.php');
 elseif (!defined('SMF'))
        exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMFs index.php.');

// del the scheduled jobs.
$smcFunc['db_query']('', 'DELETE FROM {db_prefix}scheduled_tasks WHERE task="TEA_cron"');
$smcFunc['db_query']('', 'DELETE FROM {db_prefix}scheduled_tasks WHERE task="TEA_jabber_cron"');
$smcFunc['db_query']('', 'DELETE FROM {db_prefix}scheduled_tasks WHERE task="TEA_ts_access_cron"');
$smcFunc['db_query']('', 'DELETE FROM {db_prefix}scheduled_tasks WHERE task="TEA_ts_names_cron"');
?>

