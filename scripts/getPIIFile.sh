#!/bin/sh
#
# Name:		getPIIFiles.sh
# Description:	Script for transferring files securely between
#		ClearCase client and PII File Managent Tool.
# Author:	Kliment Stefanov (stefanov@uk.ibm.com)
# Date:		18.Jan.2008
#
################################################################################
#
# Additional info:
#
# This script relies on the fact that during the installation/configuration of
# the PII File Management Tool, the user has created a public-key authentication
# between the ClearCase client (OpenSSH server) and the PII F.M.T. host server
# (OpenSSH client) WITHOUT setting a passphrase. Seting a passphrase will
# propmt for the key at execution time and this cript will stall. 
#
# To set up the passphrase-less public-key authentication:
# --------------------------------------------------------
#
# 0. Log into the ClearCase client host
#     $ ssh fmtuser@ccclient
#     Password: ********
#
# 1. Generate the key on the ClearCase client (OpenSSH server) machine
#     ccclient$ mkdir -p ~/.ssh (If it doesnt already exist)
#     ccclient$ chmod 700 ~/.ssh
#     ccclient$ cd ~/.ssh
#     ccclient$ ssh-keygen -t rsa (Press Enter on every prompt)
#
# 2. Copy the public key to the PII F.M.T. host server (OpenSSH client)
#     ccclient$ scp -p id_rsa.pub fmtuser@fmtserver:/home/fmtuser/.ssh/authorized_keys
#     Password: ********
#     ccclient$ logout
#
# 3. Log into the PII F.M.T. host server and install the public key
#     $ ssh fmtuser@fmtserver
#     Password: ********
#     fmtserver$ mkdir -p ~/.ssh (If it doesnt already exist)
#     fmtserver$ chmod 700 ~/.ssh
#     fmtserver$ cat id_rsa.pub >> ~/.ssh/authorized_keys  (Appending)
#     fmtserver$ chmod 600 ~/.ssh/authorized_keys
#     fmtserver$ mv id_rsa.pub ~/.ssh (Optional, just to be organized)
#     fmtserver$ logout
#
################################################################################

FMTHOST=$1    # Full hostname of the PII File Management Tool
SRCFILE=$2    # Full file name including absolute path of the file to be copied from
DSTFILE=$3    # Full file name including absolute path of the file to be copied to 
FMTUSER=$4    # User name to login to the PII F.M.T. host

if [ -z "$FMTHOST" -o -z "$SRCFILE" -o -z "$DSTFILE" ]; then
        echo
	echo
	echo " Script for transferring files securely between"
	echo " ClearCase client and PII File Managent Tool."
	echo
        echo " Usage: getPIIFiles.sh <piifmt_host> <src_file> <dst_file> <piifmt_user>"
        echo "-----------------------------------------------------------------------------"
        echo " <piifmt_host> - Full hostname of the PII File Management Tool"
        echo
        echo " <src_file>    - Full file name including absolute path"
        echo "                 of the file to be copied from."
	echo
	echo " <dst_file>    - Full file name including absolute path"
        echo "                 of the file to be copied to."
	echo
	echo " <piifmt_user> - User name to login to the server (Default: fmtuser)"
        echo
	echo
        exit 1
fi

if [ -x /usr/bin/scp ]; then
    SCP=/usr/bin/scp
elif [ -x /bin/scp ]; then
    SCP=/bin/scp
else
    echo "ERROR: [scp] does not exist or cannot be run on your system. Exiting.."
    exit 1
fi

if [ -z "$FMTUSER" ]; then
	$SCP -o StrictHostKeyChecking=no -p $SRCFILE fmtuser@$FMTHOST:$DSTFILE
else
	$SCP -o StrictHostKeyChecking=no -p $SRCFILE $FMTUSER@$FMTHOST:$DSTFILE
fi
