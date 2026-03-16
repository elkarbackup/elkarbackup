#! /usr/bin/env bash
set -e

docker-compose down -v
docker-compose -f docker-compose.tests.yml build
docker-compose -f docker-compose.tests.yml up --abort-on-container-exit --exit-code-from php
