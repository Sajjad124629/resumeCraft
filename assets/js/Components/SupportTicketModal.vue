<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, inject } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { route } from '@/route';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    PencilEdit02Icon,
    CheckmarkCircle01Icon,
    AlertCircleIcon,
    Clock01Icon,
    Download01Icon,
    ArrowUpRight01Icon
} from '@hugeicons/core-free-icons';
import IconHelpCircle from '@/Components/icon/icon-help-circle.vue';
import IconDownload from '@/Components/icon/icon-download.vue';
import IconSend from '@/Components/icon/icon-send.vue';
import IconCopy from '@/Components/icon/icon-copy.vue';
import IconCircleCheck from '@/Components/icon/icon-circle-check.vue';
import IconInfoCircle from '@/Components/icon/icon-info-circle.vue';
import Swal from 'sweetalert2';

const page = usePage();
const __ = inject<any>('__', (key: string) => key);

const props = defineProps<{
    modelValue: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
    (e: 'ticket-created', result: any): void;
}>();

const isOpen = computed({
    get: () => props.modelValue,
    set: (val: boolean) => emit('update:modelValue', val),
});

// Form state
const summary = ref('');
const priority = ref<'High' | 'Average' | 'Low'>('Average');
const selectedProvider = ref<'dropbox'>('dropbox');
const positionTitle = ref('N/A');
const customPosition = ref('');
const currentUrl = ref('');
const adminEmails = ref<string[]>(['sajjadhossainridoy37@gmail.com']);
const availablePositions = ref<{ id: number; title: string }[]>([]);

// Reporter identity state
const guestName = ref('Guest User');
const guestEmail = ref('');
const isLoggedIn = ref(false);
const loggedInUser = ref<{
    id: number;
    email: string;
    fullName: string;
    roleName: string;
} | null>(null);

const isSubmitting = ref(false);
const isDownloading = ref(false);
const showJsonPreview = ref(false);
const uploadSuccess = ref<any>(null);
const errorMessage = ref('');

const hasDropboxToken = ref(true);
const customToken = ref('');
const showTokenInput = ref(false);
const dropboxAppKey = ref('');
const authCode = ref('');
const isExchangingCode = ref(false);
const showOAuthConnect = ref(false);

// Computed reporter info
const reporterDisplayName = computed(() => {
    if (isLoggedIn.value && loggedInUser.value) {
        return loggedInUser.value.fullName || loggedInUser.value.email;
    }
    return guestName.value.trim() || 'Guest User';
});

const reporterDisplayEmail = computed(() => {
    if (isLoggedIn.value && loggedInUser.value) {
        return loggedInUser.value.email;
    }
    return guestEmail.value.trim() || 'guest@resumecraft.com';
});

const reporterDisplayRole = computed(() => {
    if (isLoggedIn.value && loggedInUser.value) {
        return loggedInUser.value.roleName || 'User';
    }
    return 'Guest';
});

// "Reported by" formatted with Role and Email for Power Automate email body
const reportedByFormatted = computed(() => {
    return `${reporterDisplayName.value} (${reporterDisplayRole.value}) - ${reporterDisplayEmail.value}`;
});

// Auto detect position from page props if on a position page
function detectPositionFromContext() {
    const pagePosition = (page.props as any)?.position;
    if (pagePosition && pagePosition.title) {
        positionTitle.value = pagePosition.title;
        return;
    }

    if (typeof window !== 'undefined') {
        const path = window.location.pathname;
        const match = path.match(/\/positions?\/(\d+)/i);
        if (match && availablePositions.value.length > 0) {
            const found = availablePositions.value.find((p) => p.id === parseInt(match[1]));
            if (found) {
                positionTitle.value = found.title;
                return;
            }
        }
    }

    positionTitle.value = 'N/A';
}

// Fetch context from backend
async function fetchContext() {
    // Check Inertia auth user
    const auth = (page.props.auth as any)?.user;
    if (auth) {
        isLoggedIn.value = true;
        loggedInUser.value = {
            id: auth.id,
            email: auth.email,
            fullName: auth.fullName || auth.name || auth.email,
            roleName: auth.roleName || (auth.roles?.includes('ROLE_ADMIN') ? 'Administrator' : (auth.roles?.includes('ROLE_RECRUITER') ? 'Recruiter' : 'Candidate')),
        };
    }

    try {
        const res = await fetch('/api/support-ticket/context');
        if (res.ok) {
            const data = await res.json();
            if (data.adminEmails && data.adminEmails.length > 0) {
                adminEmails.value = data.adminEmails;
            }
            if (data.positions && data.positions.length > 0) {
                availablePositions.value = data.positions;
            }
            if (data.hasDropboxToken !== undefined) {
                hasDropboxToken.value = !!data.hasDropboxToken;
            }
            if (data.dropboxAppKey) {
                dropboxAppKey.value = data.dropboxAppKey;
            }
            if (data.user) {
                isLoggedIn.value = true;
                loggedInUser.value = data.user;
            } else if (!auth) {
                isLoggedIn.value = false;
                loggedInUser.value = null;
            }
        }
    } catch (e) {
        // Fallback
    }

    detectPositionFromContext();
}

