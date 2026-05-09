#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_SLUG="ogdynamic"
ZIP_FILE="${ROOT_DIR}/${PLUGIN_SLUG}.zip"

cd "${ROOT_DIR}"

command -v pnpm >/dev/null 2>&1 || {
	echo "pnpm is required to build the admin assets." >&2
	exit 1
}

command -v composer >/dev/null 2>&1 || {
	echo "composer is required to install production PHP autoload files." >&2
	exit 1
}

command -v zip >/dev/null 2>&1 || {
	echo "zip is required to create ${PLUGIN_SLUG}.zip." >&2
	exit 1
}

extract_match() {
	local pattern="$1"
	local file="$2"

	sed -nE "s/${pattern}/\\1/p" "${file}" | head -n 1
}

PLUGIN_HEADER_VERSION="$(extract_match '^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*$' ogdynamic.php)"
PLUGIN_CONSTANT_VERSION="$(extract_match "^[[:space:]]*define\([[:space:]]*'OGDYNAMIC_VERSION',[[:space:]]*'([^']+)'.*$" ogdynamic.php)"
README_STABLE_TAG="$(extract_match '^[[:space:]]*Stable tag:[[:space:]]*([^[:space:]]+).*$' readme.txt)"
PACKAGE_VERSION="$(extract_match '^[[:space:]]*\"version\":[[:space:]]*\"([^\"]+)\".*$' package.json)"
COMPOSER_VERSION="$(extract_match '^[[:space:]]*\"version\":[[:space:]]*\"([^\"]+)\".*$' composer.json)"

EXPECTED_VERSION="${PLUGIN_HEADER_VERSION}"
VERSION_FAILURES=()

for version_name in \
	PLUGIN_HEADER_VERSION \
	PLUGIN_CONSTANT_VERSION \
	README_STABLE_TAG \
	PACKAGE_VERSION \
	COMPOSER_VERSION
do
	version_value="${!version_name}"
	if [[ -z "${version_value}" ]]; then
		VERSION_FAILURES+=("${version_name} is missing")
	elif [[ "${version_value}" != "${EXPECTED_VERSION}" ]]; then
		VERSION_FAILURES+=("${version_name} is ${version_value}, expected ${EXPECTED_VERSION}")
	fi
done

if (( ${#VERSION_FAILURES[@]} )); then
	echo "Version mismatch detected:" >&2
	printf ' - %s\n' "${VERSION_FAILURES[@]}" >&2
	exit 1
fi

echo "Version check passed: ${EXPECTED_VERSION}"

echo "Installing frontend dependencies..."
pnpm install --frozen-lockfile

echo "Building admin assets..."
pnpm build

echo "Installing production Composer dependencies..."
composer install \
	--no-dev \
	--optimize-autoloader \
	--no-interaction

if [[ ! -f "${ROOT_DIR}/vendor/autoload.php" ]]; then
	echo "Release package is missing vendor/autoload.php." >&2
	exit 1
fi

if [[ -e "${ROOT_DIR}/vendor/bin/phpcs" || -e "${ROOT_DIR}/vendor/squizlabs/php_codesniffer" ]]; then
	echo "Release package contains PHPCS development files." >&2
	exit 1
fi

rm -f vendor/composer/tmp-*.zip~

echo "Creating ${PLUGIN_SLUG}.zip..."
rm -f "${ZIP_FILE}"
zip -qr "${ZIP_FILE}" \
	CHANGELOG.md \
	composer.json \
	dist \
	includes \
	languages \
	ogdynamic.php \
	readme.txt \
	uninstall.php \
	vendor \
	-x '*/.DS_Store' \
	-x '*/.gitkeep' \
	-x '*/Thumbs.db' \
	-x 'vendor/bin/*' \
	-x 'vendor/squizlabs/php_codesniffer/*'

echo "Created ${ZIP_FILE}"
