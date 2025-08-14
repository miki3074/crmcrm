// vite.config.js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [
    vue({
      template: {
        transformAssetUrls: { base: null, includeAbsolute: false },
      },
    }),
    laravel({
      // подключай и css, и js
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
  build: {
    outDir: 'public/build',   // куда собирать
    manifest: true,           // нужен manifest.json для @vite()
    emptyOutDir: true,
  },
})


// import { defineConfig } from 'vite';
// import laravel from 'laravel-vite-plugin';
// import vue from '@vitejs/plugin-vue';

// export default defineConfig({
//     plugins: [
//         laravel({
//             input: 'resources/js/app.js',
//             refresh: true,
//         }),
//         vue({
//             template: {
//                 transformAssetUrls: {
//                     base: null,
//                     includeAbsolute: false,
//                 },
//             },
//         }),
//     ],
// });
