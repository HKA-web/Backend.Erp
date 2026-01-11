<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

/** @var \Tests\TestCase $this */
it('can send reset password email', function () {
    $user = User::factory()->create();

    Notification::fake();

    $response = $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    Notification::assertSentTo(notifiable: $user, notification: ResetPassword::class);

    $response->assertOk();
});

it('can reset password with valid token', function () {
    $user = User::factory()->create();

    Notification::fake();

    $response = $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    Notification::assertSentTo(
        notifiable: $user,
        notification: ResetPassword::class,
        callback: function (ResetPassword $notification) use ($user) {
            $response = $this->post(route('password.reset'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            return $response->assertOk();
        },
    );

    $response->assertOk();
});

it('can not send reset password email if email not found', function () {
    $response = $this->post(route('password.email'), [
        'email' => 'wrong@mail.com',
    ]);

    $response->assertUnprocessable();
});

it('can not reset password with invalid token', function () {
    $user = User::factory()->create();

    $response = $this->post(route('password.reset'), [
        'token' => 'invalid_token',
        'email' => $user->email,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable();
});
