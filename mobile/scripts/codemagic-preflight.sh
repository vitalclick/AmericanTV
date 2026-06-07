#!/usr/bin/env bash
#
# Verifies every env var the Codemagic workflows depend on is set before
# the build burns 20 minutes only to die at the publish step.
#
# Runs as the first script in each Codemagic workflow. Required vars
# differ per workflow; the WORKFLOW env var (set by Codemagic) gates
# which set we check.
#
# Exits non-zero with a tabulated diagnosis if anything's missing.
# Codemagic surfaces stderr in the build log, so the operator sees
# exactly which env vars to populate.

set -uo pipefail

# ----- required vars per workflow ----------------------------------------
COMMON_VARS=(
  API_BASE_URL
  BUILD_VERSION
  RELEASE_NOTIFY_EMAIL
)

IOS_VARS=(
  REVENUECAT_IOS_KEY
)

ANDROID_VARS=(
  REVENUECAT_ANDROID_KEY
  GOOGLE_OAUTH_CLIENT_ID_ANDROID
)

# Production-only: Firebase + icon assets MUST be configured.
IOS_PROD_VARS=(
  FIREBASE_GOOGLE_SERVICE_INFO_PLIST
  APP_ICON_PNG_B64
)

ANDROID_PROD_VARS=(
  FIREBASE_GOOGLE_SERVICES_JSON
  APP_ICON_PNG_B64
  APP_ICON_ADAPTIVE_FG_PNG_B64
  ANDROID_RELEASE_SHA256
)

# ----- workflow detection ------------------------------------------------
WORKFLOW="${CM_WORKFLOW_ID:-${WORKFLOW:-unknown}}"

declare -a REQUIRED=()
REQUIRED+=("${COMMON_VARS[@]}")

case "$WORKFLOW" in
  ios-testflight)
    REQUIRED+=("${IOS_VARS[@]}")
    ;;
  ios-app-store)
    REQUIRED+=("${IOS_VARS[@]}" "${IOS_PROD_VARS[@]}")
    ;;
  android-internal)
    REQUIRED+=("${ANDROID_VARS[@]}")
    ;;
  android-production)
    REQUIRED+=("${ANDROID_VARS[@]}" "${ANDROID_PROD_VARS[@]}")
    ;;
  *)
    echo "[preflight] WORKFLOW='$WORKFLOW' — running with common-vars only."
    ;;
esac

# ----- verification ------------------------------------------------------
declare -a MISSING=()
for VAR in "${REQUIRED[@]}"; do
  if [ -z "${!VAR-}" ]; then
    MISSING+=("$VAR")
  fi
done

if [ "${#MISSING[@]}" -gt 0 ]; then
  cat >&2 <<EOF

╔════════════════════════════════════════════════════════════════════╗
║  PREFLIGHT FAILED                                                  ║
╠════════════════════════════════════════════════════════════════════╣
║  workflow: $WORKFLOW
║  missing:
EOF
  for VAR in "${MISSING[@]}"; do
    echo "║    - $VAR" >&2
  done
  cat >&2 <<EOF
╚════════════════════════════════════════════════════════════════════╝

Populate each missing variable in the Codemagic env group
americantv-prod, then re-run the build. See
mobile/docs/CODEMAGIC_SETUP.md for the env-var matrix.

EOF
  exit 1
fi

# ----- shape checks ------------------------------------------------------
# API_BASE_URL must be HTTPS on ALL workflows. iOS App Transport Security
# blocks cleartext at runtime regardless of whether the build is going to
# TestFlight or the App Store — a TestFlight install with a cleartext
# API URL would build successfully and then fail every API call.
if [[ "$API_BASE_URL" != https://* ]]; then
  echo "[preflight] FATAL: API_BASE_URL='$API_BASE_URL' is not HTTPS." >&2
  echo "[preflight] iOS ATS blocks cleartext API calls at runtime even on" >&2
  echo "[preflight] TestFlight builds. Use https://… in the env group." >&2
  exit 1
fi

# BUILD_VERSION must be a valid SemVer (X.Y.Z, optionally with -prerelease)
# or App Store Connect / Play Console reject the upload at step 22 — but
# we've already burned 20 minutes of build time by then. Catch typos
# like 1.0.O (capital O) or 1.0 (missing patch) here.
if ! [[ "$BUILD_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$ ]]; then
  echo "[preflight] FATAL: BUILD_VERSION='$BUILD_VERSION' is not valid SemVer." >&2
  echo "[preflight] Expected MAJOR.MINOR.PATCH (e.g. 1.2.3), optionally with" >&2
  echo "[preflight] a -prerelease suffix (e.g. 1.2.3-beta.1)." >&2
  exit 1
fi

# ANDROID_RELEASE_SHA256 must look like a real fingerprint, not the
# default placeholder.
if [[ "$WORKFLOW" == "android-production" ]]; then
  if [[ "$ANDROID_RELEASE_SHA256" == "REPLACE_WITH_KEYSTORE_SHA256_FINGERPRINT" ]]; then
    echo "[preflight] FATAL: ANDROID_RELEASE_SHA256 still holds the placeholder." >&2
    echo "[preflight] App Links verification will fail. See CODEMAGIC_SETUP.md." >&2
    exit 1
  fi
fi

echo "[preflight] ✓ all required env vars set for workflow=$WORKFLOW"
