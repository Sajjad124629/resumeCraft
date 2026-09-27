<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import Label from '@/Components/ui/label/Label.vue';
import TextLink from '@/Components/TextLink.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Button from '@/Components/ui/button/Button.vue';
import InputWithIcon from '@/Components/ui/inputWithIcon/InputWithIcon.vue';
import IconMail from '@/Components/icon/icon-mail.vue';
import IconLoader from '@/Components/icon/icon-loader.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/route';

defineProps<{
    status?: string;
    errors?: Record<string, string>;
}>();

const form = useForm({
    email: '',
});

defineOptions({
    layout: AuthLayout
});

const submit = () => {
    form.post(route('password.email'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>

    <Head title="Forgot Password" />

    <div v-if="status"
        class="p-3.5 mb-5 text-sm font-medium text-green-700 bg-green-100 rounded-lg dark:bg-green-900/40 dark:text-green-300 border border-green-200 dark:border-green-800">
        {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-5 dark:text-white">
        <div>
            <Label :isRequired="true">{{ __('Email Address') }}</Label>
            <InputWithIcon v-model.trim="form.email" type="email" :placeholder="__('Enter your account email')"
                autocomplete="email" required autofocus>
                <icon-mail :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.email" />
        </div>

        <Button type="submit" :disabled="form.processing"
            class="btn !mt-6 w-full border-0 uppercase font-bold text-white bg-gradient-to-r from-[#e1147b] via-[#9c27b0] to-[#601bf9] hover:opacity-95 shadow-[0_10px_20px_-10px_rgba(225,20,123,0.5)] py-3 rounded-lg transition duration-200">
            <IconLoader v-if="form.processing"
                class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
            {{ __('EMAIL PASSWORD RESET LINK') }}
        </Button>
    </form>

    <div class="text-center dark:text-white mt-6 text-sm">
        {{ __("Remember your password?") }}
        <TextLink :href="route('app_login')"
            class="font-bold uppercase transition text-[#7c3aed] hover:text-[#e1147b] dark:text-[#a855f7] ml-1">
            {{ __('SIGN IN') }}
        </TextLink>
    </div>
</template>
