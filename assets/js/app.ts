import { createApp, h, type DefineComponent, ref } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
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
import { route, setCachedRoutes } from './route';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Symfony App';

// Reactive translation store for instantaneous language updates across all components
const currentTranslations = ref<Record<string, string>>({});
const currentLocale = ref<string>('en');

const updateTranslations = (pageProps: any) => {
    if (pageProps?.translations && typeof pageProps.translations === 'object') {
        currentTranslations.value = pageProps.translations;
    }
    if (pageProps?.locale) {
        currentLocale.value = pageProps.locale;
    }
    if (pageProps?.routes && typeof pageProps.routes === 'object') {
        setCachedRoutes(pageProps.routes);
    }
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue');
        return resolvePageComponent(`./Pages/${name}.vue`, pages);
    },
    setup({ el, App, props, plugin }) {
        const pinia = createPinia();
        
        // Initialize translations from initial page props
        updateTranslations(props.initialPage?.props);

        // Keep translations and locale updated reactively on every Inertia navigation or mutation
        router.on('navigate', (event: any) => {
            updateTranslations(event.detail?.page?.props);
        });
        router.on('success', (event: any) => {
            updateTranslations(event.detail?.page?.props);
        });

        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .use(PerfectScrollbarPlugin);

        app.component('Popper', Popper);
            
        const translate = (key: string, replacements: Record<string, string> = {}) => {
            const translations = currentTranslations.value || {};
            let translation = translations[key] ?? key;
            if (replacements && typeof replacements === 'object') {
                Object.keys(replacements).forEach(r => {
                    translation = translation.replace(`:${r}`, replacements[r]);
                });
            }
            return translation;
        };

        (window as any).__ = translate;

        // Provide the route and __ helpers globally
        app.config.globalProperties.route = route;
        app.config.globalProperties.__ = translate;
        app.config.globalProperties.currentLocale = currentLocale;
        app.provide('route', route);
        app.provide('__', translate);
        app.provide('currentLocale', currentLocale);
        
        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
