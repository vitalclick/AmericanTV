# Flutter vs. React Native — Decision Matrix for AmericanTV

Scope: a video-streaming app with a creator-economy paywall and ads on top of
an existing Laravel backend. iOS + Android, one team, ~5 month timeline to
launch.

This document is opinionated. Where it's a judgement call, that's noted.

## TL;DR

Pick **Flutter** unless your team has substantial JavaScript / React experience
and no Dart experience — in which case pick **React Native (with Expo)**.

The decision matters less than people think. Both ship great video apps. The
real risk lives in the backend (HLS migration, IAP receipt verification) and in
App Store / Play Console policy compliance, not in framework choice.

---

## What we actually need from the framework

Ranked by how much this project depends on it.

| Need | Why it matters here |
|---|---|
| **HLS video playback with PiP, background audio, ABR, AirPlay/Chromecast** | Core product is video |
| **StoreKit 2 + Play Billing wrappers with reliable receipt callbacks** | Whole monetization stack runs through IAP |
| **AdMob (or competitor) SDK** | Free-tier monetization |
| **FCM push** | Existing `DeviceToken` model expects it |
| **Sign in with Apple + Google** | Apple App Store requirement |
| **Native scroll perf for an infinite feed (shorts)** | Shorts is a TikTok-style use case |
| **Reliable platform builds + CI** | Solo team can't fight tooling |
| **Mature analytics / crash reporting** | Day-1 visibility |

---

## Side-by-side

| Dimension | Flutter | React Native (Expo) | Winner |
|---|---|---|---|
| **HLS player** | `video_player` (official) + `better_player` / `chewie` for UI. Wraps AVPlayer/ExoPlayer. PiP + background work. Chromecast via `flutter_chromecast`. | `react-native-video` (very mature), `expo-av` deprecating in 2025 in favor of `expo-video`. PiP + Chromecast supported. | **Tie**. Both wrap the same native APIs. |
| **StoreKit / Play Billing** | `in_app_purchase` (Google-maintained). Battle-tested. Subscription edge cases require code on top. | `react-native-iap` (community, very widely used) or **RevenueCat** SDK (recommended either way). | **Tie** — but if you use **RevenueCat** on either platform, it neutralises 80% of the IAP complexity. Strongly recommend it. |
| **AdMob** | `google_mobile_ads` (Google-official). | `react-native-google-mobile-ads`. | **Flutter** by a small margin — Google maintains it directly. |
| **FCM push** | `firebase_messaging` (Google-official). | `@react-native-firebase/messaging` (community, very mature) or Expo Notifications wrapping APNs+FCM. | **Tie**. |
| **Sign in with Apple** | `sign_in_with_apple` (community, mature). | `@invertase/react-native-apple-authentication`. | **Tie**. |
| **Shorts-style infinite scroll perf** | Native rendering, 60fps default, low jank. | Needs `FlashList` (Shopify) over `FlatList` to hit native perf at scale. | **Flutter** — meaningfully better on TikTok-style feeds without optimisation work. |
| **iOS look-and-feel for taps/scroll/keyboard** | Cupertino widgets are close, never quite right. | Uses real native components. Feels more iOS-native by default. | **React Native** |
| **Hot reload / dev DX** | Excellent stateful hot reload. | Fast Refresh; sometimes flaky with native module changes. | **Flutter** |
| **Build size** | iOS: ~15-20MB starting binary. Android: ~7MB. | iOS: ~25-30MB. Android: ~15MB. | **Flutter** |
| **CI / release tooling** | Codemagic / Bitrise / Fastlane all first-class. | Expo Application Services (EAS) is excellent if you use Expo. Otherwise Fastlane. | **React Native (Expo)** if you want managed CI. |
| **Hiring pool (2026)** | Dart is a learnt skill; Flutter market is large but smaller than RN. | Massive — any React dev can ramp. | **React Native** |
| **Web/desktop reuse** | Flutter web/macOS/Windows is real but rough for video. | RN web exists; usable for simple screens. | **Tie** — neither matters for v1. |
| **Backend team integration** | Dart codegen from OpenAPI is good (`openapi-generator`, `chopper`). | TS codegen is excellent; types flow back to your backend if you use a typed API spec. | **React Native** — TS+OpenAPI is the smoothest pipeline. |

---

## How this maps to your specific project

### Things that push toward Flutter

1. **Shorts feed.** A TikTok-style vertical scroller with autoplay video is the
   feature most likely to embarrass you at scale. Flutter hits 60fps with less
   tuning. If shorts is a key differentiator, weight this heavily.
2. **Build size.** Smaller install = better conversion in emerging markets,
   which your gateway mix (Razorpay, Paystack, Aamarpay, BKash) suggests you
   care about.
3. **Single-team velocity.** Flutter's hot reload is genuinely better. With a
   small team this compounds.

### Things that push toward React Native

1. **Team background.** If you (or whoever you hire) write TypeScript daily,
   the ramp on RN is days; Dart is weeks. This usually decides it.
2. **iOS feel.** If your audience skews iPhone-heavy and design polish on iOS
   matters, RN's native components have a slight edge before you spend any
   styling time.
3. **OpenAPI → TypeScript.** Your backend already produces an OpenAPI spec
   (we just wrote it). Generated TS types in a shared monorepo is a workflow
   advantage that's hard to match in Dart.

### Things that don't matter

- **"Performance"** in the abstract. Both are fast enough. The video player is
  native in both cases.
- **"Future-proofing".** Both frameworks have Google/Meta-scale backing. Neither
  is going away.
- **"Code reuse with web".** You have a Laravel/Blade web app. You won't share
  code with it regardless of framework.

---

## Cost / staffing

Assuming you're hiring one mobile dev + using your backend dev for the API:

| | Flutter | RN/Expo |
|---|---|---|
| Senior contractor day rate (rough, 2026) | $600-900 | $700-1000 |
| Time-to-first-build | half a day | half a day with EAS |
| Time-to-first-ad-hoc-distribution | 1-2 days (TestFlight) | 1-2 days (TestFlight via EAS) |
| Bus-factor risk | Lower talent pool but devs are usually generalist | Larger pool, but expertise varies wildly |

Either way, expect ~4-5 months of one full-time mobile dev to ship a polished
v1 (Phase 1-3 from the earlier plan). Add ~2 months if doing native iOS + native
Android instead.

---

## My recommendation, ranked

1. **Flutter + RevenueCat for IAP + Firebase for push/analytics.** Best default
   for your shape of product (video-heavy, paywall, ads, infinite-scroll
   shorts). Smallest binary, best feed perf, single codebase.

2. **React Native (Expo Bare workflow) + RevenueCat + Firebase**, if your
   eventual mobile hire is a strong React/TS dev. The TS↔OpenAPI pipeline is
   the strongest reason to flip the default.

3. **Don't go native iOS + native Android.** Two codebases, two teams, doubled
   App Store / Play Console maintenance, no meaningful product upside for this
   category of app. Reserve for category-defining apps that push platform
   limits (high-end games, AR, complex camera apps).

---

## One non-obvious tip

**Use RevenueCat regardless of framework choice.** Apple's StoreKit 2 +
Google's Play Billing v6/v7 are full of quiet edge cases (introductory pricing,
grace periods, billing retry, family sharing, subscription pauses). RevenueCat
handles all of it, gives you a single webhook into Laravel instead of two
platform integrations, and the free tier covers you to ~$10k MRR. The verifier
skeletons we wrote (`AppleReceiptVerifier`, `GoogleReceiptVerifier`) are still
useful — they belong on RevenueCat's webhook handler instead of in the verify
endpoint — but the per-platform receipt code goes away.
