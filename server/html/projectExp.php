<?php
/* 
* @package:		Configuration
* @subpackage:	User Authentication
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/
require_once('../config/constants.php');
require_once('../config/settings.php');
include_once('../utils/functions.php');
require_once('../utils/logger.php');

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

msg_log(DEBUG, "PAGE: projectExp.php", SILENT);

?>
<html>
<head>
	<title>PII F.M.T. - Project Explorer</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script type="text/javascript" src="javascript/dataStore.js"></script>
	<script type="text/javascript" src="javascript/projectExp.js"></script>
	<script type="text/javascript" src="javascript/xmlHttpRequest.js"></script>
	
	<script type="text/javascript">

		// Initialize the users stack
		var userData = new DataStack();
		// Initialize the File Renaming Rules list
		var rulesList = new Object();

		// Get projects
		var projData = new DataStack();
		dbGetProjects("<?php echo $_SESSION['userMail']; ?>");
		
		// Get language list
		var langList = new Object();
		dbGetLanguages();
		
		// Get privileges from the PHP settings
		<?php
			$prvlgCSV = 'Manager,';
			foreach ( $PRVLGS as $prvlg ) {
				$prvlgCSV = $prvlgCSV.$prvlg . ',';
			}
			$prvlgCSV = substr($prvlgCSV, 0, -1);
		?>
		var prvlgList = '<?php echo $prvlgCSV; ?>'.split(',');
	</script>
</head>

<body onload="testBrowser(); clearFields();">

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
		echo '		<li><a href="projectExp.php" style="background: url(images/pointer.png) no-repeat 5px 5px;">Project Explorer</a></li>'.PHP_EOL;
		if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST)) {
			echo '		<li><a href="langAndRules.php">Languages &amp; Rules</a></li>'.PHP_EOL;
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
					<p style="margin-left: 1em;">Projects</p>
				</div>
				<div style="margin-top: 1em;">
					<select size="10" class="dropMenu" id="dropMenuProjects"></select>
				</div>
				<?php
				if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST)) {
					echo '				<div>'.PHP_EOL;
					echo '					<input class="fmtButton" type="submit" name="new_project" value="Create Project" onClick="dbLogAction(\'Create Project\'); return createNewProject()" />'.PHP_EOL;
					echo '					<input class="fmtButton" type="submit" name="del_project" value="Delete Project" onClick="dbLogAction(\'Delete Project\'); return deleteProject(\'' . $_SESSION['userMail'] . '\')" />'.PHP_EOL;
					echo '				</div>'.PHP_EOL;
				}
				?>
				<div>
					<input class="fmtButton" type="submit" name="new_user" value="Create User" onClick="dbLogAction('Create User'); return createNewUser()" />
					<input class="fmtButton" type="submit" name="del_user" value="Delete User" onClick="dbLogAction('Delete User'); return deleteUser()" />
				</div>
			</div>
			<div class="userPanel">
				<div class="titlePanel">
					<p style="margin-left: 1em;">Users and Privileges</p>
				</div>
				<div style="margin-top: 1em;">
					<select size="7" class="dropMenu" id="dropMenuUsers"></select>
					<div class="scroller">
						<table class="fmtTable" id="dropMenuPrivileges" width="280" border="0" cellspacing="0" cellpadding="0"></table>
					</div>
				</div>
			</div>
		</div>
		<div class="mainColumn" style="height: 345px; border-bottom: 1px solid #dadada">
			<div class="titlePanel">
				<p style="margin-left: 1em;" id="projectNameLabel">Project properties</p>
			</div>
			<div class="propPanel">
				<div class="subPanel" style="margin-top: 0;">
					<p align="left" >ClearCase location:</p>
					<p align="left"><input class="fmtInputbox" style="width: 300px; float: left;" type="text" name="ccLocation" id="ccLocation" value="" size="90" /></p>
				</div>
				<div class="subPanel">
					<div style="float: left;">
						<p align="left">ClearCase view:</p>
						<p align="left"><input type="text" class="fmtInputbox" style="width: 140px; float: left;" name="ccView" id="ccView" value="" size="30" /></p>
					</div>
					<div style="float: right">
						<p align="left">ClearCase activity:</p>
						<p align="left"><input type="text" class="fmtInputbox" style="width: 140px; float: left;" name="ccActivity" id="ccActivity" value="" size="30" /></p>
					</div>
				</div>
				<div class="subPanel">
					<p align="left">Code reviewer:</p>
					<p align="left">
						<input class="fmtInputbox" style="width:210px; float:left; color:black; background-color:white;" type="text" name="ccCodeReview" id="ccCodeReview" value="" size="50" disabled />
						<input class="fmtButton" style="width:70px; float:right; margin:0;" type="submit" name="changeReviewer" value="Change" onClick="dbLogAction('Change'); changeReviewer()" />
					</p>
				</div>
				<div class="subPanel" style="margin-top: 2em;">
					<p align="left">FTP server:</p>
					<p align="left"><input class="fmtInputbox" style="width: 300px; float: left;" type="text" name="ftpServer" id="ftpServer" value="" size="90" /></p>
				</div>
				<div class="subPanel">
					<div style="float: left;">
						<p align="left">FTP username:</p>
						<p align="left"><input type="text" class="fmtInputbox" style="width: 140px; float: left;" name="ftpUser" id="ftpUser" value="" size="30" /></p>
					</div>
					<div style="float: right">
						<p align="left">FTP password:</p>
						<p align="left"><input type="password" class="fmtInputbox" style="width: 140px; float: left;" name="ftpPass" id="ftpPass" value="" size="30" /></p>
					</div>
				</div>
			</div>
			<div class="propPanel" style="float: right; width: 290px;">
				<div class="subPanel" style="height:20px; margin:0px;">
					<p align="left">File renaming rules:</p>
				</div>
				<div class="subPanel" style="height:20px; margin: 0 0 2px 0; width:274px;">
					<div class="tableTitle" style="float:left; width:25px;"></div>
					<div class="tableTitle" style="float:left; width:110px;">From:</div>
					<div class="tableTitle" style="float:left; width:130px;">To:</div>
				</div>
				<div class="subPanel" style="width:290px; height:150px; margin:0; overflow:auto;">
					<table class="fmtTable" id="renameTable" style="width: 271px;" border="0" cellspacing="0" cellpadding="0"></table>
				</div>
				<div class="subPanel" style="height:20px; width:274px; margin-top:11px;">
					<input class="fmtButton" style="width:100px; float:left; margin:0;" type="submit" name="addRule" value="Create Rule" onClick="dbLogAction('Create Rule'); addRule()" />
					<input class="fmtButton" style="width:100px; float:right; margin:0;" type="submit" name="delRule" value="Delete Rule" onClick="dbLogAction('Delete Rule'); delRule()" />
				</div>
			</div>
		</div>
		<div class="buttonPanel">
			<input class="fmtButton" style="width:60px; float:right; margin: 15 11px;" type="submit" name="saveProps" value="Save" onClick="dbLogAction('Save'); dbSaveProject()" />
		</div>
	</div>
</div>

</body>
</html>