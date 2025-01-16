<?php
/* 
** Description:	Contains functions for producing log information
**				related to the L10N tool operation.
** @package:	utils
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	05/09/2007
*/

/*
* Name: msg_log
* Desc: Logs information to a file (filename specified in 'config/settings.php')
* Inpt:	msg_level		-> Type: String, Value: ERROR, WARN, INFO
*		message			-> Type: String, Value: Description of the event
*		notifyUser		-> Type: Boolean, Value: [NOTIFY||SILENT]||[TRUE||FALSE]
* Outp: Type: Boolean	-> TRUE on success, FALSE otherwise.
* Date: 11.09.2007
*/
function msg_log($msglevel, $message, $notifyUser) {
	
	global $errMessage;
	
	date_default_timezone_set('UTC');
	
	$timestamp = date('d/m/Y H:i:s');
	
	// Record the IP address from where the user is connecting.
	if ( !empty($_SERVER['REMOTE_ADDR']) ) {
		$remoteAddr = $_SERVER['REMOTE_ADDR'];
	} else {
		$remoteAddr = 'IP UNKNOWN';
	}
	
	switch(LOG_LEVEL) {
	
		case 'debug':
		case 'DEBUG':
		
			if ( $msglevel === 'debug' || $msglevel === 'DEBUG' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] DEBUG: '.$message.PHP_EOL, 3, LOG_FILE);
			}
			if ( $msglevel === 'info' || $msglevel === 'INFO' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] INFO: '.$message.PHP_EOL, 3, LOG_FILE);
			}
			if ( $msglevel === 'warn' || $msglevel === 'WARN' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] WARN: '.$message.PHP_EOL, 3, LOG_FILE);
			}
			if ( $msglevel === 'error' || $msglevel === 'ERROR' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] ERROR: '.$message.PHP_EOL, 3, LOG_FILE);
			}
							
			if ($notifyUser === true) {
				$errMessage = $message;
			}
			
			break;
		
		case 'info':
		case 'INFO':
		
			if ( $msglevel === 'info' || $msglevel === 'INFO' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] INFO: '.$message.PHP_EOL, 3, LOG_FILE);
			}
			if ( $msglevel === 'warn' || $msglevel === 'WARN' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] WARN: '.$message.PHP_EOL, 3, LOG_FILE);
			}
			if ( $msglevel === 'error' || $msglevel === 'ERROR' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] ERROR: '.$message.PHP_EOL, 3, LOG_FILE);
			}
							
			if ($notifyUser === true) {
				$errMessage = $message;
			}
					
			break;
		
		case 'warn':
		case 'WARN':
			
			if ( $msglevel === 'warn' || $msglevel === 'WARN' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] WARN: '.$message.PHP_EOL, 3, LOG_FILE);
			}
			if ( $msglevel === 'error' || $msglevel === 'ERROR' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] ERROR: '.$message.PHP_EOL, 3, LOG_FILE);
			}
							
			if ($notifyUser === true) {
				$errMessage = $message;
			}
			
			break;
			
		case 'error':
		case 'ERROR':
			
			if ( $msglevel === 'error' || $msglevel === 'ERROR' ) {
				error_log('['.$timestamp.'] ['.$remoteAddr.'] ERROR: '.$message.PHP_EOL, 3, LOG_FILE);
			}
							
			if ($notifyUser === true) {
				$errMessage = $message;
			}

			break;
		
		default:
			
			error_log('['.$timestamp.'] ['.$remoteAddr.'] ERROR: Logging level not defined.'.PHP_EOL, 3, LOG_FILE);
			break;
	}
}

/*
* Name: db_log
* Desc: Logs history information to a database
* Inpt: $log_type		-> Type: String, Value: Type of the event/action
*		$log_message	-> Type: String, Value: Description of the event/action
*		$log_status		-> Type: Boolean, Value: TRUE on success, FALSE otherwise
* Outp: Type: Boolean	-> TRUE on success, FALSE otherwise.
* Date: 12.09.2007
*/
function db_log($log_type, $log_message, $log_status) {

	// If for any reason the user ID fails to get registered (unlikely),
	// then at least try to get the IP address from where he connected.
	if ( isset($_SESSION['loggedUser']) ) {
		$loggedUser = $_SESSION['loggedUser'];
	} else if ( !empty($_SERVER['REMOTE_ADDR']) ) {
		$loggedUser = $_SERVER['REMOTE_ADDR'];
	} else {
		$loggedUser = 'UNKNOWN';
	}
	
	$colNames = array('user', 'type', 'message', 'status', 'datetime');
	$logData = array($loggedUser, $log_type, $log_message, $log_status, 'timestamp');
	
	return insertData(LOG_TABLE, $colNames, $logData);
}
?>