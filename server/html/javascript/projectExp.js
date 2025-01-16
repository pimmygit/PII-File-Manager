/* 
** Description:	Contains functions for project manipulations
**				
** @package:	Configuration
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	25/09/2007
*/

/*
* Name: testBrowser
* Desc: Performs various tests for browser compatibility
*		before executing any of the scripts.
* Inpt:	none
* Outp: none
* Date: 30.10.2007
*/
function testBrowser()
{
	optionTest = true;
	document.getElementById('dropMenuUsers').options[0] = new Option("Can't handle users.", "users_error");
	lastIndex = document.getElementById('dropMenuUsers').options.length - 1;
	document.getElementById('dropMenuUsers').options[lastIndex] = null;
	if (document.getElementById('dropMenuUsers').options[lastIndex]) {
		optionTest = false;
		alert("This browser has incompatibilities!!!");
	}
}

/*
* Name: clearFields
* Desc: Resets values in all fields to null.
* Inpt:	none
* Outp: none
* Date: 16.11.2007
*/
function clearFields() {
	document.getElementById('projectNameLabel').innerHTML = 'Project properties';
	document.getElementById('ccLocation').value = '';
	document.getElementById('ccActivity').value = '';
	document.getElementById('ccView').value = '';
	document.getElementById('ftpServer').value = '';
	document.getElementById('ftpUser').value = '';
	document.getElementById('ftpPass').value = '';
}

/*
* Name: createNewProject
* Desc: Creates new project
* Inpt:	none
* Outp: none
* Date: 25.10.2007
*/
function createNewProject() {
	
	var projectName = prompt("Please enter name for the new project");
	
	if ( !projectName ) {
		return false;
	}
	
	if ( projectExist(projectName) ) {
		alert("Project with the same name already exist!");
		return false;
	}
	
	var managerMail = prompt("Enter the IBM E-mail of the person\nwho will administer this project:");
	
	if ( !managerMail ) {
		alert("Project creation cancelled.");
		return false;
	}
	
	var managerName = '';
	
	while (managerMail) {
		// Check if the result is an error code (Example: -1)
		
		managerName = isIBMer(managerMail);
		
		if ( managerName.length > 2 ) {
			break;
		} else {
			
			managerMail = prompt("No such user in IBM Bluepages!\n\nPlease enter valid IBM E-mail address\nof the person who will administer this project.");
			
			if ( !managerMail ) {
				alert("Project creation cancelled.");
				return false;
			}
		}
	}
	
	switch ( dbAddProject(projectName, managerMail, managerName) ) {
		
		case '-7': alert("Unknown server error.\nPlease contact the administrator."); break;
		case '-6': alert("Bad XML HTTP Request.\nPlease contact the administrator."); break;
		case '-5': alert("Internal server error.\nPlease contact the administrator."); break;
		case '-4': alert("Project with the same name already exist in the Database!"); break;
		case '-3':
		case '-2':
		case '-1':
		case  '0': alert("Server error.\nPlease contact the administrator."); break;
		case  '1': 
			projData.removeAll();
			dbGetProjects(managerMail);
			populateProjects();
			break;
		default  : alert("Unknown response from server.\nPlease contact the administrator."); break;
	}
}

