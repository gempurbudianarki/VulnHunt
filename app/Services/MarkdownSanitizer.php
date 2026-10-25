<?php

namespace App\Services;

use Illuminate\Support\Str;

class MarkdownSanitizer
{
    /**
     * Membersihkan dan merender markdown menggunakan League CommonMark bawaan Laravel
     * dengan opsi html_input => 'strip' dan allow_unsafe_links => false.
     */
    public static function clean(?string $markdown): string
    {
        return $markdown ?? '';
    }

    /**
     * Merender teks markdown menjadi HTML yang aman.
     */
    public static function render(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
