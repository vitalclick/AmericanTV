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
# Several variables that used to live here moved out as the config matured:
#   API_BASE_URL              -> hardcoded inline in codemagic.yaml
#   BUILD_VERSION             -> read from mobile/pubspec.yaml
#   RELEASE_NOTIFY_EMAIL      -> hardcoded inline in email.recipients
#   APP_ICON_*                -> masters committed at mobile/assets/icon/
#   FIREBASE_GOOGLE_*         -> committed at the canonical Flutter paths
#   ANDROID_RELEASE_SHA256    -> Laravel-side env var, not Codemagic
# What's left below is the set we still genuinely need pasted into
# the Codemagic env group.

COMMON_VARS=()

IOS_VARS=(
  REVENUECAT_IOS_KEY
)

ANDROID_VARS=(
  REVENUECAT_ANDROID_KEY
  GOOGLE_OAUTH_CLIENT_ID_ANDROID
)

# Production-only sets are empty for now — Firebase configs come from
# the repo, and the Google Play credential check above already covers
# the Android production upload. The arrays stay declared so the case
# block below stays uniform.
IOS_PROD_VARS=()

ANDROID_PROD_VARS=()

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
# API_BASE_URL is now hardcoded in codemagic.yaml's .env-write step;
# BUILD_VERSION comes from pubspec.yaml; ANDROID_RELEASE_SHA256 lives
# on the Laravel host. None of the historical shape-checks for those
# apply here anymore. Add new checks above this line as future
# env vars get introduced.

echo "[preflight] ✓ all required env vars set for workflow=$WORKFLOW"
