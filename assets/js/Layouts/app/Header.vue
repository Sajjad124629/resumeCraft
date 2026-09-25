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
import { computed, inject } from 'vue';
import { route } from '@/route';
const store = useAppStore();
const page = usePage();
const __ = inject<any>('__', (key: string) => key);
const currentLocale = inject<any>('currentLocale', ref('en'));
const user = computed(() => (page.props.auth as any)?.user || {});
const languages = computed(() => page.props.languages || []);
const locale = computed(() => currentLocale?.value || page.props.locale || 'en');
const dir = computed(() => {
    const d = (page.props as any)?.textDir;
    return (d === 'rtl' || d === 'ltr') ? d : 'ltr';
});
defineProps<{
    settings?: Settings,
}>();
const currentLanguage = computed(() => {
    if (Array.isArray(languages.value)) {
        return languages.value.find((lang: any) => lang.code === locale.value);
    }
    return null;
})

function switchLocale(code: string, closeDropdown?: () => void) {
    if (closeDropdown) closeDropdown();
    router.post(route('app_locale_switch', { code }), {}, {
        preserveScroll: true,
        preserveState: false,
    });
}

// const settings = computed(() => page.props.settings as Settings);
watch(dir, (newDir) => {
    if (newDir === 'rtl' || newDir === 'ltr') {
        store.toggleRTL(newDir);
    }
}, { immediate: true })

const search = ref(false);
const searchQuery = ref(new URLSearchParams(window.location.search).get('q') || '');

function submitSearch() {
    if (searchQuery.value.trim()) {
        router.visit(route('app_search', { q: searchQuery.value.trim() }));
    }
}

const userAvatar = computed(() => {
    const u = user.value;
    const img = u?.photo || u?.avatar || u?.user_detail?.image;
    if (img) {
        if (img.startsWith('http://') || img.startsWith('https://') || img.startsWith('data:')) {
            return img;
        }
        return '/storage/' + img;
    }
    const name = encodeURIComponent(u?.name || u?.user_detail?.fullname || u?.email || 'User');
    return `https://ui-avatars.com/api/?name=${name}&background=4361ee&color=fff&rounded=true&bold=true`;
});

