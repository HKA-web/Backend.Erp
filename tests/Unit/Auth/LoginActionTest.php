<?php

use App\Actions\Auth\LoginAction;
use App\DataTransferObjects\Auth\LoginData;
use App\Models\User;
use Illuminate\Validation\ValidationException;

it('can resolve login action', function () {
    $user = User::factory()->create();

    $action = LoginAction::resolve()->execute(
        data: LoginData::resolve([
            'email' => $user->email,
            'password' => 'password',
        ]),
    );

    expect(collect($action)->toJson())->toContain($user->email);
});

it('can not resolve login action if credentials are invalid', function () {
    LoginAction::resolve()->execute(
        data: LoginData::resolve([
            'email' => 'invalid@mail.com',
            'password' => 'password',
        ]),
    );
})->throws(exception: ValidationException::class);

it('can not resolve login action if data is empty', function () {
    expect(function () {
        LoginAction::resolve()->execute(
            data: LoginData::resolve([]),
        );
    })->toThrow(
        exception: Exception::class,
        exceptionMessage: 'Could not map type `App\DataTransferObjects\Auth\LoginData` with value array (empty).',
    );
});
