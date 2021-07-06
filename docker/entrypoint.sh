#! /bin/bash

source /envars.sh

EB_DIR=/app/elkarbackup

if [ -z "$APP_ENV" ];then
  export APP_ENV=prod
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

# Run commands
if [ ! -z "$1" ]; then
  command="$@"
  echo "Command: $command"
  cd "${EB_DIR}" && $command
  exit $?
fi

# Check database connection
until mysqladmin ping -h "${SYMFONY__DATABASE__HOST}" --silent; do
  >&2 echo "MySQL is unavailable - sleeping"
  sleep 1
done

cd "${EB_DIR}"

# Create/update database
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
# Create admin user
php bin/console elkarbackup:create_admin

# Set permissions
setfacl -R -m u:www-data:rwX var/cache var/sessions var/log
setfacl -dR -m u:www-data:rwX var/cache var/sessions var/log

if [ ! -z "$SYMFONY__EB__PUBLIC__KEY" ] && [ ! -f "$SYMFONY__EB__PUBLIC__KEY" ];then
  ssh-keygen -t rsa -N "" -C "Web requested key for elkarbackup." -f "${SYMFONY__EB__PUBLIC__KEY%.*}";
fi

# Empty sessions
rm -rf var/sessions/*
rm -rf var/cache/*

# Clear cache and sessions..
php bin/console cache:clear
php bin/console assets:install

apache2-foreground &

### Force tick execution and set permissions (again)
php bin/console elkarbackup:tick --env=prod > /var/log/output.log
setfacl -R -m u:www-data:rwX var/cache var/sessions var/log
setfacl -dR -m u:www-data:rwX var/cache var/sessions var/log

if [ ! -z "$ELKARBACKUP_RUN_TEST" ]; then
  ./run-tests.sh
  exit $?
fi

# Cron (enabled by default)
if [ -z "${EB_CRON}" ] || [ "${EB_CRON}" = "enabled" ]; then
  echo -e "\n\nEB_CRON is enabled. Running tick command every minute..."
  while true; do
    php bin/console elkarbackup:tick --env=prod &>/var/log/output.log &
    sleep 60
  done
else
  # Keep apache alive
  tail -f /dev/null
fi