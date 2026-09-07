export const refreshCsrfToken = async () => {
    try {
        const response = await fetch('/csrf-token', {
            headers: {
                'Accept': 'application/json'
            }
        });
        const data = await response.json();
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && data.token) {
            meta.setAttribute('content', data.token);
        }
    } catch (error) {
        console.error('❌ Failed to refresh CSRF token:', error);
    }
};
