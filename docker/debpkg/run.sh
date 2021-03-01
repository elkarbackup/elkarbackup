#!/usr/bin/env bash

docker build -t elkarbackup/debpkg .

docker run --rm \
	   -v $(pwd)/../..:/data/elkarbackup \
	   -v $(pwd):/export \
		 -e UID=$(id -u) \
		 -e GID=$(id -g) \
		 elkarbackup/debpkg
