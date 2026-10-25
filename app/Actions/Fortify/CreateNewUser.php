<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\CompetitionSettings;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        $settings = app(CompetitionSettings::class);

        // 1. Cek jendela pendaftaran
        if (! $settings->isRegistrationOpen()) {
            throw ValidationException::withMessages([
                'email' => ['Pendaftaran peserta saat ini sedang ditutup oleh panitia.'],
            ]);
        }

        // 2. Validasi input
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'nim' => [
                'required',
                'string',
                'max:30',
                Rule::unique(User::class, 'nim'),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class, 'email'),
                function ($attribute, $value, $fail) use ($settings) {
                    $allowedDomains = $settings->get('allowed_email_domains');
                    if (! empty($allowedDomains) && is_array($allowedDomains)) {
                        $emailParts = explode('@', (string) $value);
                        if (count($emailParts) !== 2) {
                            $fail('Format email tidak valid.');

                            return;
                        }

                        $emailDomain = strtolower($emailParts[1]);
                        $matched = false;

                        foreach ($allowedDomains as $domain) {
                            $domain = strtolower(trim(ltrim($domain, '@.')));
                            if ($domain === '') {
                                continue;
                            }
                            // Cocok persis atau subdomain (misal mhs.ubbg.ac.id)
                            if ($emailDomain === $domain || Str::endsWith($emailDomain, ".{$domain}")) {
                                $matched = true;
                                break;
                            }
                        }

                        if (! $matched) {
                            $fail('Email harus menggunakan alamat email resmi kampus ('.implode(', ', $allowedDomains).').');
                        }
                    }
                },
            ],
            'phone' => ['required', 'string', 'max:25'],
            'study_program' => ['required', 'string', 'max:100'],
            'cohort_year' => ['required', 'integer', 'min:2015', 'max:'.(date('Y') + 1)],
            'password' => $this->passwordRules(),
        ], [
            'nim.unique' => 'NIM ini sudah terdaftar di sistem.',
            'email.unique' => 'Email ini sudah terdaftar di sistem.',
            'cohort_year.min' => 'Tahun angkatan tidak valid.',
            'cohort_year.max' => 'Tahun angkatan tidak valid.',
        ])->validate();

        // 3. Simpan pengguna
        $user = User::create([
            'name' => $input['name'],
            'nim' => trim($input['nim']),
            'email' => strtolower(trim($input['email'])),
            'phone' => trim($input['phone']),
            'study_program' => trim($input['study_program']),
            'cohort_year' => (int) $input['cohort_year'],
            'password' => Hash::make($input['password']),
            'is_active' => true,
        ]);

        // 4. Berikan peran participant
        $user->assignRole('participant');

        return $user;
    }
}
