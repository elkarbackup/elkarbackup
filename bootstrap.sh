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

composer install
