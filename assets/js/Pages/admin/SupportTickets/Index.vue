<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Vue3Datatable from '@bhplugin/vue3-datatable';
import { Button } from '@/Components/ui/button';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    ChatNotificationIcon,
    Search01Icon,
    Delete01Icon,
    CheckmarkCircle01Icon,
    AlertCircleIcon,
    Clock01Icon,
    Download01Icon,
    ViewIcon,
    Copy01Icon,
    ArrowUpRight01Icon,
    Cancel01Icon,
    PencilEdit02Icon,
    Briefcase08Icon
} from '@hugeicons/core-free-icons';
import { route } from '@/route';
import { __ } from '@/Composables/trans';
import Swal from 'sweetalert2';

defineOptions({ layout: AppLayout });

interface SupportTicketItem {
    id: number;
    ticketId: string;
    summary: string;
    priority: 'High' | 'Average' | 'Low';
    status: 'open' | 'in_progress' | 'solved';
    reportedBy: string;
    reporterEmail: string | null;
    positionTitle: string | null;
    pageLink: string | null;
    fileName: string | null;
    cloudPath: string | null;
    provider: string;
    isSimulated: boolean;
    adminNotes: string | null;
    createdAt: string;
    solvedAt: string | null;
    downloadUrl: string | null;
}

const props = defineProps<{
    tickets: SupportTicketItem[];
    statusCounts: {
        total: number;
        open: number;
        in_progress: number;
        solved: number;
    };
    totalRows?: number;
    currentPage?: number;
    pageSize?: number;
    search?: string;
    statusFilter?: string;
    priorityFilter?: string;
    sort?: string;
    sortDir?: string;
}>();

const datatableRef = ref<any>(null);
const loading = ref(false);
const currentPage = ref(props.currentPage || 1);
const pageSize = ref(props.pageSize || 10);
const searchQuery = ref(props.search || '');
const currentStatus = ref(props.statusFilter || 'all');
const currentPriority = ref(props.priorityFilter || 'all');
const sortColumn = ref(props.sort || 'createdAt');
const sortDirection = ref(props.sortDir || 'desc');

// Selected Ticket for details modal
const selectedTicket = ref<SupportTicketItem | null>(null);
const showDetailModal = ref(false);
const adminNoteInput = ref('');
const isSavingNote = ref(false);

let searchTimer: any = null;

const fetchServerData = (page: number, limit: number, search: string, status: string, priority: string, sort: string, dir: string) => {
    loading.value = true;
    router.get(
        route('app_admin_support_tickets'),
        {
            page,
            limit,
            search: search || '',
            status,
            priority,
            sort,
            dir,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => {
                loading.value = false;
            },
        }
    );
};

const onServerChange = (data: any) => {
    currentPage.value = data.current_page || 1;
    pageSize.value = data.pagesize || 10;
    sortColumn.value = data.sort_column || 'createdAt';
    sortDirection.value = data.sort_direction || 'desc';

    if (data.change_type === 'search') {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            fetchServerData(1, pageSize.value, data.search, currentStatus.value, currentPriority.value, sortColumn.value, sortDirection.value);
        }, 350);
    } else {
        fetchServerData(currentPage.value, pageSize.value, searchQuery.value, currentStatus.value, currentPriority.value, sortColumn.value, sortDirection.value);
    }
};

const onSearchInput = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage.value = 1;
        fetchServerData(1, pageSize.value, searchQuery.value, currentStatus.value, currentPriority.value, sortColumn.value, sortDirection.value);
    }, 350);
};

const setStatusFilter = (status: string) => {
    currentStatus.value = status;
    currentPage.value = 1;
    fetchServerData(1, pageSize.value, searchQuery.value, currentStatus.value, currentPriority.value, sortColumn.value, sortDirection.value);
};

const setPriorityFilter = (priority: string) => {
    currentPriority.value = priority;
    currentPage.value = 1;
    fetchServerData(1, pageSize.value, searchQuery.value, currentStatus.value, currentPriority.value, sortColumn.value, sortDirection.value);
};

