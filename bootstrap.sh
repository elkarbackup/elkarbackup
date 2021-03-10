#!/bin/bash
if [ "$(which composer)" == "" ]
then
    echo "Download and install composer"
    curl -s https://getcomposer.org/installer | php
    ln -s composer.phar composer
    export PATH=$PATH:$PWD
fi

mkdir -p var/cache
mkdir -p var/logs
mkdir -p var/sessions

HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
if [ -z "$HTTPDUSER" ];then
    # Apache not running, use default username "www-data"
    HTTPDUSER="www-data"
fi

setfacl  -R -m u:$HTTPDUSER:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx var/cache
setfacl -dR -m u:$HTTPDUSER:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx var/cache
setfacl  -R -m u:$HTTPDUSER:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx var/logs
setfacl -dR -m u:$HTTPDUSER:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx var/logs
setfacl  -R -m u:$HTTPDUSER:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx var/sessions
setfacl -dR -m u:$HTTPDUSER:rwx -m u:elkarbackup:rwx -m u:$(id -un):rwx var/sessions
composer install --no-interaction
