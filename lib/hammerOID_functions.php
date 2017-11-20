<?php

function hammerOID_debug($message) {
        global $debug, $web, $config;
        //include_once($config['base_path'] . '/lib/functions.php');

        if (read_config_option('hammerOID_debug') == 'on' || $debug) {
                cacti_log($message, false, 'HAMMEROID_DEBUG');
        }

}


/* set_hammerOID_config_option - sets/updates a cacti config option with the given value.
   @arg $config_name - the name of the configuration setting as specified $settings array
   @arg $value       - the values to be saved
   @returns          - void */
function set_hammerOID_config_option($config_name, $value) {
//      include_once($config['base_path'] . '/lib/functions.php');
//      cacti_log('hammerOID - setting config option: '.$config_name.' value: '.$value);
        db_execute_prepared('REPLACE INTO hammerOID_settings SET name = ?, value = ?', array($config_name, $value));
}



/* read_hammerOID_config_option - finds the current value of a Cacti configuration setting
   @arg $config_name - the name of the configuration setting as specified $settings array
     in 'include/global_settings.php'
   $returns - the current value of the configuration option */
function read_hammerOID_config_option($config_name, $force = false) {

        //$db_setting = db_fetch_row_prepared('SELECT value FROM hammerOID_settings WHERE name = ?', array($config_name), false);
         $db_setting = db_fetch_cell("SELECT value FROM hammerOID_settings WHERE name='$config_name'");

        //return $db_setting["$config_name"];
        return $db_setting;
}


?>
