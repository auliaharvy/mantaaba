import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Design token PRD 7.1 (dari logo Mantaaba); mulai M1 dibaca dari CSS variable Setting
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/tailwind.blade.php',
        './resources/views/site/**/*.blade.php',
        './resources/views/components/site/**/*.blade.php',
        './resources/views/layouts/**/*.blade.php',
        './resources/views/auth/**/*.blade.php',
        './resources/views/dashboard/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    primary: '#5b3a1f', // cokelat tua: tombol, header, judul
                    'primary-hover': '#3e2714',
                    secondary: '#8a2d1e', // marun: tautan & aksen (kontras >= 4.5:1 di atas putih)
                    gold: '#c9a04a', // emas: dekoratif saja, bukan teks di atas putih
                    heading: '#2b1d12',
                    text: '#2b1d12',
                    muted: '#6e5b4b',
                    'footer-border': '#4a3018',
                    'section-bg': '#f7f1e6',
                    border: '#e8dccb',
                    surface: '#ffffff',
                },
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
