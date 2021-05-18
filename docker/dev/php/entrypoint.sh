#! /bin/bash
set -e

source /envars.sh

EB_DIR=/app/elkarbackup

if [ -z "$APP_ENV" ];then
  export APP_ENV=dev
fi


# Create required directories
if [ ! -d "${SYMFONY__EB__UPLOAD__DIR}" ]; then
  mkdir -p "${SYMFONY__EB__UPLOAD__DIR}"
fi

if [ ! -d "${SYMFONY__EB__BACKUP__DIR}" ]; then
  mkdir -p "${SYMFONY__EB__BACKUP__DIR}"
fi

if [ ! -d "${SYMFONY__EB__TMP__DIR}" ]; then
  mkdir -p "${SYMFONY__EB__TMP__DIR}"
fi

if [ ! -d "$(dirname ${SYMFONY__EB__PUBLIC__KEY})" ]; then
  mkdir -p "$(dirname ${SYMFONY__EB__PUBLIC__KEY})"
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

## = Generate Symfony secret =
## Only if SYMFONY__SECRET has the default value

if [ ! -z "$SYMFONY__EB__SECRET" ] && [ "$SYMFONY__EB__SECRET" == "ThisTokenIsNotSoSecretChangeItElkarbackup" ];then
  SYMFONY__EB__SECRET=`tr -dc A-Za-z0-9 </dev/urandom | head -c 40`
fi

# Check database connection, enabled by default
# Set DATABASE_WAIT=false to disable it
if $DATABASE_WAIT; then
until mysqladmin ping -h "${SYMFONY__DATABASE__HOST}" --silent; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 1
done
fi

cd "${EB_DIR}"

# Let's use the Docker ready parameters.yaml (environment variables)
if [ ! -f "${EB_DIR}/config/parameters.yaml" ];then
  mv /parameters.yaml.docker "${EB_DIR}/config/parameters.yaml"
fi

# Run commands
# Examples using docker-compose:
#   - command: ./makepackage.sh (it will build deb package)
#   - command: ./run-tests.sh (it will run api tests)
if [ ! -z "$1" ]; then
  command="$@"
  echo "Command: $command"
  cd "${EB_DIR}" && $command
  exit $?
fi

##
## RUNNING ELKARBACKUP
##

# Composer install
composer install

# Create/update database
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
# Create admin user
php bin/console elkarbackup:create_admin

if [ ! -z "$SYMFONY__EB__PUBLIC__KEY" ] && [ ! -f "$SYMFONY__EB__PUBLIC__KEY" ];then
  ssh-keygen -t rsa -N "" -C "Web requested key for elkarbackup." -f "${SYMFONY__EB__PUBLIC__KEY%.*}";
fi

# Empty sessions
rm -rf var/sessions/*
rm -rf var/cache/*

# Clear cache and sessions..
php bin/console cache:clear
php bin/console assets:install

# Start debug server
symfony server:start --no-tls &

### Force tick execution and set permissions (again)
php bin/console elkarbackup:tick --env=prod > /var/log/output.log
#setfacl -R -m u:www-data:rwX var/cache var/sessions var/log
#setfacl -dR -m u:www-data:rwX var/cache var/sessions var/log

# Cron
if [ "${EB_CRON}" != "disabled" ]; then
  echo -e "\n\nEB_CRON is enabled. Running tick command every minute..."
  while true; do
    php bin/console elkarbackup:tick --env=prod &>/var/log/output.log &
    sleep 60
  done
fi
