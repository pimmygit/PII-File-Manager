<?php
/* 
** @desc:		ClearCase session
**
** @package:	Security
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	17/12/2007
*/
include_once('../config/constants.php');
include_once('../config/settings.php');
include_once('../utils/logger.php');
require_once('../utils/SSH.class.php');

Class ClearCase {
	
	private $ccConn;
	private $ccClient;
	private $ccUser;
	private $ccPass;

	/*
	* @desc		Connects to and authenticates against ClearCase server via SSH
	* @author	Kliment Stefanov
	* @version	1.0, 07.02.2008
	* 
	* @param	string	$ccUser		Username to access ClearCase server
	* @param	string	$ccPass		Password to access ClearCase server
	* 
	* @return	boolean				TRUE on success, FALSE otherwise
	*/
	function __construct($user, $pass) {
		
		$this->ccUser = $user;
		$this->ccPass = $pass;
		
		// Create ClearCase client shell session
		$this->ccConn = new SSH(CC_HOST, CC_PORT);

		if (!$this->ccConn) {
			msg_log(ERROR, "Failed to connect to ClearCase client host.", NOTIFY);
			return false;
		}
		
		if ( !$this->ccConn->sshLogin($this->ccUser, $this->ccPass) ) {
			msg_log(ERROR, "Failed to authenticate user [".$this->ccUser."] on server [".CC_HOST."].", NOTIFY);
			return false;
		}
		
		$this->ccClient = $this->ccConn->createSession();
		
		if(!($this->ccClient)){
			msg_log(ERROR, "Failed to create shell session on server [".CC_HOST."].", NOTIFY);
			return false;
		}
	}
	
	/*
	* @desc		Checks if the connection to the ClearCase server has been established
	* @author	Kliment Stefanov
	* @version	1.0, 27.03.2008
	* 
	* @return	boolean				TRUE if yes, FALSE otherwise
	*/
	function isConnected() {
		
		if ($this->ccClient) {
			return true;
		}
		
		return false;
	}
	
	/*
	* @desc		Returns handler to the SSH connection
	* @author	Kliment Stefanov
	* @version	1.0, 06.05.2008
	* 
	* @return	mixed				Connection if exist, FALSE otherwise
	*/
	function getConnection() {
		
		if ($this->ccClient) {
			return $this->ccConn->getConnection();
		}
		
		return false;
	}
	
	/*
	* @desc		Closes the connection if it exist
	* @author	Kliment Stefanov
	* @version	1.0, 04.04.2008
	* 
	* @return	boolean				TRUE on success, FALSE otherwise
	*/
	function disconnect() {
		
		if ($this->ccClient) {
			return fclose($this->ccClient);
		}
		
		return false;
	}

	/*
	* @desc		Sets TRANSLATED flag of a file to the given value [yes|no]
	* @author	Kliment Stefanov
	* @version	1.0, 07.02.2008
	* 
	* @param	string	$viewName	Name of the view to set before executing the scan command
	* @param	string	$file		File name including absolute path
	* @param	string	$flag		Value of the attribute [yes|no]
	* 
	* @return	boolean				TRUE on success, FALSE otherwise
	*/
	function setFlagTranslated($viewName, $file, $flag) {
		
		msg_log(DEBUG, "Setting TRANSLATED attribute of file [".$file."] to [".$flag."]", SILENT);
		
		$cmdList[0] = "echo __START__;\n";
		$cmdList[1] = CC_BIN."/cleartool setview -exec '/var/tmp/piifmt/scripts/markPIIFileTranslated.sh ".CC_BIN." ".$file." ".$flag."' ".$viewName."; echo '__END__';\n"; //END command should be on the same line.. otherwise it wont complete ever		
		
		foreach ($this->execute($this->ccClient, $cmdList) as $respLine) {
			if (ereg('Created attribute', $respLine)) {
				return true;
			}
		}
		
		return false;
	}
	
	/*
	* @desc		Scans for PII files in a particular location
	* @author	Kliment Stefanov
	* @version	1.0, 09.01.2008
	* 
	* @param	string	$viewName	Name of the view to set before executing the scan command
	* @param	string	$location	Path to search in for PII files
	* @param	string	$filter		If to return ALL or only files not sent for translation [all|new]
	* 
	* @return	array				Details of a file in CSV format [size, date-modified, full-filename]
	*/
	function scanPIIFiles($viewName, $location, $filter) {
		
		global $errMessage;
				
		msg_log(DEBUG, "Scanning for PII files in [".$location."]:", SILENT);
		
		$cmdList[0] = 'echo __START__;'.PHP_EOL;
		$cmdList[1] = CC_BIN."/cleartool setview -exec '/var/tmp/piifmt/scripts/scanPIIFiles.sh ".CC_BIN." ".$location." ".$filter."' ".$viewName."; echo '__END__';".PHP_EOL; //END command should be on the same line.. otherwise it wont complete ever
		
		return $this->execute($this->ccClient, $cmdList);
	}
	
	/*
	* @desc		Retrieves file from source control
	* @author	Kliment Stefanov
	* @version	1.0, 16.01.2008
	* 
	* @param	string	$usr		Username to return the files to PII-F.M.T. as (public-key authentication ONLY)
	* @param	string	$viewName	Name of the view to set before executing the scan command
	* @param	string	$src		Source filename including absolute path
	* @param	string	$dst		Target filename including absolute path
	* @param	boolean	$mark		Mark files as TRANSLATED [true|false]
	* 
	* @return	boolean				TRUE on success, FALSE otherwise
	*/
	function getPIIFile($usr, $viewName, $src, $dst, $mark) {
		
		msg_log(DEBUG, "Copying file [".$src."] to the PII repository in [".PII_ROOT."].", SILENT);
		
		// From the destination filename including the absolute path, get only the path
		$dstPath = substr($dst, 0, strrpos($dst, "/")); // Assuming that the server is a UNIX system
		
		// Check if the destination directory exist, if not - create it
		if (!is_dir($dstPath)) {
			msg_log(DEBUG, "Directory [".$dstPath."] does not exist - creating..", SILENT);
			
			umask(0001);
			if (!mkdir($dstPath, 0770, true)) {
				msg_log(ERROR, "Failed to create directory [".$dstPath."].", SILENT);
				return false;
			}
		}
		
		msg_log(DEBUG, "Sending file [".$dstPath."] from server [".CC_HOST."] as user [".$usr."]..", SILENT);

		$cmdList[0] = "echo __START__;\n";
		$cmdList[1] = CC_BIN."/cleartool setview -exec '/var/tmp/piifmt/scripts/getPIIFile.sh ".$_SERVER['SERVER_NAME']." ".$src." ".$dstPath." ".$usr."' ".$viewName."; echo '__END__';\n"; //END command should be on the same line.. otherwise it wont complete ever		
		
		// Execute the command to copy the file
		$res = $this->execute($this->ccClient, $cmdList);
		
		// If file has been copied successfully, set the correct permissions.
		if ( $res && ereg('100%', implode($res) ) ) {
			
			// This is a HORRIBLE HACK but there is no other workaround to the problem:
			// After getting the files from ClearCase, they have their permissions set to 0444.
			// You cant execute CHMOD directly from here because Apache/PHP runs as apache user.
			// That's why we log in via SSH to the same server as the user accessing ClearCase and run CHMOD from there.
			//>>---------------------------------------------------------------------------------------------------------
			// Create SSH shell connection to the server itself
			$sshConn = new SSH($_SERVER['SERVER_NAME'], 22);
			// Authenticate the user
			if(!($sshConn->sshLogin($this->ccUser, $this->ccPass))){
				msg_log(ERROR, "Cannot log in to server to set permission to file [".$dst."].", SILENT);
				return false;
			}
			// Create SSH Shell session
			$sshShell = $sshConn->createSession();
			
			if(!$sshShell){
				msg_log(ERROR, "Cannot create session to change permission to file [".$dst."].", SILENT);
				return false;
			}
			// Execute the command
			$chmodCMD[0] = "echo '__START__';\n";
			$chmodCMD[1] = "chmod 0770 ".$dst."; echo '__END__';\n";
			
			//fwrite($sshShell, 'chmod 0770 '.$dst);
			if ( !$this->execute($sshShell, $chmodCMD) ) {
				msg_log(ERROR, "Failed to set permission to file [".$dst."].", SILENT);
				return false;
			}
			//<<---------------------------------------------------------------------------------------------------------
			
			// The desired way would be, but as I mentioned above it wont work:
			//chmod($dst, 0770);
			
			return true;
		} else {
			msg_log(ERROR, "File NOT copied: [".$src."].", SILENT);
			return false;
		}
	}

	/*
	* @desc		Checks in the files into ClearCase
	* @author	Kliment Stefanov
	* @version	1.0, 23.04.2008
	* 
	* @param	array	$fileList		List of filenames to Check-IN into ClearCase: CSV[size,date-modified,full-filename]
	* @param	string	$srcRoot		Absolute path of the source root location
	* @param	string	$ccView			Name of the view to set before executing the scan command
	* @param	string	$ccActivity		Name of the activity
	* @param	string	$codeReview		Person who will be prompted to perform the code review
	* @param	string	$langCode		Language code to set the LANG attribute to
	* 
	* @return	boolean				TRUE on success, FALSE otherwise
	*/
	function checkInPIIFiles($fileList, $srcRoot, $ccView, $ccActivity, $codeReview, $langCode) {

		$status = true;
		// Add the Check-In script to the list of files to be copied
		$fileList[] = '1024,00-00-00,' . $_SERVER['DOCUMENT_ROOT'] . '/scripts/checkInPIIFiles.sh';
		
		// I. Upload the files to a TEMP location on the ClearCase server
		// Create the temp location
		$dstTemp = str_replace(PII_ROOT, '/var/tmp/piifmt', $srcRoot);
		
		// Clean the TEMP directory
		$sshStream = ssh2_exec($this->ccConn->getConnection(), "rm -rf '" . dirname(dirname($dstTemp)) . "'");
		stream_set_blocking( $sshStream, true );
		$res = stream_get_contents($sshStream);
		
		// Create SFTP session
		$sftp = ssh2_sftp($this->ccConn->getConnection());
		
		// Copy the files across to the TEMP location
		foreach ($fileList as $file) {
		
			$piiFileToken = explode(",", $file);
			
			// Get the target location for PII files
			if (ereg($srcRoot, $piiFileToken[2])) {
				$dstFile = str_replace($srcRoot, $dstTemp, $piiFileToken[2]);
			}
			// Get the target location for script files
			if (ereg($_SERVER['DOCUMENT_ROOT'], $piiFileToken[2])) {
				$dstFile = str_replace($_SERVER['DOCUMENT_ROOT'], '/var/tmp/piifmt', $piiFileToken[2]);
			}
			
			// Create the directory
			// Note: For some bizzare reason ssh2_exec does not return the response from 'ls -l <dirname>,
			// nor the ssh2_ sftp_ mkdir or ssh2_ sftp_ stat works, so ther is no way to verify if the DIR was created OK
			if (!$sshStream = ssh2_exec($this->ccConn->getConnection(), "mkdir -p '" . dirname($dstFile) . "';") ) {
				msg_log(ERROR, "Failed to execute SSH2 command: [mkdir -p '" . dirname($dstFile) . "'].", SILENT);
				continue;
			}
			stream_set_blocking( $sshStream, true );
			$res = stream_get_contents($sshStream);
			
			msg_log(DEBUG, "Copying file [".$piiFileToken[2]."].", SILENT);
			
			// NOTE A: ssh2_scp_send has worse performance comparing to fwrite
			//if ( !ssh2_scp_send($this->ccConn->getConnection(), $piiFileToken[2], $dstFile, 0770) ) {
			//	msg_log(ERROR, "Failed to copy file [".str_replace($srcRoot, '.', $piiFileToken[2])."] to [".$dstFile."].", SILENT);
			//	$status = false;
			//}
			// NOTE B: sftp->fopen->file_get_contents->fwrite has better performance than ssh2_scp_send
			//msg_log(DEBUG, "Creating SFTP stream.", SILENT);
			$sftpStream = @fopen('ssh2.sftp://'.$sftp.$dstFile, 'w');
			
			//msg_log(DEBUG, "Opening remote file [".$dstFile."] for writing.", SILENT);
			try {
				
				if (!$sftpStream) {
					throw new Exception("Could not open remote file: $dstFile");
				}
				//msg_log(DEBUG, "Reading from source [".$piiFileToken[2]."] for writing.", SILENT);
				$data_to_send = @file_get_contents($piiFileToken[2]);
			
				if ($data_to_send === false) {
					throw new Exception("Could not open local file: $piiFileToken[2].");
				}
				//msg_log(DEBUG, "Writing data over SFTP session.", SILENT);
				if (@fwrite($sftpStream, $data_to_send) === false) {
					throw new Exception("Could not send data from file: $piiFileToken[2].");
				}
				
				fclose($sftpStream);
				
			} catch (Exception $e) {
				msg_log(ERROR, 'Exception: ' . $e->getMessage(), SILENT);
				fclose($sftpStream);
				$status = false;
			}
		}
		
		if ( !$status ) {
			msg_log(ERROR, "Failed to copy package to server. Check-in operation failed.", NOTIFY);
			return false;
		}
		
		// Setting permissions to the executable script (Last file in the array of files)
		$execFileToken = explode(",", $fileList[count($fileList)-1]);
		$execFile = str_replace($_SERVER['DOCUMENT_ROOT'], '/var/tmp/piifmt', $execFileToken[2]);
		msg_log(DEBUG, "Setting permissions [chmod -R 770 '".$dstTemp."'; chmod -R 775 '" . $execFile . "'].", SILENT);
		
		//if ( !$this->execute($this->ccClient, $cmdList) ) {
		if (!$sshStream = ssh2_exec($this->ccConn->getConnection(), "chmod -R 770 '".$dstTemp."'; chmod 775 '" . $execFile . "';") ) {
			msg_log(ERROR, "Failed to execute SSH2 command: [chmod -R 770 '".$dstTemp."'; chmod 775 '" . $execFile . "'].", SILENT);
			$status = false;
		}
		stream_set_blocking( $sshStream, true );
		$res = stream_get_contents($sshStream);
		
		// Verify that everything up to now has been executed successfully
		if (!$status) {
			fclose($sshStream);
			msg_log(ERROR, "Failed to set file permissions. Check-in operation failed.", NOTIFY);
			return false;
		}				
		
		// II. Execute the Check-IN script via SSH	
		msg_log(DEBUG, "Start ClearCase Check-OUT/IN operations.", SILENT);
		
		$cmdList[0] = "echo __START__;\n";
		$cmdList[1] = CC_BIN."/cleartool setview -exec '/var/tmp/piifmt/scripts/checkInPIIFiles.sh ".CC_BIN." \"".$ccView."\" \"".$ccActivity."\" \"".CC_ROOT."\" \"".$dstTemp."\" \"".$langCode."\"' ".$ccView."; echo '__END__';\n"; //END command should be on the same line.. otherwise it wont complete ever
		
		$result = $this->execute($this->ccClient, $cmdList);
		
		if ( !$result ) {
			msg_log(ERROR, "Failed to execute SSH2 command: [" . $cmdList[1] . "].", NOTIFY);
			$status = false;
		}
		
		// III. Clean the TMP location
		if ($status) {
			// We do not clean the TEMP location in case of an error in order to be able to investigate what went wrong.
			msg_log(DEBUG, "Cleaning TEMP: [".$dstTemp."].", SILENT);
			$sshStream = ssh2_exec($this->ccConn->getConnection(), "rm -rf '" . $dstTemp . "'");
			stream_set_blocking( $sshStream, true );
			$res = stream_get_contents($sshStream);
		}
		
		fclose($sshStream);
		
		return $status;
	}
	
	/*
	* @desc		Executes a command in the shell
	* @author	Kliment Stefanov
	* @version	1.0, 17.12.2007
	* 
	* @param	object	$conn		Connection object
	* @param	array	$cmdList	Set of commands to execute
	* 
	* @return	array				Result of each command
	*/
	function execute($conn, $cmdList) {
	
		$data = array();
		$record = false;
		
		foreach ($cmdList as $cmd) {
			
			//msg_log(DEBUG, "Executing SSH: [".substr($cmd, 0, -1)."].", SILENT);
			
			fwrite($conn, $cmd);
			
			while($line = fgets($conn)) {
				
				flush();
				//msg_log(DEBUG, "Response: [".$line."].", SILENT);
				
				// Start reading
				if (ereg('^__START__', $line)) {
					//msg_log(DEBUG, "Reading of data started..", SILENT);
					$record = true;
					break;
				}
				
				// In any error situation
				if (substr_count($line, ',') != 2 && // If its data it will have ',' two times (REGEXP can be used also)
					!eregi('(Create element)|(Check-IN)|(Check-OUT)|(already checked out)|(Copy_)|(setview)|(100%))', $line) && // Ignore the line if it contains *** as string 'Error' might be in the filename
					eregi('(not found)|(^error)|( error)|(can\'t be established)|(Permission denied)', $line)) {
					msg_log(ERROR, trim($line), NOTIFY);
					return false;
				}
				
				// Ignore lines with ClearCase commands
				if (ereg('cleartool', $line)) {
					if (eregi('Unable to access', $line)) {
						msg_log(WARN, trim($line), NOTIFY);
						return false;
					}
					//error_log("Command: " . $line);
					continue;
				}
				// Ignore lines with STTY commands
				if (ereg('stty', $line)) {
					//error_log("Command: " . $line);
					continue;
				}
				// Ignore lines with chmod commands
				if (ereg('chmod', $line)) {
					//error_log("Command: " . $line);
					continue;
				}
				
				// Ignore lines from SCP command when adding host to known_hosts list
				if (ereg('Permanently added', $line)) {
					//error_log("Command: " . $line);
					continue;
				}
				
				// End reading
				if (ereg('^__END__', $line)) {
					//msg_log(DEBUG, "Reading of data ended..", SILENT);
					break;
				}
				
				if ($record) {
					msg_log(DEBUG, "Data: [".trim($line)."]", SILENT);
					array_push($data, trim($line));
				}
			}
		}
		
		if ( count($data) == 0 ) {
			return true;
		} else {
			return $data;
		}
	}
}
?>