/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./templates/**/*.html.twig",
    "./src/**/*.php",
    "./assets/**/*.js",
  ],
  theme: {
    extend: {
      colors: {
        'space-cadet': '#1d1e35',
        'vivid-sky-blue': '#42deff',
        'anti-flash-white': '#ededed',
        'accent-coral': '#FF6B6B',
        'slate-gray': '#6c757d',
      },
      fontFamily: {
        'inter': ['Inter', 'sans-serif'],
        'montserrat': ['Montserrat', 'sans-serif'],
        'poppins': ['Poppins', 'sans-serif'],
      },
      spacing: {
        '18': '4.5rem',
        '88': '22rem',
        '128': '32rem',
        '144': '36rem',
      },
      animation: {
        'fade-in-up': 'fadeInUp 0.5s ease-out forwards',
        'slide-in': 'slideIn 0.3s ease-out',
      },
      keyframes: {
        fadeInUp: {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideIn: {
          '0%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(0)' },
        },
      },
      zIndex: {
        '999': '999',
        '1000': '1000',
        '1001': '1001',
      },
    },
  },
  plugins: [],
}
