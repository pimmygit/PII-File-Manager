#!/bin/sh
#
# Name:		markPIIFileTranslated.sh
# Description:	Script for setting file attribute TRANSLATED
# Author:	Kliment Stefanov (stefanov@uk.ibm.com)
# Date:		05.Feb.2008
#
################################################################################

CCBIN=$1	# Location of ClearCase executables
SRCFILE=$2	# Full file name including absolute path of the file
VALUE=$3	# Value of the attribute [yes|no] 

if [ -z "$SRCFILE" -o -z "$VALUE" ]; then
        echo
	echo
	echo " Script for setting file attribute TRANSLATED"
	echo
        echo " Usage: markPIIFileTranslated.sh <cc_bin> <filename> <value>"
        echo "-----------------------------------------------------------------------------"
	echo " <cc_bin>      - Location of ClearCase executables."
	echo	
        echo " <filename>    - Full file name including absolute path of the file."
	echo
	echo " <value>       - Value of the attribute [yes|no]"
	echo
	echo
        exit 1
fi

$CCBIN/cleartool mkattr -replace TRANSLATED \"$VALUE\" $SRCFILE
