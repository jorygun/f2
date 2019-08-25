#!/bin/bash

# for dir in 'logs'; do
#    
# 	find ~/public_html/amdflames.org/$dir/ -type f -mtime +30 -exec rm -f {}  \;
# 	find ~/public_html/amdflames.org/$dir/ -type d -empty -exec rmdir {} \;
# done
#!/bin/bash

for dir in logs 
do
 find /usr/home/digitalm/public_html/amdflames.org/$dir/ -type f -mtime +30  -exec rm -f {}  \;
 find /usr/home/digitalm/public_html/amdflames.org/$dir/ -type d -empty -exec rmdir {} \;
done
