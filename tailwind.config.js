/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './templates/test/index.html.twig', // Scan all Twig templates for Tailwind classes
    './assets/**/*.js',           // Scan your JS files (Encore/Vite)
  ],
  theme: {
    extend: {
      // Optional: add custom colors, fonts, etc. here
    },
  },
  plugins: [],
}
