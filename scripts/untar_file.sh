#!/usr/bin/env bash
set -Eeuo pipefail
trap 'echo "[untar_file.sh] ERROR at line $LINENO (exit=$?)" >&2' ERR

DEBUG="${PETE_DEBUG:-}"
log() { [[ "${DEBUG}" == "active" ]] && echo "[untar_file.sh] $*" >&2 || true; }

while getopts "n:r:q:u:o:s:k:" option; do
  case "${option}" in
    n) project_name=${OPTARG} ;;
    r) route=${OPTARG} ;;      # legacy / not used here
    q) base_path=${OPTARG} ;;
    o) file_route=${OPTARG} ;;
    s) type=${OPTARG} ;;
    k) [[ -z "${PETE_DEBUG:-}" ]] && DEBUG=${OPTARG} || true ;;
  esac
done

: "${project_name:?Missing -n project_name}"
: "${base_path:?Missing -q base_path}"
: "${file_route:?Missing -o file_route}"
: "${type:?Missing -s type}"

crud_route="${base_path}/operations/crud/${project_name}"
mkdir -p "${crud_route}"

log "DEBUG=active"
log "project_name=${project_name}"
log "base_path=${base_path}"
log "crud_route=${crud_route}"
log "type=${type}"
log "file_route=${file_route}"

# Validate inputs early
if [[ "${type}" == "file" || "${type}" == "none" ]]; then
  if [[ ! -f "${file_route}" ]]; then
    echo "[untar_file.sh] File not found: ${file_route}" >&2
    exit 2
  fi
fi

extract_tar_gz() {
  local tarpath="$1"
  log "Extracting tar.gz/tgz: ${tarpath}"
  # tar xopf - keeps permissions; consider xzf directly if you prefer
  gunzip -c "${tarpath}" | tar xopf - -C "${crud_route}"
}

extract_zip() {
  local zippath="$1"
  log "Extracting zip: ${zippath}"
  mkdir -p "${crud_route}/massive_file"
  (cd "${crud_route}/massive_file" && unzip -a "${zippath}")
}

case "${type}" in
  none|file)
    if [[ "${file_route}" == *.tar.gz || "${file_route}" == *.tgz || "${file_route}" == *.gz ]]; then
      # for tar.gz / tgz / gz (your archives)
      (cd "${crud_route}" && gunzip -c "${file_route}" | tar xopf -)
    elif [[ "${file_route}" == *.zip ]]; then
      extract_zip "${file_route}"
    else
      echo "[untar_file.sh] Unsupported extension for type=${type}: ${file_route}" >&2
      exit 3
    fi
    ;;
  url)
    log "Downloading url -> template.tar.gz"
    (cd "${crud_route}" && curl -fL --retry 3 --retry-delay 1 -o template.tar.gz "${file_route}")
    (cd "${crud_route}" && gunzip -c template.tar.gz | tar xopf -)
    ;;
  *)
    echo "[untar_file.sh] Invalid type: ${type} (expected none|file|url)" >&2
    exit 4
    ;;
esac

# Post-check: ensure expected structure exists
if [[ ! -d "${crud_route}/massive_file" ]]; then
  echo "[untar_file.sh] Extracted archive but massive_file/ not found in ${crud_route}" >&2
  # In debug mode, show listing to help diagnose
  if [[ "${DEBUG}" == "active" ]]; then
    ls -la "${crud_route}" >&2 || true
  fi
  exit 5
fi

log "Extraction complete"