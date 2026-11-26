import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

// https://vitejs.dev/config/
export default defineConfig(() => {
  return {
    server: {
      host: '0.0.0.0',
      port: 5173,
    },
    resolve: {
      alias: [
        { find: '@', replacement: './app/client/src' },
        // frontdesk-kit's consumable CSS preset (build-time import, see
        // app/client/src/css/tailwind.css). Keeps the vendor path in one place.
        // Must be ABSOLUTE: the @tailwindcss/vite resolver does not accept a
        // relative alias replacement for CSS @import.
        {
          find: '@fdk',
          replacement: fileURLToPath(
            new URL(
              './vendor/atwx/silverstripe-frontdesk-kit/client/src/css',
              import.meta.url
            )
          ),
        },
      ],
    },
    base: './',
    publicDir: 'app/client/public',
    build: {
      outDir: './app/client/dist',
      manifest: true,
      sourcemap: true,
      rollupOptions: {
        input: {
          'main.js': './app/client/src/js/main.js',
          'main.scss': './app/client/src/scss/main.scss',
          'tailwind.css': './app/client/src/css/tailwind.css',
        },
      },
    },
    plugins: [tailwindcss()],
  }
})
