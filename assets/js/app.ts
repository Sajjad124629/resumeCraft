import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { plugin as VueTippy } from 'vue-tippy';
import 'tippy.js/dist/tippy.css';
import '../styles/app.css';

async function resolvePageComponent<T>(path: string, pages: Record<string, any>): Promise<T> {
    const page = pages[path];
    if (typeof page === 'undefined') {
        throw new Error(`Page not found: ${path}`);
    }
    return typeof page === 'function' ? await page() : page;
}

import Popper from 'vue3-popper';
import { PerfectScrollbarPlugin } from 'vue3-perfect-scrollbar';
import 'vue3-perfect-scrollbar/style.css';
import 'animate.css';
import { route } from './route';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Symfony App';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue');
        return resolvePageComponent(`./Pages/${name}.vue`, pages);
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(VueTippy, {
                directive: 'tippy',
                component: 'tippy',
            })
            .use(PerfectScrollbarPlugin);

        app.component('Popper', Popper);

        const translate = (key: string, replacements: Record<string, string> = {}) => {
            let translation = key;
            if (replacements && typeof replacements === 'object') {
                Object.keys(replacements).forEach(r => {
                    translation = translation.replace(`:${r}`, replacements[r]);
                });
            }
            return translation;
        };

        // Provide the route and __ helpers globally
        app.config.globalProperties.route = route;
        app.config.globalProperties.__ = translate;
        app.provide('route', route);
        app.provide('__', translate);

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
