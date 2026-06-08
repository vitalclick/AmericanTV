#!/usr/bin/env bash
#
# Runs as the first script in each Codemagic workflow. The set of env
# vars the build genuinely cannot proceed without has shrunk to zero —
# every remaining variable in the americantv-prod env group is a
# runtime feature toggle, not a build prerequisite. So preflight is now
# advisory: it logs which optional features are configured and which
# will degrade gracefully at runtime, then always exits 0.
#
# Add back to REQUIRED + a hard-fail block if any future variable
# actually blocks the build.

set -uo pipefail

# ----- optional vars per workflow ----------------------------------------
# Variables that previously lived here moved out as config matured:
#   API_BASE_URL              -> hardcoded inline in codemagic.yaml
#   BUILD_VERSION             -> read from mobile/pubspec.yaml
#   RELEASE_NOTIFY_EMAIL      -> hardcoded inline in email.recipients
#   APP_ICON_*                -> masters committed at mobile/assets/icon/
#   FIREBASE_GOOGLE_*         -> committed at the canonical Flutter paths
#   ANDROID_RELEASE_SHA256    -> Laravel-side env var, not Codemagic
#   GCLOUD_SERVICE_ACCOUNT_*  -> AAB is artifact-only; manual upload now
# What's left is purely runtime-feature gates.

IOS_OPTIONAL=(
  REVENUECAT_IOS_KEY:"iOS in-app purchases via RevenueCat"
)

ANDROID_OPTIONAL=(
  REVENUECAT_ANDROID_KEY:"Android in-app purchases via RevenueCat"
  GOOGLE_OAUTH_CLIENT_ID_ANDROID:"native Sign in with Google"
)

WORKFLOW="${CM_WORKFLOW_ID:-${WORKFLOW:-unknown}}"

declare -a CHECK=()
case "$WORKFLOW" in
  ios-testflight|ios-app-store) CHECK+=("${IOS_OPTIONAL[@]}") ;;
  android-internal|android-production) CHECK+=("${ANDROID_OPTIONAL[@]}") ;;
esac

declare -a INACTIVE=()
declare -a ACTIVE=()
for entry in "${CHECK[@]}"; do
  VAR="${entry%%:*}"
  DESC="${entry#*:}"
  if [ -z "${!VAR-}" ]; then
    INACTIVE+=("$VAR ($DESC)")
  else
    ACTIVE+=("$VAR ($DESC)")
  fi
done

echo "[preflight] workflow=$WORKFLOW"
if [ "${#ACTIVE[@]}" -gt 0 ]; then
  echo "[preflight] features active:"
  for line in "${ACTIVE[@]}"; do echo "[preflight]   ✓ $line"; done
fi
if [ "${#INACTIVE[@]}" -gt 0 ]; then
  echo "[preflight] features inactive (env var unset — feature degrades silently at runtime):"
  for line in "${INACTIVE[@]}"; do echo "[preflight]   ✗ $line"; done
fi

exit 0
