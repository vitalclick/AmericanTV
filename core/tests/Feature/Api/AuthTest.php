<?php

namespace Tests\Feature\Api;

use App\Constants\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Smoke tests for the /api/v1/auth surface. These rely on the SQLite
 * :memory: database — Sanctum's auto-loaded migration runs but the project's
 * own migrations include columns that exist only in the production schema
 * (which lives in the SQL dump, not in migrations). Tests are gated by
 * users-table existence so they no-op when the schema can't materialize.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (!\Schema::hasTable('users')) {
            $this->markTestSkipped('users table not in migrations; this project ships its schema via a SQL dump.');
        }
    }

    public function test_login_returns_a_sanctum_token_for_valid_credentials(): void
    {
        $user = $this->makeUser(['password' => Hash::make('correct-horse')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email,
            'password'   => 'correct-horse',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'email']]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_login_rejects_a_wrong_password_with_422(): void
    {
        $this->makeUser(['password' => Hash::make('correct-horse')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'test@example.com',
            'password'   => 'nope',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['identifier']);
    }

    public function test_login_blocks_banned_users_with_403(): void
    {
        $user = $this->makeUser([
            'password' => Hash::make('correct-horse'),
            'status'   => Status::USER_BAN,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email,
            'password'   => 'correct-horse',
        ]);

        $response->assertStatus(403);
    }

    public function test_logout_revokes_only_the_calling_token(): void
    {
        $user = $this->makeUser(['password' => Hash::make('correct-horse')]);

        // Pre-existing token from another device.
        $other = $user->createToken('other');

        $login = $this->postJson('/api/v1/auth/login', [
            'identifier' => $user->email,
            'password'   => 'correct-horse',
        ])->json('token');

        $this->withToken($login)->postJson('/api/v1/auth/logout')->assertNoContent();

        // The other-device token is still alive.
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $other->accessToken->id]);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::forceCreate(array_merge([
            'firstname' => 'Test',
            'lastname'  => 'User',
            'email'     => 'test@example.com',
            'password'  => Hash::make('correct-horse'),
            'status'    => Status::USER_ACTIVE,
            'ev'        => Status::VERIFIED,
            'sv'        => Status::VERIFIED,
            'kv'        => Status::KYC_VERIFIED,
            'ts'        => Status::DISABLE,
            'tv'        => Status::ENABLE,
            'balance'   => 0,
        ], $overrides));
    }
}
