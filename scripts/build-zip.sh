#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_SLUG="tempered-themechanger"
PLUGIN_FILE="${ROOT_DIR}/${PLUGIN_SLUG}.php"
DIST_DIR="${ROOT_DIR}/dist"
DISTIGNORE_FILE="${ROOT_DIR}/.distignore"

VERSION="$(
	awk -F': ' '/^[[:space:]]+\* Version:/ { print $2; exit }' "${PLUGIN_FILE}" | tr -d '\r'
)"

if [[ -z "${VERSION}" ]]; then
	VERSION="dev"
fi

ZIP_FILE="${DIST_DIR}/${PLUGIN_SLUG}-${VERSION}.zip"
BUILD_DIR="$(mktemp -d)"

cleanup() {
	rm -rf "${BUILD_DIR}"
}
trap cleanup EXIT

mkdir -p "${DIST_DIR}"

RSYNC_ARGS=(
	-a
	--delete
	--exclude=".git/"
)

if [[ -f "${DISTIGNORE_FILE}" ]]; then
	RSYNC_ARGS+=(--exclude-from="${DISTIGNORE_FILE}")
fi

rsync "${RSYNC_ARGS[@]}" "${ROOT_DIR}/" "${BUILD_DIR}/${PLUGIN_SLUG}/"

rm -f "${ZIP_FILE}"

(
	cd "${BUILD_DIR}"
	zip -qr "${ZIP_FILE}" "${PLUGIN_SLUG}"
)

echo "Created ${ZIP_FILE}"
