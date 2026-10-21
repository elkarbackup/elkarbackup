#!/bin/bash

set -e

export APP_ENV=test
# symfony/phpunit-bridge reads this at Composer autoload time, before .env.test
# is loaded, so it must be in the process environment to take effect.
export SYMFONY_DEPRECATIONS_HELPER="${SYMFONY_DEPRECATIONS_HELPER:-999999}"
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "Database setup"
$DIR/bin/console doctrine:database:drop --force || /bin/true
$DIR/bin/console doctrine:database:create
$DIR/bin/console doctrine:migrations:migrate --no-interaction

echo "General setup"
sudo --preserve-env $DIR/bin/console elkarbackup:create_admin 
mkdir -p /tmp/elkarbackup-tests/uploads
$DIR/bin/console hautelook:fixtures:load --append

echo "Running unit tests"
$DIR/vendor/bin/phpunit "${@:1}"
