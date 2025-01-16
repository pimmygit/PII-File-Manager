/* 
** Description:	Contains functions for Source Scanner
**				
** @package:	ClearCase
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	14/01/2008
*/

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
* Name: initSetTranslated
* Desc: Checks user selection and locks the GUI during operation
* Inpt:	none
* Outp: Type: Boolean,	Value: TRUE if yes, FASLSE otherwise
* Date: 12/02/2008
*/
function initSetTranslated() {
	
	if (checkSelected()) {
		popMessage('Modifying file attributes, please wait..', 'info');
		return true;
	}
	
	return false;
}

/*
* Name: selectAll
* Desc: Selects/Deselects all files
* Inpt:	doCheck		->	Type: Boolean,	Value: If all boxes should be selected or deselected
* Outp: none
* Date: 10/03/2008
*/
function selectAll(doCheck) {
	
	var tblRows = document.getElementById("piiFilesTable").rows;
	
	if (tblRows.length > 0) {
		
		document.getElementById("checkAll").checked = doCheck;
		
		for (var i=0; i<tblRows.length; i++) {
			tblRows[i].cells[0].firstChild.checked = doCheck;
		}
	}
}

/*
* Name: selectDeselect
* Desc: Selects/Deselects all files
* Inpt:	Type: Object;	Value: Master tick
* Outp: none
* Date: 08/02/2008
*/
function selectDeselect(masterCheck) {
	
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
* Name: savePIIScan
* Desc: Prompts the user for name of the scan, save the scan information in the DB and then
*		leaves the PHP script to get the files from ClearCase
* Inpt:	prjName		-> Type: String,	Value: name of project
*		scanExist	-> Type: String,	Value: true/false
* Outp: TRUE on success, FALSE otherwise
* Date: 21/01/2008
*/
function savePIIScan(prjName, scanExist) {
	
	if (scanExist != 'true') {
		alert('Nothing has been scanned yet.');
		return false;
	}
	
	// We mark all files as selected in order to show the user
	// that all of them will be added to the package.
	selectAll(true);
	
	var scanName = prompt("Please enter name for the PII scan.");
	
	if (!scanName) {
		selectAll(false);
		return false;
	}
	
	scnNameOK = false;
	
	while (!scnNameOK) {
		// Check if users input is valid
		var scanNameRegxp = /^[a-zA-Z0-9_\.\- ]{6,30}$/;
		if (!scanNameRegxp.test(scanName)) {
			var scanName = prompt("Name should be between 6 and 30 characters long\nand can only contain [a-z][A-Z][0-9][_][.][-][SPACE] characters.");
			if ( !scanName ) {
				selectAll(false);
				return false;
			}
			scnNameOK = false;
		} else {
			// Check if there is not a scan already with the same name
			if ( dbCheckForScan(prjName, scanName) == 1) {
				var scanName = prompt("Scan with the same name already exist.\nPlease choose different one..");
				if ( !scanName ) {
					selectAll(false);
					return false;
				}
				scnNameOK = false;
			} else {
				scnNameOK = true;
			}
		}
	}
	
	popMessage('Extracting files from ClearCase..<br/>This may take a long time, please wait.', 'info');

	document.getElementById("scanValue").value = scanName;
	
	// all = 0, new = 1.
	if (document.getElementById('scanTypeValue').value == 'new') {
		var translated = 1;
	} else {
		var translated = 0;
	}
	var totalFiles = document.getElementById("labelTotalFiles").innerHTML;
	var totalSize = document.getElementById("totalSize").value;
	var scanDate = document.getElementById("labelScanDate").innerHTML;
	var scanTime = document.getElementById("labelScanTime").innerHTML;
	var rootDir = document.getElementById("labelRootDir").innerHTML;
	var scannedBy = document.getElementById("labelScannedBy").innerHTML;
	
	// If scan properties has been successfully saved, then
	// we return true so the PHP script can retrieve the files
	// from ClearCase to the PII F.M.T. repository
	if ( dbSaveScan(prjName, scanName, translated, totalFiles, totalSize, scanDate, scanTime, rootDir, scannedBy) ) {
		return true;
	} else {
		selectAll(false);
		return false;
	}
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

/*
* Name: dbSaveScan
* Desc: Prepares HTTP request to scan the project for PII files
* Inpt:	pName		->	Type: String,	Value: name of the project
*		sName		->	Type: String,	Value: name of the scan
*		translated	->	Type: INT,		Value: 0 - scan all, 1 - scan for new
*		totalFiles	->	Type: String,	Value: total number of files returned from the scan
*		totalSize	->	Type: String,	Value: total file size of all PII files
*		scanDate	->	Type: String,	Value: time when the scan was taken
*		scanTime	->	Type: String,	Value: time taken to complete the scan
*		rootDir		->	Type: String,	Value: location of the project in ClearCase
*		scannedBy	->	Type: String,	Value: name of the user performed the scan
* Outp: 				Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 22/01/2008
*/
function dbSaveScan(pName, sName, translated, totalFiles, totalSize, scanDate, scanTime, rootDir, scannedBy) {
	
	var url =	'../utils/httpRequests.php';
	var act =	'savePIIScan';
	var par =	'&project=' + pName +
				'&scan=' + sName +
				'&translated=' + translated +
				'&totalFiles=' + totalFiles +
				'&totalSize=' + totalSize +
				'&scanDate=' + scanDate +
				'&scanTime=' + scanTime +
				'&rootDir=' + rootDir +
				'&scannedBy=' + scannedBy;
	
	return syncXmlHttpRequest(url, act, par);
}
