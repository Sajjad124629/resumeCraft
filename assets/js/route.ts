import { usePage } from '@inertiajs/vue3';

// Cache routes in memory so route() works even when invoked outside Vue setup context
let cachedRoutes: Record<string, string> = {};

export function setCachedRoutes(routes: Record<string, string>) {
    if (routes && typeof routes === 'object') {
        cachedRoutes = { ...cachedRoutes, ...routes };
    }
}

/**
 * Dynamic Route helper for Symfony + Inertia
 */
export function route(name?: string, params?: any): any {
    // Helper to get routes from usePage or cache
    const getRoutes = (): Record<string, string> => {
        try {
            const page = usePage();
            const pageRoutes = page?.props?.routes as Record<string, string> | undefined;
            if (pageRoutes) {
                cachedRoutes = { ...cachedRoutes, ...pageRoutes };
                return cachedRoutes;
            }
        } catch (e) {
            // Not in setup context, use cached routes
        }
        return cachedRoutes;
    };

    if (!name) {
        return {
            current: (routeName?: string) => {
                if (typeof window !== 'undefined' && window.location) {
                    const currentPath = window.location.pathname;
                    if (!routeName) return currentPath;

                    // If routeName is a direct path like '/login'
                    if (routeName.startsWith('/')) {
                        return currentPath === routeName || currentPath.replace(/\/$/, '') === routeName.replace(/\/$/, '');
                    }

                    const routes = getRoutes();
                    // Match route by name or prefix 'app_'
                    const targetPath = routes[routeName] || routes[`app_${routeName}`] || (routeName.startsWith('app_') ? routes[routeName.replace(/^app_/, '')] : undefined);
                    if (targetPath) {
                        return currentPath === targetPath || currentPath.replace(/\/$/, '') === targetPath.replace(/\/$/, '');
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
    const routes = getRoutes();
    const resolvedRouteName = routes[name] ? name : (routes[`app_${name}`] ? `app_${name}` : (name.startsWith('app_') && routes[name.replace(/^app_/, '')] ? name.replace(/^app_/, '') : null));

    if (resolvedRouteName && routes[resolvedRouteName]) {
        let path = routes[resolvedRouteName];
        if (params !== undefined && params !== null) {
            if (typeof params === 'object') {
                const queryParams = new URLSearchParams();
                Object.keys(params).forEach(key => {
                    if (path.includes(`:${key}`)) {
                        path = path.replace(`:${key}`, encodeURIComponent(params[key]));
                    } else if (path.includes(`{${key}}`)) {
                        path = path.replace(`{${key}}`, encodeURIComponent(params[key]));
                    } else {
                        queryParams.append(key, params[key]);
                    }
                });
                const queryString = queryParams.toString();
                if (queryString) {
                    path += (path.includes('?') ? '&' : '?') + queryString;
                }
            } else {
                path += '/' + encodeURIComponent(params);
            }
        }
        return path;
    }

    // 2. Default dynamic route string resolver fallback
    let path = '/' + name.replace(/\./g, '/');
    if (params !== undefined && params !== null) {
        if (typeof params === 'object') {
            const queryParams = new URLSearchParams();
            Object.keys(params).forEach(key => {
                if (path.includes(`:${key}`)) {
                    path = path.replace(`:${key}`, encodeURIComponent(params[key]));
                } else if (path.includes(`{${key}}`)) {
                    path = path.replace(`{${key}}`, encodeURIComponent(params[key]));
                } else {
                    queryParams.append(key, params[key]);
                }
            });
            const queryString = queryParams.toString();
            if (queryString) {
                path += (path.includes('?') ? '&' : '?') + queryString;
            }
        } else {
            path += '/' + encodeURIComponent(params);
        }
    }
    return path;
}

export default route;
