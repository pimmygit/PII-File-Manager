#!/bin/sh
#
# Name:			scanPIIFiles.sh
# Description:	Script for locating translatable files with particular
#				attributes in a given project location in ClearCase.
# Author:		Kliment Stefanov (stefanov@uk.ibm.com)
# Date:			28.Apr.2008
#
################################################################################
CCBIN=$1		# Location of ClearCase executables
CCVIEW=$2		# ClearCase view
CCACTIVITY=$3	# ClearCase project activity
CCLOCATION=$4	# Project root in in ClearCase
PACKLOCATON=$5	# Location of the package to be checked-in
LANGCODE=$6		# Language code of the returned files

if [ -z "$CCBIN" -o -z "$CCVIEW" -o -z "$CCACTIVITY" -o -z "$CCLOCATION" -o -z "$PACKLOCATON" -o -z "$LANGCODE" ]; then
	echo
	echo
	echo " Script for checking in PII files into ClearCase"
	echo
	echo " Usage: canPIIFiles.sh <cc_bin> <cc_view> <cc_activity> <cc_location> <pack_location> <lang_code>"
	echo "-------------------------------------------------------------------------------------------------"
	echo " <cc_bin>		- Location of ClearCase executables."
	echo
	echo " <cc_view>		- ClearCase view."
	echo
	echo " <cc_activity>	- ClearCase activity. NULL if not to be set."
	echo
	echo " <cc_location>	- Project root in ClearCase."
	echo
	echo " <pack_location>	- Package root in the TEMP repository."
	echo
	echo " <lang_code>	- Language code of the returned files."
	echo
	echo
	exit 1
fi

# Verify that all required commands could be found on the system
CP=`which cp`; if [ ! -x "$CP" ]; then CP=/bin/clear; fi
if [ ! -x "$CP" ]; then echo "Could not locate [cp] on the system"; fi

# Declare runtime variables
i=0;

################################################################################
#
# Name: checkIn
# Desc:	Checks-IN all files in ClearCase 
# Args:	$1		-> Project root in in ClearCase
#		$2		-> Package root in PII F.M.T. repository
#		$3		-> Absolute path to the file
# Vars:	$RESP	-> true/false depending on the result from execution
#
################################################################################
checkIn() {
	
	ROOT_CC=$1;
	ROOT_TEMP=$2;
	FILE_TEMP=$3;
	
	FILE_CC=`echo ${FILE_TEMP/"$ROOT_TEMP"/"$ROOT_CC"}`;
	PARENT=`dirname "$FILE_CC"`;
	
	# Verify that the equivalent exists in ClearCase
	if [ -f "$FILE_CC" ]; then
		echo "Check-IN file:  $FILE_CC";
		# Make sure that this file is checked out
		TEST=`$CCBIN/cleartool lscheckout -me "$FILE_CC"`;
		if [ -n "$TEST" ]; then
			# Check-IN the file
			$CCBIN/cleartool checkin -nc -identical "$FILE_CC"
			# Set the LANG attribute
			$CCBIN/cleartool mkattr -replace LANG \"$LANGCODE\" $FILE_CC
		else	
			echo "Already Checked-IN. Setting LANG=\"$LANGCODE\" attribute only.";
			# Set the LANG attribute
			$CCBIN/cleartool mkattr -replace LANG \"$LANGCODE\" $FILE_CC
		fi
	else
		# If not, something went wrong when we were creating the element
		echo "Error Checking IN: Element does not exist: $FILE_CC";
	fi
}

################################################################################
#
# Name: copyFile
# Desc:	Copies all files to the equivalent ones in ClearCase 
# Args:	$1		-> Project root in in ClearCase
#		$2		-> Package root in PII F.M.T. repository
#		$3		-> Absolute path to the file
# Vars:	$RESP	-> true/false depending on the result from execution
#
################################################################################
copyFile() {
	
	ROOT_CC=$1;
	ROOT_TEMP=$2;
	FILE_TEMP=$3;
	
	FILE_CC=`echo ${FILE_TEMP/"$ROOT_TEMP"/"$ROOT_CC"}`;
	PARENT=`dirname "$FILE_CC"`;
	
	# Verify that the equivalent file exists in ClearCase
	if [ -f "$FILE_CC" ]; then
		#echo "Copy_FROM: $FILE_TEMP";
		#echo "Copy_TO:   $FILE_CC";
		$CP -f "$FILE_TEMP" "$FILE_CC"
	else
		# If not, we create the element
		echo "Create element: $FILE_CC";
		$CCBIN/clearfsimport -recurse -nsetevent "$FILE_TEMP" "$PARENT"
	fi
}

