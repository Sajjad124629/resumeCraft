<script setup lang="ts">
defineProps<{
    message?: string;
    classes?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p class="text-sm text-red-600 dark:text-red-500 mt-2" :class="classes">
            {{ message }}
        </p>
    </div>
</template>
