<?php
/* 
* @package:		File Explorer
* @subpackage:	Main
* @author:		Kliment Stefanov <stefanov@uk.ibm.com>
*/
require_once('../config/constants.php');
require_once('../config/settings.php');
require_once('../utils/logger.php');
require_once('../utils/functions.php');
require_once('../utils/fileHandler.php');
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

msg_log(DEBUG, "PAGE: fileExp.php", SILENT);

global $errMessage;

$lastErrorArray = error_get_last();

if (is_array($lastErrorArray)) {
	
	$lastErrorString = implode(",", $lastErrorArray);

	if (eregi('POST Content-Length', $lastErrorString)) {
		msg_log(WARN, "Upload interupted. File bigger than 16M.", NOTIFY);
	}
}

// Initialize info bar data
$totalFiles = 0;
$totalSize = 0;
$scanDate = '';
$scanTime = '';
$rootDir = '';
$scannedBy = '';

// Get the name of the current working project
$currProject = getCurrProj($_SESSION['userMail']);

if (empty($currProject) && $currProject != 'empty') {
	msg_log(WARN, "Failed to determine project. Please log off and log in again.", NOTIFY);
}

// Get user's data
$userData = new FMTUser($currProject, $_SESSION['userMail']);

if (isset($_POST['btnAction']) && !empty($_POST['btnAction'])) {

	switch ($_POST['btnAction']) {
		
		case 'Delete Pack' :
			
			if (isset($_POST['packName']) && !empty($_POST['packName']) ) {
				
				// First remove the scan properties from the DB
				if ( !delPIIScan( $currProject, $_POST['packName'] ) ) {
					
					msg_log(WARN, "Failed to remove package [".$_POST['packName']."] for project [".$currProject."] from the database.", NOTIFY);
					
				} else {
					// Generate the absolute path of the scan root location to be deleted
					//>>---------------------------------------------------------
					// 1. Add the project name to the PII F.M.T. repository root
					$packRoot = PII_ROOT . "/" . str_replace(" ", "", $currProject);
					// 2. Add the name of the scan to the path
					$packRoot = $packRoot . "/" . str_replace(" ", "", $_POST['packName']);
					//<<---------------------------------------------------------
					
					// Delete all files for this scan from the repository
					msg_log(DEBUG, "Removing package [".$_POST['packName']."] from the PII F.M.T. repository.", SILENT);
					
					if ( !rm_rf($packRoot) ) {
						msg_log(WARN, "Failed to remove files for package [".$_POST['packName']."] from [".$packRoot."].", NOTIFY);
					}
					
					// Reset scan selection
					unset($_POST['packName']);
					// Reset info bar data
					$totalFiles = 0;
					$totalSize = 0;
					$scanDate = '';
					$scanTime = '';
					$rootDir = '';
					$scannedBy = '';
				}
			}
			break;

		case 'Upload File' :
			
			if (isset($_FILES['uplFile']['name']) &&
				isset($_POST['packName']) && !empty($_POST['packName']) &&
				isset($_POST['relPath']) && !empty($_POST['relPath'])) {
				
				msg_log(DEBUG, "Uploading file [".basename($_FILES['uplFile']['name'])."] from local machine to package [".$_POST['packName']."].", SILENT);
				
				// I. Get the file to the PII FMT repository
				//---------------------------------------------------------
				// Generate the absolute path of the package root location to be created
				// 1. Add the project name to the PII F.M.T. repository root
				$packLocation = PII_ROOT . "/" . str_replace(" ", "", $currProject);
				// 2. Add the name of the package to the path
				$packLocation = $packLocation . "/" . str_replace(" ", "", $_POST['packName']);
				// 3. Create the absolute path of the new file
				$abslLocation = $packLocation . "/" . str_replace("./", "", $_POST['relPath']);
				// 4. If the location does not exist, then create it
				if ( !file_exists($abslLocation) ) {
					msg_log(DEBUG, "Creating directory [".$abslLocation."].", SILENT);
					if ( !mkdir($abslLocation, 0770, true) ) {
						msg_log(WARN, "Failed to create directory [".$abslLocation."].", NOTIFY);
						break;
					}
				}
				// 5. Add the name of the file to the full path
				$abslLocation = $abslLocation . "/" . basename($_FILES['uplFile']['name']);
				// 6. Upload the file to the repository
				if (!move_uploaded_file($_FILES['uplFile']['tmp_name'], $abslLocation)) {
					msg_log(WARN, "Failed to upload file to [".$abslLocation."].", NOTIFY);
					break;
				}
				
				// II. Generate the package meta data
				$totalFiles = get_num_files($packLocation);
				$totalSize = get_size($packLocation);
				$scanDate = date('Y-m-d H:i');
				$scanTime = 0;
				$rootDir = getLocationCC($currProject);
				$scannedBy = $_SESSION['userName'];
				
				// III. Update the package meta data in the database
				updatePIIScan( $currProject, $_POST['packName'], $totalFiles, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, "Modified" );
				
				// IV. Set the ownership and permissions to the new file
				chgrp($abslLocation, 'dev');
				chmod($abslLocation, 0770);
			} else {
				msg_log(WARN, "Server interupted the file upload. Possibly file bigger than 16M.", NOTIFY);
			}
			break;
			
		case 'Remove Files' :
			
			if (isset($_POST['packName']) && !empty($_POST['packName']) &&
				isset($_POST['selectedFiles'])) {
				
				foreach ($_POST['selectedFiles'] as $selFile) {
					
					msg_log(DEBUG, "Removing file [".$selFile."] from package [".$_POST['packName']."].", SILENT);
					
					$err = false;
					if ( !rm_rf($selFile) ) { $err = true; }
					
					// Empty directories are not shown in the file list as they are of no interest to the translators.
					// So, if the file was the only one in this directory we have to remove the parent directory as well,
					// otherwise the directory will be left hanging there for no reason - the user wont see it anyway.
					$parentDir = dirname($selFile);
					
					// If dir is empty, there shouldnt be more than two files ('.' and '..') returned.
					while ( count(scandir($parentDir)) < 3 ) {
						
						msg_log(DEBUG, "Removing empty directory [".$parentDir."] from package [".$_POST['packName']."].", SILENT);
						
						if ( !rmdir($parentDir) ) { $err = true; }
						
						$parentDir = dirname($parentDir);
					}
				}
				
				if ($err) {
					msg_log(WARN, "Error occurred while removing some files.", NOTIFY);
				}
				
				// I. Generate the absolute path of the package root location
				//---------------------------------------------------------
				// 1. Add the project name to the PII F.M.T. repository root
				$packLocation = PII_ROOT . "/" . str_replace(" ", "", $currProject);
				// 2. Add the name of the package to the path
				$packLocation = $packLocation . "/" . str_replace(" ", "", $_POST['packName']);
				
				// II. Generate the package meta data
				$totalFiles = get_num_files($packLocation);
				$totalSize = get_size($packLocation);
				$scanDate = date('Y-m-d H:i');
				$scanTime = 0;
				$rootDir = getLocationCC($currProject);
				$scannedBy = $_SESSION['userName'];

				// III. Update the package meta data in the database
				updatePIIScan( $currProject, $_POST['packName'], $totalFiles, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, "Modified" );
			}
			break;

		case 'Apply Renaming Rules' :
			
			if (isset($_POST['packName']) && !empty($_POST['packName']) &&
				isset($_POST['userLangCode']) && !empty($_POST['userLangCode']) &&
				isset($_POST['selectedFiles'])) {
				
				$totalRenamed = 0;
				$err = false;
				
				msg_log(DEBUG, "Applying [".$_POST['userLangCode']."] language renaming rules to package [".$_POST['packName']."].", SILENT);
				
				// Get the renaming rules from the database
				$renTokens = explode(',', getRenRules($currProject));
				
				// For each of the renaming rules
				for ($i=0; $i<count($renTokens); $i+=2) {
					
					// Rename from	-> $renTokens[i]
					// Rename to	-> $renTokens[$i+1]
					
					msg_log(DEBUG, "Searching files for pattern [".$renTokens[$i]."].", SILENT);
					
					$renFromMatch = '(' . $renTokens[$i] . ')$';
					
					// First we rename the filenames
					foreach ($_POST['selectedFiles'] as $selFile) {
						
						// Check if the file matches the renaming rule
						if (eregi($renFromMatch, basename($selFile))) {
							
							$fileNameTo = eregi_replace($renFromMatch, $renTokens[$i+1], $selFile);
							$fileNameTo = eregi_replace("_LANG_", $_POST['userLangCode'], $fileNameTo);
							
							msg_log(DEBUG, "Renaming file [".$selFile."] to [".$fileNameTo."].", SILENT);
							if (rename($selFile, $fileNameTo)) {
								$totalRenamed++;
							} else {
								$err = true;
								msg_log(WARN, "Failed to rename file [".$selFile."] to [".$fileNameTo."].", SILENT);
							}
						}
					}
					
					// Then we check if any directories need renaming
					if ($renTokens[$i] == '_ENGLDIR_') {
						
						// I. Generate the absolute path of the package root location
						//---------------------------------------------------------
						// 1. Add the project name to the PII F.M.T. repository root
						$packLocation = PII_ROOT . "/" . str_replace(" ", "", $currProject);
						// 2. Add the name of the package to the path
						$packLocation = $packLocation . "/" . str_replace(" ", "", $_POST['packName']);
						
						// Get a list of all directories
						$dirList = getAllDirs($packLocation);
						
						foreach ($dirList as $dir) {
							
							if (basename($dir) == 'en') {
								
								$newDir = dirname($dir) . '/' . $_POST['userLangCode'];
								
								msg_log(DEBUG, "Renaming directory [".$dir."] to [".$newDir."].", SILENT);
								
								if (rename($dir, $newDir)) {
									$totalRenamed++;
								} else {
									$err = true;
									msg_log(WARN, "Failed to rename directory [".$dir."] to [".$newDir."].", SILENT);
								}
							}
						}
					}
				}
				
				// Generate the package meta data
				$totalFiles = get_num_files($packLocation);
				$totalSize = get_size($packLocation);
				$scanDate = date('Y-m-d H:i');
				$scanTime = 0;
				$rootDir = getLocationCC($currProject);
				$scannedBy = $_SESSION['userName'];
				
				// Update the package meta data in the database
				updatePIIScan( $currProject, $_POST['packName'], $totalFiles, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, "Ren: " . $_POST['userLangCode'] );
				
				if ($err) {
					msg_log(WARN, "Some errors occured while renaming files. Total renamed: " . $totalRenamed, NOTIFY);
				} else {
					$infoMsg = "Total files renamed: " . $totalRenamed . ".";
				}
			}
			break;
		
		case 'Revert File Renaming' :
			
			if (isset($_POST['packName']) && !empty($_POST['packName']) &&
				isset($_POST['userLangCode']) && !empty($_POST['userLangCode']) &&
				isset($_POST['selectedFiles'])) {
				
				$totalRenamed = 0;
				$err = false;
				
				msg_log(DEBUG, "Reverting [".$_POST['userLangCode']."] language renaming rules to package [".$_POST['packName']."].", SILENT);
				
				// Get the renaming rules from the database
				$renTokens = explode(',', getRenRules($currProject));
				
				// For each of the renaming rules
				for ($i=0; $i<count($renTokens); $i+=2) {
					
					// Rule: rename_from	-> $renTokens[i]
					// Rule: rename_to		-> $renTokens[$i+1]
					
					$renToMatch = '(' . $renTokens[$i+1] . ')$';
					$renToMatch = eregi_replace("_LANG_", $_POST['userLangCode'], $renToMatch);
					
					msg_log(DEBUG, "Searching files for pattern [".$renToMatch."].", SILENT);
					
					// if ($renTokens[$i+1] != '_ENGLDIR_') { rule is for files only } else { rule is for directories only }
					if ($renTokens[$i] != '_ENGLDIR_') {
						
						foreach ($_POST['selectedFiles'] as $selFile) {
													
							// Check if the file matches the renaming rule
							if (eregi($renToMatch, basename($selFile))) {
								
								$fileNameOrig = eregi_replace($renToMatch, $renTokens[$i], $selFile);
								
								msg_log(DEBUG, "Renaming file [".$selFile."] to [".$fileNameOrig."].", SILENT);
								if (rename($selFile, $fileNameOrig)) {
									$totalRenamed++;
								} else {
									$err = true;
									msg_log(WARN, "Failed to rename file [".$selFile."] to [".$fileNameOrig."].", SILENT);
								}
							}
						}
						
					} else {
					
						// I. Generate the absolute path of the package root location
						//---------------------------------------------------------
						// 1. Add the project name to the PII F.M.T. repository root
						$packLocation = PII_ROOT . "/" . str_replace(" ", "", $currProject);
						// 2. Add the name of the package to the path
						$packLocation = $packLocation . "/" . str_replace(" ", "", $_POST['packName']);
						
						// Get a list of all directories
						$dirList = getAllDirs($packLocation);
						
						foreach ($dirList as $dir) {
							
							if (basename($dir) == $_POST['userLangCode']) {
								
								$origDir = dirname($dir) . '/en';
								
								msg_log(DEBUG, "Renaming directory [".$dir."] to [".$origDir."].", SILENT);
								
								if (rename($dir, $origDir)) {
									$totalRenamed++;
								} else {
									$err = true;
									msg_log(WARN, "Failed to rename directory [".$dir."] to [".$origDir."].", SILENT);
								}
							}
						}
					}
				}
				
				// Generate the package meta data
				$totalFiles = get_num_files($packLocation);
				$totalSize = get_size($packLocation);
				$scanDate = date('Y-m-d H:i');
				$scanTime = 0;
				$rootDir = getLocationCC($currProject);
				$scannedBy = $_SESSION['userName'];
				
				// Update the package meta data in the database
				updatePIIScan( $currProject, $_POST['packName'], $totalFiles, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, "Modified" );
				
				if ($err) {
					msg_log(WARN, "Some errors occured while renaming files. Total renamed: " . $totalRenamed, NOTIFY);
				} else {
					$infoMsg = "Total files renamed: " . $totalRenamed . ".";
				}
			}
			break;

		default :
			break;
	}
}

