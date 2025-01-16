<?php
/* 
* @package:		Main
* @subpackage:	Lang
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/
include_once('../config/constants.php');
include_once('../config/settings.php');
include_once('../utils/functions.php');
include_once('../utils/logger.php');

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

msg_log(DEBUG, "PAGE: langAndRules.php", SILENT);
?>
<html>
<head>
	<title>PII F.M.T. - Languages and Renaming Rules</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script type="text/javascript" src="javascript/dataStore.js"></script>
	<script type="text/javascript" src="javascript/langAndRules.js"></script>
	<script type="text/javascript" src="javascript/xmlHttpRequest.js"></script>
	
	<script type="text/javascript">
		
		// Initialize the Languages list
		var langList = new Object();
		dbGetLanguages();
		// Initialize the File Renaming Rules list
		var rulesList = new Object();
		dbGetRenamingRules('default');
		
	</script>
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
	<ul class="buttons">
		<li><a href="piifmt.php">PII F.M.T. Home</a></li>
		<li><a href="srcScanner.php">Source Scanner</a></li>
		<li><a href="packExp.php">Package Explorer</a></li>
		<li><a href="fileExp.php">File Explorer</a></li>
		<li><a href="preferences.php">Preferences</a></li>
		<li><a href="help.php">Help</a></li>
	</ul>
	<?php // If the user is a manager (there is at least one project to which he is a manager), then we show the 'Admin Pages'.
	if (getProjects($_SESSION['userMail'], true) != 'empty') {
		echo '	<div class="sideBarTitle">Admin Pages</div>'.PHP_EOL;
		echo '	<ul class="buttons">'.PHP_EOL;
		echo '		<li><a href="projectExp.php">Project Explorer</a></li>'.PHP_EOL;
		if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST)) {
			echo '		<li><a href="langAndRules.php" style="background: url(images/pointer.png) no-repeat 5px 5px;">Languages &amp; Rules</a></li>'.PHP_EOL;
		}
		echo '	</ul>'.PHP_EOL;
	}
	?>
</div>

<div id="mainContainer"><i></i>
	<div id="mainContainerVertCenter" align="center">
		<div class="mainColumn">
			<div class="projPanel">
				<div class="titlePanel">
					<p style="margin-left: 1em;">Languages</p>
				</div>
				<div class="propPanel" style="float: right; width: 290px;">
					<div class="subPanel" style="height:20px; margin: 0 0 2px 0; width:274px;">
						<div class="tableTitle" style="float:left; width:25px;"></div>
						<div class="tableTitle" style="float:left; width:60px;">Code:</div>
						<div class="tableTitle" style="float:left; width:180px;">Language:</div>
					</div>
					<div class="subPanel" style="width:290px; height:150px; margin:0; overflow:auto;">
						<table class="fmtTable" id="langTable" style="width: 271px;" border="0" cellspacing="0" cellpadding="0"></table>
					</div>
					<div class="subPanel" style="height:20px; width:274px; margin-top:11px;">
						<input class="fmtButton" style="width:110px; float:left; margin:0;" type="submit" name="addLang" value="Add Language" onClick="dbLogAction('Add Language'); addLang()" />
						<input class="fmtButton" style="width:110px; float:right; margin:0;" type="submit" name="delLang" value="Del Language" onClick="dbLogAction('Del Language'); delLang()" />
					</div>
				</div>
			</div>
			<div class="userPanel">
				<div class="titlePanel">
					<p style="margin-left: 1em;">Default Renaming Rules</p>
				</div>
				<div class="propPanel" style="float: right; width: 290px;">
					<div class="subPanel" style="height:20px; margin: 0 0 2px 0; width:274px;">
						<div class="tableTitle" style="float:left; width:25px;"></div>
						<div class="tableTitle" style="float:left; width:110px;">From:</div>
						<div class="tableTitle" style="float:left; width:130px;">To:</div>
					</div>
					<div class="subPanel" style="width:290px; height:150px; margin:0; overflow:auto;">
						<table class="fmtTable" id="defaultRulesTable" style="width: 271px;" border="0" cellspacing="0" cellpadding="0"></table>
					</div>
					<div class="subPanel" style="height:20px; width:274px; margin-top:11px;">
						<input class="fmtButton" style="width:100px; float:left; margin:0;" type="submit" name="addRule" value="Create Rule" onClick="dbLogAction('Create Rule'); addRule()" />
						<input class="fmtButton" style="width:100px; float:right; margin:0;" type="submit" name="delRule" value="Remove Rule" onClick="dbLogAction('Remove Rule'); delRule()" />
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

</body>
</html>