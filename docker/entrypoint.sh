#! /bin/bash

source /envars.sh

EB_DIR=/app/elkarbackup

if [ -z "$SYMFONY_ENV" ];then
  export SYMFONY_ENV=prod
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
php app/console doctrine:database:create
php app/console doctrine:migrations:migrate --no-interaction
# Create admin user
php app/console elkarbackup:create_admin

# Set permissions
setfacl -R -m u:www-data:rwX app/cache app/sessions app/logs
setfacl -dR -m u:www-data:rwX app/cache app/sessions app/logs

if [ ! -z "$SYMFONY__EB__PUBLIC__KEY" ] && [ ! -f "$SYMFONY__EB__PUBLIC__KEY" ];then
  ssh-keygen -t rsa -N "" -C "Web requested key for elkarbackup." -f "${SYMFONY__EB__PUBLIC__KEY%.*}";
fi

# Clear cache and sessions, build assetics...
php app/console cache:clear
php app/console assetic:dump

# Empty sessions
rm -rf app/sessions/*
rm -rf app/cache/*

apache2-foreground &

### Force tick execution and set permissions (again)
php app/console elkarbackup:tick --env=prod > /var/log/output.log
setfacl -R -m u:www-data:rwX app/cache app/sessions app/logs
setfacl -dR -m u:www-data:rwX app/cache app/sessions app/logs

# Cron
if [ "${EB_CRON}" == "enabled" ]; then
  echo -e "\n\nEB_CRON is enabled. Running tick command every minute..."
  while true; do
    php app/console elkarbackup:tick --env=prod &>/var/log/output.log &
    sleep 60
  done
fi
