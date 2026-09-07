import { createSSRApp, h, type DefineComponent } from 'vue';
import { renderToString } from '@vue/server-renderer';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';

async function resolvePageComponent<T>(path: string, pages: Record<string, any>): Promise<T> {
    const page = pages[path];
    if (typeof page === 'undefined') {
        throw new Error(`Page not found: ${path}`);
    }
    return typeof page === 'function' ? await page() : page;
}

createInertiaApp({
    page: null,
    render: renderToString,
    title: (title) => `${title}`,
    resolve: (name) => {
        const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue');
        return resolvePageComponent(`./Pages/${name}.vue`, pages);
    },
    setup({ App, props, plugin }) {
        const pinia = createPinia();
        
        return createSSRApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia);
    },
});
