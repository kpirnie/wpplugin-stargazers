#!/usr/bin/env bash
set -euo pipefail

ROOT="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
SRC="${ROOT}/source"
DIST="${ROOT}/distribute"
OWNER="$( stat -c '%U' "${ROOT}" )"
GROUP="$( stat -c '%G' "${ROOT}" )"

# pull some info from the plugin header and readme
SLUG="$( grep -m1 'Text Domain:' "${SRC}"/*.php | sed 's/.*Text Domain:[[:space:]]*//' | tr -d '\r' )"
VERSION="$( grep -m1 '^ \* Version:' "${SRC}/${SLUG}.php" | sed 's/^ \* Version:[[:space:]]*//' | tr -d '\r' )"
STABLE="$( grep -m1 '^Stable tag:' "${SRC}/readme.txt" | sed 's/^Stable tag:[[:space:]]*//' | tr -d '\r' )"
NAME="$( grep -m1 '^ \* Plugin Name:' "${SRC}/${SLUG}.php" | sed 's/^ \* Plugin Name:[[:space:]]*//' | tr -d '\r' )"

# they have to match, otherwise we are shipping a mismatched release
if [ "${VERSION}" != "${STABLE}" ]; then
    echo "! version mismatch: plugin header ${VERSION} vs readme stable tag ${STABLE}"
    exit 1
fi

# check the name and text domain from the plugin header, since we need them for the build
if [ -z "${NAME}" ] || [ -z "${SLUG}" ]; then
    echo "! could not read Plugin Name and/or Text Domain from the plugin header"
    exit 1
fi

# just a sanity check to make sure we are not building in the source tree
echo "# Building ${NAME} ${VERSION}"

# clean out the distribution
echo "# Cleaning Up Distribution"
rm -rf "${DIST}"
mkdir -p "${DIST}/assets/css" "${DIST}/assets/js" "${DIST}/languages"

# copy the php, the index guards, and the readme
echo "# Working on Templates"
rsync -a --prune-empty-dirs \
    --include='*/' \
    --include='*.php' \
    --exclude='*' \
    "${SRC}/" "${DIST}/"
cp "${SRC}/readme.txt" "${DIST}/readme.txt"
cp "${SRC}/LICENSE" "${DIST}/LICENSE"

# minify the assets in place, keeping the original filenames
echo "# Working on Assets"
ESBUILD="${ROOT}/node_modules/.bin/esbuild"
if [ ! -x "${ESBUILD}" ]; then
    npm install --silent --no-audit --no-fund --prefix "${ROOT}"
fi
"${ESBUILD}" "${SRC}/assets/css/style.css" --minify --outfile="${DIST}/assets/css/style.css" --log-level=warning
"${ESBUILD}" "${SRC}/assets/js/script.js" --minify --outfile="${DIST}/assets/js/script.js" --log-level=warning

# ship the composer manifest and build the autoloader against the distributed tree
echo "# Working on Vendor"
cp "${ROOT}/composer.json" "${DIST}/composer.json"
composer install --no-dev --no-interaction --quiet \
    --optimize-autoloader --classmap-authoritative \
    --working-dir="${DIST}"

# remove the lock file in the distribution, since it is not needed for the end user
rm -f "${DIST}/composer.lock"

# generate the translation template
echo "# Working on Languages"
wp i18n make-pot "${DIST}" "${DIST}/languages/${SLUG}.pot" \
    --slug="${SLUG}" \
    --domain="${SLUG}" \
    --exclude=vendor \
    --allow-root \
    --quiet

# fix the ownership and permissions
chown -R "${OWNER}:${GROUP}" "${ROOT}"
find "${SRC}" -type d -exec chmod 755 {} \;
find "${SRC}" -type f -exec chmod 644 {} \;
find "${DIST}" -type d -exec chmod 755 {} \;
find "${DIST}" -type f -exec chmod 644 {} \;
chmod +x "${ROOT}/build.sh"

echo "# Done"
