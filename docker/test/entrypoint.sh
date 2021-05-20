#! /bin/bash
set -e

source /envars.sh

EB_DIR=/app/elkarbackup

if [ -z "$APP_ENV" ];then
  export APP_ENV=test
fi

## = Set timezone =
## Only if TZ or PHP_TZ is set
## (PHP_TZ defaults to TZ)

if [ ! -z "$TZ" ];then
  ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
  
  if [ -z "$PHP_TZ" ];then
    PHP_TZ="$TZ"
  fi
fi

if [ ! -z "$PHP_TZ" ];then
  printf "[PHP]\ndate.timezone = ${PHP_TZ}\n" > /usr/local/etc/php/conf.d/tzone.ini
fi

# Check database connection
until mysqladmin ping -h "${SYMFONY__DATABASE__HOST}" --silent; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 1
done

cd "${EB_DIR}"

# Prepare required directories
mkdir -p -m 777 \
	/app/elkarbackup \
	/app/uploads \
      	/app/backups \
      	/app/tmp \
      	/app/.ssh

# Workaround for migration issues
mkdir -p /var/spool/elkarbackup/uploads
mkdir -p /usr/share/elkarbackup/extra/windows
touch /usr/share/elkarbackup/extra/windows/TriggerSnapshotGenerateOrDelete.sh

# Install dependencies
DEBIAN_FRONTEND=noninteractive
apt-get update && apt-get -y install \
	sudo \
	git \
	zip

# Download composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Composer install
composer install

# Run tests
echo "=============================================="
echo "              ELKARBACKUP TESTS               "
echo "=============================================="

./run-tests.sh

