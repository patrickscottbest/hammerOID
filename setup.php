<?php
/*
 ex: set tabstop=4 shiftwidth=4 autoindent:
 +-------------------------------------------------------------------------+
 | Copyright (C) 2017 Patrick Best                                         |
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
 | https://github.com/patrickscottbest/hammerOID                           |
 | http://docs.cacti.net/plugin:hammerOID                                  |
 +-------------------------------------------------------------------------+
*/

function plugin_hammerOID_install () {
	global $config;

	api_plugin_register_hook('hammerOID', 'config_settings', 'hammerOID_config_settings', 'setup.php');
	api_plugin_register_hook('hammerOID', 'api_device_new', 'hammerOID_device_new', 'api/hammerOID_device_new.php');
	api_plugin_register_hook('hammerOID', 'poller_bottom', 'hammerOID_poller_bottom', 'api/hammerOID_poller_bottom.php');

        api_plugin_register_hook('hammerOID', 'top_header_tabs',       'hammerOID_show_tab', 'setup.php');
        api_plugin_register_hook('hammerOID', 'top_graph_header_tabs', 'hammerOID_show_tab', 'setup.php');
	
	api_plugin_register_realm('hammerOID', 'hammerOID.php', 'hammerOID Control Panel', 1);

//	api_plugin_register_realm('hammerOID', 'mactrack_ajax_admin.php,mactrack_devices.php,mactrack_snmp.php,mactrack_sites.php,mactrack_device_types.php,mactrack_utilities.php,mactrack_macwatch.php,mactrack_macauth.php,mactrack_vendormacs.php', 'MacTrack Administrator', 1);


	/* no need for hooks on these right now. 
	// need to pop in our spine verbosity level here 

	api_plugin_register_hook('hammerOID','poller_bottom');

	// we'll need this for further integrations , make spine practice pollings and scrape the output with verbosity set.
		api_plugin_register_hook('hammerOID', 'poller_command_args', 'poller_extra_args', 'includes/polling_extra_args.php');


*/


		include_once($config['base_path'] . '/lib/utility.php');
		include_once($config['base_path'] . '/lib/import.php');
		include_once($config['base_path'] . '/lib/template.php');
		include_once($config['base_path'] . '/plugins/hammerOID/includes/hammerOID_add_data_query.php');
		include_once($config['base_path'] . '/plugins/hammerOID/lib/hammerOID_functions.php');

		cacti_log("hammerOID: Begin Plugin Installation ");

		/////////////////////////// DEBUG auto on , only for Dev branch
		set_config_option('hammerOID_debug','on');
		cacti_log("hammerOID: Installation - Auto Debug set to ON");
		/////////////////////////// 

		cacti_log("hammerOID: Installation - Setting Up SQL Tables");
		hammerOID_setup_table_new();


		cacti_log("hammerOID: Installation - Importing Templates");
		$template_files = array("cacti_data_query_hammeroid_-_global_data_query.xml", 
					"cacti_data_query_hammeroid_-_perhost_data_query.xml",
					"cacti_graph_template_hammeroid_-_per-host_cacti_max-oids_graph.xml",
					"cacti_graph_template_hammeroid_-_per-host_cacti_ping_time_graph.xml",
					"cacti_graph_template_hammeroid_-_per-host_cacti_polling_time_graph.xml",
					"cacti_graph_template_hammeroid_-_cacti_global_number_script_queries_graph.xml",
					"cacti_graph_template_hammeroid_-_cacti_global_number_server_queries_graph.xml",
					"cacti_graph_template_hammeroid_-_cacti_global_number_snmp_queries_graph.xml",
					"cacti_graph_template_hammeroid_-_cacti_global_poll_time_graph.xml");

		
		foreach ($template_files as $file){
			hammerOID_debug('hammerOID: Import XML file: '.$file);
			$xml_data = file_get_contents($config['base_path'].'/plugins/hammerOID/templates/'.$file);
			hammerOID_debug('hammerOID: Import XML file size: '.strlen($xml_data));
				//import_xml_data($xml_data, $import_as_new, $profile_id, $remove_orphans);
			import_xml_data($xml_data, true, 1, true);
		};

		// go through each host presently configured hosts and add a new datasource and graph

		include_once($config['base_path'].'/lib/api_automation_tools.php');
		include_once($config['base_path'].'/lib/data_query.php');




		$myGraphTemplatesGlobal=array(	
		'hammerOID - Cacti Global Number Script Queries Graph' => '' ,
		'hammerOID - Cacti Global Number Server Queries Graph' => '',
		'hammerOID - Cacti Global Number SNMP Queries Graph' => '',
		'hammerOID - Cacti Global Poll Time Graph' => '');

		$myGraphTemplatesPerHost=array(	
		'hammerOID - Per-Host Cacti Max-OIDs Graph' => '',
		'hammerOID - Per-Host Cacti Ping Time Graph' => '',
		'hammerOID - Per-Host Cacti Polling Time Graph' => '');

		$myDataQueryID=array(
		'hammerOID - Global Data Query' => '',
		'hammerOID - PerHost Data Query' => '');

                $graphTemplates = getGraphTemplates();

		foreach ($graphTemplates as $id => $name) {
			hammerOID_debug("looking for myGraphTemplatesGlobal from id: $id and name: $name");

			foreach ($myGraphTemplatesGlobal as $string => $this){
				// something like "38"
				hammerOID_debug("hammerOID - myGraphTemplatesGlobal as string: $string");

				if (preg_match('/'.$string.'$/', $name )) {
					hammerOID_debug("Found match from id: $id and name: $name TO STRING: $string");


					$myGraphTemplatesGlobal["$string"]="$id";
				}
			}
		}
		hammerOID_debug('hammerOID - setting config option - myGraphTemplatesGlobal');
		set_hammerOID_config_option('hammerOID_myGraphTemplatesGlobal', serialize($myGraphTemplatesGlobal) );

                $graphTemplates = getGraphTemplates();
		foreach ($graphTemplates as $id => $name) {
			hammerOID_debug("looking for myGraphTemplatesPerHost from id: $id and name: $name");

			foreach ($myGraphTemplatesPerHost as $string => $this){
				// something like "38"
				if (preg_match('/'.$string.'$/', $name )) {


					hammerOID_debug("Found match from id: $id and name: $name TO STRING: $string");
					$myGraphTemplatesPerHost["$string"]="$id";
				}
			}
		}
		hammerOID_debug('hammerOID - setting config option - myGraphTemplatesPerHost');
		set_hammerOID_config_option('hammerOID_myGraphTemplatesPerHost', serialize($myGraphTemplatesPerHost) );

                $SNMPQueries = getSNMPQueries();

		foreach ($SNMPQueries as $id => $name) {
			hammerOID_debug("hammerOID - building myDataQueryID against id: $id  name: $name");
			
			foreach ($myDataQueryID as $string => $this){
				hammerOID_debug("hammerOID - searching myDataQueryID against string: $string ");

				if (preg_match('/'.$string.'$/', $name)) {
					hammerOID_debug("hammerOID - found a myDataQueryID against string: $string ");

					$myDataQueryID[$string]=$id;
				}
			}
		}
		hammerOID_debug('hammerOID - setting config option - hammerOID_myDataQueryID');
		set_hammerOID_config_option('hammerOID_myDataQueryID', serialize($myDataQueryID) );


		hammerOID_debug("hammerOID - now working on getting Data Query Types");

		foreach ($myGraphTemplatesGlobal as $key => $value){
			hammerOID_debug("hammerOID - myGraphTemplatesGlobal as key: $key value: $value");
			$myDataQueryType[$value] = db_fetch_cell("SELECT id FROM snmp_query_graph WHERE graph_template_id=$value");
			hammerOID_debug("hammerOID - myDataQueryType[$value] is now ".$myDataQueryType[$value]."");
		};
		foreach ($myGraphTemplatesPerHost as $key => $value){
			hammerOID_debug("hammerOID - myGraphTemplatesPerHost as key: $key value: $value");
			$myDataQueryType[$value] = db_fetch_cell("SELECT id FROM snmp_query_graph WHERE graph_template_id=$value");
			hammerOID_debug("hammerOID - myDataQueryType[$value] is now ".$myDataQueryType[$value]."");

		};
		set_hammerOID_config_option('hammerOID_myDataQueryType', serialize($myDataQueryType));
		hammerOID_debug('setting config option - hammerOID_myDataQueryType');
		cacti_log("hammerOID: Installation - ID values have been set and stored");

		// sanity check should be done here ...
		// if we didn't find our IDs and types, something went wrong with the import and we're skunked.  exit.

		cacti_log("hammerOID: Installation - Creating Graphs");
		//Let's wrap through each host currently defined and add our datasource and graph.
		$hosts = getHosts();
		$php = read_config_option("path_php_binary");
		$reindexmethod=0;
		foreach ($hosts as $hostID => $hostsArray) {

			hammerOID_debug("hammerOID: Now creating graph from template for hostID: $hostID ");

			foreach ($myDataQueryID as $name => $value){
				hammerOID_add_data_query("$hostID", "$value", $reindexmethod);
			};
			hammerOID_debug("hammerOID: Done adding data queries to : $hostID ");


			/* gleaned from cli commmand: 
			#> php ../../cli/add_graphs.php --host-id=1 
							--graph-template-id=38 
							--graph-type=ds 
							--snmp-query-id=8 
							--snmp-query-type-id=27 
							--snmp-field=hostid 
							--snmp-value=1  <--- same as host-id for the PerHost graphs
				Graph Added - graph-id: (5) - data-source-ids: (6, 6)


			 This is only the per-host graphs.
			*/

			foreach($myGraphTemplatesPerHost as $graphName => $graphID){
				
			hammerOID_debug("hammerOID: Adding PerHost graphID: $graphID graphName: $graphName to hostID: $hostID ");

				//look what i've done for DataQueryType id. What an awful mess.
				$exec_output=array();
				exec($php ." ".$config['base_path'] ."/cli/add_graphs.php --host-id=$hostID".
					" --graph-type=ds --graph-template-id=".$graphID.
					" --snmp-query-id=".$myDataQueryID['hammerOID - PerHost Data Query'].
					" --snmp-query-type-id=".$myDataQueryType["".$graphID.""].
					" --snmp-field=hostid --snmp-value=".$hostID , $exec_output);
				foreach ($exec_output as $exec_output_line){ hammerOID_debug("hammerOID: $exec_output_line"); };
			
			};

			// explore this later... maybe a better way than executing an external cli script...
			//exec($php . " " . $extra_args . " > /dev/null &");
			//create_complete_graph_from_template($hostID, $myGraphTemplateID, $snmp_query_array, $empty);
		} 
			hammerOID_debug("hammerOID: Done adding PerHost graphs to ALL hosts ");

		//now let's dump each Global Poller graph into the localhost graphs
		$hostID=1;
		foreach($myGraphTemplatesGlobal as $graphName => $graphID){
			hammerOID_debug("hammerOID: Adding Global graphID: $graphID graphName: $graphName to hostID: $hostID ");


			//look what i've done for DataQueryType. What an awful mess.
				$exec_output=array();
				exec($php ." ".$config['base_path'] ."/cli/add_graphs.php --host-id=$hostID".
					" --graph-type=ds --graph-template-id=".$graphID.
					" --snmp-query-id=".$myDataQueryID['hammerOID - Global Data Query'].
					" --snmp-query-type-id=".$myDataQueryType["".$graphID.""].
					" --snmp-field=hostid --snmp-value=".$hostID , $exec_output);
				foreach ($exec_output as $exec_output_line){ hammerOID_debug("hammerOID: $exec_output_line"); };
		
		};
			hammerOID_debug("hammerOID: Done adding Global graphs to hostID: $hostID");

		cacti_log("hammerOID: Installation - Complete!");

}

