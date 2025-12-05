#!/bin/bash

DIR="$(dirname "$(readlink -f "$0")")"

echo "::group::Dockerized tests"
docker compose -f "${DIR}/docker-compose.yml" up --build --abort-on-container-exit --exit-code-from elkarbackup
err=$?
echo "::endgroup::"

echo "::group::Cleanup"
docker compose -f "${DIR}/docker-compose.yml" down
echo "::endgroup::"

if [ "$err" -eq 0 ]; then
    echo -e "\033[1;32m✅ Success: All checks passed!\033[0m"
else
    echo -e "\033[1;31m❌ Failure: One or more checks failed (err=$err).\033[0m"
fi
exit $err
