#!/usr/bin/env bash
# Keep only the newest part of /var/log/apache2/modsec_audit.log
# Usage:
#   sudo ./truncate_modsec_log.sh [-n LINES] [-c BYTES]
# Priority: if -c is provided, it takes precedence over -n.
#
# Notes:
# - We replace the file contents in-place (same inode) so Apache keeps writing.
# - We use a lock to avoid concurrent runs.
# - Requires bash; no need for moreutils/sponge.

set -euo pipefail

LOG="/var/log/apache2/modsec_audit.log"
LOCK="/var/lock/truncate_modsec_log.lock"
LINES=50000       # default: keep last 50k lines
BYTES=""          # optional size cap, e.g. 5M => 5*1024*1024 bytes

while getopts "n:c:" opt; do
  case "$opt" in
    n) LINES="${OPTARG}" ;;
    c) BYTES="${OPTARG}" ;;
    *) echo "Usage: $0 [-n LINES] [-c BYTES]" >&2; exit 1 ;;
  esac
done

if [[ ! -f "$LOG" ]]; then
  echo "Log not found: $LOG" >&2
  exit 1
fi

# Capture current mode/owner to restore after writing
MODE=$(stat -c "%a" "$LOG")
USER=$(stat -c "%U" "$LOG")
GROUP=$(stat -c "%G" "$LOG")

# Lock to avoid parallel truncations
exec 9>"$LOCK"
flock -n 9 || { echo "Another truncate is in progress"; exit 0; }

TMP=$(mktemp /tmp/modsec_audit_tail.XXXXXX)

# Prefer byte-based cap if provided; else line-based
if [[ -n "$BYTES" ]]; then
  # tail -c accepts values like 5000000; for 5M, pass 5000000 explicitly
  # If you want human suffixes, convert them before calling this script.
  tail -c "$BYTES" "$LOG" > "$TMP" || true
else
  tail -n "$LINES" "$LOG" > "$TMP" || true
fi

# Overwrite the original file contents in-place (keeps same inode)
# This ensures Apache keeps writing without reopening.
cat "$TMP" > "$LOG"

# Restore original mode/ownership (cat keeps them, but be explicit)
chmod "$MODE" "$LOG"
chown "$USER:$GROUP" "$LOG"

rm -f "$TMP"
echo "Truncated $LOG successfully."
