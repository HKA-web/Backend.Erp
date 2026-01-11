<?php

use App\Actions\Auth\RegisterAction;
use App\DataTransferObjects\Auth\RegisterData;
use App\Models\User;

it('can resolve register action', function () {
    $action = RegisterAction::resolve()->execute(
        data: RegisterData::resolve([
            'name' => 'John Doe',
            'email' => 'john@doe.com',
            'password' => 'password',
        ]),
    );

    expect(collect($action)->toJson())->toContain(needles: 'John Doe');
});

it('can not resolve register action if email has already exists', function () {
    expect(function () {
        $user = User::factory()->create();

        RegisterAction::resolve()->execute(
            data: RegisterData::resolve([
                'name' => 'John Doe',
                'email' => $user->email,
                'password' => 'password',
            ]),
        );
    })->toThrow(
        exception: Exception::class,
        exceptionMessage: 'Integrity constraint violation: 1062 Duplicate entry'
    );
});

it('can not resolve register action if data is empty', function () {
    expect(function () {
        RegisterAction::resolve()->execute(
            data: RegisterData::resolve([]),
        );
    })->toThrow(
        exception: Exception::class,
        exceptionMessage: 'Could not map type `App\DataTransferObjects\Auth\RegisterData` with value array (empty).'
    );
});
