<?php
/* 
** Description:	Contains set of constants.
**
** @package:	Config
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	05/09/2007
*/

/***************************************************
* Functions return values
***************************************************/
define ('COMMAND_OK', 1);
define ('CONNECT_FAILED', 0);
define ('SQL_FAILED', -1);
define ('WRONG_PASSWORD', -2);
define ('DOES_NOT_EXIST', -3);
define ('ALREADY_EXIST', -4);
define ('COMMAND_FAIL', -5);
define ('BAD_REQUEST', -6);
define ('UNKNOWN_ERROR', -7);

/***************************************************
* Message levels
***************************************************/
define ('DEBUG', 'debug');
define ('INFO', 'info');
define ('WARN', 'warn');
define ('ERROR', 'error');

/***************************************************
* Notifications
***************************************************/
define ('SILENT', false);
define ('NOTIFY', true);
?>