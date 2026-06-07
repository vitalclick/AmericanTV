# Store metadata templates

Drop-in starter text for App Store Connect and Play Console fields. Edit
to taste before submission, but the structure mirrors what reviewers
expect for a creator-economy video app.

## App name

| Store | Value |
|---|---|
| App Store | `AmericanTV` |
| Play Store | `AmericanTV — Watch and Create` |

(App Store caps at 30 chars; Play Store at 50. Tagline integrated for
Play.)

## Subtitle / short description

| Store | Text (80 chars max for App Store subtitle, 80 for Play short) |
|---|---|
| App Store subtitle | `Watch creators. Subscribe. Upload your own.` |
| Play short description | `Watch creator-driven video. Subscribe to plans. Upload from your phone.` |

## Promotional text (App Store only, 170 chars)

```
Stream from American creators, save videos to watch later, and upload
your own — all from your phone. New plans drop weekly.
```

## Full description

Used as the App Store "Description" and the Play Console "Full
description" (4000 chars max).

```
AmericanTV is a creator-driven video platform built for people who want
to discover independent voices and support the people making them.

WATCH
 - Browse a feed curated to the channels and creators you follow.
 - Stream in adaptive HLS over Cloudflare — looks crisp on Wi-Fi,
   stays watchable on 4G.
 - Picture-in-Picture and background audio playback on iOS and Android.

SUBSCRIBE
 - Subscribe to creator-published monthly plans for unlimited access to
   their video catalog.
 - One-time purchase available on individual videos and playlists.
 - All purchases handled securely via Apple's App Store or Google Play
   Billing.

UPLOAD
 - Record video directly from your camera or pick from your library.
 - Chunked, resumable uploads survive backgrounding the app and
   spotty connections.
 - Mobile-native publish form lets creators set category, visibility,
   thumbnail, and tags without switching to a desktop browser.

ORGANIZE
 - Watch Later, History, and Purchased tabs keep your library at a
   glance.
 - Push notifications when channels you follow drop new content.

SAFETY
 - No cross-app tracking. We don't sell your data.
 - Sign in with Apple supported. Two-factor authentication available
   for your account.
 - Comments and watch-later changes queue locally when you're offline
   and sync the next time you're online.

About: AmericanTV is operated by VitalClick (vitalclick.co.za). For
support email support@americantv.vip or visit https://americantv.vip.
```

## Keywords (App Store only, 100 chars total, comma-separated)

```
video,streaming,creator,subscribe,independent,upload,channels,community,watch,player
```

## App Privacy questionnaire (App Store)

Source of truth is `mobile/native/ios/PrivacyInfo.xcprivacy`. The web
form on App Store Connect mirrors it. Quick answers:

| Question | Answer |
|---|---|
| Do you collect data? | Yes |
| Is data used for tracking? | **No** |
| Linked to user identity | Email, name, phone, purchase history, user content, support |
| Not linked | Crash data |
| Data types used for | Account, App Functionality, Analytics |
| Sold to third parties | No |

## Play Data Safety form

Similar shape; the Play Console form is more verbose. Map:

- **Personal info → Name, Email address, Phone number**: collected,
  shared **No**, processed ephemerally **No**, encrypted in transit
  **Yes**, can request deletion **Yes**.
- **Financial info → Purchase history**: collected, shared **No**,
  required for app **Yes** (creator-paid tier access).
- **App activity → App interactions**: collected for analytics +
  app functionality, shared **No**.
- **Files and docs → Videos**: collected (creator uploads), shared **No**.
- **Device or other IDs → Device ID** (FCM token): collected,
  shared **No**.

## Age rating

Both stores: **12+** / **PG-12**.

Rationale: user-generated content is moderated post-upload via the
existing admin moderation pipeline, but un-moderated comments can
contain mild language. No nudity, no gambling, no controlled-substances
content allowed by ToS.

## Support URL + privacy policy URL

| Field | URL |
|---|---|
| Support URL | https://americantv.vip/support |
| Privacy policy | https://americantv.vip/privacy |
| Terms of service | https://americantv.vip/terms |

Both stores require all three to be live and on the same root domain as
the app name.

## Screenshots required

### iOS (App Store Connect)

| Device | Resolution | Count required |
|---|---|---|
| 6.7" iPhone (15 Pro Max) | 1290 × 2796 | 3–10 |
| 6.5" iPhone (15 Plus) | 1284 × 2778 | 3–10 |
| 5.5" iPhone (8 Plus) | 1242 × 2208 | Optional but advised |
| 12.9" iPad Pro | 2048 × 2732 | Required if iPad is supported |

### Android (Play Console)

| Asset | Resolution / shape | Notes |
|---|---|---|
| Phone screenshots | min 320px, max 3840px (long side) | 2–8 screenshots. |
| 7" tablet | 1024 × 600+ | Optional. |
| 10" tablet | 1280 × 720+ | Optional. |
| Feature graphic | 1024 × 500 | **Required**. |
| App icon | 512 × 512 | High-res. |

The 6 most informative screens to capture (rank-ordered):

1. Feed tab with content visible.
2. A video detail screen with the play button + paywall badge visible
   (illustrates the unlock flow).
3. Video player full-screen with on-screen controls.
4. Subscribe paywall (plan picker bottom sheet).
5. Library tab → Purchased view (proves the monetization loop closes).
6. Creator upload screen with progress.

## App icon

Generate from a square 1024 × 1024 master PNG. Tools that handle the
iOS + Android + adaptive-icon variants automatically:

- `flutter_launcher_icons` (already a common Flutter dep; add to
  pubspec dev_dependencies + run `dart run flutter_launcher_icons`).

The current `assets/` directory in the Laravel side has an existing
brand mark. Resize / re-export the SVG at 1024 × 1024 with a 10%
padding-safe area as the source.

## Demo account for App Store Review

Apple and Google reviewers both need a working demo account. The
account is created and refreshed by `core/database/seeders/AppReviewDemoSeeder.php`:

```sh
# First time, or after each significant deploy:
cd core
php artisan db:seed --class=AppReviewDemoSeeder
```

The seeder:
- Creates / refreshes `appreview@americantv.vip` with email, mobile,
  and KYC flags pre-verified (no SMS roundtrip required for reviewers).
- Drops 3 public videos into the user's watch-later list so the
  Library tab isn't empty.
- Grants a complimentary 1-year subscription to the first active Plan
  via `PurchasedPlan` with `trx = 'APP_REVIEW_DEMO'`. Lets reviewers
  exercise the post-paywall flow without going through IAP.

To pin the password (so 1Password keeps a stable record), set
`APP_REVIEW_DEMO_PASSWORD` in the production `.env` BEFORE seeding.
First-time runs without the env var print a randomly-generated
password to the console — save it to 1Password immediately.

Add credentials to App Store Connect → App Information → App Review
Information → Sign-In Required → fill in. Same for Play Console →
App content → App access → Demo account section.

## Marketing URL

`https://americantv.vip` — must be live, must mention the app by name
in the page metadata so search ranking + App Store review both see the
linkage.
