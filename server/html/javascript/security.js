/* 
** Description:	Contains functions for user authentication and
**				account creation.
** @package:	security
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	14/09/2007
*/

/*
* Name: userAuth
* Desc: Check if user supplied correct credentials
* Inpt:	none
* Outp: none
* Date: 17.09.2007
*/
function verifyFields() {
	
	var user = document.getElementById("ibmUSER").value;
	var pass = document.getElementById("ibmPASS").value;
		
	if ( !user || !verifyMailFormat(user) ) {
		alert("Please enter a valid IBM E-mail address.");
		return false;
	}
	
	if ( !pass ) {
		alert("Please enter your IBM Password.");
		return false;
	}
}

function verifyMailFormat(e_mail) {
	
	var emailRegxp = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
	
	if (emailRegxp.test(e_mail) != true) {
		return false;
	} else {
		return true;
	}
}