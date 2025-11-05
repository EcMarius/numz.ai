import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import fs from 'fs';
import path from 'path';

const themeFilePath = path.resolve(__dirname, 'theme.json');
const activeTheme = fs.existsSync(themeFilePath) ? JSON.parse(fs.readFileSync(themeFilePath, 'utf8')).name : 'anchor';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        // Only scan YOUR files, not vendor files (prevents encoding issues)
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './resources/themes/' + activeTheme + '/**/*.blade.php',
        './wave/resources/views/**/*.blade.php',
        './resources/plugins/**/*.blade.php',
        './resources/plugins/**/*.php',
        // NOTE: JS files removed - Tailwind's Vue preprocessor causes UTF-8 errors
        // We don't need to scan JS for Tailwind classes anyway
    ],

    theme: {
        extend: {
            animation: {
                'marquee': 'marquee 25s linear infinite',
            },
            keyframes: {
                'marquee': {
                    from: { transform: 'translateX(0)' },
                    to: { transform: 'translateX(-100%)' },
                }
            }
        },
    },

    plugins: [forms, require('@tailwindcss/typography')],
};
