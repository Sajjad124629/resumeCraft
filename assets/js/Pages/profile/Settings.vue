<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, usePage, router } from '@inertiajs/vue3';
import { route } from '@/route';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import CloudImageUpload from '@/Components/CloudImageUpload.vue';
import Swal from 'sweetalert2';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    UserAccountIcon,
    Settings01Icon,
    Briefcase08Icon,
    Tick02Icon,
    AlertCircleIcon
} from '@hugeicons/core-free-icons';
import { ref, reactive, computed, inject } from 'vue';

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
const form = reactive({
    firstName: props.profile.firstName || '',
    lastName: props.profile.lastName || '',
    phone: props.profile.phone || '',
    photo: props.profile.photo || '',
    location: props.profile.location || '',
});

const isSavingProfile = ref(false);
const profileError = ref('');
const profileSuccess = ref('');

// --- Password Form State ---
const passwordForm = reactive({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
});

const isUpdatingPassword = ref(false);
const passwordError = ref('');
const passwordSuccess = ref('');

const showToast = (title: string, icon: 'success' | 'error' | 'warning' | 'info' = 'success') => {
    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3500,
        timerProgressBar: true,
        showCloseButton: true,
        padding: '10px 20px',
    });
    toast.fire({
        icon,
        title,
        padding: '10px 20px',
    });
};

// --- Save Profile Action ---
async function saveProfile() {
    isSavingProfile.value = true;
    profileError.value = '';
    profileSuccess.value = '';

    try {
        const response = await fetch('/profile/settings/update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(form),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            profileSuccess.value = data.message || 'Profile updated successfully!';
            showToast(data.message || 'Profile updated successfully!');

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
                        fullName: data.user?.fullName || fullName,
                        photo: data.user?.photo || form.photo,
                    }
                }));
            }

            // Sync fresh auth and profile prop from backend
            router.reload({ only: ['auth', 'profile'] });
        } else {
            profileError.value = data.error || 'Failed to update profile.';
            showToast(profileError.value, 'error');
        }
    } catch (e: any) {
        profileError.value = e?.message || 'A network error occurred while updating profile.';
        showToast(profileError.value, 'error');
    } finally {
        isSavingProfile.value = false;
    }
}

