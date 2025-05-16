#!/bin/bash
set -euo pipefail

# Utility functions
# usage: var=$(url_join "http://example.com" "uploads" "05" "10")
url_join() {
  local result=""
  local part
  local first=true

  for part in "$@"; do
    # Preserve leading slash if first part is just "/"
    if $first; then
      if [[ "$part" == "/" ]]; then
        result="/"
      else
        result="${part%/}"
      fi
      first=false
    else
      part="${part#/}"
      part="${part%/}"
      result="${result%/}/${part}"
    fi
  done

  echo "$result"
}

# === Load .env.php if present ===
if [[ -f ".env.php" ]]; then
  echo -e "\033[1;34m[ENV] Loading .env.php file...\033[0m"
  source ./scripts/load_env_php.sh ".env.php"
else
  echo -e "\033[1;34m[ENV] Using GitHub Actions Secrets.\033[0m"
fi

# === Colorful Logging ===
log() {
  echo -e "\033[1;32m[INFO]\033[0m $1"
}
warn() {
  echo -e "\033[1;33m[WARN]\033[0m $1"
}
error_exit() {
  echo -e "\033[1;31m[ERROR]\033[0m $1"
  exit 1
}

# === Settings ===
ZOLA_VERSION="v0.20.0"
ZOLA_COMMAND="./.bin/zola"

# === Required Environment ===
: "${COCKPIT_URL:?Missing COCKPIT_URL}"
: "${COCKPIT_TOKEN:?Missing COCKPIT_TOKEN}"
: "${DEPLOY_URL:?Missing DEPLOY_URL}"
: "${COCKPIT_ITEMS:?Missing COCKPIT_ITEMS}"

COCKPIT_SPACE="${COCKPIT_SPACE:-}"
COCKPIT_ITEMS_PATH="${COCKPIT_ITEMS_PATH:-api/content/items}"
COCKPIT_ASSETS_API_PATH="${COCKPIT_ASSETS_API_PATH:-api/public/getAssets}"
# DEPLOY_UPLOADS_URL="${DEPLOY_URL}/uploads"
DEPLOY_UPLOADS_URL=$(url_join "$DEPLOY_URL" "uploads")
# echo $(url_join "http://example.com" "uploads" "05" "10")
# echo $(url_join "/a" "uploads" "/05/" "10")

# COCKPIT_ITEMS はカンマ区切りの文字列（例: "info,blog"）を想定
COCKPIT_ITEMS_RAW="${COCKPIT_ITEMS}"
IFS=',' read -ra COCKPIT_ITEMS <<< "$COCKPIT_ITEMS_RAW"

if [[ -z "$COCKPIT_SPACE" ]]; then
  COCKPIT_UPLOADS_URL="${COCKPIT_URL}/storage/uploads"
else
  COCKPIT_UPLOADS_URL="${COCKPIT_URL}/:${COCKPIT_SPACE}/storage/uploads"
fi

# === Step: Clean if --clean is passed ===
CLEAN=false
for arg in "$@"; do
  if [[ "$arg" == "--clean" ]]; then
    CLEAN=true
  fi
done

clean() {
  for dir in "data" "zola/public"; do
    if [[ -d "${dir}" ]]; then
      log "Cleaning directory ${dir}"
      rm -rf "${dir:?}"/*
    fi
  done
}

if $CLEAN; then
  clean
fi

# ================================================================

# === Step: Fetch Items ===
fetch_items() {
  for item in "${COCKPIT_ITEMS[@]}"; do
    log "Fetching item: ${item}"
    if [[ -z "$COCKPIT_SPACE" ]]; then
      url=$(url_join "${COCKPIT_URL}" "${COCKPIT_ITEMS_PATH}" "${item}")
    else
      url=$(url_join "${COCKPIT_URL}" ":${COCKPIT_SPACE}" "${COCKPIT_ITEMS_PATH}" "${item}")
    fi
    curl --retry 5 --retry-delay 3 --retry-max-time 60 --connect-timeout 10 --fail -sSL \
      -H "Cockpit-Token: $COCKPIT_TOKEN" "$url" -o "data/${item}.json" \
      || error_exit "Failed to fetch item: $item"

    go run scripts/convert_to_md.go \
      --input="data/${item}.json" \
      --output="zola/content/${item}" \
      --upload-url="${COCKPIT_UPLOADS_URL}" \
      --deploy-upload-url="${DEPLOY_UPLOADS_URL}" \
      || error_exit "Markdown conversion failed for $item"
  done
}

# === Step: Fetch Assets ===
fetch_assets() {
  log "Fetching assets..."
  if [[ -z "$COCKPIT_SPACE" ]]; then
    url=$(url_join "${COCKPIT_URL}" "${COCKPIT_ASSETS_API_PATH}")
  else
    url=$(url_join "${COCKPIT_URL}" "${COCKPIT_ASSETS_API_PATH}?space=${COCKPIT_SPACE}")
  fi
  curl --retry 5 --retry-delay 3 --retry-max-time 60 --connect-timeout 10 --fail -sSL \
    -H "Cockpit-Token: ${COCKPIT_TOKEN}" "$url" -o data/assets.json \
    || error_exit "Failed to fetch assets"

  go run scripts/fetch_assets.go --baseurl="${COCKPIT_UPLOADS_URL}" \
    || error_exit "fetch_assets.go failed"

  go run scripts/image_process.go \
    || error_exit "image_process.go failed"
}

# === Step: Install Zola ===
install_zola() {
  if [[ ! -f "$ZOLA_COMMAND" ]]; then
    log "Installing Zola $ZOLA_VERSION..."
    cd tmp
    curl -sL "https://github.com/getzola/zola/releases/download/${ZOLA_VERSION}/zola-${ZOLA_VERSION}-x86_64-unknown-linux-gnu.tar.gz" | tar -xz
    mv zola ../.bin/zola
    cd ..
  fi
  "$ZOLA_COMMAND" --version
}

# === Step: Build Site ===
build_site() {
  log "Building site with Zola..."
  "$ZOLA_COMMAND" --root zola build \
    || error_exit "Zola build failed"
}

# === Step: Deploy via FTP ===
deploy_site() {
  if [[ -n "${FTP_HOST:-}" ]]; then
    log "Deploying via FTPS..."
    lftp -e "
      set ftp:ssl-force true;
      set ftp:ssl-protect-data true;
      set ssl:verify-certificate no;
      open -u $FTP_USER,$FTP_PASSWORD -p $FTP_PORT ftps://$FTP_HOST;
      mirror -R --delete --verbose \
        --exclude-glob .env.php \
        --exclude-glob .env.example.php \
        ./public $FTP_REMOTE_DIR;
      bye
    " || error_exit "Deployment via lftp failed"
  else
    warn "FTP not configured. Skipping deploy."
  fi
}

# === Step: Initialize ===
mkdir -p .bin data tmp zola/public
# === Run All ===
fetch_items
fetch_assets
install_zola
build_site
deploy_site

log "✅ Site generated and deployed successfully."
