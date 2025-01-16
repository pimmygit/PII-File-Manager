<?php
require_once('../config/constants.php');
require_once('../config/settings.php');
require_once('../utils/logger.php');

$usrAgent =  $_SERVER['HTTP_USER_AGENT'];

if ( 0 && strstr($usrAgent,"Firefox") == NULL ) {
	print "Sorry, but your browser is not supported. Please use Firefox version > 1.0 (http://www.mozilla.com/firefox/)";
   	exit();
}

session_start();

// Logging out routine
if(isset($_POST['logOut'])){

	msg_log(DEBUG, "User [".$_SESSION['userMail']."] is logging off.", SILENT);
	
	session_unregister("piifmt");
	
	foreach ($_SESSION as $varName => $value) {
		msg_log(DEBUG, "Destroying session [".$varName."].", SILENT);
		unset ($_SESSION[$varName]);
	}	 
	
	session_destroy();
}

// User session check
if ( isset($_SESSION['userMail']) ) {

	header("Location: piifmt.php");
   	exit();
} 

?>

<html>
<head>
	<title>PII File Management Tool</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
</head>

<body>

<div class="titleBar"></div>

<div class="sideBar">
	<div><ul class="buttons">
		<li><a href="index.php">Home</a></li>
		<li><a href="login.php">Login</a></li>
		<li><a href="wiki/">Help</a></li>
	</ul></div>
</div>

<div id="mainContainer"><i></i>
	<div id="mainContainerVertCenter" align="center">
		<div class="infoPanel" align="center">
		<div class="infoGlue">
			<p class="regularText">PII File Management Tool INDEX page.</p>
		</div>
		</div>
		<div class="formPanel">
			
		</div>
	</div>
</div>

</body>
</html>
