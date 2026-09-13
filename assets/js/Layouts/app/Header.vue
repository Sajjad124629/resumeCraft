<script setup lang="ts">
import { ref, watch } from 'vue';
import { useAppStore } from '@/Stores/index';
import TextLink from '@/Components/TextLink.vue';
import IconMenu from '@/Components/icon/icon-menu.vue';
import IconSearch from '@/Components/icon/icon-search.vue';
import IconXCircle from '@/Components/icon/icon-x-circle.vue';
import IconSun from '@/Components/icon/icon-sun.vue';
import IconMoon from '@/Components/icon/icon-moon.vue';
import IconLaptop from '@/Components/icon/icon-laptop.vue';
import IconUser from '@/Components/icon/icon-user.vue';
// import IconMail from '@/components/icon/icon-mail.vue';
// import IconLockDots from '@/components/icon/icon-lock-dots.vue';
import IconLogout from '@/Components/icon/icon-logout.vue';
import { router, usePage } from '@inertiajs/vue3';
import { Settings } from '@/types';
import { computed } from 'vue';
const store = useAppStore();
const page = usePage();
const user = computed(() => (page.props.auth as any)?.user || {});
const languages = computed(() => page.props.languages || []);
const locale = computed(() => page.props.locale || 'en');
const dir = computed(() => page.props.dir || 'ltr');
defineProps<{
    settings?: Settings,
}>();
const currentLanguage = computed(() => {
    if (Array.isArray(languages.value)) {
        return languages.value.find((lang: any) => lang.code === locale.value);
    }
    return null;
})

// const settings = computed(() => page.props.settings as Settings);
watch(dir, (newDir) => {
    store.toggleRTL(newDir)
}, { immediate: true })

