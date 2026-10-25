import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                // Entomologist Field Guide Theme Tokens
                lumut: {
                    DEFAULT: '#17302A',
                    dark: '#0F201C',
                    light: '#23463D',
                },
                sage: {
                    DEFAULT: '#E9F0E6',
                    light: '#F4F7F2',
                    dark: '#D7E2D3',
                },
                'putih-daun': '#FBFDFA',
                daun: {
                    DEFAULT: '#2E7A58',
                    hover: '#256548',
                    light: '#3D9970',
                },
                ambar: {
                    DEFAULT: '#E2A83A',
                    light: '#F3BE58',
                    dark: '#C88E23',
                },
                'abu-batu': {
                    DEFAULT: '#5E6D68',
                    light: '#869590',
                    dark: '#45504C',
                },
                // Severity Rarity Colors
                severity: {
                    info: '#8A9690',
                    low: '#3E8E63',
                    medium: '#2F6FA8',
                    high: '#D9822B',
                    critical: '#C93A2E',
                },
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Bricolage Grotesque"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            borderRadius: {
                'panel': '24px',
                'clay': '20px',
                'input': '12px',
            },
            boxShadow: {
                'clay-btn': '0 4px 6px -1px rgba(23, 48, 42, 0.12), 0 2px 4px -2px rgba(23, 48, 42, 0.08), inset 0 1px 1px 0 rgba(255, 255, 255, 0.35)',
                'clay-btn-active': '0 1px 2px 0 rgba(23, 48, 42, 0.16), inset 0 2px 4px 0 rgba(0, 0, 0, 0.12)',
                'clay-card': '0 10px 25px -5px rgba(23, 48, 42, 0.06), 0 8px 10px -6px rgba(23, 48, 42, 0.04), inset 0 1px 2px 0 rgba(255, 255, 255, 0.8)',
            },
        },
    },
    plugins: [forms, typography],
};
