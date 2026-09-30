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
import { route } from '@/route';
const form = useForm({
    name: '',
    email: '',
    role: 'ROLE_CANDIDATE',
    password: '',
    password_confirmation: '',
    terms: false,
});
// persistent layout
defineOptions({
    layout: AuthLayout
});
const submit = () => {
    form.post(route('app_register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>
<template>

    <Head title="Register" />
    <form @submit.prevent="submit" class="space-y-5 dark:text-white">
        <div>
            <Label :isRequired="true">{{ __('I want to register as') }}</Label>
            <div class="grid grid-cols-2 gap-3 mt-1.5">
                <button
                    type="button"
                    @click="form.role = 'ROLE_CANDIDATE'"
                    class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border text-sm font-semibold transition-all duration-200 cursor-pointer"
                    :class="form.role === 'ROLE_CANDIDATE' ? 'border-[#e1147b] bg-[#e1147b]/10 text-[#e1147b] shadow-sm font-bold ring-1 ring-[#e1147b]' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400 text-gray-600 dark:text-gray-300 bg-gray-50/50 dark:bg-gray-800/50'"
                >
                    <span class="text-base">👨‍💼</span>
                    <span>{{ __('Candidate') }}</span>
                </button>
                <button
                    type="button"
                    @click="form.role = 'ROLE_RECRUITER'"
                    class="flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl border text-sm font-semibold transition-all duration-200 cursor-pointer"
                    :class="form.role === 'ROLE_RECRUITER' ? 'border-[#7c3aed] bg-[#7c3aed]/10 text-[#7c3aed] shadow-sm font-bold ring-1 ring-[#7c3aed]' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400 text-gray-600 dark:text-gray-300 bg-gray-50/50 dark:bg-gray-800/50'"
                >
                    <span class="text-base">🏢</span>
                    <span>{{ __('Recruiter') }}</span>
                </button>
            </div>
        </div>

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
            class="btn !mt-6 w-full border-0 uppercase font-bold text-white bg-gradient-to-r from-[#e1147b] via-[#9c27b0] to-[#601bf9] hover:opacity-95 shadow-[0_10px_20px_-10px_rgba(225,20,123,0.5)] py-3 rounded-lg transition duration-200">
            <IconLoader v-if="form.processing"
                class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
            {{ __('SIGN UP') }}
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
    <div class="text-center dark:text-white mt-6 text-sm space-y-2">
        <div>
            {{ __("Already have an account?") }}
            <TextLink :href="route('app_login')"
                class="font-bold uppercase transition text-[#7c3aed] hover:text-[#e1147b] dark:text-[#a855f7] ml-1">{{
                    __('SIGN IN') }}
            </TextLink>
        </div>
        <div>
            {{ __("Forgot your password?") }}
            <TextLink :href="route('password.request')"
                class="font-bold uppercase transition text-[#7c3aed] hover:text-[#e1147b] dark:text-[#a855f7] ml-1">{{
                    __('RESET IT HERE') }}
            </TextLink>
        </div>
    </div>
</template>
