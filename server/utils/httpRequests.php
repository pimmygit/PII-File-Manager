<?php
/* 
** Description:	Fetches and executes the XML HTTP Request
**
** @package:	Utils
** @subpackage:	XML HTTP Request
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	29/10/2007
*/
require_once('functions.php');

if (isset($_GET['action'])) {
	
	//foreach ($_GET as $getVal) {error_log($getVal); }
	
   	$action = $_GET['action'];
	
   	if (!isset($_GET['usrAction'])) {
   		msg_log(DEBUG, "XML HTTP Request received: [". $action ."].", SILENT);
   	}
   	
	switch ($action) {
	
		case 'usrAction':
			if ( isset($_GET['usrAction']) ) {
				msg_log(DEBUG, "ACTION: ".$_GET['usrAction'], SILENT);
				echo COMMAND_OK;
			}
			break;
							
		case 'getProjectsForManager':
			if ( isset($_GET['mail']) ) {
				echo getProjects( $_GET['mail'], true );
			}
			break;
							
		case 'getProjectsForUser':
			if ( isset($_GET['mail']) ) {
				echo getProjects( $_GET['mail'], false );
			}
			break;
							
		case 'getProjectData':
			if ( isset($_GET['project']) ) {
				echo getProjectData( $_GET['project'] );
			}
			break;
							
		case 'saveProjProps':
			if ( isset($_GET['project']) && isset($_GET['ccLocation']) && isset($_GET['ccActivity']) && isset($_GET['ccView']) && isset($_GET['ccCodeReview']) && isset($_GET['ftpServer']) && isset($_GET['ftpUser']) && isset($_GET['ftpPass']) ) {
				echo saveProjProps( $_GET['project'], $_GET['ccLocation'], $_GET['ccActivity'], $_GET['ccView'], $_GET['ccCodeReview'], $_GET['ftpServer'], $_GET['ftpUser'], $_GET['ftpPass'] );
			}
			break;
							
		case 'userExist':
			if ( isset($_GET['mail']) ) {
				echo userExist( $_GET['mail'] );
			}
			break;
						
		case 'getUsers':
			if ( isset($_GET['project']) ) {
				echo getUsers( $_GET['project'] );
			}
			break;
						
		case 'getLangList':
			echo getLangList();
			break;
						
		case 'getRenRules':
			
			if ( isset($_GET['project']) ) {
				echo getRenRules( $_GET['project'] );
			}
			break;
			
		case 'setPrvlg':
			
			if ( isset($_GET['project']) && isset($_GET['mail']) && isset($_GET['propsID']) && isset($_GET['value']) ) {
				echo setPrvlg( $_GET['project'], $_GET['mail'], $_GET['propsID'], $_GET['value'] );
			}
			break;
			
		case 'setLang':
			
			if ( isset($_GET['project']) && isset($_GET['mail']) && isset($_GET['langID']) ) {
				echo setLang( $_GET['project'], $_GET['mail'], $_GET['langID'] );
			}
			break;
			
		case 'isIBMer':
			
			if ( isset($_GET['mail']) ) {
				echo isIBMer( $_GET['mail'] );
			}
			break;
			
		case 'addProject':
			
			if ( isset($_GET['project']) && isset($_GET['mail']) && isset($_GET['name']) ) {
				echo addProject( $_GET['project'], $_GET['mail'], $_GET['name'] );
			}
			break;
			
		case 'addUser':
			
			if ( isset($_GET['project']) && isset($_GET['mail']) && isset($_GET['name']) ) {
				echo addUser( $_GET['project'], $_GET['mail'], $_GET['name'] );
			}
			break;
			
		case 'delProject':
			
			if ( isset($_GET['project']) ) {
				echo delProject( $_GET['project'] );
			}
			break;
			
		case 'delUser':
			
			if ( isset($_GET['project']) && isset($_GET['mail']) ) {
				echo delUser( $_GET['project'], $_GET['mail'] );
			}
			break;
			
		case 'remFromPref':
			
			if ( isset($_GET['mail']) ) {
				echo remFromPref( $_GET['mail'] );
			}
			break;
			
		case 'addLang':
			
			if ( isset($_GET['langCode']) && isset($_GET['langName']) ) {
				echo addLang($_GET['langCode'], $_GET['langName'] );
			}
			break;
			
		case 'delLang':
			
			if ( isset($_GET['langCode']) ) {
				echo delLang($_GET['langCode'] );
			}
			break;
			
		case 'addRule':
			
			if ( isset($_GET['project']) && isset($_GET['renFrom']) && isset($_GET['renTo']) ) {
				echo addRule( $_GET['project'], $_GET['renFrom'], $_GET['renTo'] );
			}
			break;
			
		case 'delRule':
			
			if ( isset($_GET['project']) && isset($_GET['renFrom']) && isset($_GET['renTo']) ) {
				echo delRule( $_GET['project'], $_GET['renFrom'], $_GET['renTo'] );
			}
			break;
			
		case 'editRule':
			
			if ( isset($_GET['project']) && isset($_GET['columnName']) && isset($_GET['oldRuleValue']) && isset($_GET['newRuleValue']) ) {
				echo editRenRules( $_GET['project'], $_GET['columnName'], $_GET['oldRuleValue'], $_GET['newRuleValue'] );
			}
			break;
			
		case 'getCurrProj':
			
			if ( isset($_GET['mail']) ) {
				echo getCurrProj( $_GET['mail'] );
			}
			break;
			
		case 'setCurrProj':
			
			if ( isset($_GET['mail']) && isset($_GET['projName']) ) {
				echo setCurrProj( $_GET['mail'], $_GET['projName'] );
			}
			break;
			
		case 'getPackLangCode':
			if ( isset($_GET['projName']) && isset($_GET['packName']) ) {
				echo getPackLangCode( $_GET['projName'], $_GET['packName'] );
			}
			break;
							
		case 'getAuthCC':
			
			if ( isset($_GET['mail']) ) {
				echo getAuthCC( $_GET['mail'] );
			}
			break;
			
		case 'setAuthCC':
			
			if ( isset($_GET['mail']) && isset($_GET['ccUser']) && isset($_GET['ccPass']) ) {
				echo setAuthCC( $_GET['mail'], $_GET['ccUser'], $_GET['ccPass'] );
			}
			break;
			
		case 'checkForPIIScan':
			
			if ( isset($_GET['project']) && isset($_GET['scan']) ) {
				
				if ( getPIIScan( $_GET['project'], $_GET['scan'] ) == true ) {
					echo COMMAND_OK;
				} else {
					echo DOES_NOT_EXIST;
				}
			}
			break;
			
		case 'savePIIScan':
			
			if (isset($_GET['project']) &&
				isset($_GET['scan']) &&
				isset($_GET['totalFiles']) &&
				isset($_GET['translated']) &&
				isset($_GET['totalSize']) &&
				isset($_GET['scanDate']) &&
				isset($_GET['scanTime']) &&
				isset($_GET['rootDir']) &&
				isset($_GET['scannedBy']) ) {
				
				echo savePIIScan(	$_GET['project'],
									$_GET['scan'],
									$_GET['translated'],
									$_GET['totalFiles'],
									$_GET['totalSize'],
									$_GET['scanDate'],
									$_GET['scanTime'],
									$_GET['rootDir'],
									$_GET['scannedBy'],
									'en',
									'Fresh scan' );
			}
			break;
			
		case 'delPIIScan':
			
			if ( isset($_GET['project']) && isset($_GET['scan']) ) {
				echo delPIIScan( $_GET['project'], $_GET['scan'] );
			}
			break;
			
		case 'getScansFTP':
			
			if ( isset($_GET['project']) ) {
				echo getScansFTP($_GET['project']);
			}
			break;
			
		case 'fileExistRepo':
			
			if ( isset($_GET['project']) && isset($_GET['package']) && isset($_GET['file']) ) {
				echo fileExistRepo($_GET['project'], $_GET['package'], $_GET['file']);
			}
			break;
			
		case 'packGetState':
			
			if ( isset($_GET['project']) && isset($_GET['package']) ) {
				echo packGetState($_GET['project'], $_GET['package']);
			}
			break;
			
		default:
			echo BAD_REQUEST;
			break;	
	}
} else {
	echo BAD_REQUEST;
}

?>