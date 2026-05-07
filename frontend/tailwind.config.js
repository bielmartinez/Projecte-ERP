/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts,jsx,tsx}'],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#2563eb',
          hover: '#1d4ed8',
          light: '#dbeafe',
        },
        sidebar: {
          DEFAULT: '#1e293b',
          hover: '#334155',
          active: '#475569',
        },
        danger: {
          DEFAULT: '#dc2626',
          hover: '#b91c1c',
          light: '#fee2e2',
        },
        success: {
          DEFAULT: '#16a34a',
          hover: '#15803d',
          light: '#dcfce7',
        },
      },
    },
  },
  plugins: [],
}