const cols = computed(() => [
    { field: 'ticketId', title: __('Ticket ID'), sort: true, width: '170px', minWidth: '160px' },
    { field: 'priority', title: __('Priority'), sort: true, width: '110px', minWidth: '100px', headerClass: 'justify-center text-center', cellClass: '!text-center text-center' },
    { field: 'status', title: __('Status'), sort: true, width: '160px', minWidth: '150px', headerClass: 'justify-center text-center', cellClass: '!text-center text-center' },
    { field: 'reportedBy', title: __('Reported By'), sort: true, minWidth: '180px' },
    { field: 'positionTitle', title: __('Position / Context'), sort: true, minWidth: '160px' },
    { field: 'summary', title: __('Summary'), sort: false, minWidth: '220px' },
    { field: 'createdAt', title: __('Date'), sort: true, width: '160px', minWidth: '150px' },
    { field: 'actions', title: __('Actions'), sort: false, width: '120px', minWidth: '120px', headerClass: 'justify-end text-right', cellClass: '!text-right text-right' },
]);

// Quick status change
async function changeTicketStatus(ticket: SupportTicketItem, newStatus: 'open' | 'in_progress' | 'solved') {
    if (ticket.status === newStatus) return;

    try {
        const response = await fetch(route('app_admin_support_ticket_status', { id: ticket.id }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ status: newStatus }),
        });

        const data = await response.json();
        if (data.success) {
            ticket.status = newStatus;
            if (data.ticket?.solvedAt) {
                ticket.solvedAt = data.ticket.solvedAt;
            } else if (newStatus !== 'solved') {
                ticket.solvedAt = null;
            }
            if (selectedTicket.value && selectedTicket.value.id === ticket.id) {
                selectedTicket.value.status = newStatus;
            }

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: data.message || __('Status updated successfully'),
                showConfirmButton: false,
                timer: 2000,
            });

            // Reload counts
            router.reload({ only: ['statusCounts'] });
        }
    } catch (e: any) {
        Swal.fire({
            icon: 'error',
            title: __('Error'),
            text: e.message || __('Failed to update ticket status'),
        });
    }
}

// Open detail modal
function openDetails(ticket: SupportTicketItem) {
    selectedTicket.value = ticket;
    adminNoteInput.value = ticket.adminNotes || '';
    showDetailModal.value = true;
}

function onRowDBClick(row: any) {
    if (row && typeof row === 'object') {
        openDetails(row);
    }
}

// Save Admin Note
async function saveAdminNote() {
    if (!selectedTicket.value) return;
    isSavingNote.value = true;

    try {
        const response = await fetch(route('app_admin_support_ticket_status', { id: selectedTicket.value.id }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                status: selectedTicket.value.status,
                adminNotes: adminNoteInput.value,
            }),
        });

        const data = await response.json();
        if (data.success) {
            selectedTicket.value.adminNotes = adminNoteInput.value;
            const found = props.tickets.find((t) => t.id === selectedTicket.value?.id);
            if (found) found.adminNotes = adminNoteInput.value;

            // Automatically hide the reply modal
            showDetailModal.value = false;
            selectedTicket.value = null;

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: __('Admin reply & notes saved!'),
                showConfirmButton: false,
                timer: 2000,
            });

            // Refresh table data
            fetchServerData(currentPage.value, pageSize.value, searchQuery.value, currentStatus.value, currentPriority.value, sortColumn.value, sortDirection.value);
        }
    } catch (e: any) {
        Swal.fire({
            icon: 'error',
            title: __('Error'),
            text: e.message || __('Failed to save note'),
        });
    } finally {
        isSavingNote.value = false;
    }
}

