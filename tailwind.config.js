/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ['src/**/*.php'],
    theme: {
        extend: {},
    },
    plugins: [
        require('@tailwindcss/typography'),
        require('@tailwindcss/forms'),
    ],
}
