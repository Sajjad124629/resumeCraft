<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import Label from '@/Components/ui/label/Label.vue';
import TextLink from '@/Components/TextLink.vue';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import Button from '@/Components/ui/button/Button.vue';
import InputWithIcon from '@/Components/ui/inputWithIcon/InputWithIcon.vue';
import IconMail from '@/Components/icon/icon-mail.vue';
import IconLockDots from '@/Components/icon/icon-lock-dots.vue';
import IconLoader from '@/Components/icon/icon-loader.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/route';

interface Props {
    token: string;
    email: string;
    errors?: Record<string, string>;
}

const props = defineProps<Props>();

defineOptions({
    layout: AuthLayout
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>

    <Head title="Reset Password" />

    <form @submit.prevent="submit" class="space-y-5 dark:text-white">
        <div>
            <Label>{{ __('Email Address') }}</Label>
            <InputWithIcon v-model.trim="form.email" type="email" autocomplete="email" readonly
                class="opacity-80 cursor-not-allowed">
                <icon-mail :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.email" />
        </div>

        <div>
            <Label :isRequired="true">{{ __('New Password') }}</Label>
            <InputWithIcon v-model.trim="form.password" type="password" :placeholder="__('Enter new password')" required
                autocomplete="new-password" autofocus>
                <icon-lock-dots :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.password" />
        </div>

        <div>
            <Label :isRequired="true">{{ __('Confirm Password') }}</Label>
            <InputWithIcon v-model.trim="form.password_confirmation" type="password"
                :placeholder="__('Confirm new password')" required autocomplete="new-password">
                <icon-lock-dots :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.password_confirmation" />
        </div>

        <Button type="submit" :disabled="form.processing"
            class="btn !mt-6 w-full border-0 uppercase font-bold text-white bg-gradient-to-r from-[#e1147b] via-[#9c27b0] to-[#601bf9] hover:opacity-95 shadow-[0_10px_20px_-10px_rgba(225,20,123,0.5)] py-3 rounded-lg transition duration-200">
            <IconLoader v-if="form.processing"
                class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
            {{ __('RESET PASSWORD') }}
        </Button>
    </form>

    <div class="text-center dark:text-white mt-6 text-sm">
        {{ __("Back to") }}
        <TextLink :href="route('app_login')"
            class="font-bold uppercase transition text-[#7c3aed] hover:text-[#e1147b] dark:text-[#a855f7] ml-1">
            {{ __('SIGN IN') }}
        </TextLink>
    </div>
</template>