// Generated JSON object preview
const generatedJson = computed(() => {
    const activePosition = customPosition.value.trim()
        ? customPosition.value.trim()
        : (positionTitle.value !== 'N/A' ? positionTitle.value : 'N/A');

    return {
        'Ticket ID': 'TICK-PREVIEW',
        'Summary': summary.value.trim() || 'Summary of user inquiry/issue',
        'Priority': priority.value,
        'Reported by': reportedByFormatted.value,
        'Reporter Name': reporterDisplayName.value,
        'Reporter Role': reporterDisplayRole.value,
        'Reporter Email': reporterDisplayEmail.value,
        'Position': activePosition,
        'Link': currentUrl.value || (typeof window !== 'undefined' ? window.location.href : 'http://localhost'),
        'Admins': adminEmails.value,
        'Admins\' e-mail addresses to use': adminEmails.value.join(', '),
        'Application': 'ResumeCraft',
        'Created at': new Date().toISOString(),
    };
});

function copyJsonToClipboard() {
    navigator.clipboard.writeText(JSON.stringify(generatedJson.value, null, 2));
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: __('JSON copied to clipboard!'),
        showConfirmButton: false,
        timer: 2000,
    });
}

// Reset form when modal opens
watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            errorMessage.value = '';
            uploadSuccess.value = null;
            showJsonPreview.value = false;
            summary.value = '';
            priority.value = 'Average';
            customPosition.value = '';
            if (typeof window !== 'undefined') {
                currentUrl.value = window.location.href;
            }
            fetchContext();
        }
    },
    { immediate: true }
);

function close() {
    isSubmitting.value = false;
    isDownloading.value = false;
    isOpen.value = false;
    emit('update:modelValue', false);
    if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('close-support-ticket'));
    }
}

// Submit ticket & upload to Dropbox
async function submitTicket() {
    if (!summary.value.trim()) {
        errorMessage.value = __('Please provide a summary describing your issue or question.');
        return;
    }

    if (!isLoggedIn.value && guestEmail.value.trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(guestEmail.value.trim())) {
            errorMessage.value = __('Please enter a valid email address.');
            return;
        }
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    const activePosition = customPosition.value.trim()
        ? customPosition.value.trim()
        : (positionTitle.value !== 'N/A' ? positionTitle.value : null);

    const payload = {
        summary: summary.value.trim(),
        priority: priority.value,
        position: activePosition,
        link: currentUrl.value,
        provider: 'dropbox',
        reporter_name: reporterDisplayName.value,
        reporter_email: reporterDisplayEmail.value,
        token: customToken.value.trim() || undefined,
    };

    try {
        const response = await fetch('/api/support-ticket/create', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Immediately close the ticket modal
            close();

            uploadSuccess.value = data;
            emit('ticket-created', data);

            // Notify pages like MyTickets to refresh table data
            if (typeof window !== 'undefined') {
                window.dispatchEvent(new CustomEvent('support-ticket-created', { detail: data }));
            }

            const isSim = data.isSimulated;
            Swal.fire({
                icon: isSim ? 'info' : 'success',
                title: isSim ? __('Ticket Generated (Local Mode)') : __('Support Ticket Uploaded!'),
                html: isSim
                    ? `
                    <p class="text-sm text-gray-600 mb-2">
                        JSON file <b>${data.fileName}</b> generated and saved to <code>var/support_tickets/</code>.
                    </p>
                    <p class="text-xs text-amber-700 dark:text-amber-300 font-semibold mb-2">
                        ⚠️ Dropbox token not active in .env
                    </p>
                    <p class="text-xs text-gray-500">
                        To test Power Automate: Click <b>"Download JSON Copy"</b> below and drag & drop the file into your <b>Dropbox /SupportTickets</b> folder!
                    </p>
                `
                    : `
                    <p class="text-sm text-gray-600 mb-2">
                        JSON file <b>${data.fileName}</b> uploaded via Dropbox API to <b>/SupportTickets</b>.
                    </p>
                    <p class="text-xs text-primary font-medium">
                        Power Automate flow will now trigger, read JSON, and notify admins via Email & Teams!
                    </p>
                `,
                confirmButtonText: __('OK'),
                showCancelButton: false,
                customClass: {
                    popup: 'rounded-2xl',
                    confirmButton: 'btn btn-primary px-5 py-2 rounded-xl text-sm font-semibold',
                },
            }).then(() => {
                close();
            });
        } else {
            errorMessage.value = data.error || data.message || __('Failed to submit support ticket.');
        }
    } catch (err: any) {
        errorMessage.value = err.message || __('Network error occurred while submitting ticket.');
    } finally {
        isSubmitting.value = false;
    }
}

