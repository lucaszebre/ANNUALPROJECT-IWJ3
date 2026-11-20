<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use LimitsCurrentPasswordGuesses;
    use PasswordValidationRules;

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $validator = Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ]);

        $this->validateCurrentPasswordGuess($user, $validator, 'current_password');

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
