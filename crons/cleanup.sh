#e!/bin/bash

#clean up old logs and mailings
repo=/usr/home/digitalm/Sites/flames/live
f2=/Users/john/Sites/flames/f2

if [ -e "$f2" ] ; then
	repo=$f2;
fi
echo "Repo is " $repo;

for dir in var/mono var/bulk_jobs var/logs ; do
    find $repo/$dir/ -type f -mtime +30 -delete;
    find $repo/$dir/ -type d -empty -delete;
    #ls  /usr/home/digitalm/public_html/amdflames.org/$dir ;

done