// Delete ticket
function confirmDelete(ticket: SupportTicketItem) {
    Swal.fire({
        title: __('Delete Ticket?'),
        text: __('Are you sure you want to delete ticket :id? This cannot be undone.', { id: ticket.ticketId }),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: __('Yes, delete it'),
        cancelButtonText: __('Cancel'),
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(route('app_admin_support_ticket_delete', { id: ticket.id }), {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: data.message,
                        showConfirmButton: false,
                        timer: 2000,
                    });
                    if (showDetailModal.value && selectedTicket.value?.id === ticket.id) {
                        showDetailModal.value = false;
                    }
                    fetchServerData(currentPage.value, pageSize.value, searchQuery.value, currentStatus.value, currentPriority.value, sortColumn.value, sortDirection.value);
                }
            } catch (e: any) {
                Swal.fire({
                    icon: 'error',
                    title: __('Error'),
                    text: e.message || __('Failed to delete ticket'),
                });
            }
        }
    });
}

function copyToClipboard(text: string, label = 'Copied') {
    navigator.clipboard.writeText(text);
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `${label} to clipboard!`,
        showConfirmButton: false,
        timer: 1500,
    });
}

async function downloadTicketFile(ticket: SupportTicketItem | null) {
    if (!ticket) return;
    const fallbackFilename = ticket.fileName || `${ticket.ticketId}.json`;
    const url = ticket.downloadUrl || route('app_support_ticket_download_file', { filename: fallbackFilename });

    try {
        const res = await fetch(url);
        if (!res.ok) throw new Error('Download failed');
        const blob = await res.blob();
        const blobUrl = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = blobUrl;
        a.download = fallbackFilename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(blobUrl);

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: __('JSON file downloaded!'),
            showConfirmButton: false,
            timer: 2000,
        });
    } catch (e: any) {
        // Fallback: trigger standard browser download
        const a = document.createElement('a');
        a.href = url;
        a.setAttribute('download', fallbackFilename);
        document.body.appendChild(a);
        a.click();
        a.remove();
    }
}
</script>

