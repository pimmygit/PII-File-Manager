<?php
/* 
* @package:		Main
* @subpackage:	Main
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/
include_once('../config/constants.php');
include_once('../config/settings.php');
include_once('../utils/logger.php');
include_once('../utils/functions.php');
include_once('../security/LoginLDAP.class.php');
require_once('../utils/FMTUser.class.php');

// Start the session
session_start();
// User session check
if ( !isset($_SESSION['userMail']) ) {

	msg_log(WARN, "Unauthorized user is trying to gain access from [".$_SERVER['REMOTE_ADDR']."] to [".$_SERVER['PHP_SELF']."].", SILENT);

	$siteRoot = sprintf('http%s://%s%s', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': ''), $_SERVER['HTTP_HOST'], dirname($_SERVER['PHP_SELF']));
	$thisPage = sprintf('http%s://%s%s', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == TRUE ? 's': ''), $_SERVER['HTTP_HOST'], $_SERVER['PHP_SELF']);

	// Required for back-redirection purposes
	session_register("piifmt");
	$_SESSION['URL_BACK'] = $thisPage;

	header("Location: ".$siteRoot."/login.php");
   	exit();
} 

msg_log(DEBUG, "PAGE: piifmt.php", SILENT);

// Get the name of the current working project
$currProject = getCurrProj($_SESSION['userMail']);

if (empty($currProject) && $currProject != 'empty') {
	msg_log(WARN, "Failed to determine project. Please log off and log in again.", NOTIFY);
}

// Get user's data
$userData = new FMTUser($currProject, $_SESSION['userMail']);

?>
<html>
<head>
	<title>PII File Management Tool</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script type="text/javascript" src="javascript/security.js"></script>
</head>

<body>

<div class="header">
	<div class="topBar" style="width: 50px;">
		<form method="post" action="index.php" name="logOut">
			<input type="submit" class="fmtButtonLink" name="logOut" value="Log Out" />
		</form>
	</div>
	<div class="topBar" style="width: 150px;">User: <?php echo $_SESSION['userName']; ?> </div>
</div>

<div class="sideBar">
	<div class="sideBarTitle" style="margin-top:0;">F.M.T. Pages</div>
	<?php 
	echo '	<ul class="buttons">'.PHP_EOL;
	echo '		<li><a href="piifmt.php" style="background: url(images/pointer.png) no-repeat 5px 5px;">PII F.M.T. Home</a></li>'.PHP_EOL;
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(1)) {
		echo '		<li><a href="srcScanner.php">Source Scanner</a></li>'.PHP_EOL; }
	echo '		<li><a href="packExp.php">Package Explorer</a></li>'.PHP_EOL;
	echo '		<li><a href="fileExp.php">File Explorer</a></li>'.PHP_EOL;
	echo '		<li><a href="preferences.php">Preferences</a></li>'.PHP_EOL;
	echo '		<li><a href="help.php">Help</a></li>'.PHP_EOL;
	echo '	</ul>'.PHP_EOL;
	// If the user is a manager (there is at least one project to which he is a manager), then we show the 'Admin Pages'.
	if (getProjects($_SESSION['userMail'], true) != 'empty') {
		echo '	<div class="sideBarTitle">Admin Pages</div>'.PHP_EOL;
		echo '	<ul class="buttons">'.PHP_EOL;
		echo '		<li><a href="projectExp.php">Project Explorer</a></li>'.PHP_EOL;
		if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST)) {
			echo '		<li><a href="langAndRules.php">Languages &amp; Rules</a></li>'.PHP_EOL;
		}
		echo '	</ul>'.PHP_EOL;
	}
	?>
</div>

<div id="mainContainer"><i></i>
	<div id="mainContainerVertCenter" align="center">
		<div class="infoPanel" align="center">
		<div class="infoGlue">
			<p class="regularText">Welcome to the PII File Management Tool.</p>
		</div>
		</div>
		<div class="formPanel">
			
		</div>
	</div>
</div>

</body>
</html>