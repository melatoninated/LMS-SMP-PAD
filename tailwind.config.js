import forms from '@tailwindcss/forms';

export default {
  content: ['./resources/views/**/*.blade.php', './app/**/*.php'],
  theme: { extend: {} },
  plugins: [forms]
}
