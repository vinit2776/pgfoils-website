#!/usr/bin/env bash
# Rebuild public/fonts/archivo.woff2 (Latin subset, wght 400-800, wdth 100-112) from the fontsource package.
# Needs: pip install fonttools brotli ; npm i @fontsource-variable/archivo (in a scratch folder).
# usage: scripts/build-font.sh <path-to>/archivo-latin-standard-normal.woff2
set -euo pipefail
SRC="${1:?path to archivo-latin-standard-normal.woff2}"
TMP="$(mktemp -d)"
UNICODES="U+0020-007E,U+00A0-00FF,U+0131,U+0152-0153,U+02C6,U+02DA,U+02DC,U+2013-2014,U+2018-201A,U+201C-201E,U+2022,U+2026,U+2032-2033,U+2122,U+2190-2193,U+2212"
fonttools varLib.instancer "$SRC" wght=400:800 wdth=100:112 -o "$TMP/archivo.ttf" --quiet
pyftsubset "$TMP/archivo.ttf" --unicodes="$UNICODES" --flavor=woff2 \
  --layout-features='kern,liga,tnum,lnum,case,ccmp,locl,mark,mkmk' \
  --output-file="$(dirname "$0")/../public/fonts/archivo.woff2"
echo "Built $(wc -c < "$(dirname "$0")/../public/fonts/archivo.woff2") bytes"
