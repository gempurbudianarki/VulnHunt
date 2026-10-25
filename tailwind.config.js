import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                // VulnHunt Brand Palette
                brand: {
                    primary: '#253AA1',
                    'primary-hover': '#1E2D7D',
                    'primary-light': '#EEF2FF',
                    accent: '#F88706',
                    'accent-hover': '#E07500',
                    'accent-light': '#FFF7ED',
                    slate: '#4B6186',
                    'slate-light': '#F1F5F9',
                    canvas: '#EEF2F6',
                    surface: '#FFFFFF',
                    dark: '#0F172A',
                    muted: '#64748B',
                    border: '#E2E8F0',
                },
                // Severity Rarity Colors
                severity: {
                    info: '#64748B',
                    low: '#2563EB',
                    medium: '#D97706',
                    high: '#EA580C',
                    critical: '#DC2626',
                },
                // Backward compatibility aliases
                lumut: {
                    DEFAULT: '#0F172A',
                    dark: '#020617',
                    light: '#1E293B',
                },
                sage: {
                    DEFAULT: '#EEF2F6',
                    light: '#F8FAFC',
                    dark: '#E2E8F0',
                },
                'putih-daun': '#FFFFFF',
                daun: {
                    DEFAULT: '#253AA1',
                    hover: '#1E2D7D',
                    light: '#3B52C7',
                },
                ambar: {
                    DEFAULT: '#F88706',
                    light: '#FBA33A',
                    dark: '#D97100',
                },
                'abu-batu': {
                    DEFAULT: '#64748B',
                    light: '#94A3B8',
                    dark: '#334155',
                },
            },
            fontFamily: {
                sans: ['"Inter"', '"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
                display: ['"Plus Jakarta Sans"', '"Inter"', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            borderRadius: {
                'clay-sm': '12px',
                'clay-md': '18px',
                'clay-lg': '24px',
                'clay-xl': '32px',
                'clay-pill': '9999px',
                'panel': '24px',
                'clay': '24px',
                'input': '14px',
            },
            boxShadow: {
                'clay-card': '0 16px 32px -8px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.03), inset 0 2px 4px 0 rgba(255, 255, 255, 0.95), inset 0 -2px 4px 0 rgba(15, 23, 42, 0.02)',
                'clay-card-hover': '0 20px 40px -10px rgba(37, 58, 161, 0.12), 0 8px 16px -4px rgba(15, 23, 42, 0.05), inset 0 2px 4px 0 rgba(255, 255, 255, 1)',
                'clay-card-light': '0 4px 12px -2px rgba(15, 23, 42, 0.05), 0 2px 6px -1px rgba(15, 23, 42, 0.02), inset 0 1px 2px 0 rgba(255, 255, 255, 0.9)',
                'clay-btn-primary': '0 8px 16px -2px rgba(37, 58, 161, 0.35), 0 4px 8px -2px rgba(37, 58, 161, 0.2), inset 0 2px 2px 0 rgba(255, 255, 255, 0.35), inset 0 -2px 4px 0 rgba(0, 0, 0, 0.25)',
                'clay-btn-primary-hover': '0 12px 20px -2px rgba(37, 58, 161, 0.45), 0 6px 10px -2px rgba(37, 58, 161, 0.25), inset 0 2px 3px 0 rgba(255, 255, 255, 0.45)',
                'clay-btn-primary-active': '0 2px 4px 0 rgba(37, 58, 161, 0.3), inset 0 3px 6px 0 rgba(0, 0, 0, 0.35)',
                'clay-btn-accent': '0 8px 16px -2px rgba(248, 135, 6, 0.35), 0 4px 8px -2px rgba(248, 135, 6, 0.2), inset 0 2px 2px 0 rgba(255, 255, 255, 0.4), inset 0 -2px 4px 0 rgba(0, 0, 0, 0.2)',
                'clay-btn-accent-hover': '0 12px 20px -2px rgba(248, 135, 6, 0.45), inset 0 2px 3px 0 rgba(255, 255, 255, 0.5)',
                'clay-btn-accent-active': '0 2px 4px 0 rgba(248, 135, 6, 0.3), inset 0 3px 6px 0 rgba(0, 0, 0, 0.3)',
                'clay-btn-secondary': '0 4px 10px 0 rgba(15, 23, 42, 0.06), inset 0 2px 2px 0 rgba(255, 255, 255, 0.95), inset 0 -2px 3px 0 rgba(15, 23, 42, 0.04)',
                'clay-btn-secondary-hover': '0 6px 14px 0 rgba(15, 23, 42, 0.09), inset 0 2px 3px 0 rgba(255, 255, 255, 1)',
                'clay-btn-secondary-active': '0 1px 3px 0 rgba(15, 23, 42, 0.08), inset 0 2px 4px 0 rgba(15, 23, 42, 0.08)',
                'clay-input': 'inset 0 2px 4px 0 rgba(15, 23, 42, 0.04), 0 1px 2px 0 rgba(255, 255, 255, 0.8)',
                'clay-pill': '0 4px 8px -1px rgba(15, 23, 42, 0.06), inset 0 1px 1px 0 rgba(255, 255, 255, 0.8)',
                // Backward compatibility
                'clay-btn': '0 6px 14px -2px rgba(37, 58, 161, 0.3), inset 0 2px 2px 0 rgba(255, 255, 255, 0.35), inset 0 -2px 3px 0 rgba(0, 0, 0, 0.2)',
                'clay-btn-active': '0 2px 4px 0 rgba(37, 58, 161, 0.25), inset 0 3px 5px 0 rgba(0, 0, 0, 0.3)',
            },
        },
    },
    plugins: [forms, typography],
};
