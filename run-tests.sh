#!/bin/bash

export APP_ENV=test
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

$DIR/bin/console doctrine:database:drop --force
$DIR/bin/console doctrine:database:create
$DIR/bin/console doctrine:migrations:migrate --no-interaction
$DIR/bin/console elkarbackup:create_admin 
mkdir -p /tmp/elkarbackup-tests/uploads
$DIR/bin/console hautelook:fixtures:load --append
$DIR/bin/phpunit "${@:1}"