/*
* Name: createNewUser
* Desc: Adds new user to the selected project
* Inpt:	none
* Outp: none
* Date: 25.10.2007
*/
function createNewUser() {
	
	var selProjects = document.getElementById("dropMenuProjects");
	var selProjectIndex = selProjects.selectedIndex;
	if ( selProjectIndex < 0 ) {
		alert("Please choose a project first.");
		return false;
	}
	var projectName = selProjects.options[selProjectIndex].value;
	var newUsermail = prompt("You are adding new user to project '" + projectName + "'.\n\nPlease enter the IBM E-mail of the new user.");

	if ( !newUsermail ) {
		return false;
	}

	if ( userExist(newUsermail) ) {
		alert("User with this E-mail address already exist!");
		return false;
	}

	var newUsername = '';
	
	while (newUsermail) {
		// Check if the result is an error code (Example: -1)
		
		newUsername = isIBMer(newUsermail);
		
		if ( newUsername.length > 2 ) {
			break;
		} else {
			
			newUsermail = prompt("No such user in IBM Bluepages!\n\nPlease enter valid IBM E-mail address.");
			
			if ( !newUsermail ) {
				return false;
			}
		}
	}
	
	switch ( dbAddUser(projectName, newUsermail, newUsername) ) {
		
		case '-7': alert("Unknown server error.\nPlease contact the administrator."); break;
		case '-6': alert("Bad XML HTTP Request.\nPlease contact the administrator."); break;
		case '-5': alert("Internal server error.\nPlease contact the administrator."); break;
		case '-4': alert("User with the same name already exist in the Database!"); break;
		case '-3':
		case '-2':
		case '-1':
		case  '0': alert("Server error.\nPlease contact the administrator."); break;
		case  '1':
			// User is added OK, so we set this project to be his default one.
			dbSetCurrProj(newUsermail, projectName);
			// Refresh the GUI
			userData.removeAll();
			dbGetUsers(projectName);
			populateUsers(projectName);
			break;
		default  : alert("Unknown response from server.\nPlease contact the administrator."); break;
	}
}

/*
* Name: deleteProject
* Desc: Deletes the selected project and all of its users and preferences
* Inpt:	managerMail	-> Type: String,	Value: E-mail of the currently logged user (Who is supposed to be a manager)
* Outp: none
* Date: 25.10.2007
*/
function deleteProject(managerMail) {
	
	var selProjects = document.getElementById("dropMenuProjects");
	var selProjectIndex = selProjects.selectedIndex;
	if ( selProjectIndex < 0 ) {
		alert("Please choose a project first.");
		return false;
	}
	var projectName = selProjects.options[selProjectIndex].value;
	
	if ( confirm("Are you sure you want to remove project '" + projectName + "'\nwith all of its settings and users?") ) {
		
		switch ( dbRemoveProj(projectName) ) {
			
			case '-7': alert("Unknown server error.\nPlease contact the administrator."); break;
			case '-6': alert("Bad XML HTTP Request.\nPlease contact the administrator."); break;
			case '-5': alert("Some or all project settings failed to be removed."); break;
			case '-4': alert("No such project exist in the Database!"); break;
			case '-3':
			case '-2':
			case '-1':
			case  '0': alert("Server error.\nPlease contact the administrator."); break;
			case  '1':
				projData.removeAll();
				dbGetProjects(managerMail);
				populateProjects();
				break;
			default  : alert("Unknown response from server.\nPlease contact the administrator."); break;
		}
	}
}

/*
* Name: deleteUser
* Desc: Deletes the user and its preferences from the project
* Inpt:	none
* Outp: none
* Date: 25.10.2007
*/
function deleteUser() {
	
	var selProjects = document.getElementById("dropMenuProjects");
	var selProjectIndex = selProjects.selectedIndex;
	if ( selProjectIndex < 0 ) {
		alert("Please choose a project first.");
		return false;
	}
	var projectName = selProjects.options[selProjectIndex].value;

	var selUsers = document.getElementById("dropMenuUsers");
	var selUserIndex = selUsers.selectedIndex;
	if ( selUserIndex < 0 ) {
		alert("Please select user to delete.");
		return false;
	}
	var usrName = selUsers.options[selUserIndex].value;

	if ( confirm("Are you sure you want to remove user '" + usrName + "'\nfrom project '" + projectName + "'?\n") ) {

		switch ( dbRemoveUser(projectName, usrName) ) {
			
			case '-7': alert("Unknown server error.\nPlease contact the administrator."); break;
			case '-6': alert("Bad XML HTTP Request.\nPlease contact the administrator."); break;
			case '-5': alert("Internal server error.\nPlease contact the administrator."); break;
			case '-4': alert("No such user exist in the Database!"); break;
			case '-3':
			case '-2':
			case '-1':
			case  '0': alert("Server error.\nPlease contact the administrator."); break;
			case  '1':
				// User is removed OK. Check if this user exist for any oter project and if not,
				// delete it from the preferences table
				if ( !dbUserExist(usrName) ) {
					dbRemFromPref(usrName);
				}
				
				// Refresh the GUI
				userData.removeAll();
				dbGetUsers(projectName);
				populateUsers(projectName);
				break;
			default  : alert("Unknown response from server.\nPlease contact the administrator."); break;
		}
	}
}

