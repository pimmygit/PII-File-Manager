/* 
** Description:	Contains functions for File Explorer
**				
** @package:	Main
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	06/03/2008
*/

/*
* Name: choosePack
* Desc: Verifies that a project has been selected and initiates the actual scan
* Inpt:	packName	-> Type: String,	Value: Name of the scan
* Outp: none
* Date: 06/03/2008
*/
function choosePack(packName) {
	
	document.getElementById("packName").value = packName;
	document.fePanel.submit();
}

/*
* Name: checkSelected
* Desc: Verifies if the user has selected files from the list
* Inpt:	none
* Outp: Type: Boolean,	Value: TRUE if yes, FASLSE otherwise
* Date: 08/02/2008
*/
function checkSelected() {
	
	var tblRows = document.getElementById("piiFilesTable").rows;
	var numChecked = 0;
	
	if (tblRows.length > 0) {
		
		for (var i=0; i<tblRows.length; i++) {

			if (tblRows[i].cells[0].firstChild.checked == true) {
				numChecked++;
			}
			
			if (numChecked > 0) {
				return true;
			}
		}
	}
	
	alert("No files selected to work with.");
	return false;
}

/*
* Name: selectAll
* Desc: Selects/Deselects all files
* Inpt:	masterCheck	->	Type: Object,	Value: Master tick
* Outp: none
* Date: 08/02/2008
*/
function selectAll(masterCheck) {
	
	var tblRows = document.getElementById("piiFilesTable").rows;
	
	if (tblRows.length > 0) {
		
		for (var i=0; i<tblRows.length; i++) {

			if (masterCheck.checked == true) {
				tblRows[i].cells[0].firstChild.checked = true;
			} else {
				tblRows[i].cells[0].firstChild.checked = false;
			}
		}
	} else {
		masterCheck.checked = false;
	}
}

/*
* Name: delPIIPack
* Desc: Deletes the currently selected scan
* Inpt:	none
* Outp: none
* Date: 06/03/2008
*/
function delPIIPack() {
	
	document.getElementById("btnAction").value = "Delete Pack";
	
	var selPack = document.getElementById("packName").value;
	
	return confirm("Are you sure you want to delete package '" + selPack + "'?");
}

/*
* Name: confirmUpload
* Desc: Pops out summary of where the the file will be uploaded
* Inpt:	currProj	->	Type: String,	Value: Name of the project in use
* Outp: none
* Date: 22/01/2008
*/
function confirmUpload(currProj) {
	
	var packName = document.getElementById("packName").value;
	var targPath = document.getElementById("relPath").value;
	var absFName = document.getElementById("uplFile").value;
	
	// The only relative path of two letters is './'
	if ( targPath.length < 3 ) {
		targPath = '.';
	}
	
	// Get the file name from the absolute path
	if ( absFName.substr(absFName.lastIndexOf("/") < 0) ) {
		var fName = absFName.substr(absFName.lastIndexOf("\\") + 1, absFName.length);
	} else {
		var fName = absFName.substr(absFName.lastIndexOf("/") + 1, absFName.length);
	}
	
	// Check if this location exist. If not prompt the user if it should be created
	if ( !dbFileExist(currProj, packName, targPath) ) {
		var answer = confirm('Location: [' + targPath + '] does not exist.\nDo you want to create it?');
		if (!answer) {
			return false;
		}
	}
	
	// Check if this file already exist
	if ( dbFileExist(currProj, packName, targPath + '/' + fName) ) {
		var answer = confirm('File: [' + fName + '] already exist at the specified location.\nDo you want to overwrite it?');
		if (!answer) {
			return false;
		}
	}

	return true;
}

