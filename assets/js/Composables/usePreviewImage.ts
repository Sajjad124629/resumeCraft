import { computed, onBeforeUnmount, Ref, ref } from 'vue'

export function usePreviewImage(
  file: Ref<File | null>,
  fallback: string | null = null
) {
  const objectUrl = ref<string | null>(null)
  const preview = computed(() => {
    if (file.value instanceof File) {
      if (objectUrl.value) {
        URL.revokeObjectURL(objectUrl.value)
      }
      objectUrl.value = URL.createObjectURL(file.value)
      return objectUrl.value
    }
    return fallback || '/image/no-image/no-image.png'
  })
  onBeforeUnmount(() => {
    if (objectUrl.value) {
      URL.revokeObjectURL(objectUrl.value)
    }
  })
  return {
    preview
  }
}
