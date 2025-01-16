/* 
** Description:	Contains functions for configuration of
** Default languages and File Renaming Rules
**				
** @package:	Configuration
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	20/11/2007
*/

/*
* Name: popuateLangs
* Desc: Populates the list with available languages
* Inpt:	none
* Outp: none
* Date: 20.11.2007
*/
function popuateLangs(langList) {

	var langTable = document.getElementById('langTable');
	
	// First clean the table
	while (langTable.rows.length > 0) {
		langTable.deleteRow(0);
	}

	// Populate with new data for each file renaming rule from the object
	var rowID = 0;
	for ( var key in langList ) {
		
		//alert("Adding data: langList[" + key + "] = " + langList[key]);
		 
		// Insert new row to table
		var newRow = langTable.insertRow(rowID);
		
		// Create Cell #0
		var cell_0 = newRow.insertCell(0);
		cell_0.setAttribute('id', rowID + '_0');
		cell_0.setAttribute('class', 'fmtPlainCheckbox');
			
		var element_0 = document.createElement('input');
		element_0.setAttribute('type', 'checkbox');
		element_0.setAttribute('name', 'langList' );
		element_0.setAttribute('style', 'cursor:pointer; margin:0; padding:0;');
		
		cell_0.appendChild(element_0);

		// Create Cell #1
		var cell_1 = newRow.insertCell(1);
		cell_1.setAttribute('id', 'ren_' + rowID + '_1');
		cell_1.setAttribute('align', 'left');
		cell_1.setAttribute('class', 'fmtPlainCell');
		cell_1.setAttribute('style', 'width:60px;');
		
		var element_1 = document.createTextNode(key);
		
		cell_1.appendChild(element_1);
		
		// Create Cell #2
		var cell_2 = newRow.insertCell(2);
		cell_2.setAttribute('id', 'ren_' + rowID + '_2');
		cell_2.setAttribute('align', 'left');
		cell_2.setAttribute('class', 'fmtPlainCell');
		cell_2.setAttribute('style', 'width:180px;');
								
		var element_2 = document.createTextNode(langList[key]);
		
		cell_2.appendChild(element_2);
		
		rowID++;
	}
}

/*
* Name: popuateRules
* Desc: Populates the list with default file renaming rules
* Inpt:	none
* Outp: none
* Date: 20.11.2007
*/
function popuateRules() {

	var renTable = document.getElementById('defaultRulesTable');
	
	// First clean the table
	while (renTable.rows.length > 0) {
		renTable.deleteRow(0);
	}

	// Populate with new data for each file renaming rule from the object
	var rowID = 0;
	for ( var key in rulesList ) {
		
		// Insert new row to table
		var newRow = renTable.insertRow(rowID);
		
		// Create Cell #0
		var cell_0 = newRow.insertCell(0);
		cell_0.setAttribute('id', rowID + '_0');
		cell_0.setAttribute('class', 'fmtPlainCheckbox');
			
		var element_0 = document.createElement('input');
		element_0.setAttribute('type', 'checkbox');
		element_0.setAttribute('name', 'ruleList' );
		element_0.setAttribute('style', 'cursor:pointer; margin:0; padding:0;');
		
		cell_0.appendChild(element_0);

		// Create Cell #1
		var cell_1 = newRow.insertCell(1);
		cell_1.setAttribute('id', 'ren_' + rowID + '_1');
		cell_1.setAttribute('align', 'left');
		cell_1.setAttribute('class', 'fmtPlainCell');
		cell_1.setAttribute('style', 'width:110px;');
		cell_1.setAttribute('ondblclick', 'dbLogAction("ruleFromDblClick"); editRule("ren_from", "' + key + '"); return false;'); // - Does not work in IE
		//cell_1.ondblclick = function() { editRule('ren_from', key); return false; }; - wont work
		
		var element_1 = document.createTextNode(key);
		
		cell_1.appendChild(element_1);
		
		// Create Cell #2
		var cell_2 = newRow.insertCell(2);
		cell_2.setAttribute('id', 'ren_' + rowID + '_2');
		cell_2.setAttribute('align', 'left');
		cell_2.setAttribute('class', 'fmtPlainCell');
		cell_2.setAttribute('style', 'width:130px;');
		cell_2.setAttribute('ondblclick', 'dbLogAction("ruleToDblClick"); editRule("ren_to", "' + rulesList[key] + '"); return false;'); // - Does not work in IE
		//cell_2.ondblclick = function() { editRule('ren_to', rulesList[key]); return false; }; - wont work 
								
		var element_2 = document.createTextNode(rulesList[key]);
		
		cell_2.appendChild(element_2);
		
		rowID++;
	}
}

