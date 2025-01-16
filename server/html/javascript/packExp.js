/* 
** Description:	Contains functions for Scan Explorer
**				
** @package:	Main
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	13/02/2008
*/

/*
* Name: openPack
* Desc: Redirects the window to the Source Scanner and passes the scan name to it
* Inpt:	scanName	-> Type: String,	Value: Name of the scan
* Outp: none
* Date: 13/02/2008
*/
function openPack(packName) {
	
	document.getElementById("packName").value = packName;
	document.fePanel.submit();
}

/*
* Name: getPack
* Desc: Submits the action to get the scan from either FTP or local fs
* Inpt:	src			-> Type: String,	Value: Pressed buton
*		prjName		-> Type: String,	Value: Name of the project
*		packName	-> Type: String,	Value: Name of the package
* Outp: none
* Date: 29/02/2008
*/
function getPack(src, prjName, packFileName) {
	
	var packName = prompt("Please enter name for this package.");
	
	if (!packName) {
		return false;
	}
	
	pckNameOK = false;
	
	while (!pckNameOK) {
		// Check if users input is valid
		var packNameRegxp = /^[a-zA-Z0-9_\.\- ]{6,30}$/;
		if (!packNameRegxp.test(packName)) {
			var packName = prompt("Name should be between 6 and 30 characters long\nand can only contain [a-z][A-Z][0-9][_][.][-][SPACE] characters.");
			if ( !packName ) {
				return false;
			}
			pckNameOK = false;
		} else {
			// Check if there is not a scan already with the same name
			if ( dbCheckForScan(prjName, packName) == 1) {
				var packName = prompt("Package with the same name already exist.\nPlease choose different one..");
				if ( !packName ) {
					return false;
				}
				pckNameOK = false;
			} else {
				pckNameOK = true;
			}
		}
	}
	
	
	document.getElementById("btnAction").value = src;
	document.getElementById("packageName").value = packName;
	document.getElementById("packFileName").value = packFileName;

	document.scPanel.submit();
}

/**
* Name: checkSelected
* Desc: Verifies if the user has selected packages from the list
* Inpt:	src			-> Type: String,	Value: Pressed buton
* Outp: 			-> Type: Mixed,		Value:	Number of selected packages if more than one is selected,
*												Name of the package is only one is selected,
*												FASLSE if none is selected.
* Date: 08/02/2008
*/
function checkSelected(src) {
	
	var tblRows = document.getElementById("piiScansTable").rows;
	var numChecked = 0;
	var selValue = '';
	
	if (tblRows.length > 0) {
		
		for (var i=0; i<tblRows.length; i++) {
			if (tblRows[i].cells[0].firstChild.checked == true) {
				selValue = tblRows[i].cells[0].firstChild.value;
				numChecked++;
			}
		}
		
		if (numChecked > 0) {
			
			if (src) {
				document.getElementById("btnAction").value = src;
			}
			
			if (numChecked > 1) {
				return numChecked;
			} else {
				return selValue;
			}
		}
	}
	
	alert("No packages selected to work with.");
	return false;
}

