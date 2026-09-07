<script setup lang="ts">
withDefaults(defineProps<{ width?: string, height?: string, color?: string }>(), {
    width: '24',
    height: '24',
    color: '#9b9b9b'
})
</script>
<template>
    <svg xmlns="http://www.w3.org/2000/svg" :width="width" :height="height" viewBox="0 0 24 24" fill="none" class="injected-svg"
        data-src="https://cdn.hugeicons.com/icons/3d-move-stroke-standard.svg?v=3.0"
        xmlns:xlink="http://www.w3.org/1999/xlink" role="img" :color="color">
        <path d="M12 14L17.5 19M12 14L6.5 19M12 14V7" :stroke="color" stroke-width="1.5"></path>
        <path d="M2 21.5L4.5 16L8 22L2 21.5Z" :stroke="color" stroke-width="1.5" stroke-linecap="round"
            stroke-linejoin="round"></path>
        <path d="M22 21.5L19.5 16L16 22L22 21.5Z" :stroke="color" stroke-width="1.5" stroke-linecap="round"
            stroke-linejoin="round"></path>
        <path d="M12.0404 2L8.5 6.96857L15.5 7L12.0404 2Z" :stroke="color" stroke-width="1.5" stroke-linecap="round"
            stroke-linejoin="round"></path>
    </svg>
</template>
