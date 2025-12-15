#!/bin/bash

set -euo pipefail

if [[ "${ACTIONS_STEP_DEBUG:-}" == "true" ]]; then
  set -x
fi

credentials="root:root"
host="http://localhost"

DIR=$(readlink -f "$(dirname "$0")")

# Do we have all requirements
if ! command -v pup > /dev/null 2>&1; then
	echo "pup is missing. Please install https://github.com/ericchiang/pup"
	exit 1
fi
if ! command -v curl > /dev/null 2>&1; then
	echo "curl is missing."
	exit 1
fi

function cleanup() {
	set +e
	echo "::group::🧼 Cleaning up..."
	docker compose -f "${DIR}/docker-compose.yml" down --remove-orphans
	sudo rm -rf "${DIR}/tmp"
	echo "::endgroup::"
}

NO_CLEAN=0
usage() { echo "Usage: $0 [-n] [-c]" 1>&2; exit 1; }

while getopts ":nc" o; do
	case "${o}" in
		n)
			NO_CLEAN=1
			;;
		c)
			cleanup
			exit 0
			;;
		*)
			usage
			;;
	esac
done
shift $((OPTIND-1))

if [ "$NO_CLEAN" -eq 1 ]; then
	trap - EXIT
else
	trap cleanup EXIT
fi

function log_ok() {
	echo -e "\033[1;32m✅ $1\033[0m"
}

function log_fail() {
	echo -e "\033[1;31m❌ $1\033[0m"
}

function login() {
	username=$(echo "$credentials" | cut -d: -f1)
	password=$(echo "$credentials" | cut -d: -f2)
	curl -s -c "${DIR}/tmp/cookies.txt" -X POST ${host}/login_check \
	-F "_username=${username}" \
	-F "_password=${password}" > /dev/null
}

function check_running() {
	curl -Ls -X GET ${host} | pup 'title text{}' 2> /dev/null | grep -q 'ElkarBackup'
}

function create_client_and_job() {
	output=$(curl -s -u "$credentials" -X POST ${host}/api/clients -H 'Content-Type: application/json' -d '{
		"description": "Test Client for E2E Tests",
		"isActive": true,
		"maxParallelJobs": 1,
		"name": "Test Client",
		"owner": 0,
		"postScripts": [],
		"preScripts": [],
		"quota": -1,
		"rsyncLongArgs": "",
		"rsyncShortArgs": "",
		"sshArgs": "",
		"url": "testuser@client"
	}')
	client_id=$(echo "$output" | jq -r '.id')

	output=$(curl -s -u "$credentials" -X POST ${host}/api/jobs -H 'Content-Type: application/json' -d '{
		"backupLocation": 1,
		"client": '"$client_id"',
		"description": "Test Job for E2E Tests",
		"exclude": "",
		"include": "",
		"isActive": true,
		"minNotificationLevel": 0,
		"name": "Test Job",
		"notificationsEmail": "report@example.com",
		"notificationsTo": [
				"owner",
				"email"
		],
		"path": "/home/testuser/backup_data",
		"policy": 1,
		"postScripts": [],
		"preScripts": [],
		"token": "",
		"useLocalPermissions": true
	}')
	job_id=$(echo "$output" | jq -r '.id')
	echo "${client_id}:${job_id}"
}

function run_job() {
	local client_id=$1
	local job_id=$2
	curl -s -b "${DIR}/tmp/cookies.txt" -X POST ${host}/client/${client_id}/job/${job_id}/run
}

function restore_job() {
	local client_id=$1
	local job_id=$2
	local backupLocation_id=$3
	local path="$4"
	local dest="$5"

	token=$(curl -s -b "${DIR}/tmp/cookies.txt" -X GET "${host}/client/${client_id}/job/${job_id}/restore/${backupLocation_id}/${path}" | \
		pup 'input[name="restore_backup[_token]"] attr{value}')
	if [ -z "$token" ]; then
		log_fail "Failed to get restore token."
		echo "::endgroup::"
		exit 1
	fi
	curl -s -L -b "${DIR}/tmp/cookies.txt" -X POST \
		"${host}/client/${client_id}/job/${job_id}/restore/${backupLocation_id}/${path}" \
		-F restore_backup[client]=${client_id} \
		-F restore_backup[source]="${path}" \
		-F restore_backup[path]="${dest}" \
		-F restore_backup[_token]=$token
}

function get_job_status() {
	local client_id=$1
	local job_id=$2
	curl -s -b "${DIR}/tmp/cookies.txt" -X GET ${host}/clients | \
		pup "tr[class*=\"client-${client_id} job-${job_id}\"] td[class\$=\"status\"] span text{}" | \
		xargs \
		|| echo "unknown"
}

function get_mail_subject() {
	curl -s http://localhost:8025/api/v2/messages | jq -r '.items[0].Content.Headers.Subject[0]'
}

mkdir -p "${DIR}/tmp"

if [ -z "${GITHUB_ACTIONS:-}" ]; then
echo "::group::🏗️ Building Elkarbackup Docker Image for E2E tests..."
(cd $DIR/../.. && docker build \
	-f docker/Dockerfile \
	-t elkarbackup:test \
	.
)
echo "::endgroup::"
fi

echo "::group::⏫ Starting ElkarBackup for E2E tests..."
docker compose -f "${DIR}/docker-compose.yml" up -d --build elkarbackup
echo "::endgroup::"

