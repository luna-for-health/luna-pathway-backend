<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class OAuthSignupTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSocialiteUser(string $email, ?string $name = 'Dr Jane Doe', string $id = 'social-123'): SocialiteUser
    {
        $socialUser = new SocialiteUser();
        $socialUser->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
        ]);

        return $socialUser;
    }

    private function fakeDriver(SocialiteUser $socialUser): void
    {
        $driver = \Mockery::mock(SocialiteProvider::class);
        $driver->shouldReceive('userFromToken')->andReturn($socialUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);
    }

    public function test_it_creates_a_new_account_when_the_email_is_unknown(): void
    {
        $this->fakeDriver($this->fakeSocialiteUser('new.doctor@example.com', 'Jane Doe'));

        $response = $this->postJson(route('oauth.signup', 'google'), [
            'access_token' => 'valid-token',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.user.email', 'new.doctor@example.com');
        $response->assertJsonPath('data.user.first_name', 'Jane');
        $response->assertJsonPath('data.user.last_name', 'Doe');
        $response->assertJsonPath('data.user.avatar', 1);
        $this->assertNotEmpty($response->json('data.access_token'));
        $this->assertDatabaseHas('users', [
            'email' => 'new.doctor@example.com',
            'provider' => 'google',
        ]);
    }

    public function test_request_first_and_last_name_override_the_providers_name(): void
    {
        $this->fakeDriver($this->fakeSocialiteUser('new.doctor@example.com', 'Jane Doe'));

        $response = $this->postJson(route('oauth.signup', 'google'), [
            'access_token' => 'valid-token',
            'first_name' => 'Janet',
            'last_name' => 'Smith',
            'avatar' => 5,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.user.first_name', 'Janet');
        $response->assertJsonPath('data.user.last_name', 'Smith');
        $response->assertJsonPath('data.user.avatar', 5);
    }

    public function test_it_logs_in_instead_of_duplicating_an_existing_account(): void
    {
        $user = User::factory()->create(['email' => 'existing.doctor@example.com']);
        $this->fakeDriver($this->fakeSocialiteUser('existing.doctor@example.com'));

        $response = $this->postJson(route('oauth.signup', 'google'), [
            'access_token' => 'valid-token',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.id', $user->id);
        $this->assertSame(1, User::where('email', 'existing.doctor@example.com')->count());
    }

    public function test_it_rejects_an_unsupported_provider(): void
    {
        $response = $this->postJson(route('oauth.signup', 'facebook'), [
            'access_token' => 'valid-token',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_rejects_a_bad_access_token(): void
    {
        $driver = \Mockery::mock(SocialiteProvider::class);
        $driver->shouldReceive('userFromToken')->andThrow(new \Exception('invalid token'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

        $response = $this->postJson(route('oauth.signup', 'google'), [
            'access_token' => 'bad-token',
        ]);

        $response->assertStatus(400);
    }

    public function test_it_requires_an_access_token(): void
    {
        $response = $this->postJson(route('oauth.signup', 'google'), []);

        $response->assertStatus(422);
    }
}
