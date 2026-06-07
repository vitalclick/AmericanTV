<?php

namespace App\Services\Auth;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Verifies an ID token issued by Apple or Google to the mobile client and
 * returns a normalized identity payload. Used by AuthController::socialLogin
 * to find / create the matching User row and issue a Sanctum token.
 *
 * Web's SocialLogin uses Laravel Socialite's redirect flow, which doesn't
 * survive on mobile — the platform-native SDKs (Sign in with Apple, Google
 * Sign-In) return an ID token directly and we verify it server-side here.
 */
class SocialIdentityVerifier
{
    public function verifyApple(string $idToken, ?string $nonce = null): SocialIdentity
    {
        $keys = $this->fetchAppleKeys();
        $decoded = JWT::decode($idToken, JWK::parseKeySet($keys));

        if (($decoded->iss ?? null) !== 'https://appleid.apple.com') {
            throw new RuntimeException('Apple ID token issuer mismatch.');
        }

        $expectedBundle = (string) config('iap.apple.bundle_id');
        if ($expectedBundle && ! in_array($decoded->aud ?? null, [$expectedBundle], true)) {
            throw new RuntimeException('Apple ID token audience mismatch.');
        }

        if ($nonce && ($decoded->nonce ?? null) !== $nonce) {
            throw new RuntimeException('Apple nonce mismatch.');
        }

        return new SocialIdentity(
            provider:  'apple',
            providerId: (string) $decoded->sub,
            email:     $decoded->email ?? null,
            firstName: null,
            lastName:  null,
            emailVerified: ($decoded->email_verified ?? 'true') === 'true' || ($decoded->email_verified ?? null) === true,
        );
    }

    public function verifyGoogle(string $idToken): SocialIdentity
    {
        $client   = new GoogleClient();
        $audience = $this->googleAudiences();
        $client->setAudience($audience);

        $payload = $client->verifyIdToken($idToken);
        if (!$payload) {
            throw new RuntimeException('Google ID token verification failed.');
        }

        // Defense-in-depth — verifyIdToken already checks aud, but be explicit.
        if (!in_array($payload['aud'] ?? null, (array) $audience, true)) {
            throw new RuntimeException('Google ID token audience mismatch.');
        }

        return new SocialIdentity(
            provider:   'google',
            providerId: (string) ($payload['sub'] ?? ''),
            email:      $payload['email'] ?? null,
            firstName:  $payload['given_name'] ?? null,
            lastName:   $payload['family_name'] ?? null,
            emailVerified: (bool) ($payload['email_verified'] ?? false),
        );
    }

    /**
     * Apple rotates its signing keys. Cache the JWK set for an hour so we
     * don't hammer their endpoint on every login.
     */
    private function fetchAppleKeys(): array
    {
        return Cache::remember('apple_signin_keys', now()->addHour(), function () {
            $response = Http::acceptJson()->get('https://appleid.apple.com/auth/keys');
            if ($response->failed()) {
                throw new RuntimeException('Could not fetch Apple signing keys.');
            }
            return $response->json();
        });
    }

    private function googleAudiences(): string|array
    {
        // App Store + Play Store ship distinct OAuth client IDs. Accepting an
        // array lets one Laravel install serve both apps.
        $ios     = config('services.google.client_id_ios');
        $android = config('services.google.client_id_android');
        $web     = config('services.google.client_id'); // fallback for web SPA reuse

        return array_values(array_filter([$ios, $android, $web]));
    }
}

class SocialIdentity
{
    public function __construct(
        public readonly string $provider,
        public readonly string $providerId,
        public readonly ?string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly bool $emailVerified,
    ) {}
}
