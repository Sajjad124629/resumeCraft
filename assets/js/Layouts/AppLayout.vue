<script setup lang="ts">
import MainLoader from '@/Components/icon/MainLoader.vue';
import NavigateTop from '@/Components/NavigateTop.vue';
import ThemeCustomizer from '@/Components/ThemeCustomizer.vue';
import Sidebar from './app/Sidebar.vue';
import Header from './app/Header.vue';
import Footer from './app/Footer.vue';
import Swal from 'sweetalert2'
import { useAppStore } from '@/Stores/index'
import { computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Settings } from '@/types';

import appSetting from '@/app-setting';

const store = useAppStore();
const page = usePage();
const settings = computed(() => page.props.settings as Settings);
defineProps<{
  errors?: any
  name?: string
  quote?: { message: string, author: string }
  auth?: any
  ziggy?: any
  sidebarOpen?: boolean
  flash?: any
}>()
onMounted(() => {
    appSetting.init();
    store.toggleMainLoader()
})

const showToast = (flash: any) => {
    if (flash && flash.message) {
        const iconType = (flash.alertType === 'error' || flash.alertType === 'danger') 
            ? 'error' 
            : (flash.alertType === 'success' ? 'success' : (flash.alertType === 'warning' ? 'warning' : 'info'));
        
        const toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            showCloseButton: true,
            didOpen: (toastEl) => {
                toastEl.onmouseenter = Swal.stopTimer;
                toastEl.onmouseleave = Swal.resumeTimer;
            }
        });
        toast.fire({
            icon: iconType,
            title: flash.message,
        });
    }
};

watch(
    () => page.props.flash,
    (flash: any) => {
        showToast(flash);
    },
    { deep: true, immediate: true }
);

</script>

<template>
    <div class="main-section antialiased relative font-nunito text-sm font-normal"
        :class="[store.sidebar ? 'toggle-sidebar' : '', store.menu, store.layout, store.rtlClass]">
        <div class="relative">
            <!-- sidebar menu overlay -->
            <div class="fixed inset-0 bg-[black]/60 z-50 lg:hidden" :class="{ hidden: !store.sidebar }"
                @click="store.toggleSidebar()"></div>
            <MainLoader :isShowMainLoader="store.isShowMainLoader" />
            <NavigateTop />
            <ThemeCustomizer />
            <!-- Ambient 3D studio light mesh orbs in background -->
            <div class="fixed inset-0 pointer-events-none overflow-hidden -z-10 select-none">
                <div class="absolute -top-32 -left-32 w-96 h-96 bg-primary/10 dark:bg-primary/15 rounded-full blur-3xl filter"></div>
                <div class="absolute top-1/3 -right-32 w-96 h-96 bg-purple-500/10 dark:bg-purple-600/10 rounded-full blur-3xl filter"></div>
                <div class="absolute -bottom-32 left-1/3 w-96 h-96 bg-cyan-500/10 dark:bg-cyan-500/10 rounded-full blur-3xl filter"></div>
            </div>
            <div class="main-container text-black dark:text-white-dark min-h-screen" :class="[store.navbar]">
                <Sidebar :settings="settings" />
                <div class="main-content flex flex-col min-h-screen">
                    <Header :settings="settings"/>
                    <div class="pl-6 pr-6 animation">
                        <slot />
                    </div>
                    <Footer :settings="settings"/>
                </div>
            </div>
        </div>
    </div>
</template>
