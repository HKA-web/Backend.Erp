<?php

use App\Models\User;

/**
 * @var \Tests\TestCase $this
 */
it('can register', function () {
    $response = $this->post(route(name: 'register'), [
        'name' => 'John Doe',
        'email' => 'john@doe.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated();

    expect($response->content())->toContain(needles: 'John Doe');
});

it('can not register if email has already exists', function () {
    $user = User::factory()->create();

    $response = $this->post(route(name: 'register'), [
        'name' => 'John Doe',
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable();

    expect(value: $response->content())->toContain(needles: 'The email has already been taken.');
});

it('can not register if request is empty', function () {
    $response = $this->post(route(name: 'register'));

    $response->assertUnprocessable();
    expect(value: $response->content())->toContain(needles: 'The name field is required. (and 2 more errors)');
});