/*
* Name: uploadLocal
* Desc: Shows a pop-up to the user to choose the file to upload
* Inpt:	currProj	->	Type: String,	Value: Name of the project in use
*		currPack	->	Type: String,	Value: Name of the package in use
* Outp: none
* Date: 11/03/2008
*/
function uploadFile(currProj, currPack) {
	
	// Minimum length of package name is 6 chars
	if (currPack.length < 6) {
		alert("No package selected.");
		return;
	}
	
	var mask = document.createElement('div');
	var msgPanel = document.createElement('div');
	var tblCntnt_1 = document.createElement('div');
	var tblCntnt_2 = document.createElement('div');
	var tblTitle_1 = document.createElement('p');
	var tblTitle_2 = document.createElement('p');
	var sectForm = document.createElement('form');
	var packHolder = document.createElement('input');
	var sizeLimit = document.createElement('input');
	var targSelect = document.createElement('input');
	var fileSelect = document.createElement('input');
	var btnSubmit = document.createElement('input');
	var btnCancel = document.createElement('button');
	
	mask.setAttribute('id', 'windowCover');
	mask.setAttribute('class', 'windowCover');
	
	msgPanel.setAttribute('id', 'messagePanel');
	msgPanel.setAttribute('class', 'messagePanel');
	msgPanel.setAttribute('style', 'width:350px; height:170px;');
	
	sectForm.setAttribute('method', 'POST');
	sectForm.setAttribute('name', 'fileSelect');
	sectForm.setAttribute('id', 'fileSelect');
	sectForm.setAttribute('enctype', 'multipart/form-data');
	sectForm.setAttribute('action', 'fileExp.php');
	
	tblCntnt_1.setAttribute('class', 'popPanel');
	tblCntnt_1.setAttribute('style', 'height:45px; margin-top:10px; padding:5px; background-color:#BBBBBB;');
	
	tblTitle_1.setAttribute('align', 'left');
	tblTitle_1.setAttribute('style', 'margin:3px; color:black;');
	tblTitle_1.innerHTML = "Target relative path:";
	
	targSelect.setAttribute('id', 'relPath');
	targSelect.setAttribute('name', 'relPath');
	targSelect.setAttribute('type', 'text');
	targSelect.setAttribute('class', 'dropMenu');
	targSelect.setAttribute('value', './');
		
	tblCntnt_2.setAttribute('class', 'popPanel');
	tblCntnt_2.setAttribute('style', 'height:45px; margin-top:10px; padding:5px; background-color:#BBBBBB;');
	
	tblTitle_2.setAttribute('align', 'left');
	tblTitle_2.setAttribute('style', 'margin:3px; color:black;');
	tblTitle_2.innerHTML = "Absolute file name to upload:";
	
	packHolder.setAttribute('type', 'hidden');
	packHolder.setAttribute('name', 'packName');
	packHolder.setAttribute('value', currPack);
	
	sizeLimit.setAttribute('type', 'hidden');
	sizeLimit.setAttribute('name', 'MAX_FILE_SIZE');
	sizeLimit.setAttribute('value', '16777216');
	
	fileSelect.setAttribute('id', 'uplFile');
	fileSelect.setAttribute('name', 'uplFile');
	fileSelect.setAttribute('type', 'file');
	fileSelect.setAttribute('class', 'dropMenu');
	fileSelect.setAttribute('size', '33');
		
	btnSubmit.setAttribute('type', 'submit');
	btnSubmit.setAttribute('class', 'fmtButton');
	btnSubmit.setAttribute('name', 'btnAction');
	btnSubmit.setAttribute('value', 'Upload File');
	btnSubmit.setAttribute('style', 'width:100px; height:20px; margin-right:20px; border-color:666666; color:black; float:right;');
	btnSubmit.setAttribute('onClick', 'dbLogAction("Confirm Upload File"); return confirmUpload("' + currProj + '")');
	
	btnCancel.setAttribute('class', 'fmtButton');
	btnCancel.setAttribute('style', 'width:100px; height:20px; margin-right:20px; border-color:666666; color:black; float:right;');
	btnCancel.setAttribute('onClick', 'dbLogAction("Cancel"); resetMessage();');
	btnCancel.innerHTML = "Cancel";
	
	
	tblCntnt_1.appendChild(tblTitle_1);
	tblCntnt_1.appendChild(targSelect);
	msgPanel.appendChild(tblCntnt_1);
	
	tblCntnt_2.appendChild(tblTitle_2);
	tblCntnt_2.appendChild(packHolder);
	tblCntnt_2.appendChild(sizeLimit);
	tblCntnt_2.appendChild(fileSelect);
	msgPanel.appendChild(tblCntnt_2);
	
	msgPanel.appendChild(btnCancel);
	msgPanel.appendChild(btnSubmit);
	
	sectForm.appendChild(msgPanel);
	
	document.body.appendChild(mask);
	document.body.appendChild(sectForm);
}

/*
* Name: delFiles
* Desc: Asks for confirmation to delete selected files
* Inpt:	none
* Outp: none
* Date: 17/03/2008
*/
function delFiles() {
	
	if (!checkSelected()) {
		return false;
	}
	
	document.getElementById("btnAction").value = "Remove Files";

	return confirm("Are you sure you want to delete all selected files?");
}

/*
* Name: doRename
* Desc: Sets the language and submits the form
* Inpt:	none
* Outp: none
* Date: 13/03/2008
*/
function doRename(src, langCode) {

	document.getElementById("btnAction").value = src;
	document.getElementById("userLangCode").value = langCode;

	document.fePanel.submit();
}

