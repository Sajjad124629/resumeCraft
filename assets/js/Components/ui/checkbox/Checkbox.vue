<script setup lang="ts">
import { useVModel } from '@vueuse/core';
import { cn } from '@/lib/utils'
import { HtmlHTMLAttributes } from 'vue';
const props = defineProps<{
    required?: boolean
    disabled?: boolean
    readonly?: boolean
    defaultValue?: boolean
    modelValue?: boolean
    class?: HtmlHTMLAttributes['class']
    containerClass?: HtmlHTMLAttributes['class']
}>()
const emit = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void
}>()
const modelValue = useVModel(props, 'modelValue', emit, {
    passive: true,
    defaultValue: props.defaultValue ?? false,
})
</script>
<template>
    <label :class="cn('flex items-center cursor-pointer', props.containerClass,)">
        <input type="checkbox" v-model="modelValue" :required="required" :disabled="disabled" :readonly="readonly" :class="cn('form-checkbox', props.class,)" />
        <slot />
    </label>
</template>
