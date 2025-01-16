<?php
/* 
* @package:		Configuration
* @subpackage:	User Authentication
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/
require_once('../config/constants.php');
require_once('../config/settings.php');
require_once('../utils/logger.php');
require_once('../utils/functions.php');
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

msg_log(DEBUG, "PAGE: preferences.php", SILENT);

global $errMessage;

// Get list of projects
$csvData = getProjects($_SESSION['userMail'], false);

if ($csvData && $csvData != 'empty') {
	
	$projectList = explode(",", $csvData);

	// Get the name of the current working project
	$currProject = getCurrProj($_SESSION['userMail']);
	
	if ($currProject == UNKNOWN_ERROR || $currProject == 'empty') {
		$currProject = false;
		msg_log(WARN, "Failed to determine project. Please log off and log in again.", NOTIFY);
	}
	
	// Get user's data
	$userData = new FMTUser($currProject, $_SESSION['userMail']);
	
} else {
	$userData = false;
	$projectList = false;
	$currProject = false;
	msg_log(WARN, "Failed to get projects. Please log off and log in again.", NOTIFY);
}

if (isset($_POST['ccUser']) && !empty($_POST['ccUser'])) {
	$ccUser = $_POST['ccUser'];
} else {
	$ccUser = '';
}

if (isset($_POST['ccPass']) && !empty($_POST['ccPass'])) {
	$ccPass = $_POST['ccPass'];
} else {
	$ccPass = '';
}

if (isset($_POST['saveCC']) && !empty($_POST['saveCC'])) {
	
	// Store the new user credentials in the database
	if ( !setAuthCC( $_SESSION['userMail'], $ccUser, $ccPass ) ) {
		msg_log(WARN, "Failed to save ClearCase settings to the database. Please notify the administrator.", NOTIFY);
	} else {
		
		// We retrieve the entire list of credentials because the user might have updated only the VIEW name.
		// In that case the password will be set to secret and the ClearCase login would fail
		$csvData = getAuthCC($_SESSION['userMail']);
		
		if ($csvData && $csvData != 'empty') {
			
			$ccAuthTokens = explode(',', $csvData);
			
			// If new pass has not been set, there is no need to generate new authentication key
			if ( !empty($ccPass) && $ccPass != 'secret') {
				// Create password-less public key authentication
				if (!setPassLessPubKeyAuth(CC_HOST, $_SERVER['SERVER_NAME'], $ccAuthTokens[0], $ccAuthTokens[1])) {
					msg_log(WARN, "Failed to generate authentication key.", NOTIFY);
				}
			}
			
			// If the DB has been updated with the new credentials, then we update the GUI
			$ccUser = $ccAuthTokens[0];
			if ( !empty($ccAuthTokens[1]) ) {
				$ccPass = 'secret';
			} else {
				$ccPass = '';
			}
		} else {
			msg_log(WARN, "Failed to retrieve the new ClearCase settings in order to generate authentication key.", NOTIFY);
		}
	}
	
} else {

	// Update the fields with info from the DB
	$csvData = getAuthCC($_SESSION['userMail']);
	
	if (!$csvData || $csvData == 'empty') {
		$ccUser = '';
		$ccPass = '';
	} else {
		$ccAuthTokens = explode(",", $csvData);
		$ccUser = $ccAuthTokens[0];
		if ( !empty($ccAuthTokens[1]) ) {
			$ccPass = 'secret';
		} else {
			$ccPass = '';
		}
	}
}
?>
<html>
<head>
	<title>PII F.M.T. - Preferences</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script type="text/javascript" src="javascript/functions.js"></script>
	<script type="text/javascript" src="javascript/preferences.js"></script>
	<script type="text/javascript" src="javascript/xmlHttpRequest.js"></script>
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
	echo '		<li><a href="piifmt.php">PII F.M.T. Home</a></li>'.PHP_EOL;
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(1)) {
		echo '		<li><a href="srcScanner.php">Source Scanner</a></li>'.PHP_EOL; }
	echo '		<li><a href="packExp.php">Package Explorer</a></li>'.PHP_EOL;
	echo '		<li><a href="fileExp.php">File Explorer</a></li>'.PHP_EOL;
	echo '		<li><a href="preferences.php" style="background: url(images/pointer.png) no-repeat 5px 5px;">Preferences</a></li>'.PHP_EOL;
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
		
		<div class="mainColumn" style="height:100px;">
			<div class="titlePanel">
				<p style="margin-left: 1em;">Current working project</p>
			</div>
			<div class="propPanel" style="height:30px; width:450px; margin-top:10px;">
				<div style="float:left; margin: 10px 1em 1em 1em;">
					<select class="dropMenu" style="width:250px;" id="projSelector">
					<?php for ($i=0; $i<count($projectList); $i=$i+9) {
						if ($projectList[$i] == $currProject) {
							echo '						<option value="'.$projectList[$i].'" onClick="dbLogAction(\'selectProject\'); dbSetCurrProj(\''.$_SESSION['userMail'].'\', \''.$projectList[$i].'\');" SELECTED>'.$projectList[$i].'</option>'.PHP_EOL;
						} else {
							echo '						<option value="'.$projectList[$i].'" onClick="dbLogAction(\'selectProject\'); dbSetCurrProj(\''.$_SESSION['userMail'].'\', \''.$projectList[$i].'\');">'.$projectList[$i].'</option>'.PHP_EOL;
						}
					}?>
					</select>
				</div>
			</div>
		</div>
		
		<div class="mainColumn" style="height:120px;">
			<div class="titlePanel">
				<p style="margin-left: 1em;">ClearCase authentication</p>
			</div>
			
			<form method="post" id="ccAuthPanel" name="ccAuthPanel" action="preferences.php">
			<div class="propPanel" style="height:30px; width:600px; margin-top:10px;">
				<div style="float:left; margin-left: 1em;">
					<p align="left">Username:</p>
					<p align="left"><input type="text" class="fmtInputbox" style="width: 120px; float: left;" name="ccUser" id="ccUser" value="<?php echo $ccUser; ?>" size="30" /></p>
				</div>
				<div style="float:left; margin-left:2em;">
					<p align="left">Password:</p>
					<p align="left"><input type="password" class="fmtInputbox" style="width: 120px; float: left;" name="ccPass" id="ccPass" value="<?php echo $ccPass; ?>" size="30" /></p>
				</div>
				<div style="float:right; margin-left:2em;">
					<p align="left" style="height:20px;"></p>
					<p align="left"><input class="fmtButton" style="width:60px; margin:0;" type="submit" id="saveCC" name="saveCC" value="Save" onClick="dbLogAction('Save'); popMessage('Generating authentication key. Please wait a few moments..', 'wait', 'preferences');" /></p>
				</div>
			</div>
			</form>
			
		</div>
	</div>
</div>

<?php // Routine to display error messages set in logger.php
if ( !empty($errMessage) ) {
	echo '<script type="text/javascript">popMessage("'.$errMessage.'", "warn", "preferences");</script>';
}
?>

</body>
</html>