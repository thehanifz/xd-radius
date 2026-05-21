import defaultTheme from 'tailwindcss/defaultTheme';

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
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                sidebar: {
                    DEFAULT: '#0f172a',
                    hover:   '#1e293b',
                    active:  '#1e3a8a',
                    border:  '#1e293b',
                    text:    '#94a3b8',
                    'text-active': '#e2e8f0',
                },
            },
        },
    },
    plugins: [],
};
