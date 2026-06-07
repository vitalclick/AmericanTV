<?php

namespace Tests\Feature\Database;

use App\Constants\Status;
use App\Models\Plan;
use App\Models\PurchasedPlan;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Video;
use App\Models\WatchLater;
use Database\Seeders\AppReviewDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Locks in the contract every release relies on: running the seeder gives
 * App Store Review a working account in the shape they expect.
 *
 * Apple bins the build with "we could not access the content" if any of
 * these checks regresses, so the test exists to catch it before
 * submission rather than 48h later.
 */
class AppReviewDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! \Schema::hasTable('users') ||
            ! \Schema::hasTable('videos') ||
            ! \Schema::hasTable('plans') ||
            ! \Schema::hasTable('user_logins')) {
            $this->markTestSkipped('Schema not in migrations.');
        }

        // Pin the demo password so the test doesn't rely on the random
        // first-run path which writes to stderr.
        putenv('APP_REVIEW_DEMO_PASSWORD=test-password-12chars');
    }

    public function test_creates_a_verified_user_with_a_complimentary_subscription(): void
    {
        $this->seedFixtures();
        Artisan::call('db:seed', ['--class' => AppReviewDemoSeeder::class]);

        $user = User::where('email', 'appreview@americantv.vip')->first();
        $this->assertNotNull($user, 'demo user not created');

        $this->assertSame(Status::USER_ACTIVE, (int) $user->status);
        $this->assertSame(Status::VERIFIED, (int) $user->ev);
        $this->assertSame(Status::VERIFIED, (int) $user->sv);
        $this->assertSame(Status::KYC_VERIFIED, (int) $user->kv);
        $this->assertSame(Status::DISABLE, (int) $user->ts, '2FA must stay off for reviewers');

        // Watch-later populated.
        $this->assertGreaterThan(
            0,
            WatchLater::where('user_id', $user->id)->count(),
            'reviewer would see an empty Library tab',
        );

        // Comp subscription.
        $plan = PurchasedPlan::where('user_id', $user->id)
            ->where('trx', 'APP_REVIEW_DEMO')
            ->first();
        $this->assertNotNull($plan, 'reviewer would hit the paywall instead of subscribed content');
        $this->assertTrue(
            $plan->expired_date->isFuture(),
            'comp subscription must be active, not stale',
        );

        // Recent UserLogin so the admin / activity surfaces don't show
        // "last seen: never".
        $login = UserLogin::where('user_id', $user->id)->latest('id')->first();
        $this->assertNotNull($login);
        $this->assertTrue($login->created_at->isToday());
    }

    public function test_running_twice_doesnt_duplicate_rows(): void
    {
        $this->seedFixtures();

        Artisan::call('db:seed', ['--class' => AppReviewDemoSeeder::class]);
        Artisan::call('db:seed', ['--class' => AppReviewDemoSeeder::class]);

        $this->assertSame(
            1,
            User::where('email', 'appreview@americantv.vip')->count(),
        );

        // Same UserLogin row gets its timestamp refreshed rather than a
        // second row being added; check both possibilities are bounded.
        $loginCount = UserLogin::where(
            'user_id',
            User::where('email', 'appreview@americantv.vip')->value('id'),
        )->count();
        $this->assertLessThanOrEqual(
            1,
            $loginCount,
            'second seeder run should refresh the existing UserLogin, not append',
        );
    }

    public function test_warns_but_does_not_crash_when_no_plans_exist(): void
    {
        // Don't seed any Plan rows — we still want a usable demo user
        // for review of the free-content surface.
        $this->seedFixtures(includePlan: false);

        Artisan::call('db:seed', ['--class' => AppReviewDemoSeeder::class]);

        $user = User::where('email', 'appreview@americantv.vip')->first();
        $this->assertNotNull($user);
        $this->assertSame(
            0,
            PurchasedPlan::where('user_id', $user->id)->count(),
        );
    }

    private function seedFixtures(bool $includePlan = true): void
    {
        $creator = User::forceCreate([
            'firstname' => 'Creator',
            'lastname'  => 'Test',
            'email'     => 'creator@example.com',
            'password'  => bcrypt('correct-horse'),
            'status'    => Status::USER_ACTIVE,
            'ev'        => Status::VERIFIED,
            'sv'        => Status::VERIFIED,
            'kv'        => Status::KYC_VERIFIED,
            'ts'        => Status::DISABLE,
            'tv'        => Status::ENABLE,
            'balance'   => 0,
        ]);

        // Three videos so primeWatchLater has something to pick.
        foreach (range(1, 3) as $i) {
            Video::forceCreate([
                'user_id'         => $creator->id,
                'title'           => "Demo {$i}",
                'slug'            => "demo-{$i}",
                'step'            => Status::FOURTH_STEP,
                'status'          => Status::PUBLISHED,
                'visibility'      => 0,
                'is_shorts_video' => Status::NO,
                'is_only_playlist' => Status::NO,
                'stock_video'     => Status::NO,
                'views'           => 0,
            ]);
        }

        if ($includePlan) {
            Plan::forceCreate([
                'user_id' => $creator->id,
                'name'    => 'Demo Plan',
                'slug'    => 'demo-plan',
                'price'   => 4.99,
                'status'  => Status::ENABLE,
            ]);
        }
    }
}
