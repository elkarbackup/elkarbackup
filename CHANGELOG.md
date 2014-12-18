## Upcoming release
Features:
  - Improve redudant backup notification (#69)

Bugfixes:
  - Allow spaces/backslashes in paths (#70)

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
