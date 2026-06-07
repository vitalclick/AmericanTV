<?php

namespace Tests\Feature\Api;

use App\Constants\Status;
use App\Models\Subscriber;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Coverage for VideoDetailResource.is_subscribed against the three real-
 * world cases:
 *
 *  - anonymous caller: false (no notion of subscription).
 *  - signed-in non-subscriber: false (default).
 *  - signed-in subscriber: true.
 *  - signed-in video owner: false (owner doesn't subscribe to themselves).
 *
 * Eager-load efficiency: DiscoveryController.showVideo loads
 * user.subscribers filtered to (following_id = caller). The Resource reads
 * the loaded relation. Asserting via assertJsonPath keeps these tests
 * decoupled from the rest of the resource shape.
 */
class VideoDetailSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        if (! \Schema::hasTable('users') ||
            ! \Schema::hasTable('videos') ||
            ! \Schema::hasTable('subscribers')) {
            $this->markTestSkipped('Schema not in migrations.');
        }
    }

    public function test_anonymous_caller_sees_false(): void
    {
        $video = $this->makePublishedVideo();
        $this->getJson("/api/v1/videos/{$video->slug}")
            ->assertOk()
            ->assertJsonPath('data.is_subscribed', false);
    }

    public function test_signed_in_non_subscriber_sees_false(): void
    {
        $video = $this->makePublishedVideo();
        $viewer = $this->makeUser(['email' => 'viewer@example.com']);

        $this->withToken($viewer->createToken('iOS')->plainTextToken)
            ->getJson("/api/v1/videos/{$video->slug}")
            ->assertOk()
            ->assertJsonPath('data.is_subscribed', false);
    }

    public function test_signed_in_subscriber_sees_true(): void
    {
        $video = $this->makePublishedVideo();
        $viewer = $this->makeUser(['email' => 'viewer@example.com']);

        Subscriber::forceCreate([
            'user_id'      => $video->user_id,  // the channel.
            'following_id' => $viewer->id,      // the viewer.
        ]);

        $this->withToken($viewer->createToken('iOS')->plainTextToken)
            ->getJson("/api/v1/videos/{$video->slug}")
            ->assertOk()
            ->assertJsonPath('data.is_subscribed', true);
    }

    public function test_owner_of_the_video_sees_false(): void
    {
        $video = $this->makePublishedVideo();
        $owner = User::find($video->user_id);

        $this->withToken($owner->createToken('iOS')->plainTextToken)
            ->getJson("/api/v1/videos/{$video->slug}")
            ->assertOk()
            ->assertJsonPath('data.is_subscribed', false);
    }

    /**
     * Defensive: exercise the fallback branch where VideoDetailResource is
     * given a Video whose user.subscribers relation isn't eager-loaded.
     *
     * The Controller always eager-loads today (per the test above), but
     * the Resource's else-branch falls through to a direct query so other
     * future callers (admin views, jobs, custom controllers) get the right
     * answer too. Without this test, an optimization that drops the
     * relation could silently break those paths.
     */
    public function test_resource_falls_back_to_a_direct_query_when_subscribers_arent_eager_loaded(): void
    {
        $video  = $this->makePublishedVideo();
        $viewer = $this->makeUser(['email' => 'fallback-' . uniqid() . '@example.com']);

        Subscriber::forceCreate([
            'user_id'      => $video->user_id,
            'following_id' => $viewer->id,
        ]);

        // Re-fetch with NO `with('user.subscribers')` clause — the user
        // relation is loaded (Resource needs it for channel info) but
        // user.subscribers is intentionally absent.
        $bareVideo = \App\Models\Video::with('user', 'category', 'tags', 'subtitles', 'userReactions')
            ->where('id', $video->id)
            ->first();

        $this->assertFalse(
            $bareVideo->user->relationLoaded('subscribers'),
            'Precondition: user.subscribers should NOT be eager-loaded.',
        );

        $request = \Illuminate\Http\Request::create('/test');
        $request->setUserResolver(fn () => $viewer);

        $payload = (new \App\Http\Resources\VideoDetailResource($bareVideo))->toArray($request);

        $this->assertTrue($payload['is_subscribed']);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::forceCreate(array_merge([
            'firstname' => 'Test',
            'lastname'  => 'User',
            'email'     => 'user-' . uniqid() . '@example.com',
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

    private function makePublishedVideo(): Video
    {
        $creator = $this->makeUser(['email' => 'creator-' . uniqid() . '@example.com']);
        return Video::forceCreate([
            'user_id'         => $creator->id,
            'title'           => 'Test video',
            'slug'            => 'test-video-' . uniqid(),
            'step'            => Status::FOURTH_STEP,
            'status'          => Status::PUBLISHED,
            'visibility'      => 0, // public
            'is_shorts_video' => Status::NO,
            'is_only_playlist' => Status::NO,
            'stock_video'     => Status::NO,
            'views'           => 0,
        ]);
    }
}
