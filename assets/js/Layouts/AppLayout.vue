<script setup lang="ts">
import MainLoader from '@/Components/icon/MainLoader.vue';
import NavigateTop from '@/Components/NavigateTop.vue';
import ThemeCustomizer from '@/Components/ThemeCustomizer.vue';
import Sidebar from './app/Sidebar.vue';
import Header from './app/Header.vue';
import Footer from './app/Footer.vue';
import SupportTicketModal from '@/Components/SupportTicketModal.vue';
import Swal from 'sweetalert2'
import { useAppStore } from '@/Stores/index'
import { computed, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Settings } from '@/types';

import appSetting from '@/app-setting';

const store = useAppStore();
const page = usePage();
const settings = computed(() => page.props.settings as Settings);
const isSupportTicketModalOpen = ref(false);

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
    store.toggleMainLoader();
    if (typeof window !== 'undefined') {
        window.addEventListener('open-support-ticket', () => {
            isSupportTicketModalOpen.value = true;
        });
        window.addEventListener('close-support-ticket', () => {
            isSupportTicketModalOpen.value = false;
        });
    }
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

            <!-- Global Support Ticket Modal -->
            <SupportTicketModal v-model="isSupportTicketModalOpen" />

            <!-- Floating Help & Support Action Button (Accessible from any page) -->
            <button
                type="button"
                @click="isSupportTicketModalOpen = true"
                class="fixed bottom-6 right-6 z-30 flex items-center gap-2 px-3.5 py-2.5 bg-gradient-to-r from-primary to-blue-600 text-white rounded-full shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all text-xs font-bold group select-none cursor-pointer"
                title="Help / Create Support Ticket"
            >
                <svg class="w-4 h-4 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="hidden md:inline">Support Ticket</span>
            </button>
        </div>
    </div>
</template>

