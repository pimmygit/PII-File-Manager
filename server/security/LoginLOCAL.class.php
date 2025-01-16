<?php
/* 
** Description:	Authentication against nothing
**
** @package:	Security
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	11/09/2007
*/
require_once('Login.interface.php');
require_once('../utils/mysql.php');

Class LoginLOCAL implements Login {

	function __construct() {
		
	}

	function isValidUser($username) {
		return true;		
	}
	
	function isValidPassword($password) {
		return true;
	}

	function getUserName() {
		return 'Non IBM user';
	}
	
	function hasAccess($username) {
	
		$sqlResponse = selectData(TB_USER, "user_mail", "user_mail = '".$username."'");

		if ( mysqli_num_rows($sqlResponse) > 0 ) {
			msg_log(INFO, "User: [". $username ."] found in table [". TB_USER ."] .", SILENT);
			return true;
		} else {		
			msg_log(INFO, "User: [". $username ."] not found in table [". TB_USER ."] .", SILENT);
			return false; 
		}
	}
}
?>