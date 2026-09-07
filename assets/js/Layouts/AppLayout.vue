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

watch(
    () => page.props.flash,
    (flash:any) => {
        if (flash.message) {
            const toast = Swal.mixin({
                toast: true,
                position: 'bottom',
                showConfirmButton: false,
                timer: 3000,
                showCloseButton: true,
                customClass: {
                    popup: `color-${flash.alertType}`
                },
                // target: document.getElementById(flash.alertType + '-toast')
            });
            toast.fire({
                title: flash.message,
            });
        }
    },
   { deep: true }
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
