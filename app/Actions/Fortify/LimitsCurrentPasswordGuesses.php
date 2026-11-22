<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

/**
 * Limits wrong guesses at the current password on the profile and password
 * forms, so a stolen session cannot be used to find the password. Same limit
 * as the login form: 5 wrong passwords a minute, counted per user across both
 * forms. Saves with a correct password, or without one, are never counted.
 */
trait LimitsCurrentPasswordGuesses
{
    /**
     * Run the validator, counting a failed `current_password` rule on $field.
     *
     * @throws ThrottleRequestsException when the limit is reached (429)
     * @throws ValidationException
     */
    protected function validateCurrentPasswordGuess(User $user, Validator $validator, string $field): void
    {
        $key = 'current-password:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw new ThrottleRequestsException(headers: ['Retry-After' => RateLimiter::availableIn($key)]);
        }

        $fails = $validator->fails();

        if (isset($validator->failed()[$field]['CurrentPassword'])) {
            RateLimiter::hit($key, 60);
        } elseif (filled($validator->getData()[$field] ?? null)) {
            RateLimiter::clear($key);
        }

        if ($fails) {
            throw new ValidationException($validator);
        }
    }
}
