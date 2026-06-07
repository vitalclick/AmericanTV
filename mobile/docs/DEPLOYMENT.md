# Deployment runbook — AmericanTV mobile

End-to-end runbook for shipping a new version of the AmericanTV mobile app
to TestFlight / Play Internal → App Store / Play Production.

## Identifiers

| Surface | Value |
|---|---|
| iOS bundle ID | `com.americantv.userapp` |
| iOS App Store ID | `6743315031` |
| Apple Team ID | `PDNU7JKBQZ` |
| Android package | `com.americantv.app` |
| Play Store URL | https://play.google.com/store/apps/details?id=com.americantv.app |

## First-time setup (do once)

See [CODEMAGIC_SETUP.md](./CODEMAGIC_SETUP.md). Walks through App Store
Connect API key, Google Play service account, Android keystore, env
variable group, and the App / Play store records.

## Pre-flight checks (every release)

Before bumping the version:

- [ ] Laravel backend deployed to `$API_BASE_URL`. New API endpoints
      (if any) covered by `core/tests/Feature/Api/`.
- [ ] `flutter analyze` clean locally.
- [ ] `flutter test` passes.
- [ ] `core/vendor/bin/phpunit` passes.
- [ ] No `gs()` keys referenced by new controllers that aren't in
      `TestCase::installGsStub()`. Run `GsStubDefaultsTest` to confirm.
- [ ] If new IAP plans were added, `IapProductSeeder` has run on prod.
- [ ] If new HLS-eligible videos were uploaded, `php artisan
      stream:backfill --limit=N` to migrate the backlog.
- [ ] App Review demo account is healthy:
      `php artisan db:seed --class=AppReviewDemoSeeder`.
      Verifies the account exists, ev/sv/kv flags are set, watch-later
      has 3 videos, and a complimentary plan subscription is active.

## Version bump

`mobile/pubspec.yaml` carries `version: X.Y.Z+B` where X.Y.Z is the
marketing version and B is the build number. Codemagic overrides B
automatically (App Store Connect's latest + 1); X.Y.Z is what you bump
per release.

```bash
# Edit pubspec.yaml — bump the marketing version, e.g. 1.0.0 -> 1.0.1.
git commit -am "bump: 1.0.1"
git tag v1.0.1
git push origin main --tags
```

The `BUILD_VERSION` env var on Codemagic should match this tag.

## Release notes

Add a one-paragraph "What's new" entry for each store:

- App Store Connect → TestFlight → What to Test → fill in.
- Play Console → Internal testing → release notes field → fill in.

If the text differs between stores (different audiences), that's fine.

## Release path: TestFlight → App Store

### 1. Push to TestFlight

Codemagic → **`ios-testflight`** workflow → **Start new build**.
Branch: `main`. Average wall-clock: ~22 minutes.

Watch the **Set bundle identifier + version** step — confirms the right
identifier landed.

When green:

- App Store Connect → TestFlight → Builds tab → new build appears in ~5
  minutes after the IPA upload (Apple processing time).
- Once "Ready to Submit" lights up, push to the **Internal Testers**
  group.

Testers get an email; they install via the TestFlight app.

### 2. Burn-in window

Recommended: minimum **24 hours** in TestFlight before submitting for
App Store Review. Targets:

- 5+ install events from internal testers.
- No new Crashlytics events in the priority queue.
- No spike in `app_events.name = 'video_play_session_ended'` with
  `watched_seconds < 5` (suggests playback regression).

### 3. Submit for review

Codemagic → **`ios-app-store`** workflow → **Start new build**.
Branch: `main`. Bumps `BUILD_NUMBER` again (TestFlight uses one too).

When green:
- App Store Connect → Apps → AmericanTV → version → review submission
  page auto-populates with the latest build.
- Click **Submit for Review**.

Apple review: typically 24–48 hours. Status visible in App Store Connect.

### 4. After approval

- Choose **Manual Release** during submission, then release from the
  App Store Connect dashboard when ready.
- Watch crash-free sessions for the first 12 hours via Firebase
  Crashlytics or App Store Connect's Diagnostics tab.

## Release path: Internal Testing → Production

### 1. Push to Internal Testing

Codemagic → **`android-internal`** workflow → **Start new build**.
Branch: `main`. Wall-clock: ~10 minutes (Linux x2 is fast).

When green:
- Play Console → Internal testing → Releases → new release in **Active**.
- Testers install via the opt-in link.

### 2. Burn-in window

Same 24-hour minimum. Play Console → Statistics for install / crash data.

### 3. Promote to Production

Codemagic → **`android-production`** workflow → **Start new build**.
Branch: `main`.

The workflow's `ROLLOUT_FRACTION: 0.1` deploys to **10%** of users at
first. Bump in Play Console:
- 10% → 25% (next day if metrics clean)
- 25% → 50% (next day)
- 50% → 100% (next day)

Three-day staged rollout is the conservative default. Compress if you've
already TestFlight'd the corresponding iOS build and have confidence in
shared backend changes.

## Rollback

### iOS

App Store Connect → Apps → AmericanTV → **App Store** tab → **Version
History** → previous version → **Phased Release** → set to **Resume**.

This re-promotes the older binary to the App Store. New downloads get
the previous version; existing installs stay where they are until
Apple's update system pulls.

For an emergency, expedite via App Store Connect → request expedited
review (use sparingly; Apple tracks frequency).

### Android

Play Console → Production → Releases → previous release → **Restart
rollout**. Within 5 minutes, downloads serve the previous AAB.

Existing installs roll back via Play Store auto-update on next check.

## Crashlytics symbol upload

Both platforms now upload symbols automatically:

- **iOS**: the `Upload dSYMs to Crashlytics` step in both ios workflows
  finds the FirebaseCrashlytics `upload-symbols` binary inside the
  CocoaPods install and feeds it the dSYMs from the build archive.
  Skips gracefully if Firebase isn't configured (no
  `FIREBASE_GOOGLE_SERVICE_INFO_PLIST` env var).
