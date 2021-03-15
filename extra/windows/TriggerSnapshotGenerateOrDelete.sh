#!/bin/bash

PORT=11321
INTERVAL=10
EXIT_STATUS=1
if [ "$ELKARBACKUP_LEVEL" != "CLIENT" ]
then
    echo "Only allowed at client level" >&2
    exit 1
fi
if [ "$ELKARBACKUP_EVENT" == "PRE" ]
then
    MODULE=MakeSnapshotCMountB
else
    MODULE=DeleteSnapshotCUmountB
fi
SERVER=${ELKARBACKUP_URL#*@}
SERVER=${SERVER%:*}
echo Running rsync $SERVER::$MODULE
rsync $SERVER::$MODULE
exit $?
