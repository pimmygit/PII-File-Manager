<?php
/* 
** Description:	Login interface
**
** @package:	Security
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	11/09/2007
*/
interface Login {

	// Check if the user exists in the LDAP directory
	function isValidUser($username);
	// Check if the user supplied the correct pasword
	function isValidPassword($password);
	// Retrieve users full name
	function getUserName();
	// Check if the user has access to the requested resourse
	function hasAccess($username);
}
?>