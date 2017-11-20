# hammerOID
Hammer-OID is a Cacti plugin designed to give insight to overall poller health and provide performance feedback for setting OIDs.

Default destination for log files is **CACTI_HOME/log/Hammer-OID.log**

##Current Release
 * Oct 2, 2017: Version 0.99 

##Release Notes
 * 1.2.0: Release of Cacti Hammer-OID Plugin

##Purpose
 * To enable Cacti Users to review individual poller times and tweak device settings for **Maximum OIDs Per Get Request**.

##Features
 * All hosts will have polling length graphs added. 
 * Graph tree containing overall and host-based polling times
 * Tool to "hammer" a specific host and determine target times.
 * Enable logfile rotation
 * Enable Mirage Debug logs (writes to cacti.log)

##Prerequisites
 * Cacti version 0.8.8+ [It may work on previous versions, but we haven't tested against them.]
 * Spine version 1.1.25 [It may work on previous versions, but we haven't tested against them.]  

##Installation
 * Untar/unzip plugin file into **$CACTI_HOME/plugins/**
 * Ensure permission are correct (**$CACTI_HOME/plugins/hammerOID**), generally owned by www-data:cactiuser 
 * Install hammerOID through Cacti Plugin Management
 * Enable hammerOID plugin through Cacti Plugin Management

##Usage
 * Plugin Installation creates database tables, imports templates, and creates graphs in all existing hosts.
 * Once hammerOID plugin is enabled:
	- spine will ramp up stats reporting
	- individual host polling times will be scraped and processed by the post-poller hook  
	- upon the next poll, those numbers will be available for graphing
 * Look under the Graph Tree to review the "hammerOID CACTI Polling Time" for any host

##Additional Help?
 * Feel free to submit any question on the Git.

##Possible Bugs?
 * Feel free to submit any bug related issue on the Git.

## Copyright
Copyright 2017 Patrick Scott Best
