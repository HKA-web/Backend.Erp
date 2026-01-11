<?php

/** @var \Tests\TestCase $this */

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('can logout', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);
    $response = $this->postJson(route('logout'));

    $response->assertOk();
});
