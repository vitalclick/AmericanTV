# IAP rollout — App Store + Play Console + RevenueCat

Step-by-step for turning on the in-app purchase path for plans, using the
backend pieces already in this repo:

- `IapPurchaseController::verify` (POST `/api/v1/purchases/iap/verify`)
- `iap_products` table + `IapProduct` model
- `IapProductSeeder` (seeds product ID rows from existing `Plan` rows)
- `AppleReceiptVerifier` / `GoogleReceiptVerifier` skeletons

## 0. Decide what gets sold on mobile

Mobile-IAP-eligible:
- ✅ Monthly subscription **Plans** (auto-renewable)
- ✅ One-time paid **Videos** (non-consumable) — optional in v1
- ✅ One-time paid **Playlists** (non-consumable) — optional in v1

Web-only (Apple/Google won't let you sell these in-app):
- ❌ Wallet top-ups (`Deposit` without a backing item)
- ❌ Advertiser campaigns / monetization application fees (B2B exception
  applies, but defer until later)
- ❌ Crypto / alt-gateway flows (only IAP allowed for digital content)

Recommendation for v1: **plans only**. Adds the most LTV, smallest surface
area, simplest QA. Video / playlist unlocks come later.

## 1. App Store Connect (iOS)

1. App Store Connect → Apps → AmericanTV → **Subscriptions** → Create a
   subscription group (e.g. "Creator Plans").
2. For each `Plan` row in production:
   - Add an Auto-Renewable Subscription.
   - **Product ID**: `com.americantv.plan.<plan_slug>.monthly` —
     this must match the `apple_product_id` that `IapProductSeeder` writes.
   - Duration: 1 month.
   - Pricing tier: pick the closest tier to the web price; the actual
     amount the user pays is what Apple shows, not what `Plan.price` says.
3. Fill out the Localization (display name + description) and Review
   Information for App Review.
4. Set the App-Specific Shared Secret if you'll be talking to the
   verifyReceipt endpoint (we don't — we use App Store Server API).
5. Generate an **In-App Purchase API key** for the App Store Server API:
   Users and Access → Keys → In-App Purchase → +.
   - Save the `.p8` file and the Key ID (`IAP_APPLE_KEY_ID`).
   - The Issuer ID is at the top of the Keys page (`IAP_APPLE_ISSUER_ID`).
   - Set `IAP_APPLE_BUNDLE_ID=com.americantv` and
     `IAP_APPLE_PRIVATE_KEY` to the `.p8` contents.

## 2. Google Play Console (Android)

1. Play Console → AmericanTV → Monetize → Subscriptions → Create.
2. For each `Plan`:
   - **Product ID**: `com.americantv.plan.<plan_slug>.monthly`.
   - Add a base plan: Auto-renewing, monthly.
   - Set the price in each market (USD baseline).
3. Service account for the Play Billing API:
   - Google Cloud Console → IAM → Service Accounts → Create.
   - Grant the role "Service Account User".
   - Generate a JSON key, save it on the server.
   - Set `IAP_GOOGLE_SERVICE_ACCOUNT_JSON` to the file path.
   - Set `IAP_GOOGLE_PACKAGE_NAME=com.americantv`.
4. In Play Console → Setup → API access → grant this service account
   access to financial data + manage orders on the app.

## 3. RevenueCat (recommended wrapper)

RevenueCat smooths over the StoreKit 2 + Play Billing v6/v7 edge cases
and gives you a single webhook into Laravel instead of separate paths
per platform.

1. dashboard.revenuecat.com → New project.
2. Add app: pick **iOS**, App Store Connect Shared Secret optional (we
   use Server API).
3. Add app: pick **Android**, upload the Play service account JSON.
4. Products → **Import from store** for each platform. RevenueCat
   discovers your Auto-Renewable Subscriptions automatically.
5. Entitlements → create one called **`plans`** and attach every plan
   product to it. The mobile client checks `customerInfo.entitlements.active['plans']`
   to know whether the user has an active subscription.
6. Copy the **public SDK key** for each platform into mobile `.env`:
   ```
   REVENUECAT_IOS_KEY=appl_xxxx
   REVENUECAT_ANDROID_KEY=goog_xxxx
   ```
7. Webhooks → add `https://your-api/api/v1/webhooks/revenuecat`
   pointing at a controller you'll add (next milestone).

## 4. Laravel side

```bash
cd core

# Apply IAP migrations (already in the project).
php artisan migrate

# Seed iap_products rows for every active Plan. Re-run any time you add
# a new Plan to production.
php artisan db:seed --class=IapProductSeeder

# Confirm:
php artisan tinker --execute='\App\Models\IapProduct::all(["plan_id","apple_product_id"])->each(fn($r)=>print_r($r->toArray()));'
```

You should see one row per active plan with `apple_product_id` and
`google_product_id` set.

Set the env vars from sections 1 + 2 in `.env` and restart.

## 5. Mobile side

`PlanPaywallScreen` already fetches `/plans/{slug}`, presents a
subscribe button, drives the RevenueCat purchase flow, and POSTs to
`/api/v1/purchases/iap/verify`. Wire it from the video detail screen's
paywall CTA:

```dart
// In features/video/presentation/video_detail_screen.dart's paywall CTA:
onTap: () async {
  final didSubscribe = await Navigator.of(context).push<bool>(
    MaterialPageRoute(
      builder: (_) => PlanPaywallScreen(slug: planSlug),
    ),
  );
  if (didSubscribe == true) {
    ref.invalidate(_videoDetailProvider(detail.summary.slug));
  }
},
```

(Wiring not done yet — Phase 2 follow-up, since deciding *which* plan
to offer on a given paid-video detail screen is its own UX decision.)

## 6. Testing the path

### Sandbox testing on iOS

1. Build a TestFlight or local debug build (not connected to App Store
   Connect production).
2. Sign out of the App Store on the device.
3. Open the app, hit the paywall, tap Subscribe.
4. iOS prompts for a Sandbox Apple ID — sign in with a sandbox tester
   from App Store Connect → Users and Access → Sandbox.
5. Complete the purchase. RevenueCat dashboard → Sandbox shows the
   transaction.
6. Laravel side: `select * from deposits where method_code=5001 order by id desc limit 1;`
   confirms the row, and `select * from purchased_plans where user_id=...;`
   confirms the entitlement.

### Sandbox testing on Android

1. Internal Testing track in Play Console → opt-in your tester account.
2. Install via Play Store internal link.
3. Same flow; Play Billing prompts.
4. `select * from deposits where method_code=5002 order by id desc limit 1;`

### Smoke test the unhappy path

- Cancel the purchase mid-flow → app returns `Purchase cancelled.` with
  no Deposit row created.
- Verify-receipt server failure (drop the IAP keys from `.env`) →
  the purchase completes on-device but Laravel returns 422; the user
  sees an error and the entitlement is **not** unlocked. They can hit
  "Restore Purchases" once the server is fixed to re-verify.

## 7. Pricing — read this carefully

Apple/Google take **15% (after first year) / 30% (first year)** for
subscriptions. Your creators currently keep `Plan.price - gs('plan_sell_charge')`.
On mobile this is reduced by the store cut.

Options:
- **(a) Creators absorb the cut.** Simplest, hardest pitch to creators.
- **(b) Platform absorbs the cut.** Reduces your margin to near zero.
- **(c) Inflate mobile prices.** `IapProduct.price_usd_mobile` is set
  separately from `Plan.price` for exactly this — set mobile prices
  ~43% higher to net the same dollars to the creator after Apple's
  first-year cut.

Recommend (c). Document it in your creator T&Cs and show creators a
"net earnings preview" that splits web and mobile.

## 8. Required webhook handlers (next milestone)

These don't exist yet — add before public launch:

- `POST /api/v1/webhooks/revenuecat` — handle:
  - `RENEWAL` → extend `PurchasedPlan.expired_date`
  - `CANCELLATION` / `EXPIRATION` → leave `expired_date` alone (access
    until period end)
  - `BILLING_ISSUE` → set a `payment_grace_period` flag if you want to
    give the user a window before revoking access
  - `REFUND` → mark `PurchasedPlan` inactive and create a refund
    `Transaction`. Apple penalizes apps that don't comply with refunds.

Without webhook handling, the first subscription works but renewals
silently drop after 30 days.
