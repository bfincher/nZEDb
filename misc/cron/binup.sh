#!/bin/bash

PIDFILE="/var/www/nzedb/misc/cron/nzedb_update.pid"
retrycountfile=/var/www/nzedb/misc/cron/retry_count
retrycount=$(cat $retrycountfile)
echo $retrycount

if [ -f /var/www/nzedb/misc/cron/stop ]; then
    echo "not running due to presence of stop file" >> /var/log/nzedb/update.log
    exit 0
fi


if [ -f $PIDFILE ]; then
    PID=$(cat $PIDFILE)

    # if the PID file exists but the process isn't running, delete the file
    ps -ef | grep $PID | grep -v 'grep' > /dev/null
    if [ $? -ne 0 ]; then
        rm $PIDFILE
    fi
fi

if [ -f $PIDFILE ]; then
    if [ $retrycount -gt 3 ]; then
       kill -9 `cat ${PIDFILE}` 
       rm $PIDFILE
    else
        (( retrycount++ ))
	echo $retrycount > $retrycountfile
        echo "$nzedb_update is already running"
	exit -1
    fi
fi


echo "0" > $retrycountfile
PID=$$
echo "PID = " $PID
echo $PID > ${PIDFILE}

#/var/www/nzedb/misc/cron/update.sh

export NEWZNAB_PATH="/var/www/nzedb/misc/update_scripts"
export ADMIN_PATH="/var/www/nzedb/www/admin"
export TESTING_PATH="/var/www/nzedb/misc/testing"
export NEWZNAB_BINUP="update_binaries.php"
export NEWZNAB_RELUP="update_releases.php 1 true"
export NEWZNAB_UPDATE_CLEANUP="update_cleanup.php"
export NEWZNAB_IMPORT="nzb-importmodified.php /home/bfincher/nzbimport false"
export UPDATE_PARSING="update_parsing.php"

export LOG_DIR=/var/log/nzedb
export BIN_LOG=$LOG_DIR/binup.log
export REL_LOG=$LOG_DIR/relup.log

countfile=/var/www/nzedb/misc/cron/count.txt

count=$(cat $countfile)
modulo=$(( $count%10 ))
if [ $modulo == 0 ]; then
   pushd ${NEWZNAB_PATH}
   echo "optimizing db"
   php cmd-line-optimize.php
   popd
fi
(( count++ ))
echo $count > $countfile
echo $count

cd ${NEWZNAB_PATH}
php ${NEWZNAB_BINUP}  2>&1 >> $BIN_LOG
php ${NEWZNAB_RELUP}  2>&1 >> $REL_LOG

cd ${TESTING_PATH}/Release_scripts
php fixReleaseNames.php 3 true all yes >> $LOG_DIR/update.log
php fixReleaseNames.php 1 true all yes >> $LOG_DIR/update.log
php removeCrapReleases.php true 6 >> $LOG_DIR/update.log
php delete_disabled_category_releases.php true >> $LOG_DIR/update.log

cd ${TESTING_PATH}
php clean_by_cat.php

rm $PIDFILE

