#!/usr/bin/env bash
set -Eeuo pipefail

trap 'echo "[import.sh] ERROR at line $LINENO (exit=$?)" >&2' ERR

# ---- debug gate (ONLY active when PETE_DEBUG=active) --------------------
DEBUG="${PETE_DEBUG:-}"
log() { [[ "${DEBUG}" == "active" ]] && echo "[import.sh] $*" >&2 || true; }

# ---- parse args ---------------------------------------------------------
# keep -k for backwards compatibility, but env wins
while getopts "n:p:r:q:m:u:x:y:l:t:z:h:v:k:" option; do
  case "${option}" in
    n) project_name=${OPTARG} ;;
    p) db_root_pass=${OPTARG} ;; # legacy / not used here
    r) route=${OPTARG} ;;
    q) base_path=${OPTARG} ;;
    m) logs_route=${OPTARG} ;;
    u) project_url=${OPTARG} ;;
    x) database_name=${OPTARG} ;;
    y) database_user=${OPTARG} ;;
    l) password_user=${OPTARG} ;;
    t) server=${OPTARG} ;;
    z) os_distribution=${OPTARG} ;; # legacy / not used here
    h) host=${OPTARG} ;;
    v) cookie_domain=${OPTARG} ;; # legacy / currently unused; you hardcode COOKIE_DOMAIN below
    k) [[ -z "${PETE_DEBUG:-}" ]] && DEBUG=${OPTARG} || true ;;
  esac
done

# required inputs
: "${project_name:?Missing -n project_name}"
: "${route:?Missing -r route}"
: "${base_path:?Missing -q base_path}"
: "${logs_route:?Missing -m logs_route}"
: "${project_url:?Missing -u project_url}"
: "${database_name:?Missing -x database_name}"
: "${database_user:?Missing -y database_user}"
: "${password_user:?Missing -l password_user}"
: "${server:?Missing -t server}"
: "${host:?Missing -h host}"

# ---- assume docker (no interactive sudo) --------------------------------
# If you are running inside docker containers (apache/php as root), sudo will fail
# with: "a terminal is required ...". We treat sudo as optional.
SUDO=""
if command -v sudo >/dev/null 2>&1; then
  SUDO="sudo"
fi
# But if sudo requires a TTY or password, it will fail. Detect and disable.
if [[ -n "${SUDO}" ]]; then
  if ! sudo -n true >/dev/null 2>&1; then
    SUDO=""
  fi
fi
log "SUDO=${SUDO:-<none>} (docker-first)"

crud_route="${base_path}/operations/crud/${project_name}"
massive="${crud_route}/massive_file"
config_file="${massive}/config.txt"

log "DEBUG=active"
log "project_name=${project_name}"
log "route=${route}"
log "base_path=${base_path}"
log "crud_route=${crud_route}"
log "project_url=${project_url}"
log "db=${database_name} user=${database_user} host=${host}"
log "server=${server}"

if [[ ! -d "${massive}" ]]; then
  echo "[import.sh] Missing massive_file directory: ${massive}" >&2
  exit 3
fi

if [[ ! -f "${config_file}" ]]; then
  echo "[import.sh] Missing config file: ${config_file}" >&2
  exit 3
fi

# ---- read config safely -------------------------------------------------
cfg_get() {
  local key="$1"
  # expects lines like: prefix = 'wp_'
  # returns value inside single quotes
  local line
  line="$(grep -E "^[[:space:]]*${key}[[:space:]]*" "${config_file}" | head -n 1 || true)"
  if [[ -z "${line}" ]]; then
    echo ""
    return 0
  fi
  local val
  val="$(echo "${line}" | sed -n "s/.*'\([^']*\)'.*/\1/p")"
  echo "${val}"
}

old_url="$(cfg_get domain)"
prefix="$(cfg_get prefix)"
platform="$(cfg_get platform)"

log "config: old_url=${old_url}"
log "config: prefix=${prefix}"
log "config: platform=${platform}"

if [[ -z "${prefix}" ]]; then
  echo "[import.sh] Could not read 'prefix' from ${config_file}" >&2
  exit 4
fi

# ---- move project folder ------------------------------------------------
src_dir="${massive}/filem"
dst_dir="${route}/${project_name}"

if [[ ! -d "${src_dir}" ]]; then
  echo "[import.sh] Expected site folder not found: ${src_dir}" >&2
  exit 5
fi

log "Moving ${src_dir} -> ${dst_dir}"
rm -rf "${dst_dir}"
mv -f "${src_dir}" "${dst_dir}"

# ---- generate WP salts (NO pipes -> no SIGPIPE / broken pipe) -----------
make_salt() {
  # 31 bytes -> 62 hex chars (alphanumeric), no pipes => no broken-pipe failures
  if command -v php >/dev/null 2>&1; then
    php -r 'echo bin2hex(random_bytes(31));'
    return 0
  fi

  # fallback: avoid hard-failing on SIGPIPE by disabling pipefail in this function
  set +o pipefail
  local out
  out="$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 62)"
  set -o pipefail
  echo "${out}"
}

wpkey1="$(make_salt)"
wpkey2="$(make_salt)"
wpkey3="$(make_salt)"
wpkey4="$(make_salt)"
wpkey5="$(make_salt)"
wpkey6="$(make_salt)"
wpkey7="$(make_salt)"
wpkey8="$(make_salt)"

