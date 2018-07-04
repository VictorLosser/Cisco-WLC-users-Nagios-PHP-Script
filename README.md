# Nagios check_cisco_wlc_users.php

This plugin check your Access Points via you Cisco Wireless LAN Controller.

## Usage
```
Usage:
check_cisco_wlc <hostname> <community> <warn value> <critical value> <'checkall' or 'single' or 'search' or 'total'> <AP name * >
	* for 'single' and 'search' mode only.
			--> Single mode : you need to enter the EXACT AP name as it's defined in your WLC (not case sensitive)
			--> Search mode : just type any characters and you will see results containing those characters
```

## Supported commands 
```
	checkall:
		Check all APS without any filter. Display only the most 'critical' state in text. Graphs of all APs.
	single:
		Check AP you want depending on what you precise in 6th argument.
	search:
		Type the name of the AP, not necessary completely. It's just to search an AP.
	total:
		Brief about all APs.
				   
```

## Usage in Nagios

Copy file `check_cisco_wlc_users.php` to Nagios plugins directory (for example `/usr/lib/nagios/plugins/`).

## Links

Nagios plugin developement [https://nagios-plugins.org/doc/guidelines.html#PLUGOPTIONS]