- **Android**: the Crashlytics Gradle plugin uploads the obfuscation
  mapping (`mapping.txt`) automatically when `minifyEnabled = true` on
  the release buildType.

If a crash on TestFlight still shows as a hex address tree, check the
"Upload dSYMs" step log for the actual upload command line — likely the
dSYMs directory wasn't found.

## Feature flag suggestions

Long-running feature work that's risky to ship un-gated should be
behind one of these flags (none of which exist yet — add when needed):

- `gs('mobile_iap_enabled')` — kill switch for IAP if RevenueCat
  webhook misbehaves.
- `gs('mobile_uploads_enabled')` — kill switch for creator uploads.
- `gs('mobile_hls_only')` — toggle between HLS and MP4 fallback in
  PlaybackController.

A `gs()` flag is server-side, so flipping it doesn't require a new
build — only a Laravel deploy.

## On-call notes

Common issues + first-line response:

| Symptom | First check |
|---|---|
| Mobile crashes on launch | Firebase Crashlytics; usually a Sanctum token format mismatch or a missing env var. |
| "Video won't play" reports | `videos.hls_status` column for the affected video; the Cloudflare Stream webhook may not have fired. |
| IAP failures | RevenueCat dashboard; cross-check `deposits.iap_transaction_id` for a corresponding row. |
| Push not arriving | `DeviceToken` table for the user; FCM/APNs dashboard for delivery confirmations. |
| Offline ops piling up | `app_events.name = 'comment_dropped'` and `'watch_later_dropped'`; check the user's `Sanctum` token validity. |

## Long-term build health

- Codemagic free tier covers ~15 iOS releases or ~50 Android releases
  monthly. If you ship more often, switch to paid (cheapest tier ~$28/mo).
- iOS build wall-clock will grow as pubspec.yaml deps grow. The cache
  steps in `codemagic.yaml` keep it ~6 min on cache hit, ~22 min on miss.
- Apple bumps min iOS SDK version periodically. Watch the deprecation
  warnings in xcode-project use-profiles output for early signals.
