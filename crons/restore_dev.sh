#!/bin/bash
#reloads the f2-dev db from the main db backup
#run daily or on demand


PATH=/bin:/usr/bin:/usr/local/bin
HOME=/usr/home/digitalm
PWpro=STjzyHFr
PWdev=fXFjb9ED


cd ${HOME}/backups

#copy main db

# get latest daily file

latest=$(ls -t daily.sql.* | head -1 )
gunzip -c "$latest" > temp.sql;

mysql -hdb158.pair.com -udigitalm_6 -p${PWdev} digitalm_f2dev < tmp.sql
echo "Restored from $latest";


