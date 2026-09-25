<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { DashboardSquare03Icon, UserGroup03Icon, MoneyReceive02Icon, Wallet01Icon } from '@hugeicons/core-free-icons';
import { Link } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});

const props = defineProps<{
    stats: any;
    latestPositions: any[];
    popularPositions: any[];
    tags: any[];
}>();
</script>

<template>

    <Head :title="__ ? __('Dashboard') : 'Dashboard'" />

    <div class="pt-5 space-y-6">
        <div class="flex items-center justify-between">
            <h3
                class="text-2xl font-black tracking-tight text-gray-900 dark:text-white-light flex items-center gap-2.5">
                <span
                    class="inline-block w-2.5 h-7 rounded-full bg-gradient-to-b from-primary to-blue-400 shadow-[0_0_12px_rgba(67,97,238,0.5)]"></span>
                {{ __('Dashboard') }}
            </h3>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <div class="panel bg-gradient-to-r from-cyan-500 to-cyan-400 text-white">
                <div class="flex justify-between font-semibold">
                    <div class="text-md">{{ __('Total Candidates') }}</div>
                    <HugeiconsIcon :icon="UserGroup03Icon" :size="24" color="currentColor" />
                </div>
                <div class="flex items-center mt-5">
                    <div class="text-3xl font-bold ltr:mr-3 rtl:ml-3"> {{ stats.totalCandidates }} </div>
                </div>
            </div>

            <div class="panel bg-gradient-to-r from-violet-500 to-violet-400 text-white">
                <div class="flex justify-between font-semibold">
                    <div class="text-md">{{ __('Total Positions') }}</div>
                    <HugeiconsIcon :icon="DashboardSquare03Icon" :size="24" color="currentColor" />
                </div>
                <div class="flex items-center mt-5">
                    <div class="text-3xl font-bold ltr:mr-3 rtl:ml-3"> {{ stats.totalPositions }} </div>
                </div>
            </div>

            <div class="panel bg-gradient-to-r from-blue-500 to-blue-400 text-white">
                <div class="flex justify-between font-semibold">
                    <div class="text-md">{{ __('Total CVs') }}</div>
                    <HugeiconsIcon :icon="Wallet01Icon" :size="24" color="currentColor" />
                </div>
                <div class="flex items-center mt-5">
                    <div class="text-3xl font-bold ltr:mr-3 rtl:ml-3"> {{ stats.totalCvs }} </div>
                </div>
            </div>

            <div class="panel bg-gradient-to-r from-fuchsia-500 to-fuchsia-400 text-white">
                <div class="flex justify-between font-semibold">
                    <div class="text-md">{{ __('New CVs (24h)') }}</div>
                    <HugeiconsIcon :icon="MoneyReceive02Icon" :size="24" color="currentColor" />
                </div>
                <div class="flex items-center mt-5">
                    <div class="text-3xl font-bold ltr:mr-3 rtl:ml-3"> {{ stats.cvs24h }} </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="panel">
                <div class="mb-5 flex items-center justify-between border-b pb-3">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-blue-500 to-cyan-400 shadow-[0_0_10px_rgba(59,130,246,0.5)]"></span>
                        <h5 class="font-bold text-lg text-gray-900 dark:text-white-light">{{ __('Latest Positions') }}
                        </h5>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="w-full text-left table-auto">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2 font-semibold">{{ __('Title') }}</th>
                                <th class="p-2 font-semibold">{{ __('Company') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pos in latestPositions" :key="pos.id"
                                class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-2">
                                    <Link :href="route('app_position_show', { id: pos.id })" class="text-blue-600 hover:underline">
                                        {{ pos.title }}
                                    </Link>
                                </td>
                                <td class="p-2 text-gray-500">{{ pos.company || '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="mb-5 flex items-center justify-between border-b pb-3">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-purple-500 to-indigo-600 shadow-[0_0_10px_rgba(168,85,247,0.5)]"></span>
                        <h5 class="font-bold text-lg text-gray-900 dark:text-white-light">{{ __('Most Popular Positions') }}</h5>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="w-full text-left table-auto">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2 font-semibold">{{ __('Title') }}</th>
                                <th class="p-2 font-semibold">{{ __('CVs Submitted') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pos in popularPositions" :key="pos.id"
                                class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-2">
                                    <Link :href="route('app_position_show', { id: pos.id })" class="text-blue-600 hover:underline">
                                        {{ pos.title }}
                                    </Link>
                                </td>
                                <td class="p-2 text-gray-500">{{ pos.cvCount }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="mb-5 flex items-center justify-between border-b pb-3">
                <div class="flex items-center gap-2.5">
                    <span
                        class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-emerald-500 to-teal-400 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                    <h5 class="font-bold text-lg text-gray-900 dark:text-white-light">{{ __('Technology Tag Cloud') }}
                    </h5>
                </div>
            </div>
            <div class="flex flex-wrap gap-3 justify-center py-4">
                <Link v-for="tag in tags" :key="tag.name" :href="route('app_search', { q: tag.name })"
                    class="bg-blue-100 hover:bg-blue-200 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/70 px-3 py-1 rounded-full text-center transition cursor-pointer"
                    :style="{ fontSize: `${Math.min(0.8 + (tag.weight * 0.1), 2.5)}rem`, opacity: Math.min(0.5 + (tag.weight * 0.1), 1) }">
                    #{{ tag.name }} ({{ tag.weight }})
                </Link>
                <div v-if="tags.length === 0" class="text-gray-500 w-full text-center">{{ __('No tags available yet. Add projects to your profile!') }}</div>
            </div>
        </div>
    </div>
</template>
