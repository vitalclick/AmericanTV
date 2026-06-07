# Codemagic setup — first-time configuration

Step-by-step for getting `mobile/codemagic.yaml` to run successfully against
production stores. Do these once; then the four workflows run unattended.

## App identifiers (recap)

| Surface | Value |
|---|---|
| iOS bundle ID | `com.americantv.userapp` |
| iOS App Store ID | `6743315031` |
| Apple Team ID | `PDNU7JKBQZ` |
| Android package name | `com.americantv.app` |

## 1. Sign-up + connect the repo

1. Create / sign in to Codemagic.io.
2. **Apps → Add application → GitHub** → pick the `vitalclick/americantv`
   repo.
3. Codemagic auto-detects `mobile/codemagic.yaml` and surfaces the four
   workflows in the UI.

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
2. **Reference name**: `americantv-keystore` (must match `codemagic.yaml`'s
   `android_signing` entry).
3. Upload the `.jks` file.
4. **Keystore password**: provided by whoever generated the keystore.
5. **Key alias**: `uploadkey`.
6. **Key password**: usually the same as the keystore password.
7. Click **Save**.

### Extracting the SHA-256 fingerprint

The `assetlinks.json` endpoint serves this fingerprint so Android App
Links can verify the domain owns the app. The Laravel `WellKnownController`
reads it from `ANDROID_RELEASE_SHA256` in the production `.env`:

```sh
# Run locally with the keystore file at hand; Codemagic doesn't need
# to know this value.
keytool -list -v -keystore /path/to/upload-keystore.jks -alias uploadkey \
  | grep "SHA256:"
```

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

| Key | Where to get it | Notes |
|---|---|---|
| `API_BASE_URL` | Your Laravel host | e.g. `https://americantv.vip/api/v1` |
| `REVENUECAT_IOS_KEY` | RevenueCat dashboard | `appl_...` |
| `REVENUECAT_ANDROID_KEY` | RevenueCat dashboard | `goog_...` |
| `GOOGLE_OAUTH_CLIENT_ID_ANDROID` | Google Cloud Console → OAuth client IDs | For native Google Sign-In on Android. |
| `FIREBASE_GOOGLE_SERVICE_INFO_PLIST` | base64 of iOS `GoogleService-Info.plist` | `base64 -i GoogleService-Info.plist`. Empty string = Firebase skipped. |
| `FIREBASE_GOOGLE_SERVICES_JSON` | base64 of Android `google-services.json` | `base64 -i google-services.json`. Empty = Firebase skipped. |
| `RELEASE_NOTIFY_EMAIL` | Any email | Build success/failure notifications. Not secret. |
| `BUILD_VERSION` | Bump per release (e.g. `1.0.0`) | Marketing version. Not secret. |
| `BUILD_NUMBER` | Auto-set by Codemagic from latest+1 | Don't override unless re-uploading the same version. |
| `APP_ICON_PNG_B64` | base64 of `assets/icon/app-icon.png` | `base64 -i app-icon.png`. Master 1024×1024 icon (no alpha). |
| `APP_ICON_ADAPTIVE_FG_PNG_B64` | base64 of adaptive-foreground PNG | `base64 -i app-icon-adaptive-foreground.png`. Transparent background. |
| `ANDROID_RELEASE_SHA256` | SHA-256 of upload keystore | `keytool -list -v -keystore americantv-release.keystore -alias americantv \| grep "SHA256:"`. Powers `/.well-known/assetlinks.json` on the Laravel side. |

Once the group is saved, attach it to all four workflows (the YAML
references it via `groups: [americantv-prod]`).

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

## 8. First build smoke test

1. Codemagic → **`ios-testflight`** workflow → **Start new build** →
   branch: `claude/analyze-codebase-PEpEp` (or `main` after merge).
2. Watch the logs. Common first-time errors and fixes:

   | Symptom | Fix |
   |---|---|
   | `fetch-signing-files: No bundle id matches` | Confirm App Store Connect "Identifiers" has `com.americantv.userapp` registered. |
   | `Pods failed to install` | The base scripts run `flutter create` to materialize `ios/` — make sure your branch has the latest `pubspec.yaml`. |
   | `flutter build ipa` exits with `MissingPluginException` | The `flutter pub get` step is part of `base_scripts`; if it didn't run, the build will fail at IPA stage. |
   | `agvtool: ... not found` | The "Set bundle identifier + version" step assumes Xcode 15+. Confirm the workflow's `xcode: latest` directive resolved as expected in logs. |
   | `keychain initialize` permission error | Re-create the App Store Connect API key with App Manager (not Developer) role. |

3. After IPA upload, App Store Connect → TestFlight → Internal Testing →
   Add the build → Add testers.

4. Repeat for `android-internal`. Successful build = AAB on the Internal
   Testing track of `com.americantv.app`.

## 9. Promoting to production

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
