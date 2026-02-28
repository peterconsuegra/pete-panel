#!/bin/bash
set -euo pipefail

BASE_PATH=""
while getopts "b:" opt; do
  case "$opt" in
    b) BASE_PATH="${OPTARG}" ;;
  esac
done

if [[ -z "${BASE_PATH}" ]]; then
  echo "Usage: $0 -b /absolute/base/path" >&2
  exit 2
fi

SRC="/var/log/apache2/modsec_audit.log"
DST="${BASE_PATH}/storage/logs/modsec_audit.log"
OUT="${BASE_PATH}/storage/logs/modsec-audit.html"
GEN="${BASE_PATH}/mod_sec_report/mod_sec_report"

# Copy current audit log (don’t remove the dst first; just overwrite)
echo "Copying ${SRC} -> ${DST}"
install -o www-data -g www-data -m 0640 /dev/null "${DST}" || true
cp -f "${SRC}" "${DST}"
chown www-data:www-data "${DST}" || true

# Generate report
echo "Generating ${OUT}"
tmp_html="$(mktemp)"
pushd "${BASE_PATH}/mod_sec_report" >/dev/null
"./mod_sec_report" "${DST}" > "${tmp_html}"
popd >/dev/null

install -o www-data -g www-data -m 0644 "${tmp_html}" "${OUT}"
rm -f "${tmp_html}"
echo "Done: ${OUT}"
