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
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                sidebar: {
                    DEFAULT:      '#0d1117',
                    hover:        '#161b22',
                    active:       '#1d2e4a',
                    border:       '#21262d',
                    text:         '#7d8590',
                    'text-active':'#e6edf3',
                },
                brand: {
                    50:  '#eef2ff',
                    100: '#e0e7ff',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                },
            },
            boxShadow: {
                'glow-indigo': '0 0 20px -5px rgba(99,102,241,0.4)',
                'glow-sm':     '0 0 10px -3px rgba(99,102,241,0.25)',
            },
            animation: {
                'fade-in':    'fadeIn 0.2s ease-out',
                'slide-up':   'slideUp 0.25s ease-out',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%':   { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideUp: {
                    '0%':   { opacity: '0', transform: 'translateY(8px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
        },
    },
    plugins: [],
};