/*
* Name: addLang
* Desc: Adds file renaming rule to the database
* Inpt:	none
* Outp: none
* Date: 20.11.2007
*/
function addLang() {
	
	// Get the table with rules
	var langTable = document.getElementById("langTable");
	var langList = document.getElementsByName("langList");

	// Get the users input
	var langCode = prompt("Enter language code:");
	
	if ( !langCode ) {
		return false;
	}
	
	for (var i=0; i<langList.length; i++) {
		
		// Compare to both fields in order to avoid os renaming overlapping
		var origCode = langTable.rows[i].cells[1].firstChild.nodeValue;
		
		if ( langCode == origCode ) {
			alert("Similar language code already exists.");
			return false;
		}
	}
	
	// Get the users input
	var langName = prompt("Enter language name:");
	
	if ( !langName ) {
		return false;
	}
	
	if (dbAddLang(langCode, langName) ) {
		// Redraw the table
		dbGetLanguages('default');
	}
}

/*
* Name: delLang
* Desc: Deletes selected language from the database
* Inpt:	none
* Outp: none
* Date: 20.11.2007
*/
function delLang() {
	
	// Get the table with rules
	var langTable = document.getElementById("langTable");
	var langList = document.getElementsByName("langList");

	for (var i=0; i<langList.length; i++) {
		
		var langCode = langTable.rows[i].cells[1].firstChild.nodeValue;
		
		// Compare to both fields in order to avoid os renaming overlapping
		if ( langTable.rows[i].cells[0].firstChild.checked ) {
			
			if ( dbDelLang(langCode) ) {
				var actionHappened = true;
			}
		}
	}
	
	if (actionHappened) {
		// Redraw the table
		dbGetLanguages('default');
	}
}


/*
* Name: addRule
* Desc: Adds file renaming rule to the database
* Inpt:
* Outp: none
* Date: 13.11.2007
*/
function addRule() {
	
	// Get the table with rules
	var renTable = document.getElementById("defaultRulesTable");
	var rulesList = document.getElementsByName("ruleList");

	// Get the users input
	var renFrom = prompt("Enter search pattern:");
	
	if ( !renFrom ) {
		return false;
	}
	
	for (var i=0; i<rulesList.length; i++) {
		
		// Compare to both fields in order to avoid os renaming overlapping
		var origFrom = renTable.rows[i].cells[1].firstChild.nodeValue;
		var origTo = renTable.rows[i].cells[2].firstChild.nodeValue;
		
		if ( renFrom == origFrom || renFrom == origTo ) {
			alert("Similar rule already exists.");
			return false;
		}
	}
	
	// Get the users input
	var renTo = prompt("Enter replace string:");
	
	if ( !renTo ) {
		return false;
	}
	
	if (dbAddRule('default', renFrom, renTo) ) {
		// Redraw the table
		dbGetRenamingRules('default');
	}
}

/*
* Name: delRule
* Desc: Deletes selected rules from the database
* Inpt:	none
* Outp: none
* Date: 13.11.2007
*/
function delRule() {
	
	// Get the table with rules
	var renTable = document.getElementById("defaultRulesTable");
	var rulesList = document.getElementsByName("ruleList");

	for (var i=0; i<rulesList.length; i++) {
		
		var origFrom = renTable.rows[i].cells[1].firstChild.nodeValue;
		var origTo = renTable.rows[i].cells[2].firstChild.nodeValue;
		
		// Compare to both fields in order to avoid os renaming overlapping
		if ( renTable.rows[i].cells[0].firstChild.checked ) {
			var actionHappened = true;
			dbDelRule('default', origFrom, origTo);
		}
	}

	if (actionHappened) {
		// Redraw the table
		dbGetRenamingRules('default');
	}
}

