#!/bin/bash
## copies  assets and news from production to dev, renames as live_news, etc.
## in prep for running gen_pubs and fix assets



PWlive=STjzyHFr
PWdev=fXFjb9ED
PWmac=milstd883

sqltemp=/tmp/f2temp.sql



if [[ 1 ]] ; then
	#mac settings

	if [[ ! -f $sqltemp ]] ; then
	mysqldump -hlocalhost -uadmin -p${PWmac} f2 assets news_items > $sqltemp 2>/dev/null
	fi

	mysql -hlocalhost -uadmin -p${PWmac} f2dev < $sqltemp 2>/dev/null
	mysql -hlocalhost -uadmin -p${PWmac} f2dev  <<EOT
	DROP TABLE IF EXISTS live_assets;
	RENAME TABLE assets TO live_assets;
	DROP TABLE IF EXISTS assets;

	DROP TABLE IF EXISTS live_news;
	RENAME TABLE news_items TO live_news;
	DROP TABLE IF EXISTS news_items;
EOT
else
	if [[ ! -f $sqltemp ]] ; then
	mysqldump -hdb151d.pair.com -udigitalm_r -p${PWlive} digitalm_db1 assets news_items > $sqltemp 2>/dev/null
	fi

	mysql -hdb158.pair.com -udigitalm_6 -p${PWdev} digitalm_f2dev < $sqltemp 2>/dev/null
	mysql -hdb158.pair.com -udigitalm_6 -p${PWdev} digitalm_f2dev <<EOT
	DROP TABLE IF EXISTS live_assets;
	RENAME TABLE assets TO live_assets;
	DROP TABLE IF EXISTS assets;

	DROP TABLE IF EXISTS live_news;
	RENAME TABLE news_items TO live_news;
	DROP TABLE IF EXISTS news_items;
EOT

fi

#rm $sqltemp



