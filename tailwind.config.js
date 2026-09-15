import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Design system per system-description2.md §6.2 — flat solid colors only, no gradients.
                // Primary teal/blue #0E7490, success #16A34A, warning #D97706, danger #DC2626.
                dhp: {
                    50: '#ECFEFF',
                    100: '#CFFAFE',
                    200: '#A5F3FC',
                    300: '#67E8F9',
                    400: '#22D3EE',
                    500: '#0E7490',
                    600: '#0E7490',
                    700: '#0C6474',
                    800: '#155E75',
                    900: '#083344',
                    950: '#04202B',
                },
            },
            boxShadow: {
                card: '0 1px 2px rgba(8, 51, 68, 0.06), 0 4px 16px rgba(8, 51, 68, 0.06)',
                pop: '0 8px 30px rgba(8, 51, 68, 0.16)',
            },
        },
    },

    plugins: [forms],
};
