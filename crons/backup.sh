#!/bin/bash
#creates backup and then deletes anything except 2 most recent
#new daily sql dump, then remove oldest


PATH=/bin:/usr/bin:/usr/local/bin
HOME=/usr/home/digitalm
SITE=$HOME/Sites/flames/live
PW=STjzyHFr

#see if its a Wed make weekly backup from oldest daily)
day=`date +%u`
datecode=`date +\%Y\%m\%d`
#datecode = '000000'
cd $HOME/backups

#echo datecode $datecode on day $day in $(pwd).

#on Wed, copy newest daily to weekly
if [ "$day" = 3 ]; then
    ls -tpr ./daily.sql.* | head -n 1 | xargs -r -d '\n' rename 's/daily/weekly/' --  
    ls -tpr ./daily.site.* | head -n 1 | xargs -r -d '\n' rename 's/daily/weekly/' --    
fi


#daily backups
mysqldump -hdb151d.pair.com -udigitalm_r -p PW digitalm_db1 | gzip > daily.sql.$datecode.sql.gz


tar  -czf $HOME/backups/daily.site.$datecode.tar.gz --exclude=$SITE/vendor  $SITE


#remove older files leaving 1 less than +n
ls -tp1 daily.sql.* | tail -n +7 |  xargs -r -d '\n' rm --
ls -tp1 daily.site.* | tail -n +7 |  xargs -r -d '\n' rm --


ls -tp1 weekly.sql.* | tail -n +3 |  xargs -r -d '\n' rm --
ls -tp1 weekly.site.* | tail -n +3 |  xargs -r -d '\n' rm --


#clean up old logs and mailings
#for dir in public_html/amdflames.org/logs  bmail ; do
    #find /usr/home/digitalm/$dir/ -type f -mtime +30 -delete;
    #find /usr/home/digitalm/$dir/ -type d -empty -delete;
    #ls  /usr/home/digitalm/public_html/amdflames.org/$dir ;

#done


#comment below heree
:<<END


END