/*
* Name: renameFiles
* Desc: Sets the language code if the user is a manager
* Inpt:	currProj	->	Type: String,	Value: Name of the project in use
*		packName	->	Type: String,	Value: Name of the package in use
* Outp: none
* Date: 13/03/2008
*/
function renameFiles(projName, packName) {
	
	if (!checkSelected()) {	return;	}
	
	// Check if this is a source package
	var packLangCode = document.getElementById("packLangCode").value;
	if (packLangCode == 'en') { alert("Source packages cannot be renamed."); return; }
	
	// Check if this package has been translated already and on what language	
	var transl = dbCheckTranslated(projName, packName);
	if (transl) { alert("Renaming rules has been already applied to this package."); return; }
		
	var userLangCode = document.getElementById("userLangCode").value;
	
	if (userLangCode == 'en') {
		
		var tokenArray = dbGetLangList().split(',');
		
		var mask = document.createElement('div');
		var msgPanel = document.createElement('div');
		var tblTitle = document.createElement('div');
		var tblCntnt = document.createElement('div');
		var selector = document.createElement('select');
		var btnCancel = document.createElement('button');
		
		mask.setAttribute('id', 'windowCover');
		mask.setAttribute('class', 'windowCover');
		
		msgPanel.setAttribute('id', 'messagePanel');
		msgPanel.setAttribute('class', 'messagePanel');
		msgPanel.setAttribute('style', 'width:350px; height:130px;');
		
		tblTitle.setAttribute('class', 'popPanel');
		tblTitle.setAttribute('style', 'margin-top:20px; background-color:#A9A9A9; color:black');
		tblTitle.innerHTML = "Please select language from the list:";
		
		tblCntnt.setAttribute('class', 'popPanel');
		tblCntnt.setAttribute('style', 'height:25px; background-color:#BBBBBB;');
		
		selector.setAttribute('name', 'ftpScanSelector');
		selector.setAttribute('class', 'dropMenu');
		selector.options[0] = new Option('Select language.', 'none');
		
		index = 1;
		
		for ( var i=0; i<tokenArray.length; i=i+2 ) {
			
			if (tokenArray[i] == 'en') continue;
			
			selector.options[index] = new Option(tokenArray[i+1], tokenArray[i]);
			selector.options[index].setAttribute('onClick', 'dbLogAction("Do Apply Renaming Rules"); doRename("Apply Renaming Rules", this.value);');
			
			index++;
		}
		
		btnCancel.setAttribute('class', 'fmtButton');
		btnCancel.setAttribute('style', 'width:100px; height:20px; margin-right:20px; border-color:666666; color:black;');
		btnCancel.setAttribute('onClick', 'dbLogAction("Cancel"); resetMessage();');
		btnCancel.innerHTML = "Cancel";
		
		document.body.appendChild(mask);
		
		msgPanel.appendChild(tblTitle);
		tblCntnt.appendChild(selector);
		msgPanel.appendChild(tblCntnt);
		msgPanel.appendChild(btnCancel);
		
		document.body.appendChild(msgPanel);
	} else {
		doRename("Apply Renaming Rules", userLangCode);
	}
}

/*
* Name: revRenaming
* Desc: Checks if the package has been already renamed and to what language 
* Inpt:	currProj	->	Type: String,	Value: Name of the project in use
*		packName	->	Type: String,	Value: Name of the package in use
* Outp: none
* Date: 17/03/2008
*/
function revRenaming(projName, packName) {
	
	if (!checkSelected()) {
		return false;
	}
	
	// Check if this package has been translated already and on what language	
	var transl = dbCheckTranslated(projName, packName);
	
	if (!transl) {
		alert("No renaming rules has been applied to this package yet.");
		return false;
	}
	
	document.getElementById("userLangCode").value = transl;
	document.getElementById("btnAction").value = "Revert File Renaming";
	
	return true;
}

