#!/usr/bin/env bash

DIR=$(dirname $(readlink -f "$0"))

docker build -t elkarbackup/debpkg "$DIR"

docker run --rm \
	   -v "$DIR"/../..:/data/elkarbackup \
	   -v "$DIR":/export \
		 -e UID=$(id -u) \
		 -e GID=$(id -g) \
		 elkarbackup/debpkg