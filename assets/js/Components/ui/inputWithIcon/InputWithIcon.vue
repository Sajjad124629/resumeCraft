<script setup lang="ts">
import { useVModel } from '@vueuse/core';

const props = defineProps<{
    autocomplete?: string
    placeholder?: string
    required?: boolean
    disabled?: boolean
    readonly?: boolean
    type: string
    defaultValue?: string | number
    modelValue?: string | number
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void
}>()
const modelValue = useVModel(props,'modelValue',emit,{
    passive: true,
    defaultValue: props.defaultValue,
})
</script>
<template>
    <div class="relative text-white-dark">
        <input v-model="modelValue" :type="type" :placeholder="placeholder" :required="required" :disabled="disabled" :readonly="readonly" :autocomplete="autocomplete"
            class="form-input ps-10 placeholder:text-white-dark" />
        <span class="absolute -translate-y-1/2 start-4 top-1/2">
            <slot />
        </span>
    </div>
</template>
