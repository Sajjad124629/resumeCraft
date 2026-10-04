<script setup lang="ts">
import { ref, computed, watch, inject } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import StaticSelect from '@/Components/ui/select/StaticSelect.vue';
import Swal from 'sweetalert2';

const page = usePage();
const __ = inject<any>('__', (key: string) => key);

const props = defineProps<{
    modelValue: boolean;
    user?: {
        id?: number;
        email?: string;
        roleName?: string;
        role?: string;
    };
    profile?: {
        firstName?: string;
        lastName?: string;
        phone?: string;
        location?: string;
        email?: string;
        roleName?: string;
    };
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'synced', result: any): void;
}>();

const isOpen = computed({
    get: () => props.modelValue,
    set: (val: boolean) => emit('update:modelValue', val),
});

// Fallback to auth user from Inertia page props
const authUser = computed(() => (page.props.auth as any)?.user || {});

// Non-removable fields (Name, Email, Role)
const nonRemovable = computed(() => {
    const fn = props.profile?.firstName 
        || authUser.value?.firstName 
        || (authUser.value?.name ? authUser.value.name.split(' ')[0] : '') 
        || '';

    const ln = props.profile?.lastName 
        || authUser.value?.lastName 
        || (authUser.value?.name ? authUser.value.name.split(' ').slice(1).join(' ') : '') 
        || '';

    const fullName = `${fn} ${ln}`.trim() 
        || authUser.value?.fullName 
        || authUser.value?.name 
        || 'User';

    const email = props.user?.email 
        || props.profile?.email 
        || authUser.value?.email 
        || '';

    const role = props.user?.roleName 
        || props.user?.role 
        || props.profile?.roleName 
        || authUser.value?.roleName 
        || authUser.value?.role 
        || 'User';

    return {
        fullName,
        firstName: fn,
        lastName: ln,
        email,
        role,
    };
});

// Industry options for StaticSelect
const industryOptions = [
    { id: 'Technology', name: 'Technology' },
    { id: 'Finance', name: 'Finance / Banking' },
    { id: 'Healthcare', name: 'Healthcare' },
    { id: 'Consulting', name: 'Consulting' },
    { id: 'Education', name: 'Education' },
    { id: 'Manufacturing', name: 'Manufacturing' },
    { id: 'Other', name: 'Other' },
];

// Form state for additional CRM fields
const form = ref({
    accountName: '',
    phone: '',
    title: '',
    city: '',
    website: '',
    industry: 'Technology',
    description: '',
});

const isSubmitting = ref(false);
const errorMessage = ref('');

// Populate default values when modal opens
watch(
    () => props.modelValue,
    (val) => {
        if (val) {
            errorMessage.value = '';
            const defaultAccount = nonRemovable.value.fullName !== 'User'
                ? `${nonRemovable.value.fullName}'s Account`
                : 'My Account';

            form.value = {
                accountName: defaultAccount,
                phone: props.profile?.phone || authUser.value?.phone || '',
                title: nonRemovable.value.role !== 'User' ? nonRemovable.value.role : 'Candidate',
                city: props.profile?.location || '',
                website: '',
                industry: 'Technology',
                description: `Synced from ResumeCraft application for user ${nonRemovable.value.email}`,
            };
        }
    },
    { immediate: true }
);

const close = () => {
    if (!isSubmitting.value) {
        isOpen.value = false;
    }
};

