## 1.3.2 (2019/11/6)
Bugfixes:
- Restore email notifications, deleted by error in v1.3 (#388)
- Disable "nothing to schedule" log messages. Add debug type messages. (#342)

## 1.3.1 (2018/10/16)
Bugfixes:
- Jobs remaining warning message (#328)
- Run all the retains for scheduled jobs (#326)
- Incorrect call to getEffectiveDir in Rsnapshotconfig (#323)
- ELKARBACKUP_URL envvar with incorrect value (#321)

## 1.3.0 (2018/10/01)
Features:
- Multiple backup locations (#273) --- Thanks @Igortxete7  :)
- Multiple backups at a time (#286) --- Thanks @Igortxete7 :)
- Added option to restore directly to a remote host (#301)
- Updated translations from Crowdin (#305)

Bugfixes:
- Restore filenames with non-ascii chars (#281)
- Fixed logrotate permission issue (#279)
- Update parameters template (#289)
- Username regular expression modified (#285)
- Tahoe variable misspelling fixed (#269)
- Fixed tahoe navar bug (#268)

## 1.2.7 (2017/12/20)
Bugfixes:
  - Download files without memory limit (#246)
  - Debug information in prod (#256)
  - Get job errors from log file (#257 #250 #221)

## 1.2.6 (2017/07/03)
Bugfixes:
  - Fix diskusage progress bar (#235)
  - Distinguish warning status with a different color (#234)
  - BackupRunningCommand: fix array error and limit the commands output (#233 #231 #227 #217)
  - Added universal script installer. Added sudo config (#226 #232)
  - Editing a user forces a password change (#210)
  - Changing an user email to another user's email gives 500 (#209)
  - Restore window: 404 error following a symlink (#159)
  - Cloning annoyance: wrong url (#216)
  - Change status from ABORTING to ABORTED if the lockfile has been deleted (#219 by @vnetmx)
  - DB name database not escaped for creation (#212)

## 1.2.5 (2017/01/13)
Features:
  - Added german translation (#185) by @nephilim75

Bugfixes:
  - Notifications: SMTP transport not working (#174)
  - PHP Notice: Undefined variable: job (#172)
  - Translate some missing strings (#185)

## 1.2.4 (2016/10/18)
Bugfixes:
  - PHP Warning: file_get_contents(/var/lib/elkarbackup/.ssh/authorized_keys) (#170)
  - PHP Warning: fopen(/etc/auto.master): failed to open stream (#169)
  - PHP Notice: Undefined variable: id (#168)
  - PHP Notice: Undefined index: children (#167)
  - Exception Undefined offset running TickCommand (#165)
  - Cient quota is saved wrong (#162)
  - Upload directory changed in sudoers (#160)
  - Jobs blocked in red-queued state in jobs list (#163)
  - Backup job status running after an exception warning (#166)

## 1.2.3 (2016/09/30)
Bugfixes:
  - Error 500 saving policy (fixes #161)

## 1.2.2 (2016/09/18)
Features:
  - Scripts: new environment variable PR #155 (by @jocker-x)

Bugfixes:
  - New Ubuntu 16.04 compatible deb package (fixes #150)

## 1.2.1 (2016/07/18)
Features:
  - Advanced client configuration and custom SSH args (fixes #87)
  - Client clonation feature (fixes #10)
  - Users with limited access (fixes #32)
  - HTTPS access enabled by default (fixes #98)
  - Tahoe storage support (by @adrianles)
  - New Symfony 1.8 LTS (until Nov. 2018)

Bugfixes:
  - Missing html tag (fixes #113)
  - Logger shows wrong message (fixes #114)

## 1.1.5 (2015/10/14)
Bugfixes:
  - Exclude not working (#95)
  - Client pagination bug (#94)

## 1.1.4 (2015/05/06)
Features:
  - Download directory in ZIP format (#74)
  - Edit client: remove multiple job edition (#37)
  - Parameters: added disable_background (#79)
  - Scripts: new environment variables available (#22 #30 #80)
  - Show warning on job status when the script fails (#4)
  - Policy edition: improve error messages (#86)
  - Support for custom SSH args (#87)
  - Check rsnapshot version and show warning (#88)
  - Added a new param mailer_from (#81)

Bugfixes:
  - Fixed policy edition issue in the yearly tab (#84)
  - Fixed policy edition issue in the hourly tab (#83)
  - Fixed error removing a policy. Improve alert error and translations. (#85)

## 1.1.3 (2015/02/26)
Features:
  - Improve redudant backup notification (#69)
  - Client edition: show quota in GB (#67)
  - Restore tree: added folder/file icons (#72)
  - Show clients: new add-client button on top (#36)
  - Show clients: run-now button on job rows (#36)
  - Client and job edition: Improve script selector (#68)

Bugfixes:
  - Allow spaces/backslashes in excludes/includes (#70)
  - Set minimum value -1 for quota (#67)
  - Restore tree: remove links from filenames (#72)

## 1.1.2 (2014/12/04)
Features:
  - Improved script edition form "used by" section (#63)

Bugfixes:
  - Fixed "Add key" button issue in Google Chrome (#65)
  - Fixed restore-directory navigation issue (#66)
  - Added sort jobs help panel (#33)

## 1.1.1 (2014/11/18)

Bugfixes:
  - Menu logo with a wrong link (#60)
  - Show jobs disabled when the client is disabled (#61)
  - Script edition: add translations to the new checkbox table (#62)

## 1.1.0 (2014/11/17)

Features:
  - New Bootstrap 3.2.0
  - New theme (by @christiangr #51)
  - Improved usability
    - Buttons with icons
    - "Clients" renamed to "Job" on the menu (#36)
    - Reallocated action buttons
    - "Run now": improved feedback (#34)
    - Disabled buttons (explain the reason with a tooltip)
    - Colorized backup error messages
    - Direct link to client/job filtered log
    - Show required fields with a red asterisk
    - New return page after submitting a form
  - Show elkarbackup version on the login (#9)
  - Disk or storage disk usage bar (#8)
  - Password change: ask password twice (#55)

Bugfixes:
  - Fix dpkg-reconfigure error (#54 @xezpeleta)

## 1.0.23 (2014/07/04)

Features:
  - Ubuntu 14.04 Trusty / Debian 8 Jessie compatibility (#48 @xezpeleta)

Bugfixes:
  - Include/Exclude bug (#25 #29 #45 @alfredobz)
  - Add logrotate policy (#42, @alfredobz)

## 1.0.22 (2014/03/21)

Features:
  - GUI - Clients: Show disk usage in GB (#8, @xezpeleta)
  - Allow access with URL http://ipaddress/elkarbackup (#31, @elacunza)
  - Report Elkarbackup status to Nagios: send_nsca_result.sh script (#21, @elacunza)

Bugfixes:
  - Windows snapshots: log snapshot creation errors as debugging aid (@blaskurain)
  - Fix problem with monthly and yearly policy (#41, @xezpeleta)

## 1.0.21 (2013/10/22)

Bugfixes:
  - Fix problem with daily policy (#17, @blaskurain)
  - Make policy field required (@xezpeleta)
