<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import { Button } from '@/Components/ui/button';
import Label from '@/Components/ui/label/Label.vue';
import InputWithIcon from '@/Components/ui/inputWithIcon/InputWithIcon.vue';
import IconUser from '@/Components/icon/icon-user.vue';
import IconMail from '@/Components/icon/icon-mail.vue';
import IconLockDots from '@/Components/icon/icon-lock-dots.vue';
import IconLoader from '@/Components/icon/icon-loader.vue';
import IconGoogle from '@/Components/icon/icon-google.vue';
import IconFacebook from '@/Components/icon/icon-facebook.vue';
import Checkbox from '@/Components/ui/checkbox/Checkbox.vue';
import { Head, useForm } from '@inertiajs/vue3';
import AuthLayout from '@/Layouts/AuthLayout.vue';
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});
// persistent layout
defineOptions({
    layout:AuthLayout
});
const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
<template>

    <Head title="Register" />
    <form @submit.prevent="submit" class="space-y-5 dark:text-white">
        <div>
            <Label for="name" :isRequired="true">Name</Label>
            <InputWithIcon v-model.trim="form.name" type="text" placeholder="Full name" autocomplete="name" required>
                <IconUser :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.name" />
        </div>
        <div>
            <Label :isRequired="true">Email</Label>
            <InputWithIcon v-model.trim="form.email" type="email" placeholder="email@example.com" autocomplete="email"
                required>
                <IconMail :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.email" />
        </div>
        <div>
            <Label :isRequired="true">Password</Label>
            <InputWithIcon v-model.trim="form.password" type="password" placeholder="Password" required
                autocomplete="new-password">
                <IconLockDots :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.password" />
        </div>
        <div>
            <Label :isRequired="true">Confirm Password</Label>
            <InputWithIcon v-model.trim="form.password_confirmation" type="password" placeholder="Confirm password"
                required autocomplete="new-password">
                <IconLockDots :fill="true" />
            </InputWithIcon>
            <InputError :message="form.errors.password_confirmation" />
        </div>
        <div>
            <Checkbox v-model="form.terms" required>
                <span class="text-white-dark">I agree to the <TextLink href="#">Terms of Service</TextLink></span>
            </Checkbox>
        </div>
        <Button type="submit" :disabled="form.processing"
            class="btn btn-gradient !mt-6 w-full border-0 uppercase shadow-[0_10px_20px_-10px_rgba(67,97,238,0.44)]">
            <IconLoader v-if="form.processing"
                class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
            Sign UP
        </Button>

        <div class="relative my-7 text-center md:mb-9">
            <span class="absolute inset-x-0 top-1/2 h-px w-full -translate-y-1/2 bg-white-light dark:bg-white-dark"></span>
            <span class="relative bg-white px-2 font-bold uppercase text-white-dark dark:bg-dark dark:text-white-light"><span>OR</span></span>
        </div>

        <div class="mb-10 space-y-4">
            <a href="/connect/google" class="btn btn-outline-dark w-full flex justify-center items-center gap-2 rounded-md border border-gray-300 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <icon-google class="w-5 h-5" />
                <span class="font-semibold">{{ __('Sign up with Google') }}</span>
            </a>

            <a href="/connect/facebook" class="btn btn-outline-dark w-full flex justify-center items-center gap-2 rounded-md border border-gray-300 py-2 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <icon-facebook class="w-5 h-5 text-blue-600" />
                <span class="font-semibold">{{ __('Sign up with Facebook') }}</span>
            </a>
        </div>
    </form>
     <div class="text-center dark:text-white mt-5">
        Already have an account?
        <TextLink href="/login"
            class="underline uppercase transition text-primary hover:text-black dark:hover:text-white">Sign In
        </TextLink>
    </div>
</template>