const submitSync = async () => {
    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const targetUserId = props.user?.id || authUser.value?.id;
        const payload = {
            userId: targetUserId,
            firstName: nonRemovable.value.firstName,
            lastName: nonRemovable.value.lastName || nonRemovable.value.firstName || 'User',
            email: nonRemovable.value.email,
            accountName: form.value.accountName.trim() || `${nonRemovable.value.fullName}'s Account`,
            phone: form.value.phone.trim(),
            title: form.value.title.trim(),
            city: form.value.city.trim(),
            website: form.value.website.trim(),
            industry: form.value.industry,
            description: form.value.description.trim(),
        };

        const response = await fetch('/api/salesforce/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            isOpen.value = false;
            emit('synced', data);

            Swal.fire({
                icon: 'success',
                title: 'Synced with Salesforce!',
                html: `
                    <div class="text-left text-sm space-y-2 mt-2">
                        <p class="text-gray-700">The user has been successfully created in your Salesforce CRM:</p>
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200 font-mono text-xs text-gray-800">
                            <div><strong>Account ID:</strong> ${data.accountId}</div>
                            <div><strong>Contact ID:</strong> ${data.contactId}</div>
                        </div>
                    </div>
                `,
                confirmButtonColor: '#4361ee',
                confirmButtonText: 'Great!',
            });
        } else {
            errorMessage.value = data.error || 'Failed to sync with Salesforce. Please check credentials or network.';
        }
    } catch (err: any) {
        errorMessage.value = err.message || 'An unexpected error occurred while communicating with Salesforce.';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <div v-if="isOpen" class="fixed inset-0 bg-black/60 z-[999] flex items-center justify-center p-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto border border-gray-100 dark:border-gray-700/80"
            >
                <!-- Professional Header with Salesforce Logo -->
                <div class="flex justify-between items-center border-b pb-4 dark:border-gray-700">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-sky-50 to-blue-100 dark:from-sky-950/60 dark:to-blue-900/40 border border-sky-200/80 dark:border-sky-800/60 flex items-center justify-center text-sky-600 dark:text-sky-400 shadow-xs shrink-0">
                            <!-- Salesforce Cloud Logo -->
                            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM19 18H6c-2.21 0-4-1.79-4-4 0-2.05 1.53-3.76 3.56-3.97l1.07-.11.5-.95C8.08 7.14 9.94 6 12 6c2.62 0 4.88 1.86 5.39 4.43l.3 1.5 1.53.11c1.56.1 2.78 1.41 2.78 2.96 0 1.65-1.35 3-3 3z" />
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 tracking-tight">
                                {{ __('Sync to Salesforce CRM') }}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Create an Account with a linked Contact in Salesforce via REST API') }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="close"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-2xl font-light leading-none p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                    >
                        &times;
                    </button>
                </div>

                <!-- Error Banner -->
                <div
                    v-if="errorMessage"
                    class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 rounded-xl text-red-700 dark:text-red-300 text-xs"
                >
                    <strong>Error:</strong> {{ errorMessage }}
                </div>

                <form @submit.prevent="submitSync" class="space-y-4">
                    <!-- Non-removable Fields Section -->
                    <div class="bg-gray-50/80 dark:bg-gray-700/30 p-4 rounded-xl border border-gray-100 dark:border-gray-700/60">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                {{ __('Account Information (Non-removable)') }}
                            </span>
                            <span class="text-[10px] px-2 py-0.5 bg-gray-200/70 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded-md font-medium">
                                {{ __('Read-only') }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs">
                            <div>
                                <Label class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5 block">
                                    {{ __('Name') }}
                                </Label>
                                <input
                                    type="text"
                                    :value="nonRemovable.fullName"
                                    disabled
                                    class="form-input h-10 text-xs w-full bg-gray-100/90 dark:bg-gray-800 text-gray-600 dark:text-gray-300 cursor-not-allowed font-medium rounded-xl border-gray-200 dark:border-gray-700"
                                />
                            </div>
                            <div>
                                <Label class="text-xs font-semibold text-gray-600 dark:text-gray-300 mb-1.5 block">
                                    {{ __('Email') }}
                                </Label>
                                <input
                                    type="text"
                                    :value="nonRemovable.email"
                                    disabled
                                    class="form-input h-10 text-xs w-full bg-gray-100/90 dark:bg-gray-800 text-gray-600 dark:text-gray-300 cursor-not-allowed font-medium rounded-xl border-gray-200 dark:border-gray-700"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Additional CRM Information Section -->
                    <div class="space-y-3.5">
                        <div class="flex items-center gap-2 pt-1">
                            <span class="w-1.5 h-4 bg-sky-500 rounded-full"></span>
                            <span class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">
                                {{ __('Additional CRM Details') }}
                            </span>
                        </div>

                        <!-- Account / Company Name -->
                        <div>
                            <Label for="sf_account_name" :isRequired="true" class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-1.5 block">
                                {{ __('Account / Organization Name') }}
                            </Label>
                            <input
                                id="sf_account_name"
                                type="text"
                                v-model.trim="form.accountName"
                                required
                                :placeholder="__('e.g. Acme Corp or John Doe Account')"
                                class="form-input h-10 text-xs w-full rounded-xl border-gray-200 dark:border-gray-700 focus:border-primary focus:ring-primary/20"
                            />
                            <p class="text-[11px] text-gray-400 mt-1">
                                {{ __('An Account with this name will be created in Salesforce.') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Phone Number -->
                            <div>
                                <Label for="sf_phone" class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-1.5 block">
                                    {{ __('Phone Number') }}
                                </Label>
                                <input
                                    id="sf_phone"
                                    type="text"
                                    v-model.trim="form.phone"
                                    :placeholder="__('+1 234 567 8900')"
                                    class="form-input h-10 text-xs w-full rounded-xl border-gray-200 dark:border-gray-700 focus:border-primary focus:ring-primary/20"
                                />
                            </div>

                            <!-- Job Title -->
                            <div>
                                <Label for="sf_title" class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-1.5 block">
                                    {{ __('Job Title / Role') }}
                                </Label>
                                <input
                                    id="sf_title"
                                    type="text"
                                    v-model.trim="form.title"
                                    :placeholder="__('e.g. Senior Software Engineer')"
                                    class="form-input h-10 text-xs w-full rounded-xl border-gray-200 dark:border-gray-700 focus:border-primary focus:ring-primary/20"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <!-- Industry with StaticSelect -->
                            <div>
                                <Label for="sf_industry" class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-1.5 block">
                                    {{ __('Industry') }}
                                </Label>
                                <StaticSelect
                                    v-model="form.industry"
                                    :options="industryOptions"
                                    label="name"
                                    :reduce="(opt: any) => opt.id"
                                    :placeholder="__('Select Industry')"
                                    class="text-xs"
                                />
                            </div>

                            <!-- City / Location -->
                            <div>
                                <Label for="sf_city" class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-1.5 block">
                                    {{ __('City / Location') }}
                                </Label>
                                <input
                                    id="sf_city"
                                    type="text"
                                    v-model.trim="form.city"
                                    :placeholder="__('e.g. New York, USA')"
                                    class="form-input h-10 text-xs w-full rounded-xl border-gray-200 dark:border-gray-700 focus:border-primary focus:ring-primary/20"
                                />
                            </div>
                        </div>

                        <!-- Website -->
                        <div>
                            <Label for="sf_website" class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-1.5 block">
                                {{ __('Website / LinkedIn (Optional)') }}
                            </Label>
                            <input
                                id="sf_website"
                                type="url"
                                v-model.trim="form.website"
                                :placeholder="__('https://example.com')"
                                class="form-input h-10 text-xs w-full rounded-xl border-gray-200 dark:border-gray-700 focus:border-primary focus:ring-primary/20"
                            />
                        </div>

                        <!-- Description / Notes -->
                        <div>
                            <Label for="sf_description" class="text-xs font-semibold text-gray-700 dark:text-gray-200 mb-1.5 block">
                                {{ __('CRM Notes / Description') }}
                            </Label>
                            <Textarea
                                id="sf_description"
                                v-model.trim="form.description"
                                rows="2"
                                :placeholder="__('Any additional notes for this user in Salesforce CRM...')"
                                class="text-xs w-full rounded-xl border-gray-200 dark:border-gray-700 focus:border-primary focus:ring-primary/20"
                            />
                        </div>
                    </div>

                    <!-- Clean Footer -->
                    <div class="flex justify-end gap-2.5 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <Button
                            type="button"
                            variant="outline"
                            @click="close"
                            :disabled="isSubmitting"
                            class="rounded-xl px-5 h-10 text-xs font-semibold"
                        >
                            <span>{{ __('Cancel') }}</span>
                        </Button>
                        <Button
                            type="submit"
                            :disabled="isSubmitting"
                            class="rounded-xl px-6 h-10 text-xs font-semibold flex items-center gap-1.5 shadow-sm"
                        >
                            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-1.5 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ isSubmitting ? __('Syncing...') : __('Sync to Salesforce') }}</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
