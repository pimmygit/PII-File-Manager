/* 
** Description:	Contains various functions used in in more than one web page
** @package:	utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	23/11/2007
*/



/*
* Name: positionCursor
* Desc: Places the cursor in the first empty field
* Inpt:	none
* Outp: none
* Date: 23.11.2007
*/
function positionCursor() {
	
	var userField = document.getElementById("ibmUSER");
	var passField = document.getElementById("ibmPASS");
		
	if ( !userField.value ) {
		
		try {
			userField.select();
		} catch (e) {
			userField.focus();
		}
	} else {
		
		try {
			passField.select();
		} catch (e) {
			passField.focus();
		}
	}
}

/*
* Name: isInteger
* Desc: Places the cursor in the first empty field
* Inpt:	none
* Outp: none
* Date: 23.04.2008
*/
function isInteger(s) {

	var i;
	
	for (i = 0; i < s.length; i++) {   
		// Check that current character is number.
		var c = s.charAt(i);
		if (((c < "0") || (c > "9"))) return false;
	}
	// All characters are numbers.
	return true;
}

/*
* Name: popMessage
* Desc: Pops out a message and blocks all actions to the GUI
* Inpt:	message	-> Type: String,	Value: Message to show
*		type	-> Type: String,	Value: Type of the message [wait|info|alert]
*		src		-> Type: String,	Value: From which page this function was executed (to decide if mask should be applied)
* Outp: none
* Date: 11/02/2008
*/
function popMessage(message, type, src) {
	
	var mask = document.createElement('div');
	var msgPanel = document.createElement('div');
	var msgArea = document.createElement('div');

	mask.setAttribute('id', 'windowCover');
	mask.setAttribute('class', 'windowCover');
	if (src == "preferences") {
		// Some pages have their display set to block, which breaks this
		mask.style.MozOpacity = 1;
		//mask.filter.alpha.opacity = 0;
		mask.style.backgroundColor = 'transparent';
	}

	msgPanel.setAttribute('id', 'messagePanel');
	msgPanel.setAttribute('class', 'messagePanel');
	
	msgArea.setAttribute('id', 'messageArea');
	msgArea.setAttribute('class', 'messageArea');
	msgArea.innerHTML = message;
	
	if (type != 'wait') {
		
		if (type != 'info') {
			mask.setAttribute('style', 'background-color: #FF030D;');
		}
		msgPanel.setAttribute('onClick', 'resetMessage();');
		msgPanel.setAttribute('style', 'cursor: pointer;');
		
		var remMesg = document.createElement('div');
		remMesg.innerHTML = 'Click to remove..';
		
		document.body.appendChild(mask);
		msgPanel.appendChild(msgArea);
		msgPanel.appendChild(remMesg);
		if (src != "preferences") {
			document.body.appendChild(msgPanel);
		} else {
			mask.appendChild(msgPanel);
		}
	} else {
		document.body.appendChild(mask);
		msgPanel.appendChild(msgArea);
		if (src != "preferences") {
			document.body.appendChild(msgPanel);
		} else {
			mask.appendChild(msgPanel);
		}
	}
}

/*
* Name: resetMessage
* Desc: Removes message from the screen
* Inpt:	none
* Outp: none
* Date: 29/01/2008
*/
function resetMessage() {

	var mask = document.getElementById('windowCover');
	var msgPanel = document.getElementById('messagePanel');
	
	mask.parentNode.removeChild(mask);
	msgPanel.parentNode.removeChild(msgPanel);
}

/*
* Name: dbLogAction
* Desc: Prepares HTTP request to log the user action (for Debug purposes)
* Inpt:	buttonName	->	Type: String,	Value: name of the button pressed
* Outp: none
* Date: 03/04/2008
*/
function dbLogAction(buttonName) {
	var url = '../utils/httpRequests.php';
	var act = 'usrAction';
	var par = '&usrAction=' + buttonName;
	
	// Use Synchronous XML HTTP Request to avoid sending this request at the same tame
	// as the submit request from the page.
	syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbGetLangList
* Desc: Retrieves a list of available languages
* Inpt:	none
* Outp: Type: String,	Value: CSV list of available languages [lang_code,lang_name,lang_code,lang_name,...]
* Date: 12/03/2008
*/
function dbGetLangList() {
	var url = '../utils/httpRequests.php';
	var act = 'getLangList';
	var par = '';
	
	return syncXmlHttpRequest(url, act, par);
}
