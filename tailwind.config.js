import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Design token PRD 7.1: warna dari CSS variable yang ditulis layout dari Setting, sehingga perubahan warna berlaku tanpa build ulang.
const token = (name) => `rgb(var(--color-${name}) / <alpha-value>)`;

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/tailwind.blade.php',
        './resources/views/site/**/*.blade.php',
        './resources/views/components/site/**/*.blade.php',
        './resources/views/layouts/**/*.blade.php',
        './resources/views/auth/**/*.blade.php',
        './resources/views/dashboard/**/*.blade.php',
        './config/mantaaba.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    primary: token('primary'), // hijau hutan: tombol, header, judul
                    'primary-hover': token('primary-dark'),
                    gold: token('secondary'), // emas: dekoratif saja, bukan teks di atas putih
                    accent: token('accent'), // terakota: tautan & CTA sekunder (kontras >= 4.5:1 di atas putih)
                    'section-bg': token('surface'), // putih kehijauan
                    heading: '#14231C',
                    text: '#1F2B25',
                    muted: '#5A6B62',
                    'footer-border': '#2C5A47',
                    border: '#DFE7E1',
                    surface: '#ffffff',
                },
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                display: ['Cinzel', ...defaultTheme.fontFamily.serif],
                arabic: ['Amiri', ...defaultTheme.fontFamily.serif],
            },
            borderRadius: { site: '10px' },
        },
    },

    plugins: [forms],
};
