#! /usr/bin/env bash
set -e

docker-compose down -v
docker-compose -f docker-compose.build.yml build
docker-compose -f docker-compose.build.yml up
