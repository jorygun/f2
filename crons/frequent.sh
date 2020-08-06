#!/bin/bash

#update live data frequently

# env settings?
export PATH="$PATH:/usr/local/bin";
cd "$HOME/Sites/flames/crons"
#echo "Starting frequent.sh at " $(date)

#REPO='beta'

php ./recent_articles.php -q

php ./recent_assets.php -q







