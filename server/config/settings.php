<?php
/* 
** Description:	Contains the properies for the operation of 
**				the PII F.M.T. file management tool.
** @package:	Config
** @author:		Kliment Stefanov <stefanov@uk.ibm.com>
** @created:	05/09/2007
*/
global $FMT_ADMIN_LIST;

/***************************************************
* PII Files storage repository
***************************************************/
define('PII_ROOT', '/var/piifmt');

/***************************************************
* PII Files storage repository
***************************************************/
define('PII_USER', 'fmtuser');								# Local user to access the server, used for SCP of files.
															# This user should be used to create the SSH public-key authentication

/***************************************************
* Type of user authentication
***************************************************/
define('USE_LDAP_AUTHENTICATION', true);

/***************************************************
* PII F.M.T. list of administrators
***************************************************/
$FMT_ADMIN_LIST = array('stefanov@uk.ibm.com', 'eflarup@us.ibm.com', 'KYTEADRI@uk.ibm.com', 'bendtsen@dk.ibm.com');

/***************************************************
* LDAP Server settings
***************************************************/
define('LDAP_HOST', 'bluepages.ibm.com');						# Full host name of the LDAP server
define('LDAP_PORT', '389');										# Port to connect to the LDAP server							
define('LDAP_OU', 'bluepages');									# Organizational Unit
define('LDAP_O', 'ibm.com');									# Organization

/***************************************************
* ClearCase Client host server
***************************************************/
define('CC_HOST', 'napoli.hursley.ibm.com');					# Full host name of the ClearCase client host
define('CC_PORT', '22');										# Port to connect to the ClearCase client host
define('CC_ROOT', '/export/src');								# Root location of all projects
define('CC_BIN', '/usr/atria/bin');								# Location of ClearCase executables (cleartool for example)

/***************************************************
* MySQL database settings
***************************************************/
define('DB_HOST', 'napoli.hursley.ibm.com');					# Host name of the database server ("localhost" if on the same server)
define('DB_PORT', '3306');										# Port to connect to the database server
define('DB_NAME', 'piifmt');									# Name of the database which contains L10N tables
define('DB_USER', 'fmtuser');									# Username to connect as to the database
define('DB_PASS', 'n3tc00l');									# Password to authenticate the user
define('TB_PROJ', 'projects');									# Table containing all projects and their properties
define('TB_USER', 'users');										# Table containing all users allowed to access L10N tool and their permissions
define('TB_RULE', 'renaming_rules');							# Table containing renaming rules
define('TB_PREF', 'preferences');								# Table containing users preferences
define('TB_LANG', 'languages');									# Table containing all translation languages

/***************************************************
* User privileges (Note: db dependent; do not use commas - ',')
***************************************************/
$PRVLGS = array(1 =>'Scan source control for PII.',				# Description of the privilege
					'Modify TRANSLATED file attribute.',		# Description of the privilege
					'Extract files and create packages.',		# Description of the privilege
					'Upload/Download/Delete packages.',			# Description of the privilege
					'FTP Upload/Download of packages.',			# Description of the privilege
					'Add/Remove files from packages.',			# Description of the privilege
					'Apply file renaming rules.',				# Description of the privilege
					'Check-IN translated files in CC.',			# Description of the privilege
					'Run tests against source files.',			# Description of the privilege
					'Invoke language pack build.',				# Description of the privilege
					'Invoke pseudo translated build.',			# Description of the privilege
					'Send language pack to CFM.',				# Description of the privilege
					'View project log information.');			# Description of the privilege

/***************************************************
* Log setting
***************************************************/
define('LOG_TABLE', 'historylog');								# Name of the table in the DB for history logging
define('LOG_FILE', '/usr/local/apache/logs/piifmt.log');		# Location of the log file
define('LOG_LEVEL', 'debug');									# Log level: debug, info, warn, error
?>