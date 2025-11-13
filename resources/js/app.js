import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
// import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { ZiggyVue } from 'ziggy-js';

import axios from 'axios'

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// === Глобальный перехватчик Axios ===
// === Глобальный перехватчик Axios ===
axios.interceptors.response.use(
  response => response,
  error => {
    if (!error.response) return Promise.reject(error)

    const status = error.response.status

    // 403 — нет доступа
    if (status === 403) {
      if (window.location.pathname !== '/dashboard') {
        
        window.location.href = '/dashboard'
      }
    }

    // 404 — страница не найдена
    // if (status === 404) {
    //   if (window.location.pathname !== '/dashboard') {
        
    //     window.location.href = '/dashboard'
    //   }
    // }

    return Promise.reject(error)
  }
)