/*
* Name: addRule
* Desc: Adds file renaming rule to the database
* Inpt:	renFrom	-> Type: String,	Value: Rename from
*		renTo	-> Type: String,	Value: Rename to
* Outp: none
* Date: 13.11.2007
*/
function addRule() {
	
	// Get the table with rules
	var renTable = document.getElementById("renameTable");
	var rulesList = document.getElementsByName("ruleList");

	// Get the project we want to modify
	var selProjects = document.getElementById("dropMenuProjects");
	var selProjectIndex = selProjects.selectedIndex;
	if ( selProjectIndex < 0 ) {
		alert("Please choose a project first.");
		return false;
	}
	var projectName = selProjects.options[selProjectIndex].value;
	
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
	
	if ( !dbAddRule(projectName, renFrom, renTo) ) {
		return false;
	}
	
	// Redraw the table
	dbGetRenamingRules(projectName)
	return true;
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
	var renTable = document.getElementById("renameTable");
	var rulesList = document.getElementsByName("ruleList");

	// Get the project we want to modify
	var selProjects = document.getElementById("dropMenuProjects");
	var selProjectIndex = selProjects.selectedIndex;
	if ( selProjectIndex < 0 ) {
		alert("Please choose a project first.");
		return false;
	}
	var projectName = selProjects.options[selProjectIndex].value;
	
	for (var i=0; i<rulesList.length; i++) {
		
		var origFrom = renTable.rows[i].cells[1].firstChild.nodeValue;
		var origTo = renTable.rows[i].cells[2].firstChild.nodeValue;
		
		// Compare to both fields in order to avoid os renaming overlapping
		if ( renTable.rows[i].cells[0].firstChild.checked ) {
			dbDelRule(projectName, origFrom, origTo);
		}
	}
	// Redraw the table
	dbGetRenamingRules(projectName);
}

/*
* Name: editRule
* Desc: Changes rule for the selected project.
* Inpt:	colName	-> Type: String,	Value: name of the column to modify
*		oldVal	-> Type: String,	Value: Old value of the rule
* Outp: none
* Date: 15.11.2007
*/
function editRule(colName, oldRuleValue) {
	
	// Get the project we want to modify
	var selProjects = document.getElementById("dropMenuProjects");
	var selProjectIndex = selProjects.selectedIndex;
	if ( selProjectIndex < 0 ) {
		alert("MISBEHAVIOR: Please report it to the administrator.");
		return false;
	}
	var projectName = selProjects.options[selProjectIndex].value;

	var newRuleValue = prompt("Enter the new value for this rule:", oldRuleValue);
	if ( !newRuleValue ) {
		return false;
	}
	
	// Get the table with rules to verify if this rule already exist
	var renTable = document.getElementById("renameTable");
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
	if ( dbUpdateRule(projectName, colName, oldRuleValue, newRuleValue) ) {
	
		// Redraw the table
		dbGetRenamingRules(projectName);
	}
}

/*
* Name: projectExist
* Desc: Checks if project with the specified name already
*		exists in the database.
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 25.10.2007
*/
function projectExist(pName) {
	
	// Get the drop-down list with projects
	var dropListProj = document.getElementById('dropMenuProjects');
	
	// Go through the projects
	for ( var i=0; i<dropListProj.options.length; i++ ) {
		if ( dropListProj.options[i].value == pName) {
			return true;
		}
	}
	return false;
}

/*
* Name: userExist
* Desc: Checks if user with the specified name already exist.
* Inpt:	newUser	-> Type: String,	Value: IBM E-mail address
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 25.10.2007
*/
function userExist(newUser) {
	
	// Get the drop-down list with users
	var dropListUsers = document.getElementById('dropMenuUsers');
	
	// Go through the projects
	for ( var i=0; i<dropListUsers.options.length; i++ ) {
		if ( dropListUsers.options[i].value == newUser) {
			return true;
		}
	}
	return false;
}