# ---- build wp-config insert ---------------------------------------------
tmp_insert="${crud_route}/otemp.txt"
cat > "${tmp_insert}" <<EOF
define('WP_HOME','http://${project_url}');
define('WP_SITEURL','http://${project_url}');
define('DB_NAME', '${database_name}');
define('DB_USER', '${database_user}');
define('DB_PASSWORD', '${password_user}');
define('DB_HOST', '${host}');

define('AUTH_KEY',         '${wpkey1}');
define('SECURE_AUTH_KEY',  '${wpkey2}');
define('LOGGED_IN_KEY',    '${wpkey3}');
define('NONCE_KEY',        '${wpkey4}');
define('AUTH_SALT',        '${wpkey5}');
define('SECURE_AUTH_SALT', '${wpkey6}');
define('LOGGED_IN_SALT',   '${wpkey7}');
define('NONCE_SALT',       '${wpkey8}');
\$table_prefix  = '${prefix}';

define('COOKIE_DOMAIN', '.${project_url}');
EOF

wp_config="${dst_dir}/wp-config.php"
template="${base_path}/templates/wp-template-without-prefix.php"

if [[ ! -f "${template}" ]]; then
  echo "[import.sh] Missing template: ${template}" >&2
  exit 6
fi

log "Rebuilding wp-config.php"
rm -f "${wp_config}"
# Insert after line 21 (same behavior as legacy)
sed "21r ${tmp_insert}" < "${template}" > "${wp_config}"

# ---- permissions (safer defaults first) ---------------------------------
log "Setting safe perms 755/644"
find "${dst_dir}" -type d -exec chmod 755 {} +
find "${dst_dir}" -type f -exec chmod 644 {} +

# ---- wp-cli actions -----------------------------------------------------
if command -v wp >/dev/null 2>&1; then
  log "Deactivating plugins (best-effort)"
  (cd "${dst_dir}" && wp plugin deactivate really-simple-ssl || true)
  (cd "${dst_dir}" && wp plugin deactivate ssl-insecure-content-fixer || true)
  (cd "${dst_dir}" && wp plugin deactivate redis-cache || true)

  # Redis commands exist only if wp-cli package is installed. Keep best-effort.
  (cd "${dst_dir}" && wp redis status || true)

  # object-cache.php may not exist
  rm -f "${dst_dir}/wp-content/object-cache.php" || true

  # URL rewrite
  if [[ -n "${old_url}" ]]; then
    log "search-replace old_url -> project_url"
    (cd "${dst_dir}" && wp search-replace "${old_url}" "${project_url}" || true)
  else
    log "old_url empty; skipping old_url search-replace"
  fi

  log "forcing https->http for destination"
  (cd "${dst_dir}" && wp search-replace "https://${project_url}" "http://${project_url}" || true)
else
  log "wp-cli not found; skipping plugin + search-replace steps"
fi

# ---- apache options -----------------------------------------------------
if [[ "${server}" == "apache" ]]; then
  log "Apache mode: permissive dirs + logs + root htaccess"

  # docker-first: avoid sudo if it isn't non-interactive
  if [[ -n "${SUDO}" ]]; then
    ${SUDO} find "${dst_dir}" -type d -exec chmod 777 {} + || true
  else
    find "${dst_dir}" -type d -exec chmod 777 {} + || true
  fi

  mkdir -p "${logs_route}/${project_name}"
  : > "${logs_route}/${project_name}/error.log"
  : > "${logs_route}/${project_name}/access.log"

  ht_tpl="${base_path}/templates/htaccess_main.txt"
  if [[ -f "${ht_tpl}" ]]; then
    rm -f "${dst_dir}/.htaccess"
    cp "${ht_tpl}" "${dst_dir}/.htaccess"
  else
    log "htaccess template missing: ${ht_tpl}"
  fi

  rm -f "${dst_dir}/wp-content/.htaccess" || true
  rm -f "${dst_dir}/wp-includes/.htaccess" || true
fi

# ---- uploads hardening (always apply) -----------------------------------
ht_uploads_tpl="${base_path}/templates/htaccess_uploads.txt"
mkdir -p "${dst_dir}/wp-content/uploads"
rm -f "${dst_dir}/wp-content/uploads/.htaccess" || true

if [[ -f "${ht_uploads_tpl}" ]]; then
  cp "${ht_uploads_tpl}" "${dst_dir}/wp-content/uploads/.htaccess"
  log "Applied uploads htaccess: ${dst_dir}/wp-content/uploads/.htaccess"
else
  log "uploads htaccess template missing: ${ht_uploads_tpl}"
fi

# ---- final perms (lock down wp-config) ----------------------------------
log "Final perms 755/644 + wp-config 600"

if [[ -n "${SUDO}" ]]; then
  ${SUDO} find "${dst_dir}" -type d -exec chmod 755 {} + || true
  ${SUDO} find "${dst_dir}" -type f -exec chmod 644 {} + || true
  ${SUDO} chmod 600 "${wp_config}" || true
else
  find "${dst_dir}" -type d -exec chmod 755 {} + || true
  find "${dst_dir}" -type f -exec chmod 644 {} + || true
  chmod 600 "${wp_config}" || true
fi

# ---- cleanup ------------------------------------------------------------
rm -f "${dst_dir}/.maintenance" || true
rm -rf "${crud_route}" || true

# ---- refresh opcache (best-effort) --------------------------------------
if command -v php >/dev/null 2>&1; then
  (cd /var/www/html/Pete && php artisan opcache:refresh) || true
fi

log "DONE"