<?php

namespace App\Services;

use Illuminate\Support\Str;

class MarkdownSanitizer
{
    /**
     * Protokol tautan yang diizinkan (aman).
     */
    protected const SAFE_SCHEMES = ['http', 'https', 'mailto'];

    /**
     * Membersihkan teks markdown dari tag HTML mentah dan protokol URL berbahaya.
     */
    public static function clean(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        // 1. Hapus tag blok berbahaya beserta isinya (script, style, iframe, object, embed, svg)
        $clean = preg_replace('/<(script|style|iframe|object|embed|applet)\b[^>]*>.*?<\/\1>/is', '', $markdown);

        // 2. Buang seluruh tag HTML mentah yang tersisa (misal <img onerror=...>, <a href=...>, <div onclick=...>)
        $clean = strip_tags($clean);

        // 3. Netralkan protokol tautan tidak aman pada sintaks markdown [text](url)
        $clean = preg_replace_callback('/\[(?<text>[^\]]+)\]\((?<url>(?:[^()]|\([^()]*\))*)\)/s', function ($matches) {
            $text = $matches['text'];
            $target = trim($matches['url']);

            // Ekstrak URL utama sebelum judul opsional
            $urlParts = preg_split('/\s+["\']/', $target);
            $url = trim($urlParts[0], '"\'');

            // Deteksi skema URL jika ada
            if (preg_match('/^\s*([a-zA-Z0-9+.-]+)\s*:/i', $url, $schemeMatches)) {
                $scheme = strtolower($schemeMatches[1]);
                if (! in_array($scheme, self::SAFE_SCHEMES, true)) {
                    // Tautan berbahaya (javascript:, data:, vbscript:, file:, dll) ditolak/dinetralkan
                    return "[{$text}](#)";
                }
            }

            return $matches[0];
        }, $clean);

        return trim($clean);
    }

    /**
     * Merender teks markdown menjadi HTML yang aman dan terformat.
     */
    public static function render(?string $markdown): string
    {
        $clean = static::clean($markdown);

        if (trim($clean) === '') {
            return '';
        }

        return Str::markdown($clean, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}
