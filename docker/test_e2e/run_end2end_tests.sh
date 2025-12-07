#!/bin/bash

set -euo pipefail

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

trap cleanup EXIT

function log_ok() {
	echo -e "\033[1;32m✅ $1\033[0m"
}

function log_fail() {
	echo -e "\033[1;31m❌ $1\033[0m"
}

function login() {
	username=$(echo "$credentials" | cut -d: -f1)
	password=$(echo "$credentials" | cut -d: -f2)
	curl -s -c "${DIR}/tmp/cookies.txt" -X POST -v ${host}/login_check \
	-F "_username=${username}" \
	-F "_password=${password}"
}

function check_running() {
	curl -Ls -X GET ${host} | pup 'title text{}' 2> /dev/null | grep -q 'ElkarBackup'
}

function cleanup() {
	set +e
	echo "::group::🧼 Cleaning up..."
	docker compose -f "${DIR}/docker-compose.yml" down --remove-orphans
	sudo rm -rf "${DIR}/tmp"
	echo "::endgroup::"
}

echo "::group::⏫ Starting ElkarBackup for E2E tests..."
mkdir -p "${DIR}/tmp"
docker compose -f "${DIR}/docker-compose.yml" up -d --build elkarbackup
echo "::endgroup::"

echo "::group::⌛ Waiting for ElkarBackup to be ready..."
cnt=6
for i in $(seq 1 $cnt); do
	if check_running; then
		break
	fi
	if [ "$i" -eq $cnt ]; then
		echo "Cotainer logs:"
		docker compose -f "${DIR}/docker-compose.yml" --progress quiet logs elkarbackup
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

log_ok "Success: All checks passed!"
exit 0