/*
* Name: isIBMer
* Desc: Checks if user with the specified E-mail address
*		exists in the IBM bluepages.
* Inpt:	uMail	-> Type: String,	Value: name of project
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 08.11.2007
*/
function isIBMer(uMail) {
	
	var url = '../utils/httpRequests.php';
	var act = 'isIBMer';
	var par = '&mail=' + uMail;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: populateProjects
* Desc: Populates project list
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: none
* Date: 29.10.2007
*/
function populateProjects() {
	
	// Get the drop-down list we want to modify
	var dropListProj = document.getElementById('dropMenuProjects');
	// Clear list from old data
	dropListProj.options.length = 0;
	// Populate with new data for each user from the users object
	for ( var i=0; i<projData.getSize(); i++ ) {
		dropListProj.options[i] = new Option(projData.getElement(i).getName(), projData.getElement(i).getName());
		dropListProj.options[i].setAttribute('onClick', 'dbLogAction("selectProject"); popuateProps("' + projData.getElement(i).getName() + '")');
	}
	// Clear the user list, the list with privileges and the language list
	// because nothing is selected after this list is populated
	document.getElementById('dropMenuUsers').options.length = 0;

	while (document.getElementById('dropMenuPrivileges').rows.length > 0) {
		document.getElementById('dropMenuPrivileges').deleteRow(0);
	}
	if (document.getElementById('dropMenuLang')) {
		document.getElementById('dropMenuLang').options.length = 0;
	}
}

/*
* Name: populateUsers
* Desc: Populates users list
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: none
* Date: 29.10.2007
*/
function populateUsers(pName) {
	
	// Clear users object before adding the users for the selected project
	userData.removeAll();
	// First clean the list of old values
	rulesList = new Object();
	while (document.getElementById('renameTable').rows.length > 0) {
		document.getElementById('renameTable').deleteRow(0);
	}
	
	// Fetch users and their preferences for the selected project (initialized in dataStore.js)
	dbGetUsers(pName);
	// Get File Renaming Rules list (initialized in dataStore.js)
	dbGetRenamingRules(pName);
	
	// Get the drop-down list we want to modify
	var dropListUser = document.getElementById('dropMenuUsers');
	// Clear list from old data
	dropListUser.options.length = 0;

	// Populate with new data for each user from the users object
	for ( var i=0; i<userData.getSize(); i++ ) {
		dropListUser.options[i] = new Option(userData.getElement(i)[1], userData.getElement(i)[0]);
		dropListUser.options[i].setAttribute('onClick', 'dbLogAction("selectUser"); populatePrivileges("' + userData.getElement(i) + '");');
	}
	// Clear the list with privileges because no user
	// is selected after the user list is populated
	while (document.getElementById('dropMenuPrivileges').rows.length > 0) {
		document.getElementById('dropMenuPrivileges').deleteRow(0);
	}
	// Clear the list with languages because no user
	// is selected after the user list is populated
	if (document.getElementById('dropMenuLang')) {
		document.getElementById('dropMenuLang').options.length = 0;
	}
}

/*
* Name: populatePrivileges
* Desc: Populates privileges for the specified user
* Inpt:	uProps	-> Type: String,	Value: Users properties in CVS
* Outp: none
* Date: 30.10.2007
*/
function populatePrivileges(uProps) {
	//alert(uProps);
	var _userProps = uProps.split(',');
	var tblPrv = document.getElementById('dropMenuPrivileges');
	// Clear list from old data
	while (tblPrv.rows.length > 0) {
		tblPrv.deleteRow(0);
	}
	
	var prvID = 0;
	for ( var i=3; i<_userProps.length; i++ ) {
		
		// Normally we should decrease the counter by 4, as the first privilege is 
		// the fourth user property.
		// But because we want to squeeze the language drop-down menu under the Manager,
		// start counting from 3 instead of 4 and calculate the rowID and the prpID
		
		// ID of the row in the scroll table
		var rowID = i - 3;
		// ID of the user property in the DB
		var prpID = prvID + 4;

		if (rowID == 1) {
		
			// Insert new row to table
			var newRow = tblPrv.insertRow(rowID);
			
			// Create Cell #1
			var cell_1 = newRow.insertCell(0);
			cell_1.setAttribute('colspan', '2');
						
			var element_1 = document.createElement('select');
			element_1.setAttribute('class', 'dropMenu');
			element_1.setAttribute('style', 'width: 98%; margin-top: 1em;');
			element_1.setAttribute('id', 'dropMenuLang');
			
			cell_1.appendChild(element_1);
			continue;
		}
		
		// Insert new row to table
		var newRow = tblPrv.insertRow(rowID);
		
		// Create Cell #1
		var cell_1 = newRow.insertCell(0);
		cell_1.setAttribute('id', rowID + '_1');
		cell_1.setAttribute('class', 'checkBox');
		if (rowID == 0) {cell_1.setAttribute('style', 'border-bottom: 2px solid #dadada;');}
			
		var element_1 = document.createElement('input');
		element_1.setAttribute('type', 'checkbox');
		element_1.setAttribute('id', 'chk_' + _userProps[0] );
		element_1.setAttribute('style', 'cursor: pointer; font-size: 12px;');
		element_1.setAttribute('onclick', 'dbLogAction("selectPrivilege"); return setPrivilege("'+_userProps.join()+'", '+prpID+');'); // - Does not work in IE
		//element_1.onclick = function() { setPrivilege(_userProps.join(), i); };
		
		if (_userProps[prpID] == 1) {
			element_1.checked = true;
		} else {
			element_1.checked = false;
		}
		
		cell_1.appendChild(element_1);

		// Create Cell #2
		var cell_2 = newRow.insertCell(1);
		cell_2.setAttribute('id', rowID + '_2');
		cell_2.setAttribute('align', 'left');
		if (rowID == 0) {cell_2.setAttribute('style', 'border-bottom: 2px solid #dadada;');}		
		var element_2 = document.createTextNode(prvlgList[prvID]);
		
		cell_2.appendChild(element_2);
		
		// If this user is a manager, he has all priivileges assigned
		// and therefore there is no need to populate them
		if (rowID == 0 && _userProps[prpID] == 1) {
			break;
		}
		prvID++;
	}
	
	// The list with privileges is ready, so if the user is not a manager
	// we populate the language drop-down menu
	if (prvID > 2) {
		populateLang(_userProps.join());
	}
}

/*
* Name: populateLang
* Desc: Populates language list and sets the assigned lang to the user
* Inpt:	uMail	-> Type: String,	Value: E-mail of the user
* Outp: none
* Date: 07.11.2007
*/
function populateLang(uProps) {
	
	var _userProps = uProps.split(',');
	// Get the drop-down list we want to modify
	var dropListUser = document.getElementById('dropMenuLang');
	// Clear list from old data
	dropListUser.options.length = 0;
	// Populate with new data for each user from the users object
	var counter = 0;
	for ( var key in langList ) {
		dropListUser.options[counter] = new Option(langList[key], key);
		dropListUser.options[counter].setAttribute('onClick', 'dbLogAction("selectLanguage"); setLang("' + _userProps + '", "' + key + '")');
		if (_userProps[3] == key) {
			dropListUser.options[counter].selected = true;
		}
		counter++;
	}
}

/*
* Name: popuateProps
* Desc: Populates the properties panel
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: none
* Date: 26.10.2007
*/
function popuateProps(pName) {

	// Populate users for this project
	populateUsers(pName);
	// Get the fields we want to modify
	var projName = document.getElementById('projectNameLabel');
	var ccLocation = document.getElementById('ccLocation');
	var ccActivity = document.getElementById('ccActivity');
	var ccView = document.getElementById('ccView');
	var ccCodeReview = document.getElementById('ccCodeReview');
	var ftpServer = document.getElementById('ftpServer');
	var ftpUser = document.getElementById('ftpUser');
	var ftpPass = document.getElementById('ftpPass');
	var renTable = document.getElementById('renTable');
	
	for (var i=0; i<projData.getSize(); i++) {
		
		if (projData.getElement(i).getName() == pName) {
			//projData.getElement(i).showProps();
			projName.innerHTML = "'" + projData.getElement(i).getName() + "' properties";
			ccLocation.value = projData.getElement(i).getCcLocation();
			ccActivity.value = projData.getElement(i).getCcActivity();
			ccView.value = projData.getElement(i).getCcView();
			ccCodeReview.value = projData.getElement(i).getCcCodeReview();
			ftpServer.value = projData.getElement(i).getFtpServer();
			ftpUser.value = projData.getElement(i).getFtpUser();
			ftpPass.value = 'secret';
			break;
		}
	}
}

/*
* Name: popuateRules
* Desc: Populates the renaming rules panel
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: none
* Date: 26.10.2007
*/
function popuateRules() {

	var renTable = document.getElementById('renameTable');
	
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
		//cell_1.ondblclick = function() { editRule('ren_from', key); return false; }; - this does not work
		
		var element_1 = document.createTextNode(key);
		
		cell_1.appendChild(element_1);
		
		// Create Cell #2
		var cell_2 = newRow.insertCell(2);
		cell_2.setAttribute('id', 'ren_' + rowID + '_2');
		cell_2.setAttribute('align', 'left');
		cell_2.setAttribute('class', 'fmtPlainCell');
		cell_2.setAttribute('style', 'width:130px;');
		cell_2.setAttribute('ondblclick', 'dbLogAction("ruleToDblClick"); editRule("ren_to", "' + rulesList[key] + '"); return false;'); // - Does not work in IE
		//cell_2.ondblclick = function() { editRule('ren_to', rulesList[key]); return false; }; - this does not work
								
		var element_2 = document.createTextNode(rulesList[key]);
		
		cell_2.appendChild(element_2);
		
		rowID++;
	}
}

