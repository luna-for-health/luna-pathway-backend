<?php

namespace Tests\Feature\Luna;

use App\Models\MagicCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SyncLunaUserTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://luna.test/api/internal/doctors/users/resolve';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.luna.base_url' => 'https://luna.test',
            'services.luna.token' => 'doctors-test-token',
        ]);
        Http::preventStrayRequests();
    }

    private function fakeLunaResponse(int $lunaUserId = 4242): void
    {
        Http::fake([self::ENDPOINT => Http::response(['status' => true, 'data' => ['id' => $lunaUserId, 'created' => true]], 201)]);
    }

    public function test_register_links_the_new_user_to_luna(): void
    {
        $this->fakeLunaResponse();

        $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
        ])->assertOk();

        $this->assertSame(4242, User::where('email', 'jane.doe@example.com')->value('luna_user_id'));
        Http::assertSent(fn (Request $request) => $request->url() === self::ENDPOINT
            && $request->hasHeader('X-Internal-Token', 'doctors-test-token')
            && $request['email'] === 'jane.doe@example.com'
            && $request['first_name'] === 'Jane');
    }

    public function test_register_does_not_call_luna_when_updating_an_existing_user(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $this->postJson(route('auth.register'), [
            'user_id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $user->email,
        ])->assertOk();

        Http::assertNothingSent();
    }

    public function test_verify_code_links_the_user_it_creates_to_luna(): void
    {
        $this->fakeLunaResponse();
        MagicCode::create(['email' => 'new@example.com', 'code' => '123456', 'expires_at' => now()->addMinutes(10)]);

        $this->postJson(route('auth.verifyCode'), ['email' => 'new@example.com', 'code' => '123456'])->assertOk();

        $this->assertSame(4242, User::where('email', 'new@example.com')->value('luna_user_id'));
    }

    public function test_oauth_signup_links_the_new_user_to_luna(): void
    {
        $this->fakeLunaResponse();
        $socialUser = (new SocialiteUser)->map(['id' => 'social-123', 'name' => 'Jane Doe', 'email' => 'oauth@example.com']);
        $driver = \Mockery::mock(SocialiteProvider::class);
        $driver->shouldReceive('userFromToken')->andReturn($socialUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $this->postJson(route('oauth.signup', 'google'), ['access_token' => 'valid-token'])->assertCreated();

        $this->assertSame(4242, User::where('email', 'oauth@example.com')->value('luna_user_id'));
    }

    public function test_signup_still_succeeds_when_luna_fails(): void
    {
        Http::fake([self::ENDPOINT => Http::response(['status' => false], 500)]);

        $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
        ])->assertOk();

        $this->assertNull(User::where('email', 'jane.doe@example.com')->value('luna_user_id'));
    }

    public function test_signup_does_not_call_luna_when_it_is_not_configured(): void
    {
        config(['services.luna.base_url' => null]);
        Http::fake();

        $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
        ])->assertOk();

        Http::assertNothingSent();
    }

    public function test_the_command_links_only_users_without_a_luna_id(): void
    {
        $this->fakeLunaResponse(99);
        $unlinkedUser = User::factory()->create();
        $linkedUser = User::factory()->create();
        $linkedUser->forceFill(['luna_user_id' => 7])->save();

        $this->artisan('luna:sync-users')->assertSuccessful();

        $this->assertSame(99, $unlinkedUser->fresh()->luna_user_id);
        $this->assertSame(7, $linkedUser->fresh()->luna_user_id);
        Http::assertSentCount(1);
    }
}
