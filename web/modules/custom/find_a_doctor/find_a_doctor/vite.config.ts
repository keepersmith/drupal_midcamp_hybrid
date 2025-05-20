import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  base: '/modules/custom/find_a_doctor/find_a_doctor/dist/',
  build: {
    rollupOptions: {
      input: {
        app: './src/main.tsx',
      },
      output: {
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
        entryFileNames: '[name].js'
      }
    }
  }

})
