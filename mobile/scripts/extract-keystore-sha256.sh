#!/usr/bin/env bash
#
# Extracts the SHA-256 cert fingerprint from the Android upload keystore
# for ANDROID_RELEASE_SHA256 in the Laravel .env (drives
# .well-known/assetlinks.json).
#
# Usage:
#   ./extract-keystore-sha256.sh /path/to/upload-keystore.jks
#   ./extract-keystore-sha256.sh /path/to/upload-keystore.jks uploadkey
#
# Defaults to alias 'uploadkey' (Android Studio's default).

set -euo pipefail

if [ $# -lt 1 ]; then
  echo "usage: $0 <keystore.jks> [alias]" >&2
  echo "  alias defaults to 'uploadkey'" >&2
  exit 1
fi

KEYSTORE="$1"
ALIAS="${2:-uploadkey}"

if [ ! -f "$KEYSTORE" ]; then
  echo "FATAL: $KEYSTORE not found." >&2
  exit 1
fi

if ! command -v keytool >/dev/null 2>&1; then
  echo "FATAL: keytool not in PATH. Install a JDK." >&2
  exit 1
fi

# keytool prompts for the password interactively. -storepass would inline
# it but exposes via process listing; prefer the prompt.
SHA=$(keytool -list -v -keystore "$KEYSTORE" -alias "$ALIAS" 2>/dev/null \
  | grep "SHA256:" \
  | head -n 1 \
  | sed 's/.*SHA256: //')

if [ -z "$SHA" ]; then
  echo "FATAL: couldn't extract SHA-256. Wrong alias? Wrong password?" >&2
  exit 1
fi

echo "Found SHA-256 for alias '$ALIAS' in $KEYSTORE:"
echo
echo "  $SHA"
echo
echo "Add to your Laravel production .env:"
echo
echo "  ANDROID_RELEASE_SHA256=\"$SHA\""
echo
echo "Then: php artisan config:clear"
echo
echo "Verify:"
echo "  curl https://americantv.vip/.well-known/assetlinks.json | jq"