function plugin_hammerOID_uninstall () {

	global $config;

	include_once($config['base_path'] . '/lib/utility.php');
	include_once($config['base_path'] . '/lib/api_graph.php');
	include_once($config['base_path'] . '/lib/api_tree.php');
	include_once($config['base_path'] . '/lib/api_data_source.php');
	include_once($config['base_path'] . '/lib/api_aggregate.php');
	include_once($config['base_path'] . '/lib/api_automation.php');
	include_once($config['base_path'] . '/lib/api_automation_tools.php');
	include_once($config['base_path'] . '/lib/api_device.php');
	include_once($config['base_path'] . '/lib/template.php');
	include_once($config['base_path'] . '/lib/html_tree.php');
	include_once($config['base_path'] . '/lib/html_form_template.php');
	include_once($config['base_path'] . '/lib/rrd.php');
	include_once($config['base_path'] . '/lib/data_query.php');
	include_once($config['base_path'] . '/plugins/hammerOID/lib/hammerOID_functions.php');

	cacti_log("hammerOID: Begin Uninstall");
	hammerOID_debug("Preparing to Uninstall hammerOID.");

	$myGraphTemplatesPerHost = unserialize(read_hammerOID_config_option('hammerOID_myGraphTemplatesPerHost'));
	$myGraphTemplatesGlobal = unserialize(read_hammerOID_config_option('hammerOID_myGraphTemplatesGlobal'));
	$myDataQueryID = unserialize(read_hammerOID_config_option('hammerOID_myDataQueryID'));


	hammerOID_debug("Building the BLOB.");
	
	//BUILD THE BLOB !  BUILD THE BLOB !
	//BUILD THE BLOB !  BUILD THE BLOB !
	//BUILD THE BLOB !  BUILD THE BLOB !
	//THIS IS CRAZY, but the only way i can think of to destroy so many graphs and their datasources automagically.
	//Construct the serialised meat, then prepend.

	$myIndex=0;
	$blob_selected_items="{";
	foreach($myGraphTemplatesPerHost as $key => $value){
		hammerOID_debug("Building the BLOB. PerHost key: $key value: $value");

		$graphid_array = db_fetch_assoc("SELECT id from graph_local WHERE graph_template_id=".$value."");

		foreach($graphid_array as $graphid){
				hammerOID_debug("building up the special blob. this value is". $graphid['id']);
				// will look like this syntax: 'a:3:{i:0;s:2:"86";i:1;s:2:"88";i:2;s:2:"87";}'
			$blob_selected_items .= "i:".$myIndex.";s:".strlen($graphid['id']).":\"".$graphid['id']."\";";
					
			$myIndex++;

		}

	}
	foreach($myGraphTemplatesGlobal as $key => $value){
		hammerOID_debug("Building the BLOB. Global key: $key value: $value");

		$graphid_array = db_fetch_assoc("SELECT id from graph_local WHERE graph_template_id=".$value."");

		foreach($graphid_array as $graphid){
				hammerOID_debug("building up the special blob. this value is". $graphid['id']);
				// will look like this syntax: 'a:3:{i:0;s:2:"86";i:1;s:2:"88";i:2;s:2:"87";}'
			$blob_selected_items .= "i:".$myIndex.";s:".strlen($graphid['id']).":\"".$graphid['id']."\";";
					
			$myIndex++;

		}

	}
	hammerOID_debug("Building the BLOB. Prepending Items. Item Count: $myIndex");

	//prepend item count
        $blob_selected_items.="}";
        $blob_selected_items="a:".$myIndex.":".$blob_selected_items;

	hammerOID_debug("The blob_selected_items as built is : ".$blob_selected_items);


//////////////////////////////////////////////////////
//////////////////////////////////////////////////////
// build a very specific string to pass to the delete function, as nabbed from core "graphs.php"
// We're presenting a serialized item value, which needs to be hacked into peices and executed here.

//as posted: 
//delete_type=2&action=actions&selected_items=a%3A3%3A%7Bi%3A0%3Bs%3A2%3A%2286%22%3Bi%3A1%3Bs%3A2%3A%2288%22%3Bi%3A2%3Bs%3A2%3A%2287%22%3B%7D&drp_action=1&__csrf_magic=sid%3Aae108b113b4d23f74574aebf2628e9621bd58a77%2C1507648987

// simulate variable returned from web browsing session
//0000   73 65 6c 65 63 74 65 64 5f 69 74 65 6d 73 3d 61  selected_items=a
//0010   25 33 41 33 25 33 41 25 37 42 69 25 33 41 30 25  %3A3%3A%7Bi%3A0%
//0020   33 42 73 25 33 41 32 25 33 41 25 32 32 38 36 25  3Bs%3A2%3A%2286%
//0030   32 32 25 33 42 69 25 33 41 31 25 33 42 73 25 33  22%3Bi%3A1%3Bs%3
//0040   41 32 25 33 41 25 32 32 38 38 25 32 32 25 33 42  A2%3A%2288%22%3B
//0050   69 25 33 41 32 25 33 42 73 25 33 41 32 25 33 41  i%3A2%3Bs%3A2%3A
//0060   25 32 32 38 37 25 32 32 25 33 42 25 37 44 26     %2287%22%3B%7D&

//////////////////////////////////////////////////////
//////////////////////////////////////////////////////

	$selected_items = sanitize_unserialize_selected_items($blob_selected_items);

foreach($selected_items as $thing) {
	hammerOID_debug("hammerOID: selected item to delete is: ".$thing);
}

//                if ($selected_items != false) {

// of course we're dropping everything, so drop action will always be 2
//                        if (get_request_var('drp_action') == '1') { // delete
//                                if (!isset_request_var('delete_type')) {
//                                        set_request_var('delete_type', 1);
// 				}

//                                switch (get_nfilter_request_var('delete_type')) {
//                                        case '2': // delete all data sources referenced by this graph
                                                $all_data_sources = array_rekey(db_fetch_assoc('SELECT DISTINCT dtd.local_data_id
                                                        FROM data_template_data AS dtd
                                                        INNER JOIN data_template_rrd AS dtr
                                                        ON dtd.local_data_id=dtr.local_data_id
                                                        INNER JOIN graph_templates_item AS gti
                                                        ON dtr.id=gti.task_item_id
                                                        WHERE ' . array_to_sql_or($selected_items, 'gti.local_graph_id') . '
                                                        AND dtd.local_data_id > 0'), 'local_data_id', 'local_data_id');


                                                $data_sources = array_rekey(db_fetch_assoc('SELECT dtd.local_data_id,
                                                        COUNT(DISTINCT gti.local_graph_id) AS graphs
                                                        FROM data_template_data AS dtd
                                                        INNER JOIN data_template_rrd AS dtr
                                                        ON dtd.local_data_id=dtr.local_data_id
                                                        INNER JOIN graph_templates_item AS gti
                                                        ON dtr.id=gti.task_item_id
                                                        WHERE dtd.local_data_id > 0
                                                        GROUP BY dtd.local_data_id
                                                        HAVING graphs = 1
                                                        AND ' . array_to_sql_or($all_data_sources, 'local_data_id')), 'local_data_id', 'local_data_id');

                                                if (sizeof($data_sources)) {
                                                        api_data_source_remove_multi($data_sources);
                                                        api_plugin_hook_function('data_source_remove', $data_sources);
                                                }

                                                api_graph_remove_multi($selected_items);
                                                api_plugin_hook_function('graphs_remove', $selected_items);

                                                /* Remove orphaned data sources */
                                                $data_sources = array_rekey(db_fetch_assoc('SELECT DISTINCT dtd.local_data_id
                                                        FROM data_template_data AS dtd
                                                        INNER JOIN data_template_rrd AS dtr
                                                        ON dtd.local_data_id=dtr.local_data_id
                                                        LEFT JOIN graph_templates_item AS gti
                                                        ON dtr.id=gti.task_item_id
                                                        WHERE ' . array_to_sql_or($all_data_sources, 'dtd.local_data_id') . '
                                                        AND gti.local_graph_id IS NULL
                                                        AND dtd.local_data_id > 0'), 'local_data_id', 'local_data_id');

                                                if (sizeof($data_sources)) {
                                                        api_data_source_remove_multi($data_sources);
                                                        api_plugin_hook_function('data_source_remove', $data_sources);
                                                }
	cacti_log("hammerOID: All hammerOID generated Graphs and Data Sources have been removed.");


	hammerOID_debug("hammerOID: Deleting data Queries");
	//delete data queries
	$hosts = getHosts();
	foreach ($hosts as $hostID => $hostName) {
		hammerOID_debug("hammerOID: Deleting data Query from hostID: $hostID hostname: ".$hostName['hostname']."");
		foreach ($myDataQueryID as $key => $value){
			api_device_dq_remove($hostID, $value);
		};
	};
	cacti_log("hammerOID: Data Queries have been removed from all hosts.");


	// delete the one time database add for settings.
	db_execute('DROP TABLE IF EXISTS `hammerOID_settings`');
	hammerOID_debug("Dropped the hammerOID_settings table.");
	cacti_log("hammerOID: Database table hammerOID_settings has been removed.");

	
		$automation_hosts=db_fetch_assoc("SELECT max_oids_original,id FROM hammerOID_automation");
		foreach ($automation_hosts as $key => $value){

                        // let's put the original host OIDs to the way we found it.
                        db_execute("UPDATE host ".
                               " SET max_oids = ".$value['max_oids_original'].
                               " WHERE id = ".$value['id']."");

                        // and let's remove the entry from our table.  Bye!
                        db_execute("DELETE FROM hammerOID_automation 
                                WHERE id=".$value['id']."");
		}
		cacti_log("hammerOID: Uninstall - All original OIDs have been restored.");

	// delete the one time database add for settings.
	db_execute('DROP TABLE IF EXISTS `hammerOID_automation`');
	hammerOID_debug("Dropped the hammerOID_automation table.");
	cacti_log("hammerOID: Database table hammerOID_automation has been removed.");

        // Remove items from the settings table
        db_execute('DELETE FROM settings WHERE name LIKE "%hammerOID%"');
	hammerOID_debug("Dropped the relevant cacti settings table entries.");
	cacti_log("hammerOID: Removed settings.");

	cacti_log("hammerOID: Completed Uninstall. Goodbye.");

}

function plugin_hammerOID_check_config () {
	// Here we will check to ensure everything is configured
	return true;
}

function hammerOID_version () {
	return plugin_hammerOID_version();
}

function plugin_hammerOID_version () {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/hammerOID/INFO', true);
	return $info['info'];
}


function hammerOID_db_table_exists($table) {
        return sizeof(db_fetch_assoc("SHOW TABLES LIKE '$table'"));
}


function hammerOID_setup_table_new () {
        if (!hammerOID_db_table_exists('hammerOID_settings')) {
                db_execute("CREATE TABLE `hammerOID_settings` (
                        `name` varchar(50) NOT NULL,
                        `value` varchar(4096) NOT NULL,
                        PRIMARY KEY  (`name`)) ENGINE=InnoDB;");
        }



        if (!hammerOID_db_table_exists('hammerOID_automation')) {
                db_execute("CREATE TABLE `hammerOID_automation` (
                        `id` mediumint(8),
                        `max_oids_original` int(12),
                        `max_oids_last` int(12),
                        `polling_time_mean` double,
                        `polling_time_variance` varchar(4096) NOT NULL,
                        `polling_time_deviation` double,
                        `polling_time_last` double,
                        `last_oids_delta` double,
                        `poll_cycle_counter` double,
                        PRIMARY KEY  (`id`)) ENGINE=InnoDB;");
	}

}

function hammerOID_config_settings () {
        global $tabs, $settings, $item_rows, $config;


        $hammerOID_log_path = $config['base_path'] . '/log/';
        $tabs['hammerOID'] = 'hammerOID';
        $settings['hammerOID'] = array(
                'hammerOID_advanced_header' => array(
                        'friendly_name' => 'hammerOID Advanced Options',
                        'method' => 'spacer',
                        ),
                'hammerOID_automation_enabled' => array(
                        'friendly_name' => 'hammerOID automation enabled',
                        'description' => 'This enables the ongoing automatic adjustment of maximum OIDs from poll to poll.',
                        'method' => 'checkbox'
                        ),
                'hammerOID_learning_holddown' => array(
                        'friendly_name' => 'hammerOID learning holddown',
                        'description' => 'How many polling cycles should be used to get a feel for pre-hammerOID polling averages.  We will use this as the baseline to judge our OID modifications.  Default is 4 polls, or about 20 minutes.  Ensure a stable system during this time!',
                        'method' => 'textbox',
                        'size' => 1,
                        'max_length' => 10,
                        'default' => '4',
                        ),
                'hammerOID_step_number' => array(
                        'friendly_name' => 'hammerOID automation steps',
                        'description' => 'How many steps Automation should shift OID numbers from poll to poll. More is aggresive.',
                        'method' => 'textbox',
                        'size' => 1,
                        'max_length' => 10,
                        'default' => '1',
                        ),
                'hammerOID_min_number' => array(
                        'friendly_name' => 'hammerOID min setting',
                        'description' => 'Minimum OIDs that hammerOID will shrink to for all hosts. Failsafe.',
                        'method' => 'textbox',
                        'size' => 1,
                        'max_length' => 10,
                        'default' => '3',
                        ),
                'hammerOID_max_number' => array(
                        'friendly_name' => 'hammerOID max setting',
                        'description' => 'Maximum OIDs that hammerOID will shrink to for all hosts. Failsafe.',
                        'method' => 'textbox',
                        'size' => 1,
                        'max_length' => 10,
                        'default' => '30',
                        ),


                'hammerOID_debug_header' => array(
                    'friendly_name' => 'hammerOID Debug',
                    'method' => 'spacer',
                            ),
                'hammerOID_debug' => array(
                    'friendly_name' => 'Enable hammerOID Debug',
                    'description' => 'debug logs outputted into cacti.log',
                    'method' => 'checkbox'
            ),

            //    'hammerOID_log_path' => array(
             //           'friendly_name' => 'HammerOID log output path',
              //          'description' => 'This is the path location to output the logs',
               //         'method' => 'textbox',
                 //       'default' => $hammerOID_log_path,
                  //      'max_length' => 255
            	   //),

                'hammerOID_advanced_header' => array(
                    'friendly_name' => 'hammerOID Usage Reports',
                    'method' => 'spacer',
            ),
                'hammerOID_myDataQueryID' => array(
                        'friendly_name' => 'HammerOID Usage Reports' ,
                        'description' => 'Not yet implemented. Let us have some anonymized info on performance increases observed, thanks!',
                        'method' => 'checkbox',
                ),
                );
}


function hammerOID_show_tab() {
        global $config, $user_auth_realm_filenames;

        if (api_user_realm_auth('hammerOID.php')) {
                if (substr_count($_SERVER['REQUEST_URI'], 'hammerOID.php')) {
                        print '<a href="' . $config['url_path'] . 'plugins/hammerOID/hammerOID.php"><img src="' . $config['url_path'] . '
plugins/hammerOID/images/tab_hammerOID_down.gif" alt="' . __('hammerOID', 'hammerOID') . '"></a>';
                }else{
                        print '<a href="' . $config['url_path'] . 'plugins/hammerOID/hammerOID.php"><img src="' . $config['url_path'] . '
plugins/hammerOID/images/tab_hammerOID.gif" alt="' . __('hammerOID', 'hammerOID') . '"></a>';
                }
        }
}



?>
