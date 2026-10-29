#!/usr/bin/env bash
set -Eeuo pipefail

APP_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

echo "Starting default queue worker..."
php artisan queue:work --tries=3 --timeout=90 &
mail_pid=$!

echo "Starting Laravel scheduler..."
php artisan schedule:work &
scheduler_pid=$!

stop_children() {
    trap - TERM INT
    kill -TERM "$mail_pid" "$scheduler_pid" 2>/dev/null || true
    wait "$mail_pid" "$scheduler_pid" 2>/dev/null || true
}

trap 'stop_children; exit 0' TERM INT

set +e
wait -n "$mail_pid" "$scheduler_pid"
exit_code=$?
set -e

echo "A queue or scheduler process stopped; restarting the Railway service."
stop_children
if (( exit_code == 0 )); then
    exit_code=1
fi
exit "$exit_code"
