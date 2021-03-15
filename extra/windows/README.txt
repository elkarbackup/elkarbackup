Description
===========

This directory contains the scripts for rsync which make the creation
of snapshots on demand easy.

VSS
===

http://www.maresware.com/

Download from: http://www.dmares.com/pub/nt_32/vss.exe

Install
=======

ICWRsync must be installed in the default location for the installer
to work.

Copy this directory to c:\Elkarbackup and double click on INSTALL.bat

Uninstall
=========

Edit the rsyncd.conf configuration file and remove the lines which
call MakeSnapshotCMountB.cmd and DeleteSnapshotCUmountB.cmd

How it works
============

This scripts make use of the rsync's pre-xfer trigger. The installer
creates two modules with the following names and functionality.

    - MakeSnapshotCMountB: creates a shadow copy of the C:\ unit and
      mounts it as the B:\ unit. Only the rsync daemon can see the B:\
      unit. If the B:\ unit is mounted it first tries to unmount
      it. Calls the MakeSnapshotCMountB.cmd as pre-xfer script to
      this.

    - DeleteSnapshotCUmountB: unmounts the B:\ unit and deletes the
      last snapshot created by a call to the MakeSnapshotCMountB
      module. Calls the DeleteSnapshotCUmountB.cmd as pre-xfer script
      to this.

You are expected to use the provided
TriggerSnapshotGenerateOrDelete.sh script to trigger snapshot
generation and deletion.

You might be tempted to create a module with the following
configuration:

    [ShadowWithSnapshot]
    path = /cygdrive/b/SomeDirectory
    read only = true
    transfer logging = yes
    pre-xfer  exec = /cygdrive/c/ElkarBackup/MakeSnapshotCMountB.cmd
    post-xfer exec = /cygdrive/c/ElkarBackup/DeleteSnapshotCUmountB.cmd

IT WILL NOT WORK. Prior to the execution of the pre-xfer script rsync
tries to chdir to the path (/cygdrive/b/SomeDirectory in this
case). Since B:\ is not yet mounted this chdir fails and thus
MakeSnapshotCMountB.cmd never gets a chance to run.
