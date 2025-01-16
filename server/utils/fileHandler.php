<?php
/* 
** Description:	Contains various functions for file manipulations
** @package:	utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	25/01/2008
*/

require_once('../config/constants.php');
require_once('../config/settings.php');
require_once('logger.php');

/*
* Name: genFileInfoList
* Desc: Generates array of file information in a given directory
*		@param string $scanDir	Type: String,	Value: Absolute path to scan
*		@return string			Type: Array,	Value: Details of a file in CSV format [size,date-modified,full-filename]
* Date: 25/01/2008
*/
function genFileInfoList($scanDir) {
	
	// PHP caches results from functions returning file information.
	// For acurate information (however theoretically poor performance ;-)) clear the cache before reading file information.
	clearstatcache();
	
	if ( !is_dir($scanDir) ) {
		msg_log(WARN, "[".$scanDir."] does not exist or is not a directory.", NOTIFY);
		return false;
	}
	
	msg_log(DEBUG, "Scanning [".realpath($scanDir)."].", SILENT);
		
	$fileList = array();
	
	return getFileInfoList($fileList, $scanDir);
}

/*
* Name: getFileInfoList
* Desc: Recursively scans the directory and populates the list with file information
* Inpt:	$fList		Type: String,	Value: Array to populate with file info
*		$scanDir	Type: String,	Value: Absolute path to scan
* Outp:	none
* Date: 25/01/2008
*/
function getFileInfoList($fList, $scnDir) {
	
	$dirContent = scandir($scnDir);
	
	foreach ($dirContent as $item) {
		
		if ( $item != '.' && $item != '..' ) {
			
			$realFile = realpath($scnDir .'/' . $item);
			
			if ( is_dir( $realFile ) ) {
				// Recurse into the directory
				msg_log(DEBUG, "Reading [".$realFile."].", SILENT);
				$fList = getFileInfoList($fList, $realFile);
			}
			
			if ( is_file( $realFile ) ) {
				// Populate the array with file info
				msg_log(DEBUG, "Adding [".$realFile."] to the list.", SILENT);
				array_push($fList, getFileInfo( $realFile ));
			}
		}
	}
	
	return $fList;
}

/*
* Name: getFileInfo
* Desc: Reads file information: name, size, last modified for a given filename
* Inpt:	$fName	Type: String,	Value: Absolute path of the file
* Outp:			Type: String,	Value: Details of a file in CSV format [size,date-modified,full-filename]
* Date: 25/01/2008
*/
function getFileInfo($fName) {
	
	if ( !is_file($fName) ) {
		msg_log(WARN, "File [".$fName."] does not exist or is not a regular file.", SILENT);
		return false;
	}
	
	return filesize($fName) . ',' . date("Y-m-d H:i", filemtime($fName)) . ',' . $fName;
}

/*
* Name: getAllDirs
* Desc: Reads the directory and returns a list of all sub-directories
* Inpt:	$fName	Type: String,	Value: Absolute path of the directory to list
* Outp:			Type: Array,	Value: List of all directories
* Date: 14/03/2008
*/
function getAllDirs($fName) {
	
	$dirList = array();
	
	if ( is_dir($fName) ) {
		
		array_push($dirList, $fName);
		
		$dirContent = scandir($fName);
		
		foreach ($dirContent as $item) {
			
			if ( $item != '.' && $item != '..' ) {
				
				$realFile = realpath($fName .'/' . $item);
				
				$subDirList = getAllDirs($realFile);
				
				if ( $subDirList ) {
					$dirList = array_merge($dirList, $subDirList);
				}
			}
		}
		
		return $dirList;
	} else {
		return false;
	}
}

/*
* Name: rm_rf
* Desc: Same behaviour as the unix command 'rm -rf <filename>'
* Inpt:	$fName	Type: String,	Value: Absolute path of the file/dir
* Outp:			Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 25/01/2008
*/
function rm_rf($fName) {
	
	if ( is_file($fName) ) {
		
		return unlink($fName);
		
	} else if ( is_dir($fName) ) {
		
		$dirContent = scandir($fName);
		
		foreach ($dirContent as $item) {
			
			if ( $item != '.' && $item != '..' ) {
				
				$realFile = realpath($fName .'/' . $item);
				
				if ( !rm_rf($realFile) ) { return false; }
			}
		}
		
		return rmdir($fName);
		
	} else {
		msg_log(WARN, "[".$fName."] is neither a file or a directory. Skipping.", SILENT);
		return true;
	}
}

