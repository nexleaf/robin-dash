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

$file = $_GET['file'];

if($file == "upgrade.sh" || $file == "sysupgrade.sh") {echo str_replace("<REPLACE-ME>", $sn . $wdir . "data/customfw/" . $networkname . "/", file_get_contents($dir . "resources/firmware/" . $file));}
else if($file == "REBOOT" || $file == "sysupgrade" || $file == "lsof.sh") {echo file_get_contents($dir . "resources/firmware/" . $file);}
else if(file_exists($dir . "data/uploads/" . $networkname . "/firmware/ubnt-HP/" . $file) && strpos(strtolower($file), 'ubnt-HP') !==FALSE) {echo file_get_contents($dir . "data/uploads/" . $networkname . "/firmware/ubnt-HP/" . $file);}
else if(file_exists($dir . "data/uploads/" . $networkname . "/firmware/" . $file)) {echo file_get_contents($dir . "data/uploads/" . $networkname . "/firmware/" . $file);}
else {header("HTTP/1.1 404 Not Found");echo "<h1>404 Not Found</h1>";}
?>