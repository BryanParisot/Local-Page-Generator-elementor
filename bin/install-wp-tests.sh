#!/usr/bin/env bash

set -euo pipefail

if [ "$#" -lt 3 ]; then
  echo "Usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]"
  exit 1
fi

LPG_TEST_DB_NAME="$1"
LPG_TEST_DB_USER="$2"
LPG_TEST_DB_PASS="$3"
LPG_TEST_DB_HOST="${4:-localhost}"
LPG_TEST_WP_VERSION="${5:-latest}"
LPG_SKIP_DB_CREATE="${6:-false}"
LPG_TEMP_ROOT="${TMPDIR:-/tmp}"
LPG_TEMP_ROOT="${LPG_TEMP_ROOT%/}"
LPG_WP_TESTS_DIR="${WP_TESTS_DIR:-${LPG_TEMP_ROOT}/wordpress-tests-lib}"
LPG_WP_CORE_DIR="${WP_CORE_DIR:-${LPG_TEMP_ROOT}/wordpress}"

download() {
  local source_url="$1"
  local destination="$2"

  if command -v curl >/dev/null 2>&1; then
    curl --fail --silent --show-error --location "$source_url" --output "$destination"
    return
  fi

  if command -v wget >/dev/null 2>&1; then
    wget --quiet --output-document="$destination" "$source_url"
    return
  fi

  echo "curl ou wget est nécessaire pour installer WordPress."
  exit 1
}

