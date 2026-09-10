<script setup lang="ts">
import VueCollapsible from 'vue-height-collapsible/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { BitcoinBagIcon, ChatNotificationIcon, CircleArrowMoveDownLeftIcon, Configuration02Icon, DashboardSquare03Icon, EarthIcon, GroupItemsIcon, MentoringIcon, Note04Icon, NoteIcon, QrCodeIcon, Settings01Icon, TagsIcon, TaxesIcon, UserAccountIcon, UserGroup03Icon, UserMultiple02Icon, UserTime01Icon, WarehouseIcon, Mail01Icon, Wallet01Icon,Wallet02Icon, MoneyReceive02Icon, Coins01Icon, BankIcon, Briefcase08Icon, AiSheetsIcon, Invoice03Icon } from '@hugeicons/core-free-icons';
import TextLink from '@/Components/TextLink.vue';
import IconCaretsDown from '@/Components/icon/icon-carets-down.vue';
import IconCaretDown from '@/Components/icon/icon-caret-down.vue';

import { useAppStore } from '@/Stores/index';
import { ref, watch, computed, inject } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { route as defaultRoute } from '@/route';
import { Settings } from '@/types';
const route = inject<any>('route', defaultRoute);
const __ = inject<any>('__', (key: string) => key);
const store = useAppStore();
const page = usePage();
const currentRoute = ref(page.url || (typeof window !== 'undefined' ? window.location.pathname : ''));
const isRouteActive = (routeString?: string) => {
    if (!routeString || typeof routeString !== 'string') return false;

    const current = currentRoute.value || (typeof window !== 'undefined' ? window.location.pathname : '');
    if (!current || typeof current !== 'string') return false;

    const cleanCurrent = current.split('?')[0].replace(/^\//, '').replace(/\/$/, '');

    // Try matching resolved URL route
    try {
        const resolvedPath = route(routeString);
        if (resolvedPath && typeof resolvedPath === 'string') {
            const cleanResolved = resolvedPath.split('?')[0].replace(/^\//, '').replace(/\/$/, '');
            if (cleanCurrent === cleanResolved) return true;
            if (cleanResolved !== '' && cleanCurrent.startsWith(cleanResolved + '/')) return true;
        }
    } catch (e) {
        // Fallback
    }

    const cleanRoute = routeString.replace(/^\//, '').replace(/\/$/, '');

    if (cleanCurrent === cleanRoute) return true;

    const dotCurrent = cleanCurrent.replace(/\//g, '.');
    return dotCurrent === cleanRoute || dotCurrent.startsWith(cleanRoute + '.');
};
defineProps<{
    settings?: Settings,
}>();

interface MenuChild {
    title: string;
    icon: any;
    route: string;
}

interface MenuItem {
    title: string;
    icon: any;
    route?: string;
    group?: string;
    children?: MenuChild[];
}

const menuItems: MenuItem[] = [
    {
        title: 'Dashboard',
        icon: DashboardSquare03Icon,
        route: 'dashboard',
    },
    // {
    //     title: 'People',
    //     icon: UserGroup03Icon,
    //     group: 'peopleGroup',
    //     children: [],
    // },
    // {
    //     title: 'HRM Management',
    //     icon: UserMultiple02Icon,
    //     group: 'hrGroup',
    //     children: [],
    // },
    // {
    //     title: 'Note',
    //     icon: Note04Icon,
    //     group: 'noteGroup',
    //     children: [],
    // },
    // {
    //     title: 'Income',
    //     icon: MoneyReceive02Icon,
    //     group: 'incomeGroup',
    //     children: [],
    // },
    // {
    //     title: 'Expense',
    //     icon: Wallet01Icon,
    //     group: 'expenseGroup',
    //     children: [],
    // },
    // {
    //     title: 'Accounting',
    //     icon: Briefcase08Icon,
    //     group: 'accountingGroup',
    //     children: [],
    // },
    // {
    //     title: 'Setting',
    //     icon: Settings01Icon,
    //     group: 'dashboardGroup',
    //     children: [],
    // },
];
console.log('Hello');

const activeDropdown = ref('');
watch(
    () => page.url,
    (url) => {
        currentRoute.value = url || (typeof window !== 'undefined' ? window.location.pathname : '');
        const group = menuItems.find(
            (m: any) => m.children?.length && m.children.some((c: any) => isRouteActive(c.route))
        );
        activeDropdown.value = group?.group ?? '';
    },
    { immediate: true }
);

const isActive = computed(() => {
    return (item: any) => {
        if (item.children?.length) {
            return item.children.some((c: any) => isRouteActive(c.route));
        }
        return isRouteActive(item.route);
    };
});

const toggleMobileMenu = () => {
    if (window.innerWidth < 1024) {
        store.toggleSidebar();
    }
};
</script>


<template>
    <div :class="{ 'dark text-white-dark': store.semidark }">
        <nav
            class="sidebar fixed min-h-screen h-full top-0 bottom-0 w-[260px] shadow-[5px_0_25px_0_rgba(94,92,154,0.1)] z-50 transition-all duration-300">
            <div class="bg-white dark:bg-[#0e1726] h-full">
                <div class="flex justify-between items-center px-4 py-3">
                    <TextLink :href="route('dashboard')" class="main-logo flex items-center shrink-0">
                        <img class="w-8 ml-[5px]"
                            :src="settings?.logo ? '/storage/' + settings.logo : '/assets/images/logo.svg'"
                            :alt="settings?.title || 'VR'" />
                        <span
                            class="text-2xl ltr:ml-1.5 rtl:mr-1.5 font-semibold align-middle lg:inline dark:text-white-light">{{
                                settings?.title || 'VR' }}</span>
                    </TextLink>
                    <a href="javascript:;" @click="store.toggleSidebar()"
                        class="collapse-icon w-8 h-8 rounded-full flex items-center hover:bg-gray-500/10 dark:hover:bg-dark-light/10 dark:text-white-light transition duration-300 rtl:rotate-180 hover:text-primary">
                        <IconCaretsDown class="m-auto rotate-90" />
                    </a>
                </div>
                <PerfectScrollbar class="h-[calc(100vh-80px)] relative">
                    <ul class="relative font-semibold space-y-0.5 p-4 py-6">
                        <li v-for="item in menuItems" :key="item.title" class="menu nav-item">
                            <!-- Dropdown -->
                            <template v-if="item.children">
                                <button type="button" class="nav-link group w-full" :class="{ active: isActive(item) }"
                                    @click="activeDropdown === item.group ? activeDropdown = '' : activeDropdown = item.group || ''">
                                    <div class="flex items-center">
                                        <HugeiconsIcon :icon="item.icon" :size="24" color="currentColor"
                                            :stroke-width="1.5" />
                                        <!-- <component :is="item.icon"
                                            class="group-hover:!text-primary shrink-0 w-20 h-20" /> -->
                                        <span
                                            class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark">
                                            {{ __(item.title) }}
                                        </span>
                                    </div>
                                    <div :class="{ 'rtl:rotate-90 -rotate-90': activeDropdown !== item.group }">
                                        <IconCaretDown />
                                    </div>
                                </button>

                                <VueCollapsible :isOpen="activeDropdown === item.group">
                                    <ul class="sub-menu text-gray-500">
                                        <li v-for="child in item.children" :key="child.route">

                                            <TextLink :href="route(child.route)"
                                                class="before:content-none after:content-none flex items-center"
                                                :class="{ active: isRouteActive(child.route) }"
                                                @click="toggleMobileMenu">
                                                <HugeiconsIcon :icon="child.icon" :size="20" color="currentColor"
                                                    :stroke-width="1.5" class="me-2" />
                                                <!-- <component :is="child.icon" width="20" height="20"
                                                    :color="currentRoute.startsWith(child.route) ? '#4361ee' : '#9b9b9b'"
                                                    class="me-2" /> -->
                                                {{ __(child.title) }}
                                            </TextLink>
                                        </li>
                                    </ul>
                                </VueCollapsible>
                            </template>

                            <!-- Single link -->
                            <template v-else>
                                <ul>
                                    <li class="nav-item">
                                        <TextLink :href="route(item.route)" class="group"
                                            :class="{ active: isActive(item), }" @click="toggleMobileMenu">
                                            <div class="flex items-center">
                                                <HugeiconsIcon :icon="item.icon" :size="24" color="currentColor"
                                                    :stroke-width="1.5" />
                                                <!-- <component :is="item.icon" class="group-hover:!text-primary shrink-0" /> -->
                                                <span
                                                    class="ltr:pl-3 rtl:pr-3 text-black dark:text-[#506690] dark:group-hover:text-white-dark ">
                                                    {{ __(item.title) }}
                                                </span>
                                            </div>
                                        </TextLink>
                                    </li>
                                </ul>
                            </template>
                        </li>
                    </ul>
                </PerfectScrollbar>
            </div>
        </nav>
    </div>
</template>
