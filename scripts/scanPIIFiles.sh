#!/bin/sh
#
# Name:		scanPIIFiles.sh
# Description:	Script for locating translatable files with particular
#		attributes in a given project location in ClearCase.
# Author:	Kliment Stefanov (stefanov@uk.ibm.com)
# Date:		18.Jan.2008
#
################################################################################
CCBIN=$1        # Location of ClearCase executables
CCLOCATION=$2	# Location of the project in ClearCase
TRANSL=$3	# Determine if only files not sent for translation should be returned from the scan. Value: [all|new]

# Verify that all required commands could be found on the system
SED=`which sed`; if [ ! -x "$SED" ]; then SED=/bin/sed; fi
if [ ! -x "$SED" ]; then echo "Could not locate [sed] on the system"; exit; fi

if [ -z "$CCLOCATION" ]; then
        echo
	echo
	echo " Script for searching for PII files in a project by"
	echo " given ClearCase file attributes."
	echo
        echo " Usage: canPIIFiles.sh <cc_bin> <cc_location> <translated>"
        echo "-----------------------------------------------------------------------------"
        echo " <cc_bin>      - Location of ClearCase executables."
        echo
        echo " <cc_location>    - Location of the project in ClearCase"
        echo
	echo " <translated>     - Determine if only files not sent for translation should be"
        echo "                    returned from the scan. Value: [all|new]."
	echo
	echo
        exit 1
fi

################################################################################
#
# Name: isUCM
# Desc:	Checks if the used view is from a UCM project
# Args:	none
# Vars:	$RESP	-> true/false depending on the result from execution
#
################################################################################
isUCM() {

        VIEW_NAME=`"$CCBIN/"cleartool pwv | "$SED" -e '/^Set view:/!d' -e 's/^Set view: //'`

        VIEW_ATTR=`"$CCBIN/"cleartool lsview -long "$VIEW_NAME" | "$SED" -e '/^View attributes:/!d' -e 's/^View attributes: //'`

        if [ -z "$VIEW_ATTR" ]; then
                return 1
        else
                return 0
        fi
}

# If the view is from a UCM project, then we have to rebase to the recommended baseline
if isUCM; then
	$CCBIN/cleartool rebase -recommended
fi

# Determine if we should scan for ALL PII files of only for the NEW/MODIFIED ones
if [ -z "$TRANSL" ] || [ "$TRANSL" = all ]; then
	$CCBIN/cleartool find $CCLOCATION -version 'SRC_XLATE=="yes"' -exec 'ls -g -G --time-style=long-iso $CLEARCASE_PN' | awk '{ printf "%s,%s %s,%s\n" , $3 , $4 , $5 , $6 ; }'
elif [ "$TRANSL" = new ]; then
        $CCBIN/cleartool find $CCLOCATION -version 'SRC_XLATE=="yes" && TRANSLATED=="no"' -exec 'ls -g -G --time-style=long-iso $CLEARCASE_PN' | awk '{ printf "%s,%s %s,%s\n" , $3 , $4 , $5 , $6 ; }'
fi