/*
* Name: setPrivilege
* Desc: Sets the privilege in the database
* Inpt:	uProps	-> Type: String,	Value: User properties in CSV
*		propsID	-> Type: String,	Value: Properties ID
* Outp: none
* Date: 26.10.2007
*/
function setPrivilege(uProps, propsID) {
	
	var _userProps = uProps.split(',');
	
	// Change the value in DB
	if (_userProps[propsID] == 1) {
		// We update the GUI only if database update is successfull
		
		if ( dbSetPrvlg(_userProps[2], _userProps[0], propsID, 0) ) {
			_userProps[propsID] = 0;
		} else {
			return false;
		}
	} else {
		// We update the GUI only if database update is successfull
		
		if ( dbSetPrvlg(_userProps[2], _userProps[0], propsID, 1) ) {
			_userProps[propsID] = 1;
		} else {
			return false;
		}
	}

	// Change the value in the stack
	for ( var j=0; j<userData.getSize(); j++ ) {
		
		// Determine which user is to be modified
		if (userData.getElement(j)[0] == _userProps[0]) {
			// Update the stack
			userData.updateData(j, _userProps);
			// This is required to refresh the onClick action for the user
			document.getElementById('dropMenuUsers').options[j].setAttribute('onClick', 'dbLogAction("selectUser"); populatePrivileges("' + _userProps + '");');
			break;
		}
	}
	
	// Refresh the list
	populatePrivileges(_userProps.join());
}

