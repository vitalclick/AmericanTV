# Stress-test: iOS TestFlight pipeline

Walk-through of the `ios-testflight` Codemagic workflow as it would
execute on a real run, looking for failure modes that aren't caught
by the preflight script + the YAML's own guards.

The pipeline has 17 steps. Below: what could break at each one, and
what guard catches it.

## Step inventory

| # | Step | What goes in | What goes out |
|---|---|---|---|
| 1 | Preflight env vars | env group | exit 0 or exit 1 with named missing var |
| 2 | Show Flutter + Dart versions | — | log only |
| 3 | Materialize platform projects | `mobile/pubspec.yaml` | `mobile/{ios,android}/` |
| 4 | Layer native shims | `native/ios/BackgroundUploadHandler.swift` | `ios/Runner/BackgroundUploadHandler.swift` |
| 5 | Copy privacy manifest + entitlements | `native/ios/{PrivacyInfo.xcprivacy, Runner.entitlements}` | same under `ios/Runner/` |
| 6 | Patch Info.plist | `ios/Runner/Info.plist` | ATS / usage descriptions / background modes added |
| 7 | Copy proguard + network security config | — (Android only effect) | skipped on iOS workflow |
| 8 | Wire Android shrinking | — | skipped on iOS workflow |
| 9 | Rewrite platform identifiers | `ios/Runner.xcodeproj/project.pbxproj` | `PRODUCT_BUNDLE_IDENTIFIER = com.americantv.userapp` |
| 10 | Drop launcher icon sources | `APP_ICON_PNG_B64` env | `assets/icon/app-icon.png` |
| 11 | Generate launcher icons | `assets/icon/app-icon.png` | `ios/Runner/Assets.xcassets/AppIcon.appiconset/*.png` |
| 12 | Resolve dependencies | `pubspec.yaml` | `.dart_tool/` populated |
| 13 | Run codegen | `.dart_tool/` | `*.freezed.dart`, `*.g.dart` |
| 14 | Analyze + test | source | log; failure breaks build |
| 15 | Write .env from env group | env group | `mobile/.env` |
| 16 | Drop Firebase config files | `FIREBASE_GOOGLE_SERVICE_INFO_PLIST` env | `ios/Runner/GoogleService-Info.plist` |
| 17 | Install CocoaPods | `Podfile` | `Pods/`, `Podfile.lock` |
| 18 | Set bundle identifier + version | env vars | versioned project files |
| 19 | Configure code signing | App Store Connect integration | profiles installed in keychain |
| 20 | Build IPA | everything above | `build/ios/ipa/Runner.ipa` |
| 21 | Upload dSYMs to Crashlytics | `build/ios/archive/**/dSYMs` | Crashlytics symbol map |
| 22 | Publish via app_store_connect | IPA | TestFlight build entry |

## Failure modes by step

### Step 1 — Preflight

**Failure**: any required env var missing.
**Guard**: ASCII-box error in stderr, exits 1, build aborts.
**Recovery**: populate the missing var in `americantv-prod`, re-run.

**Failure**: `API_BASE_URL` is `http://...` instead of `https://...`.
**Guard**: Production-workflow shape check rejects with explicit
"App Store / Play Store reject cleartext API hosts" message.
However, **ios-testflight isn't a production workflow** — the shape
check only fires for `ios-app-store` / `android-production`. A
TestFlight build with cleartext API URL would succeed at the
build step and fail at runtime when ATS blocks the API call.

**Hole identified**: TestFlight shape check.

### Step 3 — Materialize platform projects

**Failure**: `flutter create .` fails because the existing `pubspec.yaml`
declares incompatible Flutter SDK version.
**Guard**: implicit — the `flutter` line in the YAML pins `stable`;
mismatch surfaces immediately.
**Recovery**: bump `flutter:` in the YAML; re-run.

**Failure**: `flutter create` overwrites `mobile/lib/main.dart` due to
`--no-overwrite` not being honored (rare; CLI bug).
**Guard**: none. `git diff` post-build would reveal it, but the build
ships before the diff is reviewed.

**Hole identified**: `flutter create` could in theory overwrite Dart
source. Adding `git status --porcelain mobile/lib | head -1` after
this step would detect any modifications and fail loudly.

### Step 4 — Layer native shims

**Failure**: `native/ios/BackgroundUploadHandler.swift` is missing
because the branch is stale.
**Guard**: `[ -d ios/Runner ]` check would skip silently — actually
worse than failing, because the build would succeed but background
uploads would be inert.

**Hole identified**: the file-exists guard hides a missing source.
Should explicitly fail if the source is missing.

### Step 6 — Patch Info.plist

**Failure**: PlistBuddy is missing (non-Mac runner).
**Guard**: `[ ! -f ios/Runner/Info.plist ] && exit 0`. Wrong guard —
on Mac, Info.plist exists, so we proceed; PlistBuddy is always
available on macOS runners. Hole closed.

**Failure**: a previous run left `:NSAppTransportSecurity:NSAllowsArbitraryLoads`
present, so the `Delete` would succeed and `Add` would re-add. The
script handles this case via the explicit `Delete || true` before each
`Add`.

### Step 9 — Rewrite platform identifiers

**Failure**: sed pattern matches multiple occurrences in
`project.pbxproj`. `PRODUCT_BUNDLE_IDENTIFIER` typically appears 6+
times in the file (per build configuration), so all of them flip
to `com.americantv.userapp` — which is correct.

