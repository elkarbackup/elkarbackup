#!/bin/bash

set -e

export APP_ENV=test
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "Database setup"
$DIR/bin/console doctrine:database:drop --force || /bin/true
$DIR/bin/console doctrine:database:create
$DIR/bin/console doctrine:migrations:migrate --no-interaction
sudo --preserve-env $DIR/bin/console elkarbackup:create_admin 
mkdir -p /tmp/elkarbackup-tests/uploads
$DIR/bin/console hautelook:fixtures:load --append
$DIR/bin/phpunit "${@:1}"
