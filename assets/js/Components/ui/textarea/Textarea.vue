<script setup lang="ts">
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils';

const props = withDefaults(defineProps<{
    id?: string
    autocomplete?: string
    placeholder?: string
    required?: boolean
    disabled?: boolean
    readonly?: boolean
    defaultValue?: string | number
    modelValue?: string | number
    rows?: number | string
    cols?: number | string
    class?: string
}>(), {
    rows: 3,
})
const emit = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void
}>()
const modelValue = useVModel(props, 'modelValue', emit, {
    passive: true,
    defaultValue: props.defaultValue,
})
</script>
<template>
    <textarea :id="id" :rows="rows" :cols="cols" v-model="modelValue" :placeholder="placeholder" :required="required"
        :disabled="disabled" :readonly="readonly" :autocomplete="autocomplete"
        :class="cn('form-textarea', props.class)"></textarea>
</template>
