<?php

use App\Models\User;

/** @var \Tests\TestCase $this */
it('can send verification link', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->post(route('verification.send'));

    $response->assertOk();
});

it('can not send verification link if user not authenticated', function () {
    $response = $this->post(route('verification.send'));

    $response->assertUnauthorized();
});

it('can not send verification link if user has already verified', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('verification.send'));

    $response->assertUnprocessable();
});