// Download JSON directly to user's computer
async function downloadDirectJson() {
    if (!summary.value.trim()) {
        summary.value = 'Support inquiry from ' + (typeof window !== 'undefined' ? window.location.pathname : 'application');
    }

    isDownloading.value = true;
    const activePosition = customPosition.value.trim()
        ? customPosition.value.trim()
        : (positionTitle.value !== 'N/A' ? positionTitle.value : null);

    const payload = {
        summary: summary.value.trim(),
        priority: priority.value,
        position: activePosition,
        link: currentUrl.value,
        reporter_name: reporterDisplayName.value,
        reporter_email: reporterDisplayEmail.value,
    };

    try {
        const response = await fetch('/api/support-ticket/download-json', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `ticket_${Date.now()}.json`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);

            // Close modal upon downloading JSON
            close();

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: __('JSON file downloaded!'),
                showConfirmButton: false,
                timer: 2500,
            });
        }
    } catch (e: any) {
        errorMessage.value = 'Failed to download JSON file: ' + e.message;
    } finally {
        isDownloading.value = false;
    }
}

// Global window event listener to open modal from anywhere
function handleGlobalOpen(event?: any) {
    if (event?.detail) {
        if (event.detail.position) {
            positionTitle.value = event.detail.position;
        }
        if (event.detail.summary) {
            summary.value = event.detail.summary;
        }
    }
    isOpen.value = true;
}

function openDropboxAuth() {
    const key = dropboxAppKey.value || '5ktai8sczd822zh';
    const url = `https://www.dropbox.com/oauth2/authorize?client_id=${encodeURIComponent(key)}&response_type=code&token_access_type=offline`;
    window.open(url, '_blank');
}

async function connectDropboxWithCode() {
    if (!authCode.value.trim()) return;
    isExchangingCode.value = true;
    try {
        const res = await fetch('/api/support-ticket/dropbox-exchange-code', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: authCode.value.trim() }),
        });
        const data = await res.json();
        if (data.success) {
            hasDropboxToken.value = true;
            authCode.value = '';
            showOAuthConnect.value = false;
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: __('Dropbox connected successfully! Refresh token saved.'),
                showConfirmButton: false,
                timer: 3000,
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: __('Connection Failed'),
                text: data.error || __('Could not exchange authorization code.'),
            });
        }
    } catch (e: any) {
        Swal.fire({
            icon: 'error',
            title: __('Error'),
            text: e.message,
        });
    } finally {
        isExchangingCode.value = false;
    }
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape' && isOpen.value) {
        close();
    }
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('open-support-ticket', handleGlobalOpen);
        window.addEventListener('close-support-ticket', close);
        window.addEventListener('keydown', handleKeydown);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('open-support-ticket', handleGlobalOpen);
        window.removeEventListener('close-support-ticket', close);
        window.removeEventListener('keydown', handleKeydown);
    }
});
</script>

