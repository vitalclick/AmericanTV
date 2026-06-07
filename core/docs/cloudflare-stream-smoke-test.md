# Cloudflare Stream — end-to-end smoke test

A happy-path checklist to verify the HLS migration works before publishing
the mobile app. Covers: account setup → migrating one video → confirming
the mobile player streams the signed manifest.

## Prerequisites

- A Cloudflare account with **Stream** enabled.
- One existing video in `videos` table with a `VideoFile` row and a
  publicly-reachable source URL (S3/Wasabi/DO Spaces presigned URLs work;
  local storage works only if your Laravel host is reachable from
  Cloudflare's egress IPs).

## 1. Cloudflare side — credentials

1. **Account ID**: dashboard → top-right → "Copy account ID".
2. **API token** (`CLOUDFLARE_STREAM_API_TOKEN`): dashboard → My Profile →
   API Tokens → Create Token → "Cloudflare Stream Edit" template → restrict
   to the account from step 1.
3. **Signing key** (only required for paid/private videos):
   `POST /accounts/{account_id}/stream/keys` returns
   `{ id, pem, jwk }`. Save `id` as `CLOUDFLARE_STREAM_SIGNING_KEY_ID` and
   the PEM as `CLOUDFLARE_STREAM_SIGNING_KEY_PEM`. The PEM is a multi-line
   value — paste into `.env` with surrounding quotes and `\n` line breaks
   preserved.
4. **Webhook secret**: dashboard → Stream → Webhooks → set the destination
   to `https://your-api/api/v1/webhooks/cloudflare-stream` and copy the
   secret into `CLOUDFLARE_STREAM_WEBHOOK_SECRET`.

## 2. Laravel side

```bash
# Verify the env reaches the service.
php artisan tinker --execute='
  app(App\Services\Stream\CloudflareStreamService::class);
  echo config("stream.providers.cloudflare.account_id"), PHP_EOL;
'

# Apply the migrations that add stream_provider_id, hls_status, duration_seconds.
php artisan migrate

# Optional: confirm the webhook controller will accept a signed payload.
# This should print "Invalid signature" (we sent no Webhook-Signature header).
curl -sS -X POST http://localhost:8000/api/v1/webhooks/cloudflare-stream \
  -H 'Content-Type: application/json' \
  -d '{"uid":"test","status":{"state":"ready"}}'
```

## 3. Migrate one video

```bash
# Dry-run first so you see which video will be uploaded.
php artisan stream:backfill --limit=1 --dry-run

# For real. Note the Stream UID it prints.
php artisan stream:backfill --limit=1
```

Verify in the database:

```sql
SELECT id, title, stream_provider, stream_provider_id, hls_status
FROM videos
WHERE stream_provider = 'cloudflare'
ORDER BY id DESC LIMIT 1;
```

`hls_status = 2` means Cloudflare is transcoding. Wait for the webhook to
flip it to `3`. Typical times:

| Source duration | Transcode wall time |
|---|---|
| < 5 min | ~30 sec |
| 5-30 min | 1-3 min |
| 30+ min | 5-10 min |

While waiting, watch the Stream dashboard for the video status.

## 4. Verify the signed source endpoint

```bash
# Get a Sanctum token via /auth/login or copy one from
# `select token from personal_access_tokens limit 1`.
TOKEN=...
VIDEO_ID=...   # the id you migrated

curl -sS http://localhost:8000/api/v1/videos/$VIDEO_ID/source \
  -H "Authorization: Bearer $TOKEN" | jq
```

Expected response after the webhook lands:

```json
{
  "hls_url": "https://customer-XXXX.cloudflarestream.com/eyJhbGciOi.../manifest/video.m3u8",
  "mp4_url": null,
  "mp4_sources": [],
  "poster": "https://your-cdn/.../thumb.jpg",
  "expires_at": "2026-06-07T16:00:00+00:00",
  "duration_seconds": 187
}
```

If `hls_url` is `null`, the webhook hasn't fired yet — check that:
1. The webhook destination URL is publicly reachable (use ngrok in dev).
2. The HMAC verification passes (check `laravel.log` for "signature mismatch").
3. The `stream_provider_id` matches what's in `videos`.

## 5. Smoke-test the manifest in a player

```bash
# Plain ffprobe to confirm the manifest is valid.
ffprobe "$(curl -sS http://localhost:8000/api/v1/videos/$VIDEO_ID/source \
  -H "Authorization: Bearer $TOKEN" | jq -r .hls_url)"

# Or open the URL in VLC: File -> Open Network -> paste the hls_url.
```

You should see multiple variant streams (240p / 360p / 480p / 720p / 1080p
depending on source resolution).

## 6. Mobile happy path

```bash
cd mobile
flutter run
```

In the app:

1. Sign in.
2. Open the video you migrated.
3. Tap play. Network tab of the device's debugger should show a single
   `.m3u8` GET followed by several `.ts` segment GETs against the
   `customer-XXXX.cloudflarestream.com` host.
4. The `expires_at` returned by `/source` is 4 hours by default; you can
   keep watching without the URL expiring.

## 7. Failure modes worth probing

- **Token expiry**: change `STREAM_SIGNED_URL_TTL=60` in `.env`, restart,
  fetch a fresh source URL, wait 70 seconds, and try to play. The
  player should fail; fetching `/source` again should succeed.
- **Webhook retry**: in the Cloudflare dashboard, manually re-fire the
  webhook for a known UID. The handler is idempotent — `hls_status`
  should stay at `3` and `hls_manifest_url` shouldn't change.
- **Backfill resume**: stop `stream:backfill` mid-run with Ctrl-C; rerun.
  It picks up where it left off (the `WHERE stream_provider_id IS NULL`
  filter excludes already-pushed videos).

## 8. Production checklist before flipping the flag

- [ ] `STREAM_SIGNED_URL_TTL` set to your desired session length (default 4h).
- [ ] Webhook endpoint TLS cert valid and reachable from Cloudflare.
- [ ] `CLOUDFLARE_STREAM_SIGNING_KEY_PEM` and `_WEBHOOK_SECRET` rotated
      from any dev values you may have used.
- [ ] `stream:backfill` has been run to completion in batches across all
      eligible videos (or you've decided to only migrate new uploads).
- [ ] `VideoController@mergeChunks` modified to hand off to Stream for
      new uploads (not yet done — Phase 2 follow-up).
- [ ] DRM decision made — FairPlay/Widevine if rights holders require it.
