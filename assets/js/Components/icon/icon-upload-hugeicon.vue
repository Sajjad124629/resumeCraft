<script setup lang="ts">
withDefaults(defineProps<{ width?: string,height?:string,color?:string }>(), {
    width: '24',
    height: '24',
    color: '#9b9b9b'
})
</script>

<template>
    <svg xmlns="http://www.w3.org/2000/svg" :width="width" :height="height" viewBox="0 0 24 24" fill="none" class="injected-svg" data-src="https://cdn.hugeicons.com/icons/upload-04-stroke-standard.svg?v=2.0" xmlns:xlink="http://www.w3.org/1999/xlink" role="img" :color="color">
<path d="M21 20V17.5C21 16.9477 20.5523 16.5 20 16.5H16.8284C16.298 16.5 15.7893 16.7107 15.4142 17.0858L14.7929 17.7071C14.6054 17.8946 14.351 18 14.0858 18H9.91421C9.649 18 9.39464 17.8946 9.20711 17.7071L8.58579 17.0858C8.21071 16.7107 7.70201 16.5 7.17157 16.5H4C3.44772 16.5 3 16.9477 3 17.5V20C3 20.5523 3.44772 21 4 21H20C20.5523 21 21 20.5523 21 20Z" :stroke="color" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
<path d="M7.5 7.5L12 3L16.5 7.5M12 4V15" :stroke="color" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
</svg>
</template>
