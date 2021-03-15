@echo off
C:\ElkarBackup\vss.exe B:
cscript /nologo C:\ElkarBackup\snapshot.vbs /command:DeleteSnapshot /snapshot:C:\ElkarBackup\ids.txt
del C:\ElkarBackup\ids.txt