/*
* Name: setLang
* Desc: Sets the privilege in the database
* Inpt:	uProps	-> Type: String,	Value: User properties in CSV
*		propsID	-> Type: String,	Value: Properties ID
* Outp: none
* Date: 07.11.2007
*/
function setLang(uProps, langID) {
	
	var _userProps = uProps.split(',');
	
	if ( dbSetLang(_userProps[2], _userProps[0], langID) ) {
	
		var _userProps = uProps.split(',');
		_userProps[3] = langID;
		// This is required to refresh the onClick action for the user
		document.getElementById('dropMenuUsers').options[document.getElementById('dropMenuUsers').selectedIndex].setAttribute('onClick', 'populatePrivileges("' + _userProps + '"); populateLang("' + _userProps + '");');
		return true;
	} else {
		return false;
	}
}

/*
* Name: changeReviewer
* Desc: Sets a new person to review the checked-in into ClearCase package.
* Inpt:	uMail	-> Type: String,	Value: E-mail of the user logged in
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 01.04.2008
*/
function changeReviewer() {
	
	// Check if a project has been selected
	if ( document.getElementById('projectNameLabel').innerHTML == "Project properties" ) {
		alert("No project has been selected from the list.");
		return false;
	}
	
	// Get the project name
	projName = document.getElementById('projectNameLabel').innerHTML;
	// Get only what is between the quotes
	projName = projName.substring(projName.indexOf("'") + 1, projName.lastIndexOf("'"));
	
	var reviewer = prompt("Please enter the E-mail of the person\nwho will perform the code review.");
	
	if ( !reviewer ) {
		return false;
	}
	
	var newUsername = '';
	
	while (reviewer) {
		// Check if the result is an error code (Example: -1)
		
		newUsername = isIBMer(reviewer);
		
		if ( newUsername.length > 2 ) {
			break;
		} else {
			
			reviewer = prompt("No such user in IBM Bluepages!\n\nPlease enter valid IBM E-mail address.");
			
			if ( !reviewer ) {
				return false;
			}
		}
	}
	
	document.getElementById('ccCodeReview').value = reviewer;
}

