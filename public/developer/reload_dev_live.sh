#!/bin/bash
## copies  assets and news from live to dev,
## in prep for running gen_pubs and fix assets on latest data
##
## When ready, those apps are run on live data in live/production site




PWlive=STjzyHFr
PWdev=fXFjb9ED
PWmac=milstd883

sqltemp=/tmp/f2temp.sql



if [[ $HOME == '/Users/john' ]] ; then
	SITEPATH=$HOME/Sites/flames/f2/public

	#mac settings
	echo "Updating mac dev db";

	if [[ ! -f $sqltemp ]] ; then
	/usr/local/mysql/bin/mysqldump -h "localhost" -u "admin" -p${PWmac} f2 assets news_items read_table > "$sqltemp"
	fi

	/usr/local/mysql/bin/mysql -h "localhost" -u "admin" -p${PWmac} f2dev < $"sqltemp"



#	mysql -hlocalhost -uadmin -p${PWmac} f2dev  <<EOT
# 	DROP TABLE IF EXISTS backup_assets;
# 	RENAME TABLE assets TO backup_assets;
# 	DROP TABLE IF EXISTS assets;
#
# 	DROP TABLE IF EXISTS backup_news;
# 	RENAME TABLE news_items TO backup_news;
# 	DROP TABLE IF EXISTS news_items;
#EOT
	echo "Mac Done."
else
	echo "Updating pair dev db";

	SITEPATHL=$HOME/Sites/flames/live/public
	SITEPATHD=$HOME/Sites/flames/dev/public


	if [[ ! -f $sqltemp ]] ; then
	/usr/local/bin/mysqldump -hdb151d.pair.com -udigitalm_r -p${PWlive} digitalm_db1 assets news_items read_table > $sqltemp 2>/dev/null
	fi

	/usr/local/bin/mysql -hdb158.pair.com -udigitalm_6 -p${PWdev} digitalm_f2dev < $sqltemp 2>/dev/null

	JSON="/news/current/news_index.json"
	echo "Starting copy of $JSON"

	echo $(/bin/ls -l "$SITEPATHL/$JSON")


	cp "$SITEPATHL/$JSON" "$SITEPATHD/$JSON";
	echo $(/bin/ls -l "$SITEPATHL/$JSON")

# 	mysql -hdb158.pair.com -udigitalm_6 -p${PWdev} digitalm_f2dev <<EOT
# 	DROP TABLE IF EXISTS backup_assets;
# 	RENAME TABLE assets TO backup_assets;
# 	DROP TABLE IF EXISTS assets;
#
# 	DROP TABLE IF EXISTS backup_news;
# 	RENAME TABLE news_items TO backup_news;
# 	DROP TABLE IF EXISTS news_items;
#EOT
	echo "Pair Done."
fi

rm $sqltemp


