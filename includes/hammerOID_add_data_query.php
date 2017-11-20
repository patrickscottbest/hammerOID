<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2017 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/* do NOT run this script through a web browser */
//if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
//	die('<br><strong>This script is only meant to run at the command line.</strong>');
//}

/* We are not talking to the browser */
//$no_http_headers = true;



function hammerOID_add_data_query ($host_id, $data_query_id, $reindex_method) {

	global $config;


	if (!is_numeric($host_id)) {
		hammerOID_debug("hammerOID: ERROR: You must supply a valid host-id to run this script!\n");
		return(1);
	}

	if (!is_numeric($data_query_id)) {
		hammerOID_debug("hammerOID: ERROR: You must supply a numeric data-query-id for all hosts!\n");
		return(1);
	}

	include_once($config['base_path'].'/include/global.php');
	include_once($config['base_path'].'/lib/api_automation_tools.php');
	include_once($config['base_path'].'/lib/data_query.php');
        include_once($config['base_path'].'/plugins/hammerOID/lib/hammerOID_functions.php');

	//include_once(dirname(__FILE__) . '/../../../include/global.php') ;
	//include_once(dirname(__FILE__) .'/../../../lib/api_automation_tools.php');
	//include_once(dirname(__FILE__) .'/../../../lib/data_query.php');

	/*
	 * verify valid host id and get a name for it
	 */
	$host_name = db_fetch_cell('SELECT hostname FROM host WHERE id = ' . $host_id);
	if (!isset($host_name)) {
		hammerOID_debug("hammerOID: ERROR: Unknown Host Id ($host_id)\n");
			
		//exit(1);
		return;
	}

	/*
	 * verify valid data query and get a name for it
	 */
	$data_query_name = db_fetch_cell('SELECT name FROM snmp_query WHERE id = ' . $data_query_id);
	if (!isset($data_query_name)) {
		hammerOID_debug("ERROR: Unknown Data Query Id ($data_query_id)\n");
		//exit(1);
		return;
	}

	/*
	 * Now, add the data query and run it once to get the cache filled
	 */
	$exists_already = db_fetch_cell("SELECT host_id FROM host_snmp_query WHERE host_id=$host_id AND snmp_query_id=$data_query_id AND reindex_method=$reindex_method");
	if ((isset($exists_already)) &&
		($exists_already > 0)) {
		hammerOID_debug("ERROR: Data Query is already associated for host: ($host_id: $host_name) data query ($data_query_id: $data_query_name)");
		//echo "ERROR: Data Query is already associated for host: ($host_id: $host_name) data query ($data_query_id: $data_query_name) reindex method ($reindex_method: " . $reindex_types[$reindex_method] . ")\n";
		hammerOID_debug("Data Query is already associated for host: ($host_id: $host_name)  ");
		//exit(1); // don't exit man, bad news for the rest of the script.
		return;
	} else {
		db_execute('REPLACE INTO host_snmp_query 
			(host_id,snmp_query_id,reindex_method) 
			VALUES (' . 
				$host_id        . ',' . 
				$data_query_id  . ',' . 
				$reindex_method . ')');

		/* recache snmp data */
		run_data_query($host_id, $data_query_id);
	}

	if (is_error_message()) {
		hammerOID_debug("hammerOID: ERROR: Failed to add this data query for host ($host_id: $host_name) data query ($data_query_id: $data_query_name)");
		//cacti_log("hammerOID: ERROR: Failed to add this data query for host ($host_id: $host_name) data query ($data_query_id: $data_query_name)" . $reindex_types[$reindex_method] . ")\n";
		//exit(1); // don't exit man, bad news for the rest of the script.
		return;
	} else {
		//echo "Success - Host ($host_id: $host_name) data query ($data_query_id: $data_query_name) reindex method ($reindex_method: " . $reindex_types[$reindex_method] . ")\n";
		hammerOID_debug("hammerOID : Success - Host ($host_id: $host_name) data query ($data_query_id: $data_query_name) \n");
		return;
	}
}
