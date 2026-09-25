<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import Label from '@/Components/ui/label/Label.vue';
import TextLink from '@/Components/TextLink.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Button from '@/Components/ui/button/Button.vue';
import InputWithIcon from '@/Components/ui/inputWithIcon/InputWithIcon.vue';
import IconMail from '@/Components/icon/icon-mail.vue';
import IconLoader from '@/Components/icon/icon-loader.vue';
import IconLockDots from '@/Components/icon/icon-lock-dots.vue';
import IconGoogle from '@/Components/icon/icon-google.vue';
import IconFacebook from '@/Components/icon/icon-facebook.vue';
import { Head, useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue';
import { route } from '@/route';


defineProps<{
    status?: string;
    canResetPassword: boolean;
    error?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// persistent layout
defineOptions({
    layout: AuthLayout
});

const submit = () => {
    form.post(route('app_login'), {
        forceFormData: true,
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>

    <Head title="Log in" />
    <div v-if="error" class="p-3.5 mb-5 text-sm font-medium text-red-700 bg-red-100 rounded-lg dark:bg-red-900/40 dark:text-red-300 border border-red-200 dark:border-red-800">
        {{ error }}
    </div>
    <form @submit.prevent="submit" class="space-y-5 dark:text-white">
        <div>
            <Label>{{ __('Email') }}</Label>
            <InputWithIcon v-model.trim="form.email" type="email" :placeholder="__('Enter Email')" autocomplete="email"
                required>
                <icon-mail :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.email" />
        </div>
        <div>
            <Label>{{ __('Password') }}</Label>
            <InputWithIcon v-model.trim="form.password" type="password" :placeholder="__('Enter Password')" required>
                <icon-lock-dots :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.password" />
        </div>
        <div>
            <Checkbox v-model="form.remember">
                <span class="text-white-dark">{{ __('Remember me') }}</span>
            </Checkbox>
        </div>
        <Button type="submit" :disabled="form.processing"
            class="btn !mt-6 w-full border-0 uppercase font-bold text-white bg-gradient-to-r from-[#e1147b] via-[#9c27b0] to-[#601bf9] hover:opacity-95 shadow-[0_10px_20px_-10px_rgba(225,20,123,0.5)] py-3 rounded-lg transition duration-200">
            <IconLoader v-if="form.processing"
                class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
            {{ __('SIGN IN') }}
        </Button>

        <div class="relative my-7 text-center">
            <span class="absolute inset-x-0 top-1/2 h-px w-full -translate-y-1/2 bg-gray-200 dark:bg-gray-700"></span>
            <span
                class="relative bg-white dark:bg-[#0e1726] px-3 font-semibold text-xs text-gray-400 dark:text-gray-400 uppercase tracking-widest">OR</span>
        </div>

        <div class="flex items-center justify-center gap-4 my-6">
            <a :href="route('connect_facebook')"
                class="w-11 h-11 rounded-full flex items-center justify-center bg-gradient-to-tr from-[#9c27b0] via-[#e1147b] to-[#601bf9] text-white shadow-md hover:scale-110 hover:shadow-lg transition-all duration-200"
                title="Facebook">
                <icon-facebook class="w-5 h-5 text-white" />
            </a>

            <a :href="route('connect_google')"
                class="w-11 h-11 rounded-full flex items-center justify-center bg-gradient-to-tr from-[#9c27b0] via-[#e1147b] to-[#601bf9] text-white shadow-md hover:scale-110 hover:shadow-lg transition-all duration-200"
                title="Google">
                <icon-google class="w-5 h-5 text-white" />
            </a>
        </div>
    </form>
    <div class="text-center dark:text-white mt-6 text-sm">
        {{ __("Don't have an account ?") }}
        <TextLink :href="route('app_register')"
            class="font-bold uppercase transition text-[#7c3aed] hover:text-[#e1147b] dark:text-[#a855f7] ml-1">{{
                __('SIGN UP') }}
        </TextLink>
    </div>
</template>
