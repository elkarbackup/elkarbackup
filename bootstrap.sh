#!/bin/bash
if [ "$(which composer)" == "" ]
then
    echo "Download and install composer"
    curl -s https://getcomposer.org/installer | php
    ln -s composer.phar composer
    export PATH=$PATH:$PWD
fi
if [ ! -d web/js/dojo-release-1.8.1 ]
then
    echo "Download and install dojo 1.8.1"
    curl http://download.dojotoolkit.org/release-1.8.1/dojo-release-1.8.1.tar.gz | (cd web/js; tar zx)
fi

mkdir -p app/cache
mkdir -p app/logs
mkdir -p app/sessions
sudo setfacl  -R -m u:www-data:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx app/cache
sudo setfacl -dR -m u:www-data:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx app/cache
sudo setfacl  -R -m u:www-data:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx app/logs
sudo setfacl -dR -m u:www-data:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx app/logs
sudo setfacl  -R -m u:www-data:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx app/sessions
sudo setfacl -dR -m u:www-data:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx app/sessions
composer install --no-interaction
