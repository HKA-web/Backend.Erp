<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

/** @var \Tests\TestCase $this */
it('can verify email', function () {
    $user = User::factory()->unverified()->create();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        name: 'verification.verify',
        expiration: now()->addMinutes(value: 60),
        parameters: [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class);

    /** @var User $user */
    $user = $user->fresh();

    expect($user->hasVerifiedEmail())->toBeTrue();

    $response->assertOk();
});

it('can not verify email if the link has expired', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        name: 'verification.verify',
        expiration: now()->addMinutes(value: -60),
        parameters: [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    /** @var User $user */
    $user = $user->fresh();

    expect($user->hasVerifiedEmail())->toBeFalse();

    $response->assertInternalServerError();
});

it('can not verify email if the hash is invalid', function () {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        name: 'verification.verify',
        expiration: now()->addMinutes(value: 60),
        parameters: [
            'id' => $user->id,
            'hash' => sha1('wrong-hash'),
        ]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    /** @var User $user */
    $user = $user->fresh();

    expect($user->hasVerifiedEmail())->toBeFalse();

    $response->assertInternalServerError();
});
