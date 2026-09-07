<script setup lang="ts">
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css';
import { useVModel } from '@vueuse/core'
import { watch } from 'vue';

const props = defineProps<{
    modelValue?: string
    placeholder?: string
    disabled?: boolean
    defaultValue?: string
    class?: string
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', payload: string): void
}>()

// Two-way binding
const modelValue = useVModel(props, 'modelValue', emit, {
    passive: true,
    defaultValue: props.defaultValue ?? '',
})
watch(modelValue, (val) => {
  if (val === '<p><br></p>') {
    emit('update:modelValue', '')
  }
})
</script>

<template>
    <QuillEditor v-model:content="modelValue" contentType="html" theme="snow" toolbar="full" :placeholder="placeholder"
        :read-only="disabled" :class="class" />
</template>
