# Codemagic setup — first-time configuration

Step-by-step for getting `codemagic.yaml` (at repo root) to run successfully against
production stores. Do these once; then the four workflows run unattended.

## Current state (3.0.0+14)

| Artifact | Status |
|---|---|
| Marketing version + build number | **3.0.0+14**, set in `mobile/pubspec.yaml` |
| Platform projects (`mobile/android/`, `mobile/ios/`) | Committed to the repo |
| App icon masters (`mobile/assets/icon/app-icon.png`, `app-icon-adaptive-foreground.png`) | Committed |
| Generated launcher icons | Committed under `mobile/ios/.../AppIcon.appiconset/` + `mobile/android/.../mipmap-*` + `drawable-*/` |
| Native iOS shim (`BackgroundUploadHandler.swift`) | Committed under `mobile/ios/Runner/` |
| Native Android shim (`BackgroundUploadHandler.kt`) | Committed under `mobile/android/app/src/main/kotlin/com/americantv/app/upload/` |
| Privacy manifest + entitlements | Committed under `mobile/ios/Runner/` |
| Proguard rules + network security config | Committed under `mobile/android/app/` |

Codemagic's pipeline still runs `flutter create . --no-overwrite` as a
safety net for fresh clones, but the canonical platform code is the one
in `main`.

## App identifiers (recap)

| Surface | Value |
|---|---|
| iOS bundle ID | `com.americantv.userapp` |
| iOS App Store ID | `6743315031` |
| Apple Team ID | `PDNU7JKBQZ` |
| Android package name | `com.americantv.app` |
| Play Store URL | https://play.google.com/store/apps/details?id=com.americantv.app |

## Quick-start checklist

Tick each item before triggering the first TestFlight / Internal build:

- [ ] Codemagic account connected to the GitHub repo (§1)
- [ ] App Store Connect API key uploaded as integration `americantv-app-store` (§2)
- [ ] Google Play service account JSON uploaded (§3)
- [ ] `uploadkey.jks` uploaded as `uploadkey`, alias `uploadkey` (§4)
- [ ] `ANDROID_RELEASE_SHA256` set in Laravel production `.env` (§4)
- [ ] `americantv-prod` env group populated (§5)
- [ ] App Store Connect record for `com.americantv.userapp` (§6)
- [ ] Play Console record for `com.americantv.app` (§7)
- [ ] `AppReviewDemoSeeder` run against production Laravel (§8)
- [ ] First TestFlight + Internal build green (§9)

## 1. Sign-up + connect the repo

1. Create / sign in to Codemagic.io.
2. **Apps → Add application → GitHub** → pick the `vitalclick/americantv`
   repo.
3. Codemagic auto-detects `codemagic.yaml` (at the repo root) and surfaces
   the four workflows in the UI. The file must live at the root —
   Codemagic doesn't expose a custom-path setting on the standard plan,
   so we keep it there even though the rest of the Flutter project lives
   under `mobile/`. The workflow scripts use `$CM_BUILD_DIR/mobile/...`
   for everything build-related, so file location and project location
   are decoupled.

## 2. App Store Connect integration

Required to (a) fetch signing certificates + provisioning profiles, and
(b) upload builds to TestFlight / App Store Review.

1. In App Store Connect → **Users and Access → Keys → App Store Connect API**
   → Generate a key. Role: **App Manager** (Admin works too but App Manager
   is the least-privilege option).
