<script setup lang="ts">
import { Settings } from '@/types';
defineProps<{
    settings: Settings,
}>();
</script>
<template>
    <div class="dark:text-white-dark text-center ltr:sm:text-left rtl:sm:text-right p-6 pt-0 mt-auto">
        © {{ new Date().getFullYear() }}. {{ settings.developed_by }} All rights reserved.
    </div>
</template>
