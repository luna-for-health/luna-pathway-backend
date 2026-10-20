<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_user_with_a_birthdate(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'birthdate' => '1990-05-20',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.birthdate', '1990-05-20');
        $this->assertDatabaseHas('users', ['email' => 'jane.doe@example.com']);
    }

    public function test_it_creates_a_user_without_a_birthdate(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.birthdate', null);
    }

    public function test_it_defaults_the_avatar_to_1(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.avatar', 1);
    }

    public function test_it_creates_a_user_with_a_chosen_avatar(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'avatar' => 4,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.avatar', 4);
        $this->assertSame(4, User::where('email', 'jane.doe@example.com')->first()->avatar_id);
    }

    public function test_it_updates_the_avatar_when_user_id_is_passed(): void
    {
        $user = User::factory()->create(['avatar_id' => 1]);

        $response = $this->postJson(route('auth.register'), [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'avatar' => 7,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.avatar', 7);
        $this->assertSame(7, $user->fresh()->avatar_id);
    }

    public function test_it_keeps_the_existing_avatar_when_not_passed_on_update(): void
    {
        $user = User::factory()->create(['avatar_id' => 5]);

        $response = $this->postJson(route('auth.register'), [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.avatar', 5);
    }

    public function test_it_updates_the_birthdate_when_user_id_is_passed(): void
    {
        $user = User::factory()->create(['birthdate' => '1985-01-01']);

        $response = $this->postJson(route('auth.register'), [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'birthdate' => '1992-11-03',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.birthdate', '1992-11-03');
        $this->assertSame('1992-11-03', $user->fresh()->birthdate->toDateString());
    }

    public function test_it_keeps_the_existing_birthdate_when_not_passed_on_update(): void
    {
        $user = User::factory()->create(['birthdate' => '1985-01-01']);

        $response = $this->postJson(route('auth.register'), [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.birthdate', '1985-01-01');
    }

    public function test_it_rejects_a_future_birthdate(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'birthdate' => now()->addDay()->toDateString(),
        ]);

        $response->assertStatus(422);
    }

    public function test_it_rejects_an_invalid_birthdate(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'birthdate' => 'not-a-date',
        ]);

        $response->assertStatus(422);
    }

    public function test_it_creates_a_user_with_a_location(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
            'location' => 'Paris, France',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.location', 'Paris, France');
        $this->assertSame('Paris, France', User::where('email', 'jane.doe@example.com')->first()->location);
    }

    public function test_it_creates_a_user_without_a_location(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.com',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.location', null);
    }

    public function test_it_updates_the_location_when_user_id_is_passed(): void
    {
        $user = User::factory()->create(['location' => 'Lyon, France']);

        $response = $this->postJson(route('auth.register'), [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'location' => 'Marseille, France',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.location', 'Marseille, France');
        $this->assertSame('Marseille, France', $user->fresh()->location);
    }

    public function test_it_keeps_the_existing_location_when_not_passed_on_update(): void
    {
        $user = User::factory()->create(['location' => 'Lyon, France']);

        $response = $this->postJson(route('auth.register'), [
            'user_id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.user.location', 'Lyon, France');
    }

    public function test_it_requires_first_and_last_name(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'email' => 'jane.doe@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name', 'last_name']);
    }
}