/*
* Name: get_size
* Desc: Same behaviour as the unix command 'du <filename>'
* Inpt:	$path	Type: String,	Value: Absolute path of the file/dir
* Outp:			Type: INT,		Value: Total size
* Date: 04/03/2008
*/
function get_size($path)
{
	if(!is_dir($path)) return filesize($path);
	
	if ($handle = opendir($path)) {
		
		$size = 0;
		
		while (false !== ($file = readdir($handle))) {
			if( $file != '.' && $file != '..' ) {
				$size += get_size($path.'/'.$file);
			}
		}
		
		closedir($handle);
		return $size;
	}
}

/*
* Name: get_num_files
* Desc: Counts the number of files in a directory recursively
* Inpt:	$path	Type: String,	Value: Absolute path of the file/dir
* Outp:			Type: INT,		Value: Total number of files
* Date: 04/03/2008
*/
function get_num_files($path)
{
	if(!is_dir($path)) return 1;
	
	$dirContent = scandir($path);
	
	$numFiles = 0;
	
	foreach ($dirContent as $item) {
		
		if ( $item != '.' && $item != '..' ) {
			
			$realFile = realpath($path .'/' . $item);
			
			if ( is_dir( $realFile ) ) {
				// Recurse into the directory
				$numFiles += get_num_files($realFile);
			}
			
			if ( is_file( $realFile ) ) {
				$numFiles++;
			}
		}
	}
	
	return $numFiles;
}

/*
* Name: chgrp_r
* Desc: Changes group ownership recursively (chgrp -R <grp_name> <filename>)
* Inpt:	$path		-> Type: String,	Value: Absolute path of the file/dir
* 		$grpName	-> Type: String,	Value: Name of the group
* Outp:				-> Type: Boolean,	Value: Status of the execution
* Date: 04/03/2008
*/
function chgrp_r($path, $grpName)
{
	if(!is_dir($path)) return chgrp($path, $grpName);
	
	$dirContent = scandir($path);
	
	$status = true;
	
	foreach ($dirContent as $item) {
		
		if ( $item != '.' && $item != '..' ) {
			
			$realFile = realpath($path .'/' . $item);
			
			// Recurse into the directory
			if (!chgrp_r($realFile, $grpName)) {
				$status = false;
			}
		}
	}
	
	if (!chgrp($path, $grpName)) {
		$status = false;
	}
	
	return $status;
}

/*
* Name: chmod_r
* Desc: Changes file permissions recursively (chmod -R <mode> <filename>)
* Inpt:	$path		-> Type: String,	Value: Absolute path of the file/dir
* 		$mode		-> Type: INT,		Value: Mode code
* Outp:				-> Type: Boolean,	Value: Status of the execution
* Date: 04/03/2008
*/
function chmod_r($path, $mode)
{
	if(!is_dir($path)) return chmod($path, $mode);
	
	$dirContent = scandir($path);
	
	$status = true;
	
	foreach ($dirContent as $item) {
		
		if ( $item != '.' && $item != '..' ) {
			
			$realFile = realpath($path .'/' . $item);
			
			// recurse into the directory
			if (!chmod_r($realFile, $mode)) {
				$status = false;
			}
		}
	}
	
	if (!chmod($path, $mode)) {
		$status = false;
	}
	
	return $status;
}

/*
* Name: readfile_chunked
* Desc: Same behaviour as readfile(), however fopen()/fread() is about 55% faster and
* 		PHP 5 has a limit for the filesize. Thats why we split the file into chunks.
* 		More info: http://uk.php.net/manual/en/function.readfile.php
* Inpt:	$filename	->	Type: String,	Value: Absolute path of the file
*		$retbytes	->	Type: String,	Value: Absolute path of the file/dir
* Outp:					Type: INT,		Value: Number of bytes delivered
* Date: 15/02/2008
*/
// We use this function instead of 'readfile()' for two reasons:
// 1. fopen()/fread() is about 55% faster
// 2. PHP 5 has a limit for the filesize. Thats why we split the file into chunks.
// More info: http://uk.php.net/manual/en/function.readfile.php
function readfile_chunked($filename, $retbytes=true) {
	
	$chunksize = 1*(1024*1024); // One Megabyte per chunk
	$buffer = '';
	$cnt =0;
	$handle = fopen($filename, 'rb');

	if ($handle === false) {
		msg_log(ERROR, "Cannot open file [".$filename."] for reading.", NOTIFY);
		return false;
	}

	while (!feof($handle)) {
		
		$buffer = fread($handle, $chunksize);
		echo $buffer;
		flush();
		
		if ($retbytes) {
			$cnt += strlen($buffer);
		}
	}
	
	$status = fclose($handle);
	
	if ($retbytes && $status) {
		return $cnt; // return number of bytes delivered like readfile() does.
	}
	
	return $status;
}

