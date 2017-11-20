<?php
function hammerOID_poller_bottom(){

	global $config, $debug;

	include_once($config['base_path'] . '/plugins/hammerOID/lib/hammerOID_functions.php');


	if (read_config_option('hammerOID_automation_enabled')=='on') {

		// get a list of the active hosts 
		// check to see if we've got them on our hammerOID table
		// check the latest polling duration, and calculate an OID change
		// increment the cycle counter on the entry
		// write the OID change to the hammerOID DB and to the actual host object


		hammerOID_debug("This is function: hammerOID_poller_bottom from within: hammerOID_poller_bottom.php");
		
		$alive_hosts_total = db_fetch_cell_prepared('SELECT count(*) FROM host WHERE status=3');
		hammerOID_debug("automation:  I'm showing $alive_hosts_total hosts alive this polling cycle.");

		$alive_hosts = db_fetch_assoc('SELECT id,hostname,polling_time,max_oids FROM host WHERE status=3');

		foreach ($alive_hosts as $key => $value){
	
			hammerOID_debug("automation: processing id:".$value['id']." hostname:".$value['hostname']);
			update_automation_table($value['id'],$value['max_oids'],$value['polling_time']);

		}

		cleanup_automation_table($alive_hosts);
		hammerOID_debug("automation: cleanup complete");
		
		// FINAL STATS

		$learning_cycle=read_config_option('hammerOID_learning_holddown');
		$alive_hosts_in_learning=db_fetch_cell("SELECT COUNT(*) FROM `hammerOID_automation` where poll_cycle_counter<$learning_cycle");

		cacti_log("hammerOID automation: final stats: serialised aggregate polling_time_mean: ". 
			round(db_fetch_cell("SELECT sum(polling_time_mean) FROM `hammerOID_automation` where poll_cycle_counter>$learning_cycle"),3).
		"  secs, polling_time_last: ".
			round(db_fetch_cell("SELECT sum(polling_time_last) FROM `hammerOID_automation` where poll_cycle_counter>$learning_cycle"),3).
		" secs");	



		//hammerOID_debug("automation: final stats: 
		cacti_log("hammerOID automation: final stats: hosts: $alive_hosts_total alive, ".
								"$alive_hosts_in_learning learning, ".
			db_fetch_cell("SELECT COUNT(*) FROM `hammerOID_automation` where polling_time_last<polling_time_mean").
								" polltime better than mean, ".
			db_fetch_cell("SELECT COUNT(*) FROM `hammerOID_automation` where polling_time_last<(polling_time_mean+polling_time_deviation)").
							" polltime better than mean with standard deviation");
		
		

	}
	else { 
		hammerOID_debug("automation: hammerOID is Active but automation was disabled for this cycle from the settings panel.");
	cacti_log("hammerOID automation: hammerOID is Active but automation was disabled for this cycle from the settings panel.");}

}