################################################################################
#
# Name: checkOut
# Desc:	For each given file, Checks-OUT the equivalent in ClearCase 
# Args:	$1		-> Project root in in ClearCase
#		$2		-> Package root in PII F.M.T. repository
#		$3		-> Absolute path to the file
# Vars:	$RESP	-> true/false depending on the result from execution
#
################################################################################
checkOut() {
	
	ROOT_CC=$1;
	ROOT_TEMP=$2;
	FILE_TEMP=$3;
	
	FILE_CC=`echo ${FILE_TEMP/"$ROOT_TEMP"/"$ROOT_CC"}`;
	PARENT=`dirname "$FILE_CC"`;
	
	# Verify that the equivalent exists in ClearCase
	if [ -f "$FILE_CC" ]; then
		echo "Check-OUT file: $FILE_CC";
		$CCBIN/cleartool checkout -nc "$FILE_CC"
	#else
	#	# If not, we Check-OUT the parent directory to create the element
	#	echo "Check-OUT dir:  $PARENT";
	#	$CCBIN/cleartool checkout -nc "$PARENT"
	#	
	#	# Build a list of unique parent checkouts because 
	#	TEST="";
	#	for PARENT_DIR in $PARENTS_LIST
	#	do
	#		if ["$PARENT" = "$PARENT_DIR"]; then
	#			TEST="YES";
	#		fi
	#	done
	#	
	#	if [ -z "$TEST" ]; then
	#		PARENTS_LIST[$i]="$PARENT"
	#		i=`echo "$i + 1" | bc`;
	#	fi
	fi
}

################################################################################
#
# Name: scanDir
# Desc:	Scans a given directory for files and checks-in each of them
#		into ClearCase, while recursing into each subdirectory
# Args:	$1		-> Action to perform with the file
#		$2		-> Absolute path to the directory
# Vars:	$RESP	-> true/false depending on the result from execution
#
################################################################################
scanDir() {
	
	ACT=$1
	DIR=$2
	
	for FILE in "$DIR/"*
	do
		if [ -f "$FILE" ]; then
			case "$ACT" in
				"checkOut")
					checkOut $CCLOCATION $PACKLOCATON $FILE ;;
				"copyFile")
					copyFile $CCLOCATION $PACKLOCATON $FILE ;;
				"checkIn")
					checkIn $CCLOCATION $PACKLOCATON $FILE ;;
			esac
		elif [ -d "$FILE" ]; then
			scanDir "$ACT" "$FILE"
		fi
	done
}

#
# I. Verify the view is set
#--------------------------------------------------------------------------------
TEST=`$CCBIN/cleartool pwv | sed -e '/Set view: /!d' -e 's/Set view: //'`;

if [ "$TEST" != "$CCVIEW" ]; then
	echo "View [$TEST] not set. Exiting."
	exit 1
fi

#
# II. Start the operation if the given value is a file or dir
#--------------------------------------------------------------------------------
if [ -d "$PACKLOCATON" -o -f "$PACKLOCATON" ]; then
	# 1. Check-OUT all files (or parent directories
	scanDir "checkOut" "$PACKLOCATON"
	# 2. Copy the files across
	scanDir "copyFile" "$PACKLOCATON"
	# 3. Check-IN all files
	scanDir "checkIn" "$PACKLOCATON"
	
#	# Check-IN any parent checkouts if any
#	for PARENT_DIR in $PARENTS_LIST
#	do
#		$CCBIN/cleartool checkin -nc "$PARENT_DIR"
#	done
else
	echo "$PACKLOCATON does not exist.";
	exit 1
fi