const onAvatarError = (e: Event) => {
    const target = e.target as HTMLImageElement;
    const name = encodeURIComponent(user.value?.name || user.value?.user_detail?.fullname || 'User');
    target.src = `https://ui-avatars.com/api/?name=${name}&background=4361ee&color=fff&rounded=true&bold=true`;
};
</script>
<template>
    <header class="sticky top-0 z-40" :class="{ dark: store.semidark && store.menu === 'horizontal' }">
        <div
            class="backdrop-blur-md bg-white/85 dark:bg-[#0e1726]/85 border-b border-gray-200/60 dark:border-gray-800/80 shadow-[0_4px_20px_-4px_rgba(0,0,0,0.06)] transition-all">
            <div class="relative flex w-full items-center px-5 py-2.5">
                <div class="horizontal-logo flex lg:hidden justify-between items-center ltr:mr-2 rtl:ml-2">
                    <TextLink :href="route('app_dashboard')" class="main-logo flex items-center shrink-0">
                        <img class="w-8 ltr:-ml-1 rtl:-mr-1 inline"
                            :src="settings?.logo ? '/storage/' + settings.logo : '/assets/images/logo.svg'"
                            :alt="settings?.title || 'VR'" />
                        <span
                            class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle hidden md:inline dark:text-white-light transition-all duration-300">{{
                            settings?.title || 'VR' }}</span>
                    </TextLink>

                    <a href="javascript:;"
                        class="collapse-icon flex-none dark:text-[#d0d2d6] hover:text-primary dark:hover:text-primary flex lg:hidden ltr:ml-2 rtl:mr-2 p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition active:scale-95"
                        @click="store.toggleSidebar()">
                        <icon-menu class="w-5 h-5" />
                    </a>
                </div>
                <div
                    class="sm:flex-1 ltr:sm:ml-0 ltr:ml-auto sm:rtl:mr-0 rtl:mr-auto flex items-center space-x-2 lg:space-x-3 rtl:space-x-reverse dark:text-[#d0d2d6]">
                    <div class="sm:ltr:mr-auto sm:rtl:ml-auto">
                        <form
                            class="sm:relative absolute inset-x-0 sm:top-0 top-1/2 sm:translate-y-0 -translate-y-1/2 sm:mx-0 mx-4 z-10 sm:block hidden"
                            :class="{ '!block': search }" @submit.prevent="submitSearch">
                            <div class="relative">
                                <input type="text" v-model="searchQuery"
                                    class="form-input !rounded-full ltr:pl-9 rtl:pr-9 ltr:sm:pr-4 rtl:sm:pl-4 ltr:pr-9 rtl:pl-9 peer bg-gray-100/80 dark:bg-gray-800/60 focus:bg-white dark:focus:bg-[#1a2941] border border-gray-200 dark:border-gray-700 placeholder:text-gray-400 focus:shadow-[0_0_0_3px_rgba(67,97,238,0.25)] transition-all text-xs"
                                    :placeholder="__('Search positions, CVs...')" />
                                <button type="submit"
                                    class="absolute w-9 h-9 inset-0 ltr:right-auto rtl:left-auto appearance-none peer-focus:text-primary transition-colors text-gray-400">
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
                            class="search_btn sm:hidden p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                            @click="search = !search">
                            <icon-search class="w-4.5 h-4.5 mx-auto dark:text-[#d0d2d6]" />
                        </button>
                    </div>
                    <div class="flex items-center">
                        <a href="javascript:;" v-show="store.theme === 'light'"
                            class="flex items-center p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:text-primary hover:bg-gray-200 dark:hover:bg-gray-700 transition shadow-xs active:scale-95"
                            @click="store.toggleTheme('dark')">
                            <icon-sun class="w-4.5 h-4.5 text-amber-500" />
                        </a>
                        <a href="javascript:;" v-show="store.theme === 'dark'"
                            class="flex items-center p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:text-primary hover:bg-gray-200 dark:hover:bg-gray-700 transition shadow-xs active:scale-95"
                            @click="store.toggleTheme('system')">
                            <icon-moon class="w-4.5 h-4.5 text-blue-400" />
                        </a>
                        <a href="javascript:;" v-show="store.theme === 'system'"
                            class="flex items-center p-2 rounded-xl bg-gray-100 dark:bg-gray-800 hover:text-primary hover:bg-gray-200 dark:hover:bg-gray-700 transition shadow-xs active:scale-95"
                            @click="store.toggleTheme('light')">
                            <icon-laptop class="w-4.5 h-4.5 text-purple-400" />
                        </a>
                    </div>
                    <div class="dropdown shrink-0" v-if="(languages as []).length > 0">
                        <Popper :placement="store.rtlClass === 'rtl' ? 'bottom-end' : 'bottom-start'"
                            offsetDistance="8">
                            <button type="button"
                                class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100/80 dark:bg-gray-800/80 hover:border-primary/50 hover:text-primary transition shadow-xs active:scale-95 text-xs font-black uppercase">
                                <span>{{ locale === 'es' ? '🇪🇸 ES' : '🇬🇧 EN' }}</span>
                            </button>
                            <template #content="{ close }">
                                <ul
                                    class="p-2 text-dark dark:text-white-dark flex flex-col gap-1 font-semibold dark:text-white-light/90 w-[150px] bg-white dark:bg-[#1a2941] shadow-lg rounded-lg border border-gray-100 dark:border-gray-700">
                                    <template v-for="(language, index) in languages" :key="index">
                                        <li>
                                            <button type="button"
                                                class="w-full flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-primary transition text-xs"
                                                :class="{ 'bg-primary/10 text-primary font-bold': (language as { code: string }).code === locale }"
                                                @click="switchLocale((language as { code: string }).code, close)">
                                                <span class="flex items-center gap-2">
                                                    <span>{{ (language as any).code === 'es' ? '🇪🇸' : '🇬🇧' }}</span>
                                                    <span>{{ (language as { name: string }).name }}</span>
                                                </span>
                                                <span v-if="(language as any).code === locale"
                                                    class="text-primary text-xs font-bold">✓</span>
                                            </button>
                                        </li>
                                    </template>
                                </ul>
                            </template>
                        </Popper>
                    </div>
                    <!-- User Profile Dropdown or Guest Login/Register -->
                    <div v-if="user && user.email" class="dropdown shrink-0">
                        <Popper :placement="store.rtlClass === 'rtl' ? 'bottom-end' : 'bottom-start'" offsetDistance="8"
                            class="!block">
                            <button type="button"
                                class="relative group block rounded-full focus:outline-none ring-2 ring-primary/20 hover:ring-primary transition">
                                <img class="w-9 h-9 rounded-full object-cover border-2 border-primary/40 shadow-sm"
                                    :src="userAvatar" @error="onAvatarError"
                                    :alt="user?.user_detail?.fullname || user?.name || ''" />
                            </button>
                            <template #content="{ close }">
                                <ul
                                    class="text-dark dark:text-white-dark !py-0 w-[240px] font-semibold dark:text-white-light/90 shadow-lg rounded-lg border border-gray-100 dark:border-gray-700 bg-white dark:bg-[#1a2941]">
                                    <li>
                                        <div
                                            class="flex items-center px-4 py-3.5 border-b border-gray-100 dark:border-gray-700/60">
                                            <div class="flex-none">
                                                <img class="rounded-full w-10 h-10 object-cover border border-gray-200 dark:border-gray-600 shadow-xs"
                                                    :src="userAvatar" @error="onAvatarError"
                                                    :alt="user?.user_detail?.fullname || user?.name || ''" />
                                            </div>
                                            <div class="ltr:pl-3 rtl:pr-3 truncate">
                                                <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                                    {{ user?.user_detail?.fullname || user?.name || 'User' }}
                                                </h4>
                                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{
                                                    user?.email }}</div>
                                                <span v-if="user?.roleName || user?.role"
                                                    class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                                                    :class="{
                                                        'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300': user?.roles?.includes('ROLE_ADMIN'),
                                                        'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300': user?.roles?.includes('ROLE_RECRUITER') && !user?.roles?.includes('ROLE_ADMIN'),
                                                        'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300': user?.roles?.includes('ROLE_CANDIDATE') && !user?.roles?.includes('ROLE_ADMIN'),
                                                    }">
                                                    {{ user?.roleName ? __(user.roleName) :
                                                        (user?.roles?.includes('ROLE_ADMIN') ? __('Admin') :
                                                    (user?.roles?.includes('ROLE_RECRUITER') ? __('Recruiter') :
                                                    __('Candidate'))) }}
                                                </span>
                                            </div>
                                        </div>
                                    </li>
                                    <li v-if="user?.candidateProfileId">
                                        <TextLink :href="route('app_profile_index')"
                                            class="dark:hover:text-white flex items-center px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40"
                                            @click="close()">
                                            <icon-user
                                                class="w-4.5 h-4.5 ltr:mr-2.5 rtl:ml-2.5 shrink-0 text-gray-500" />
                                            {{ __('Profile') }}
                                        </TextLink>
                                    </li>
                                    <li v-if="user?.roles?.includes('ROLE_ADMIN')">
                                        <TextLink :href="route('app_admin_users')"
                                            class="dark:hover:text-white flex items-center px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40"
                                            @click="close()">
                                            <icon-user
                                                class="w-4.5 h-4.5 ltr:mr-2.5 rtl:ml-2.5 shrink-0 text-purple-500" />
                                            {{ __('User Management') }}
                                        </TextLink>
                                    </li>
                                    <li class="border-t border-gray-100 dark:border-gray-700/60">
                                        <TextLink method="post" as="button" :href="route('app_logout')"
                                            class="text-danger flex items-center px-4 py-2.5 w-full text-left text-sm hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                            <icon-logout class="w-4.5 h-4.5 ltr:mr-2.5 rtl:ml-2.5 rotate-90 shrink-0" />
                                            {{ __('Sign Out') }}
                                        </TextLink>
                                    </li>
                                </ul>
                            </template>
                        </Popper>
                    </div>

                    <!-- Guest login buttons if unauthenticated -->
                    <div v-else class="flex items-center gap-2">
                        <TextLink :href="route('app_login')" class="btn btn-outline-primary btn-sm text-xs px-3 py-1.5 rounded-lg">
                            {{ __('Log In') }}
                        </TextLink>
                        <TextLink :href="route('app_register')" class="btn btn-primary btn-sm text-xs px-3 py-1.5 rounded-lg">
                            {{ __('Sign Up') }}
                        </TextLink>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