const handleLogout = () => {
    router.post(route('logout') as string);
};
const search = ref(false);
</script>
<template>
    <header class="z-40" :class="{ dark: store.semidark && store.menu === 'horizontal' }">
        <div class="shadow-sm">
            <div class="relative bg-white flex w-full items-center px-5 py-2.5 dark:bg-[#0e1726]">
                <div class="horizontal-logo flex lg:hidden justify-between items-center ltr:mr-2 rtl:ml-2">
                    <TextLink :href="route('dashboard')" class="main-logo flex items-center shrink-0">
                        <img class="w-8 ltr:-ml-1 rtl:-mr-1 inline" :src="settings?.logo ? '/storage/' + settings.logo : '/assets/images/logo.svg'" :alt="settings?.title || 'VR'" />
                        <span
                            class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle hidden md:inline dark:text-white-light transition-all duration-300">{{ settings?.title || 'VR' }}</span>
                    </TextLink>

                    <a href="javascript:;"
                        class="collapse-icon flex-none dark:text-[#d0d2d6] hover:text-primary dark:hover:text-primary flex lg:hidden ltr:ml-2 rtl:mr-2 p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:bg-white-light/90 dark:hover:bg-dark/60"
                        @click="store.toggleSidebar()">
                        <icon-menu class="w-5 h-5" />
                    </a>
                </div>
                <div
                    class="sm:flex-1 ltr:sm:ml-0 ltr:ml-auto sm:rtl:mr-0 rtl:mr-auto flex items-center space-x-1.5 lg:space-x-2 rtl:space-x-reverse dark:text-[#d0d2d6]">
                    <div class="sm:ltr:mr-auto sm:rtl:ml-auto">
                        <form
                            class="sm:relative absolute inset-x-0 sm:top-0 top-1/2 sm:translate-y-0 -translate-y-1/2 sm:mx-0 mx-4 z-10 sm:block hidden"
                            :class="{ '!block': search }" @submit.prevent="search = false">
                            <div class="relative">
                                <input type="text"
                                    class="form-input ltr:pl-9 rtl:pr-9 ltr:sm:pr-4 rtl:sm:pl-4 ltr:pr-9 rtl:pl-9 peer sm:bg-transparent bg-gray-100 placeholder:tracking-widest"
                                    placeholder="Search..." />
                                <button type="button"
                                    class="absolute w-9 h-9 inset-0 ltr:right-auto rtl:left-auto appearance-none peer-focus:text-primary">
                                    <icon-search class="mx-auto" />
                                </button>
                                <button type="button"
                                    class="hover:opacity-80 sm:hidden block absolute top-1/2 -translate-y-1/2 ltr:right-2 rtl:left-2"
                                    @click="search = false">
                                    <icon-x-circle />
                                </button>
                            </div>
                        </form>

                        <button type="button"
                            class="search_btn sm:hidden p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:bg-white-light/90 dark:hover:bg-dark/60"
                            @click="search = !search">
                            <icon-search class="w-4.5 h-4.5 mx-auto dark:text-[#d0d2d6]" />
                        </button>
                    </div>
                    <div>
                        <a href="javascript:;" v-show="store.theme === 'light'"
                            class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60 mb-1"
                            @click="store.toggleTheme('dark')">
                            <icon-sun />
                        </a>
                        <a href="javascript:;" v-show="store.theme === 'dark'"
                            class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60 mb-1"
                            @click="store.toggleTheme('system')">
                            <icon-moon />
                        </a>
                        <a href="javascript:;" v-show="store.theme === 'system'"
                            class="flex items-center p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60 mb-1"
                            @click="store.toggleTheme('light')">
                            <icon-laptop />
                        </a>
                    </div>
                    <div class="dropdown shrink-0" v-if="(languages as []).length > 0">
                        <Popper :placement="store.rtlClass === 'rtl' ? 'bottom-end' : 'bottom-start'"
                            offsetDistance="8">
                            <button type="button"
                                class="block p-2 rounded-full bg-white-light/40 dark:bg-dark/40 hover:text-primary hover:bg-white-light/90 dark:hover:bg-dark/60">
                                <img :src="currentLanguage?.image ? '/storage/' + currentLanguage?.image : '/image/language/EN.svg'"
                                    alt="flag" class="w-5 h-5 object-cover rounded-full" />
                            </button>
                            <template #content="{ close }">
                                <ul
                                    class="!px-2 text-dark dark:text-white-dark grid grid-cols-2 gap-2 font-semibold dark:text-white-light/90 w-[280px]">
                                    <template v-for="(language, index) in languages" :key="index">
                                        <li>
                                            <TextLink
                                                :href="route('dashboard')"
                                                type="button" class="w-full hover:text-primary"
                                                :class="{ 'bg-primary/10 text-primary': (language as { code: string }).code === locale }"
                                                @click="close()">
                                                <img class="w-5 h-5 object-cover rounded-full"
                                                    :src="'/storage/' + (language as { image: string }).image" alt="" />
                                                <span class="ltr:ml-3 rtl:mr-3">{{ (language as { name: string }).name
                                                }}</span>
                                            </TextLink>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </Popper>
                    </div>
                    <div class="dropdown shrink-0">
                        <Popper :placement="store.rtlClass === 'rtl' ? 'bottom-end' : 'bottom-start'" offsetDistance="8"
                            class="!block">
                            <button type="button" class="relative group block">
                                <img class="w-9 h-9 rounded-full object-cover saturate-50 group-hover:saturate-100"
                                    :src="user?.user_detail?.image ? '/storage/' + user.user_detail?.image : '/assets/images/logo.svg'"
                                    :alt="user?.user_detail?.fullname || ''" />
                            </button>
                            <template #content="{ close }">
                                <ul
                                    class="text-dark dark:text-white-dark !py-0 w-[230px] font-semibold dark:text-white-light/90">
                                    <li>
                                        <div class="flex items-center px-4 py-4">
                                            <div class="flex-none">
                                                <img class="rounded-md w-10 h-10 object-cover"
                                                    :src="user?.user_detail?.image ? '/storage/' + user.user_detail?.image : '/assets/images/logo.svg'"
                                                    :alt="user?.user_detail?.fullname || ''" />
                                            </div>
                                            <div class="ltr:pl-4 rtl:pr-4 truncate">
                                                <h4 class="text-base">
                                                    {{ user?.user_detail?.fullname || 'Admin' }}
                                                </h4>
                                                <a class="text-black/60 hover:text-primary dark:text-dark-light/60 dark:hover:text-white"
                                                    href="javascript:;">{{ user?.email || 'admin@example.com' }}</a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <TextLink :href="route('dashboard')" class="dark:hover:text-white"
                                            @click="close()">
                                            <icon-user class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 shrink-0" />

                                            Profile
                                        </TextLink>
                                    </li>
                                    <li class="border-t border-white-light dark:border-white-light/10">
                                        <TextLink method="post" as="button" href="/logout" @click="handleLogout"
                                            class="text-danger !py-3 w-full text-left">
                                            <icon-logout class="w-4.5 h-4.5 ltr:mr-2 rtl:ml-2 rotate-90 shrink-0" />
                                            Sign Out
                                        </TextLink>
                                    </li>
                                </ul>
                            </template>
                        </Popper>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
