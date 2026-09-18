import { refreshCsrfToken } from "@/csrf"; // adjust path

export const safeFetch = async (url: string, options: RequestInit = {}) => {
    let response = await fetch(url, options);
    if (response.status === 419) {
        await refreshCsrfToken();
        const newToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        options.headers = {
            ...options.headers,
            'X-CSRF-TOKEN': newToken,
        };

        response = await fetch(url, options);
    }

    return response;
};
