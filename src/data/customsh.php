<?php
/*
           _     _                _           _     
          | |   (_)              | |         | |    
 _ __ ___ | |__  _ _ __ ______ __| | __ _ ___| |__  
| '__/ _ \| '_ \| | '_ \______/ _` |/ _` / __| '_ \ 
| | | (_) | |_) | | | | |    | (_| | (_| \__ \ | | |
|_|  \___/|_.__/|_|_| |_|     \__,_|\__,_|___/_| |_|

robin-dash: Centralized Controller for Robin-Mesh networking devices
Copyright (C) 2010-2011 Cody Cooper.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/


if(file_exists("../settings.php")) {require("../settings.php");}
else {header("Location: ../oobe.php");exit;}


if(!$_GET['network']) {exit;}
else if(!file_exists($dir . "data/" . $_GET['network'] . ".xml")) {exit;}
else {$xmlp = simplexml_load_file($dir . "data/" . $_GET['network'] . ".xml");$networkname = $_GET['network'];}


// and here we go...
echo "#!/bin/sh\n";

// Set the alternate IP to be that of this server
echo "echo \"" . $sip . "\" > /etc/dashboard.fallback_ip\n";


// SSH Key Access
if(file_exists($dir . "data/uploads/" . $networkname . "/ssh.key") && strpos(file_get_contents($dir . "data/uploads/" . $networkname . "/ssh.key"), 'rsa') !==FALSE) {echo "echo \"" . file_get_contents($dir . "data/uploads/" . $networkname . "/ssh.key") . "\" > /etc/dropbear/rsa_authorized_keys\n";}
else if(file_exists($dir . "data/uploads/" . $networkname . "/ssh.key") && strpos(file_get_contents($dir . "data/uploads/" . $networkname . "/ssh.key"), 'dsa') !==FALSE) {echo "echo \"" . file_get_contents($dir . "data/uploads/" . $networkname . "/ssh.key") . "\" > /etc/dropbear/dsa_authorized_keys\n";}
else {echo "\n";}


// Custom.sh: The real stuff.
// Check that the directory exists, if not: make it
if(is_dir($dir . "data/uploads/" . $networkname . "/")) {echo "";}
else {mkdir($dir . "data/uploads/" . $networkname . "/");}

if($xmlp->management->custom_update == "1") {
	$csrv = $xmlp->general->services_cstm_srv;
	
	if(substr($csrv, -1) == "/") {echo "";}
	else {$csrv = $xmlp->general->services_cstm_srv . "/";}
	
	if($csrv == "svn6.assembla.com/svn/RobinMesh/custom-scripts/") {exit;}
	else if(strpos($csrv, 'http') !==FALSE) {$url = $csrv . "custom.sh";}
	else {$url = "http://" . $csrv . "custom.sh";}
	
	if(file_exists($dir . "data/uploads/" . $networkname . "/custom.sh")) {
		// we already have the script, say it
		echo file_get_contents($dir . "data/uploads/" . $networkname . "/custom.sh");
	}
	else {
		// get the script, and remove the header
		$fc = str_replace("#!/bin/sh", "", file_get_contents($url));
		
		// say the script
		echo $fc;
		
		// cache the script for later
		$fh = fopen($dir . "data/uploads/" . $networkname . "/custom.sh", 'w') or die("Can't write to the data file.");
		fwrite($fh, $fc);
		fclose($fh);
	}
}
else {echo "";}
?>