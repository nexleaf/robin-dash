<?php



if (file_exists("settings.php")) {
	require("settings.php");
}
else {
	die("# We need to setup the server first");
}


function auth_user() {
	if(isset($_SESSION['user']) && isset($_SESSION['pass']) && file_exists($dir . "data/" . $_SESSION['user'] . ".xml")) {
		// $xmlp = simplexml_load_file($dir . "data/" . $_SESSION['user'] . ".xml");
	        if($_SESSION['pass'] != $xmlp->robindash->password) {
			print "# Invalid session\n";
			die("# Invalid session\n");
	        }
	}
}

function verify_localhost() {
	$ip = $_SERVER['REMOTE_ADDR'];
	//print $ip;
	if ($ip != "localhost" || $ip != "127.0.0.1") {
		die("Sync only allowed from localhost!");
	}
}

function show_config() {

	$fc = file_get_contents($dir . "data/" . $_SESSION['user'] . ".xml");	

	print $fc;

}

function store_config() {

	//$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);
	$uploadfile = $dir . "data/" . $_SESSION['user'] . ".xml";

	// TODO: Verify remote file has newer version that local file

	if (move_uploaded_file($_FILES['conffile']['tmp_name'], $uploadfile)) {
		echo "File is valid, and was successfully uploaded.\n";
	} else {
		echo "Possible file upload attack!\n";
	}

}


function do_login() {

	// Figure out the login url
	$loginurl = "";
	if (stripos($xmlp->robindash->configsync, "https") != 0) {
		$loginurl = "https://" . $sn . $wdir; 
	} else {
		$loginurl = "http://" . $sn . $wdir; 
	}

	// Do the login
	$post_data = array();
	$post_data['user'] = $_SESSION['user'];
	$post_data['pass'] = $xmlp->robindash->password;
	
	$ckfile = $dir . "data/" . $_SESSION['user'] . "_cookies.txt"
	$ch = curl_init($loginurl);
	
	curl_setopt($ch, CURLOPT_POST, 1 );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	curl_setopt($ch, CURLOPT_HEADER,1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$postResult = curl_exec($ch);
	if (curl_errno($ch)) {
		die("# unable to login!");
	}
	curl_close($ch);
	
}



function pull_config() {

	// Figre out server url for checkin
	$serverurl = $xmlp->robindash->configsync;
	if (stripos($xmlp->robindash->configsync, "http") != 0) {
		$serverurl = "http://" . $xmlp->robindash->configsync . '/sync-config.php?action=show';
	}

	// Get the config
	$post_data = array();
	$post_data['user'] = $_SESSION['user'];
	$post_data['pass'] = $xmlp->robindash->password;
	
	$ckfile = $dir . "data/" . $_SESSION['user'] . "_cookies.txt"
	$ch = curl_init($loginurl);
	
	curl_setopt($ch, CURLOPT_COOKIEFILE, $ckfile);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $ckfile);
	curl_setopt($ch, CURLOPT_HEADER,1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$getResult = curl_exec($ch);
	if (curl_errno($ch)) {
		die("# unable to get data!");
	}
	curl_close($ch);

	return $getResult;

}

function push_config() {

}


// Verify that we are configured for remote sync and load the xml for everyone
$xmlp = simplexml_load_file($dir . "data/" . $_SESSION['user'] . ".xml");	

switch ($_GET["action"]) {

	case "show":
		auth_user();
		show_config();
		break;
	case "store":
		auth_user();
		store_config();
		break;
	case "dosync":
		verify_localhost();
		$remoteconfig = pull_config();
		print $remoteconfig;
		/* if remote new, sync local, if local new push */
		break;
	default:
		print "# invalid action\n";
}