if [ "$LPG_TEST_WP_VERSION" = "latest" ]; then
  LPG_VERSION_RESPONSE="${LPG_TEMP_ROOT}/lpg-wp-latest.json"
  download "https://api.wordpress.org/core/version-check/1.7/" "$LPG_VERSION_RESPONSE"
  LPG_RESOLVED_WP_VERSION="$(grep -o '"version":"[^"]*' "$LPG_VERSION_RESPONSE" | head -1 | sed 's/"version":"//')"
else
  LPG_RESOLVED_WP_VERSION="$LPG_TEST_WP_VERSION"
fi

if [ -z "$LPG_RESOLVED_WP_VERSION" ]; then
  echo "Impossible de déterminer la version de WordPress à tester."
  exit 1
fi

if [[ "$LPG_TEST_WP_VERSION" =~ ^(nightly|trunk)$ ]]; then
  LPG_WP_TESTS_TAG="trunk"
elif [[ "$LPG_RESOLVED_WP_VERSION" =~ ^[0-9]+\.[0-9]+\.0$ ]]; then
  LPG_WP_TESTS_TAG="tags/${LPG_RESOLVED_WP_VERSION%.0}"
else
  LPG_WP_TESTS_TAG="tags/${LPG_RESOLVED_WP_VERSION}"
fi

install_wordpress() {
  if [ -d "$LPG_WP_CORE_DIR/wp-includes" ]; then
    return
  fi

  mkdir -p "$LPG_WP_CORE_DIR"
  local archive="${LPG_TEMP_ROOT}/lpg-wordpress.tar.gz"

  if [[ "$LPG_TEST_WP_VERSION" =~ ^(nightly|trunk)$ ]]; then
    download "https://wordpress.org/nightly-builds/wordpress-latest.zip" "${LPG_TEMP_ROOT}/lpg-wordpress.zip"
    unzip -q "${LPG_TEMP_ROOT}/lpg-wordpress.zip" -d "${LPG_TEMP_ROOT}/lpg-wordpress-nightly"
    cp -R "${LPG_TEMP_ROOT}/lpg-wordpress-nightly/wordpress/." "$LPG_WP_CORE_DIR"
  else
    download "https://wordpress.org/wordpress-${LPG_RESOLVED_WP_VERSION}.tar.gz" "$archive"
    tar --strip-components=1 -xzf "$archive" -C "$LPG_WP_CORE_DIR"
  fi
}

install_test_library() {
  if [ ! -d "$LPG_WP_TESTS_DIR/includes" ]; then
    mkdir -p "$LPG_WP_TESTS_DIR"

    if command -v svn >/dev/null 2>&1; then
      svn checkout --quiet "https://develop.svn.wordpress.org/${LPG_WP_TESTS_TAG}/tests/phpunit/includes/" "$LPG_WP_TESTS_DIR/includes"
      svn checkout --quiet "https://develop.svn.wordpress.org/${LPG_WP_TESTS_TAG}/tests/phpunit/data/" "$LPG_WP_TESTS_DIR/data"
    else
      local test_archive="${LPG_TEMP_ROOT}/lpg-wordpress-develop.tar.gz"
      local test_source="${LPG_TEMP_ROOT}/lpg-wordpress-develop-${LPG_RESOLVED_WP_VERSION}"
      local test_tag_version="$LPG_RESOLVED_WP_VERSION"
      local test_archive_url=""

      if [[ "$test_tag_version" =~ ^[0-9]+\.[0-9]+\.0$ ]]; then
        test_tag_version="${test_tag_version%.0}"
      fi

      if [[ "$test_tag_version" =~ ^[0-9]+\.[0-9]+$ ]]; then
        test_archive_url="https://github.com/WordPress/wordpress-develop/archive/refs/heads/${test_tag_version}.tar.gz"
      else
        test_archive_url="https://github.com/WordPress/wordpress-develop/archive/refs/tags/${test_tag_version}.tar.gz"
      fi

      download "$test_archive_url" "$test_archive"
      mkdir -p "$test_source"
      tar --strip-components=1 -xzf "$test_archive" -C "$test_source"
      cp -R "$test_source/tests/phpunit/includes" "$LPG_WP_TESTS_DIR/includes"
      cp -R "$test_source/tests/phpunit/data" "$LPG_WP_TESTS_DIR/data"
    fi
  fi

  if [ ! -f "$LPG_WP_TESTS_DIR/wp-tests-config.php" ]; then
    download "https://develop.svn.wordpress.org/${LPG_WP_TESTS_TAG}/wp-tests-config-sample.php" "$LPG_WP_TESTS_DIR/wp-tests-config.php"

    sed -i.bak "s:dirname( __FILE__ ) . '/src/':'${LPG_WP_CORE_DIR%/}/':" "$LPG_WP_TESTS_DIR/wp-tests-config.php"
    sed -i.bak "s/youremptytestdbnamehere/${LPG_TEST_DB_NAME}/" "$LPG_WP_TESTS_DIR/wp-tests-config.php"
    sed -i.bak "s/yourusernamehere/${LPG_TEST_DB_USER}/" "$LPG_WP_TESTS_DIR/wp-tests-config.php"
    sed -i.bak "s/yourpasswordhere/${LPG_TEST_DB_PASS}/" "$LPG_WP_TESTS_DIR/wp-tests-config.php"
    sed -i.bak "s|localhost|${LPG_TEST_DB_HOST}|" "$LPG_WP_TESTS_DIR/wp-tests-config.php"
  fi
}

create_database() {
  if [ "$LPG_SKIP_DB_CREATE" = "true" ]; then
    return
  fi

  if ! command -v mysqladmin >/dev/null 2>&1; then
    echo "mysqladmin est nécessaire pour créer la base de tests."
    exit 1
  fi

  local host="$LPG_TEST_DB_HOST"
  local port=""

  if [[ "$host" == *:* ]]; then
    port="${host##*:}"
    host="${host%%:*}"
  fi

  local connection_args=("--host=${host}" "--protocol=tcp")

  if [ -n "$port" ]; then
    connection_args+=("--port=${port}")
  fi

  MYSQL_PWD="$LPG_TEST_DB_PASS" mysqladmin create "$LPG_TEST_DB_NAME" --user="$LPG_TEST_DB_USER" "${connection_args[@]}"
}

install_wordpress
install_test_library
create_database

echo "WordPress ${LPG_RESOLVED_WP_VERSION} et sa suite de tests sont prêts."