/*
* Name: popMessage
* Desc: Pops out a message and blocks all actions to the GUI
* Inpt:	message	-> Type: String,	Value: Message to show
*		type	-> Type: String,	Value: Type of the message [wait|info|alert]
* Outp: none
* Date: 11/02/2008
*/
function popMessage(message, type) {
	
	var mask = document.createElement('div');
	var msgPanel = document.createElement('div');
	var msgArea = document.createElement('div');
	
	mask.setAttribute('id', 'windowCover');
	mask.setAttribute('class', 'windowCover');
	
	msgPanel.setAttribute('id', 'messagePanel');
	msgPanel.setAttribute('class', 'messagePanel');
	
	msgArea.setAttribute('id', 'messageArea');
	msgArea.setAttribute('class', 'messageArea');
	msgArea.innerHTML = message;
	
	if (type != 'wait') {
		
		if (type != 'info') {
			mask.setAttribute('style', 'background-color: #FF030D;');
		}
		
		msgPanel.setAttribute('onClick', 'dbLogAction("Cancel"); resetMessage();');
		msgPanel.setAttribute('style', 'cursor: pointer;');
		
		var remMesg = document.createElement('div');
		remMesg.innerHTML = 'Click to remove..';
		
		document.body.appendChild(mask);
		msgPanel.appendChild(msgArea);
		msgPanel.appendChild(remMesg);
		document.body.appendChild(msgPanel);
	} else {
		document.body.appendChild(mask);
		msgPanel.appendChild(msgArea);
		document.body.appendChild(msgPanel);
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
* Name: displayPath
* Desc: Shows the entire path of the file in a pop-up panel
* Inpt:	path	->	Type: String,	Value: full path of the file to display
* Outp: none
* Date: 28/03/2008
*/
function displayPath(parent, path) {
	
	if ( document.getElementById('pathPanel') ) {
		document.getElementById('pathPanel').parentNode.removeChild(document.getElementById('pathPanel'));
	}
	
	var msgPanel = document.createElement('div');
	
	// Width of the panel is the number of chars multiplied by 6px
	var panelWidth = 6 * path.length;
	// However we make sure we cover the entire panel underneath
	if (parent.offsetWidth > panelWidth) {
		panelWidth = parent.offsetWidth;
	}
	
	var panelLeft = getElementPos(parent)[0];
	var panelTop = getElementPos(parent)[1]; //window.event.clientY;

	
	msgPanel.setAttribute('id', 'pathPanel');
	msgPanel.setAttribute('class', 'pathPopup');
	msgPanel.setAttribute('style', 'width:' + panelWidth + 'px; top:' + panelTop + 'px; left:' + panelLeft + 'px;');
	msgPanel.onmouseout = function () { hidePath(); }
	msgPanel.innerHTML = path;
	
	document.body.appendChild(msgPanel);
}

/*
* Name: getElementPos
* Desc: Returns the position of the given element
* Inpt:	object	->	Type: Object,	Value: The object we are trying to get its positions
* Outp:				Type: Array,	Value: [posLeft, posTop]
* Date: 28/03/2008
*/
function getElementPos(object) {

	var curleft = curtop = 0;
	
	if (object.offsetParent) {
	
		do {
			curleft += object.offsetLeft;
			curtop += object.offsetTop;
		} while (object = object.offsetParent);
		
		return [curleft, curtop];
	} else {
		return false;
	}
}

/*
* Name: hidePath
* Desc: Removes the poped-out path from the screen
* Inpt:	none
* Outp: none
* Date: 28/03/2008
*/
function hidePath() {

	var pathPanel = document.getElementById('pathPanel');

	if (pathPanel) {
		pathPanel.parentNode.removeChild(pathPanel);
	}
}

/*
* Name: dbFileExist
* Desc: Removes message from the screen
* Inpt:	currProj	->	Type: String,	Value: Name of the project in use
*		packName	->	Type: String,	Value: Name of the package in use
*		targPath	->	Type: String,	Value: Relative path within the project and package
* Outp: 				Type: Boolean,	Value: TRUE if exist, FALSE otherwise
* Date: 12/03/2008
*/
function dbFileExist(currProj, packName, targPath) {
	var url = '../utils/httpRequests.php';
	var act = 'fileExistRepo';
	var par = '&project=' + currProj + '&package=' + packName + '&file=' + targPath;
	
	var resp = syncXmlHttpRequest(url, act, par);
	
	// This is translation of the error codes
	if (resp == -4) {
		return true;
	}
	
	return false;
}

/*
* Name: dbCheckTranslated
* Desc: Check if a package has been translated and on what language
* Inpt:	currProj	->	Type: String,	Value: Name of the project in use
*		packName	->	Type: String,	Value: Name of the package in use
*		targPath	->	Type: String,	Value: Relative path within the project and package
* Outp: 				Type: Boolean,	Value: TRUE if exist, FALSE otherwise
* Date: 12/03/2008
*/
function dbCheckTranslated(currProj, packName) {
	var url = '../utils/httpRequests.php';
	var act = 'packGetState';
	var par = '&project=' + currProj + '&package=' + packName;
	
	var resp = syncXmlHttpRequest(url, act, par);
	
	if ( resp != -3 ) {
		// Make sure that we get the state in the format "Ren: _LANG_CODE_"
		if (resp.substring(0,4) == "Ren:") {
			return resp.substring(5);
		}
	}
	
	return false;
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
