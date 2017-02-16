#! /bin/bash

set -x

originalDirectory=$(pwd)

DBTYPE='mysql'

if [[ $TRAVIS_PHP_VERSION == *"hhvm"* ]]
then
	PHPINI=/etc/hhvm/php.ini
	echo "hhvm.enable_zend_compat = true" >> $PHPINI
fi

mkdir ./../web
cd ./../web

travis_retry wget https://github.com/wikimedia/mediawiki/archive/$MW.tar.gz
tar -zxf $MW.tar.gz
mv mediawiki-$MW w
ln -s ./w ./wiki
cd w

travis_retry composer self-update
travis_retry composer install

mysql -e 'CREATE DATABASE mediawiki;'
php maintenance/install.php --dbtype $DBTYPE --dbuser root --dbname mediawiki --dbpath $(pwd) --pass adminpass TravisWiki admin

cd $originalDirectory
