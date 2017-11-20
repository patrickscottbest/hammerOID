<?php
function hammerOID_device_new($save){

	global $config, $debug;

        //api_plugin_hook_function('api_device_new', $save);

        include_once($config['base_path'] . '/plugins/hammerOID/lib/hammerOID_functions.php');
	include_once($config['base_path'] . '/plugins/hammerOID/includes/hammerOID_add_data_query.php');

	hammerOID_debug("This is function: hammerOID_device_new from within: hammerOID_device_new.php");
        hammerOID_debug("Detected a Device Save, Let's see if it has a hammerOID graph.");

        $myGraphTemplatesPerHost = unserialize(read_hammerOID_config_option('hammerOID_myGraphTemplatesPerHost'));
        $myGraphTemplatesGlobal = unserialize(read_hammerOID_config_option('hammerOID_myGraphTemplatesGlobal'));
        $myDataQueryID = unserialize(read_hammerOID_config_option('hammerOID_myDataQueryID'));
        $myDataQueryType = unserialize(read_hammerOID_config_option('hammerOID_myDataQueryType'));

	$php = read_config_option("path_php_binary");

                        hammerOID_debug("Now creating data queries for hostID: ".$save['id'] );

			$reindexmethod=0;
			// only need the one type, as this is a single host
                        foreach ($myDataQueryID as $name => $value){
                                hammerOID_add_data_query($save['id'], "$value", $reindexmethod);
                        };

                        hammerOID_debug("Done adding data queries to hostID: ".$save['id'] );


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

                        hammerOID_debug("Adding PerHost graphID: $graphID graphName: $graphName to hostID: ".$save['id'] );

                                //look what i've done for DataQueryType id. What an awful mess.
                                $exec_output=array();
                                exec($php ." ".$config['base_path'] ."/cli/add_graphs.php --host-id=".$save['id'].
                                        " --graph-type=ds --graph-template-id=".$graphID.
                                        " --snmp-query-id=".$myDataQueryID['hammerOID - PerHost Data Query'].
                                        " --snmp-query-type-id=".$myDataQueryType["".$graphID.""].
                                        " --snmp-field=hostid --snmp-value=".$save['id'] , $exec_output);
                                foreach ($exec_output as $exec_output_line){ hammerOID_debug("$exec_output_line"); };

                        };

                        // explore this later... maybe a better way than executing an external cli script...
                        //exec($php . " " . $extra_args . " > /dev/null &");
                        //create_complete_graph_from_template($hostID, $myGraphTemplateID, $snmp_query_array, $empty);

                        hammerOID_debug("Done adding PerHost graphs to saved host");
                }



?>
