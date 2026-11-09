import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

// Design token PRD 6.1 (diambil dari CSS akp2i.or.id)
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/tailwind.blade.php',
        './resources/views/site/**/*.blade.php',
        './resources/views/components/site/**/*.blade.php',
        './resources/views/layouts/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                brand: {
                    primary: '#00923f', // tombol, link, aksen (teks putih di atasnya hanya >= 18px bold)
                    'primary-hover': '#00612a',
                    secondary: '#00662c', // teks kecil di atas putih / latar untuk teks putih kecil
                    heading: '#002d14',
                    text: '#000000',
                    muted: '#3f7959',
                    'footer-border': '#005726',
                    'section-bg': '#f8faff',
                    border: '#e1e5ee',
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
