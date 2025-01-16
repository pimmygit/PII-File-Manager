CREATE DATABASE IF NOT EXISTS piifmt;

USE piifmt;

#
# Name: projects
# Desc: Contains list of projects and their properties
# Last: 26.10.2007
#
CREATE TABLE IF NOT EXISTS projects(
name			VARCHAR(30) NOT NULL PRIMARY KEY,								# Name of the project
cc_location		VARCHAR(255),													# Location in ClearCase
cc_activity		VARCHAR(30),													# ClearCase activity
dev_reviewer	VARCHAR(50),													# IBM E-mail of developer to code-review
ftp_server		VARCHAR(90),													# Address of remote FTP server
ftp_user		VARCHAR(50),													# Username to access the FTP server
ftp_pass		VARCHAR(20),													# Password to access the FTP server
datetime		TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP	# Timestamp of the last modification
);

#
# Name: users
# Desc: Contains user accounts
# Last: 26.10.2007
#
CREATE TABLE IF NOT EXISTS users(
user_mail	VARCHAR(50)	NOT NULL,				# Users E-mail account name
user_name	VARCHAR(30) NOT NULL,				# Users full name as in Bluepages
project		VARCHAR(30)	NOT NULL,				# User is assigned to this project
lang		VARCHAR(5)	NOT NULL DEFAULT 'en',	# Language code for file renaming (must match the Lang code table)
manager		BOOLEAN		DEFAULT false,			# Manager privileges
prvlg_01	BOOLEAN		DEFAULT true,			# Various privileges defined in settings.php
prvlg_02	BOOLEAN 	DEFAULT true,			# Various privileges defined in settings.php
prvlg_03	BOOLEAN 	DEFAULT true,			# Various privileges defined in settings.php
prvlg_04	BOOLEAN 	DEFAULT true,			# Various privileges defined in settings.php
prvlg_05	BOOLEAN 	DEFAULT false,			# Various privileges defined in settings.php
prvlg_06	BOOLEAN 	DEFAULT false,			# Various privileges defined in settings.php
prvlg_07	BOOLEAN 	DEFAULT false,			# Various privileges defined in settings.php
prvlg_08	BOOLEAN 	DEFAULT false,			# Various privileges defined in settings.php
prvlg_09	BOOLEAN 	DEFAULT false,			# Various privileges defined in settings.php
prvlg_10	BOOLEAN 	DEFAULT false,			# Various privileges defined in settings.php
prvlg_11	BOOLEAN 	DEFAULT false,			# Various privileges defined in settings.php
prvlg_12	BOOLEAN 	DEFAULT false,			# Various privileges defined in settings.php
prvlg_13	BOOLEAN 	DEFAULT false			# Various privileges defined in settings.php
);

#
# Name: preferences
# Desc: Contains user preferences for each IBM user account
# Last: 26.10.2007
#
CREATE TABLE IF NOT EXISTS preferences(
mail		VARCHAR(50) NOT NULL PRIMARY KEY,	# Users IBM E-mail
project		VARCHAR(30),						# Current working project
cc_view		VARCHAR(30),						# Name of ClearCase view
cc_user		VARCHAR(30),						# ClearCase username
cc_pass		VARCHAR(20)							# ClearCase password
);

#
# Name: scans
# Desc: Contains source control scans for each project
# 	*	Those values are required to compare the result from the scan with
#		what is found as files in the PII F.M.T. repository in case someone has messed up the files.
# Last: 16.01.2008
#
CREATE TABLE IF NOT EXISTS scans(
project			VARCHAR(30) NOT NULL,					# Name of the project
scan_name		VARCHAR(30) NOT NULL,					# Name/Identifier of the scan
translated		BOOLEAN DEFAULT false,					# Defines if only files with flag TRANSLATE set to yes should be shown (0 - any, 1 - only new files)
total_files		INT UNSIGNED,							# Number of files returned from the scan*
total_size		INT UNSIGNED,							# Total size in bytes of all files returned from the scan*
scan_date		TIMESTAMP DEFAULT CURRENT_TIMESTAMP,	# Timestamp when the scan was done
scan_time		VARCHAR(12),							# Time taken to perform the scan
scanned_by		VARCHAR(30),							# Name of the person performed the scan
root_dir		VARCHAR(255),							# Root location to scan in
language		VARCHAR(5) DEFAULT 'en',				# Source packages are 'en', returned are with the language code of the person who created the package
state			VARCHAR(12)	DEFAULT 'Fresh scan'		# State of the scan ['Sent' -> Sent for translation for eaxmple]
);

#
# Name: pii_files NOT_TO_BE_USED (not implemented)
# Desc: Contains files and their properties retrieved from source control scans
# Last: 16.01.2008
#
CREATE TABLE IF NOT EXISTS pii_files (
scan_name		VARCHAR(30) NOT NULL,					# Name/Identifier of the scan
file_name		VARCHAR(255) NOT NULL,					# Full name including path of the file
file_data		MEDIUMBLOB NOT NULL,					# Binary data of the file
file_size		MEDIUMINT UNSIGNED NOT NULL,			# Size of the file on the disk in bytes
file_date		TIMESTAMP DEFAULT CURRENT_TIMESTAMP,	# Time of the last modification of the file
status			VARCHAR(10),							# Status of various checks against the file
summary			MEDIUMTEXT								# Information regarding the file or its status
);

#
# Name: languages
# Desc: Contains all available languages
# Last: 06.11.2007
#
CREATE TABLE IF NOT EXISTS languages(
lang_code	VARCHAR(5)	NOT NULL PRIMARY KEY,	# Language code (en, es, zh_TW)
lang_name	VARCHAR(30)	NOT NULL				# Language name (English, Spanish, Traditional Chinese)
);

#
# Name: renaming_rules
# Desc: Contains renaming rules (default and per project which overwrite the default)
# Last: 26.10.2007
#
CREATE TABLE IF NOT EXISTS renaming_rules(
project		VARCHAR(30) NOT NULL,	# Project name ('default' if default rule)
ren_from	VARCHAR(30) NOT NULL,	# Old value
ren_to		VARCHAR(30)				# New value
);

CREATE USER fmtadmin IDENTIFIED BY 'm1cr0mus3';
CREATE USER fmtuser IDENTIFIED BY 'n3tc00l';

GRANT ALL PRIVILEGES ON piifmt.* TO fmtadmin@localhost IDENTIFIED BY 'm1cr0mus3' WITH GRANT OPTION;
GRANT INSERT, UPDATE, DELETE, SELECT ON piifmt.* TO fmtuser@localhost IDENTIFIED BY 'n3tc00l';
