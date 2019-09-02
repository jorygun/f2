#!/bin/bash
#reloads the f2-dev db from the main db backup
#run daily or on demand


PATH=/bin:/usr/bin:/usr/local/bin
HOME=/usr/home/digitalm

cd ${HOME}/backups


# get latest daily file
latest=$(ls -t daily.sql.* | head -1 )
gunzip < "$latest" | mysql -udigitalm_6 -pfXFjb9ED digitalm_f2dev 
echo "Restored from $latest";


