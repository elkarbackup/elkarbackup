FROM php:7.4

COPY envars.sh /
COPY parameters.yaml.docker /
COPY entrypoint.sh /

RUN chmod +x /entrypoint.sh

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \        
        git \
        curl \
        acl \
        rsnapshot \
        gzip \
        zip \
        mariadb-client \
        fakeroot \
        lintian \
        && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
        pdo_mysql \
        pcntl

# Download composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Download Symfony binary
ENV SYMFONY_VERSION 4.23.4
ENV SYMFONY_SHA256 d9838643fd2cc9db495ebbf02785b8527494853da24cdd96ca78e049b8b85d09
RUN set -ex; \
      curl -o symfony.gz -L "https://github.com/symfony/cli/releases/download/v${SYMFONY_VERSION}/symfony_linux_amd64.gz"; \
      echo "${SYMFONY_SHA256}  symfony.gz" | sha256sum -c -; \
      gzip -d symfony.gz && chmod u+x symfony && mv symfony /usr/bin/symfony;

# Workaround: avoid Version20130306101349.php migration error
# TODO: fix the migration file in the codebase
RUN mkdir -p /usr/share/elkarbackup/extra/windows/ \
        && touch /usr/share/elkarbackup/extra/windows/TriggerSnapshotGenerateOrDelete.sh

WORKDIR /app/elkarbackup

ENTRYPOINT ["/entrypoint.sh"]
CMD []

EXPOSE 8000
