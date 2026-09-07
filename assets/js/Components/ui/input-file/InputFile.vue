<script setup lang="ts">
import { useVModel } from '@vueuse/core'
import { ref } from 'vue';

const props = defineProps<{
  accept?: string
  multiple?: boolean
  required?: boolean
  disabled?: boolean
  class?: string
  modelValue?: File | File[] | null
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: File | File[] | null): void
}>()

// two-way binding with v-model
const modelValue = useVModel(props, 'modelValue', emit, {
  passive: true,
  defaultValue: props.multiple ? [] : null,
})
// Ref to native input
const inputRef = ref<HTMLInputElement | null>(null)

// Expose to parent
defineExpose({
  inputRef,
})

function onChange(event: Event) {
  const target = event.target as HTMLInputElement
  if (!target.files) {
    modelValue.value = props.multiple ? [] : null
    return
  }

  modelValue.value = props.multiple ? Array.from(target.files) : target.files[0]
}
</script>

<template>
  <input
    ref="inputRef"
    type="file"
    :accept="accept"
    :multiple="multiple"
    :required="required"
    :disabled="disabled"
    :class="class"
    @change="onChange"
  />
</template>
