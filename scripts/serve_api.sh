#!/usr/bin/env bash
set -euo pipefail

HOST="${1:-127.0.0.1}"
PORT="${2:-8000}"

exec php \
  -d post_max_size=128M \
  -d upload_max_filesize=128M \
  -d max_input_time=300 \
  -d max_execution_time=300 \
  -d display_errors=0 \
  -d display_startup_errors=0 \
  -d log_errors=1 \
  -d error_log=/tmp/acil_alalim_backend.log \
  -S "${HOST}:${PORT}" \
  -t api