// If package was selected
if (isset($_POST['packName']) && !empty($_POST['packName'])) {
	
	$packageName = $_POST['packName'];
	
	// Get scan information from the database
	$packInfo = getPIIScan($currProject, $packageName);
	
	// We get recorded values in order to compare them with what we've read from the repository
	// This is to notice any unusual file changes in the files (tampered data)
	$totalFilesRec = $packInfo['totalFiles'];
	$totalSizeRec = $packInfo['totalSize'];
	$scanDate = $packInfo['scanDate']; if (strlen($scanDate) > 16) { $scanDate = substr($scanDate, 0, -3); } // No need to show the seconds, they are always zero
	$scanTime = $packInfo['scanTime'];
	$rootDir = $packInfo['rootDir'];
	$scannedBy = $packInfo['scannedBy'];
	$langCode = $packInfo['language'];
	$scanState = $packInfo['state'];
	
	// Generate the target file name including the absolute path
	//>>---------------------------------------------------------
	// 1. Add the project name to the PII F.M.T. repository root
	$packRoot = PII_ROOT . "/" . str_replace(" ", "", $currProject);
	// 2. Add the name of the scan to the path
	$packRoot = $packRoot . "/" . str_replace(" ", "", $packageName);

	// Check if files for this scan exist in the PII F.M.T. repository
	if ( !is_dir($packRoot) ) {
		msg_log(ERROR, "Package [".$packageName."] for project [".$currProject."] has no files in the PII F.M.T. repository [".$packRoot."].", NOTIFY);
	} else {
		msg_log(DEBUG, "Files are in the PII F.M.T. repository at [".$packRoot."]. Files: [".$totalFilesRec."], Size: [".$totalSizeRec."].", SILENT);
	}
	
	// Scan repository for PII files
	$fileRepoList = genFileInfoList($packRoot);
} else {
	$packageName = '';
	$packInfo = '';
	$totalFilesRec = '';
	$totalSizeRec = '';
	$scanDate = '';
	$scanTime = '';
	$rootDir = '';
	$scannedBy = '';
	$langCode = '';
	$scanState = '';
	$packRoot = '';
}
?>
<html>
<head>
	<title>File Explorer - PII F.M.T.</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<link rel="stylesheet" type="text/css" href="main.css" />
	<script language="JavaScript">name = 'fileExp';</script>
	<script type="text/javascript" src="javascript/functions.js"></script>
	<script type="text/javascript" src="javascript/fileExp.js"></script>
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
	echo '		<li><a href="fileExp.php" style="background: url(images/pointer.png) no-repeat 5px 5px;">File Explorer</a></li>'.PHP_EOL;
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

