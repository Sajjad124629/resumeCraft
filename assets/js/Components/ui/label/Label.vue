<script setup lang="ts">
import { HtmlHTMLAttributes } from 'vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Quiz01Icon } from '@hugeicons/core-free-icons';
import { cn } from '@/Lib/utils'
const props = defineProps<{
    isRequired?: boolean
    isIcon?: boolean
    iconMessage?: string
    class?: HtmlHTMLAttributes['class']
}>()
</script>

<template>
    <label :class="cn(props.class, 'flex')">
        <slot /> <span v-if="isRequired" class="text-danger">*</span>
        <HugeiconsIcon v-if="isIcon" :icon="Quiz01Icon" :size="16" color="currentColor" :stroke-width="1.5" :title="iconMessage" class="ml-1"/>
    </label>
</template>