2. Save the `.p8` file. Record the Key ID and Issuer ID.
3. In Codemagic Teams → **Integrations → App Store Connect** → **Connect**.
4. Name the integration **`americantv-app-store`** (matches
   `codemagic.yaml`'s `integrations.app_store_connect` value).
5. Paste the Key ID, Issuer ID, and the `.p8` contents.

## 3. Google Play integration

Required to upload the AAB to internal / production tracks.

1. Google Cloud Console → Create a service account → no roles required at
   project level.
2. Service account → **Keys → Add key → JSON**. Download.
3. Google Play Console → **Setup → API access** → **Link existing project**
   pointing at the same GCP project.
4. Find the service account, click **Grant access**:
   - **App permissions**: limit to `com.americantv.app`.
   - **Account permissions**: **Release manager** (or just Release apps).
5. In Codemagic → **Teams → Integrations → Google Play** → upload the JSON.
   Reference it in the env group below as `GCLOUD_SERVICE_ACCOUNT_CREDENTIALS`.

## 4. Android keystore

Required to sign release AABs. **The keystore file MUST NOT be
committed to git** — `mobile/.gitignore` already blocks `*.jks` and
`*.keystore`, but the discipline matters: losing this file means the
next AAB Play Store sees won't validate, and there's no recovery path
short of re-publishing as a new app.

### If you already have a keystore (the live one)

The repo is configured for an upload keystore with alias **`uploadkey`**
(the standard alias Android Studio generates). If yours uses a different
alias, override `CM_KEYSTORE_ALIAS` in the env group.

1. Codemagic Teams → **Code signing identities → Android keystores** →
   **Upload**.
2. **Reference name**: `uploadkey` (must match `codemagic.yaml`'s
   `android_signing` entry).
3. Upload the `.jks` file.
4. **Keystore password**: provided by whoever generated the keystore.
5. **Key alias**: `uploadkey`.
6. **Key password**: usually the same as the keystore password.
7. Click **Save**.

### Extracting the SHA-256 fingerprint

The `assetlinks.json` endpoint serves this fingerprint so Android App
Links can verify the domain owns the app. The Laravel `WellKnownController`
reads it from `ANDROID_RELEASE_SHA256` in the production `.env`.

Use the helper script:

```sh
mobile/scripts/extract-keystore-sha256.sh /path/to/uploadkey.jks
```

It prompts for the keystore password, runs `keytool -list -v`, and
prints the SHA-256 + the exact lines to paste into `.env` + the
post-update verify command. Defaults to alias `uploadkey`; pass a
second argument to override.

Output looks like:

```
SHA256: AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99:AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99
```

Paste the value (after `SHA256:`, including the colons) into the
Laravel `.env` as:

```
ANDROID_RELEASE_SHA256="AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99:AA:BB:CC:DD:EE:FF:00:11:22:33:44:55:66:77:88:99"
```

Apply via `php artisan config:clear` then verify with:

```sh
curl https://americantv.vip/.well-known/assetlinks.json | jq
```

### If you need to generate a fresh keystore

Only if the live `uploadkey.jks` is genuinely lost AND the Play Store
record for `com.americantv.app` is brand-new (i.e. nothing's been
signed yet). On an existing app, re-keying requires Google's Play App
Signing key reset which is an Authentication-Required process.

```sh
keytool -genkey -v -keystore upload-keystore.jks \
  -alias uploadkey -keyalg RSA -keysize 4096 -validity 25000
```

Save the .jks + password to 1Password under "AmericanTV Android
Upload Keystore" before anything else.

## 5. Environment variable group

Codemagic Teams → **Environment variables → Add group** named
**`americantv-prod`**. Add these keys (all "Secure" except where noted):

| Key | Where to get it | Required? | Notes |
|---|---|---|---|
| `REVENUECAT_IOS_KEY` | RevenueCat dashboard → Project → API keys → iOS public SDK key | iOS only | `appl_...`. Public SDK key — shipped in binary. |
| `REVENUECAT_ANDROID_KEY` | RevenueCat dashboard → Android public SDK key | Android only | `goog_...`. Public SDK key — shipped in binary. |
| `GOOGLE_OAUTH_CLIENT_ID_ANDROID` | Google Cloud Console → OAuth client IDs (also visible in `mobile/android/app/google-services.json`) | Android only | For native Google Sign-In. Public identifier. |

Android workflows do **not** include `google_play` auto-publishing.
The AAB is surfaced as a Codemagic build artifact instead — download
it from the build's **Artifacts** tab and upload manually via the
Play Console. To switch back to automated publishing later, restore
the `publishing.google_play` block in `codemagic.yaml` and add
`GCLOUD_SERVICE_ACCOUNT_CREDENTIALS` (Google Play Console → API access
service account JSON, not Firebase Admin) to this env group.

The following are **not** env-group entries — they live elsewhere:

- **`API_BASE_URL`** — hardcoded inline in `codemagic.yaml` (`https://americantv.vip/api/v1`). Single environment, never rotates, no reason to indirect through Codemagic.
- **`GoogleService-Info.plist`** (iOS Firebase) — committed at `mobile/ios/Runner/GoogleService-Info.plist`. Drop the file in once from Firebase Console → Project settings → iOS app.
- **`google-services.json`** (Android Firebase) — committed at `mobile/android/app/google-services.json`. Same pattern from the Android tab.
- **`BUILD_VERSION`** — read from `mobile/pubspec.yaml`'s `version:` field. Bump there per release.
- **`BUILD_NUMBER`** — auto-set by Codemagic from the latest store version + 1.
- **`RELEASE_NOTIFY_EMAIL`** — hardcoded recipients in each workflow's `email.recipients` block. Edit YAML to rotate.
- **`BUNDLE_ID`** / **`PACKAGE_NAME`** / **`GOOGLE_PLAY_TRACK`** / **`ROLLOUT_FRACTION`** / **`APPLE_TEAM_ID`** — workflow-level vars in YAML, not in the env group.
App icon masters (`app-icon.png`, `app-icon-adaptive-foreground.png`)
are committed under `mobile/assets/icon/` and read directly from
disk by `flutter_launcher_icons` — no env-var override.

Static config (`BUNDLE_ID`, `PACKAGE_NAME`, `GOOGLE_PLAY_TRACK`,
`ROLLOUT_FRACTION`, `APPLE_TEAM_ID`) is hardcoded inline at the
workflow level in `codemagic.yaml`, not in this env group. Edit the
YAML to change them, not the Codemagic UI.

Once the group is saved, attach it to all four workflows (the YAML
references it via `groups: [americantv-prod]`).

The `codemagic-preflight.sh` script at the start of every workflow
verifies the required-for-this-workflow set is populated. Missing or
malformed values exit with an ASCII-box error in stderr so you see the
problem at second zero, not after 20 minutes of build.

## 6. App Store Connect — App record

1. App Store Connect → **My Apps → +**.
2. **Platform**: iOS.
3. **Bundle ID**: `com.americantv.userapp` (must match the YAML).
4. **SKU**: e.g. `americantv-ios`.
5. **User Access**: Full Access.
6. Confirm the App Store ID matches `6743315031`.

After the first TestFlight build lands, fill in the App Information /
Pricing / App Privacy sections — the workflow won't progress past
"Waiting for Review" without these.

## 7. Google Play Console — App record

1. Play Console → **Create app**.
2. Choose **App name**: AmericanTV.
3. **Package name**: `com.americantv.app` (must match the YAML).
4. **App or game**: App.
5. **Free or paid**: as appropriate.
6. Complete the Content rating + Target audience + Data safety
   declarations before promoting any build past Internal Testing.

## 8. App Review demo account

App Store Connect and Play Console both require working credentials so
reviewers can exercise the auth + paywall surface without registering.

```sh
cd core
php artisan db:seed --class=AppReviewDemoSeeder
```

The seeder is idempotent — running on every deploy is safe. It:
- Creates / refreshes `appreview@americantv.vip` with `ev = sv = kv =
  VERIFIED` (no 2FA, no SMS round-trip).
- Drops 3 public videos into the user's watch-later list so the
  Library tab isn't empty.
- Grants a complimentary 1-year `PurchasedPlan` against the first
  active `Plan` so reviewers see the post-subscribe content without
  going through IAP.
- Stamps a `UserLogin` row + `users.last_login` (if the column exists)
  to today, so admin "last seen" doesn't read "never".

Pin a stable password by setting `APP_REVIEW_DEMO_PASSWORD` in the
production `.env` **before** seeding. First-run without it generates a
random hex string and prints to stderr — save it to 1Password before
losing the deploy console.

Plug the credentials into:
- App Store Connect → App Information → App Review Information →
  Sign-In Required.
- Play Console → App content → App access → Demo account.

## 9. First build smoke test

1. Codemagic → **`ios-testflight`** workflow → **Start new build** →
   branch: `main` (or the PR branch pre-merge).
2. Watch the logs. The preflight step exits in seconds with a named
   missing env var if anything in §5 isn't set. Subsequent common
   failure modes:

   | Symptom | Fix |
   |---|---|
   | `fetch-signing-files: No bundle id matches` | App Store Connect → Identifiers must have `com.americantv.userapp`. |
   | `Pods failed to install` | Transient — re-run. CocoaPods CDN sometimes flakes. |
   | `flutter build ipa` exits with `MissingPluginException` | `flutter pub get` step in `base_scripts` failed earlier; check the log. |
   | `agvtool: ... not found` | The "Set bundle identifier + version" step assumes Xcode 15+; confirm `xcode: latest` resolved. |
   | `keychain initialize` permission error | API key needs App Manager (not Developer) role. |
   | `[android-signing] WARNING: CM_KEYSTORE_PATH is empty` | Codemagic didn't find a keystore named `uploadkey`. Re-check §4. |
   | `PHP Fatal error: Access level to Tests\TestCase::createApplication() must be public` | Old commit — pull latest; fixed in `c025a35`. |

3. After IPA upload, App Store Connect → TestFlight → Internal Testing →
   Add the build → Add testers.

4. Repeat for `android-internal`. Successful build = AAB on the Internal
   Testing track of `com.americantv.app`.

## 10. Promoting to production

- **iOS**: After successful TestFlight runs, switch the workflow trigger
  to `ios-app-store`. The next build submits to App Store Review (not
  TestFlight). Apple's review takes 24–48h.
- **Android**: Switch to `android-production`. Codemagic uploads to the
  Production track at 10% rollout (`ROLLOUT_FRACTION: 0.1`). Bump the
  percentage in Play Console as confidence grows.

## Cost ballpark

- Codemagic free tier: 500 build minutes/month. A clean iOS build is
  ~20 min, Android ~10 min. Enough for ~15 iOS releases or ~50 Android
  releases monthly.
- iOS uses `mac_mini_m2`; Android uses `linux_x2`. Both are at the
  cheaper end of the instance grid.

## Manual escape hatches

If Codemagic is down or you need to ship faster than the queue:

- iOS: `flutter build ipa` locally + Transporter app upload.
- Android: `flutter build appbundle` locally + `gradle publishReleaseBundle`.

Both require the same signing materials this YAML configures, so
"keep the keystore + App Store Connect API key safe" is the only
discipline that matters either way.

The per-release `mobile/docs/DEPLOYMENT.md` runbook is the operational
companion to this setup doc — once §1–§8 are done, you live in
`DEPLOYMENT.md` for the rest of the app's life.
