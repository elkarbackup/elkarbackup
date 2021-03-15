@echo off
SET RSYNC_CONF=%ProgramFiles% (x86)\ICW\rsyncd.conf
>> "%RSYNC_CONF%" echo.
>> "%RSYNC_CONF%" echo.
>> "%RSYNC_CONF%" echo.
>> "%RSYNC_CONF%" echo # Phony modules to trigger snapshot creation and mounting
>> "%RSYNC_CONF%" echo [MakeSnapshotCMountB]
>> "%RSYNC_CONF%" echo path = /cygdrive/c/ElkarBackup/token
>> "%RSYNC_CONF%" echo read only = true
>> "%RSYNC_CONF%" echo transfer logging = yes
>> "%RSYNC_CONF%" echo pre-xfer exec = /cygdrive/c/ElkarBackup/MakeSnapshotCMountB.cmd
>> "%RSYNC_CONF%" echo.
>> "%RSYNC_CONF%" echo [DeleteSnapshotCUmountB]
>> "%RSYNC_CONF%" echo path = /cygdrive/c/ElkarBackup/token
>> "%RSYNC_CONF%" echo read only = true
>> "%RSYNC_CONF%" echo transfer logging = yes
>> "%RSYNC_CONF%" echo pre-xfer exec = /cygdrive/c/ElkarBackup/DeleteSnapshotCUmountB.cmd
>> "%RSYNC_CONF%" echo.
>> "%RSYNC_CONF%" echo ### WARNING: the following module WILL NOT WORK as expected
>> "%RSYNC_CONF%" echo ### the reason is that rsync tries to chdir to SomeDirectory before running the pre-xfer script. Since the B: unit does not exit it fails
>> "%RSYNC_CONF%" echo ### [ShadowWithSnapshot]
>> "%RSYNC_CONF%" echo ### path = /cygdrive/b/SomeDirectory
>> "%RSYNC_CONF%" echo ### read only = true
>> "%RSYNC_CONF%" echo ### transfer logging = yes
>> "%RSYNC_CONF%" echo ### pre-xfer  exec = /cygdrive/c/ElkarBackup/MakeSnapshotCMountB.cmd
>> "%RSYNC_CONF%" echo ### post-xfer exec = /cygdrive/c/ElkarBackup/DeleteSnapshotCUmountB.cmd
echo Triggers to take snapshots of C:\ and mount them as B:\ created. Check the "%RSYNC_CONF%" file.
echo You will have to download vss.exe from http://www.dmares.com/pub/nt_32/vss.exe for this to work.
pause