<template>
    <Teleport to="body">
        <div v-if="isOpen" class="fixed inset-0 bg-black/60 z-[999] flex items-center justify-center p-4" @click.self="close">
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto border border-gray-100 dark:border-gray-700/80 animate__animated animate__fadeInDown animate__faster"
            >
                <!-- Modal Header -->
                <div class="flex justify-between items-start border-b pb-3 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-indigo-500/10 to-blue-600/20 border border-primary/30 flex items-center justify-center text-primary shadow-xs shrink-0">
                            <IconHelpCircle class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h4 class="text-lg font-bold text-gray-900 dark:text-white tracking-tight">
                                    {{ __('Help & Support Desk') }}
                                </h4>
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                    Power Automate Flow
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ __('Create support inquiries, track status, and trigger automated Dropbox notifications.') }}
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

                <!-- User identity -->
                <div v-if="isLoggedIn && loggedInUser" class="flex items-center border-b border-gray-100 dark:border-gray-700/80 pb-2 text-[11px] text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>{{ __('Reporting as') }}: <strong class="text-gray-800 dark:text-gray-200">{{ loggedInUser.fullName }}</strong> ({{ loggedInUser.roleName }})</span>
                    </div>
                </div>

                <!-- CREATE TICKET FORM -->
                <div class="space-y-4">
                    <!-- Error Alert -->
                    <div
                        v-if="errorMessage"
                        class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 rounded-xl text-red-700 dark:text-red-300 text-xs flex items-center gap-2"
                    >
                        <IconInfoCircle class="w-4 h-4 shrink-0 text-red-500" />
                        <span>{{ errorMessage }}</span>
                    </div>

                    <!-- Success Banner after upload -->
                    <div
                        v-if="uploadSuccess"
                        class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 rounded-xl space-y-2 text-xs"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 font-bold text-emerald-800 dark:text-emerald-300 text-sm">
                                <IconCircleCheck class="w-5 h-5 text-emerald-600" />
                                <span>{{ __('Ticket Uploaded Successfully!') }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-gray-600 dark:text-gray-300 mt-2">
                            <div><strong class="text-gray-800 dark:text-white">Ticket ID:</strong> {{ uploadSuccess.ticketId }}</div>
                            <div><strong class="text-gray-800 dark:text-white">Storage:</strong> Dropbox API</div>
                            <div class="col-span-2 truncate"><strong class="text-gray-800 dark:text-white">File:</strong> {{ uploadSuccess.fileName }}</div>
                            <div class="col-span-2 truncate text-primary font-mono text-[11px]"><strong class="text-gray-800 dark:text-white">Dropbox Path:</strong> {{ uploadSuccess.cloudPath }}</div>
                        </div>

                        <div
                            v-if="uploadSuccess.isSimulated"
                            class="p-2.5 bg-amber-100/70 dark:bg-amber-900/30 border border-amber-300 dark:border-amber-700/50 rounded-lg text-amber-900 dark:text-amber-200 text-[11px] space-y-1"
                        >
                            <strong>💡 Note for Power Automate Video Demonstration:</strong>
                            <p>
                                JSON file saved locally in var/support_tickets/. Click <b>"Download JSON Copy"</b> below and drag & drop the file into your <b>Dropbox /SupportTickets</b> folder!
                            </p>
                        </div>

                        <div class="flex items-center justify-between pt-2 border-t border-emerald-200 dark:border-emerald-800/40 text-[11px] text-emerald-700 dark:text-emerald-300">
                            <span>⚡ Power Automate will process this file and generate admin email notifications.</span>
                            <a
                                :href="uploadSuccess.downloadUrl"
                                download
                                class="btn btn-outline-primary btn-xs py-1 px-2.5 rounded-lg font-bold"
                            >
                                {{ __('Download JSON Copy') }}
                            </a>
                        </div>
                    </div>

                    <form @submit.prevent="submitTicket" class="space-y-4">
                        <!-- Reporter Identity Card -->
                        <div class="p-3.5 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-100 dark:border-gray-700/60 space-y-2.5">
                            <div class="text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <IconInfoCircle class="w-3.5 h-3.5 text-primary" />
                                    <span>{{ __('Reporter Identity & Role') }}</span>
                                </span>
                                <span v-if="isLoggedIn" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 text-[10px] font-bold">
                                    Logged In: {{ loggedInUser?.roleName }}
                                </span>
                                <span v-else class="px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 text-[10px] font-bold">
                                    Guest User
                                </span>
                            </div>

                            <!-- If Logged In: Automatically Populated -->
                            <div v-if="isLoggedIn && loggedInUser" class="space-y-1 text-xs">
                                <div class="flex items-center justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                                    <span class="text-gray-500 dark:text-gray-400">Reported by:</span>
                                    <span class="font-bold text-gray-900 dark:text-white">{{ loggedInUser.fullName }} ({{ loggedInUser.roleName }})</span>
                                </div>
                                <div class="flex items-center justify-between py-1 border-b border-gray-100 dark:border-gray-700/40">
                                    <span class="text-gray-500 dark:text-gray-400">Your Email:</span>
                                    <span class="font-mono text-gray-700 dark:text-gray-300 font-semibold">{{ loggedInUser.email }}</span>
                                </div>
                            </div>

                            <!-- If Guest (Not Logged In): Input Name & Email -->
                            <div v-else class="space-y-2">
                                <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/30 text-amber-800 dark:text-amber-300 text-[11px] flex items-center justify-between">
                                    <span>💡 You are not logged in. Sign in to track your ticket status later!</span>
                                    <a href="/login" class="text-primary font-bold underline shrink-0">Sign In</a>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('Your Name') }}
                                        </label>
                                        <input
                                            type="text"
                                            v-model="guestName"
                                            placeholder="Enter your name"
                                            class="form-input text-xs py-1.5 px-2.5 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('Your Email Address') }} <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            v-model="guestEmail"
                                            required
                                            placeholder="e.g. user@example.com"
                                            class="form-input text-xs py-1.5 px-2.5 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Field Preview in JSON -->
                            <div class="pt-1 text-[11px] flex items-center justify-between text-gray-500 dark:text-gray-400">
                                <span>Field in JSON:</span>
                                <span class="font-mono text-[11px] text-primary font-bold truncate max-w-[360px]" :title="reportedByFormatted">
                                    "Reported by": "{{ reportedByFormatted }}"
                                </span>
                            </div>
                        </div>

                        <!-- Ticket Summary (Mandatory) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1">
                                {{ __('Summary / Issue Description') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="summary"
                                rows="3"
                                required
                                class="form-textarea w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 focus:bg-white dark:focus:bg-gray-900 focus:ring-2 focus:ring-primary/20 focus:border-primary transition resize-none placeholder:text-gray-400"
                                :placeholder="__('Briefly describe your question, issue, or feedback...')"
                            ></textarea>
                        </div>

                        <!-- Priority Selector (High, Average, Low) -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider mb-1.5">
                                {{ __('Priority') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <label
                                    class="flex items-center justify-center gap-2 p-2.5 rounded-xl border cursor-pointer transition select-none text-xs font-semibold"
                                    :class="priority === 'High' 
                                        ? 'bg-red-50 border-red-400 text-red-700 dark:bg-red-950/40 dark:border-red-600 dark:text-red-300 ring-2 ring-red-400/30' 
                                        : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 text-gray-600 dark:text-gray-300'"
                                >
                                    <input type="radio" value="High" v-model="priority" class="sr-only" />
                                    <span class="w-2.5 h-2.5 rounded-full bg-red-500 shrink-0"></span>
                                    <span>{{ __('High') }}</span>
                                </label>

                                <label
                                    class="flex items-center justify-center gap-2 p-2.5 rounded-xl border cursor-pointer transition select-none text-xs font-semibold"
                                    :class="priority === 'Average' 
                                        ? 'bg-amber-50 border-amber-400 text-amber-700 dark:bg-amber-950/40 dark:border-amber-600 dark:text-amber-300 ring-2 ring-amber-400/30' 
                                        : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 text-gray-600 dark:text-gray-300'"
                                >
                                    <input type="radio" value="Average" v-model="priority" class="sr-only" />
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                                    <span>{{ __('Average') }}</span>
                                </label>

                                <label
                                    class="flex items-center justify-center gap-2 p-2.5 rounded-xl border cursor-pointer transition select-none text-xs font-semibold"
                                    :class="priority === 'Low' 
                                        ? 'bg-emerald-50 border-emerald-400 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-600 dark:text-emerald-300 ring-2 ring-emerald-400/30' 
                                        : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 text-gray-600 dark:text-gray-300'"
                                >
                                    <input type="radio" value="Low" v-model="priority" class="sr-only" />
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span>{{ __('Low') }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Context Metadata Card -->
                        <div class="p-3.5 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-100 dark:border-gray-700/60 space-y-2.5">
                            <div class="text-[11px] font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center justify-between">
                                <span class="flex items-center gap-1.5">
                                    <IconInfoCircle class="w-3.5 h-3.5 text-primary" />
                                    {{ __('Ticket Context Information') }}
                                </span>
                                <span class="text-[10px] text-gray-400 font-normal">Included in JSON</span>
                            </div>

                            <!-- Position Context -->
                            <div class="text-xs py-1 border-b border-gray-100 dark:border-gray-700/40">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-gray-500 dark:text-gray-400">Position:</span>
                                    <span class="font-semibold text-primary">
                                        {{ customPosition.trim() || positionTitle }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <select
                                        v-if="availablePositions.length > 0"
                                        v-model="positionTitle"
                                        class="form-select text-[11px] py-1 px-2 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 flex-1"
                                    >
                                        <option value="N/A">N/A (General / Not specific to a position)</option>
                                        <option v-for="pos in availablePositions" :key="pos.id" :value="pos.title">
                                            {{ pos.title }}
                                        </option>
                                    </select>
                                    <input
                                        type="text"
                                        v-model="customPosition"
                                        class="form-input text-[11px] py-1 px-2 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 placeholder:text-gray-400 flex-1"
                                        :placeholder="__('Or custom position title...')"
                                    />
                                </div>
                            </div>

                            <!-- Page Link -->
                            <div class="flex items-center justify-between text-xs py-1 border-b border-gray-100 dark:border-gray-700/40">
                                <span class="text-gray-500 dark:text-gray-400">Invoked from Link:</span>
                                <span class="font-mono text-[11px] text-gray-800 dark:text-gray-300 truncate max-w-[320px]" :title="currentUrl">
                                    {{ currentUrl }}
                                </span>
                            </div>

                            <!-- Admins' e-mail addresses to use -->
                            <div class="text-xs pt-1">
                                <span class="text-gray-500 dark:text-gray-400 block mb-1">Admins' e-mail addresses to notify:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="email in adminEmails"
                                        :key="email"
                                        class="px-2 py-0.5 rounded-md bg-primary/10 text-primary dark:bg-primary/20 text-[11px] font-medium"
                                    >
                                        {{ email }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Destination Cloud Storage (Dropbox Only) -->
                        <div class="p-3 rounded-xl bg-blue-50/60 dark:bg-blue-950/20 border border-blue-200/70 dark:border-blue-800/50 flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                                    ☁
                                </div>
                                <div>
                                    <span class="font-bold text-blue-900 dark:text-blue-200 block">Dropbox API (/SupportTickets)</span>
                                    <span class="text-[11px] text-blue-700/80 dark:text-blue-300/80">Power Automate watches this folder for newly uploaded ticket JSON files</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200">
                                ACTIVE
                            </span>
                        </div>

                        <!-- JSON Preview Toggle -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <button
                                    type="button"
                                    @click="showJsonPreview = !showJsonPreview"
                                    class="text-xs text-primary hover:underline font-semibold flex items-center gap-1"
                                >
                                    <span>{{ showJsonPreview ? __('Hide JSON Payload') : __('Inspect Generated JSON Payload') }}</span>
                                    <span class="text-[10px] font-mono">({{ showJsonPreview ? '▲' : '▼' }})</span>
                                </button>
                                <button
                                    v-if="showJsonPreview"
                                    type="button"
                                    @click="copyJsonToClipboard"
                                    class="text-[11px] text-gray-500 hover:text-primary flex items-center gap-1"
                                >
                                    <IconCopy class="w-3.5 h-3.5" />
                                    <span>Copy JSON</span>
                                </button>
                            </div>

                            <div v-if="showJsonPreview" class="relative">
                                <pre class="bg-gray-900 text-emerald-400 p-3 rounded-xl text-[11px] font-mono overflow-x-auto max-h-48 border border-gray-800">{{ JSON.stringify(generatedJson, null, 2) }}</pre>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center justify-between pt-2 border-t dark:border-gray-700">
                            <!-- Direct JSON Download Button -->
                            <button
                                type="button"
                                @click="downloadDirectJson"
                                :disabled="isDownloading || isSubmitting"
                                class="btn btn-outline-secondary btn-sm px-3.5 py-2 rounded-xl text-xs font-semibold flex items-center gap-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 transition cursor-pointer"
                            >
                                <IconDownload class="w-4 h-4" />
                                <span>{{ isDownloading ? __('Downloading...') : __('Download JSON') }}</span>
                            </button>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="close"
                                    class="btn btn-outline-dark btn-sm px-4 py-2 rounded-xl text-xs font-semibold cursor-pointer"
                                >
                                    {{ __('Cancel') }}
                                </button>

                                <!-- Primary Generate & Upload Button -->
                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="btn btn-primary btn-sm px-5 py-2 rounded-xl text-xs font-bold flex items-center gap-2 shadow-md shadow-primary/20 hover:shadow-lg hover:shadow-primary/30 active:scale-95 transition cursor-pointer"
                                >
                                    <IconSend v-if="!isSubmitting" class="w-4 h-4" />
                                    <span v-if="isSubmitting" class="animate-spin inline-block w-4 h-4 border-2 border-current border-t-transparent rounded-full"></span>
                                    <span>{{ isSubmitting ? __('Uploading to Dropbox...') : __('Generate & Upload to Dropbox') }}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>
