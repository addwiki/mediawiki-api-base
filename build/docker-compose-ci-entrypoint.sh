#!/bin/bash

set -x

# Wait for the DB to be ready?
/wait-for-it.sh $MYSQL_SERVER:3306 -t 300
sleep 1
/wait-for-it.sh $MYSQL_SERVER:3306 -t 300

# Install MediaWiki
php maintenance/install.php --server="http://localhost:8877" --scriptpath= --dbtype mysql --dbuser $MYSQL_USER --dbpass $MYSQL_PASSWORD --dbserver $MYSQL_SERVER --lang en --dbname $MYSQL_DATABASE --pass LongCIPass123 SiteName CIUser

# Settings for extensions
echo "wfLoadExtension( 'OAuth' );" >> LocalSettings.php
echo "\$wgGroupPermissions['sysop']['mwoauthproposeconsumer'] = true;" >> LocalSettings.php
echo "\$wgGroupPermissions['sysop']['mwoauthmanageconsumer'] = true;" >> LocalSettings.php
echo "\$wgGroupPermissions['sysop']['mwoauthviewprivate'] = true;" >> LocalSettings.php
echo "\$wgGroupPermissions['sysop']['mwoauthupdateownconsumer'] = true;" >> LocalSettings.php

# Update MediaWiki & Extensions
php maintenance/update.php --quick

## Run some needed scripts
# Add an OAuth Consumer
php maintenance/resetUserEmail.php --no-reset-password CIUser CIUser@addwiki.github.io
if [ ! -f createOAuthConsumer.json ]; then
    php extensions/OAuth/maintenance/addwikiAddOauth.php --approve --callbackUrl https://CiConsumerUrl \
    --callbackIsPrefix true --user CIUser --name CIConsumer --description CIConsumer --version 1.1.0 \
    --grants highvolume --jsonOnSuccess > createOAuthConsumer.json
fi


# Run apache
apache2-foreground