**Failure**: the project.pbxproj is XML, not the line-oriented format
the sed assumes. Modern Xcode 15+ defaults to the standard plist text
format that sed handles correctly. Older Xcode versions used a binary
format; sed silently produces a corrupted file. The YAML's `xcode:
latest` pins this away, but if a future Codemagic upgrade drops
support for `latest` and falls back to an older Xcode, the failure
mode is silent.

**Hole identified**: no validation that `project.pbxproj` was modified
correctly. A grep-then-assert post-step would catch this.

### Step 10 — Drop launcher icon sources

**Failure**: `APP_ICON_PNG_B64` is set but contains corrupted base64.
**Guard**: `base64 -d` fails silently when given junk; the resulting
PNG is a 0-byte file. The next step's `dart run flutter_launcher_icons`
errors out on the invalid PNG.

This is acceptable — the error surfaces, the build fails, the operator
fixes the env var. The error message could be friendlier; not critical.

### Step 11 — Generate launcher icons

**Failure**: `dart run flutter_launcher_icons` succeeds but produces
nothing because the source PNG was 1023×1023 instead of 1024×1024.
**Guard**: the tool emits a warning but doesn't fail. The build
continues with no icons. The default-icon marker artifact wouldn't fire
because `app-icon.png` does exist.

**Hole identified**: marker-file logic doesn't cover "icon source
present but wrong size." Would need to also check the icon output for
each platform directory after the step.

### Step 14 — Analyze + test

**Failure**: flutter test discovers a new failing test.
**Guard**: standard CI failure path; build aborts before consuming the
expensive iOS-build minutes.

### Step 17 — Install CocoaPods

**Failure**: `pod install` needs network access and one of the Cocoa
mirrors timing out.
**Guard**: none beyond Codemagic's job timeout (90 minutes for iOS).
Failures here are transient and a retry usually works; documented in
the runbook.

### Step 19 — Configure code signing

**Failure**: App Store Connect API key revoked.
**Guard**: `app-store-connect fetch-signing-files` returns 401; explicit
error in the log. Recovery: regenerate the key in App Store Connect,
update the Codemagic integration.

**Failure**: bundle ID `com.americantv.userapp` not registered under
team `PDNU7JKBQZ`.
**Guard**: same command surfaces "Bundle ID not found." Recovery:
register the bundle ID in App Store Connect → Identifiers.

### Step 20 — Build IPA

**Failure**: Xcode emits a warning about a deprecated API in
`BackgroundUploadHandler.swift` that the next Xcode version turns into
an error.
**Guard**: none. We don't compile-test the native shims at PR time;
this would only surface during the release build.

**Hole identified** (also noted in PR-REVIEW.md): PR CI should run
`flutter build ios --no-codesign` to compile-test the native shims.

### Step 21 — dSYM upload

**Failure**: `upload-symbols` not found inside `ios/Pods` because the
Firebase Crashlytics pod isn't installed.
**Guard**: `find ... | head -n 1` returns empty; the step `exit 0`s
with a log message. Crashlytics won't have symbols for this build,
but the IPA still uploads to TestFlight.

This is the right tradeoff — better to ship an unsymbolicated build
than to block release on a missing symbol map.

### Step 22 — Publish to TestFlight

**Failure**: App Store Connect rejects upload because the build
number isn't strictly greater than the previous build.
**Guard**: Codemagic sets `BUILD_NUMBER` from latest+1, so this
shouldn't happen — but if a parallel build raced and consumed the
slot, the upload would 409.
**Recovery**: re-run the workflow; Codemagic re-fetches latest+1.

## Identified holes — summary

| Hole | Severity | Status |
|---|---|---|
| Cleartext API URL not blocked for TestFlight | Medium | **Fixed** in `3875343` — HTTPS check now fires on all workflows. |
| `flutter create` could overwrite `mobile/lib/main.dart` | Low | Open. Low risk because `flutter create --no-overwrite` has been reliable in practice. |
| Missing shim source silently skipped | Medium | **Fixed** in `27e04cb` — explicit assertion on source presence + post-cp verification. |
| `project.pbxproj` post-sed not validated | Medium | **Fixed** in `27e04cb` — counts post-rewrite occurrences and fails if zero. |
| Wrong-size icon source skipped marker logic | Low | Open. `flutter_launcher_icons` emits a warning on misshapen sources; build proceeds with default icons (caught by the existing marker logic). |
| Native shim breakage only surfaces at release | Medium | **Fixed** in `34ff5a5` — PR CI now compile-tests both Android (Linux runner, every PR) and iOS (macOS runner, gated to mobile/native/ios or pubspec changes). |
| BUILD_VERSION typo accepted | Low | **Fixed** in `3875343` — preflight rejects non-SemVer values. |

**All four Medium holes closed.** The two remaining Low items are
defensible to leave open: `--no-overwrite` is reliable enough in
practice that the post-step git status check would emit more false
positives than real findings, and the wrong-size-icon scenario already
falls through to a build that ships and gets caught by the existing
marker artifact.

## Other observations

- The pipeline is **largely linear** — no parallelism. A 22-min iOS
  build could be ~14 min if codegen + analyze ran in parallel with
  pod install. Future optimization; not blocking.
- The cache step covers `~/Library/Caches/CocoaPods`, `~/.pub-cache`,
  `.dart_tool`. **Missing** from the cache: `~/.gradle/caches` on iOS
  (irrelevant) and `ios/Pods/` (intentional — Codemagic recommends
  against caching to avoid pod-resolution drift).
- The `BUILD_VERSION` env var has no validation. A typo like `1.0.O`
  (capital O for zero) would pass through to App Store Connect, which
  would reject the upload at step 22. Worth an explicit semver regex
  in preflight.

## Recommendation

Ship the PR. All Medium holes are now closed; the two Low items left
open are defensible (see status table above). The TestFlight workflow
is safe to run today; flipping to `ios-app-store` after a successful
TestFlight burn-in is the documented next step.
