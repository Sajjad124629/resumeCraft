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
    class?: string
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
    <input v-model="modelValue" :type="type" :placeholder="placeholder" :required="required" :disabled="disabled" :readonly="readonly" :autocomplete="autocomplete"
         :class="class"/>
</template>
