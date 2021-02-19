##
## Bootstrap & Build elkarbackup
##

FROM php:7.3-cli-alpine

RUN apk add --no-cache \
      git \
      curl \
      grep \
      mysql-client \
      acl \
      rsnapshot \
    && docker-php-ext-install \
      pdo_mysql \
      pcntl

# Prepare default directories
RUN mkdir -p -m 777 \
      /app/elkarbackup \
      /app/uploads \
      /app/backups \
      /app/tmp \
      /app/.ssh

# Download and install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


ENV DOCKERDIR=./docker/

## Copy Elkarbackup from this repo
COPY . /app/elkarbackup
RUN set -ex; \
      cd /app/elkarbackup; \
      rm app/config/parameters.yml; \
      mkdir -p var/cache var/sessions var/logs; \
      # This doctrine migration file was provoking an error as it was trying to
      # copy a file to a hardcoded path it does not exist in this docker image.
      # We can disable it without negative consequences
      rm app/DoctrineMigrations/Version20130306101349.php;
      
## Download jquery
ENV JQUERY_VERSION 1.12.0
ENV JQUERY_SHA256 5f1ab65fe2ad6b381a1ae036716475bf78c9b2e309528cf22170c1ddeefddcbf
RUN set -ex; \
      cd /app/elkarbackup; \
      mkdir -p web/js/jquery && cd web/js/jquery; \
      curl -o jquery-"${JQUERY_VERSION}".min.js "http://code.jquery.com/jquery-${JQUERY_VERSION}.min.js"; \
      echo "${JQUERY_SHA256}  jquery-${JQUERY_VERSION}.min.js" | sha256sum -c -;

## Download Dojo
ENV DOJO_VERSION 1.8.14
ENV DOJO_SHA256 2023c8c8c8722b4c63976b7a269e20bda2fa09010706aef10b3821be81691727
RUN set -ex; \
      cd /app/elkarbackup; \
      mkdir -p web/js && cd web/js; \
      curl -o dojo.tar.gz "https://download.dojotoolkit.org/release-${DOJO_VERSION}/dojo-release-${DOJO_VERSION}.tar.gz"; \
      echo "${DOJO_SHA256}  dojo.tar.gz" | sha256sum -c -; \
      tar -xzf dojo.tar.gz && rm dojo.tar.gz;


## Custom composer.json (database not required)
COPY $DOCKERDIR/composer.json.docker /app/elkarbackup/composer.json

## Custom parameters.yml template with envars
COPY $DOCKERDIR/parameters.yml.docker /app/elkarbackup/app/config/parameters.yml.dist

## Custom config.yml (to log to stderr)
COPY $DOCKERDIR/config.yml.docker /app/elkarbackup/app/config/config.yml

## Custom app.php and app_dev.php (fixes env variables bug)
COPY $DOCKERDIR/app.php /app/elkarbackup/web/app.php
COPY $DOCKERDIR/app_dev.php /app/elkarbackup/web/app_dev.php

## Disable "Manage parameters" menu item
COPY $DOCKERDIR/src/Builder.php /app/elkarbackup/src/Binovo/ElkarBackupBundle/Menu/Builder.php

COPY $DOCKERDIR/envars.sh /app/elkarbackup/app/envars.sh
## Composer install
RUN set -ex; \
      cd /app/elkarbackup; \
      source app/envars.sh; \
      composer install --no-interaction


##
## PHP Apache image with Elkarbackup
##


FROM php:7.3-apache
RUN apt-get update && apt-get install -y \
      default-mysql-client \
      acl \
      rsnapshot \
      sudo \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install \
      pdo_mysql \
      pcntl

COPY --from=0 /app /app

ENV DOCKERDIR=./docker/

# Apache configuration
COPY $DOCKERDIR/elkarbackup.conf /etc/apache2/sites-available
RUN a2enmod rewrite \
  && a2dissite 000-default \
  && a2dissite default-ssl \
  && a2ensite elkarbackup

# Sudoers for script execution with environment variables
RUN echo "Cmnd_Alias ELKARBACKUP_SCRIPTS=/app/uploads/*" >> /etc/sudoers.d/elkarbackup
RUN echo "Defaults!ELKARBACKUP_SCRIPTS env_keep += \"ELKARBACKUP_LEVEL ELKARBACKUP_EVENT ELKARBACKUP_URL ELKARBACKUP_ID ELKARBACKUP_PATH ELKARBACKUP_STATUS ELKARBACKUP_CLIENT_NAME ELKARBACKUP_JOB_NAME ELKARBACKUP_OWNER_EMAIL ELKARBACKUP_RECIPIENT_LIST ELKARBACKUP_CLIENT_TOTAL_SIZE ELKARBACKUP_JOB_TOTAL_SIZE ELKARBACKUP_JOB_RUN_SIZE ELKARBACKUP_CLIENT_STARTTIME ELKARBACKUP_CLIENT_ENDTIME ELKARBACKUP_JOB_STARTTIME ELKARBACKUP_JOB_ENDTIME ELKARBACKUP_SSH_ARGS\"" >> /etc/sudoers.d/elkarbackup
RUN echo "elkarbackup ALL = NOPASSWD: ELKARBACKUP_SCRIPTS" >> /etc/sudoers.d/elkarbackup

# Add SSH default key location
RUN echo "    IdentityFile /app/.ssh/id_rsa" >> /etc/ssh/ssh_config

# Console commands log output
RUN ln -sf /proc/1/fd/1 /var/log/output.log

## Set timezone
RUN ln -snf /usr/share/zoneinfo/Europe/Paris /etc/localtime && echo "Europe/Paris" > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "Europe/Paris"\n' > /usr/local/etc/php/conf.d/tzone.ini

COPY $DOCKERDIR/entrypoint.sh /
COPY $DOCKERDIR/envars.sh /
RUN chmod u+x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD []

VOLUME /app/backups
EXPOSE 80

LABEL maintainer="Xabi Ezpeleta <xezpeleta@gmail.com>"