/*
* Name: dbGetProjects
* Desc: Prepares the XML HTTP Request to fetch projects for this manager from the DB.
* Inpt:	uMail	-> Type: String,	Value: E-mail of the user logged in
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 02.11.2007
*/
function dbGetProjects(uMail) {

	var url = '../utils/httpRequests.php';
	var act = 'getProjectsForManager';
	var par = '&mail=' + uMail;
	
	xmlHttpRequest(url, act, par);
}

/*
* Name: dbGetUsers
* Desc: Prepares the XML HTTP Request to fetch users and their privileges for this project.
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 27.10.2007
*/
function dbGetUsers(pName) {

	var url = '../utils/httpRequests.php';
	var act = 'getUsers';
	var par = '&project=' + pName;
	
	syncXmlHttpRequest(url, act, par);
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
* Name: dbSetPrvlg
* Desc: Updates users privileges in the database.
* Inpt:	pName	-> Type: String,	Value: name of project
*		uMail	-> Type: String,	Value: users E-mail address
*		propsID	-> Type: String,	Value: Property ID
*		val		-> Type: String,	Value: 0|1
* Outp: 		-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 01.11.2007
*/
function dbSetPrvlg(pName, uMail, propsID, val) {
	
	var url = '../utils/httpRequests.php';
	var act = 'setPrvlg';
	var par = '&project=' + pName + '&mail=' + uMail + '&propsID=' + propsID + '&value=' + val;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbSetLang
* Desc: Updates users privileges in the database.
* Inpt:	pName	-> Type: String,	Value: name of project
*		uMail	-> Type: String,	Value: users E-mail address
*		langID	-> Type: String,	Value: Language ID
* Outp: 		-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 07.11.2007
*/
function dbSetLang(pName, uMail, langID) {
	
	var url = '../utils/httpRequests.php';
	var act = 'setLang';
	var par = '&project=' + pName + '&mail=' + uMail + '&langID=' + langID;
	
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
	
	return xmlHttpRequest(url, act, par);
}

/*
* Name: dbUpdateRule
* Desc: Updates users privileges in the database.
* Inpt:	pName	-> Type: String,	Value: name of project
*		colName	-> Type: String,	Value: users E-mail address
*		oldRule	-> Type: String,	Value: users E-mail address
*		newRule	-> Type: String,	Value: Language ID
* Outp: 		-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 15.11.2007
*/
function dbUpdateRule(pName, colName, oldRule, newRule) {
	
	var url = '../utils/httpRequests.php';
	var act = 'editRule';
	var par = '&project=' + pName + '&columnName=' + colName + '&oldRuleValue=' + oldRule + '&newRuleValue=' + newRule;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbAddProject
* Desc: Prepares HTTP request to add the project to the database.
* Inpt:	pName	-> Type: String,	Value: name of the new project
* Outp: 		-> Type: INT,		Value: PIIFMT return code
* Date: 08.11.2007
*/
function dbAddProject(pName, mMail, mName) {

	var url = '../utils/httpRequests.php';
	var act = 'addProject';
	var par = '&project=' + pName + '&mail=' + mMail + '&name=' + mName;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbAddUser
* Desc: Prepares HTTP request to add the project to the database.
* Inpt:	pName	-> Type: String,	Value: name of the new project
* Outp: 		-> Type: INT,		Value: PIIFMT return code
* Date: 09.11.2007
*/
function dbAddUser(pName, uMail, uName) {

	var url = '../utils/httpRequests.php';
	var act = 'addUser';
	var par = '&project=' + pName + '&mail=' + uMail + '&name=' + uName;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbRemoveProj
* Desc: Prepares HTTP request to remove the project from the database.
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 25.09.2007
*/
function dbRemoveProj(pName) {
	var url = '../utils/httpRequests.php';
	var act = 'delProject';
	var par = '&project=' + pName;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbRemoveUser
* Desc: Prepares HTTP request to remove all users for this project.
* Inpt:	pName	-> Type: String,	Value: name of project
* Outp: 		-> Type: Boolean,	Value: TRUE if exists, FALSE otherwise
* Date: 25.09.2007
*/
function dbRemoveUser(pName, uMail) {
	var url = '../utils/httpRequests.php';
	var act = 'delUser';
	var par = '&project=' + pName + '&mail=' + uMail;
	
	return syncXmlHttpRequest(url, act, par);
}

/*
* Name: dbSaveProject
* Desc: Prepares HTTP request to remove all users for this project.
* Inpt:	none
* Outp: none
* Date: 15.11.2007
*/
function dbSaveProject() {

	// Get the project we want to modify
	var selProjects = document.getElementById("dropMenuProjects");
	var selProjectIndex = selProjects.selectedIndex;
	if ( selProjectIndex < 0 ) {
		alert("Please select a project first.");
		return false;
	}

	var url = '../utils/httpRequests.php';
	var act = 'saveProjProps';
	var par =	'&project=' + selProjects.options[selProjectIndex].value + 
				'&ccLocation=' + document.getElementById('ccLocation').value + 
				'&ccActivity=' + document.getElementById('ccActivity').value + 
				'&ccView=' + document.getElementById('ccView').value + 
				'&ccCodeReview=' + document.getElementById('ccCodeReview').value + 
				'&ftpServer=' + document.getElementById('ftpServer').value + 
				'&ftpUser=' + document.getElementById('ftpUser').value;
	
	// Confirm that the user wants to change the password
	newPass = document.getElementById('ftpPass').value;
	
//	if ( newPass != 'secret' ) {
//		
//		passConfirm = prompt('Confirm password');
//		
//		if (passConfirm) {
//		
//			if ( passConfirm == newPass ) {
//				par = par + '&ftpPass=' + newPass;
//			} else {
//				par = par + '&ftpPass=secret'; // Setting the password to 'secret' will avoid modifying it.
//				alert('Password not updated: Match failed.');
//			}
//		}
//	} else {
//		par = par + '&ftpPass=secret';
//	}
	if ( newPass != 'secret' ) {
		par = par + '&ftpPass=' + newPass;
	} else {
		par = par + '&ftpPass=secret';
	}
	
	if ( !syncXmlHttpRequest(url, act, par) ) {
		alert("Failed to save project properties.\nPlease notify the administrator about this error.");
	}
}

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

/*
* Name: dbSetReviewer
* Desc: Prepares the XML HTTP request for setting the person who will code review.
* Inpt:	projName	-> Type: String, Value: Name of the project
*		userMail	-> Type: String, Value: E-mail of the user
* Outp: none
* Date: 01.04.2008
*/
function dbSetReviewer(projName, userMail) {
	
	var url =	'../utils/httpRequests.php';
	var act =	'setReviewer';
	var par =	'&mail=' + userMail + '&projName=' + projName;
	
	xmlHttpRequest(url, act, par);
}

/*
* Name: dbUserExist
* Desc: Prepares the XML HTTP request for testing if the specified user exist in the DB.
* Inpt:	userMail	-> Type: String, Value: E-mail of the user
* Outp: none
* Date: 11.01.2008
*/
function dbUserExist(userMail) {
	
	var url =	'../utils/httpRequests.php';
	var act =	'userExist';
	var par =	'&mail=' + userMail;
	
	var resp = syncXmlHttpRequest(url, act, par);
	
	if ( resp == -4 ) {
		return true;
	}
	
	return false;
}

/*
* Name: dbRemFromPref
* Desc: Prepares the XML HTTP request for removing of the specified user from the preferences table.
* Inpt:	userMail	-> Type: String, Value: E-mail of the user
* Outp: none
* Date: 18.03.2008
*/
function dbRemFromPref(userMail) {
	var url =	'../utils/httpRequests.php';
	var act =	'remFromPref';
	var par =	'&mail=' + userMail;
	
	xmlHttpRequest(url, act, par);
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