/*
* Name: selectAll
* Desc: Selects/Deselects all files
* Inpt:	none
* Outp: none
* Date: 08/02/2008
*/
function selectAll(masterCheck) {
	
	var tblRows = document.getElementById("piiScansTable").rows;
	
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
* Name: setPackName
* Desc: Prompts the user to set the name of the package
* Inpt:	none
* Outp: none
* Date: 04/03/2008
*/
function setPackName(prjName) {
	
	var packName = prompt("Please enter name for this package.");
	
	if (!packName) {
		return false;
	}
	
	pckNameOK = false;
	
	while (!pckNameOK) {
		// Check if users input is valid
		var packNameRegxp = /^[a-zA-Z0-9_\.\- ]{6,30}$/;
		if (!packNameRegxp.test(packName)) {
			var packName = prompt("Name should be between 6 and 30 characters long\nand can only contain [a-z][A-Z][0-9][_][.][-][SPACE] characters.");
			if ( !packName ) {
				return false;
			}
			pckNameOK = false;
		} else {
			// Check if there is not a scan already with the same name
			if ( dbCheckForScan(prjName, packName) == 1) {
				var packName = prompt("Package with the same name already exist.\nPlease choose different one..");
				if ( !packName ) {
					return false;
				}
				pckNameOK = false;
			} else {
				pckNameOK = true;
			}
		}
	}
	
	document.getElementById("pkgName").value = packName;
	
	return true;
}

/*
* Name: uploadLocal
* Desc: Shows a pop-up to the user to choose the file to upload
* Inpt:	none
* Outp: none
* Date: 22/01/2008
*/
function uploadFromLocal(currProj) {
	
	var mask = document.createElement('div');
	var msgPanel = document.createElement('div');
	var tblTitle = document.createElement('div');
	var tblCntnt = document.createElement('div');
	var sectForm = document.createElement('form');
	var sizeLimit = document.createElement('input');
	var pckName = document.createElement('input');
	var selector = document.createElement('input');
	var btnSubmit = document.createElement('input');
	var btnCancel = document.createElement('button');
	
	mask.setAttribute('id', 'windowCover');
	mask.setAttribute('class', 'windowCover');
	
	msgPanel.setAttribute('id', 'messagePanel');
	msgPanel.setAttribute('class', 'messagePanel');
	msgPanel.setAttribute('style', 'width:350px; height:130px;');
	
	tblTitle.setAttribute('class', 'popPanel');
	tblTitle.setAttribute('style', 'margin-top:20px; background-color:#A9A9A9; color:black');
	tblTitle.innerHTML = "Please choose file to get:";
	
	tblCntnt.setAttribute('class', 'popPanel');
	tblCntnt.setAttribute('style', 'height:25px; background-color:#BBBBBB;');
	
	sectForm.setAttribute('method', 'POST');
	sectForm.setAttribute('name', 'fileSelect');
	sectForm.setAttribute('id', 'fileSelect');
	sectForm.setAttribute('enctype', 'multipart/form-data');
	sectForm.setAttribute('action', 'packExp.php');
	
	sizeLimit.setAttribute('type', 'hidden');
	sizeLimit.setAttribute('name', 'MAX_FILE_SIZE');
	sizeLimit.setAttribute('value', '1048576');
	
	pckName.setAttribute('type', 'hidden');
	pckName.setAttribute('name', 'pkgName');
	pckName.setAttribute('id', 'pkgName');
	
	selector.setAttribute('type', 'file');
	selector.setAttribute('name', 'uplFile');
	selector.setAttribute('class', 'dropMenu');
	selector.setAttribute('size', '33');
	
	btnSubmit.setAttribute('type', 'submit');
	btnSubmit.setAttribute('class', 'fmtButton');
	btnSubmit.setAttribute('name', 'btnAction');
	btnSubmit.setAttribute('value', 'Upload');
	btnSubmit.setAttribute('style', 'width:100px; height:20px; margin-right:20px; border-color:666666; color:black; float:right;');
	btnSubmit.setAttribute('onClick', 'dbLogAction("Confirm Upload"); return setPackName("' + currProj + '")');
	
	btnCancel.setAttribute('class', 'fmtButton');
	btnCancel.setAttribute('style', 'width:100px; height:20px; margin-right:20px; border-color:666666; color:black; float:right;');
	btnCancel.setAttribute('onClick', 'dbLogAction("Cancel"); resetMessage();');
	btnCancel.innerHTML = "Cancel";
	
	document.body.appendChild(mask);
	
	msgPanel.appendChild(tblTitle);
	tblCntnt.appendChild(sizeLimit);
	tblCntnt.appendChild(selector);
	tblCntnt.appendChild(pckName);
	msgPanel.appendChild(tblCntnt);
	msgPanel.appendChild(btnCancel);
	msgPanel.appendChild(btnSubmit);
	sectForm.appendChild(msgPanel);
	
	document.body.appendChild(sectForm);
}

/*
* Name: uploadFromFTP
* Desc: Shows a pop-up to the user to choose the file to upload
* Inpt:	none
* Outp: none
* Date: 22/01/2008
*/
function uploadFromFTP(currProj) {
	
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
	tblTitle.innerHTML = "Please choose file to get:";
	
	tblCntnt.setAttribute('class', 'popPanel');
	tblCntnt.setAttribute('style', 'height:25px; background-color:#BBBBBB;');
	
	selector.setAttribute('name', 'ftpScanSelector');
	selector.setAttribute('class', 'dropMenu');
	selector.options[0] = new Option('Select package from the list.', 'none');
	
	var tokenArray = dbGetScansFTP(currProj).split(',');
	
	for ( var i=0; i<tokenArray.length; i++ ) {
		selector.options[i+1] = new Option(tokenArray[i], tokenArray[i]);
		selector.options[i+1].setAttribute('onClick', 'dbLogAction("Confirm Get from FTP"); getPack("Get from FTP", "' + currProj + '", "' + tokenArray[i] + '")');
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
}

/*
* Name: checkinPack
* Desc: Checks in the selected package to ClearCase
* Inpt:	projName	->	Type: String,	Value: Name of the project currently in use
*		uMail		->	Type: String,	Value: E-mail of the user
* Outp: none
* Date: 01/04/2008
*/
function checkinPack(projName, uMail) {
	
	// Check users selection (only one package at a time can be selected)
	var selPack = checkSelected();
	if (!selPack) { return; }
	if (isInteger(selPack)) { alert("Only one package can be checked in at a time"); return; }
	
	// Check if this is a source package
	
	if (dbGetPackLangCode(projName, selPack) == 'en') { alert("Source packages cannot be Checked-IN."); return; }

	// Check if project is set in the users properties
	if (projName == "empty" || !projName) { alert("No project selected in the Preferences page."); return; }
	
	// Get project's details
	var projData = dbGetProjectData(projName);
	// Get language code set to the current user
	var langCode = document.getElementById("langCode").value;
	
	if (projData == -5) {
		alert("Internal server error, please inform the administrator.");
		return;
	}
	
	if (projData == "empty") {
		alert("No data exist in the database for this project.");
		return;
	}
	
	var mask = document.createElement('div');
	var msgPanel = document.createElement('div');
	var tblTitle = document.createElement('div');
	var tblCntnt = document.createElement('div');
	
	var sectForm = document.createElement('form');
	var pckName = document.createElement('input');
	var lngName = document.createElement('input');
	
	var row_1 = document.createElement('div');
	var cell_1_1 = document.createElement('div');
	var cell_1_2 = document.createElement('div');
	var row_2 = document.createElement('div');
	var cell_2_1 = document.createElement('div');
	var cell_2_2 = document.createElement('div');
	var row_3 = document.createElement('div');
	var cell_3_1 = document.createElement('div');
	var cell_3_2 = document.createElement('div');
	var row_4 = document.createElement('div');
	var cell_4_1 = document.createElement('div');
	var cell_4_2 = document.createElement('div');
	var row_5 = document.createElement('div');
	var cell_5_1 = document.createElement('div');
	var cell_5_2 = document.createElement('div');
	var row_6 = document.createElement('div');
	var cell_6_1 = document.createElement('div');
	var cell_6_2 = document.createElement('div');
	
	var selector = document.createElement('select');
	var btnSubmit = document.createElement('input');
	var btnCancel = document.createElement('button');
	
	mask.setAttribute('id', 'mask');
	mask.setAttribute('class', 'windowCover');
	
	msgPanel.setAttribute('id', 'msgPanel');
	msgPanel.setAttribute('class', 'messagePanel');
	msgPanel.setAttribute('style', 'width:400px; height:240px;');
	
	tblTitle.setAttribute('class', 'popPanel');
	tblTitle.setAttribute('style', 'width: 350px; margin-top:20px; background-color:#A9A9A9; color:black');
	tblTitle.innerHTML = "Check-in package into ClearCase";
	
	tblCntnt.setAttribute('class', 'popPanel');
	tblCntnt.setAttribute('style', 'width: 350px; height:130px; background-color:#BBBBBB;');
	
	row_1.setAttribute('class', 'highlightOn');
	cell_1_1.setAttribute('class', 'popLabel');
	cell_1_1.setAttribute('style', 'width:100px; margin-top:5px; float:left; font-weight:bold;');
	cell_1_1.innerHTML = "Project name:";
	cell_1_2.setAttribute('class', 'popLabel');
	cell_1_2.setAttribute('style', 'width:220px; margin-top:5px; float:right;');
	cell_1_2.innerHTML = projData.getName();
	row_2.setAttribute('class', 'highlightOff');
	cell_2_1.setAttribute('class', 'popLabel');
	cell_2_1.setAttribute('style', 'width:100px; float:left; font-weight:bold;');
	cell_2_1.innerHTML = "CC Location:";
	cell_2_2.setAttribute('class', 'popLabel');
	cell_2_2.setAttribute('style', 'width:220px; float:right;');
	cell_2_2.innerHTML = projData.getCcLocation();
	row_3.setAttribute('class', 'highlightOn');
	cell_3_1.setAttribute('class', 'popLabel');
	cell_3_1.setAttribute('style', 'width:100px; float:left; font-weight:bold;');
	cell_3_1.innerHTML = "CC View:";
	cell_3_2.setAttribute('class', 'popLabel');
	cell_3_2.setAttribute('style', 'width:220px; float:right;');
	cell_3_2.innerHTML = projData.getCcView(uMail);
	row_4.setAttribute('class', 'highlightOff');
	cell_4_1.setAttribute('class', 'popLabel');
	cell_4_1.setAttribute('style', 'width:100px; float:left; font-weight:bold;');
	cell_4_1.innerHTML = "CC Activity:";
	cell_4_2.setAttribute('class', 'popLabel');
	cell_4_2.setAttribute('style', 'width:220px; float:right;');
	cell_4_2.innerHTML = projData.getCcActivity();
	row_5.setAttribute('class', 'highlightOff');
	cell_5_1.setAttribute('class', 'popLabel');
	cell_5_1.setAttribute('style', 'width:100px; float:left; font-weight:bold;');
	cell_5_1.innerHTML = "Dev review:";
	cell_5_2.setAttribute('class', 'popLabel');
	cell_5_2.setAttribute('style', 'width:220px; float:right;');
	cell_5_2.innerHTML = projData.getCcCodeReview();
	row_6.setAttribute('class', 'highlightOff');
	cell_6_1.setAttribute('class', 'popLabel');
	cell_6_1.setAttribute('style', 'width:100px; float:left; font-weight:bold;');
	cell_6_1.innerHTML = "Language:";
	cell_6_2.setAttribute('class', 'popLabel');
	cell_6_2.setAttribute('style', 'width:220px; float:right;');
	
	if ( langCode == 'en' ) {
		
		var tokenArray = dbGetLangList().split(',');
		
		selector.setAttribute('name', 'langSelector');
		selector.setAttribute('class', 'dropMenu');
		selector.options[0] = new Option('Select language.', 'none');
		
		index = 1;
		
		for ( var i=0; i<tokenArray.length; i=i+2 ) {
			
			if (tokenArray[i] == 'en') continue;
			
			selector.options[index] = new Option(tokenArray[i+1], tokenArray[i]);
			selector.options[index].setAttribute('onClick', 'dbLogAction("Select language"); document.getElementById("lngCode").value = this.value;');
			
			index++;
		}
		cell_6_2.appendChild(selector);
	} else {
		cell_6_2.innerHTML = langCode;
	}
	
	sectForm.setAttribute('method', 'POST');
	sectForm.setAttribute('name', 'packSelect');
	sectForm.setAttribute('id', 'packSelect');
	sectForm.setAttribute('enctype', 'multipart/form-data');
	sectForm.setAttribute('action', 'packExp.php');
	
	pckName.setAttribute('type', 'hidden');
	pckName.setAttribute('name', 'pkgName');
	pckName.setAttribute('id', 'pkgName');
	pckName.setAttribute('value', selPack);
	
	lngName.setAttribute('type', 'hidden');
	lngName.setAttribute('name', 'lngCode');
	lngName.setAttribute('id', 'lngCode');
	lngName.setAttribute('value', langCode);
	
	btnCancel.setAttribute('class', 'fmtButton');
	btnCancel.setAttribute('style', 'width:100px; height:20px; margin-right:20px; border-color:666666; color:black; float:right;');
	btnCancel.setAttribute('onClick', 'dbLogAction("Cancel"); resetMessage();');
	btnCancel.innerHTML = "Cancel";
	
	btnSubmit.setAttribute('type', 'submit');
	btnSubmit.setAttribute('class', 'fmtButton');
	btnSubmit.setAttribute('name', 'btnAction');
	btnSubmit.setAttribute('value', 'Check-in Package');
	btnSubmit.setAttribute('onClick', 'if (document.getElementById("lngCode").value == "en") {alert("Please choose language first"); return false;} clearCheckinPack(); popMessage("Check-in operation in progress..<br/>This may take a very long time, please be patient.", "wait"); dbLogAction("Confirm Check-in Package");');
	btnSubmit.setAttribute('style', 'width:130px; height:20px; margin-right:20px; border-color:666666; color:black; float:right;');
	
	document.body.appendChild(mask);
	
	msgPanel.appendChild(tblTitle);
	
	row_1.appendChild(cell_1_1);
	row_1.appendChild(cell_1_2);
	tblCntnt.appendChild(row_1);
	row_2.appendChild(cell_2_1);
	row_2.appendChild(cell_2_2);
	tblCntnt.appendChild(row_2);
	row_3.appendChild(cell_3_1);
	row_3.appendChild(cell_3_2);
	tblCntnt.appendChild(row_3);
	row_4.appendChild(cell_4_1);
	row_4.appendChild(cell_4_2);
	tblCntnt.appendChild(row_4);
	row_5.appendChild(cell_5_1);
	row_5.appendChild(cell_5_2);
	tblCntnt.appendChild(row_5);
	row_6.appendChild(cell_6_1);
	row_6.appendChild(cell_6_2);
	tblCntnt.appendChild(row_6);
	msgPanel.appendChild(tblCntnt);
	
	sectForm.appendChild(pckName);
	sectForm.appendChild(lngName);
	sectForm.appendChild(btnSubmit);
	sectForm.appendChild(btnCancel);
	msgPanel.appendChild(sectForm);
	
	document.body.appendChild(msgPanel);
}

/*
* Name: clearCheckinPack
* Desc: Name speaks for itself
* Inpt:	none
* Outp: none
* Date: 29/01/2008
*/
function clearCheckinPack() {

	var mask = document.getElementById('mask');
	var msgPanel = document.getElementById('msgPanel');
	
	mask.parentNode.removeChild(mask);
	
	msgPanel.style.height = 0;
	msgPanel.style.margin = 0;
	msgPanel.style.MozOpacity = 0;
	msgPanel.style.backgroundColor = 'transparent';
}

/*
* Name: delPIIScan
* Desc: Deletes the currently selected scan
* Inpt:	src			-> Type: String,	Value: Pressed buton
* Outp: none
* Date: 22/01/2008
*/
function delPIIScans(src) {
	
	if (checkSelected()) {
		document.getElementById("btnAction").value = src;
		return confirm("Are you sure you want to delete selected scans?");
	}
	
	return false;
}

/*
* Name: dbGetProjectData
* Desc: Prepares the XML HTTP Request to fetch projects for this manager from the DB.
* Inpt:	pName	-> Type: String,	Value: Name of the project
* Outp: 		-> Type: Object,	Value: ProjectData
* Date: 02.02.2008
*/
function dbGetProjectData(pName) {

	var url = '../utils/httpRequests.php';
	var act = 'getProjectData';
	var par = '&project=' + pName;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbGetProjectData
* Desc: Prepares the XML HTTP Request to fetch the language coge for the given package.
* Inpt:	projName	-> Type: String,	Value: Name of the project
* Inpt:	packName	-> Type: String,	Value: Name of the project
* Outp: 			-> Type: String,	Value: Language code
* Date: 11.06.2008
*/
function dbGetPackLangCode(projName, packName) {
	
	var url = '../utils/httpRequests.php';
	var act = 'getPackLangCode';
	var par = '&projName=' + projName + '&packName=' + packName;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbDelScan
* Desc: Prepares HTTP request to scan the project for PII files
* Inpt:	pName	-> Type: String,	Value: name of project
*		sMail	-> Type: String,	Value: name of the scan
* Outp: none
* Date: 14/01/2008
*/
function dbDelScan(pName, sName) {
	var url = '../utils/httpRequests.php';
	var act = 'delPIIScan';
	var par = '&project=' + pName + '&scan=' + sName;
	
	xmlHttpRequest(url, act, par);
}

/*
* Name: dbDelScan
* Desc: Prepares HTTP request to scan the project for PII files
* Inpt:	pName	-> Type: String,	Value: name of project
*		uMail	-> Type: String,	Value: E-mail of the user
* Outp: none
* Date: 14/01/2008
*/
function dbGetScansFTP(pName) {
	var url = '../utils/httpRequests.php';
	var act = 'getScansFTP';
	var par = '&project=' + pName;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbCheckForScan
* Desc: Prepares HTTP request to scan the project for PII files
* Inpt:	prjName	->	Type: String,	Value: name of the project
*		scnName	->	Type: String,	Value: name of the scan
* Outp: 			Type: Boolean,	Value: TRUE if exist, FALSE otherwise
* Date: 22/01/2008
*/
function dbCheckForScan(prjName, scnName) {
	var url = '../utils/httpRequests.php';
	var act = 'checkForPIIScan';
	var par = '&project=' + prjName + '&scan=' + scnName;
	
	return syncXmlHttpRequest(url, act, par);
}

