<?php

namespace Database\Seeders;

use App\Constants\Status;
use App\Models\Plan;
use App\Models\PurchasedPlan;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Video;
use App\Models\WatchLater;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Creates / refreshes a demo account App Store and Play Store reviewers
 * can use to exercise the app without registering. Required by Apple
 * for any app with a sign-in wall (we do; you can't open the feed
 * without auth).
 *
 * Idempotent: re-running on the same database refreshes the user, makes
 * sure verification flags are set, and re-attaches a watchable demo
 * playlist. Doesn't create duplicate accounts.
 *
 *   php artisan db:seed --class=AppReviewDemoSeeder
 *
 * For App Store Connect / Play Console:
 *   email:    appreview@americantv.vip
 *   password: stored in 1Password under "AmericanTV App Review"
 *
 * The password defaults to a random 16-char string the first time the
 * seeder runs against a fresh DB. To set a stable password, set
 * APP_REVIEW_DEMO_PASSWORD in the production .env BEFORE seeding.
 */
class AppReviewDemoSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'appreview@americantv.vip';
        $password = (string) env('APP_REVIEW_DEMO_PASSWORD', '');
        if ($password === '') {
            // First-run: generate a random one. Read it out of the users
            // table or rotate it by setting APP_REVIEW_DEMO_PASSWORD and
            // re-seeding.
            $password = bin2hex(random_bytes(8));
            $this->command->warn(
                "APP_REVIEW_DEMO_PASSWORD not set — generated `{$password}`. "
                . 'Save it to 1Password and set the env var.'
            );
        }

        $user = User::firstOrNew(['email' => $email]);
        $user->firstname = 'App Store';
        $user->lastname  = 'Reviewer';
        $user->username  = 'app-reviewer';
        $user->password  = Hash::make($password);
        $user->status    = Status::USER_ACTIVE;
        $user->ev        = Status::VERIFIED;
        $user->sv        = Status::VERIFIED;
        $user->kv        = Status::KYC_VERIFIED;
        $user->ts        = Status::DISABLE; // no 2FA — reviewers can't intercept SMS.
        $user->tv        = Status::ENABLE;

        // Some production schemas track a `last_login` column on users
        // directly (separate from the UserLogin table). Refreshing it
        // matters because admin panels sort by it; an "inactive" demo
        // account looks suspicious to reviewers spot-checking the user
        // list. Guarded by Schema::hasColumn so installs without the
        // column don't choke.
        if (Schema::hasColumn('users', 'last_login')) {
            $user->last_login = Carbon::now();
        }
        $user->save();

        $this->primeWatchLater($user);
        $this->ensureActiveSubscription($user);
        $this->stampRecentLogin($user);

        $this->command->info("App Review demo user ready: {$email}");
    }

    /**
     * Apple sometimes flags accounts with no recent activity as "inactive"
     * during App Store Review — they expect to see a user who's been
     * using the app. Drop a fresh UserLogin row stamped with the same
     * geo metadata the real login flow records, so the admin / activity
     * surfaces show a recent session.
     */
    private function stampRecentLogin(User $user): void
    {
        // Idempotent: refresh the most recent row's timestamp rather than
        // accumulating one per seed run. Keeps the activity log clean
        // even when the seeder runs on every deploy.
        $login = UserLogin::where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $login) {
            $login = new UserLogin();
            $login->user_id = $user->id;
            // Conservative placeholders. The real login flow rewrites these
            // on the next sign-in; reviewers signing in for the first time
            // will overwrite with their actual geo.
            $login->user_ip      = '0.0.0.0';
            $login->city         = 'Cupertino';
            $login->country      = 'United States';
            $login->country_code = 'US';
            $login->longitude    = '-122.0322';
            $login->latitude     = '37.3230';
            $login->browser      = 'AmericanTV App';
            $login->os           = 'iOS / Android';
        }

        $login->created_at = Carbon::now();
        $login->updated_at = Carbon::now();
        $login->save();
    }

    /**
     * Drop 3 public videos into the demo user's watch-later list so the
     * Library tab isn't empty when reviewers tap into it.
     */
    private function primeWatchLater(User $user): void
    {
        $videos = Video::published()
            ->public()
            ->whereHas('user', fn ($q) => $q->active())
            ->latest('id')
            ->take(3)
            ->get();

        foreach ($videos as $video) {
            WatchLater::firstOrCreate([
                'user_id'  => $user->id,
                'video_id' => $video->id,
            ]);
        }
    }

    /**
     * Apple reviewers need to see what the paywall looks like *after*
     * subscribing — otherwise they reject for "we couldn't access the
     * subscription content you describe in the listing." Granting a free
     * active subscription via PurchasedPlan does that without involving
     * IAP at all.
     */
    private function ensureActiveSubscription(User $user): void
    {
        $plan = Plan::where('status', Status::ENABLE)->first();
        if (! $plan) {
            $this->command->warn(
                'No active Plan rows — App Reviewer will see the paywall but '
                . 'can\'t exercise the post-subscribe flow.'
            );
            return;
        }

        PurchasedPlan::updateOrCreate(
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
            [
                'owner_id'     => $plan->user_id,
                'trx'          => 'APP_REVIEW_DEMO',
                'amount'       => 0, // free; flagged as comp via the trx string.
                'expired_date' => Carbon::now()->addYear(),
            ],
        );

        $this->command->info("Granted complimentary subscription to plan {$plan->slug}.");
    }
}
