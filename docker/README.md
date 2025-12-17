# ElkarBackup

## Images
An overview of all container images is available at https://github.com/elkarbackup/elkarbackup/pkgs/container/elkarbackup

## How to use this image

```sh
$ docker run --name my-elkarbackup --link mariadb:lts -d ghcr.io/elkarbackup/elkarbackup:latest
```

## Hosted on GitHub Container Registry

- GitHub image is at `ghcr.io/elkarbackup/elkarbackup:<version>`

### Where to store data

Docker container does not come with persistent storage. However, there are
several ways to store data in the host machine. We encourage users to
familiarize themselves with the [options available](https://docs.docker.com/storage/).

Below you have the directories you might want to persist:

| path         | description                       |
| ------------ | --------------------------------- |
| /app/backups | Default backup storage directory. |
| /app/uploads | Pre and post scripts.             |
| /app/.ssh    | SSH keys.                         |

The automatically generated SSH key ElkarBackup uses to connect to remote machines can be found in the /app/.ssh/id_rsa.pub file which would be mounted on your host.

### ... via `docker-compose`

You can use **Docker Compose** to easily run ElkarBackup in an isolated environment built with Docker containers:

**docker-compose.yml**

```yaml
---
services:
  elkarbackup:
    image: ghcr.io/elkarbackup/elkarbackup:latest
    environment:
      SYMFONY__DATABASE__PASSWORD: "your-password-here"
      EB_CRON: "enabled"
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:80"]
      timeout: 2s
      interval: 30s
      start_period: 60s
    volumes:
      - backups:/app/backups
      - uploads:/app/uploads
      - sshkeys:/app/.ssh
    ports:
      - 8000:80
    requires:
      - db

  db:
    image: mariadb:lts
    environment:
      MYSQL_ROOT_PASSWORD: "your-password-here"
    volumes:
      - db:/var/lib/mysql

volumes:
  db:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: ./mariadb
  backups:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: ./backups
  uploads:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: ./uploads
  sshkeys:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: ./sshkeys
```

Run `docker-compose up`, wait for it to initialize completely, and go the address:

- http://localhost:8000

## Environment variables

The following environment variables are also honored for configuring your ElkarBackup instance:

### General

| name    | default value | description                      |
| ------- | ------------- | -------------------------------- |
| TZ      | Europe/Paris  | Timezone                         |
| PHP_TZ  | Europe/Paris  | Timezone (PHP)                   |
| EB_ACL  | enabled       | use setfacl, otherwise use chown |
| EB_CRON | enabled       | run tick command periodically    |

### Database configuration

| name                        | default value | description      |
| --------------------------- | ------------- | ---------------- |
| SYMFONY__DATABASE__DRIVER   | pdo_mysql     | driver           |
| SYMFONY__DATABASE__PATH     | null          | db path (sqlite) |
| SYMFONY__DATABASE__HOST     | db            | db host          |
| SYMFONY__DATABASE__PORT     | 3306          | db port          |
| SYMFONY__DATABASE__NAME     | elkarbackup   | db name          |
| SYMFONY__DATABASE__USER     | root          | db user          |
| SYMFONY__DATABASE__PASSWORD | root          | db password      |

### Mailer configuration

| name                       | default value | description  |
| -------------------------- | ------------- | ------------ |
| SYMFONY__MAILER__TRANSPORT | smtp          | transport    |
| SYMFONY__MAILER__HOST      | localhost     | host         |
| SYMFONY__MAILER__USER      | null          | user         |
| SYMFONY__MAILER__PASSWORD  | null          | password     |
| SYMFONY__MAILER__FROM      | null          | from address |

### Elkarbackup configuration

| name                             | default value                  | description                                          |
| -------------------------------- | ------------------------------ | ---------------------------------------------------- |
| SYMFONY__EB__SECRET              | random value will be generated | framework secret                                     |
| SYMFONY__EB__UPLOAD__DIR         | /app/uploads                   | scripts directory                                    |
| SYMFONY__EB__BACKUP__DIR         | /app/backups                   | backups directory                                    |
| SYMFONY__EB__TMP__DIR            | /app/tmp                       | tmp directory                                        |
| SYMFONY__EB__URL__PREFIX         | null                           | url path prefix (i.e. /elkarbackup)                  |
| SYMFONY__EB__PUBLIC__KEY         | /app/.ssh/id_rsa.pub           | ssh public key path                                  |
| SYMFONY__EB__MAX__PARALLEL__JOBS | 1                              | max parallel jobs                                    |
| SYMFONY__EB__POST__ON__PRE__FAIL | true                           | post on pre fail                                     |
| SYMFONY__EB__DISK_USAGE_ENABLED  | true                           | Run diskUsage statistics after job completeion       |
