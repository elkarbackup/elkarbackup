#!/usr/bin/env bash

docker run --rm --name ebname \
	   -v $(pwd)/../..:/data/elkarbackup \
	   -v $(pwd):/export \
		 -e UID=$(id -u) \
		 -e GID=$(id -g) \
		 elkarbackup/deb:latest
