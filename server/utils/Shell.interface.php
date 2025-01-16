<?php
/* 
** Description:	Shell interface
**
** @package:	Security
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	19/12/2007
*/
interface Shell {

	// Authenticate the user
	function sshLogin($username, $password);
	// Create shell session
	function createSession();
}
?>