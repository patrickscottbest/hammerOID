#!/usr/bin/php -q
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
if (!isset($_SERVER['argv'][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
	die('<br><strong>This script is only meant to run at the command line.</strong>');
}

$no_http_headers = true;

include(dirname(__FILE__) . '/../../../include/global.php');
include_once($config['base_path'] . '/lib/api_automation_tools.php');
include_once($config['base_path'] . '/lib/data_query.php');
include_once($config['base_path'] . '/lib/utility.php');
include_once($config['base_path'] . '/lib/sort.php');
include_once($config['base_path'] . '/lib/template.php');
include_once($config['base_path'] . '/lib/api_data_source.php');
include_once($config['base_path'] . '/lib/api_graph.php');
include_once($config['base_path'] . '/lib/snmp.php');
include_once($config['base_path'] . '/lib/data_query.php');
include_once($config['base_path'] . '/lib/api_device.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

// root@cacti:/var/www/html/cacti/cli# php ./add_graphs.php --host-id=5 --graph-type=ds --graph-template-id=38 --snmp-query-id=7 --snmp-query-type-id=23 --snmp-field=hostid --snmp-value=5
//Graph Added - graph-id: (76) - data-source-ids: (91, 91)


function hammerOID_add_graphs( $host_id, $graph_type, $graph_template, $snmp_query_id, $snmp_query_type_id, $snmp_field, $snmp_value) {


	/* Verify the host's existance */
	if (!isset($hosts[$host_id]) || $host_id == 0) {
		cacti_log ("ERROR: Unknown Host ID ($host_id)\n");
		exit(1);
	}


	} elseif ($graph_type == 'ds') {
		$snmp_query_array = array();
		$snmp_query_array['snmp_query_id']       = $dsGraph['snmpQueryId'];
		$snmp_query_array['snmp_index_on']       = get_best_data_query_index_type($host_id, $dsGraph['snmpQueryId']);
		$snmp_query_array['snmp_query_graph_id'] = $dsGraph['snmpQueryType'];

		$req = 'SELECT distinct snmp_index
			FROM host_snmp_cache
			WHERE host_id=' . $host_id . '
			AND snmp_query_id=' . $dsGraph['snmpQueryId'];

		$index_snmp_filter = 0;
		if (sizeof($dsGraph['snmpField'])) {
			foreach ($dsGraph['snmpField'] as $snmpField) {
				$req  .= ' AND snmp_index IN (
					SELECT DISTINCT snmp_index FROM host_snmp_cache WHERE host_id=' . $host_id . ' AND field_name = ' . db_qstr($snmpField);

				if (sizeof($dsGraph['snmpValue'])) {
					$req .= ' AND field_value = ' . db_qstr($dsGraph['snmpValue'][$index_snmp_filter]). ')';
				} else {
					$req .= ' AND field_value REGEXP "' . addslashes($dsGraph['snmpValueRegex'][$index_snmp_filter]) . '")';
				}

				$index_snmp_filter++;
			}
		}

		$snmp_indexes = db_fetch_assoc($req);

		if (sizeof($snmp_indexes)) {
			foreach ($snmp_indexes as $snmp_index) {
				
				$duplicate_graph_detected = false;
				
				$snmp_query_array['snmp_index'] = $snmp_index['snmp_index'];

				$existsAlready = db_fetch_cell_prepared('SELECT id
					FROM graph_local
					WHERE graph_template_id = ?
					AND host_id = ?
					AND snmp_query_id = ?
					AND snmp_index = ?',
					array($template_id, $host_id, $dsGraph['snmpQueryId'], $snmp_query_array['snmp_index']));

				if (isset($existsAlready) && $existsAlready > 0) {
					if ($graphTitle != '') {
						db_execute_prepared('UPDATE graph_templates_graph
							SET title = ?
							WHERE local_graph_id = ?',
							array($graphTitle, $existsAlready));

						update_graph_title_cache($existsAlready);
					}

					$dataSourceId = db_fetch_cell_prepared('SELECT
						data_template_rrd.local_data_id
						FROM graph_templates_item, data_template_rrd
						WHERE graph_templates_item.local_graph_id = ?
						AND graph_templates_item.task_item_id = data_template_rrd.id
						LIMIT 1', 
						array($existsAlready));

					echo "NOTE: Not Adding Graph - this graph already exists - graph-id: ($existsAlready) - data-source-id: ($dataSourceId)\n";

					$duplicate_graph_detected = true;
					
					continue;
				}

				$empty = array(); /* Suggested Values are not been implemented */

				$returnArray = create_complete_graph_from_template($template_id, $host_id, $snmp_query_array, $empty);

				if ($graphTitle != '') {
					db_execute_prepared('UPDATE graph_templates_graph
						SET title = ?
						WHERE local_graph_id = ?', 
						array($graphTitle, $returnArray['local_graph_id']));

					update_graph_title_cache($returnArray['local_graph_id']);
				}

				$dataSourceId = db_fetch_cell_prepared('SELECT
					data_template_rrd.local_data_id
					FROM graph_templates_item, data_template_rrd
					WHERE graph_templates_item.local_graph_id = ?
					AND graph_templates_item.task_item_id = data_template_rrd.id
					LIMIT 1', 
					array($returnArray['local_graph_id']));

				foreach($returnArray['local_data_id'] as $item) {
					push_out_host($host_id, $item);

					if ($dataSourceId != '') {
						$dataSourceId .= ', ' . $item;
					} else {
						$dataSourceId = $item;
					}
				}

				echo 'Graph Added - graph-id: (' . $returnArray['local_graph_id'] . ") - data-source-ids: ($dataSourceId)\n";
			}
			
			if($duplicate_graph_detected == true){
                                exit(1);
                        } else {
                                exit(0);
                        }
			

	exit(0);


