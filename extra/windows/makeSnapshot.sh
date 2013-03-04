#!/bin/bash

PORT=11321
INTERVAL=10
EXIT_STATUS=1
if [ "$ELKARBACKUP_LEVEL" != "CLIENT" ]
then
    echo "Only allowed at client level" >&2
    exit 1
fi
SERVER=${ELKARBACKUP_URL#*@}
SERVER=${SERVER%:*}
RESULT=$(echo "SNAPSHOT"|nc -i $INTERVAL $SERVER $PORT)
echo $RESULT
case $RESULT in
    OK*)
        EXIT_STATUS=0
        ;;
esac
exit $EXIT_STATUS
