import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/**
 * Design rules enforced here, so they cannot be broken by accident in a view:
 *  1. Every corner is square. All border radius values resolve to 0.
 *  2. No gradients. The background image and gradient utilities are disabled.
 */
/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    // Badge tones are chosen in PHP (for example from a status enum), so the
    // class names never appear in full in the views.
    safelist: ['badge-neutral', 'badge-info', 'badge-success', 'badge-warning', 'badge-danger'],

    corePlugins: {
        backgroundImage: false,
        gradientColorStops: false,
    },

    theme: {
        borderRadius: {
            none: '0',
            sm: '0',
            DEFAULT: '0',
            md: '0',
            lg: '0',
            xl: '0',
            '2xl': '0',
            '3xl': '0',
            full: '0',
        },
        extend: {
            fontFamily: {
                sans: ['"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                brand: {
                    50: '#F1F7F3',
                    100: '#E0EEE5',
                    200: '#BFDCC9',
                    300: '#8FC0A1',
                    400: '#5A9E75',
                    500: '#2F7F52',
                    600: '#226A43',
                    700: '#1A5536',
                    800: '#14432B',
                    900: '#0F3321',
                    950: '#0A2417',
                },
                paper: '#F5F4EF',
                line: '#DCD9CF',
                ink: '#1B1F1C',
                muted: '#5B625D',
                gold: {
                    100: '#F6EBD2',
                    600: '#A6761C',
                    700: '#8A6117',
                },
            },
            boxShadow: {
                card: '0 1px 0 rgba(15, 51, 33, 0.06)',
            },
        },
    },

    plugins: [forms],
};
