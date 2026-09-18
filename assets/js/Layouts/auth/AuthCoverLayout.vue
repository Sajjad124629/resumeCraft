<script setup lang="ts">

import TextLink from '@/components/TextLink.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
interface Settings {
    logo?: string;
    title?: string;
}
const page = usePage();
const settings = computed(() => page.props.settings as Settings);
</script>

<!-- <script setup>
</script> -->
<template>
    <div>
        <div class="absolute inset-0">
            <img src="/assets/images/auth/bg-gradient.png" alt="image" class="h-full w-full object-cover" />
        </div>
        <div
            class="relative flex min-h-screen items-center justify-center bg-[url(/assets/images/auth/map.png)] bg-cover bg-center bg-no-repeat px-6 py-10 dark:bg-[#060818] sm:px-16">
            <img src="/assets/images/auth/coming-soon-object1.png" alt="image"
                class="absolute left-0 top-1/2 h-full max-h-[893px] -translate-y-1/2" />
            <img src="/assets/images/auth/coming-soon-object2.png" alt="image"
                class="absolute left-24 top-0 h-40 md:left-[30%]" />
            <img src="/assets/images/auth/coming-soon-object3.png" alt="image"
                class="absolute right-0 top-0 h-[300px]" />
            <img src="/assets/images/auth/polygon-object.svg" alt="image" class="absolute bottom-0 end-[28%]" />
            <div
                class="relative flex w-full max-w-[1502px] flex-col justify-between overflow-hidden rounded-md bg-white/60 backdrop-blur-lg dark:bg-black/50 lg:min-h-[758px] lg:flex-row lg:gap-10 xl:gap-0">
                <div
                    class="relative hidden w-full items-center justify-center bg-[linear-gradient(225deg,rgba(239,18,98,1)_0%,rgba(67,97,238,1)_100%)] p-5 lg:inline-flex lg:max-w-[835px] xl:-ms-28 ltr:xl:skew-x-[14deg] rtl:xl:skew-x-[-14deg]">
                    <div
                        class="absolute inset-y-0 w-8 from-primary/10 via-transparent to-transparent ltr:-right-10 ltr:bg-gradient-to-r rtl:-left-10 rtl:bg-gradient-to-l xl:w-16 ltr:xl:-right-20 rtl:xl:-left-20">
                    </div>
                    <div class="ltr:xl:-skew-x-[14deg] rtl:xl:skew-x-[14deg]">
                        <TextLink :href="route('app_dashboard')" class="w-48 block lg:w-72 ms-10 flex items-center">
                            <img :src="settings.logo ? '/storage/' + settings.logo : '/assets/images/logo.svg'"
                                :alt="settings.title" class="w-[100px]" />
                            <h1 class="text-white dark:text-white text-3xl font-extrabold"> {{ settings.title }}</h1>
                        </TextLink>
                        <div class="mt-24 hidden w-full max-w-[430px] lg:block">
                            <img src="/assets/images/auth/login.svg" alt="Cover Image" class="w-full" />
                        </div>
                    </div>
                </div>
                <div
                    class="relative flex w-full flex-col items-center justify-center gap-6 px-4 pb-16 pt-6 sm:px-6 lg:max-w-[667px]">
                    <div
                        class="flex w-full max-w-[440px] items-center gap-2 lg:absolute lg:end-6 lg:top-6 lg:max-w-full">
                        <TextLink :href="route('app_dashboard')" class="w-8 block lg:hidden">
                            <img src="/assets/images/logo.svg" alt="Logo" class="mx-auto w-10" />
                        </TextLink>
                    </div>
                    <div class="w-full max-w-[440px] lg:mt-16">
                        <div class="mb-10" v-if="route().current('login')">
                            <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">Sign in
                            </h1>
                            <p class="text-base font-bold leading-normal text-white-dark">Enter your email and password
                                to login</p>
                        </div>
                        <div class="mb-10" v-else>
                            <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">Sign UP
                            </h1>
                            <p class="text-base font-bold leading-normal text-white-dark">Enter your information to
                                register</p>
                        </div>
                        <slot />
                    </div>
                    <p class="absolute bottom-6 w-full text-center dark:text-white">© {{ new Date().getFullYear()
                    }}.SAJJAD All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </div>
</template>
