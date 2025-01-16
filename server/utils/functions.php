<?php
/* 
** Description:	Contains various functions
** @package:	utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	23/11/2007
*/

require_once('../config/constants.php');
require_once('../config/settings.php');
require_once('fileHandler.php');
require_once('logger.php');
require_once('mysql.php');
require_once('../security/LoginLDAP.class.php');
require_once('../sourceControl/ClearCase.class.php');
require_once('../utils/FTP.class.php');

/*
* Name: getDomain
* Desc: Determines the domain name used to store the COOKIES
* Inpt:	none
* Outp: Type: String, Value: domain name
* Date: 23/11/2007
*/
function getDomain() {

	if ( isset($_SERVER['HTTP_HOST']) ) {
		
		// Get domain
		$dom = $_SERVER['HTTP_HOST'];
		
		// Strip www from the domain
		if (strtolower(substr($dom, 0, 4)) == 'www.') { 
			$dom = substr($dom, 4);
		}
		
		// Check if a port is used, and if it is, strip that info
		$uses_port = strpos($dom, ':');
		if ($uses_port) {
			$dom = substr($dom, 0, $uses_port);
		}
		
		// Add period to Domain (to work with or without www and on subdomains)
		$dom = '.' . $dom;
	} else {
		$dom = false;
	}
	
	msg_log(DEBUG, "Server domain name is [".$dom."].", SILENT);
			
	return $dom;  
}

/*
* Name: getProjects
* Desc: Reads from the DB all projects for this user.
* Inpt:	$userMail		-> Type: String,	Value: E-mail of the user
* 		$whereManager	-> Type: Boolean,	Value: If this flag is enabled only projects where the user is a managers will be displayed
* Outp:					-> Type: String,	Value: User Data in SCV
* Date: 26.09.2007
*/
function getProjects( $userMail, $whereManager ) {
	
	global $FMT_ADMIN_LIST;
	
	// Create list of projects for this user
	// If the user is an admin then he has access to all projects
	if (in_array($userMail, $FMT_ADMIN_LIST)) {
		msg_log(DEBUG, "Requesting all projects for admin: [". $userMail ."].", SILENT);
		$sqlResponse = selectData(TB_USER, 'DISTINCT project', '');
	} else if ($whereManager) {
		msg_log(DEBUG, "Requesting projects for manager: [". $userMail ."].", SILENT);
		$sqlResponse = selectData(TB_USER, 'DISTINCT project', 'user_mail = "'.$userMail.'" AND manager = true');
	} else {
		msg_log(DEBUG, "Requesting projects for user: [". $userMail ."].", SILENT);
		$sqlResponse = selectData(TB_USER, 'DISTINCT project', 'user_mail = "'.$userMail.'"');
	}
	
	$projList = '"';
	
	if ($sqlResponse) {
		
		while ($userData = mysqli_fetch_array($sqlResponse, MYSQLI_ASSOC)) {
			$projList = $projList.$userData['project'].'", "';
		}
		
		msg_log(DEBUG, "Found ". substr_count($projList, ',') ." projects.", SILENT);
		$projList = substr($projList, 0, -3);
		
	} else {
		return 'empty';
	}
	
	if (strlen($projList) < 2) {
		msg_log(DEBUG, "Found 0 projects.", SILENT);
		return 'empty';
	}
	
	msg_log(DEBUG, "Fetching project information.", SILENT);
	
	// If this user is a manager of one or more projects:
	$sqlResponse = selectData(TB_PROJ, '*', 'name IN ('.$projList.') ORDER BY name ASC');
	
	$projPropsList = '';
	if ($sqlResponse) {
		
		while ($userData = mysqli_fetch_array($sqlResponse, MYSQLI_ASSOC)) {
			
			foreach ($userData as $data) {
				$projPropsList = $projPropsList.$data.",";
			}
		}
		
		if (strlen($projPropsList) < 6) {
			return 'empty';
		} else {
			return substr($projPropsList, 0, -1);
		}
	}
	
	return 'empty';
}

/*
* Name: getProjectData
* Desc: Reads from the DB all projects for this user.
* Inpt:	$projName		-> Type: String,	Value: Project name
* Outp:					-> Type: String,	Value: Project data in CSV
* Date: 26.09.2007
*/
function getProjectData( $projName ) {
	
	msg_log(DEBUG, "Requesting data for project: [". $projName ."].", SILENT);

	$sqlResponse = selectData(TB_PROJ, '*', 'name = "'.$projName.'"');
	
	$projPropsList = '';
	
	if ($sqlResponse) {
		
		while ($projData = mysqli_fetch_array($sqlResponse, MYSQLI_ASSOC)) {
			
			foreach ($projData as $data) {
				$projPropsList = $projPropsList.$data.",";
			}
		}
		
		if (strlen($projPropsList) < 6) {
			msg_log(WARN, "Inconsistent data for project: [". $projName ."].", SILENT);
			return 'empty';
		} else {
			msg_log(DEBUG, "Received [".substr_count($projPropsList, ',')."] properties for project: [". $projName ."].", SILENT);
			return substr($projPropsList, 0, -1);
		}
	}
	
	msg_log(WARN, "Failed to get result from SQL server.", SILENT);
	
	return 'empty';
}

