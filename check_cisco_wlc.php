#!/usr/bin/php
<?php

/*
Author:         Apollock
First release:  2013-09-12

Updated at:     2018-06-27
By:             Victor LOSSSER <victor.losser@viacesi.fr> / Intern
Company:        Würth France
https://github.com/VictorLosser/Cisco-WLC-users-Nagios-PHP-Script
*/

function help() { ?>

Usage:
check_cisco_wlc <hostname> <community> <warn value> <critical value> <'checkall' or 'single' or 'search' or 'total'> <AP name * >
	* for 'single' and 'search' mode only.
			--> Single mode : you need to enter the EXACT AP name as it's defined in your WLC (not case sensitive)
			--> Search mode : just type any characters and you will see results containing those characters

This plugin check your Access Points via you Cisco Wireless LAN Controller.

There are 4 modes :
        - checkall:     Check all APS without any filter. Display only the most 'critical' state in text. Graphs of all APs.
        - single:       Check AP you want depending on what you precise in 6th argument.
        - search:       Type the name of the AP, not necessary completely. It's just to search an AP.
        - total:        Brief about all APs.

<?php
}

// Collect all arguments in variables
@$host=$argv[1];
@$community=$argv[2];
@$warn=intval($argv[3]);
@$critical=intval($argv[4]);
@$action=$argv[5]; // "checkall" or "single" or "search" or "total"
@$ap_select=$argv[6];

// Variables initialization
$grandtotal = 0;
if ($action == "checkall" | $action == "total") {
        $message = "All APs within spec. Click to view.\n";
        $okay_aps = $crit_aps = $warn_aps = $message;
}
$are_warn = false; $are_crit = false;
$perfdata = " | ";

// Nagios exit codes
$e_okay = 0; $e_warn = 1; $e_crit = 2; $e_unkn = 3;

// Check that all required parameters are filled in
if (!$action || !$host || !$community || !$warn || !$critical) {
                help();
                exit($e_unkn);
}
elseif ($action == "single") {
        if (!$ap_select){
                print "Please select an AP by entering it's name after \"single\"\n";
                # exit($e_unkn);
        }
}

if ($warn > $critical) {
                print "Warning value must be less than the critical value!\n";
                exit($e_unkn);
}

$aps = snmp2_real_walk($host, $community, '1.3.6.1.4.1.14179.2.2.1.1.3') or exit($e_unkn); // Retrieve all APs name and oid via SNMP

if ($action == "single") { // If single mode, process APs name filter
        $aps = preg_grep("/$ap_select\"$/i",$aps);
}
elseif ($action == "checkall") {
        $aps = preg_grep("/AP/i",$aps);
}
elseif ($action == "search") {
        $aps = preg_grep("/$ap_select/i",$aps);
}

// Check if APs table is empty. If it is, script stop.
if (empty($aps)) {
        print "AP not found\n";
        exit($e_unkn);
}

foreach ($aps as $key => $name) {
                preg_match("^(.+?)2.2.1.1.3.(.+?)$^" , $key, $match);
                $oid = trim($match[2]);
                preg_match("#STRING: \"(.*?)\"#",$name,$match);
                $name = trim($match[1]);

                $numassoc1 = snmp2_get($host,$community,'1.3.6.1.4.1.14179.2.2.13.1.4.'.$oid.'.0'); //SSID #1
                preg_match("#[0-9]+#",$numassoc1,$match);
                $num1 = @intval($match[0]);

                $numassoc2 = snmp2_get($host,$community,'1.3.6.1.4.1.14179.2.2.13.1.4.'.$oid.'.1'); //SSID #2
                preg_match("#[0-9]+#",$numassoc2,$match);
                $num2 = @intval($match[0]);

                $total = $num1+$num2;
                $perfdata .= "$name=$total;$warn;$critical;0;";
                if ($action == "checkall" || $action == "single" || $action == "search") {
                        if ($total < $warn) {
                                        $okay_aps .= "OK: $name has $total client(s).\n";
                        }
                        if ($total >= $warn && $total < $critical) {
                                        $warn_aps .= "WARNING: $name has $total clients.\n";
                                        $are_warn = true;
                        }
                        if ($total >= $critical) {
                                        $crit_aps .= "CRITICAL: $name has $total clients!\n";
                                        $are_crit = true;
                        }
                }

                $grandtotal = $grandtotal+$total;

        }

if ($action == "checkall" || $action == "single" || $action == "search") {
        if ($are_crit) {
                print $crit_aps.$perfdata."\n"; exit($e_crit);
        }
        if ($are_warn) {
                print $warn_aps.$perfdata."\n"; exit($e_warn);
        }
        print $okay_aps.$perfdata."\n"; exit($e_okay);
}

if ($action == "total") {
                if ($grandtotal == 0) {
                                print "OK: There are no clients.\n";
                                exit($e_okay);
                }
                if ($grandtotal < $warn) {
                                print "OK: $grandtotal associated clients. | clients=$grandtotal;$warn;$critical;0;\n";
                                exit($e_okay);
                }
                if ($grandtotal >= $warn && $grandtotal < $critical) {
                                print "WARNING: $grandtotal associated clients. | clients=$grandtotal;$warn;$critical;0;\n";
                                exit($e_warn);
                }
                if ($grandtotal >= $critical) {
                                print "CRITICAL: $grandtotal associated clients. | clients=$grandtotal;$warn;$critical;0;\n";
                                exit($e_crit);
                }
}
?>
