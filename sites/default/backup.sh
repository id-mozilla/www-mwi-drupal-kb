#!/bin/sh
# Drupal backup script
 
if [ $# -ne 1 ]; then
  echo 1>&2 "Usage: $0 backup_file.mysql"
  exit 1
fi
 
if [ ! -e "settings.php" ]
then
   echo "Must run script in same directory as settings.php"
   exit
fi
 
#------------------------------------------------------------------
 
# tables with transient data that should not be backed up
SKIP="Tables_in"
 
# grab necessary values from settings.php
USR=`grep ^\\$db_url settings.php | sed -n 's/.*\/\(.*\):.*/\1/p'`
PWD=`grep ^\\$db_url settings.php | sed -n 's/.*:\(.*\)@.*/\1/p'`
DBN=`grep ^\\$db_url settings.php | sed -n 's/.*\/\(.*\).;$/\1/p'`
HST=`grep ^\\$db_url settings.php | sed -n 's/.*@\(.*\)\/.*/\1/p'`
PRE=`grep ^\\$db_prefix settings.php | sed -n "s/.*'\(.*\)';/\1/p"`
 
#------------------------------------------------------------------
 
# remove any existing data
rm -f $1
 
# dump out the structure of all tables
mysqldump -h${HST} -u${USR} -p${PWD} \
   -d -e -q --compact --single-transaction \
   --add-drop-table ${DBN} > $1
 
# dump the data, skipping tables indicated in skip list
for TBL in $(echo "show tables" | \
   mysql -h${HST} -u${USR} -p${PWD} ${DBN} | grep -v -e ${SKIP})
do
mysqldump -h${HST} -u${USR} -p${PWD} \
   -e -q -t --compact --skip-extended-insert \
   --single-transaction --add-drop-table ${DBN} ${TBL} >> $1
done
 
# MySQL doesn't like zeros in autoincrement columns and will screw
# up the anonymous user record
echo "UPDATE \`$PRE""users\` SET uid=0 WHERE name='';" >> $1
