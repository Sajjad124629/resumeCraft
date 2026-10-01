<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { route } from '@/route';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import InputError from '@/Components/InputError.vue';
import CloudImageUpload from '@/Components/CloudImageUpload.vue';
import { inject } from 'vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const __ = inject<any>('__', (key: string) => key);

const props = defineProps<{
    user: {
        id: number;
        email: string;
        role: string;
        roleName: string;
        isVerified: boolean;
        hasPassword?: boolean;
    };
    profile: {
        firstName: string;
        lastName: string;
        phone: string;
        photo: string;
        location: string;
    };
}>();

// --- Profile Form State ---
const form = useForm({
    firstName: props.profile.firstName || '',
    lastName: props.profile.lastName || '',
    phone: props.profile.phone || '',
    photo: props.profile.photo || '',
    location: props.profile.location || '',
});

// --- Password Form State ---
const passwordForm = useForm({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
});

// --- Save Profile Action ---
function saveProfile() {
    form.post(route('app_profile_settings_update'), {
        preserveScroll: true,
        onSuccess: () => {
            const fullName = `${form.firstName || ''} ${form.lastName || ''}`.trim() || 'User';

            // Instantly sync with Inertia auth.user so Header updates reactively
            if (page.props.auth && (page.props.auth as any).user) {
                const u = (page.props.auth as any).user;
                u.photo = form.photo;
                u.avatar = form.photo;
                u.name = fullName;
                u.fullName = fullName;
                if (u.user_detail) {
                    u.user_detail.image = form.photo;
                    u.user_detail.fullname = fullName;
                }
            }

            // Dispatch global event for instant header reactivity
            if (typeof window !== 'undefined') {
                window.dispatchEvent(new CustomEvent('auth-user-updated', {
                    detail: {
                        fullName,
                        photo: form.photo,
                    }
                }));
            }
        },
    });
}

// --- Update Password Action ---
function updatePassword() {
    passwordForm.post(route('app_profile_change_password'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
        },
    });
}
</script>

<template>

    <Head :title="__('Account Settings')" />

    <div class="space-y-6 mt-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Profile Details (7 Cols) -->
            <div class="lg:col-span-7 space-y-6">
                <div class="panel p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div
                        class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-blue-500 to-cyan-400"></span>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Profile Information') }}
                            </h3>
                        </div>
                    </div>

                    <form @submit.prevent="saveProfile" class="space-y-4">
                        <!-- Profile Image Upload -->
                        <div class="mb-4">
                            <CloudImageUpload v-model="form.photo" :label="__('Profile Picture / Avatar')" />
                            <InputError :message="form.errors.photo" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label :isRequired="true" for="firstName">{{ __('First Name') }}</Label>
                                <Input id="firstName" type="text" v-model.trim="form.firstName"
                                    class="form-input mt-1.5 block w-full rounded-xl" required
                                    :placeholder="__('e.g. John')" />
                                <InputError :message="form.errors.firstName" class="mt-1" />
                            </div>
                            <div>
                                <Label :isRequired="true" for="lastName">{{ __('Last Name') }}</Label>
                                <Input id="lastName" type="text" v-model.trim="form.lastName"
                                    class="form-input mt-1.5 block w-full rounded-xl" required
                                    :placeholder="__('e.g. Doe')" />
                                <InputError :message="form.errors.lastName" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="phone">{{ __('Phone Number') }}</Label>
                                <Input id="phone" type="text" v-model.trim="form.phone"
                                    class="form-input mt-1.5 block w-full rounded-xl"
                                    :placeholder="__('+1 234 567 8900')" />
                                <InputError :message="form.errors.phone" class="mt-1" />
                            </div>
                            <div>
                                <Label for="location">{{ __('Location / City') }}</Label>
                                <Input id="location" type="text" v-model.trim="form.location"
                                    class="form-input mt-1.5 block w-full rounded-xl"
                                    :placeholder="__('e.g. New York, USA')" />
                                <InputError :message="form.errors.location" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <Label for="email">{{ __('Email Address') }}</Label>
                            <Input id="email" type="email" :value="user.email" disabled
                                class="form-input mt-1.5 block w-full rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-500 cursor-not-allowed" />
                            <p class="text-[11px] text-gray-400 mt-1">{{ __('Email is linked to your account authentication.') }}</p>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <Button type="submit" variant="default" :disabled="form.processing"
                                class="px-6 rounded-xl font-semibold">
                                <span v-if="form.processing">{{ __('Saving...') }}</span>
                                <span v-else>{{ __('Save Changes') }}</span>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Right Column: Password & Security (5 Cols) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="panel p-6 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div
                        class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-4 mb-6">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-purple-500 to-indigo-500"></span>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Change Password') }}</h3>
                        </div>
                    </div>

                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div v-if="user.hasPassword">
                            <Label :isRequired="true" for="currentPassword">{{ __('Current Password') }}</Label>
                            <Input id="currentPassword" type="password" v-model="passwordForm.currentPassword"
                                class="form-input mt-1.5 block w-full rounded-xl" required
                                :placeholder="__('Enter current password')" />
                            <InputError :message="passwordForm.errors.currentPassword" class="mt-1" />
                        </div>

                        <div>
                            <Label :isRequired="true" for="newPassword">{{ __('New Password') }}</Label>
                            <Input id="newPassword" type="password" v-model="passwordForm.newPassword"
                                class="form-input mt-1.5 block w-full rounded-xl" required
                                :placeholder="__('Min. 6 characters')" />
                            <InputError :message="passwordForm.errors.newPassword" class="mt-1" />
                        </div>

                        <div>
                            <Label :isRequired="true" for="confirmPassword">{{ __('Confirm New Password') }}</Label>
                            <Input id="confirmPassword" type="password" v-model="passwordForm.confirmPassword"
                                class="form-input mt-1.5 block w-full rounded-xl" required
                                :placeholder="__('Re-type new password')" />
                            <InputError :message="passwordForm.errors.confirmPassword" class="mt-1" />
                        </div>

                        <div class="pt-4 flex justify-end">
                            <Button type="submit" variant="destructive" :disabled="passwordForm.processing"
                                class="px-6 rounded-xl font-semibold">
                                <span v-if="passwordForm.processing">{{ __('Updating...') }}</span>
                                <span v-else>{{ __('Update Password') }}</span>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