// --- Update Password Action ---
async function updatePassword() {
    isUpdatingPassword.value = true;
    passwordError.value = '';
    passwordSuccess.value = '';

    if (!passwordForm.newPassword || passwordForm.newPassword.length < 6) {
        passwordError.value = 'New password must be at least 6 characters long.';
        showToast(passwordError.value, 'error');
        isUpdatingPassword.value = false;
        return;
    }

    if (passwordForm.newPassword !== passwordForm.confirmPassword) {
        passwordError.value = 'New password and confirmation do not match.';
        showToast(passwordError.value, 'error');
        isUpdatingPassword.value = false;
        return;
    }

    try {
        const response = await fetch('/profile/change-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                currentPassword: passwordForm.currentPassword,
                newPassword: passwordForm.newPassword,
                confirmPassword: passwordForm.confirmPassword,
            }),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            passwordSuccess.value = data.message || 'Password changed successfully!';
            showToast(data.message || 'Password changed successfully!');
            passwordForm.currentPassword = '';
            passwordForm.newPassword = '';
            passwordForm.confirmPassword = '';
        } else {
            passwordError.value = data.error || 'Failed to change password.';
            showToast(passwordError.value, 'error');
        }
    } catch (e: any) {
        passwordError.value = e?.message || 'A network error occurred while updating password.';
        showToast(passwordError.value, 'error');
    } finally {
        isUpdatingPassword.value = false;
    }
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

                    <!-- Alerts -->
                    <div v-if="profileSuccess"
                        class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-xs font-medium flex items-center gap-2">
                        <HugeiconsIcon :icon="Tick02Icon" :size="16" /> {{ profileSuccess }}
                    </div>
                    <div v-if="profileError"
                        class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-xs font-medium flex items-center gap-2">
                        <HugeiconsIcon :icon="AlertCircleIcon" :size="16" /> {{ profileError }}
                    </div>

                    <form @submit.prevent="saveProfile" class="space-y-4">
                        <!-- Profile Image Upload -->
                        <div class="mb-4">
                            <CloudImageUpload v-model="form.photo" :label="__('Profile Picture / Avatar')" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label :isRequired="true" for="firstName">{{ __('First Name') }}</Label>
                                <Input id="firstName" type="text" v-model.trim="form.firstName"
                                    class="form-input mt-1.5 block w-full rounded-xl" required
                                    :placeholder="__('e.g. John')" />
                            </div>
                            <div>
                                <Label :isRequired="true" for="lastName">{{ __('Last Name') }}</Label>
                                <Input id="lastName" type="text" v-model.trim="form.lastName"
                                    class="form-input mt-1.5 block w-full rounded-xl" required
                                    :placeholder="__('e.g. Doe')" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="phone">{{ __('Phone Number') }}</Label>
                                <Input id="phone" type="text" v-model.trim="form.phone"
                                    class="form-input mt-1.5 block w-full rounded-xl"
                                    :placeholder="__('+1 234 567 8900')" />
                            </div>
                            <div>
                                <Label for="location">{{ __('Location / City') }}</Label>
                                <Input id="location" type="text" v-model.trim="form.location"
                                    class="form-input mt-1.5 block w-full rounded-xl"
                                    :placeholder="__('e.g. New York, USA')" />
                            </div>
                        </div>

                        <div>
                            <Label for="email">{{ __('Email Address') }}</Label>
                            <Input id="email" type="email" :value="user.email" disabled
                                class="form-input mt-1.5 block w-full rounded-xl bg-gray-50 dark:bg-gray-800 text-gray-500 cursor-not-allowed" />
                            <p class="text-[11px] text-gray-400 mt-1">{{ __('Email is linked to your account authentication.') }}</p>
                        </div>

                        <div class="pt-4 flex justify-end">
                            <Button type="submit" variant="default" :disabled="isSavingProfile"
                                class="px-6 rounded-xl font-semibold">
                                <span v-if="isSavingProfile">{{ __('Saving...') }}</span>
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

                    <!-- Alerts -->
                    <div v-if="passwordSuccess"
                        class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-xs font-medium flex items-center gap-2">
                        <HugeiconsIcon :icon="Tick02Icon" :size="16" /> {{ passwordSuccess }}
                    </div>
                    <div v-if="passwordError"
                        class="mb-4 p-3 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-xs font-medium flex items-center gap-2">
                        <HugeiconsIcon :icon="AlertCircleIcon" :size="16" /> {{ passwordError }}
                    </div>

                    <form @submit.prevent="updatePassword" class="space-y-4">
                        <div v-if="user.hasPassword">
                            <Label :isRequired="true" for="currentPassword">{{ __('Current Password') }}</Label>
                            <Input id="currentPassword" type="password" v-model="passwordForm.currentPassword"
                                class="form-input mt-1.5 block w-full rounded-xl" required
                                :placeholder="__('Enter current password')" />
                        </div>

                        <div>
                            <Label :isRequired="true" for="newPassword">{{ __('New Password') }}</Label>
                            <Input id="newPassword" type="password" v-model="passwordForm.newPassword"
                                class="form-input mt-1.5 block w-full rounded-xl" required
                                :placeholder="__('Min. 6 characters')" />
                        </div>

                        <div>
                            <Label :isRequired="true" for="confirmPassword">{{ __('Confirm New Password') }}</Label>
                            <Input id="confirmPassword" type="password" v-model="passwordForm.confirmPassword"
                                class="form-input mt-1.5 block w-full rounded-xl" required
                                :placeholder="__('Re-type new password')" />
                        </div>

                        <div class="pt-4 flex justify-end">
                            <Button type="submit" variant="destructive" :disabled="isUpdatingPassword"
                                class="px-6 rounded-xl font-semibold">
                                <span v-if="isUpdatingPassword">{{ __('Updating...') }}</span>
                                <span v-else>{{ __('Update Password') }}</span>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
