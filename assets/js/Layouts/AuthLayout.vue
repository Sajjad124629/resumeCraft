<script setup lang="ts">
import AuthCoverLayout from '@/layouts/auth/AuthCoverLayout.vue';
import MainLoader from '@/components/icon/MainLoader.vue';
import NavigateTop from '@/components/NavigateTop.vue';
import { useAppStore } from '@/stores/index'
import { onMounted, } from 'vue';
const store = useAppStore();

onMounted(() => {
        store.toggleMainLoader()
})
</script>

<template>
    <div class="main-section antialiased relative font-nunito text-sm font-normal"
        :class="[store.menu, store.layout, store.rtlClass]">
        <MainLoader :isShowMainLoader="store.isShowMainLoader" />
        <NavigateTop />
        <div class="text-black dark:text-white-dark">
            <AuthCoverLayout>
                <slot />
            </AuthCoverLayout>
        </div>
    </div>
</template>
