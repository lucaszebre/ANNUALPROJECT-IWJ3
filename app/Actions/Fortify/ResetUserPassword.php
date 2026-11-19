<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    public function reset(mixed $user, array $input): void
    {
        Validator::make($input, [
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(12)->letters()->mixedCase()->numbers(),
            ],
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
