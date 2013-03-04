@echo off
nssm install Elkarbackup cscript /nologo c:\Elkarbackup\snapshot.vbs /command:RunServer /port:11321 /allow:192.168.122.1/0 /volume:c:\ /symlink:c:\ElkarBackup\snapshot
net start Elkarbackup
pause