<form method="post" id="fePanel" name="fePanel" action="fileExp.php">

<div class="containerTop">
	<?php
	echo '	<div style="float:left; margin:0; width: 220px;">'.PHP_EOL;
	echo '		<select class="dropMenu" style="width:210px; margin: 10px 20px 0 10px;" name="packSelector">'.PHP_EOL;
	echo '			<option value ="none">Select translation package.</option>'.PHP_EOL;
			
			$packList = getPIIScanList( $_SESSION['userMail'], $currProject );
			
			if (is_array($packList) ) {
				foreach ( $packList as $packName ) {
					if (!empty($packageName) && ($packageName == $packName) ) {
						echo '				<option value="'.$packName.'" onClick="dbLogAction(\'choosePackage\'); return choosePack(this.value);" SELECTED>'.$packName.'</option>'.PHP_EOL;
					} else {
						echo '				<option value="'.$packName.'" onClick="dbLogAction(\'choosePackage\'); return choosePack(this.value);">'.$packName.'</option>'.PHP_EOL;
					}
				}
			}
			
	echo '		</select>'.PHP_EOL;
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(4)) {
		echo '		<input class="fmtButton" style="float:right; width:100px; margin:10px 0 0 10px;" type="submit" name="btnAction" value="Delete Pack" onclick="dbLogAction(\'Delete Pack\'); return delPIIPack();" />'.PHP_EOL; }
	echo '	</div>'.PHP_EOL;
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(6)) {
		echo '	<div class="fmtCheckbox" style="width:200px; margin:0;">'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:120px; margin: 10px 20px 0 50px;" type="submit" name="btnAction" value="Upload File" onclick="dbLogAction(\'Upload File\'); uploadFile(\''.$currProject.'\', \''.$packageName.'\'); return false;" />'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:120px; margin: 10px 20px 0 50px;" type="submit" name="btnAction" value="Remove Files" onclick="dbLogAction(\'Remove Files\'); return delFiles();" />'.PHP_EOL;
		echo '	</div>'.PHP_EOL;
	}
	if (in_array($_SESSION['userMail'], $FMT_ADMIN_LIST) || $userData->getPrivilege(0) || $userData->getPrivilege(7)) {
		echo '	<div class="fmtCheckbox" style="width:200px; margin:0;">'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:150px; margin: 10px 20px 0 20px;" type="submit" name="btnAction" value="Apply Renaming Rules" onclick="dbLogAction(\'Apply Renaming Rules\'); renameFiles(\''.$currProject.'\', \''.$packageName.'\'); return false;" />'.PHP_EOL;
		echo '		<input class="fmtButton" style="width:150px; margin: 10px 20px 0 20px;" type="submit" name="btnAction" value="Revert File Renaming" onclick="dbLogAction(\'Revert File Renaming\'); return revRenaming(\''.$currProject.'\', \''.$packageName.'\');" />'.PHP_EOL;
		echo '	</div>'.PHP_EOL;
	}
	?>
</div>

<div class="containerTop" style="margin-top:10px; top:160px; height:20px; background-color:#DCDCDC;">
	<div class="tableTitle" style="float:left; border: 0px solid #CCCCCC; width:25px;"><input type="checkbox" name="checkAll" value="Check All" onclick="selectAll(this);" /></div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:510px;">File Name</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:100px;">Size</div>
	<div class="tableTitle" style="float:left; border-left: 1px solid #CCCCCC; width:150px;">Date</div>
</div>

<div class="containerExtend" style="border-top: 1px solid #CCCCCC;">
<table class="fmtTable" id="piiFilesTable" style="width: 785px;" border="0" cellspacing="0" cellpadding="0">
<?php if ( isset($fileRepoList) && (count($fileRepoList) > 0)) {

	$totalFiles = 0;
	$totalSize = 0;
	
	foreach ($fileRepoList as $item) {
		
		$piiFileToken = explode(",", $item);
		
		$totalFiles++;
		$totalSize += $piiFileToken[0];
		
		$strippedFilename = substr($piiFileToken[2], strlen($packRoot));
		
		if (strlen($strippedFilename) > 65) {
			$strippedFilename = '..' . substr($strippedFilename, -65);
		}
		
		echo '<tr>'.PHP_EOL;
		echo '	<td class="checkbox"><input type="checkbox" name="selectedFiles[]" value="'.$piiFileToken[2].'"/></td>'.PHP_EOL;
		echo '	<td style="width:510px; padding-left:5px;" onMouseOver="displayPath(this, \''.substr($piiFileToken[2], strlen($packRoot)).'\');">'.$strippedFilename.'</td>'.PHP_EOL; // Here we substring the hole path from the PII repository root
		echo '	<td style="width:100px; padding-left:10px;">'.$piiFileToken[0].'</td>'.PHP_EOL;
		echo '	<td style="width:150px; padding-left:10px;">'.$piiFileToken[1].'</td>'.PHP_EOL;
		echo '	<td style="width:10px; padding-left:0px;"><input type="hidden" name="piifile[]" value="'.$item.'" /></td>'.PHP_EOL;
		echo '</tr>';
	}
	
	if (isset($totalFilesRec) && ($totalFilesRec != $totalFiles) ) {
		msg_log(WARN, "Scan [".$_POST['packName']."] has [".$totalFiles."] files, which differs from the scan result [".$totalFilesRec."]. Repository tampered, user [".$_SESSION['userName']."] notified.", NOTIFY);
		msg_log(DEBUG, "Resetting scan [".$_POST['packName']."] properties in order to reflect the new repository content.", SILENT);
		updatePIIScan( $currProject, $_POST['packName'], $totalFiles, $totalSizeRec, $scanDate, $scanTime, $rootDir, $scannedBy, 'Tampered' );
	}
	if (isset($totalSizeRec) && ($totalSizeRec != $totalSize) ) {
		msg_log(WARN, "Scan [".$_POST['packName']."] has total file size of [".$totalSize."] bytes, which differs from what the scan returned [".$totalSizeRec."]. Repository tampered, user [".$_SESSION['userName']."] notified.", NOTIFY);
		msg_log(DEBUG, "Resetting scan [".$_POST['packName']."] properties in order to reflect the new repository content.", SILENT);
		updatePIIScan( $currProject, $_POST['packName'], $totalFilesRec, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, 'Tampered' );
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

<input type="hidden" id="btnAction" name="btnAction" value="" />
<input type="hidden" id="packName" name="packName" value="<?php echo $packageName; ?>" />
<input type="hidden" id="userLangCode" name="userLangCode" value="<?php echo $userData->getLang(); ?>" />
<input type="hidden" id="packLangCode" name="packLangCode" value="<?php echo $langCode; ?>" />

</form>

<?php // Routine to display error messages set in logger.php
if ( !empty($errMessage) ) {
	echo '<script type="text/javascript">popMessage("'.$errMessage.'", "warn");</script>';
}
if ( !empty($infoMsg) ) {
	echo '<script type="text/javascript">popMessage("'.$infoMsg.'", "info");</script>';
}
?>

</body>
</html>