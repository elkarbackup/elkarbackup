@echo off
SET RSYNC_CONF=%ProgramFiles%\ICW\rsyncd.conf
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
