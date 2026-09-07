import { ref } from 'vue';
import { safeFetch } from './safeFetch';
export function useFetch() {
  const loading = ref(false);
  const error = ref<string | null>(null);

  const get = async (fullUrl: string) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await safeFetch(fullUrl, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN':
            (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)
              ?.content || ''
        }
      });

      if (!response.ok)
        throw new Error(`HTTP error! Status: ${response.status}`);

      const data = await response.json();
      return data;
    } catch (err: any) {
      console.error('Fetch GET error:', err);
      error.value = err.message;
      return null;
    } finally {
      loading.value = false;
    }
  };

  return { get};
}
