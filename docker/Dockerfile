##
## Bootstrap & Build elkarbackup
##

FROM php:7.4-cli-alpine

WORKDIR /app/elkarbackup

RUN apk add --no-cache \
      git \
      curl \
      grep \
      mysql-client \
      acl \
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

# Set environment variable to allow Composer to run as root (inside Docker)
ENV COMPOSER_ALLOW_SUPERUSER=1

ENV DOCKERDIR=./docker

## Composer install
COPY composer.json composer.lock .
COPY config/parameters.yaml.dist /app/elkarbackup/config/parameters.yaml.dist
COPY $DOCKERDIR/parameters.yaml.docker /app/elkarbackup/config/parameters.yaml
RUN set -ex && \
      cd /app/elkarbackup && \
      mkdir -p public/css public/fonts public/js public/js/bootstrap public/js/moment public/js/jquery && \
      composer update --no-interaction --no-audit

## Composer install
COPY composer.json composer.lock .
COPY config/parameters.yaml.dist config/
RUN set -ex && \
      cd /app/elkarbackup && \
      mkdir -p public/css public/fonts public/js public/js/bootstrap public/js/moment public/js/jquery && \
      composer update --no-interaction --no-audit

## Copy Elkarbackup from this repo
COPY . /app/elkarbackup
RUN set -ex; \
      cd /app/elkarbackup; \
      mkdir -p var/cache var/sessions var/log; \
      # This doctrine migration file was provoking an error as it was trying to
      # copy a file to a hardcoded path it does not exist in this docker image.
      # We can disable it without negative consequences
      rm migrations/Version20130306101349.php;

## Custom parameters.yml template with envars
COPY $DOCKERDIR/parameters.yaml.docker /app/elkarbackup/config/parameters.yaml.dist

## Custom monolog.yaml (to log to stderr)
COPY $DOCKERDIR/monolog.yaml.docker /app/elkarbackup/config/packages/prod/monolog.yaml

## Disable "Manage parameters" menu item
RUN sed -i \
      "/array([[:space:]]*'label'[[:space:]]*=>[[:space:]]*\$t->trans('Manage parameters'/,/),/d" \
      /app/elkarbackup/src/Menu/Builder.php


##
## PHP Apache image with Elkarbackup
##


FROM php:7.4-apache-bullseye
RUN apt-get update && apt-get install -y \
      default-mysql-client \
      acl \
      sudo \
      git \
      zip \
      rsync \
      perl \
      libconfig-inifiles-perl \
      liblchown-perl \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install \
      pdo_mysql \
      pcntl

# Download and install diskus
RUN curl -sL -o diskus.deb \
    https://github.com/sharkdp/diskus/releases/download/v0.8.0/diskus_0.8.0_$(dpkg --print-architecture).deb && \
    dpkg -i diskus.deb && \
    rm -f diskus.deb

# Install rsnapshot from source (not available in bullseye repos)
RUN apt-get update && apt-get install -y autoconf automake \
    && cd /tmp \
    && git clone https://github.com/rsnapshot/rsnapshot.git \
    && cd rsnapshot \
    && ./autogen.sh \
    && ./configure --sysconfdir=/etc \
    && make install \
    && rm -rf /tmp/rsnapshot \
    && apt-get purge -y autoconf automake \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/*

# Download and install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
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
RUN ln -sf /dev/stdout /var/log/output.log

## Set timezone
RUN ln -snf /usr/share/zoneinfo/Europe/Paris /etc/localtime && echo "Europe/Paris" > /etc/timezone
RUN printf '[PHP]\ndate.timezone = "Europe/Paris"\n' > /usr/local/etc/php/conf.d/tzone.ini

COPY $DOCKERDIR/entrypoint.sh /
COPY $DOCKERDIR/envars.sh /

WORKDIR /app/elkarbackup

COPY --from=0 /app /app

RUN chmod u+x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD []

VOLUME /app/backups
EXPOSE 80

LABEL maintainer="Xabi Ezpeleta <xezpeleta@gmail.com>"
LABEL org.opencontainers.image.description="Elkarbackup Container Image"
LABEL org.opencontainers.image.source=https://github.com/elkarbackup/elkarbackup
LABEL org.opencontainers.image.url=https://github.com/elkarbackup/elkarbackup/blob/master/docker/README.md
LABEL org.opencontainers.image.documentation=https://github.com/elkarbackup/elkarbackup/blob/master/docker/README.md
