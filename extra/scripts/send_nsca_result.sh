#!/bin/sh
# Report to Nagios via NSCA the backup job result
# Steps for setting up:
# 1. Install and configure NSCA service in Nagios server (Debian nsca package)
# 2. Install and configure NSCA client in Elkarbackup server (Debian send_nsca package)
# 3. Be sure to configure the same password and cipher in both client and server NSCA
# 4. Configure services in Nagios:
#    a. Accept passive checks
#    b. service_description MUST be $ELKARBACKUP_URL, ie: <client_url>:<task_path>
# 5. Configure CFG_* parameter in this script
#
# This script sends as output log $ELKARBACKUP_PATH
#
# ---CONFIGURATION PARAMETERS
# This is the hostname or IP where NSCA service is listening (usually Nagios server)
CFG_NSCA_HOST=monitor
# This is the host_name defined in Nagios passive service configuration
CFG_NAGIOS_HOST_NAME=elkarbackup
# ---END OF CONFIGURATION

if [ $ELKARBACKUP_STATUS -eq 0 ]
then
    status=0
else
    status=2
fi

send_nsca -H $CFG_NSCA_HOST -c /etc/send_nsca.cfg <<END
$CFG_NAGIOS_HOST_NAME	$ELKARBACKUP_URL	$status	$ELKARBACKUP_PATH
END
