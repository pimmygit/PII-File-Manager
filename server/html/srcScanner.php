<?php
/* 
* @package:		File Manager
* @subpackage:	Main
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/
require_once('../config/constants.php');
require_once('../config/settings.php');
require_once('../utils/logger.php');
require_once('../utils/functions.php');
require_once('../utils/fileHandler.php');
require_once('../utils/PIIFile.class.php');
require_once('../utils/FMTUser.class.php');
require_once('../sourceControl/ClearCase.class.php');

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

msg_log(DEBUG, "PAGE: srcScanner.php", SILENT);

global $errMessage;

// Initialize array of PII files information
$piiFileList = Array();
// Initialize info bar data
$totalFiles = 0;
$totalSize = 0;
$scanDate = '';
$scanTime = '';
$rootDir = '';
$scannedBy = '';

// Get posted data
if (isset($_POST['scanType']) && !empty($_POST['scanType'])) { $scanType = $_POST['scanType']; } else { $scanType = 'all'; }
if (isset($_POST['extractMark']) && !empty($_POST['extractMark'])) { $extractMark = true; } else { $extractMark = false; }
if (isset($_POST['totalFiles']) && !empty($_POST['totalFiles'])) { $totalFiles = $_POST['totalFiles']; }
if (isset($_POST['totalSize']) && !empty($_POST['totalSize'])) { $totalSize = $_POST['totalSize']; }
if (isset($_POST['scanDate']) && !empty($_POST['scanDate'])) { $scanDate = $_POST['scanDate']; }
if (isset($_POST['scanTime']) && !empty($_POST['scanTime'])) { $scanTime = $_POST['scanTime']; }
if (isset($_POST['rootDir']) && !empty($_POST['rootDir'])) { $rootDir = $_POST['rootDir']; }
if (isset($_POST['scannedBy']) && !empty($_POST['scannedBy'])) { $scannedBy = $_POST['scannedBy']; }

// Initialize options selection
if ( $scanType == 'all' ) {
	$optScanAll = 'checked';
	$optScanNew = '';
} else {
	$optScanAll = '';
	$optScanNew = 'checked';
}

// Get the name of the current working project
$currProject = getCurrProj($_SESSION['userMail']);

if (empty($currProject) && $currProject != 'empty') {
	msg_log(WARN, "Failed to determine project. Please log off and log in again.", NOTIFY);
}

// Get user's data
$userData = new FMTUser($currProject, $_SESSION['userMail']);

if (isset($_POST['btnAction']) && !empty($_POST['btnAction'])) {
	
	switch ($_POST['btnAction']) {
		
		case 'Set Translated' :
			
			if (isset($_POST['selectedFile'])) {
				
				$filesModified = 0;
				
				// Get ClearCase login details
				list($ccUser, $ccPass) = explode(',', getAuthCC($_SESSION['userMail']));
				
				// Create connection to ClearCase
				$ccClient = new ClearCase($ccUser, $ccPass);
				
				// Upload the executable script
				if ($ccClient->isConnected()) {
					
					msg_log(DEBUG, 'Uploading script to ClearCase client.', SILENT);
					
					$scriptPath = $_SERVER['DOCUMENT_ROOT'].'/scripts/markPIIFileTranslated.sh';
					$dstPath = '/var/tmp/piifmt/scripts/markPIIFileTranslated.sh';
					
					if (!$sshStream = ssh2_exec($ccClient->getConnection(), "mkdir -p '" . dirname($dstPath) . "';") ) {
						msg_log(ERROR, "Failed to execute SSH2 command: [mkdir -p '" . dirname($dstPath) . "'].", NOTIFY);
						return false;
					}
					stream_set_blocking( $sshStream, true );
					$res = stream_get_contents($sshStream);
					
					if ( !ssh2_scp_send($ccClient->getConnection(), $scriptPath, $dstPath, 0775) ) {
						msg_log(ERROR, "Failed to copy script [".$dstPath."].", NOTIFY);
						return false;
					}
					
				} else {
					msg_log(ERROR, "Failed to connect to ClearCase server.", NOTIFY);
					break;
				}
				
				// Get ClearCase view from project properties
				$ccView = getViewCC($currProject);
				
				foreach ($_POST['selectedFile'] as $item) {
					
					// First check if the file list is from a fresh scan i.e. the files are with the real ClearCase path
					// If they are from a saved scan (in the PII F.M.T. repository) then we have to generate the real ClearCase path
					if ( isset($_POST['scanValue']) && !empty($_POST['scanValue']) ) {
						// Generate the ClearCase file name including the absolute path from the location in the PII F.M.T. repository
						//>>---------------------------------------------------------
						// 1. Add the project name to the PII F.M.T. repository root
						$scanRoot = PII_ROOT . "/" . str_replace(" ", "", $currProject);
						// 2. Add the name of the scan to the path
						$scanRoot = $scanRoot . "/" . str_replace(" ", "", $_POST['scanValue']);
						// 3. Replace ClearCase root with the scan root directory path
						$fPath = str_replace( $scanRoot, CC_ROOT, $item);
						//<<---------------------------------------------------------
						
						if ( $ccClient->setFlagTranslated($ccView, $fPath, 'yes') ) {
							$filesModified++;
						}
						
					} else {
						
						if ( $ccClient->setFlagTranslated($ccView, $item, 'yes') ) {
							$filesModified++;
						}
					}
				}
				
				msg_log(DEBUG, $filesModified . " files modified.", SILENT);
				
				if ( sizeof($_POST['selectedFile']) != $filesModified ) {
					msg_log(WARN, "Failed to modify attribute TRANSLATED on some files.", NOTIFY);
				}
			}
			break;
			
		case 'Unset Translated' :
			
			if (isset($_POST['selectedFile'])) {
				
				$filesModified = 0;
				
				// Get ClearCase login details
				list($ccUser, $ccPass) = explode(',', getAuthCC($_SESSION['userMail']));
				
				// Create connection to ClearCase
				$ccClient = new ClearCase($ccUser, $ccPass);

				// Upload the executable script
				if ($ccClient->isConnected()) {
					
					msg_log(DEBUG, 'Uploading script to ClearCase client.', SILENT);
					
					$scriptPath = $_SERVER['DOCUMENT_ROOT'].'/scripts/markPIIFileTranslated.sh';
					$dstPath = '/var/tmp/piifmt/scripts/markPIIFileTranslated.sh';
					
					if (!$sshStream = ssh2_exec($ccClient->getConnection(), "mkdir -p '" . dirname($dstPath) . "';") ) {
						msg_log(ERROR, "Failed to execute SSH2 command: [mkdir -p '" . dirname($dstPath) . "'].", NOTIFY);
						return false;
					}
					stream_set_blocking( $sshStream, true );
					$res = stream_get_contents($sshStream);
					
					if ( !ssh2_scp_send($ccClient->getConnection(), $scriptPath, $dstPath, 0775) ) {
						msg_log(ERROR, "Failed to copy script [".$dstPath."].", NOTIFY);
						return false;
					}
					
				} else {
					msg_log(ERROR, "Failed to connect to ClearCase server.", NOTIFY);
					break;
				}
								
				// Get ClearCase view from project properties
				$ccView = getViewCC($currProject);
				
				foreach ($_POST['selectedFile'] as $item) {
					
					// First check if the file list is from a fresh scan i.e. the files are with the real ClearCase path
					// If they are from a saved scan (in the PII F.M.T. repository) then we have to generate the real ClearCase path
					if ( isset($_POST['scanValue']) && !empty($_POST['scanValue']) ) {
						// Generate the ClearCase file name including the absolute path from the location in the PII F.M.T. repository
						//>>---------------------------------------------------------
						// 1. Add the project name to the PII F.M.T. repository root
						$scanRoot = PII_ROOT . "/" . str_replace(" ", "", $currProject);
						// 2. Add the name of the scan to the path
						$scanRoot = $scanRoot . "/" . str_replace(" ", "", $_POST['scanValue']);
						// 3. Replace ClearCase root with the scan root directory path
						$fPath = str_replace( $scanRoot, CC_ROOT, $item);
						//<<---------------------------------------------------------
						
						if ( $ccClient->setFlagTranslated($ccView, $fPath, 'no') ) {
							$filesModified++;
						}
						
					} else {
						
						if ( $ccClient->setFlagTranslated($ccView, $item, 'no') ) {
							$filesModified++;
						}
					}
				}
				
				msg_log(DEBUG, $filesModified . " files modified.", SILENT);
				
				if ( sizeof($_POST['selectedFile']) != $filesModified ) {
					msg_log(WARN, "Failed to modify attribute TRANSLATED on some files.", NOTIFY);
				}
			}
			break;
			
		case 'Create Package' :
			
			if (isset($_POST['piifile']) && !empty($_POST['piifile']) &&
				isset($_POST['scanValue']) && !empty($_POST['scanValue']) ) {
				
				$totalFilesSaved = 0;
				$result = array();
				
				// Get ClearCase login details
				list($ccUser, $ccPass) = explode(',', getAuthCC($_SESSION['userMail']));

				// Create connection to ClearCase
				$ccClient = new ClearCase($ccUser, $ccPass);

				// Upload the executable script
				if ($ccClient->isConnected()) {
					
					msg_log(DEBUG, 'Uploading script to ClearCase client.', SILENT);
					
					$scriptPath = $_SERVER['DOCUMENT_ROOT'].'/scripts/getPIIFile.sh';
					$dstPath = '/var/tmp/piifmt/scripts/getPIIFile.sh';
					
					if (!$sshStream = ssh2_exec($ccClient->getConnection(), "mkdir -p '" . dirname($dstPath) . "';") ) {
						msg_log(ERROR, "Failed to execute SSH2 command: [mkdir -p '" . dirname($dstPath) . "'].", NOTIFY);
						return false;
					}
					stream_set_blocking( $sshStream, true );
					$res = stream_get_contents($sshStream);
					
					if ( !ssh2_scp_send($ccClient->getConnection(), $scriptPath, $dstPath, 0775) ) {
						msg_log(ERROR, "Failed to copy script [".$dstPath."].", NOTIFY);
						return false;
					}
					flush();
					$ccClient->disconnect();
					sleep(1);
				} else {
					msg_log(ERROR, "Failed to connect to ClearCase server.", NOTIFY);
					break;
				}
				
				// Get ClearCase view from project properties
				$ccView = getViewCC($currProject);
				
				foreach ($_POST['piifile'] as $item) {
					// Populate array with data in order to display the filenames again
					array_push($result, $item);
					
					// Split into tokens in order to retrieve the full filename to be extracted from ClearCase
					$piiFileToken = explode(",", $item);
					
					// Generate the target file name including the absolute path
					//>>---------------------------------------------------------
					// 1. Add the project name to the PII F.M.T. repository root
					$scanRoot = PII_ROOT . "/" . str_replace(" ", "", $currProject);
					// 2. Add the name of the scan to the path
					$scanRoot = $scanRoot . "/" . str_replace(" ", "", $_POST['scanValue']);
					// 3. Replace ClearCase root with the scan root directory path
					$dstFile = str_replace( CC_ROOT, $scanRoot, $piiFileToken[2]);
					//<<---------------------------------------------------------
					
					// Create connection to ClearCase
					// Intentionally we create connection for every file, otherwise if one
					// transaction fails, it blocks the entire session
					$ccClient = new ClearCase($ccUser, $ccPass);

					// Fetch the PII files from ClearCase into the PII F.M.T. repository
					if ($ccClient->isConnected()) {
						if ( $ccClient->getPIIFile($ccUser, $ccView, $piiFileToken[2], $dstFile, $scanType) ) {
							$totalFilesSaved++;
						} else {
							$errMessage = "Failed to add some files to the package.";
						}
					}
					$ccClient->disconnect();
				}
			}
			break;
			
		case 'Scan Now' :
			
			// Reset scan selection
			unset($_POST['scanValue']);
			// Reset info bar data
			$totalFiles = 0;
			$totalSize = 0;
			$scanDate = '';
			$scanTime = '';
			$rootDir = '';
			$scannedBy = '';
			
			// Set the default timezone to use. (Available since PHP 5.1)
			date_default_timezone_set('UTC');
			// Record the starting point of the scan as seconds
			$scanTimeStart = time();
			// Record the starting point of the scan as datetime
			$scanDate = date('Y-m-d H:i');
			// Record the person performing the scan
			$scannedBy = $_SESSION['userName'];
			
			// Get ClearCase login details
			list($ccUser, $ccPass) = explode(',', getAuthCC($_SESSION['userMail']));
			if ( empty($ccUser) || empty($ccPass) ) {
				msg_log(WARN, "ClearCase username/password for user [".$_SESSION['userName']."] MISSING from the users preferences. Aborting scan.", NOTIFY);
				break;
			} else {
				msg_log(DEBUG, "ClearCase username/password retrieved correctly for user [".$_SESSION['userName']."].", SILENT);
			}
			
			// Get location to scan for PII files
			$rootDir = getLocationCC($currProject);
			if ( empty($rootDir) ) {
				msg_log(WARN, "ClearCase root location NOT SPECIFIED for project [".$currProject."]. Aborting scan.", NOTIFY);
				break;
			} else {
				msg_log(DEBUG, "ClearCase root location for project [".$currProject."] is [".$rootDir."].", SILENT);
			}
			// Create connection to ClearCase
			$ccClient = new ClearCase($ccUser, $ccPass);
			
			// Scan ClearCase for PII files
			if ($ccClient->isConnected()) {
				
				// Get ClearCase view from project properties
				$ccView = getViewCC($currProject);
				
				msg_log(DEBUG, 'Uploading script to ClearCase client.', SILENT);
				
				$scriptPath = $_SERVER['DOCUMENT_ROOT'].'/scripts/scanPIIFiles.sh';
				$dstPath = '/var/tmp/piifmt/scripts/scanPIIFiles.sh';
				
				if (!$sshStream = ssh2_exec($ccClient->getConnection(), "mkdir -p '" . dirname($dstPath) . "';") ) {
					msg_log(ERROR, "Failed to execute SSH2 command: [mkdir -p '" . dirname($dstPath) . "'].", NOTIFY);
					return false;
				}
				stream_set_blocking( $sshStream, true );
				$res = stream_get_contents($sshStream);
		
				if ( !ssh2_scp_send($ccClient->getConnection(), $scriptPath, $dstPath, 0775) ) {
					msg_log(ERROR, "Failed to copy script [".$dstPath."].", NOTIFY);
					return false;
				}
				
				$result = $ccClient->scanPIIFiles($ccView, $rootDir, $scanType);
			} else {
				$errMessage = "Failed to connect to ClearCase server.";
				break;
			}
			
			if (!$result && empty($errMessage) ) {
				msg_log(WARN, "Internal server error. Please inform the administrator.", NOTIFY);
				//break;
			}
			
			// Calculate the time taken to scan the project source
			$scanTimeDelta = time() - $scanTimeStart;
			// There is no way the scan to take more than 24 hours ;-)
			$scanTimeHrs = date('G', $scanTimeDelta);
			$scanTimeMin = date('i', $scanTimeDelta);
			$scanTimeSec = date('s', $scanTimeDelta);
			if ($scanTimeHrs > 0) {
				$scanTime = $scanTime . $scanTimeHrs . 'h, ';
			}
			if ($scanTimeMin > 0 || $scanTimeHrs > 0) {
				$scanTime = $scanTime . $scanTimeMin . 'm, ';
			}
			if ($scanTimeSec > 0  || $scanTimeHrs > 0 || $scanTimeMin > 0) {
				$scanTime = $scanTime . $scanTimeSec . 's';
			}
			
			break;
		
		default :
			break;
	}
}
?>
<html>
<head>
	<title>Source Scanner - PII F.M.T.</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script type="text/javascript" src="javascript/functions.js"></script>
	<script type="text/javascript" src="javascript/srcScanner.js"></script>
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
		echo '		<li><a href="srcScanner.php" style="background: url(images/pointer.png) no-repeat 5px 5px;">Source Scanner</a></li>'.PHP_EOL; }
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

<form method="post" id="srcPanel" name="srcPanel" action="srcScanner.php">

<div class="containerTop">
	<?php
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(3)) {
		echo '	<div class="fmtCheckbox" style="margin:0;">'.PHP_EOL;
			// User should not be able to save scan which hasn't happen yet
			if (isset($_POST['btnAction']) && !empty($_POST['btnAction']) && ($_POST['btnAction'] == 'Scan Now')) {
				echo '			<input class="fmtButton" style="width:130px; margin: 10px 20px 0 10px;" type="submit" name="btnAction" value="Create Package" onclick="dbLogAction(\'Create Package\'); return savePIIScan(\''.$currProject.'\', \'true\');" />';
			} else {
				echo '			<input class="fmtButton" style="width:130px; margin: 10px 20px 0 10px;" type="submit" name="btnAction" value="Create Package" onclick="return savePIIScan(\''.$currProject.'\', \'false\');" />';
			}
		echo '	</div>'.PHP_EOL;
	}
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(2)) {
		echo '	<div class="fmtCheckbox" style="margin:0;">'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:120px; margin: 10px 20px 0 10px;" type="submit" name="btnAction" value="Set Translated" onclick="dbLogAction(\'Set Translated\'); return initSetTranslated();"/>'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:120px; margin: 10px 20px 0 10px;" type="submit" name="btnAction" value="Unset Translated"  onclick="dbLogAction(\'Unset Translated\'); return initSetTranslated();"/>'.PHP_EOL;
		echo '	</div>'.PHP_EOL;
	}
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(1)) {
		echo '	<div class="fmtCheckbox" style="float:right; margin:0;">'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:130px; margin: 10px 20px 0 10px" type="submit" name="btnAction" value="Scan Now" onClick="dbLogAction(\'Scan Now\'); popMessage(\'Scanning ClearCase for PII, please wait..\', \'wait\');"/>'.PHP_EOL;
		echo '	</div>'.PHP_EOL;
		echo '	'.PHP_EOL;
		echo '	<div class="fmtCheckbox" style="float:right; margin: 7px 20px 0 10px;">'.PHP_EOL;
		echo '		<input type="radio" name="scanType" value="all" '.$optScanAll.' onclick="dbLogAction(\'Find All PII\'); document.getElementById(\'scanTypeValue\').value = \'all\';"> Find All PII'.PHP_EOL;
		echo '		<br />'.PHP_EOL;
		echo '		<input type="radio" name="scanType" value="new" '.$optScanNew.' onclick="dbLogAction(\'Find New PII\'); document.getElementById(\'scanTypeValue\').value = \'new\';"> Find New PII'.PHP_EOL;
		echo '	</div>'.PHP_EOL;
	}
	?>
</div>

<div class="containerTop" style="margin-top:10px; top:160px; height:20px; background-color:#DCDCDC;">
	<div class="tableTitle" style="float:left; border: 0px solid #CCCCCC; width:25px;"><input type="checkbox" id="checkAll" name="checkAll" value="Check All" onclick="selectDeselect(this);" /></div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:510px;">File Name</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:100px;">Size</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:150px;">Date</div>
</div>

<div class="containerExtend" style="border-top: 1px solid #CCCCCC;">
<table class="fmtTable" id="piiFilesTable" style="width: 785px;" border="0" cellspacing="0" cellpadding="0">
<?php if ( isset($result) && is_array($result) && (count($result) > 0)) {
	
	$totalFiles = 0;
	$totalSize = 0;
	
	foreach ($result as $item) {
		
		$piiFileToken = explode(",", $item);
		
		$totalFiles++;
		$totalSize += $piiFileToken[0];
		
		if (strlen($piiFileToken[2]) > 65) {
			$strippedFilename = '..' . substr($piiFileToken[2], -65);
		}
		
		echo '<tr>'.PHP_EOL;
		echo '	<td class="checkbox"><input type="checkbox" name="selectedFile[]" value="'.$piiFileToken[2].'"/></td>'.PHP_EOL;
		echo '	<td style="width:510px; padding-left:5px;">'.$strippedFilename.'</td>'.PHP_EOL;
		echo '	<td style="width:100px; padding-left:10px;">'.$piiFileToken[0].'</td>'.PHP_EOL;
		echo '	<td style="width:150px; padding-left:10px;">'.$piiFileToken[1].'</td>'.PHP_EOL;
		echo '	<td style="width:10px; padding-left:0px;"><input type="hidden" name="piifile[]" value="'.$item.'" /></td>'.PHP_EOL;
		echo '</tr>';
	}
	
	if (isset($totalFilesRec) && ($totalFilesRec != $totalFiles) ) {
		msg_log(WARN, "Scan [".$_POST['scanValue']."] has [".$totalFiles."] files, which differs from the scan result [".$totalFilesRec."]. Repository tampered.", NOTIFY);
		msg_log(DEBUG, "Resetting scan [".$_POST['scanValue']."] properties in order to reflect the new repository content.", SILENT);
		updatePIIScan( $currProject, $_POST['scanValue'], $totalFiles, $totalSizeRec, $scanDate, $scanTime, $rootDir, $scannedBy, 'Modified' );
	}
	if (isset($totalSizeRec) && ($totalSizeRec != $totalSize) ) {
		msg_log(WARN, "Scan [".$_POST['scanValue']."] has total file size of [".$totalSize."] bytes, which differs from what the scan returned [".$totalSizeRec."]. Repository tampered.", NOTIFY);
		msg_log(DEBUG, "Resetting scan [".$_POST['scanValue']."] properties in order to reflect the new repository content.", SILENT);
		updatePIIScan( $currProject, $_POST['scanValue'], $totalFilesRec, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, 'Modified' );
	}
}?>
</table>
</div>

<div class="containerBottom" style="background-color:#EEEEEE">
	<div style="float:left; margin:0; padding:0; width:80px; height:50px;">
		<div style="margin:5px 0 5px 10px;">Total Files: </div>
		<div style="margin:5px 0 5px 10px;">Total Size: </div>
	</div>
	<div style="float:left; margin:0; padding:0; width:80px; height:50px;">
		<div id="labelTotalFiles" style="margin:5px 5px 5px 0; font-weight:normal;"><?php echo $totalFiles; ?></div>
		<div id="labelTotalSizeKB" style="margin:5px 5px 5px 0; font-weight:normal;"><?php echo round($totalSize / 1024) . ' kb'; ?></div>
	</div>
	<div style="float:left; margin:0; padding:0; width:80px; height:50px;">
		<div style="margin:5px 0 5px 10px;">Timestamp: </div>
		<div style="margin:5px 0 5px 10px;">Scan time: </div>
	</div>
	<div style="float:left; margin:0; padding:0; width:110px; height:50px;">
		<div id="labelScanDate" style="margin:5px 5px 5px 0; font-weight:normal;"><?php echo $scanDate; ?></div>
		<div id="labelScanTime" style="margin:5px 5px 5px 0; font-weight:normal;"><?php echo $scanTime; ?></div>
	</div>
	<div style="float:left; margin:0; padding:0; width:85px; height:50px;">
		<div style="margin:5px 0 5px 10px;">Root dir: </div>
		<div style="margin:5px 0 5px 10px;">User name: </div>
	</div>
	<div style="float:left; margin:0; padding:0; width:180px; height:50px;">
		<div id="labelRootDir" style="margin:5px 5px 5px 0; font-weight:normal;"><?php echo $rootDir; ?></div>
		<div id="labelScannedBy" style="margin:5px 5px 5px 0; font-weight:normal;"><?php echo $scannedBy; ?></div>
	</div>
	<div style="float:left; margin:0; padding:0; width:60px; height:50px;">
		<div style="margin:5px 0 5px 10px;">State: </div>
		<div style="margin:5px 0 5px 10px;"></div>
	</div>
	<div style="float:left; margin:0; padding:0; width:110px; height:50px;">
		<div id="labelScanState" style="margin:5px 5px 5px 0; font-weight:normal;"><?php if ( isset($scanState) ) { echo $scanState; }?></div>
		<div id="labelNotDefined" style="margin:5px 5px 5px 0; font-weight:normal;"><?php echo ''; ?></div>
	</div>
</div>

<input type="hidden" id="scanValue" name="scanValue" value="<?php if (isset($_POST['scanValue']) && !empty($_POST['scanValue']) ) { echo $_POST['scanValue']; }?>" />
<input type="hidden" id="scanTypeValue" name="scanTypeValue" value="<?php echo $scanType; ?>" />
<input type="hidden" id="totalFiles" name="totalFiles" value="<?php echo $totalFiles; ?>" />
<input type="hidden" id="totalSize" name="totalSize" value="<?php echo $totalSize; ?>" />
<input type="hidden" id="scanDate" name="scanDate" value="<?php echo $scanDate; ?>" />
<input type="hidden" id="scanTime" name="scanTime" value="<?php echo $scanTime; ?>" />
<input type="hidden" id="rootDir" name="rootDir" value="<?php echo $rootDir; ?>" />
<input type="hidden" id="scannedBy" name="scannedBy" value="<?php echo $scannedBy; ?>" />
</form>

<?php // Routine to display error messages set in logger.php
if ( !empty($errMessage) ) {
	echo '<script type="text/javascript">popMessage("'.htmlentities($errMessage).'", "warn");</script>';
}
?>

</body>
</html>