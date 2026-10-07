<script setup lang="ts">
import { Settings } from '@/types';
import IconHelpCircle from '@/Components/icon/icon-help-circle.vue';

defineProps<{
    settings?: Settings,
}>();

function openSupportTicket() {
    if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('open-support-ticket'));
    }
}
</script>
<template>
    <footer
        class="dark:text-white-dark flex flex-col sm:flex-row items-center justify-between gap-2 p-6 pt-2 mt-auto text-xs text-gray-500 dark:text-gray-400 border-t border-gray-100/60 dark:border-gray-800/60">
        <div>
            © {{ new Date().getFullYear() }}. {{ settings?.developed_by || 'ResumeCraft' }} All rights reserved.
        </div>
        <!-- <div class="flex items-center gap-4">
            <button
                type="button"
                @click="openSupportTicket"
                class="inline-flex items-center gap-1.5 text-primary hover:underline font-semibold transition cursor-pointer"
                title="Open support ticket submission modal"
            >
                <IconHelpCircle class="w-4 h-4 text-primary" />
                <span>Create support ticket</span>
            </button>
        </div> -->
    </footer>
</template>
