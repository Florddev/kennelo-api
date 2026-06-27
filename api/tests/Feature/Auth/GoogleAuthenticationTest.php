<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\MediaService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(string $id, string $email, string $given = 'Ada', string $family = 'Lovelace', ?string $avatar = null): void
{
    $socialiteUser = (new SocialiteUser)
        ->setRaw(['given_name' => $given, 'family_name' => $family])
        ->map(['id' => $id, 'name' => trim($given.' '.$family), 'email' => $email, 'avatar' => $avatar]);

    $provider = Mockery::mock(AbstractProvider::class);
    $provider->shouldReceive('stateless')->andReturn($provider);
    $provider->shouldReceive('userFromToken')->andReturn($socialiteUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

test('new users can register with google', function () {
    fakeGoogleUser('google-123', 'ada@example.com');

    $response = $this->post('/api/login/google', ['token' => 'google-access-token']);

    $response->assertOk()
        ->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
            'user' => ['id', 'first_name', 'last_name', 'email'],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'ada@example.com',
        'google_id' => 'google-123',
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    expect(User::where('email', 'ada@example.com')->first()->hasRole('user'))->toBeTrue();
});

test('google login merges with an existing email account', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'google_id' => null]);

    fakeGoogleUser('google-123', 'ada@example.com');

    $response = $this->post('/api/login/google', ['token' => 'google-access-token']);

    $response->assertOk();

    expect(User::where('email', 'ada@example.com')->count())->toBe(1);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'google_id' => 'google-123',
    ]);
});

test('returning google users can authenticate', function () {
    $user = User::factory()->create(['email' => 'ada@example.com', 'google_id' => 'google-123']);

    fakeGoogleUser('google-123', 'ada@example.com');

    $response = $this->post('/api/login/google', ['token' => 'google-access-token']);

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id);

    expect(User::where('google_id', 'google-123')->count())->toBe(1);
});

test('google login fails without a token', function () {
    $response = $this->post('/api/login/google', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['token']);
});

test('google login imports the google profile picture into media', function () {
    Storage::fake('public');
    Queue::fake();
    Http::fake(['*' => Http::response('fake-image-bytes', 200)]);

    fakeGoogleUser('google-123', 'ada@example.com', avatar: 'https://lh3.googleusercontent.com/a/fake-picture');

    $response = $this->post('/api/login/google', ['token' => 'google-access-token']);

    $response->assertOk();

    $user = User::where('email', 'ada@example.com')->first();

    expect($user->getFirstMedia(MediaService::COLLECTION_AVATAR))->not->toBeNull();
});
