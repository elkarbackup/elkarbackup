#! /bin/bash
set -e

# Volumes:
#     You can mount your local Elkarbackup directory with:
#                       -v $(pwd)/../..:/data/elkarbackup
#
# Environment variables:
# 
# $GIT_REPO
#     If the "/data/elkarbackup" directory it's empty, it will clone the code from
#     https://github.com/elkarbackup/elkarbackup.git
#		You can use this envar to specify your own Elkarbackup clone
#		repository URL
#		Add "-b <branch>" if you want to select a custom branch. Example:
#			REPO="https://github.com/xezpeleta/elkarbackup.git -b fix-issue-79"

DATA_DIR="/data/elkarbackup"
EXPORT_DIR="/export"

mkdir -p "$DATA_DIR" && cd "$DATA_DIR/.."

# Select version
if [ -z "$GIT_REPO" ];then
	if [ -d "$DATA_DIR/.git" ];then
		echo "Detected (ElkarBackup?) Git repository. Trying to build deb package."
	else
		GIT_REPO="https://github.com/elkarbackup/elkarbackup.git"
		echo "Version not specified. Using current Elkarbackup git repo: $GIT_REPO"
		echo "Git clone..."
		git clone $GIT_REPO
	fi
else
	echo "Selected git repo: $GIT_REPO"
	echo "Git clone..."
	git clone $GIT_REPO
fi

cd $DATA_DIR
./bootstrap.sh
./makepackage.sh

DEB_FILE=`ls *deb`

mkdir -p "$EXPORT_DIR/build"
mv "$DEB_FILE" "$EXPORT_DIR/build/"

## Set correct permissions
if [ ! -z "$UID" ];
then
	chown -R "$UID" "$EXPORT_DIR/build"
fi

if [ ! -z "$GID" ];
then
	chgrp -R "$GID" "$EXPORT_DIR/build"
fi
