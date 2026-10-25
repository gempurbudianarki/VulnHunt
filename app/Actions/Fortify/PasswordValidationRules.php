<?php

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     * Sesuai Bagian 10: Kebijakan password minimal 10 karakter.
     *
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return [
            'required',
            'string',
            Password::min(10)->letters()->numbers(),
            'confirmed',
        ];
    }
}