<template>
    <Head :title="__('Support Tickets')" />

    <div class="pt-5 space-y-6">
        <!-- Header & Top Action Toolbar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white-light flex items-center gap-2.5">
                    <span class="inline-block w-2.5 h-7 rounded-full bg-gradient-to-b from-primary to-blue-400 shadow-[0_0_12px_rgba(67,97,238,0.5)]"></span>
                    {{ __('Support Tickets') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 pl-5">
                    {{ __('Manage user inquiries, view Power Automate Dropbox uploads, and track resolution status') }}
                </p>
            </div>

            <!-- Header Quick Badge -->
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                    <span>Dropbox Cloud Connected</span>
                </span>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Tickets -->
            <div
                @click="setStatusFilter('all')"
                class="p-4 rounded-2xl bg-white dark:bg-gray-800 border cursor-pointer transition shadow-xs hover:shadow-md"
                :class="currentStatus === 'all' ? 'border-primary ring-2 ring-primary/20' : 'border-gray-100 dark:border-gray-700/60'"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total Tickets') }}</span>
                    <span class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center">
                        <HugeiconsIcon :icon="AlertCircleIcon" :size="18" />
                    </span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div class="text-2xl font-black text-gray-900 dark:text-white">{{ statusCounts.total }}</div>
                    <span class="text-[11px] font-semibold text-gray-400">{{ __('All submissions') }}</span>
                </div>
            </div>

            <!-- Open Tickets -->
            <div
                @click="setStatusFilter('open')"
                class="p-4 rounded-2xl bg-white dark:bg-gray-800 border cursor-pointer transition shadow-xs hover:shadow-md"
                :class="currentStatus === 'open' ? 'border-amber-500 ring-2 ring-amber-500/20' : 'border-gray-100 dark:border-gray-700/60'"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ __('Open') }}</span>
                    <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                        <HugeiconsIcon :icon="AlertCircleIcon" :size="18" />
                    </span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ statusCounts.open }}</div>
                    <span class="text-[11px] font-semibold text-amber-600/70 dark:text-amber-400/70">{{ __('Requires attention') }}</span>
                </div>
            </div>

            <!-- In Progress Tickets -->
            <div
                @click="setStatusFilter('in_progress')"
                class="p-4 rounded-2xl bg-white dark:bg-gray-800 border cursor-pointer transition shadow-xs hover:shadow-md"
                :class="currentStatus === 'in_progress' ? 'border-blue-500 ring-2 ring-blue-500/20' : 'border-gray-100 dark:border-gray-700/60'"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-blue-600 dark:text-blue-400 uppercase tracking-wider">{{ __('In Progress') }}</span>
                    <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <HugeiconsIcon :icon="Clock01Icon" :size="18" />
                    </span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ statusCounts.in_progress }}</div>
                    <span class="text-[11px] font-semibold text-blue-600/70 dark:text-blue-400/70">{{ __('Under review') }}</span>
                </div>
            </div>

            <!-- Solved Tickets -->
            <div
                @click="setStatusFilter('solved')"
                class="p-4 rounded-2xl bg-white dark:bg-gray-800 border cursor-pointer transition shadow-xs hover:shadow-md"
                :class="currentStatus === 'solved' ? 'border-emerald-500 ring-2 ring-emerald-500/20' : 'border-gray-100 dark:border-gray-700/60'"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ __('Solved') }}</span>
                    <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                        <HugeiconsIcon :icon="CheckmarkCircle01Icon" :size="18" />
                    </span>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ statusCounts.solved }}</div>
                    <span class="text-[11px] font-semibold text-emerald-600/70 dark:text-emerald-400/70">{{ __('Resolved') }}</span>
                </div>
            </div>
        </div>

        <!-- Table Panel with Vue3Datatable & Pagination -->
        <div class="panel border-0 p-5">
            <!-- Filters & Search Control -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-4">
                <!-- Status Filter Pills -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <button
                        type="button"
                        @click="setStatusFilter('all')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition select-none"
                        :class="currentStatus === 'all'
                            ? 'bg-primary text-white shadow-xs'
                            : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    >
                        {{ __('All') }} ({{ statusCounts.total }})
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('open')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition select-none flex items-center gap-1"
                        :class="currentStatus === 'open'
                            ? 'bg-amber-500 text-white shadow-xs'
                            : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    >
                        <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>
                        <span>{{ __('Open') }} ({{ statusCounts.open }})</span>
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('in_progress')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition select-none flex items-center gap-1"
                        :class="currentStatus === 'in_progress'
                            ? 'bg-blue-500 text-white shadow-xs'
                            : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    >
                        <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>
                        <span>{{ __('In Progress') }} ({{ statusCounts.in_progress }})</span>
                    </button>

                    <button
                        type="button"
                        @click="setStatusFilter('solved')"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold transition select-none flex items-center gap-1"
                        :class="currentStatus === 'solved'
                            ? 'bg-emerald-600 text-white shadow-xs'
                            : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span>
                        <span>{{ __('Solved') }} ({{ statusCounts.solved }})</span>
                    </button>
                </div>

                <!-- Priority Dropdown & Search Bar -->
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <select
                        v-model="currentPriority"
                        @change="setPriorityFilter(currentPriority)"
                        class="form-select text-xs py-1.5 px-3 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80 text-gray-700 dark:text-gray-200"
                    >
                        <option value="all">{{ __('All Priorities') }}</option>
                        <option value="High">{{ __('High Priority') }}</option>
                        <option value="Average">{{ __('Average Priority') }}</option>
                        <option value="Low">{{ __('Low Priority') }}</option>
                    </select>

                    <div class="relative w-full sm:w-64">
                        <input
                            type="text"
                            v-model="searchQuery"
                            @input="onSearchInput"
                            :placeholder="__('Filter tickets...')"
                            class="form-input text-xs pl-8 pr-3 py-1.5 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80 w-full"
                        />
                        <HugeiconsIcon
                            :icon="Search01Icon"
                            :size="14"
                            color="currentColor"
                            class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400"
                        />
                    </div>
                </div>
            </div>

            <!-- Vue3Datatable Component in Server Mode -->
            <div class="datatable">
                <Vue3Datatable
                    ref="datatableRef"
                    :isServerMode="true"
                    :loading="loading"
                    :totalRows="props.totalRows ?? (props.tickets || []).length"
                    :rows="tickets || []"
                    :columns="cols"
                    :search="searchQuery"
                    :page="currentPage"
                    :pageSize="pageSize"
                    :pageSizeOptions="[5, 10, 20, 50, 100]"
                    :showPageSize="true"
                    :pagination="true"
                    :showNumbers="true"
                    :showFirstPage="true"
                    :showLastPage="true"
                    :sortable="true"
                    :sortColumn="sortColumn"
                    :sortDirection="sortDirection"
                    skin="bh-table-hover"
                    :paginationInfo="__('Showing') + ' {0} ' + __('to') + ' {1} ' + __('of') + ' {2} ' + __('entries')"
                    :noDataContent="__('No support tickets found.')"
                    @change="onServerChange"
                    @rowDBClick="onRowDBClick"
                >
                    <!-- Ticket ID Slot -->
                    <template #ticketId="data">
                        <div class="flex items-center gap-1.5">
                            <button
                                type="button"
                                @click="openDetails(data.value)"
                                class="font-mono font-bold text-primary hover:text-blue-700 dark:hover:text-blue-300 transition text-xs flex items-center gap-1"
                            >
                                <span>{{ data.value.ticketId }}</span>
                            </button>
                            <button
                                type="button"
                                @click="copyToClipboard(data.value.ticketId, 'Ticket ID copied')"
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                                title="Copy Ticket ID"
                            >
                                <HugeiconsIcon :icon="Copy01Icon" :size="13" />
                            </button>
                        </div>
                    </template>

                    <!-- Priority Slot -->
                    <template #priority="data">
                        <div class="flex justify-center items-center">
                            <span
                                v-if="data.value.priority === 'High'"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 border border-rose-200/60 dark:border-rose-900/40"
                            >
                                High
                            </span>
                            <span
                                v-else-if="data.value.priority === 'Average'"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200/60 dark:border-amber-900/40"
                            >
                                Average
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-gray-300 border border-slate-200 dark:border-gray-600"
                            >
                                Low
                            </span>
                        </div>
                    </template>

                    <!-- Status Slot (Interactive Dropdown for Admin) -->
                    <template #status="data">
                        <div class="flex justify-center items-center">
                            <select
                                :value="data.value.status"
                                @change="changeTicketStatus(data.value, ($event.target as HTMLSelectElement).value as any)"
                                class="text-xs font-bold py-1 px-2.5 rounded-full border transition cursor-pointer select-none"
                                :class="{
                                    'bg-amber-50 text-amber-700 border-amber-300 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800': data.value.status === 'open',
                                    'bg-blue-50 text-blue-700 border-blue-300 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800': data.value.status === 'in_progress',
                                    'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800': data.value.status === 'solved',
                                }"
                            >
                                <option value="open">🟡 {{ __('Open') }}</option>
                                <option value="in_progress">🔵 {{ __('In Progress') }}</option>
                                <option value="solved">🟢 {{ __('Solved') }}</option>
                            </select>
                        </div>
                    </template>

                    <!-- Reported By Slot -->
                    <template #reportedBy="data">
                        <div class="text-xs">
                            <div class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
                                <span>{{ data.value.reportedBy }}</span>
                                <span
                                    v-if="data.value.isSimulated"
                                    class="px-1.5 py-0.2 rounded text-[10px] bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 font-semibold"
                                >
                                    Local
                                </span>
                            </div>
                            <div v-if="data.value.reporterEmail" class="text-gray-400 text-[11px] font-mono">
                                {{ data.value.reporterEmail }}
                            </div>
                        </div>
                    </template>

                    <!-- Position Slot -->
                    <template #positionTitle="data">
                        <div v-if="data.value.positionTitle" class="flex items-center gap-1.5 text-xs text-gray-800 dark:text-gray-200 font-semibold truncate max-w-xs">
                            <HugeiconsIcon :icon="Briefcase08Icon" :size="13" class="text-primary shrink-0" />
                            <span class="truncate" :title="data.value.positionTitle">{{ data.value.positionTitle }}</span>
                        </div>
                        <div v-else class="text-gray-400 text-xs italic">
                            {{ __('General Inquiries') }}
                        </div>
                    </template>

                    <!-- Summary Slot -->
                    <template #summary="data">
                        <div class="truncate max-w-xs text-xs text-gray-700 dark:text-gray-300" :title="data.value.summary">
                            {{ data.value.summary }}
                        </div>
                    </template>

                    <!-- Date Slot -->
                    <template #createdAt="data">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ data.value.createdAt }}</span>
                    </template>

                    <!-- Actions Slot -->
                    <template #actions="data">
                        <div class="flex items-center justify-end gap-1.5">
                            <Button
                                size="sm"
                                variant="outline"
                                class="h-7 px-2 text-xs rounded-lg flex items-center gap-1"
                                @click="openDetails(data.value)"
                                title="View Details"
                            >
                                <HugeiconsIcon :icon="ViewIcon" :size="13" color="currentColor" />
                                <span>{{ __('View') }}</span>
                            </Button>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center h-7 w-7 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-primary transition cursor-pointer"
                                @click.stop="downloadTicketFile(data.value)"
                                title="Download JSON file"
                            >
                                <HugeiconsIcon :icon="Download01Icon" :size="13" />
                            </button>

                            <Button
                                size="sm"
                                variant="destructive"
                                class="h-7 w-7 p-0 rounded-lg flex items-center justify-center"
                                @click="confirmDelete(data.value)"
                                title="Delete Ticket"
                            >
                                <HugeiconsIcon :icon="Delete01Icon" :size="13" color="currentColor" />
                            </Button>
                        </div>
                    </template>
                </Vue3Datatable>
            </div>
        </div>
    </div>

    <!-- Ticket Detail Modal for Admin -->
    <div
        v-if="showDetailModal && selectedTicket"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-fadeIn"
    >
        <div class="bg-white dark:bg-gray-800 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700 space-y-5 animate-scaleUp max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-700 pb-4">
                <div class="flex items-center gap-2.5">
                    <span class="w-3 h-3 rounded-full bg-primary"></span>
                    <div>
                        <h4 class="text-base font-black text-gray-900 dark:text-white">
                            {{ selectedTicket.ticketId }}
                        </h4>
                        <span class="text-xs text-gray-400">
                            {{ __('Submitted on') }} {{ selectedTicket.createdAt }}
                        </span>
                    </div>
                </div>

                <button
                    type="button"
                    @click="showDetailModal = false"
                    class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 hover:text-gray-900 dark:hover:text-white flex items-center justify-center transition"
                >
                    <HugeiconsIcon :icon="Cancel01Icon" :size="16" />
                </button>
            </div>

            <!-- Priority & Status Bar -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <span
                        class="px-2.5 py-1 rounded-full text-xs font-bold"
                        :class="{
                            'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300': selectedTicket.priority === 'High',
                            'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300': selectedTicket.priority === 'Average',
                            'bg-slate-100 text-slate-800 dark:bg-gray-700 dark:text-gray-300': selectedTicket.priority === 'Low',
                        }"
                    >
                        {{ selectedTicket.priority }} {{ __('Priority') }}
                    </span>

                    <span v-if="selectedTicket.solvedAt" class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                        {{ __('Resolved at') }}: {{ selectedTicket.solvedAt }}
                    </span>
                </div>

                <!-- Status Selector -->
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500">{{ __('Set Status') }}:</span>
                    <select
                        :value="selectedTicket.status"
                        @change="changeTicketStatus(selectedTicket, ($event.target as HTMLSelectElement).value as any)"
                        class="form-select text-xs py-1 px-3 rounded-xl border-gray-200 dark:border-gray-700 font-bold"
                        :class="{
                            'bg-amber-50 text-amber-800': selectedTicket.status === 'open',
                            'bg-blue-50 text-blue-800': selectedTicket.status === 'in_progress',
                            'bg-emerald-50 text-emerald-800': selectedTicket.status === 'solved',
                        }"
                    >
                        <option value="open">🟡 Open</option>
                        <option value="in_progress">🔵 In Progress</option>
                        <option value="solved">🟢 Solved</option>
                    </select>
                </div>
            </div>

            <!-- Context Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 bg-gray-50/60 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700/50 text-xs">
                <div>
                    <span class="font-bold text-gray-500 dark:text-gray-400 block mb-0.5">{{ __('Reported By') }}:</span>
                    <span class="text-gray-900 dark:text-gray-100 font-semibold">{{ selectedTicket.reportedBy }}</span>
                    <span v-if="selectedTicket.reporterEmail" class="block font-mono text-[11px] text-gray-500">{{ selectedTicket.reporterEmail }}</span>
                </div>

                <div>
                    <span class="font-bold text-gray-500 dark:text-gray-400 block mb-0.5">{{ __('Position') }}:</span>
                    <span class="text-gray-900 dark:text-gray-100 font-semibold">{{ selectedTicket.positionTitle || __('None (General)') }}</span>
                </div>

                <div class="sm:col-span-2" v-if="selectedTicket.pageLink">
                    <span class="font-bold text-gray-500 dark:text-gray-400 block mb-0.5">{{ __('Originating Page') }}:</span>
                    <a
                        :href="selectedTicket.pageLink"
                        target="_blank"
                        class="text-primary hover:underline flex items-center gap-1 break-all"
                    >
                        <span>{{ selectedTicket.pageLink }}</span>
                        <HugeiconsIcon :icon="ArrowUpRight01Icon" :size="12" />
                    </a>
                </div>

                <div class="sm:col-span-2" v-if="selectedTicket.cloudPath">
                    <span class="font-bold text-gray-500 dark:text-gray-400 block mb-0.5">{{ __('Dropbox Storage Path') }}:</span>
                    <span class="font-mono text-gray-600 dark:text-gray-400 break-all">{{ selectedTicket.cloudPath }}</span>
                </div>
            </div>

            <!-- Summary / Issue Description -->
            <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Issue Summary / Description') }}</label>
                <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700 text-xs text-gray-800 dark:text-gray-200 whitespace-pre-line leading-relaxed">
                    {{ selectedTicket.summary }}
                </div>
            </div>

            <!-- Admin Notes / Response Textarea -->
            <div class="space-y-2">
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                    {{ __('Admin Resolution Notes & User Feedback') }}
                </label>
                <textarea
                    v-model="adminNoteInput"
                    rows="3"
                    :placeholder="__('Add resolution notes or instructions for the user...')"
                    class="form-textarea text-xs w-full rounded-2xl border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50"
                ></textarea>
                <div class="flex justify-end">
                    <Button
                        size="sm"
                        class="bg-primary hover:bg-primary/90 text-white font-bold rounded-xl text-xs flex items-center gap-1 h-8"
                        :disabled="isSavingNote"
                        @click="saveAdminNote"
                    >
                        <HugeiconsIcon :icon="PencilEdit02Icon" :size="13" />
                        <span>{{ isSavingNote ? __('Saving...') : __('Save Notes') }}</span>
                    </Button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-between pt-2 border-t border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline cursor-pointer"
                        @click="downloadTicketFile(selectedTicket)"
                    >
                        <HugeiconsIcon :icon="Download01Icon" :size="14" />
                        <span>{{ __('Download Ticket JSON') }}</span>
                    </button>

                    <button
                        type="button"
                        class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline"
                        @click="confirmDelete(selectedTicket)"
                    >
                        {{ __('Delete Ticket') }}
                    </button>
                </div>

                <Button
                    size="sm"
                    variant="outline"
                    class="rounded-xl px-4 text-xs font-semibold"
                    @click="showDetailModal = false"
                >
                    {{ __('Close') }}
                </Button>
            </div>
        </div>
    </div>
</template>
