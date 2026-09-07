<script setup lang="ts">
withDefaults(defineProps<{ width?: string, height?: string, color?: string }>(), {
    width: '24',
    height: '24',
    color: '#9b9b9b'
})
</script>
<template>
    <svg xmlns="http://www.w3.org/2000/svg" :width="width" :height="height" viewBox="0 0 24 24" fill="none"
        class="injected-svg" data-src="https://cdn.hugeicons.com/icons/user-circle-stroke-rounded.svg?v=1.0.1"
        xmlns:xlink="http://www.w3.org/1999/xlink" role="img" :color="color">
        <path
            d="M15 9C15 7.34315 13.6569 6 12 6C10.3431 6 9 7.34315 9 9C9 10.6569 10.3431 12 12 12C13.6569 12 15 10.6569 15 9Z"
            :stroke="color" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        <path
            d="M22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12Z"
            :stroke="color" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
        <path d="M17 17C17 14.2386 14.7614 12 12 12C9.23858 12 7 14.2386 7 17" :stroke="color" stroke-width="1.5"
            stroke-linecap="round" stroke-linejoin="round"></path>
    </svg>
</template>
