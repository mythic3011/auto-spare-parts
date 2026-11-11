// Shared legacy portal theme. Run npm --prefix resources/styles run build.
module.exports = {
  "darkMode": "class",
  "theme": {
    "extend": {
      "colors": {
        gray: require("tailwindcss/colors").slate,
        "app": {
          "bg": "var(--color-app-bg)",
          "surface": "var(--color-app-surface)",
          "soft": "var(--color-app-surface-soft)",
          "border": "var(--color-app-border)",
          "text": "var(--color-app-text)",
          "muted": "var(--color-app-muted)"
        },
        "brand": {
          "DEFAULT": "var(--color-brand)",
          "strong": "var(--color-brand-strong)",
          "soft": "var(--color-brand-soft)"
        },
        "primary": {
          "50": "#eff6ff",
          "100": "#dbeafe",
          "200": "#bfdbfe",
          "300": "#93c5fd",
          "400": "#60a5fa",
          "500": "#3b82f6",
          "600": "#2563eb",
          "700": "#1d4ed8",
          "800": "#1e40af",
          "900": "#1e3a8a",
          "950": "#172554"
        },
        "success": {
          "50": "#f0fdf4",
          "500": "#22c55e",
          "600": "#16a34a",
          "700": "#15803d"
        },
        "danger": {
          "50": "#fef2f2",
          "500": "#ef4444",
          "600": "#dc2626",
          "700": "#b91c1c"
        },
        "warning": {
          "50": "#fefce8",
          "500": "#eab308",
          "600": "#ca8a04",
          "700": "#a16207"
        }
      },
      "boxShadow": {
        "soft": "0 18px 45px rgba(15, 23, 42, 0.10)",
        "soft-dark": "0 18px 45px rgba(0, 0, 0, 0.35)"
      }
    },
    "fontFamily": {
      "body": [
        "Inter",
        "ui-sans-serif",
        "system-ui",
        "-apple-system",
        "Segoe UI",
        "Roboto",
        "Helvetica Neue",
        "Arial",
        "Noto Sans",
        "sans-serif"
      ],
      "sans": [
        "Inter",
        "ui-sans-serif",
        "system-ui",
        "-apple-system",
        "Segoe UI",
        "Roboto",
        "Helvetica Neue",
        "Arial",
        "Noto Sans",
        "sans-serif"
      ]
    }
  },
  "content": [
    "./index.php",
    "./logout*.{html,php}",
    "./page/**/*.{html,php}",
    "./resources/templates/**/*.php",
    "./resources/php/information.php",
    "./resources/js/**/*.js"
  ],
  "safelist": [
    "hidden"
  ],
  plugins: [require("@tailwindcss/forms"), require("@tailwindcss/typography"), require("@tailwindcss/aspect-ratio"), require("@tailwindcss/container-queries")]
};
