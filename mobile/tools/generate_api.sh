#!/usr/bin/env bash
set -euo pipefail

# Regenerates the typed Dart API client from the Laravel OpenAPI spec.
#
# Output goes to lib/api/generated/ (gitignored). Hand-written code in
# lib/api/ imports from there.
#
# Requires openapi-generator-cli (https://openapi-generator.tech).
#   brew install openapi-generator
# or
#   npm install -g @openapitools/openapi-generator-cli

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
MOBILE_DIR="$(dirname "$SCRIPT_DIR")"
REPO_ROOT="$(dirname "$MOBILE_DIR")"

SPEC="$REPO_ROOT/core/docs/api/openapi-v1.yaml"
OUT="$MOBILE_DIR/lib/api/generated"
CONFIG="$SCRIPT_DIR/openapi-generator-config.yaml"

if [ ! -f "$SPEC" ]; then
  echo "OpenAPI spec not found at $SPEC" >&2
  exit 1
fi

if ! command -v openapi-generator-cli >/dev/null 2>&1 && ! command -v openapi-generator >/dev/null 2>&1; then
  echo "openapi-generator-cli not installed. See script header." >&2
  exit 1
fi

GEN=$(command -v openapi-generator-cli || command -v openapi-generator)

rm -rf "$OUT"
"$GEN" generate \
  -i "$SPEC" \
  -g dart-dio \
  -o "$OUT" \
  -c "$CONFIG"

cd "$MOBILE_DIR"
dart run build_runner build --delete-conflicting-outputs

echo "Generated client at $OUT"
