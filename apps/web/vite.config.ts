import path from 'node:path';

import { sveltekit } from '@sveltejs/kit/vite';
import autoprefixer from 'autoprefixer';
import tailwindcss from 'tailwindcss';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode, command }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const proxyTarget = env.VITE_DEV_API_PROXY_TARGET || 'http://localhost:8880';

  return {
    plugins: [sveltekit()],
    resolve: {
      alias: {
        '@': path.resolve('./src')
      }
    },
    css: {
      postcss: {
        plugins: [
          tailwindcss({
            content: ['./src/**/*.{html,js,svelte,ts}'],
            darkMode: 'class'
          }),
          autoprefixer()
        ]
      }
    },
    server: {
      proxy: command === 'serve'
        ? {
            '/api': {
              target: proxyTarget,
              changeOrigin: true
            }
          }
        : undefined
    },
    test: {
      environment: 'node',
      include: ['tests/**/*.test.ts']
    }
  };
});
