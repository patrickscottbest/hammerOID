<?php


/* do NOT run this script through a web browser */
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die('<br><strong>This script is only meant to run at the command line.</strong>');
}

// test using:
// "/usr/bin/php" /var/www/html/cacti/plugins/hammerOID/scripts/ss_hammerOID.php '4' 'num_indexes'


global $config;

$no_http_headers = true;

/* display No errors */
error_reporting(1);

if (!isset($called_by_script_server)) {
	include_once(dirname(__FILE__) . '/../../../include/global.php') ;
	include_once(dirname(__FILE__) . '/../../../lib/utility.php');
	include_once(dirname(__FILE__) . '/../../../lib/import.php');
	include_once(dirname(__FILE__) . '/../../../lib/api_automation_tools.php');
	include_once(dirname(__FILE__) . '/../../../lib/data_query.php');
	include_once(dirname(__FILE__) . '/../../../lib/database.php');
	include_once(dirname(__FILE__) . '/../lib/hammerOID_functions.php');

//	array_shift($_SERVER['argv']);
	array_shift($_SERVER['argv']);

        print call_user_func_array('ss_hammerOID_pollers', $_SERVER['argv']);


}
else { 

	//cacti_log("hammerOID - ss_hammerOID.pollers.php called by script server"); 

	include_once($config['base_path'] . '/lib/utility.php');
	include_once($config['base_path'] . '/lib/import.php');
	include_once($config['base_path'] . '/lib/api_automation_tools.php');
	include_once($config['base_path'] . '/lib/data_query.php');
	include_once($config['base_path'] . '/lib/database.php');
	include_once(dirname(__FILE__) . '/../lib/hammerOID_functions.php');

}

function ss_hammerOID_pollers( $cmd, $arg1 = '', $arg2 = '') {


	if (($cmd == 'index')) {

		$arr_index = ss_hammerOID_pollers_indexes();

		foreach ($arr_index as $this_index) {
			//print $this_index . "\n";
			print $this_index;
		}

	} elseif (($cmd == 'num_indexes')) {

		//$arr_index = ss_hammerOID_pollers_indexes();
		//return sizeof($arr_index);
		return '1';

	} elseif ($cmd == 'query') {

		$arr_index = ss_hammerOID_pollers_indexes();
		foreach ($arr_index as $index){
			print $index . '!' . $index . "\n";
		}

	} elseif ($cmd == 'get') {

		//cacti_log("hammerOID - ss_hammerOID.pollers.php Received a \"get\" command"); 

		$exists_already = db_fetch_row("SELECT ".$arg1." FROM poller WHERE id=".$arg2."");
		if (isset($exists_already)) {

			if ($arg1=="total_time"){ return $exists_already['total_time']; }
			else if ($arg1=="snmp"){ return $exists_already['snmp']; }
			else if ($arg1=="script"){ return $exists_already['script']; }
			else if ($arg1=="server"){ return $exists_already['server']; }
			else {
				return 'U';
			}

		} 
		else {
			//cacti_log("hammerOID - ss_hammerOID.pollers.php - \"get\" - couldn't find a matching row"); 
			return 'U' ;	// if i don't have a record of it, then let's just send back U, to be interpreted as NaN. 
					// perhaps next poll cycle scrape it will be there.  Good luck!
		}

	} 
	else {
		//cacti_log("hammerOID - ss_hammerOID.pollers.php Didn't receive a command"); 
	}

} 

function ss_hammerOID_pollers_indexes() {
	//cacti_log("hammerOID - ss_hammerOID.pollers.php Getting list of indexes"); 

        $return_arr = array();
		
	$rows = db_fetch_assoc("SELECT id FROM poller");


        for ($i=0;($i<sizeof($rows));$i++) {
                        $return_arr[$i] = $rows[$i]['id'];
        }

        return $return_arr;
}