function update_automation_table ($id,$max_oids,$polling_time=""){

	hammerOID_debug("automation: id $id : update_automation_table function");
	$learning_cycle=read_config_option('hammerOID_learning_holddown');

		
	if (!sizeof(db_fetch_assoc("SELECT id from hammerOID_automation WHERE id=".$id.""))){ 
		//we don't see this ID on the automation table. Let's add it.

		hammerOID_debug("automation: id $id : this host not seen yet. adding new host to our automation table");
		hammerOID_debug("automation: id $id : learning cycle");
		hammerOID_debug("automation: id $id : polling_time_mean, initial sample $polling_time step 1");


		$vals="($id,$max_oids,$max_oids,0,1,$polling_time,$polling_time)";
                db_execute("INSERT INTO hammerOID_automation 
                        (id,max_oids_original,max_oids_last,last_oids_delta,poll_cycle_counter,polling_time_last,polling_time_mean)
                        VALUES $vals");

		// chase up with the variance serialised array
		add_to_variance($id,$polling_time,true);

	}
	else if (db_fetch_cell("SELECT poll_cycle_counter from hammerOID_automation WHERE id=$id") < $learning_cycle ) { 
		// Cycle Timer learning hold-down

		hammerOID_debug("automation: id $id : learning cycle");
		$my_poll_cycle_counter=increment_poll_cycle_counter($id);

		// Add this learning cycle poll time 
			hammerOID_debug("automation: id $id : polling_time_mean, initial sample $polling_time step ".
				 $my_poll_cycle_counter.
				"");
			db_execute("UPDATE hammerOID_automation 
				SET polling_time_mean = polling_time_mean + $polling_time 
				WHERE id=$id");
			db_execute("UPDATE hammerOID_automation 
				SET polling_time_last = $polling_time 
				WHERE id=$id");
			
		add_to_variance($id,$polling_time,false);
		
		
		// Average the polling_time_mean and calculate our deviation if we're ready.
		if ($my_poll_cycle_counter == $learning_cycle){
			db_execute("UPDATE hammerOID_automation 
                                SET polling_time_mean = polling_time_mean / ".$learning_cycle.
                                " WHERE id=$id");
		
			$mean=db_fetch_cell("SELECT polling_time_mean from hammerOID_automation WHERE id=$id");	
			hammerOID_debug("automation: id $id : polling_time_mean, learning done.  avg: $mean".
					" secs based on $my_poll_cycle_counter polls");

			calculate_deviation($id, $mean);		
								
		}	

	}


	else {

		$my_poll_cycle_counter=increment_poll_cycle_counter($id);

		// determine if our last OID update made a difference to poll timer, examined against deviation.
		$new_delta=0;

		// decision tree.
		// in bounds?
			// yes -> keep moving in the same direction
			// no -> 
				//on the good side? -> 
							// yes!  good, keep moving it in the same dir
							// no.  reverse course immediately.

		// The theory, there's a not-zone and a hot-zone for selecting OIDs on some devices.  
		// It depends on the querying we're doing, and the power of the source machine.
		// If there's a win, We'll find it with this method.  Otherwise, we'll just be up and down the range
		// ...all night long.

		// CHART

		// <--- shortest poll cycle                                        longest poll cycle --->
		// <---ouside--variation---> <---- inside ---> mean <--- inside ---> <------outside------>
		// *                        max OR min_oids boundary 	 				 *
		//		       X<------x stepping from inside to outside, keep going
		//		       x------>X stepping from outside to inside, turn back
		// |<-b bounce zone, be here ->|
		
				
		$deviation=floatval(db_fetch_cell("SELECT polling_time_deviation from hammerOID_automation WHERE id=$id")); 
		hammerOID_debug("automation: id $id : cycle $my_poll_cycle_counter : deviation is $deviation");
		$mean=floatval(db_fetch_cell("SELECT polling_time_mean from hammerOID_automation WHERE id=$id"));
		hammerOID_debug("automation: id $id : cycle $my_poll_cycle_counter : mean is $mean");

		$last_delta_polarity=check_last_delta_polarity($id);
		hammerOID_debug("automation: id $id : cycle $my_poll_cycle_counter : last polarity was $last_delta_polarity");
		$last_poll_time=check_polling_time_last($id);
		hammerOID_debug("automation: id $id : cycle $my_poll_cycle_counter : last polling time was $last_poll_time");
		hammerOID_debug("automation: id $id : cycle $my_poll_cycle_counter : this polling time is  $polling_time");

		//inside polling_time deviation bounds
		if ( abs($polling_time - $mean) < $deviation ) {
			hammerOID_debug("automation: id $id : cycle $my_poll_cycle_counter : inside deviation bounds.");


			// so how did we get inside deviation bounds, were we here before?
			if ( abs($last_poll_time - $mean) < $deviation ) {
					// yes, continue with this direction of travel and see if we can get out.
				$new_delta = $last_delta_polarity * (read_config_option('hammerOID_step_number'));
			}
			else {
				// no, we were better before. turn about, let's get back to the good place.
				$new_delta = (-1) * $last_delta_polarity * (read_config_option('hammerOID_step_number'));
			}

		}

		//outside polling_time deviation bounds
		else {
			hammerOID_debug("automation: id $id : cycle $my_poll_cycle_counter : outside deviation bounds.");

			// so how did we get outside deviation bounds, were we here before?
			if ( abs($last_poll_time - $mean) >= $deviation ) { 
				// yes, we were here, but are we on the right side of quick?
				if ($polling_time < $mean){
					// yup, we're quick.  Good.  
					// Let's push a little further in the direction we were moving
					$new_delta = (-1) * $last_delta_polarity * (read_config_option('hammerOID_step_number'));
				}
				else{
					// nope, we're slow.  oh well, let's keep moving in the direction we were headed.
					$new_delta = $last_delta_polarity * (read_config_option('hammerOID_step_number'));
				}
			}
			else { 
				// nope we haven't been outside deviation 

				//are we on the right side off quick?
				if ($polling_time < $mean){
					// yup, we're quick.  Good.  
					// Let's push a little further in the direction we were moving
					$new_delta = $last_delta_polarity * (read_config_option('hammerOID_step_number'));
				}
				else{
					// nope, we're slow. let's reverse and get out of here.
					$new_delta = (-1) * $last_delta_polarity * (read_config_option('hammerOID_step_number'));
				}
			}
		}
				
		

		hammerOID_debug("automation: id $id : new proposed delta is $new_delta OIDs.");

		// let's determine if our new proposed delta is between min and max OIDs  when applied.
							
		$new_max_oids=db_fetch_cell("SELECT max_oids_last from hammerOID_automation WHERE id=$id");

		if ((($new_max_oids+$new_delta) < read_config_option('hammerOID_min_number')) || 
			(($new_max_oids+$new_delta) > read_config_option('hammerOID_max_number'))) {
			
			hammerOID_debug("automation: id $id : new delta $new_delta would be out of OID min/max bounds. ".
					"Reversing course.");
			$new_delta=$new_delta*(-1);
			hammerOID_debug("automation: id $id : Setting delta to $new_delta and the new max oids to $new_max_oids");
				
		}
		else 
		{
			hammerOID_debug("automation: id $id : new delta would be in bounds. OK.");
			$new_max_oids=db_fetch_cell("SELECT max_oids_last from hammerOID_automation WHERE id=$id") + $new_delta;
			hammerOID_debug("automation: id $id : Setting delta to $new_delta and the new max oids to $new_max_oids");
		}



		// now that we've determined what the new delta direction should be
			//	set the OID count in the actual host
		hammerOID_debug("automation: id $id : updating the host OID information on host table ");
			db_execute("UPDATE host 
				SET max_oids = $new_max_oids
				WHERE id=$id");

			// 	update the automation table	
		hammerOID_debug("automation: id $id :  updating automation table with lasts");
                        db_execute_prepared("UPDATE hammerOID_automation 
                                SET max_oids_last = ?, polling_time_last = ?, last_oids_delta = ?
                                WHERE id = ?",
                                array($new_max_oids, $polling_time, $new_delta, $id));
	}

}

function cleanup_automation_table($alive_hosts) {

	hammerOID_debug("automation - cleanup_automation_table");

	$automation_hosts=db_fetch_assoc("SELECT id,max_oids_original from hammerOID_automation");

	foreach ($automation_hosts as $key_automation => $value_automation){
		$found=0;

			foreach ($alive_hosts as $key_alive => $value_alive){
				if ($value_automation['id']==$value_alive['id']){
					$found=1;		
				}	
			}
	
		if ($found==0){
			// sadly, this host is not currently up.	
			hammerOID_debug("automation: cleanup - removing id ".$value_automation['id']." from hammerOID table.");
			hammerOID_debug("automation: cleanup: id ".$value_automation['id'].
					" Original max_oids recorded as :".$value_automation['max_oids_original'].
					" replacing operating max_oids of :".
					db_fetch_cell("SELECT max_oids FROM host where id=".$value_automation['id'].""));

			// let's put the original host to the way we found it.
					// prepared statements didn't work here....
					// db_execute_prepared("UPDATE host 
					//        SET max_oids = ?
					//        WHERE id = ?",
					//        array($value_automation['max_oids_original'], $value_alive['id']));
												//thats why
					//        array($value_automation['max_oids_original'], $value_automation['id']));
                        db_execute("UPDATE host ". 
                               " SET max_oids = ".$value_automation['max_oids_original'].
                               " WHERE id = ".$value_automation['id']."");

			hammerOID_debug("automation: cleanup - confirmation A, running host max_oids is now: ".
					db_fetch_cell("SELECT max_oids FROM host where id=".$value_automation['id'].""));

			// and let's remove the entry from our table.  Bye!
			db_execute("DELETE FROM hammerOID_automation 
				WHERE id=".$value_automation['id']."");

		// debug, doesn't work though cause it always says Bad.. probably the way i sizeof a cell, rather than an assoc.
		//	if (!sizeof(db_fetch_cell("SELECT id FROM hammerOID_automation where id=".$value_automation['id'].""))){
		//		hammerOID_debug("automation: cleanup - confirmation B, host is (notfound).  Good.  ");
		//	}
		//	else {
		//		hammerOID_debug("automation: cleanup - confirmation B, host is (found). Bad.  ");
		//	}
				
			
		}

	}

}

function add_to_variance($id,$polling_time,$initial_variance_flag=false){
	if ($initial_variance_flag){
		db_execute("UPDATE hammerOID_automation SET polling_time_variance='".
				mysql_escape_mimic(serialize(array($polling_time))).
			   "' WHERE id=$id");
	}
	else {
		$my_array=unserialize(db_fetch_cell("SELECT polling_time_variance FROM hammerOID_automation where id=$id"));
		$my_array[]= $polling_time;
		db_execute("UPDATE hammerOID_automation SET polling_time_variance='".
				mysql_escape_mimic(serialize($my_array)).
			   "' WHERE id=$id");
	}
}

function calculate_deviation($id, $mean){

	$variation_blob=db_fetch_cell("SELECT polling_time_variance FROM hammerOID_automation where id=$id");
	hammerOID_debug("automation: id $id : polling_time_deviation, blob is $variation_blob");

	$my_array=unserialize($variation_blob);

	$aggregate=0;	
	hammerOID_debug("automation: id $id : polling_time_variation, aggregate now: $aggregate ");
	foreach ($my_array as $value){
		
		hammerOID_debug("automation: id $id : polling_time_variation, this blob value: $value ");
		$aggregate+=((abs(floatval($mean)-floatval($value)))**2);
		hammerOID_debug("automation: id $id : polling_time_variation, aggregate now: $aggregate ");
	}

	hammerOID_debug("automation: id $id : polling_time_variation, array items: ".
			sizeof($my_array).
			" , aggregate square of diffs: $aggregate");
		
	$my_deviation=sqrt($aggregate/(sizeof($my_array)));
	db_execute("UPDATE hammerOID_automation SET polling_time_deviation=$my_deviation where id=$id");

	hammerOID_debug("automation: id $id : polling_time_deviation: $my_deviation");

}

function increment_poll_cycle_counter($id){

	db_execute("UPDATE hammerOID_automation 
		SET poll_cycle_counter = poll_cycle_counter +1 
		WHERE id=$id");
				
	return db_fetch_cell("SELECT poll_cycle_counter from hammerOID_automation WHERE id=$id");
}

function mysql_escape_mimic($inp) { 
    if(is_array($inp)) 
        return array_map(__METHOD__, $inp); 

    if(!empty($inp) && is_string($inp)) { 
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp); 
    } 

    return $inp; 
} 

function check_last_delta_polarity($id){
	// checks whether last delta was increment,decrement,or neutral 
	//	if neutral, return a rando to get us rollin
	//	we should always be moving after the initial 0

	$last_delta=db_fetch_cell("SELECT last_oids_delta from hammerOID_automation WHERE id=$id");

		if ($last_delta < 0 )      { return (-1); }
		else if ($last_delta > 0 ) { return (1) ; } 		
			// tiebreaker
		else { 
			srand(); 
			rand(1,2);
			if(rand(1,2)==1){ 
				return (1);}
			else {  return (-1);}
		}
}

function check_polling_time_last($id){
	return (floatval(db_fetch_cell("SELECT polling_time_last from hammerOID_automation WHERE id=$id")));
}


?>
