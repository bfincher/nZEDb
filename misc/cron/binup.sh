#!/bin/bash

BASEDIR=/var/www/nzedb
PIDFILE="$BASEDIR/misc/cron/nzedb_update.pid"
retrycountfile=$BASEDIR/misc/cron/retry_count
retrycount=$(cat $retrycountfile)
echo $retrycount

if [ -f $BASEDIR/misc/cron/stop ]; then
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

export NEWZNAB_PATH="$BASEDIR/misc/update"
export TESTING_PATH="$BASEDIR/misc/testing"
export NEWZNAB_BINUP="update_binaries.php"
export NEWZNAB_RELUP="update_releases.php 1 false"

export LOG_DIR=/var/log/nzedb
export BIN_LOG=$LOG_DIR/binup.log
export REL_LOG=$LOG_DIR/relup.log

countfile=$BASEDIR/misc/cron/count.txt

count=$(cat $countfile)
modulo=$(( $count%100 ))
if [ $modulo == 0 ]; then
   pushd ${NEWZNAB_PATH}
   echo "optimizing db"
   php optimise_db.php space >> $LOG_DIR/update.log
   popd
fi
(( count++ ))
echo $count > $countfile
echo $count

#cd ${TESTING_PATH}
#php nzb-import.php /data_local/nzbimport true true false 1000 >> $BIN_LOG 2>&1

cd ${NEWZNAB_PATH}
##php backfill.php alt.binaries.town 1000000  >> $LOG_DIR/backfill.log
php ${NEWZNAB_BINUP} >> $BIN_LOG 2>&1
date >> $BIN_LOG
php ${NEWZNAB_RELUP} >> $REL_LOG 2>&1
date >> $REL_LOG
php decrypt_hashes.php 2000 >> $LOG_DIR/update.log 2>&1

cd ${TESTING_PATH}/Release
php removeCrapReleases.php true 6 blacklist >> $LOG_DIR/update.log 2>&1
php removeCrapReleases.php true 6 blfiles >> $LOG_DIR/update.log 2>&1
php removeCrapReleases.php true 6 codec >> $LOG_DIR/update.log 2>&1
php removeCrapReleases.php true 6 executable >> $LOG_DIR/update.log 2>&1
php removeCrapReleases.php true 6 gibberish >> $LOG_DIR/update.log 2>&1
php removeCrapReleases.php true 6 installbin >> $LOG_DIR/update.log 2>&1
php removeCrapReleases.php true 6 short >> $LOG_DIR/update.log 2>&1
#php delete_disabled_category_releases.php true >> $LOG_DIR/update.log 2>&1


#cd ${NEWZNAB_PATH}/threaded_scripts
#python postprocess_threaded.py all >> $LOG_DIR/update.log

cd ${NEWZNAB_PATH}/python
nice python postprocess_threaded.py additional >> $LOG_DIR/update.log 2>&1
python postprocess_threaded.py nfo >> $LOG_DIR/update.log 2>&1
python postprocess_threaded.py movie >> $LOG_DIR/update.log 2>&1
python postprocess_threaded.py tv >> $LOG_DIR/update.log 2>&1

cd ${NEWZNAB_PATH}/
php postprocess.php music true >> $LOG_DIR/update.log 2>&1
php postprocess.php book true >> $LOG_DIR/update.log 2>&1
php postprocess.php pre true >> $LOG_DIR/update.log 2>&1
php postprocess.php sharing true >> $LOG_DIR/update.log 2>&1
php match_prefiles.php  10000 show >> $LOG_DIR/update.log 2>&1
php requestid.php 10000 true >> $LOG_DIR/update.log 2>&1
php predbftmatch.php 10000 show >> $LOG_DIR/update.log 2>&1


cd ${TESTING_PATH}/Release
php fixReleaseNames.php 3 true all yes >> $LOG_DIR/update.log 2>&1
php fixReleaseNames.php 1 true all yes >> $LOG_DIR/update.log 2>&1
php fixReleaseNames.php 7 true all yes >> $LOG_DIR/update.log 2>&1

#cd ${NEWZNAB_PATH}/python
#python fixreleasenames_threaded.py par2 >> $LOG_DIR/update.log 2>&1
#cd ${TESTING_PATH}
#php clean_by_cat.php >> $LOG_DIR/update.log

echo "Done" >> $LOG_DIR/update.log
date >> $LOG_DIR/update.log

rm $PIDFILE

