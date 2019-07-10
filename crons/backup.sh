#!/bin/bash
#creates backup and then deletes anything except 2 most recent
#new daily sql dump, then remove oldest

#see if its a Wed make weekly backup from oldest daily)
day=`date +%u`
cd /usr/home/digitalm/backups

#on Wed, copy newest daily to weekly
if [ "$day" = 3 ]; then
    ls -tpr daily.sql.* | head -n 1 | xargs -r -d '\n' rename 's/daily/weekly/' --  
    ls -tpr daily.scripts.* | head -n 1 | xargs -r -d '\n' rename 's/daily/weekly/' --   
	ls -tpr daily.web.* | head -n 1 | xargs -r -d '\n' rename 's/daily/weekly/' --   
fi


#daily backups
/usr/local/bin/mysqldump -hdb151d.pair.com -udigitalm_r -pSTjzyHFr digitalm_db1 > daily.sql.`/bin/date +\%Y\%m\%d`.sql

/usr/bin/tar -czf /usr/home/digitalm/backups/daily.scripts.`/bin/date +\%Y\%m\%d`.tar.gz /usr/home/digitalm/public_html/amdflames.org/scripts

/usr/bin/tar -czf /usr/home/digitalm/backups/daily.web.`/bin/date +\%Y\%m\%d`.tar.gz /usr/home/digitalm/public_html/amdflames.org

#remove older files leaving 1 less than +n
ls -tp1 daily.sql.* | tail -n +4 |  xargs -r -d '\n' rm --
ls -tp1 daily.scripts.* | tail -n +4 |  xargs -r -d '\n' rm --
ls -tp1 daily.web.* | tail -n +2 |  xargs -r -d '\n' rm --

ls -tp1 weekly.sql.* | tail -n +3 |  xargs -r -d '\n' rm --
ls -tp1 weekly.scripts.* | tail -n +4 |  xargs -r -d '\n' rm --
ls -tp1 weekly.web.* | tail -n +2 |  xargs -r -d '\n' rm --


#clean up old logs and mailings
for dir in public_html/amdflames.org/logs  bmail
 do
find /usr/home/digitalm/$dir/ -type f -mtime +30 -delete;
#find /usr/home/digitalm/$dir/ -type d -empty -delete;
#ls  /usr/home/digitalm/public_html/amdflames.org/$dir ;

done

#comment below heree
:<<END


END
