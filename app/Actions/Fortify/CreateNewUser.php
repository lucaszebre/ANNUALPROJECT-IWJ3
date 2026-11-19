<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(12)->letters()->mixedCase()->numbers(),
            ],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => mb_strtolower($input['email']),
            'password' => $input['password'],
        ]);
    }
}
