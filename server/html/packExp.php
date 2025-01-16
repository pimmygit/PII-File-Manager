<?php
/* 
* @package:		Package Explorer
* @subpackage:	Main
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/
require_once('../config/constants.php');
require_once('../config/settings.php');
require_once('../utils/logger.php');
require_once('../utils/functions.php');
require_once('../utils/fileHandler.php');
require_once('../utils/FTP.class.php');
require_once('../utils/FMTUser.class.php');
require_once('../utils/FMTZipArchive.class.php');

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

msg_log(DEBUG, "PAGE: packExp.php", SILENT);

global $errMessage;
$infoMsg = '';

// Get the name of the current working project
$currProject = getCurrProj($_SESSION['userMail']);

if (empty($currProject) && $currProject != 'empty') {
	msg_log(WARN, "Failed to determine project. Please log off and log in again.", NOTIFY);
}

// Get user's data
$userData = new FMTUser($currProject, $_SESSION['userMail']);

if (isset($_POST['btnAction']) && !empty($_POST['btnAction'])) {

	switch ($_POST['btnAction']) {
		
		case 'Upload' :
			
			if (isset($_FILES['uplFile']['name']) && !empty($_POST['pkgName'])) {
				
				msg_log(DEBUG, "Uploading file [".basename($_FILES['uplFile']['name'])."] from local machine.", SILENT);
				
				// Check if the file to be taken from FTP is a ZIP archive
				if (!eregi('(.zip)$', basename($_FILES['uplFile']['name']))) {
					msg_log(WARN, "File [".basename($_FILES['uplFile']['name'])."] is not a translation package.", NOTIFY);
					break;
				}

				// I. Get the file to a TEMP location
				//---------------------------------------------------------
				// Define the TEMP archive
				$tmpArchive = "/var/piifmt/tmp/" . basename($_FILES['uplFile']['name']);
				
				if (!move_uploaded_file($_FILES['uplFile']['tmp_name'], $tmpArchive)) {
					msg_log(WARN, "Failed to upload file [".$tmpArchive."].", NOTIFY);
					break;
				}
			
				// II. Extract the file to the scan location
				
				// Generate the absolute path of the package root location to be created
				// 1. Add the project name to the PII F.M.T. repository root
				$packLocation = PII_ROOT . "/" . str_replace(" ", "", $currProject);
				// 2. Add the name of the file to the path
				$packLocation = $packLocation . "/" . str_replace(" ", "", $_POST['pkgName']);
				
				$zipArchive = new FMTZipArchive();
				
				if ($zipArchive->open($tmpArchive)!==TRUE) {
				    msg_log(WARN, "Failed to open ZIP archive [".basename($_FILES['filePack']['name'])."].", NOTIFY);
				    $zipArchive->close();
				    rm_rf($tmpArchive);
				    break;
				}
				
				if (!$zipArchive->extractTo($packLocation . '/')) {
					msg_log(WARN, "Failed to extract package [".basename($_FILES['uplFile']['name'])."].", NOTIFY);
					$zipArchive->close();
					rm_rf($tmpArchive);
					break;
				}
				
				$totalFiles = get_num_files($packLocation);
				$totalSize = get_size($packLocation);
				$scanDate = date('Y-m-d H:i');
				$scanTime = 0;
				$rootDir = getLocationCC($currProject);
				$scannedBy = $_SESSION['userName'];
				
				$zipArchive->close();
				
				// III. Remove the TEMP file
				rm_rf($tmpArchive);
				
				// IV. Add the scan to the list in the database
				savePIIScan( $currProject, $_POST['pkgName'], 0, $totalFiles, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, $userData->getLang(), "from FTP" );
				
				// IV. Set the ownership and permissions
				chgrp_r($packLocation, 'dev');
				chmod_r($packLocation, 0770);
				
				
			}
			break;
			
		case 'Download' :
			
			if (isset($_POST['selectedPack'])) {
				
				foreach ($_POST['selectedPack'] as $dnlPack) {
					
					msg_log(DEBUG, "Sending package [".$dnlPack."] to a local machine (User download).", SILENT);
					
					// I. CREATE A ZIP ARCHIVE
					//------------------------------------------------------------
					// Generate the absolute path of the package root location to be deleted
					//>>---------------------------------------------------------
					// 1. Add the project name to the PII F.M.T. repository root
					$packLocation = PII_ROOT . "/" . str_replace(" ", "", $currProject);
					// 2. Add the name of the package to the path
					$packLocation = $packLocation . "/" . str_replace(" ", "", $dnlPack);
					//<<---------------------------------------------------------
					
					$zipArchive = new FMTZipArchive();
					$dstArchive = "/var/piifmt/tmp/".str_replace(" ", "", $dnlPack).".zip";
					
					if ($zipArchive->open($dstArchive, ZIPARCHIVE::CREATE)!==TRUE) {
					    msg_log(WARN, "Failed to create ZIP archive [".$dstArchive."].", NOTIFY);
					}
					
					msg_log(DEBUG, "Compressing directory [".$packLocation."] to [".$dstArchive."].", SILENT);
					
					$zipArchive->addDir($packLocation, '/');
					$zipArchive->close();
					
					// II. DOWNLOAD THE ZIP ARCHIVE
					header("Pragma: public");
					header("Expires: 0");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Cache-Control: private", false);
					header("Content-Type: application/zip");
					header("Content-Disposition: attachment; filename=\"".basename($dstArchive)."\";");
					header("Content-Transfer-Encoding: binary");
					header("Content-Length: ".@filesize($dstArchive));
					set_time_limit(0);
					//@readfile("$filename") or die("File not found.");
					
					readfile_chunked($dstArchive);
					
					// III. Remove the temp file
					rm_rf($dstArchive);
				}
			}
			break;
			
		case 'Get from FTP' :
			
			if (isset($_POST['packFileName']) && isset($_POST['packageName'])) {
				
				msg_log(DEBUG, "Getting file [".$_POST['packFileName']."] from FTP.", SILENT);
				
				// Check if the file to be taken from FTP is a ZIP archive
				if (!eregi('(.zip)$', $_POST['packFileName'])) {
					msg_log(WARN, "File [".$_POST['packFileName']."] is not a translation package.", NOTIFY);
					break;
				}
				
				// Generate the absolute path of the package root location to be created
				// 1. Add the project name to the PII F.M.T. repository root
				$packLocation = PII_ROOT . "/" . str_replace(" ", "", $currProject);
				// 2. Add the name of the file to the path
				$packLocation = $packLocation . "/" . str_replace(" ", "", $_POST['packageName']);
				
				// Check if a scan with the same name already exist
				if (is_dir($packLocation)) {
					// Determine the name of the scan by its directory name
					$notif = false;
					// 1. Get list of the scans
					$scanList = getPIIScanList( $_SESSION['userMail'], $currProject );
					foreach ($scanList as $scName) {
						// If [name of the scan with stripped spaces] == [directory name]
						if (str_replace(" ", "", $scName) == substr($packLocation, strrpos($packLocation, '/') - strlen($packLocation) + 1)) {
							msg_log(WARN, "Scan [".$scName."] already exist in this project.", NOTIFY);
							$notif = true;
						}
					}
					// In case no scan was matched (unlikely) and notification did not appear
					if (!$notif) {
						msg_log(WARN, "Scan [".$scName."] already exist in this project.", NOTIFY);
					}
					break;
				}
				
				// I. Get the scan from FTP to a TEMP location
				//---------------------------------------------------------
				// Define the TEMP archive
				$tmpArchive = "/var/piifmt/tmp/".$_POST['packFileName'];
				// Get FTP server details
				$ftpServer = getFtpServer($currProject);
				// Create FTP connection to the server
				$ftpConn = new FTP($ftpServer[0], 21);
				// Authenticate the user
				if ($ftpConn && $ftpSess = $ftpConn->login($ftpServer[1], $ftpServer[2])) {
					// Determine the remote FTP absolute file name
					$ftpArchive = ftp_pwd($ftpSess) . '/' . str_replace(" ", "", $currProject) . '/' . $_POST['packFileName'];
					// Upload the file to the destination location
					if (!ftp_get($ftpSess, $tmpArchive, $ftpArchive, FTP_BINARY)) {
						msg_log(ERROR, "Failed to get file [".$ftpArchive."] from FTP.", NOTIFY);
						break;
					}
				} else {
					break;
				}
				
				// II. Extract the file to the scan location
				$zipArchive = new FMTZipArchive();
				
				if ($zipArchive->open($tmpArchive)!==TRUE) {
				    msg_log(WARN, "Failed to open ZIP archive [".$_POST['packFileName']."].", NOTIFY);
				    $zipArchive->close();
				    rm_rf($tmpArchive);
				    break;
				}
				
				if (!$zipArchive->extractTo($packLocation . '/')) {
					msg_log(WARN, "Failed to extract package [".$_POST['packFileName']."].", NOTIFY);
					$zipArchive->close();
					rm_rf($tmpArchive);
					break;
				}
				
				$totalFiles = get_num_files($packLocation);
				$totalSize = get_size($packLocation);
				$scanDate = date('Y-m-d H:i');
				$scanTime = 0;
				$rootDir = getLocationCC($currProject);
				$scannedBy = $_SESSION['userName'];
				
				$zipArchive->close();
				
				// III. Remove the TEMP file
				rm_rf($tmpArchive);
				
				// IV. Add the scan to the list in the database
				savePIIScan( $currProject, $_POST['packageName'], 0, $totalFiles, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, $userData->getLang(), "from FTP" );
				
				// IV. Set the ownership and permissions
				chgrp_r($packLocation, 'dev');
				chmod_r($packLocation, 0770);
			}			
			break;
			
		case 'Send to FTP' :
			
			if (isset($_POST['selectedPack'])) {
				
				$isErr = false;
				
				foreach ($_POST['selectedPack'] as $selPack) {
					
					msg_log(DEBUG, "Putting file [".$selPack."] to FTP site.", SILENT);
					
					// I. CREATE A ZIP ARCHIVE
					//------------------------------------------------------------
					// Generate the absolute path of the package root location
					//>>---------------------------------------------------------
					// 1. Add the project name to the PII F.M.T. repository root
					$packLocation = PII_ROOT . "/" . str_replace(" ", "", $currProject);
					// 2. Add the name of the package to the path
					$packLocation = $packLocation . "/" . str_replace(" ", "", $selPack);
					//<<---------------------------------------------------------
					
					$zipArchive = new FMTZipArchive();
					$dstArchive = "/var/piifmt/tmp/".str_replace(" ", "", $selPack).".zip";
					
					if ($zipArchive->open($dstArchive, ZIPARCHIVE::CREATE)!==TRUE) {
					    msg_log(WARN, "Failed to create ZIP archive [".$dstArchive."].", NOTIFY);
					    $isErr = true;
					}
					
					msg_log(DEBUG, "Compressing directory [".$packLocation."] to [".$dstArchive."].", SILENT);
					
					$zipArchive->addDir($packLocation, '/');
					$zipArchive->close();
					
					// II. UPLOAD THE ZIP ARCHIVE TO FTP SERVER
					//------------------------------------------------------------
					// Get FTP server details
					$ftpServer = getFtpServer($currProject);
					// Create FTP connection to the server
					$ftpConn = new FTP($ftpServer[0], 21);
					// Authenticate the user
					if ($ftpConn && $ftpConn->login($ftpServer[1], $ftpServer[2])) {
						// Upload the file tho the destination location
						if ( !$ftpConn->ftpPut($dstArchive, str_replace(" ", "", $currProject), true) ) {
							$isErr = true;
						}
					} else {
						$isErr = true;
					}
					
					// III. Remove the temp file
					rm_rf($dstArchive);
				}
				
				if ($isErr) {
					msg_log(WARN, "One or more packages failed to be uploaded to the FTP site [".$ftpServer[0]."].", NOTIFY);
				} else {
					$infoMsg = "Packages successfully uploaded to the FTP site.";
				}
			}			
			break;
		
		case 'Check-in Package' :
			
			if (isset($_POST['pkgName']) && !empty($_POST['pkgName']) &&
				isset($_POST['lngCode']) && !empty($_POST['lngCode'])) {
				
				msg_log(DEBUG, "Check IN package [".$_POST['pkgName']."] to source control.", SILENT);
				
				// Get source control properties
				$csvData = getProjectData( $currProject );
				if ($csvData == "empty") {
					msg_log(ERROR, "Failed to get properties for project [".$currProject."] from the database.", SILENT);
				}
				$arrData = explode(',', $csvData);
				$ccRootDir = $arrData[1];
				$ccActivity = $arrData[2];
				$ccView = $arrData[3];
				$codeReview = $arrData[4];
				$langCode = $_POST['lngCode'];
				
				// Get package properties (Number of files is required to determine if all files are copied)
				$packInfo = getPIIScan($currProject, $_POST['pkgName']);
				$totalFilesRec = $packInfo['totalFiles'];
				
				// Generate the absolute file name of the source root location
				//>>---------------------------------------------------------
				// 1. Add the project name to the PII F.M.T. repository root
				$srcPackRoot = PII_ROOT . "/" . str_replace(" ", "", $currProject);
				// 2. Add the name of the package to the path
				$srcPackRoot .= "/" . str_replace(" ", "", $_POST['pkgName']);
			
				// Generate the list of files for this package
				$fileList = genFileInfoList($srcPackRoot);
				
				// Verify that the number of files in the directory equals to the stated in the database one
				if (count($fileList) != $totalFilesRec) {
					msg_log(ERROR, "Inconsistency in the stated number of files: [".$totalFilesRec."] compared to real one [".count($fileList)."] for package [".$_POST['pkgName']."].", NOTIFY);
					break;
				}
				
				// Get ClearCase login details
				list($ccUser, $ccPass) = explode(',', getAuthCC($_SESSION['userMail']));
				if ( empty($ccUser) || empty($ccPass) ) {
					msg_log(WARN, "ClearCase username/password for user [".$_SESSION['userName']."] MISSING from the users preferences. Aborting..", NOTIFY);
					break;
				} else {
					msg_log(DEBUG, "ClearCase username/password retrieved correctly for user [".$_SESSION['userName']."].", SILENT);
				}
				
				// Create connection to ClearCase
				$ccClient = new ClearCase($ccUser, $ccPass);
				
				// Scan ClearCase for PII files
				if ($ccClient->isConnected()) {
					if ( !$ccClient->checkInPIIFiles($fileList, $srcPackRoot, $ccView, $ccActivity, $codeReview, $langCode) ) {
						msg_log(ERROR, "Check-in operation failed. Please check the log file.", NOTIFY);
						break;
					}
				} else {
					msg_log(ERROR, "Failed to connect to ClearCase server.", NOTIFY);
					break;
				}
				
				// Update package metadata
				updatePIIScan( $currProject, $_POST['pkgName'], $packInfo['totalFiles'], $packInfo['totalSize'], $packInfo['scanDate'], $packInfo['scanTime'], $packInfo['rootDir'], $packInfo['scannedBy'], "Checked-IN" );
				
				// Send notification to the code reviewer
				msg_log(DEBUG, "Sending notification E-mail to: [".$codeReview."].", SILENT);
				
				$from_header = "From: ".$_SESSION['userMail'];
				$subject = "PII F.M.T. Package submission for code review";
				
				$mailContent =	"Dear IBMer,".PHP_EOL.PHP_EOL.
								"You have been selected to review the following translation package:".PHP_EOL.PHP_EOL.
								"-------------------------------------------------------".PHP_EOL.
								"Project name:			".$currProject.PHP_EOL.
								"Package name:			".$_POST['pkgName'].PHP_EOL.
								"Number files:			".$totalFilesRec.PHP_EOL.
								"Source root:			".$ccRootDir.PHP_EOL.
								"CC view:				".$ccView.PHP_EOL.
								"CC Activity:			".$ccActivity.PHP_EOL.
								"L10N Engineer:			".$_SESSION['userName'].PHP_EOL.
								"Time of Check-IN:		".date('Y-m-d H:i').PHP_EOL.
								"-------------------------------------------------------".PHP_EOL.PHP_EOL.
								"This E-mail has been auto generated upon the Check-IN of the translation package.".PHP_EOL.
								"Please review the submitted files and sign the code review. ".PHP_EOL.
								"Should you find any errors or inconsistency of the package, or".PHP_EOL.
								"you are not the person to perform this code review, please".PHP_EOL.
								"reply to the L10N engineer with any details.".PHP_EOL.PHP_EOL.PHP_EOL.
								"Thank you very much for your time.".PHP_EOL.PHP_EOL.
								"Best Regards,".PHP_EOL.PHP_EOL.
								"PII F.M.T.";
								
				if ( mail($codeReview, $subject, $mailContent, $from_header) ) {
				
					$infoMsg =	"Package checked in. Notification has been sent to ".$codeReview;
				}
			} else {
				msg_log(WARN, "Missing parameter from request. Please contact the administrator.", NOTIFY);
			}
			break;
			
		case 'Delete Pack' :
			
			if (isset($_POST['selectedPack'])) {
				
				foreach ($_POST['selectedPack'] as $selPack) {
					
					// First remove the scan properties from the DB
					if ( !delPIIScan( $currProject, $selPack ) ) {
						
						msg_log(WARN, "Failed to remove package [".$selPack."] for project [".$currProject."] from the database.", NOTIFY);
					
					} else {
						// Generate the absolute path of the scan root location to be deleted
						//>>---------------------------------------------------------
						// 1. Add the project name to the PII F.M.T. repository root
						$packRoot = PII_ROOT . "/" . str_replace(" ", "", $currProject);
						// 2. Add the name of the scan to the path
						$packRoot = $packRoot . "/" . str_replace(" ", "", $selPack);
						//<<---------------------------------------------------------
						
						// Delete all files for this scan from the repository
						msg_log(DEBUG, "Removing package [".$selPack."] from the PII F.M.T. repository.", SILENT);
						
						if ( !rm_rf($packRoot) ) {
							msg_log(WARN, "Failed to remove files for package [".$selPack."] from [".$scanRoot."].", NOTIFY);
						}
					}
				}
			}
			
			break;
		
		default :
			break;
	}
}
?>
<html>
<head>
	<title>Package Explorer - PII F.M.T.</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script language="JavaScript">name = 'packExp';</script>
	<script type="text/javascript" src="javascript/functions.js"></script>
	<script type="text/javascript" src="javascript/dataStore.js"></script>
	<script type="text/javascript" src="javascript/packExp.js"></script>
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
	echo '		<li><a href="packExp.php" style="background: url(images/pointer.png) no-repeat 5px 5px;">Package Explorer</a></li>'.PHP_EOL;
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

<form method="post" id="scPanel" name="scPanel" action="packExp.php">

<div class="containerTop">
	<?php
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(4)) {
		echo '	<div class="fmtCheckbox" style="margin:0;">'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:120px; margin: 10px 20px 0 10px;" type="submit" name="btnAction" value="Upload" onclick="dbLogAction(\'Upload\'); uploadFromLocal(\''.$currProject.'\'); return false;" />'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:120px; margin: 10px 20px 0 10px;" type="submit" name="btnAction" value="Download" onclick="dbLogAction(\'Download\'); return checkSelected(\'Download\');" />'.PHP_EOL;
		echo '	</div>'.PHP_EOL;
		echo '	'.PHP_EOL;
	}
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(5)) {
		echo '	<div class="fmtCheckbox" style="margin:0;">'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:120px; margin: 10px 20px 0 10px;" type="submit" name="btnAction" value="Get from FTP" onclick="dbLogAction(\'Get from FTP\'); uploadFromFTP(\''.$currProject.'\'); return false;" />'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:120px; margin: 10px 20px 0 10px;" type="submit" name="btnAction" value="Send to FTP" onclick="dbLogAction(\'Send to FTP\'); return checkSelected(\'Send to FTP\');" />'.PHP_EOL;
		echo '	</div>'.PHP_EOL;
		echo '	'.PHP_EOL;
	}
	echo '	<div class="fmtCheckbox" style="margin:0; float:right;">'.PHP_EOL;
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(8)) {
		echo '		<input class="fmtButton" style="width:130px; margin:10px 20px 0 10px;" type="submit" name="btnAction" value="Check-in Package" onclick="dbLogAction(\'Check-in Package\'); checkinPack(\''.$currProject.'\', \''.$_SESSION['userMail'].'\'); return false;" />'.PHP_EOL; }
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(4)) {
		echo '		<input class="fmtButton" style="width:130px; margin:10px 20px 0 10px;" type="submit" name="btnAction" value="Delete Package" onclick="dbLogAction(\'Delete Package\'); return delPIIScans(\'Delete Pack\');" />'.PHP_EOL; }
	echo '	</div>'.PHP_EOL;
	?>
</div>

<div class="containerTop" style="margin-top:10px; top:160px; height:20px; background-color:#DCDCDC;">
	<div class="tableTitle" style="float:left; border: 0px solid #CCCCCC; width:25px;"><input type="checkbox" name="checkAll" value="Check All" onclick="selectAll(this);" /></div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:320px;">Package Name</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:50px;">Files</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:70px;">Size kb.</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:150px;">Date</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:70px;">Type</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:100px;">Status</div>
</div>

<div class="containerExtend" style="border-top: 1px solid #CCCCCC;">
<table class="fmtTable" id="piiScansTable" style="width: 785px;" border="0" cellspacing="0" cellpadding="0">
<?php
	$totalPacks = 0;
	
	$packList = getPIIScanList( $_SESSION['userMail'], $currProject );
	
	if (is_array($packList) ) {
		foreach ( $packList as $packName ) {
			
			// Get scan information from the database
			$packInfo = getPIIScan($currProject, $packName);
			// If the user did search for all files or only for the new/modified ones
			$packType = $packInfo['translated'] ? 'New' : 'All';
			
			echo '<tr class="highlighted">'.PHP_EOL;
			echo '	<td class="checkbox"><input type="checkbox" name="selectedPack[]" value="'.$packName.'"/></td>'.PHP_EOL;
			echo '	<td style="width:315px; padding-left:5px;" onclick="dbLogAction(\'openPackage\'); openPack(\''.$packName.'\')">'.$packName.'</td>'.PHP_EOL;
			echo '	<td style="width:45px; padding-left:5px;" onclick="dbLogAction(\'openPackage\'); openPack(\''.$packName.'\')">'.$packInfo['totalFiles'].'</td>'.PHP_EOL;
			echo '	<td style="width:65px; padding-left:5px;" onclick="dbLogAction(\'openPackage\'); openPack(\''.$packName.'\')">'.round($packInfo['totalSize'] / 1024).'</td>'.PHP_EOL;
			echo '	<td style="width:140px; padding-left:10px;" onclick="dbLogAction(\'openPackage\'); openPack(\''.$packName.'\')">'.substr($packInfo['scanDate'], 0, -3).'</td>'.PHP_EOL;
			echo '	<td style="width:60px; padding-left:10px;" onclick="dbLogAction(\'openPackage\'); openPack(\''.$packName.'\')">'. $packType .'</td>'.PHP_EOL;
			echo '	<td style="width:85px; padding-left:5px;" onclick="dbLogAction(\'openPackage\'); openPack(\''.$packName.'\')">'.$packInfo['state'].'</td>'.PHP_EOL;
			echo '</tr>';
			
			$totalPacks++;
		}
	}
?>
</table>
</div>

<div class="containerBottom" style="height:25; background-color:#EEEEEE">

	<div style="float:left; margin:5px 5px 5px 15px; padding:0; width:90px; height:20px;">Total Packages:</div>
	<div style="float:left; margin:5px 0; padding:0; width:40px; height:20px; font-weight:normal;"><?php echo $totalPacks; ?></div>

	<div style="float:left; margin:5px 5px 5px 15px; padding:0; width:60px; height:20px;">Location:</div>
	<div style="float:left; margin:5px 0; padding:0; width:140px; height:20px; font-weight:normal;"><?php echo getLocationCC($currProject); ?></div>

</div>

<input type="hidden" id="btnAction" name="btnAction" value="" />
<input type="hidden" id="packageName" name="packageName" value="" />
<input type="hidden" id="packFileName" name="packFileName" value="" />
<input type="hidden" id="langCode" name="langCode" value="<?php echo $userData->getLang(); ?>" />

</form>

<?php // Routine to display error messages set in logger.php
if ( !empty($errMessage) ) {
	echo '<script type="text/javascript">popMessage("'.$errMessage.'", "warn");</script>';
}

if ( !empty($infoMsg) ) {
	echo '<script type="text/javascript">popMessage("'.$infoMsg.'", "info");</script>';
}
?>

<form method="post" id="fePanel" name="fePanel" action="fileExp.php">
	<input type="hidden" id="packName" name="packName" value="" />
</form>

</body>
</html>