import { usePage } from '@inertiajs/vue3';

/**
 * Dynamic Route helper for Symfony + Inertia
 */
export function route(name?: string, params?: any): any {
    if (!name) {
        return {
            current: (routeName?: string) => {
                if (typeof window !== 'undefined' && window.location) {
                    const currentPath = window.location.pathname;
                    if (!routeName) return currentPath;
                    try {
                        const routes = (usePage()?.props?.routes as Record<string, string>) || {};
                        const targetPath = routes[routeName];
                        if (targetPath) {
                            return currentPath === targetPath || currentPath.replace(/\/$/, '') === targetPath.replace(/\/$/, '');
                        }
                    } catch (e) {
                        // ignore
                    }
                    return false;
                }
                return false;
            }
        };
    }

    // Direct path
    if (name.startsWith('/')) {
        return name;
    }

    // 1. Dynamic lookup from Symfony Router (shared via Inertia props)
    try {
        const page = usePage();
        const routes = (page?.props?.routes as Record<string, string>) || {};
        if (routes[name]) {
            let path = routes[name];
            if (params !== undefined && params !== null) {
                if (typeof params === 'object') {
                    const queryParams = new URLSearchParams();
                    Object.keys(params).forEach(key => {
                        if (path.includes(`:${key}`)) {
                            path = path.replace(`:${key}`, params[key]);
                        } else if (path.includes(`{${key}}`)) {
                            path = path.replace(`{${key}}`, params[key]);
                        } else {
                            queryParams.append(key, params[key]);
                        }
                    });
                    const queryString = queryParams.toString();
                    if (queryString) {
                        path += (path.includes('?') ? '&' : '?') + queryString;
                    }
                } else {
                    path += '/' + params;
                }
            }
            return path;
        }
    } catch (e) {
        // Fallback
    }

    // 2. Default dynamic route string resolver
    let path = '/' + name.replace(/\./g, '/');
    if (params !== undefined && params !== null) {
        if (typeof params === 'object') {
            const queryParams = new URLSearchParams();
            Object.keys(params).forEach(key => {
                if (path.includes(`:${key}`)) {
                    path = path.replace(`:${key}`, params[key]);
                } else if (path.includes(`{${key}}`)) {
                    path = path.replace(`{${key}}`, params[key]);
                } else {
                    queryParams.append(key, params[key]);
                }
            });
            const queryString = queryParams.toString();
            if (queryString) {
                path += (path.includes('?') ? '&' : '?') + queryString;
            }
        } else {
            path += '/' + params;
        }
    }
    return path;
}

export default route;
