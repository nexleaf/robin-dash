<?php



if (file_exists("settings.php")) {
	require("settings.php");
}
else {
	die("# We need to setup the server first");
}


function auth_user($xmlp) {
	if(isset($_SESSION['user']) && isset($_SESSION['pass']) && file_exists($dir . "data/" . $_SESSION['user'] . ".xml")) {
	        if($_SESSION['pass'] != $xmlp->robindash->password) {
			print "# Invalid session\n";
			die("# Invalid session\n");
	        }
	}
}


function verify_localhost() {
	$ip = $_SERVER['REMOTE_ADDR'];
	if ($ip == "localhost" || $ip == "127.0.0.1" || $ip == "127.0.1.1") {
		/* all good to sync */
	} else {
		die("Sync only allowed from localhost!");
	}	
}


function write_configuration($network, $config) {
	global $dir;
	
	// save the configuration
	$fh = fopen($dir . "data/" . $network . ".xml", 'w') or die("Can't write to the data file.");
	fwrite($fh, $config);
	fclose($fh);

	// clear the checkinID
	$fh = fopen($dir . "data/cid/" . $network . ".txt", 'w') or die("Can't write to the data file.");
	fwrite($fh, "-\n");
	fclose($fh);
}


function show_config($network) {
	header('Content-Description: File Transfer');
	header('Content-Type: text/xml');
	header('Content-Disposition: attachment; filename='. $network .'.xml');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	//header('Content-Length: ' . filesize($dir . "data/" . $network . ".xml"));
	ob_end_clean();
	flush();
	$fs = filesize($dir . "data/" . $network . ".xml");
	$fd = fopen($dir . "data/" . $network . ".xml", 'rb');
	$fc = fread($fd, $fs);
	fclose($fd);
	print $fc;
}


function store_config($network) {
	$uploadfile = $dir . "data/" . $network . ".xml";

	// TODO: Verify remote file has newer version that local file

	if (move_uploaded_file($_FILES['conffile']['tmp_name'], $uploadfile)) {
		# echo "File is valid, and was successfully uploaded.\n";
	} else {
		echo "# Error with config sync upload!\n";
	}

	// clear the checkinID
	$fh = fopen($dir . "data/cid/" . $network . ".txt", 'w') or die("Can't write to the data file.");
	fwrite($fh, "-\n");
	fclose($fh);
}


function do_login($xmlp, $network) {

	global $sn;
	global $wdir;
	
	// Figure out the login url
	$loginurl = "";
	if (stripos($xmlp->robindash->configsync, "https") === false) {
		$loginurl = "http://" . $sn . $wdir; 
	} else {
		$loginurl = "https://" . $sn . $wdir; 
	}

	if (!isset($_GET['usepass'])) {
		die("# Must set usepass GET parameter");
	}

	// Do the login
	$post_data = array();
	$post_data['user'] = $network;
	$post_data['pass'] = $_GET['usepass'];
	
	$ckfile = $dir . "data/" . $network . "_cookies.txt";
	$ch = curl_init($loginurl);
	
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$postResult = curl_exec($ch);
	if (curl_errno($ch)) {
		die("# unable to login! " . curl_errno($ch));
	}
	curl_close($ch);
}


function pull_config($xmlp, $network) {

	do_login($xmlp, $network);

	// Figre out server url for checkin
	$serverurl = $xmlp->robindash->configsync;
	if (stripos($xmlp->robindash->configsync, "http") === false) {
		$serverurl = "http://" . $xmlp->robindash->configsync . '/sync-config.php?action=show';
	}

	// Get the config
	$ckfile = $dir . "data/" . $network . "_cookies.txt";

	$ch = curl_init($serverurl);
	
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	// curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
	//curl_setopt($ch, CURLOPT_CRLF, 1);

	$getResult = curl_exec($ch);
	if (curl_errno($ch)) {
		die("# unable to get data! " . curl_error($ch));
	}
	curl_close($ch);

	return $getResult;
}


function push_config($xmlp, $network) {
	
	global $dir;
	
	// Figre out server url for checkin
	$serverurl = $xmlp->robindash->configsync;
	if (stripos($xmlp->robindash->configsync, "http") === false) {
		$serverurl = "http://" . $xmlp->robindash->configsync . '/sync-config.php?action=store';
	}

	// Do the POST
	$post_data = array();
	$post_data['conffile'] = "@" . $dir . "data/" . $network . ".xml";
	
	$ckfile = $dir . "data/" . $network . "_cookies.txt";

	$ch = curl_init($serverurl);
	
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	//curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$postResult = curl_exec($ch);
	
	if (curl_errno($ch)) {
		die("# unable to upload file! " . curl_error($ch) . " " . $upfile . "\n");

	}
	print $postResult;
	
	curl_close($ch);
}


// Verify that we are configured for remote sync and load the xml for everyone
$network = "";
if (isset($_GET['network'])) {
	$network = $_GET['network'];
} else {
	$network = $_SESSION['user'];
}
$xmlp = simplexml_load_file($dir . "data/" . $network . ".xml");	

switch ($_GET["action"]) {

	/**
	 * First two cases are for the root dashboard. It makes no decisions,
	 * it only does what the remote dashboards tell it to: display the current config
	 * or store a new config.
	 */
	case "show":
		auth_user($xmlp);
		show_config($network);
		break;
	case "store":
		auth_user($xmlp);
		store_config($network);
		break;
	/**
	 * This case runs on the remote dashboard. The remote dashboard decides which
	 * configuraiton is newer and will either send the new one to the root dashboar
	 * or write the configuration from the root dashboard locally.
	 */
	case "dosync":

		verify_localhost($xmlp);
		$remoteconfig = pull_config($xmlp, $network);

		$remotexml = simplexml_load_string($remoteconfig);

		$remotever = intval($remotexml->robindash->configversion);
		$localver = intval($xmlp->robindash->configversion);
		
		/* if remote new, sync local, if local new push */
		if ($localver > $remotever) {
			push_config($xmlp, $network);
		} else if ($localver < $remotever) {
			write_configuration($network, $remoteconfig);			
		} else {
			// Same configuration so no changes!
		}
		break;
	default:
		print "# invalid action\n";
}



