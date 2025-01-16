/* 
** Description:	Contains functions for modifying user preferences
**				
** @package:	Configuration
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	27/11/2007
*/


/*
* Name: dbSetCurrProj
* Desc: Prepares the XML HTTP request for fetching the current working project.
* Inpt:	userMail	-> Type: String, Value: E-mail of the user
*		projName	-> Type: String, Value: Name of the project
* Outp: none
* Date: 11.01.2008
*/
function dbSetCurrProj(userMail, projName) {
	
	var url =	'../utils/httpRequests.php';
	var act =	'setCurrProj';
	var par =	'&mail=' + userMail + '&projName=' + projName;
	
	xmlHttpRequest(url, act, par);
}