/*
* Name: setPassLessPubKeyAuth
* Desc: Authenticates the user creating the SSH connection
* Inpt: $srvFrom	-> Type: String, Value: Access from server	(ClearCase server)
* 		$srvTo		-> Type: String, Value: Access to server	(PII F.M.T server)
* 		$username	-> Type: String, Value: Username of the user accessing the server
* 		$password		-> Type: String, Value: Password of the user accessing the server
* Outp: none
* Date: 16.04.2008
*/
function setPassLessPubKeyAuth($srvFrom, $srvTo, $username, $password) {
	
	msg_log(DEBUG, "Setting passphrase-less public key between [".$srvFrom."] and [".$srvTo."] for user [".$username."].", SILENT);

	$usrKey = $username . '@' . $srvFrom;
	
	// Create session to the source server
	$srcHostSess = new SSH($srvFrom, 22);
	
	if(!($srcHostSess->sshLogin($username, $password))){
		msg_log(ERROR, "Cannot log in to server [".$srvFrom."].", SILENT);
		return false;
	}
	
	if(!$srcShell = $srcHostSess->createSession()){
		msg_log(ERROR, "Cannot create session to [".$srvFrom."].", SILENT);
		return false;
	}
	
	// Create session to the target server
	$dstHostSess = new SSH($srvTo, 22);
	
	if(!($dstHostSess->sshLogin($username, $password))){
		msg_log(ERROR, "Cannot log in to server [".$srvTo."].", SILENT);
		return false;
	}
	
	if(!$dstShell = $dstHostSess->createSession()){
		msg_log(ERROR, "Cannot create session to [".$srvTo."].", SILENT);
		return false;
	}
	
	msg_log(DEBUG, "Generating passphrase-less public key: [".$usrKey."].", SILENT);		
	
	$srcCMD[1] = "mkdir -p ~/.ssh;";
	$srcCMD[2] = "chmod 700 ~/.ssh;";
	$srcCMD[3] = "cd ~/.ssh;";
	$srcCMD[4] = "rm ~/.ssh/id_rsa*;";
	$srcCMD[5] = "ssh-keygen -q -N '' -f ~/.ssh/id_rsa -t rsa;";
	$srcCMD[6] = "cat ~/.ssh/id_rsa.pub;";
	
	foreach ($srcCMD as $cmd) {
		$sshStream = ssh2_exec($srcHostSess->getConnection(), $cmd);
		stream_set_blocking($sshStream, true);
		$pubKey = stream_get_contents($sshStream); // The last output should give you the public key
	}
	
	if (empty($pubKey) || !ereg('^ssh-rsa', $pubKey)) {
		msg_log(ERROR, "Failed to generate public key.", SILENT);
		fclose($srcShell);
		fclose($dstShell);
		return false;
	}
	
	msg_log(DEBUG, "Checking for existing public key: [".$usrKey."].", SILENT);
	
	$sshStream = ssh2_exec($dstHostSess->getConnection(), "cat ~/.ssh/authorized_keys;");
	stream_set_blocking($sshStream, true);
	$keyRing = stream_get_contents($sshStream);
	
	if (	!ereg('No such file or directory', $keyRing) &&	// If keys file exist
		 	ereg($usrKey, $keyRing) ) {						// and such key already exist in the file
		
		msg_log(INFO, "Pub key for [".$usrKey."] already exist. UPDATING key..", SILENT);
		
		// Remove the old key from the file
		$sshStream = ssh2_exec($dstHostSess->getConnection(), 'sed "/'.$usrKey.'/d" ~/.ssh/authorized_keys > ~/.ssh/authorized_keys_new; mv ~/.ssh/authorized_keys_new ~/.ssh/authorized_keys;');
		stream_set_blocking($sshStream, true);
		$res = stream_get_contents($sshStream);
	}
	
	msg_log(DEBUG, "Adding the new public key to the key ring on [".$usrKey."].", SILENT);
	$sshStream = ssh2_exec($dstHostSess->getConnection(), "cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys;");
	stream_set_blocking($sshStream, true);
	$keyRing = stream_get_contents($sshStream);
		
	msg_log(DEBUG, "Verifying public key [".$usrKey."].", SILENT);
	
	$sshStream = ssh2_exec($dstHostSess->getConnection(), "cat ~/.ssh/authorized_keys; chmod 600 ~/.ssh/*");
	stream_set_blocking($sshStream, true);
	$keyRing = stream_get_contents($sshStream);
	
	if ( !ereg($usrKey, $keyRing) ) {
		msg_log(ERROR, "Failed to distribute the public key to [".$srvTo."].", SILENT);
		return false;
	}
	
	fclose($srcShell);
	fclose($dstShell);
	
	return true;		
}
?>