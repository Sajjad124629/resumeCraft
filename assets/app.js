import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';

createInertiaApp({
  title: title => title ? (title.includes('ResumeCraft') ? title : `${title} - ResumeCraft`) : 'ResumeCraft',
  resolve: name => {
    const pages = import.meta.glob('./js/Pages/**/*.vue', { eager: true });
    return pages[`./js/Pages/${name}.vue`].default;
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el);
  },
});
