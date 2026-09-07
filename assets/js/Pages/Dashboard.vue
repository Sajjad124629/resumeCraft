<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { DashboardSquare03Icon, UserGroup03Icon, MoneyReceive02Icon, Wallet01Icon } from '@hugeicons/core-free-icons';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head :title="__ ? __('Dashboard') : 'Dashboard'" />

    <div class="pt-5">
        <div class="mb-6 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">Dashboard</h5>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
            <div class="panel bg-gradient-to-r from-cyan-500 to-cyan-400 text-white">
                <div class="flex justify-between font-semibold">
                    <div class="text-md">Total Users</div>
                    <HugeiconsIcon :icon="UserGroup03Icon" :size="24" color="currentColor" />
                </div>
                <div class="flex items-center mt-5">
                    <div class="text-3xl font-bold ltr:mr-3 rtl:ml-3"> 1,234 </div>
                </div>
            </div>

            <div class="panel bg-gradient-to-r from-violet-500 to-violet-400 text-white">
                <div class="flex justify-between font-semibold">
                    <div class="text-md">Revenue</div>
                    <HugeiconsIcon :icon="MoneyReceive02Icon" :size="24" color="currentColor" />
                </div>
                <div class="flex items-center mt-5">
                    <div class="text-3xl font-bold ltr:mr-3 rtl:ml-3"> $12,345 </div>
                </div>
            </div>

            <div class="panel bg-gradient-to-r from-blue-500 to-blue-400 text-white">
                <div class="flex justify-between font-semibold">
                    <div class="text-md">Expenses</div>
                    <HugeiconsIcon :icon="Wallet01Icon" :size="24" color="currentColor" />
                </div>
                <div class="flex items-center mt-5">
                    <div class="text-3xl font-bold ltr:mr-3 rtl:ml-3"> $4,567 </div>
                </div>
            </div>

            <div class="panel bg-gradient-to-r from-fuchsia-500 to-fuchsia-400 text-white">
                <div class="flex justify-between font-semibold">
                    <div class="text-md">System Status</div>
                    <HugeiconsIcon :icon="DashboardSquare03Icon" :size="24" color="currentColor" />
                </div>
                <div class="flex items-center mt-5">
                    <div class="text-3xl font-bold ltr:mr-3 rtl:ml-3"> Active </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="mb-5 flex items-center justify-between">
                <h5 class="font-semibold text-lg dark:text-white-light">Welcome to Symfony Inertia Application</h5>
            </div>
            <p class="text-gray-500 dark:text-gray-400">
                Your dashboard application is fully migrated and running on Symfony + Inertia.js + Vue 3!
            </p>
        </div>
    </div>
</template>
