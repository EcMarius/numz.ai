/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './entrypoints/**/*.{html,tsx,ts}',
    './components/**/*.{tsx,ts}',
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f5f5f5',
          100: '#e5e5e5',
          200: '#cccccc',
          300: '#b3b3b3',
          400: '#999999',
          500: '#808080',
          600: '#666666',
          700: '#4d4d4d',
          800: '#333333',
          900: '#1a1a1a',
          950: '#0a0a0a',
        },
      },
    },
  },
  plugins: [],
}