/*
* Name: saveProjProps
* Desc: Saves project properties in the database
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$ccLocation		-> Type: String,	Value: ClearCase location of the project
*		$ccActivity		-> Type: String,	Value: ClearCase activity
*		$ccView			-> Type: String,	Value: ClearCase view
*		$ftpServer		-> Type: String,	Value: Name of the FTP server
*		$ftpUser		-> Type: String,	Value: Username
*		$ftpPass		-> Type: String,	Value: Password
* Outp:					-> Type: INT,		Value: PIIFMT error code
* Date: 15.11.2007
*/
function saveProjProps( $projectName, $ccLocation, $ccActivity, $ccView, $ccCodeReview, $ftpServer, $ftpUser, $ftpPass ) {
	
	msg_log(DEBUG, "Saving project -> projectName[".$projectName."], ccLocation[".$ccLocation."], ccActivity[".$ccActivity."], ccView[".$ccView."], ftpServer[".$ftpServer."], ftpUser[".$ftpUser."], ftpPass[******].", SILENT);
		
	$condition = "name = '" . $projectName . "'";
	
	// Determine if FTP password needs updating
	if ($ftpPass == 'secret') {
		$colNames = array('cc_location', 'cc_activity', 'cc_view', 'dev_reviewer', 'ftp_server', 'ftp_user');
		$projData = array($ccLocation, $ccActivity, $ccView, $ccCodeReview, $ftpServer, $ftpUser);
	} else {
		$colNames = array('cc_location', 'cc_activity', 'cc_view', 'dev_reviewer', 'ftp_server', 'ftp_user', 'ftp_pass');
		$projData = array($ccLocation, $ccActivity, $ccView, $ccCodeReview, $ftpServer, $ftpUser, $ftpPass);
	}
	
	if ( updateData(TB_PROJ, $colNames, $projData, $condition) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: addProject
* Desc: Crete project in the database
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$userMail		-> Type: String,	Value: E-mail of the user
*		$userName		-> Type: String,	Value: Name of the user
* Outp:					-> Type: INT,	Value: PIIFMT error code
* Date: 08.11.2007
*/
function addProject( $projName, $userMail, $userName ) {
	
	msg_log(DEBUG, "Creating project -> projectName[".$projName."], userMail[".$userMail."], userName[".$userName."].", SILENT);
	
	$condition = "name = '" . $projName . "'";
	
	$sqlResult = selectData(TB_PROJ, 'name', $condition);
	
	if ( $sqlResult ) {
		if ( mysqli_num_rows($sqlResult) > 0 ) {
			msg_log(WARN, "Project [".$projName."] already exists.", SILENT);
			return ALREADY_EXIST;
		}
	}

	// Add the project to the database
	$colNames = array('name');
	$projData = array($projName);
	msg_log(DEBUG, "Adding project [".$projName."] to db[".DB_NAME.".".TB_PROJ."].", SILENT);
	if ( insertData(TB_PROJ, $colNames, $projData) ) {
		
		// Add user to be the manager of the project
		$colNames = array('user_mail', 'user_name', 'project', 'manager');
		$projData = array($userMail, $userName, $projName, 1);
		msg_log(DEBUG, "Setting manager [".$userName."] for project [".$projName."].", SILENT);		
		if ( insertData(TB_USER, $colNames, $projData) ) {
			
			// Add the default renaming rules to the project
			msg_log(DEBUG, "Setting default renaming rules for project [".$projName."].", SILENT);		
			$colNames = array('ren_from', 'ren_to');
			$condition = "project = 'default'";
			$sqlResult = selectData(TB_RULE, $colNames, $condition);
			
			if ( $sqlResult ) {
				
				while ( $ruleData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC) ) {

					//error_log( "Rule: [". $projName ."][". $ruleData['ren_from'] ."][". $ruleData['ren_to'] ."]");
					$colNames = array('project', 'ren_from', 'ren_to');
					$projData = array($projName, $ruleData['ren_from'], $ruleData['ren_to']);
					
					if ( !insertData(TB_RULE, $colNames, $projData) ) {
						return SQL_FAILED;
					}
				}
			}
			
			// If this user has no current project set, then set this project to be his current one
			if (getCurrProj($userMail) == 'empty') {
				setCurrProj( $userMail, $projName );
			}
			
			return COMMAND_OK;
			
		} else {
			// Try to clean the project table
			$condition = "name = '" . $projName . "'";
			removeData(TB_PROJ, $condition);
			return SQL_FAILED;
		}
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: addUser
* Desc: Add user to a project
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$userMail		-> Type: String,	Value: E-mail of the user
*		$userName		-> Type: String,	Value: Name of the user
* Outp:					-> Type: INT,		Value: PIIFMT error code
* Date: 09.11.2007
*/
function addUser( $projName, $userMail, $userName ) {
	
	msg_log(DEBUG, "Adding user [".$userMail."] to project [".$projName."].", SILENT);
	
	// First check if user with the same e-mail and for the same project
	// is not already in the DB
	$condition = "user_mail = '" . $userMail . "' AND project = '" . $projName . "'";
	
	$sqlResult = selectData(TB_USER, 'user_mail', $condition);
	
	if ( $sqlResult ) {
		if ( mysqli_num_rows($sqlResult) > 0 ) {
			msg_log(WARN, "User [.$userMail.] already exists in project [.$projName.].", SILENT);
			return ALREADY_EXIST;
		}
	}

	// Add user
	$colNames = array('user_mail', 'user_name', 'project');
	$projData = array($userMail, $userName, $projName);
	msg_log(DEBUG, "Adding user [".$userMail."] to project [.$projName.].", SILENT);
	if ( insertData(TB_USER, $colNames, $projData) ) {
		
		// If this user has no current project set, then set this project to be his current one
		if (getCurrProj($userMail) == 'empty') {
			setCurrProj( $userMail, $projName );
		}
			
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: delProject
* Desc: Removes project and its properties from the database
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$userMail		-> Type: String,	Value: E-mail of the user
* Outp:					-> Type: INT,		Value: PIIFMT error code
* Date: 09.11.2007
*/
function delProject( $projName ) {
	
	msg_log(DEBUG, "Removing project [".$projName."].", SILENT);
	
	// First try to remove the project from the database
	// If this succeeds, then we remove whatever we can
	$condition = "name = '" . $projName . "'";
	if ( !removeData(TB_PROJ, $condition) ) {
		return COMMAND_FAIL;
	}
	
	// Remove the users
	$condition = "project = '" . $projName . "'";
	if ( !removeData(TB_USER, $condition) ) {
		return COMMAND_FAIL;
	}
	
	// Remove renaming rules
	$condition = "project = '" . $projName . "'";
	$condition = "project = '" . $projName . "'";
	if ( !removeData(TB_RULE, $condition) ) {
		return COMMAND_FAIL;
	}
	return COMMAND_OK;
}	

/*
* Name: delUser
* Desc: Remove user from project
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$userMail		-> Type: String,	Value: E-mail of the user
* Outp:					-> Type: INT,		Value: PIIFMT error code
* Date: 09.11.2007
*/
function delUser( $projName, $userMail ) {
	
	msg_log(DEBUG, "Removing user [".$userMail."] from project [".$projName."].", SILENT);

	// First check if user with this e-mail exist in the DB
	$condition = "user_mail = '" . $userMail . "' AND project = '" . $projName . "'";
	
	$sqlResult = selectData(TB_USER, 'user_mail', $condition);
	
	if ( $sqlResult ) {
		if ( mysqli_num_rows($sqlResult) < 1 ) {
			return DOES_NOT_EXIST;
		}
	}
	
	if ( removeData(TB_USER, $condition) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: remFromPref
* Desc: Remove user from project
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$userMail		-> Type: String,	Value: E-mail of the user
* Outp:					-> Type: INT,		Value: PIIFMT error code
* Date: 18.03.2008
*/
function remFromPref( $userMail ) {
	
	msg_log(DEBUG, "Removing user [".$userMail."] from the preferences table.", SILENT);

	$condition = "mail = '" . $userMail . "'";
	
	if ( removeData(TB_PREF, $condition) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}

/*
* Name: getUsers
* Desc: Reads from the DB all users for this project.
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
* Outp:					-> Type: String,	Value: User Data in SCV (first element is rownums, second is colnums. Rest is data), [empty] if there are no users.
* Date: 26.09.2007
*/
function getUsers( $projectName ) {
	
	msg_log(DEBUG, "Retrieveing users for project [".$projectName."].", SILENT);

	// Determine exactly which project should be used
	$condition = "project = '".$projectName."' ORDER BY user_name ASC";
	
	// Check the database for duplicate entry
	$sqlResult = selectData(TB_USER, '*', $condition);
	
	if ($sqlResult) {
		
		// First item from the list should be the number of returned rows from the DB
		$userDataList = mysqli_num_rows($sqlResult).',';
		// Second item from the list should be the number of returned columns per user
		$userDataList = $userDataList.mysqli_num_fields($sqlResult).',';
		
		while ($userData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC)) {
		
			foreach ($userData as $data) {
				$userDataList = $userDataList.$data.",";
			}
		}
		
		if (strlen($userDataList) < 5) {
			msg_log(WARN, "Found 0 users.", SILENT);
			return 'empty';
		} else {
			$numUsers = (substr_count($userDataList, ',') - 2) / 18; // We select 18 fields from the database and the first two items are not users
			msg_log(DEBUG, "Found ". $numUsers ." users.", SILENT);
			return substr($userDataList, 0, -1);
		}
	}
}	

/*
* Name: getUserData
* Desc: Reads from the DB all users for this project.
* Inpt:	$projName	-> Type: String,	Value: Name of the project
* 		$userMail	-> Type: String,	Value: E-mail of the user
* Outp:				-> Type: String,	Value: Associative array of user data
* Date: 12.03.2008
*/
function getUserData( $projName, $userMail ) {
	
	msg_log(DEBUG, "Retrieveing data for user [".$userMail."] for project [".$projName."].", SILENT);
	
	$condition = "project = '".$projName."' AND user_mail = '".$userMail."'";
	
	$sqlResult = selectData(TB_USER, '*', $condition);
	
	if ($sqlResult) {
		return mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);
	} else {
		return false;
	}
}	

/*
* Name: getLangList
* Desc: Reads all languages from the DB.
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
* Outp:	Type: String,	Value: User Data in CSV.
* Date: 07.11.2007
*/
function getLangList() {
	
	msg_log(DEBUG, "Retrieveing language list.", SILENT);

	// Dummy condition to add the ordering
	$condition = "1 ORDER BY lang_name ASC";
	
	$sqlResult = selectData(TB_LANG, '*', $condition);
	
	if ($sqlResult) {
		
		$langList = '';
		
		while ($langData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC)) {
		
			foreach ($langData as $data) {
				$langList = $langList.$data.",";
			}
		}
		
		if (strlen($langList) < 2) {
			msg_log(WARN, "Found 0 languages.", SILENT);
			return 'empty';
		} else {
			$numLangs = substr_count($langList, ',') / 2; // Divide by two as there are lang codes and lang names
			msg_log(DEBUG, "Found ". $numLangs ." languages.", SILENT);
			return substr($langList, 0, -1);
		}
	}
}	

/*
* Name: getRenRules
* Desc: Reads all languages from the DB. (CSV required for AJAX)
* Inpt:	$projectName	->	Type: String,	Value: Name of the project
* Outp:						Type: String,	Value: Rules in CSV.
* Date: 13.11.2007
*/
function getRenRules($projName) {
	
	msg_log(DEBUG, "Retrieveing list of renaming rules.", SILENT);

	$condition = "project = '".$projName."' ORDER BY ren_from ASC";
	
	// Check the database for duplicate entry
	$sqlResult = selectData(TB_RULE, 'ren_from, ren_to', $condition);
	
	if ($sqlResult) {
		
		$rulesList = '';
		
		while ($langData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC)) {
		
			foreach ($langData as $data) {
				$rulesList = $rulesList.$data.",";
			}
		}
		
		if (strlen($rulesList) < 2) {
			msg_log(WARN, "Found 0 rules.", SILENT);
			return 'empty';
		} else {
			$num_rules = substr_count($rulesList, ',') / 2; // Divide by to as we fetch(count) two fileds
			msg_log(DEBUG, "Found ". $num_rules ." renaming rules.", SILENT);
			return substr($rulesList, 0, -1);
		}
	}
}

/*
* Name: editRenRules
* Desc: Updates the requested renaming rule.
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$colName		-> Type: String,	Value: Name of the DB column to update
*		$oldRule		-> Type: String,	Value: Old value
*		$newRule		-> Type: String,	Value: New value
* Outp:	Type: String,	Value: Rules in CSV.
* Date: 14.11.2007
*/
function editRenRules($projName, $colName, $oldRule, $newRule) {
	
	msg_log(DEBUG, "Renaming rule [".$oldRule."] to [".$newRule."] for project [".$projName."].", SILENT);
	
	// Determine which rule to edit
	$condition = "project = '".$projName."' AND ".$colName." = '".$oldRule."'";
	
	// Update the database
	return updateData(TB_RULE, $colName, $newRule, $condition);
}

/*
* Name: setPrvlg
* Desc: Updates the privilege for the user.
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$userMail		-> Type: String,	Value: Users E-mail address
*		$propsID		-> Type: String,	Value: ID of the property
*		$prvlgVal		-> Type: String,	Value: TRUE|FALSE
* Outp:					-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 01.11.2007
*/
function setPrvlg( $projectName, $userMail, $propsID, $prvlgVal ) {
	
	msg_log(DEBUG, "Setting privilege ID[".$propsID."], value[".$prvlgVal."] to user[".$userMail."] for project [".$projectName."].", SILENT);

	// Determine exactly which project should be used
	$condition = "project = '".$projectName."' AND user_mail = '".$userMail."'";
	
	switch ($propsID) {
		case 0:
		case 1:
		case 2:
		case 3:
			return false;
		case 4:
			$colName = 'manager';
			break;
		default:
			$prvlgID = $propsID - 4;
			if ($prvlgID < 10) {
				$colName = 'prvlg_0' . $prvlgID;
			} else {
				$colName = 'prvlg_' . $prvlgID;
			}
			break;
	}
	
	// Update the database
	return updateData(TB_USER, $colName, $prvlgVal, $condition);
}

/*
* @desc		Retrieves the privileges for a given user and project
* @author	Kliment Stefanov
* @version	1.0, 07.05.2008
* 
* @param	string	$projName	Connection object
* @param	string	$userMail	Set of commands to execute
* 
* @return	array				Associative array of privileges
*/
function getPrvlg($projName, $userMail) {
	
	msg_log(DEBUG, "Retrieveing privileges for user [".$userMail."] for project [".$projName."].", SILENT);
	
	$userData = getUserData( $projName, $userMail );
	
	if (!$userData || count($userData) < 18) { // This is the number of columns in the DB for a user
		msg_log(ERROR, "Failed to get privileges for user [".$userMail."] for project [".$projName."].", SILENT);
		return false;
	}
	
	return array_slice($userData, 4);
}

/*
* Name: setLang
* Desc: Updates the privilege for the user.
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$userMail		-> Type: String,	Value: Users E-mail address
*		$propsID		-> Type: String,	Value: ID of the property
*		$prvlgVal		-> Type: String,	Value: TRUE|FALSE
* Outp:					-> Type: Boolean,	Value: TRUE on success, FALSE otherwise
* Date: 07.11.2007
*/
function setLang( $projectName, $userMail, $langID ) {
	
	msg_log(DEBUG, "Setting language [".$langID."] to user[".$userMail."] for project [".$projectName."].", SILENT);

	// Determine exactly which project should be used
	$condition = "project = '".$projectName."' AND user_mail = '".$userMail."'";
	
	// Update the database
	return updateData(TB_USER, 'lang', $langID, $condition);
}

/*
* Name: addLang
* Desc: Crete project in the database
* Inpt:	$langCode	-> Type: String,	Value: Language code
*		$langName	-> Type: String,	Value: Language name
* Outp:				-> Type: INT,		Value: PIIFMT error code
* Date: 20.11.2007
*/
function addLang( $langCode, $langName ) {
	
	msg_log(DEBUG, "Creating language [".$langName."] with code [".$langCode."].", SILENT);

	// Add rule to the database
	$colNames = array('lang_code', 'lang_name');
	$rowData = array($langCode, $langName);
	
	if ( insertData(TB_LANG, $colNames, $rowData) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: delLang
* Desc: Crete project in the database
* Inpt:	$langCode	-> Type: String,	Value: Language code
* Outp:				-> Type: INT,		Value: PIIFMT error code
* Date: 20.11.2007
*/
function delLang( $langCode ) {
	
	msg_log(DEBUG, "Removing language with code [".$langCode."].", SILENT);

	$condition = "lang_code = '".$langCode."'";
	
	if ( removeData(TB_LANG, $condition) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: addRule
* Desc: Crete project in the database
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$userMail		-> Type: String,	Value: E-mail of the user
*		$userName		-> Type: String,	Value: Name of the user
* Outp:					-> Type: INT,		Value: PIIFMT error code
* Date: 14.11.2007
*/
function addRule( $projName, $renFrom, $renTo ) {
	
	msg_log(DEBUG, "Creating renaming rule for project [".$projName."]-> from[".$renFrom."], to[".$renTo."].", SILENT);

	// Add rule to the database
	$colNames = array('project', 'ren_from', 'ren_to');
	$rowData = array($projName, $renFrom, $renTo);
	
	if ( insertData(TB_RULE, $colNames, $rowData) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: delRule
* Desc: Crete project in the database
* Inpt:	$projectName	-> Type: String,	Value: Name of the project
*		$renFrom		-> Type: String,	Value: From value
*		$renTo			-> Type: String,	Value: To value
* Outp:					-> Type: INT,		Value: PIIFMT error code
* Date: 14.11.2007
*/
function delRule( $projName, $renFrom, $renTo ) {
	
	msg_log(DEBUG, "Removing renaming rule for project [".$projName."]-> from[".$renFrom."], to[".$renTo."].", SILENT);

	$condition = "project = '".$projName."' AND ren_from = '".$renFrom."' AND ren_to = '".$renTo."'";
	
	if ( removeData(TB_RULE, $condition) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: isIBMer
* Desc: Checks if the user exist in IBM bluepages
* Inpt:	$userMail	-> Type: String,	Value: E-mail of the user
* Outp:				-> Type: String,	Value: Full name of the user if exists
* Date: 08.11.2007
*/
function isIBMer( $userMail ) {
	
	msg_log(DEBUG, "Checking if user [".$userMail."] exist in IBM Bluepages.", SILENT);

	$user = new LoginLDAP();
	
	if ( $user->isValidUser($userMail) ) {
		return $user->getUserName();
	} else {
		return DOES_NOT_EXIST;
	}
}

/*
* Name: userExist
* Desc: Checks if the user exist in the users table
* Inpt:	$userMail	-> Type: String,	Value: E-mail of the user
* Outp:				-> Type: Boolean,	Value: FMT error code
* Date: 18.03.2008
*/
function userExist( $userMail ) {
	
	$sqlResponse = selectData(TB_USER, "user_mail", "user_mail = '".$userMail."'");
	
	if ( mysqli_num_rows($sqlResponse) > 0 ) {
		msg_log(DEBUG, "User [". $userMail ."] found in [". DB_NAME .".". TB_USER ."].", SILENT);
		return ALREADY_EXIST;
	} else {		
		msg_log(DEBUG, "User [". $userMail ."] not found in [". DB_NAME .".". TB_USER ."].", SILENT);
		return DOES_NOT_EXIST; 
	}
}

/*
* Name: getCurrProj
* Desc: Retrieves currently used project from the database
* Inpt:	$userMail	-> Type: String,	Value: E-mail of the user
* Outp:				-> Type: String,	Value: Name of the project.
* Date: 11.01.2007
*/
function getCurrProj($userMail) {
	
	msg_log(DEBUG, "Retrieving currently working project for user [".$userMail."].", SILENT);

	// Determine the user
	$condition = "mail = '".$userMail."'";
	
	// Get the data from the DB
	$sqlResult = selectData('preferences', 'project', $condition);
	
	if ($sqlResult) {
		
		$dataList = '';
		
		if ( mysqli_num_rows($sqlResult) > 1 ) {
			msg_log(ERROR, "More than one entry in DB[preferences] for user: [". $userMail ."].", SILENT);
			return UNKNOWN_ERROR;
		}
		
		if ( mysqli_num_rows($sqlResult) < 1 ) {
			msg_log(INFO, "No entry in DB[preferences] for user: [". $userMail ."].", SILENT);
			return 'empty';
		}
		
		$userData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);
		
		return $userData['project'];
	}
}

/*
* Name: setCurrProj
* Desc: Sets the currently used project
* Inpt:	$userMail	-> Type: String,	Value: E-mail of the user
*		$projName	-> Type: String,	Value: Name of the project
* Outp:				-> Type: INT,		Value: PIIFMT error code
* Date: 11.01.2008
*/
function setCurrProj( $userMail, $projName ) {
	
	msg_log(DEBUG, "Setting currently used project [".$projName."] for user [".$userMail."] in the database.", SILENT);

	// First check if there is an entry in the DB for this user
	$condition = "mail = '" . $userMail . "'";
	
	$sqlResult = selectData('preferences', 'mail', $condition);
	
	if ( $sqlResult && (mysqli_num_rows($sqlResult) > 0) ) {

		msg_log(DEBUG, "Updating currently used project [".$projName."] for user [".$userMail."].", SILENT);

		//Define the user
		$condition = "mail = '".$userMail."'";
		
		$colName = 'project';
		$usrData = $projName;
		
		// Update the database
		return updateData('preferences', $colName, $usrData, $condition);
		
	} else {
		
		msg_log(DEBUG, "Creating entry for user [".$userMail."] in the DB and setting currently used project to [".$projName."].", SILENT);

		$colName = array('mail', 'project');
		$usrData = array($userMail, $projName);
		
		// Add the entry to the database
		return insertData('preferences', $colName, $usrData);
	}
}	

/*
* Name: getAuthCC
* Desc: Retrieves users ClearCase login information
* Inpt:	$userMail	-> Type: String,	Value: E-mail of the user
* Outp:				-> Type: String,	Value: CSV: [USER,PASS].
* Date: 27.11.2007
*/
function getAuthCC($userMail) {
	
	msg_log(DEBUG, "Retrieving ClearCase login for user [".$userMail."].", SILENT);

	// Determine the user
	$condition = "mail = '".$userMail."'";
	
	// Get the data from the DB
	$sqlResult = selectData('preferences', 'cc_user, cc_pass', $condition);
	
	if ($sqlResult) {
		
		$dataList = '';
		
		if ( mysqli_num_rows($sqlResult) > 1 ) {
			msg_log(ERROR, "More than one entry in DB[clearcase_login] for user: [". $userMail ."].", SILENT);
			return UNKNOWN_ERROR;
		}
		
		if ( mysqli_num_rows($sqlResult) < 1 ) {
			msg_log(INFO, "No entry in DB[clearcase_login] for user: [". $userMail ."].", SILENT);
			return 'empty';
		}
		
		$userData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);
		
		$csvUserData = $userData['cc_user'] . "," . base64_decode($userData['cc_pass']);
		
		if ( strlen($csvUserData) <= 3 ) {
			$csvUserData = "empty";
		}
		
		return $csvUserData;
	}
}

/*
* Name: setAuthCC
* Desc: Stores ClearCase credentials in the database
* Inpt:	$userMail	-> Type: String,	Value: E-mail of the user
*		$ccUser		-> Type: String,	Value: Username in ClearCase
*		$ccPass		-> Type: String,	Value: Password for ClearCase
* Outp:				-> Type: INT,		Value: PIIFMT error code
* Date: 27.11.2007
*/
function setAuthCC( $userMail, $ccUser, $ccPass ) {
	
	msg_log(DEBUG, "Storing ClearCase login for user [".$userMail."].", SILENT);

	// First check if there is an entry in the DB for this user
	$condition = "mail = '" . $userMail . "'";
	
	$sqlResult = selectData('preferences', 'mail', $condition);
	
	// If entry already exist, only update the existing one
	if ( $sqlResult && (mysqli_num_rows($sqlResult) > 0) ) {

		msg_log(DEBUG, "Updating ClearCase login for user [".$userMail."].", SILENT);

		//Define the user
		$condition = "mail = '".$userMail."'";
		
		// Determine if the password needs updating
		if ($ccPass == 'secret') {
			$colName = array('cc_user');
			$usrData = array($ccUser);
		} else {
			$colName = array('cc_user', 'cc_pass');
			$usrData = array($ccUser, base64_encode($ccPass));
		}
		
		// Update the database
		return updateData('preferences', $colName, $usrData, $condition);
		
	} else {
		
		msg_log(DEBUG, "Creating ClearCase login for user [".$userMail."].", SILENT);

		// Determine if the password needs updating
		if ($ccPass == 'secret') {
			$colName = array('mail', 'cc_user');
			$usrData = array($userMail, $ccUser);
		} else {
			$colName = array('mail', 'cc_user', 'cc_pass');
			$usrData = array($userMail, $ccUser, base64_encode($ccPass));
		}
		
		// Add the entry to the database
		return insertData('preferences', $colName, $usrData);
	}
}

/*
* Name: getLocationCC
* Desc: Retrieves ClearCase source control path from project properties
* Inpt:	$projName	-> Type: String,	Value: Name of the project
* Outp:				-> Type: String,	Value: Full path
* Date: 15.01.2008
*/
function getLocationCC($projName) {
	
	msg_log(DEBUG, "Retrieving ClearCase source path for project [".$projName."].", SILENT);

	// Determine the project
	$condition = "name = '".$projName."'";
	
	// Get the data from the DB
	$sqlResult = selectData(TB_PROJ, 'cc_location', $condition);
	
	if ($sqlResult) {
		
		if ( mysqli_num_rows($sqlResult) > 1 ) {
			msg_log(ERROR, "More than one entry in DB[".TB_PROJ."] for project: [". $projName ."].", SILENT);
			return UNKNOWN_ERROR;
		}
		
		if ( mysqli_num_rows($sqlResult) < 1 ) {
			msg_log(INFO, "No entry in DB[".TB_PROJ."] for project: [". $projName ."].", SILENT);
			return false;
		}
		
		$userData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);
		
		return $userData['cc_location'];
	}
}

/*
* Name: getActivityCC
* Desc: Retrieves ClearCase activity from project properties
* Inpt:	$projName	-> Type: String,	Value: Name of the project
* Outp:				-> Type: String,	Value: Activity name
* Date: 27.05.2008
*/
function getActivityCC($projName) {
	
	msg_log(DEBUG, "Retrieving ClearCase activity for project [".$projName."].", SILENT);

	// Determine the project
	$condition = "name = '".$projName."'";
	
	// Get the data from the DB
	$sqlResult = selectData(TB_PROJ, 'cc_activity', $condition);
	
	if ($sqlResult) {
		
		if ( mysqli_num_rows($sqlResult) > 1 ) {
			msg_log(ERROR, "More than one entry in DB[".TB_PROJ."] for project: [". $projName ."].", SILENT);
			return UNKNOWN_ERROR;
		}
		
		if ( mysqli_num_rows($sqlResult) < 1 ) {
			msg_log(INFO, "No entry in DB[".TB_PROJ."] for project: [". $projName ."].", SILENT);
			return false;
		}
		
		$userData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);
		
		return $userData['cc_activity'];
	}
}

/*
* Name: getViewCC
* Desc: Retrieves ClearCase view from project properties
* Inpt:	$projName	-> Type: String,	Value: Name of the project
* Outp:				-> Type: String,	Value: View name
* Date: 27.05.2008
*/
function getViewCC($projName) {
	
	msg_log(DEBUG, "Retrieving ClearCase view for project [".$projName."].", SILENT);

	// Determine the project
	$condition = "name = '".$projName."'";
	
	// Get the data from the DB
	$sqlResult = selectData(TB_PROJ, 'cc_view', $condition);
	
	if ($sqlResult) {
		
		if ( mysqli_num_rows($sqlResult) > 1 ) {
			msg_log(ERROR, "More than one entry in DB[".TB_PROJ."] for project: [". $projName ."].", SILENT);
			return UNKNOWN_ERROR;
		}
		
		if ( mysqli_num_rows($sqlResult) < 1 ) {
			msg_log(INFO, "No entry in DB[".TB_PROJ."] for project: [". $projName ."].", SILENT);
			return false;
		}
		
		$userData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);
		
		return $userData['cc_view'];
	}
}

/*
* Name: getFtpServer
* Desc: Retrieves FTP server details from project properties
* Inpt:	$projName	-> Type: String,	Value: Name of the project
* Outp:				-> Type: Array,		Value: [ftp_server,ftp_user,ftp_pass]
* Date: 26.02.2008
*/
function getFtpServer($projName) {
	
	msg_log(DEBUG, "Retrieving FTP server details for project [".$projName."].", SILENT);

	// Determine the project
	$condition = "name = '".$projName."'";
	
	$colNames = Array('ftp_server', 'ftp_user', 'ftp_pass');
	
	// Get the data from the DB
	$sqlResult = selectData(TB_PROJ, $colNames, $condition);
	
	if ($sqlResult) {
		
		if ( mysqli_num_rows($sqlResult) > 1 ) {
			msg_log(ERROR, "More than one entry in DB[".TB_PROJ."] for project: [". $projName ."].", SILENT);
			return UNKNOWN_ERROR;
		}
		
		if ( mysqli_num_rows($sqlResult) < 1 ) {
			msg_log(INFO, "No entry in DB[".TB_PROJ."] for project: [". $projName ."].", SILENT);
			return false;
		}
		
		$userData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);
		
		return Array($userData['ftp_server'], $userData['ftp_user'], $userData['ftp_pass']);
	}
	return false;
}

/*
* Name: getPIIScanList
* Desc: Reads the DB and creates a list of available scans for the given project
* Inpt:	$userMail	-> Type: String,	Value: E-mail of the user
*		$projName	-> Type: String,	Value: Name of the project
* Outp:				-> Type: Array,		Value: List of scan names
* Date: 23.01.08
*/
function getPIIScanList( $userMail, $projName ) {
	
	global $errMessage;
	
	$userData = getUserData( $projName, $userMail );
	
	if ( $userData['lang'] == 'en') {
		msg_log(DEBUG, "Retrieving scans for project [".$projName."].", SILENT);
		// Get scans only for the selected project no matter the languages
		$condition = "project = '" . $projName . "'";		
	} else {
		msg_log(DEBUG, "Retrieving scans for project [".$projName."] and language [".$userData['lang']."].", SILENT);
		// Get scans only for the selected project and specified language languages
		$condition = "project = '" . $projName . "' and language = '" . $userData['lang'] . "'";		
	}
	
	// Get scans only for the selected project
	$condition = "project = '" . $projName . "'";
	
	$sqlResult = selectData('scans', 'scan_name', $condition);
	
	if ( $sqlResult && (mysqli_num_rows($sqlResult) > 0) ) {
		
		msg_log(DEBUG, "[" . mysqli_num_rows($sqlResult) . "] scans found for project [".$projName."].", SILENT);
		
		$scans = array();
		
		while ($scanData = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC)) {
		
			array_push($scans, $scanData['scan_name']);
		}
		
		return $scans;
		
	} else {
		msg_log(DEBUG, "No scans found for project [".$projName."].", SILENT);
		return false;
	}
}	

/*
* Name: getPIIScan
* Desc: Retrieves scan information for a given project and scan name
* Inpt:	$projName	-> Type: String,	Value: Name of the project
*		$scanName	-> Type: String,	Value: Name of the scan
* Outp:				-> Type: Array,		Value: Array of strings
* Date: 22.01.08
*/
function getPIIScan( $projName, $scanName ) {
	
	msg_log(DEBUG, "Getting scan [".$scanName."] for project [".$projName."].", SILENT);

	// Look for this scan only in the current project
	$condition = "project = '" . $projName . "' AND scan_name = '". $scanName . "'";
	
	$sqlResult = selectData('scans', '*', $condition);
	
	if ( $sqlResult && (mysqli_num_rows($sqlResult) > 0) ) {
		
		if ( mysqli_num_rows($sqlResult) > 1 ) {
			msg_log(WARN, "More than one PII scan with name [".$scanName."] exists for project [".$projName."] in the database.", NOTIFY);
			
			// In this case we return only the first entry
			// The idea is that the user will get only the old scan and eventually
			// will report this error
			while ($data = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC)) {
				$scanData['projectName'] = $data['project'];
				$scanData['scanName'] = $data['scan_name'];
				$scanData['translated'] = $data['translated'];
				$scanData['totalFiles'] = $data['total_files'];
				$scanData['totalSize'] = $data['total_size'];
				$scanData['scanDate'] = $data['scan_date'];
				$scanData['scanTime'] = $data['scan_time'];
				$scanData['scannedBy'] = $data['scanned_by'];
				$scanData['rootDir'] = $data['root_dir'];
				$scanData['language'] = $data['language'];
				$scanData['state'] = $data['state'];
				
				return $scanData;
			}
		}
		
		// Everythig went well so we return the entry
		$data = mysqli_fetch_array($sqlResult, MYSQLI_ASSOC);
		
		$scanData['projectName'] = $data['project'];
		$scanData['scanName'] = $data['scan_name'];
		$scanData['translated'] = $data['translated'];
		$scanData['totalFiles'] = $data['total_files'];
		$scanData['totalSize'] = $data['total_size'];
		$scanData['scanDate'] = $data['scan_date'];
		$scanData['scanTime'] = $data['scan_time'];
		$scanData['scannedBy'] = $data['scanned_by'];
		$scanData['rootDir'] = $data['root_dir'];
		$scanData['language'] = $data['language'];
		$scanData['state'] = $data['state'];
		
		return $scanData;

	} else {
		msg_log(DEBUG, "PII scan with ID [".$scanName."] does not exists for project [".$projName."].", SILENT);
		return false;
	}
}	

/*
* Name: savePIIScan
* Desc: Saves scan properties for particular project in the database.
* 		NOTE: This will not check if a scan with this name already exists for this project.
* Inpt:	$projName	->	Type: String,	Value: name of the project
*		$scanName	->	Type: String,	Value: name of the scan
*		$translated	->	Type: INT,		Value: 0 - scan all, 1 - scan for new
*		$totalFiles	->	Type: String,	Value: total number of files returned from the scan
*		$totalSize	->	Type: String,	Value: total file size of all PII files
*		$scanDate	->	Type: String,	Value: time when the scan was taken
*		$scanTime	->	Type: String,	Value: time taken to complete the scan
*		$rootDir	->	Type: String,	Value: location of the project in ClearCase
*		$scannedBy	->	Type: String,	Value: name of the user performed the scan
*		$lang		->	Type: String,	Value: Language to which the package belongs
*		$state		->	Type: String,	Value: Misc info
* Outp:				->	Type: INT,		Value: PIIFMT error code
* Date: 22.01.2008
*/
function savePIIScan( $projName, $scanName, $translated, $totalFiles, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, $lang, $state ) {
	
	msg_log(DEBUG, "Storing scan [".$scanName."] for project [".$projName."] in the database.", SILENT);
	
	// Add rule to the database
	$colNames = array('project', 'scan_name', 'translated', 'total_files', 'total_size', 'scan_date', 'scan_time', 'scanned_by', 'root_dir', 'language', 'state');
	$rowData = array($projName, $scanName, $translated, $totalFiles, $totalSize, $scanDate, $scanTime, $scannedBy, $rootDir, $lang, $state);
	
	if ( insertData('scans', $colNames, $rowData) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}

/*
* Name: updatePIIScan
* Desc: Updates scan properties for particular project in the database.
* 		NOTE: This will not check if a scan with this name already exists for this project.
* Inpt:	$projName	->	Type: String,	Value: name of the project
*		$scanName	->	Type: String,	Value: name of the scan
*		$totalFiles	->	Type: String,	Value: total number of files returned from the scan
*		$totalSize	->	Type: String,	Value: total file size of all PII files
*		$scanDate	->	Type: String,	Value: time when the scan was taken
*		$scanTime	->	Type: String,	Value: time taken to complete the scan
*		$rootDir	->	Type: String,	Value: location of the project in ClearCase
*		$scannedBy	->	Type: String,	Value: name of the user performed the scan
*		$state		->	Type: String,	Value: Misc info
* Outp:				->	Type: INT,		Value: PIIFMT error code
* Date: 01.02.2008
*/
function updatePIIScan( $projName, $scanName, $totalFiles, $totalSize, $scanDate, $scanTime, $rootDir, $scannedBy, $state ) {
	
	msg_log(DEBUG, "Updating scan [".$scanName."] for project [".$projName."] in the database.", SILENT);
	
	$colNames = array('project', 'scan_name');
	$rowData = array($projName, $scanName);

	if ( !empty($totalFiles) ) {
		array_push($colNames, 'total_files');
		array_push($rowData, $totalFiles);
	}
	if ( !empty($totalSize) ) {
		array_push($colNames, 'total_size');
		array_push($rowData, $totalSize);
	}
	if ( !empty($scanDate) ) {
		array_push($colNames, 'scan_date');
		array_push($rowData, $scanDate);
	}
	if ( !empty($scanTime) ) {
		array_push($colNames, 'scan_time');
		array_push($rowData, $scanTime);
	}
	if ( !empty($scannedBy) ) {
		array_push($colNames, 'scanned_by');
		array_push($rowData, $scannedBy);
	}
	if ( !empty($rootDir) ) {
		array_push($colNames, 'root_dir');
		array_push($rowData, $rootDir);
	}
	if ( !empty($state) ) {
		array_push($colNames, 'state');
		array_push($rowData, $state);
	}
	
	// Add rule to the database
	$condition = "project = '".$projName."' AND scan_name = '" .$scanName. "'";
	
	if ( updateData('scans', $colNames, $rowData, $condition) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}

/*
* Name: delPIIScan
* Desc: Crete project in the database
* Inpt:	$projName	-> Type: String,	Value: Name of the project
*		$scanName	-> Type: String,	Value: Name of the scan
* Outp:				-> Type: INT,		Value: PIIFMT error code
* Date: 22.01.2008
*/
function delPIIScan( $projName, $scanName ) {
	
	msg_log(DEBUG, "Removing scan [".$scanName."] for project [".$projName."] from the database.", SILENT);

	$condition = "project = '".$projName."' AND scan_name = '".$scanName."'";
	
	if ( removeData('scans', $condition) ) {
		return COMMAND_OK;
	} else {
		return SQL_FAILED;
	}
}	

/*
* Name: getScansFTP
* Desc: Reads the FTP location and creates a list of available scans for the given project
* Inpt:	$projName	-> Type: String,	Value: Name of the project
* Outp:				-> Type: String,	Value: CSV of scan names
* Date: 23.01.08
*/
function getScansFTP( $projName ) {
	
	msg_log(DEBUG, "Retrieving scans from FTP for project [".$projName."].", SILENT);
	
	// Get FTP server details
	$ftpServer = getFtpServer($projName);
	// Create FTP connection to the server
	$ftpConn = new FTP($ftpServer[0], 21);
	// Authenticate the user
	if ($ftpConn && $ftpSession = $ftpConn->login($ftpServer[1], $ftpServer[2])) {
		// Get list of available scans for this project
		//----------------------------------------------
		// Define the project location
		$fullPath = ftp_pwd($ftpSession) . '/' . str_replace(" ", "", $projName);
		// Get list of files in this directory		
		if ($scanList = ftp_nlist($ftpSession, $fullPath)) {
			
			// close the connection
			ftp_close($ftpSession);
			
			$csvScans = '';
			
			foreach ( $scanList as $fName ) {
				$csvScans .= substr($fName, strrpos($fName, '/') - strlen($fName) + 1) . ',';
			}
			
			if (strlen($csvScans) > 1) {
				return substr($csvScans, 0, -1);
			}
		}
		
		return 'empty';
	}
}

/*
* Name: getPackLangCode
* Desc: Returns the language code for the given package
* Inpt:	$projName	-> Type: String,	Value: Name of the project
*		$packName	-> Type: String,	Value: Name of the package
* Outp:				-> Type: String,	Value: Language code
* Date: 11.06.08
*/
function getPackLangCode( $projName, $packName ) {
	
	$packInfo = getPIIScan($projName, $packName);
	
	msg_log(DEBUG, "Package [".$packName."] for project [".$projName."] has LANG set to [".$packInfo['language']."].", SILENT);
	// We add LANG as prefix in order to go trought the error check for string less than 3
	return "LANG:".$packInfo['language'];
}

/*
* Name: fileExistRepo
* Desc: Reads the FTP location and creates a list of available scans for the given project
* Inpt:	$projName	-> Type: String,	Value: Name of the project
* Outp:				-> Type: String,	Value: CSV of scan names
* Date: 11.03.08
*/
function fileExistRepo( $projName, $packName, $fName ) {
	
	// Generate the absolute path of the package root location to be created
	// 1. Add the project name to the PII F.M.T. repository root
	$absFileName = PII_ROOT . "/" . str_replace(" ", "", $projName);
	// 2. Add the name of the package to the path
	$absFileName = $absFileName . "/" . str_replace(" ", "", $packName);
	// 3. Add the full name of the file including relative path
	$absFileName = $absFileName . "/" . $fName;
	
	msg_log(DEBUG, "Testing if file [".$absFileName."] exist in the repository.", SILENT);
	
	if (file_exists($absFileName) ) {
		msg_log(DEBUG, "File [".$absFileName."] already exists in the repository.", SILENT);
		return ALREADY_EXIST;
	} else {
		msg_log(DEBUG, "File [".$absFileName."] does not exist in the repository.", SILENT);
		return DOES_NOT_EXIST;
	}
}

/*
* Name: packGetState
* Desc: Retrieves package status for a given project and package name
* Inpt:	$projName	-> Type: String,	Value: Name of the project
*		$scanName	-> Type: String,	Value: Name of the scan
* Outp:				-> Type: String,	Value: Returns the state of the package
* Date: 17.03.08
*/
function packGetState( $projName, $packName ) {
	
	if ($packData = getPIIScan( $projName, $packName )) {
		msg_log(DEBUG, "State of package [".$packName."] for project [".$projName."] is [".$packData['state']."].", SILENT);
		return $packData['state'];
	} else {
		return DOES_NOT_EXIST;
	}
}
?>