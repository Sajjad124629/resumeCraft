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
import { Head, useForm } from '@inertiajs/vue3';
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue';


defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// persistent layout
defineOptions({
    layout:AuthLayout
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Log in" />
    <form @submit.prevent="submit" class="space-y-5 dark:text-white">
        <div>
            <Label>{{ __('Email') }}</Label>
            <InputWithIcon v-model.trim="form.email" type="email" :placeholder="__('Enter Email') " autocomplete="email"
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
            class="btn btn-gradient !mt-6 w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]">
            <IconLoader v-if="form.processing"
                class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
           {{ __('Sign In') }}
        </Button>
    </form>
    <div class="text-center dark:text-white mt-5">
       {{ __("Don't have an account ?") }}
        <TextLink :href="route('register')"
            class="underline uppercase transition text-primary hover:text-black dark:hover:text-white">{{ __('Sign Up') }}
        </TextLink>
    </div>
</template>
