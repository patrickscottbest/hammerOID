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

	array_shift($_SERVER['argv']);

        print call_user_func_array('ss_host_polltime', $_SERVER['argv']);


}
else { 

	//cacti_log("hammerOID - ss_hammerOID.php called by script server"); 

	include_once($config['base_path'] . '/lib/utility.php');
	include_once($config['base_path'] . '/lib/import.php');
	include_once($config['base_path'] . '/lib/api_automation_tools.php');
	include_once($config['base_path'] . '/lib/data_query.php');
	include_once($config['base_path'] . '/lib/database.php');
	include_once(dirname(__FILE__) . '/../lib/hammerOID_functions.php');

}

function ss_host_polltime($host_id , $cmd, $arg1 = '', $arg2 = '') {


	if (($cmd == 'index')) {


		print $host_id;  // that's it, just spit back the solitary hostid in place of an ordered index.

	} elseif (($cmd == 'num_indexes')) {


		return '1';  // again, that's all we're giving back.  Just 1 index to choose from, the host ID!

	} elseif ($cmd == 'query') {

		print $host_id . '!' . $host_id . "\n"; // wow it's getting really simple.  Only one query response neeed.

	} elseif ($cmd == 'get') {

		$exists_already = db_fetch_row("SELECT * FROM host WHERE id=".$host_id."");
		if (isset($exists_already)) {

			if ($arg1=="polling_time"){ return $exists_already['polling_time']; }
			else if ($arg1=="min_time"){ return $exists_already['min_time']; }
			else if ($arg1=="max_time"){ return $exists_already['max_time']; }
			else if ($arg1=="cur_time"){ return $exists_already['cur_time']; }
			else if ($arg1=="avg_time"){ return $exists_already['avg_time']; }
			else if ($arg1=="max_oids"){ return $exists_already['max_oids']; }
			else {
				return 'U';
			}

		} 
		else {
			return 'U' ;	// if i don't have a record of it, then let's just send back U, to be interpreted as NaN. 
					// perhaps next poll cycle scrape it will be there.  Good luck!
		}

	} 

} 
