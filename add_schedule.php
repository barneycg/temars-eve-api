<?php
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
        require_once(dirname(__FILE__) . '/SSI.php');
 elseif (!defined('SMF'))
        exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMFs index.php.');

// add the scheduled job.
$smcFunc['db_insert']('replace',
        '{db_prefix}scheduled_tasks',
                array(
                'id_task' => 'int',
                'next_time' => 'int',
                'time_offset' => 'int',
                'time_regularity' => 'int',
                'time_unit' => 'string',
                'disabled' => 'int',
                'task' => 'string',
                ),
                array(
                0, 0, 0, 2, "m", 1, "TEA"
                ),
                array('task')
        );
?>

