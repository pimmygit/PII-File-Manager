<?php
/* 
** Description:	Authentication against LDAP server
**
** @package:	Utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	25/02/2008
*/

Class FTP {

	private $conn;

	/*
	* Name: __construct
	* Desc: Creates an SSH connection to a server on a specified port
	* Inpt: $conHost	-> Type: String, Value: Host name to connect to
	*		$conPort	-> Type: String, Value: Port number to connect to
	* Outp: none
	* Date: 25.02.2008
	*/
	function __construct($conHost, $conPort) {
		
		// Connect to the remote server
		msg_log(DEBUG, "Connecting to FTP server [".$conHost."] on port [".$conPort."].", SILENT);
		
		if(!($this->conn = ftp_connect($conHost, $conPort))){
			msg_log(ERROR, "Failed to connect to host [".$conHost."] on port [".$conPort."].", NOTIFY);
			return false;
		}
	}

	/*
	* Name: login
	* Desc: Authenticates the user creating connection
	* Inpt: $username	-> Type: String, Value: Username accessing the server
	*		$password	-> Type: String, Value: Password to validate the user
	* Outp: none
	* Date: 17.12.2007
	*/
	function login($username, $password) {
	
		// Try to authenticate with username and password
		msg_log(DEBUG, "Authenticating user [".$username."].", SILENT);
		
		if ($this->conn) {
			if(!ftp_login($this->conn, $username, $password)) {
				msg_log(ERROR, "Failed to authenticate user [".$username."].", NOTIFY);
				return false;
			}
		}
		
		msg_log(DEBUG, "User [". $username ."] logged in.", SILENT);
		return $this->conn;		
	}

	/*
	* Name: ftpPut
	* Desc: Uploads file to the FTP server
	* Inpt: $srcFile	-> Type: String,	Value: Absolute path of the file to be uploaded
	*		$dstPath	-> Type: String,	Value: Destination path to copy the file to
	* 		$overwrite	-> Type: Boolean,	Value: Activate file overwrite if TRUE
	* Outp:				-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
	* Date: 25.02.2008
	*/
	function ftpPut($srcFile, $dstPath, $overwrite) {
		
		if ($this->conn) {
			
			if ( !is_file($srcFile) ) {
				msg_log(ERROR, "[".$srcFile."] is not a regular file.", SILENT);
				return false;
			}
			
			// Add leading slash to the destination path in case it is missing
			if (strpos($dstPath, '/') === 0) {
				$fullPath = ftp_pwd($this->conn) . $dstPath;
			} else {
				$fullPath = ftp_pwd($this->conn) . '/' . $dstPath;
			}
			
			msg_log(DEBUG, "Uploading file [".$srcFile."] to [".$fullPath."].", SILENT);
			
			// Check if the directory exist, if not - create it
			if ( !@ftp_chdir($this->conn, $fullPath) ) {
				
				msg_log(INFO, "[".$fullPath."] does not exist. Creating.", SILENT);
				
				if (!$this->mkDir($fullPath)) {
					msg_log(ERROR, " Failed to create directory [".$fullPath."].", SILENT);
					return false;
				}
			}
			
			// Get the name of the uploaded file
			$dstFileName = substr($srcFile, strrpos($srcFile, '/') - strlen($srcFile) + 1);
			
			// Check if file with the same name already exist in this location.
			// There is no ftp_exist(filename), so we use a little hack
			if ( ftp_nlist($this->conn, $fullPath) && in_array($dstFileName, ftp_nlist($this->conn, $fullPath)) ) {
				
				if ($overwrite) {
					msg_log(INFO, "File [".$dstFileName."] already exist. Overwriting.", SILENT);
				} else {
					msg_log(INFO, "File [".$dstFileName."] already exist. Skipping file upload.", SILENT);
					return false;
				}
			}
			
			// Determine if the trailing slash was added to the path
			if (strrpos($fullPath, '/') == strlen($fullPath) - 1) {
				$fullPath .= $dstFileName;
			} else {
				$fullPath .= '/' . $dstFileName;
			}
			
			// Finally we upload the file to the FTP site
			if ( !ftp_put($this->conn, $fullPath, $srcFile, FTP_BINARY)) {
				msg_log(ERROR, "Failed to upload file [".$srcFile."] to [".$fullPath."].", SILENT);
				return false;
			}
			
		} else {
			msg_log(ERROR, "Connection to FTP server does not exist.", SILENT);
			return false;
		}
		
		return true;
	}
	
	/*
	* Name: mkDir
	* Desc: Recursively creates directories on the FTP server
	* Inpt: $path	-> Type: String,	Value: Directory path to create
	* Outp:			-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
	* Date: 25.02.2008
	*/
	function mkDir($path) {
		
		$dir = split("/", $path);
		$path = "";
		
		for ($i=1; $i<count($dir); $i++) {
			
			$path .= "/".$dir[$i];
			
			if( !@ftp_chdir($this->conn, $path) ) {
				
				@ftp_chdir($this->conn, "/");
				
				msg_log(DEBUG, "Creating directory [". $path ."].", SILENT);
				if( !@ftp_mkdir($this->conn, $path) ) {
					return false;
				}
			}
		}
		
		return true;
	}
}
?>