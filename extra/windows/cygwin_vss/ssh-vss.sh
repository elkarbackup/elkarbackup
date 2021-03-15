#!/bin/bash
set -e

#
# Called  as client  pre script  makes an  snapshot of  the configured
# volume. Called  as client post  script removes the  snapshot. Useful
# for backing up files that might be in use, in particular databases.
#
# Tested for the following Windows versions:
#
#  - Windows 2008 R2
#
# This  script  expects  to  have a  c:\Elkarbackup\snapshot.vbs  file
# (available in the distributions) on  the windows side and ssh access
# configured. Using  the default configuration it  creates an snapshot
# of c: and makes it available under C:/Elkarbackup/snapshot_C.
#
# In order  to workaround the limitations of  cygwin regarding windows
# links and  the effect of  --relative used by rsnapshot  when calling
# rsync the path  MUST contain a '/./' element.   For example the, the
# following path on a task will work:
#
# /cygdrive/c/Elkarbackup/snapshot_C/Users/./Administrador/Documents
#
# but these won't:
#
# /cygdrive/c/Elkarbackup/snapshot_C/Users/Administrador/Documents
# /cygdrive/c/Elkarbackup/snapshot_C/./Users/Administrador/Documents
# /cygdrive/c/Elkarbackup/snapshot_C/
#
# The reason is twofold:
#
# On  the one  hand cygwin  currently  cannot cd  into the  snapshot's
# root. On  the other hand because  --relative is used  rsync tries to
# resolve  all the  symlinks  and when  fails  to resolve  snapshot_C,
# aborts. The /./  element of the path instructs rsync  to only try to
# resolve symlinks below that directory.
#

SNAPSHOT_VBS="C:/Elkarbackup/snapshot.vbs"
SNAPSHOT_VOLUME="C"
SNAPSHOT_LINK="C:/Elkarbackup/snapshot_$SNAPSHOT_VOLUME"
SNAPSHOT_FILE="/cygdrive/c/Elkarbackup/snapshotids.txt"

function make_snapshot() {
    SSH_URL=$(echo $ELKARBACKUP_URL | sed 's/:.*//')
    ssh -i $HOME/.ssh/id_rsa $SSH_URL "cscript /nologo $SNAPSHOT_VBS /command:CreateSnapshot /volume:$SNAPSHOT_VOLUME /symlink:$SNAPSHOT_LINK > $SNAPSHOT_FILE"
}

function destroy_snapshot() {
    SSH_URL=$(echo $ELKARBACKUP_URL | sed 's/:.*//')
    ssh -i $HOME/.ssh/id_rsa $SSH_URL "rm $SNAPSHOT_LINK; cscript /nologo $SNAPSHOT_VBS /command:DeleteSnapshot /snapshot:\$(cat $SNAPSHOT_FILE | tr -d '\n\r ')"
}

if [ "$ELKARBACKUP_LEVEL" != "CLIENT" ]
then
    echo "Only allowed at client level" >&2
    exit 1
fi
if [ "$ELKARBACKUP_EVENT" == "PRE" ]
then
    make_snapshot
else
    destroy_snapshot
fi
