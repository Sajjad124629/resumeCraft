import { route } from '../route';

declare global {
    let route: typeof route;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof route;
    }
}