echo "::group::⌛ Waiting for ElkarBackup to be ready..."
cnt=6
for i in $(seq 1 $cnt); do
	if check_running; then
		break
	fi
	if [ "$i" -eq $cnt ]; then
		echo "Container logs:"
		docker compose -f "${DIR}/docker-compose.yml" logs elkarbackup
		echo "Web output:"
		curl -Lsv -X GET ${host} || true
		log_fail "ElkarBackup is not ready after $cnt attempts..."
		echo "::endgroup::"
		exit 1
	fi
	sleep 5
done
log_ok "ElkarBackup is ready."
echo "::endgroup::"

echo "::group::⚙️ Setting up backup client and job..."
# Create some data to backup
mkdir -p "${DIR}/tmp/client_data" "${DIR}/tmp/client_restore/"
chmod 777 "${DIR}/tmp/client_restore/"
for i in $(seq 1 5); do
	echo "This is a test file number $i" > "${DIR}/tmp/client_data/file${i}.txt"
done

docker compose -f "${DIR}/docker-compose.yml" up -d --build client
ids=$(create_client_and_job)
if [ -z "$ids" ] || [ "$ids" == "null:null" ]; then
	log_fail "Failed to create job definitions."
	exit 1
fi
client_id=$(echo "$ids" | cut -d: -f1)
job_id=$(echo "$ids" | cut -d: -f2)
if [ "$(get_job_status "$client_id" "$job_id")" == "" ]; then
	log_ok "Created client ID: $client_id and job ID: $job_id"
else
	echo "Container logs:"
	docker compose -f "${DIR}/docker-compose.yml" logs elkarbackup
	log_fail "Failed to create client and job."
	echo "::endgroup::"
	exit 1
fi
echo "::endgroup::"

login

echo "::group::📥 Running job ${client_id}.${job_id}..."
if run_job "$client_id" "$job_id" | grep -q "Job queued successfully"; then
	log_ok "Backup job queued successfully."
else
	log_fail "Failed to start backup job."
	exit 1
fi
echo "::endgroup::"

echo "::group::⌛ Waiting for Backup Job to complete..."
cnt=30
for i in $(seq 1 $cnt); do
	if [ "$(get_job_status "$client_id" "$job_id")" == "OK" ]; then
		log_ok "Backup job completed successfully."
		break
	elif [ "$(get_job_status "$client_id" "$job_id")" == "FAIL" ]; then
		echo "Container logs:"
		docker compose -f "${DIR}/docker-compose.yml" logs elkarbackup
		echo "Log records:"
		docker exec test_e2e-elkarbackup-1 bash -c \
			"mysql \
				-h\$DATABASE_HOST \
				-u\$DATABASE_USER \
				-p\$DATABASE_PASSWORD \
				-Delkarbackup \
				-e 'SELECT datetime, level, link, message \
					FROM LogRecord WHERE link = \"/client/${client_id}/job/${job_id}\";'"
		log_fail "Backup job failed."
		echo "::endgroup::"
		exit 1
	fi
	if [ "$i" -eq $cnt ]; then
		echo "Container logs:"
		docker compose -f "${DIR}/docker-compose.yml" logs elkarbackup
		echo "Web output:"
		curl -s -b "${DIR}/tmp/cookies.txt" -X GET ${host}/clients || true
		log_fail "ElkarBackup did not complete backup job after $cnt attempts..."
		echo "::endgroup::"
		exit 1
	fi
	sleep 5
done
echo "::endgroup::"

echo "::group::📩 Checking for Mail notification..."
cnt=10
for i in $(seq 1 $cnt); do
	if get_mail_subject | grep -q "Log for backup from job"; then
		log_ok "Mail notification received successfully."
		break
	fi
	if [ "$i" -eq $cnt ]; then
		echo "Container logs:"
		docker compose -f "${DIR}/docker-compose.yml" logs elkarbackup
		log_fail "ElkarBackup did not send mail notification after $cnt attempts..."
		echo "::endgroup::"
		exit 1
	fi
	sleep 5
done
echo "::endgroup::"

echo "::group::↩️ Running restore job ${client_id}.${job_id}..."
output=$(restore_job "$client_id" "$job_id" 1 Daily.0/home/testuser/backup_data /home/testuser/restore_data)
if echo "$output" | grep -q "Your backup restore process has been enqueued"; then
	log_ok "Restore job queued successfully."
else
	log_fail "Failed to start restore job."
	echo "Web output:"
	echo "$output"
	echo "::endgroup::"
	exit 1
fi
echo "::endgroup::"

echo "::group::⌛ Waiting for Restore Job to complete..."
cnt=40
for i in $(seq 1 $cnt); do
	if diff -r docker/test_e2e/tmp/client_data docker/test_e2e/tmp/client_restore/backup_data/ 2> /dev/null; then
		log_ok "Restore job completed successfully."
		break
	fi
	if [ "$i" -eq $cnt ]; then
		echo "Container logs:"
		docker compose -f "${DIR}/docker-compose.yml" logs elkarbackup
		echo "Directory contents:"
		tree docker/test_e2e/tmp/client_restore/ || true
		echo "Log records:"
		docker exec test_e2e-elkarbackup-1 bash -c \
			"mysql \
				-h\$DATABASE_HOST \
				-u\$DATABASE_USER \
				-p\$DATABASE_PASSWORD \
				-Delkarbackup \
				-e 'SELECT datetime, level, link, message \
					FROM LogRecord;'"
		log_fail "Restore job failed."
		echo "::endgroup::"
		exit 1
	fi
	sleep 5
done
echo "::endgroup::"

echo -e "\033[1;32m🥳 Success: All checks passing!\033[0m"
exit 0