/*
* Name: editRule
* Desc: Changes rule for the selected project.
* Inpt:	colName	-> Type: String,	Value: name of the column to modify
*		oldVal	-> Type: String,	Value: Old value of the rule
* Outp: none
* Date: 22.11.2007
*/
function editRule(colName, oldRuleValue) {

	var newRuleValue = prompt("Enter the new value for this rule:", oldRuleValue);
	if ( !newRuleValue ) {
		return false;
	}
	
	// Get the table with rules
	var renTable = document.getElementById("defaultRulesTable");
	var rulesList = document.getElementsByName("ruleList");
	
	for (var i=0; i<rulesList.length; i++) {
		
		// Compare to both fields in order to avoid renaming overlapping
		var origFrom = renTable.rows[i].cells[1].firstChild.nodeValue;
		var origTo = renTable.rows[i].cells[2].firstChild.nodeValue;
		
		if ( newRuleValue == origFrom || newRuleValue == origTo ) {
			alert("Error: Similar rule already exists.");
			return false;
		}
	}
	
	// Update the database
	if ( dbUpdateRule('default', colName, oldRuleValue, newRuleValue) ) {
	
		// Redraw the table
		dbGetRenamingRules('default');
	}
}

/*
* Name: dbGetLanguages
* Desc: Prepares the XML HTTP Request to fetch the available languages
* Inpt:	none
* Outp: none
* Date: 07.11.2007
*/
function dbGetLanguages() {

	var url = '../utils/httpRequests.php';
	var act = 'getLangList';
	var par = '';
	
	xmlHttpRequest(url, act, par);
}

/*
* Name: dbGetRenamingRules
* Desc: Prepares the XML HTTP Request to fetch the File Renaming Rules for this project.
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 13.11.2007
*/
function dbGetRenamingRules(pName) {

	var url = '../utils/httpRequests.php';
	var act = 'getRenRules';
	var par = '&project=' + pName;
	
	xmlHttpRequest(url, act, par);
}

/*
* Name: dbAddLang
* Desc: Adds new language to the database.
* Inpt:	langCode	-> Type: String,	Value: name of project
*		langName	-> Type: String,	Value: initial value of the filename
* Outp: 			-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 20.11.2007
*/
function dbAddLang(langCode, langName) {
	
	var url = '../utils/httpRequests.php';
	var act = 'addLang';
	var par = '&langCode=' + langCode + '&langName=' + langName;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbDelLang
* Desc: Removes language from the database.
* Inpt:	langCode	-> Type: String,	Value: name of project
* Outp: none
* Date: 20.11.2007
*/
function dbDelLang(langCode) {
	
	var url = '../utils/httpRequests.php';
	var act = 'delLang';
	var par = '&langCode=' + langCode;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbAddRule
* Desc: Updates users privileges in the database.
* Inpt:	pName	-> Type: String,	Value: name of project
*		renFrom	-> Type: String,	Value: initial value of the filename
*		renTo	-> Type: String,	Value: renamed value
* Outp: 		-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 14.11.2007
*/
function dbAddRule(pName, renFrom, renTo) {
	
	var url = '../utils/httpRequests.php';
	var act = 'addRule';
	var par = '&project=' + pName + '&renFrom=' + renFrom + '&renTo=' + renTo;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbDelRule
* Desc: Updates users privileges in the database.
* Inpt:	pName	-> Type: String,	Value: name of project
*		renFrom	-> Type: String,	Value: initial value of the filename
*		renTo	-> Type: String,	Value: renamed value
* Outp: none
* Date: 14.11.2007
*/
function dbDelRule(pName, renFrom, renTo) {
	
	var url = '../utils/httpRequests.php';
	var act = 'delRule';
	var par = '&project=' + pName + '&renFrom=' + renFrom + '&renTo=' + renTo;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbUpdateRule
* Desc: Updates users privileges in the database.
* Inpt:	projName	-> Type: String,	Value: name of the project
*		colName		-> Type: String,	Value: name of the column
*		oldRule		-> Type: String,	Value: old rule
*		newRule		-> Type: String,	Value: new rule
* Outp: 			-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 15.11.2007
*/
function dbUpdateRule(projName, colName, oldRule, newRule) {
	
	var url = '../utils/httpRequests.php';
	var act = 'editRule';
	var par = '&project=default&columnName=' + colName + '&oldRuleValue=' + oldRule + '&newRuleValue=' + newRule;
	
	return syncXmlHttpRequest(url, act, par);
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
