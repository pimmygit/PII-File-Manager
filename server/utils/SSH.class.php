<?php
/* 
** Description:	Authentication against LDAP server
**
** @package:	Security
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	17/12/2007
*/
require_once('Shell.interface.php');

Class SSH implements Shell {

	private $sshconn;
	private $sshshell;

	/*
	* Name: __construct
	* Desc: Creates an SSH connection to a server on a specified port
	* Inpt: $conHost	-> Type: String, Value: Host name to connect to
	*		$conPort	-> Type: String, Value: Port number to connect to
	* Outp: none
	* Date: 17.12.2007
	*/
	function __construct($conHost, $conPort) {
		
		// Verify that the SSH2 libraries had been installed correctly
		if (!function_exists("ssh2_connect")) {
			msg_log(ERROR, "Server not configured properly: Function ssh2_connect does not exist.", NOTIFY);
			return false;
		}
		
		// Connect to the remote server
		msg_log(DEBUG, "Connecting to server [".$conHost."] on port [".$conPort."].", SILENT);
		
		if(!($this->sshconn = ssh2_connect($conHost, $conPort))){
			msg_log(ERROR, "Failed to connect to host [".$conHost."] on port [".$conPort."].", NOTIFY);
			return false;
		}
	}

	/*
	* Name: sshLogin
	* Desc: Authenticates the user creating the SSH connection
	* Inpt: $username	-> Type: String, Value: Username accessing the server
	*		$password	-> Type: String, Value: Password to validate the user
	* Outp: none
	* Date: 17.12.2007
	*/
	function sshLogin($username, $password) {
	
		// Try to authenticate with username and password
		msg_log(DEBUG, "Authenticating user [".$username."].", SILENT);
		
		if ($this->sshconn) {
			if(!@ssh2_auth_password($this->sshconn, $username, $password)) {
				msg_log(INFO, "Failed to authenticate user [".$username."].", SILENT);
				return false;
			}
		}
		
		msg_log(DEBUG, "User [". $username ."] logged in.", SILENT);
		return true;		
	}

	/*
	* Name: sshPubKeyAuth
	* Desc: Authenticates the user creating the SSH connection
	* Inpt: $username	-> Type: String, Value: Username accessing the server
	*		$pubKey		-> Type: String, Value: Location of the public key
	* 		$prvKey		-> Type: String, Value: Location of the private key
	* 		$pass		-> Type: String, Value: passphrase
	* Outp: none
	* Date: 16.04.2008
	*/
	function sshPubKeyAuth($username, $pubKey, $prvKey, $pass) {
	
		// Try to authenticate with username and password
		msg_log(DEBUG, "Authenticating user [".$username."].", SILENT);
		
		if (!isset($pubKey) || empty($pubKey)) {
			$pubKey = '~/.ssh/authorized_keys';
		}
		if (!isset($prvKey) || empty($prvKey)) {
			$prvKey = '~/.ssh/id_rsa';
		}
		if (!isset($pass) || empty($prvKey)) {
			$pass = '';
		}
		
		if ($this->sshconn) {
			if(!@ssh2_auth_pubkey_file($this->sshconn, $username, $pubKey, $prvKey, $pass)) {
				msg_log(INFO, "Failed to authenticate user [".$username."].", SILENT);
				return false;
			}
		}
		
		msg_log(DEBUG, "User [". $username ."] logged in.", SILENT);
		return true;		
	}

	/*
	* Name: createSession
	* Desc: Creates SSH shell session
	* Inpt: none
	* Outp: Type: Object, Value: Shell SSH session
	* Date: 17.12.2007
	*/
	function createSession() {
		
		if ($this->sshconn) {
			
			if ( !($this->sshshell = @ssh2_shell($this->sshconn, 'vt102', null, 160, 90, SSH2_TERM_UNIT_CHARS)) ) {
				msg_log(ERROR, "Failed to create shell session.", SILENT);
				return false;
			}
			
			// Wait until the server sends data to the STDOUT before echoing it.
			stream_set_blocking( $this->sshshell, true );
			return $this->sshshell;
			
		} else {
			msg_log(ERROR, "SSH connection to server does not exist.", SILENT);
			return false;
		}
	}

	/*
	* Name: getConnection
	* Desc: Returns SSH connction
	* Inpt: none
	* Outp: Type: Resource, Value: Shell SSH connction
	* Date: 17.04.2008
	*/
	function getConnection() {
		
		return $this->sshconn;
	}
}

?>