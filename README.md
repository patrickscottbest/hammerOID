# Download
https://github.com/patrickscottbest/hammerOID/archive/hammerOID.zip

# About hammerOID
hammerOID is a Cacti plugin designed to give insight to overall poller health and provide automated machine learning performance tools for setting optimum Maximum SNMP Object Identifiers (Max OIDs).

1) Insight is provided defacto with graphs based on internal table information provided by Spine poller.  This by itself is a very useful feature of hammerOID plugin.
2) Automatic OID "hammering" is not turned on by default, but provides some amazing "overnight" insight into the performance of end equipment when OIDs are shifted.

The problem with OID selection in Cacti is that it is often overlooked and depending on the scenario or end-host equipment either contributes very greatly or insignificantly to the overall polling health of the cacti ecosystem.  

In large deployments (>100,000 data sources) or in installations where critical polling interval is too close for comfort (just slightly less than 5 minutes), this script can greatly reduce the overall time required to compete a poll cycle.

Challenges encountered with OIDs:

*It is most often left to defaults, or is adjusted without knowing exactly what is happening behind the scenes.
*Some devices lock up (IBM AMM cards for example) if the requested OIDs are too low
*Some devices use exorbinantly large OID strings which cause rework of the Spine poller to make requests that fit in a single UDP query (F5 VIP OIDs are very lengthy)
*A sweet spot exists to reduce overall poll time on a per-host basis, which can be found through experimentation, but that's time consuming

A previous article I wrote on how to tune OIDs and the theory behind it.
See file: cacti-tuning-how-to-set-maximum-oids-per-get-request.xml 
Deadlink: http://realworldnumbers.com/cacti-tuning-how-to-set-maximum-oids-per-get-request/
Dang.  I used to own that domain and I stupidly let it go.  I will put the cached article in xml format with this repo.  


# Download
https://github.com/patrickscottbest/hammerOID/archive/hammerOID.zip

# Current Release
 * November 19, 2017: Version 0.8
 * September 1, 2022: Version 0.8.1

# Release Notes
 * 0.8: Release of Cacti Hammer-OID Plugin
 * 0.8.1: Updates to Readme.  I doubt this thing works anymore, but maybe it shouldn't die.  OID length still a huge problem with many vendors and large installations.

# Purpose
 * To enable Cacti Users to review individual poller times and tweak device settings for **Maximum OIDs Per Get Request**.

# Features
 * Graph templates Per-Host and Global are added
 * All hosts new, old, or modified, will have graphs based on those templates automatically added
 * Automation and machine learning controls for step/min/max OIDs 
 * Debug feature with lots of insight
 
# Prerequisites
 * Cacti version 0.8.8+ [It may work on previous versions, but we haven't tested against them.]
 * Spine version 1.1.25 [It may work on previous versions, but we haven't tested against them.]  

# Installation 
## Notes
 * Note, installation will take a VERY VERY long time on some installations.  Patience after clicking install for up to 5 minutes please. 
   The tasks completed by installation are: 
   ** Import templates
   ** Create per-host graphs for every device
   ** Create global graphs attributed to the primary poller (localhost, ID-1)
   ** Create database tables.
 
## Procedure:
 * Untar/unzip plugin file into **$CACTI_HOME/plugins/**
 * Ensure that the directory name is **hammerOID/** , if downloaded from git it may look like hammerOID-master, this is incorrect.
 * Ensure permission are correct (**$CACTI_HOME/plugins/hammerOID**), generally owned by www-data:cactiuser 
 * Install hammerOID through Cacti Plugin Management
 * Enable hammerOID plugin through Cacti Plugin Management
 * Settings -> hammerOID -> Automation Enabled checkbox when you're ready for machine learning OID tuning 
 * Settings -> hammerOID -> Debug Disable checkbox if you're overwhelmed.  Cacti log will still report the basics.

# Uninstall
 * disable the plugin through Cacti plugin management
 * uninstall the plugin through Cacti plugin management
 The uninstall procedure will delete all hammerOID graphs (per-host and global) and uninstall the template packs.  It will remove all cacti settings, restore OIDs to their original state (this was recorded during automation enablement), and delete all hammerOID related databases.

# Usage
 * Plugin Installation creates database tables, imports templates, and creates graphs in all existing hosts.
 * Once hammerOID plugin is enabled:
	- individual host polling times will be scraped and processed by the post-poller hook  
	- upon the next poll, those numbers will be available for graphing
 * Look under the Graph Tree to review the "hammerOID CACTI Polling Time" for any host


# Additional Help?
 * Feel free to submit any question on the Git.

# Possible Bugs?
 * Feel free to submit any bug related issue on the Git.

# Copyright
Copyright 2017 Patrick Scott Best

# Contact
patrickscottbest@gmail.com


