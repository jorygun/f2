#!/bin/bash
#reloads the f2-dev db from the main db backup
#run daily or on demand


PATH=/bin:/usr/bin:/usr/local/bin
HOME=/usr/home/digitalm
PWpro=STjzyHFr
PWdev=fXFjb9ED
sqltemp=/tmp/sqltemp.sql


cd ${HOME}/backups

#copy main db

# get latest daily file

latest=$(ls -t daily.sql.* | head -1 )
gunzip -c "$latest" > $sqltemp;

mysql -hdb158.pair.com -udigitalm_6 -p${PWdev} digitalm_f2dev < $sqltemp 2>/dev/null

echo "Dev db restored from $latest";
rm $sqltemp



