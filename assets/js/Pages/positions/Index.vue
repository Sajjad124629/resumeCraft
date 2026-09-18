<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Vue3Datatable from '@bhplugin/vue3-datatable';

import { Button } from '@/Components/ui/button';
import TextLink from '@/Components/TextLink.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    PlusSignIcon,
    PencilEdit02Icon,
    Delete01Icon,
    Search01Icon,
    Copy01Icon
} from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    positions: any[];
    totalRows?: number;
    currentPage?: number;
    pageSize?: number;
    search?: string;
    sort?: string;
    sortDir?: string;
    availableAttributes: any[];
    auth?: any;
}>();

const datatableRef = ref<any>(null);
const selectedRows = ref<any[]>([]);
const loading = ref(false);
const currentPage = ref(props.currentPage || 1);
const pageSize = ref(props.pageSize || 10);
const searchQuery = ref(props.search || '');
const sortColumn = ref(props.sort || 'id');
const sortDirection = ref(props.sortDir || 'desc');

let searchTimer: any = null;

const fetchServerData = (page: number, limit: number, search: string, sort: string, dir: string) => {
    loading.value = true;
    router.get('/positions', {
        page,
        limit,
        search: search || '',
        sort,
        dir
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            loading.value = false;
        }
    });
};

const onServerChange = (data: any) => {
    currentPage.value = data.current_page || 1;
    pageSize.value = data.pagesize || 10;
    sortColumn.value = data.sort_column || 'id';
    sortDirection.value = data.sort_direction || 'desc';

    if (data.change_type === 'search') {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            fetchServerData(1, pageSize.value, data.search, sortColumn.value, sortDirection.value);
        }, 350);
    } else {
        fetchServerData(currentPage.value, pageSize.value, searchQuery.value, sortColumn.value, sortDirection.value);
    }
};

const onSearchInput = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        fetchServerData(1, pageSize.value, searchQuery.value, sortColumn.value, sortDirection.value);
    }, 350);
};

const cols = computed(() => [
    { field: 'title', title: __('Title'), sort: true },
    { field: 'company', title: __('Company'), sort: true },
    { field: 'level', title: __('Level'), sort: true },
    { field: 'cvCount', title: __('CVs'), sort: true, width: '110px', minWidth: '110px', headerClass: 'justify-center text-center', cellClass: '!text-center text-center' },
    { field: 'isPublic', title: __('Status'), sort: true, width: '140px', minWidth: '140px', headerClass: 'justify-center text-center', cellClass: '!text-center text-center' },
]);

const onRowSelect = (rows: any[]) => {
    selectedRows.value = rows || [];
};

function getSelectedPositionId(): number | null {
    if (selectedRows.value.length !== 1) return null;
    const row = selectedRows.value[0];
    return row?.id ?? (typeof row === 'number' ? row : null);
}

function viewPosition() {
    const id = getSelectedPositionId();
    if (id) {
        router.visit(`/positions/${id}`);
    }
}

function editPosition() {
    const id = getSelectedPositionId();
    if (id) {
        router.visit(`/positions/${id}/edit`);
    }
}

function duplicatePosition() {
    const id = getSelectedPositionId();
    if (!id) return;
    router.post(`/positions/${id}/duplicate`, {}, {
        onSuccess: () => {
            selectedRows.value = [];
            datatableRef.value?.clearSelectedRows();
        }
    });
}

function deletePositions() {
    if (selectedRows.value.length === 0) return;
    const count = selectedRows.value.length;
    if (confirm(`Are you sure you want to delete ${count} position(s)?`)) {
        selectedRows.value.forEach((row: any) => {
            const id = row?.id ?? (typeof row === 'number' ? row : null);
            if (id) {
                router.delete(`/positions/${id}`);
            }
        });
        selectedRows.value = [];
        datatableRef.value?.clearSelectedRows();
    }
}

function onRowDBClick(row: any) {
    const id = row?.id ?? (typeof row === 'number' ? row : null);
    if (id) {
        router.visit(`/positions/${id}`);
    }
}

const isRecruiter = () => {
    return props.auth?.user?.roles?.includes('ROLE_RECRUITER') || props.auth?.user?.roles?.includes('ROLE_ADMIN');
};
</script>

<template>

    <Head :title="__('Positions')" />

    <div class="pt-5 space-y-6">
        <!-- Header & Action Toolbar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3
                    class="text-2xl font-black tracking-tight text-gray-900 dark:text-white-light flex items-center gap-2.5">
                    <span
                        class="inline-block w-2.5 h-7 rounded-full bg-gradient-to-b from-primary to-blue-400 shadow-[0_0_12px_rgba(67,97,238,0.5)]"></span>
                    {{ __('Positions') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 pl-5">
                    {{ __('Manage openings, CV templates, and access rules') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Selection Toolbar -->
                <div v-if="selectedRows.length > 0 && isRecruiter()"
                    class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl shadow-xs transition-all animate-fadeIn">
                    <span class="text-xs text-blue-800 dark:text-blue-300 font-bold mr-1">{{ selectedRows.length }} {{
                        __('selected') }}</span>
                    <Button v-if="selectedRows.length === 1" size="sm" variant="outline"
                        class="flex gap-1 h-7 text-xs rounded-lg" @click="viewPosition">
                        <HugeiconsIcon :icon="Search01Icon" :size="13" color="currentColor" /> {{ __('View') }}
                    </Button>
                    <Button v-if="selectedRows.length === 1" size="sm" variant="outline"
                        class="flex gap-1 h-7 text-xs rounded-lg" @click="editPosition">
                        <HugeiconsIcon :icon="PencilEdit02Icon" :size="13" color="currentColor" /> {{ __('Edit') }}
                    </Button>
                    <Button v-if="selectedRows.length === 1" size="sm" variant="outline"
                        class="flex gap-1 h-7 text-xs rounded-lg" @click="duplicatePosition">
                        <HugeiconsIcon :icon="Copy01Icon" :size="13" color="currentColor" /> {{ __('Duplicate') }}
                    </Button>
                    <Button size="sm" variant="destructive" @click="deletePositions"
                        class="flex gap-1 h-7 text-xs rounded-lg">
                        <HugeiconsIcon :icon="Delete01Icon" :size="13" color="currentColor" /> {{ __('Delete') }}
                    </Button>
                </div>

                <!-- 3D Create Position Button -->
                <TextLink v-if="isRecruiter()" :href="route('app_position_create_view')">
                    <Button size="sm"
                        class="bg-gradient-to-r from-primary to-blue-600 hover:from-primary/90 hover:to-blue-700 !text-white font-bold shadow-[0_4px_14px_rgba(67,97,238,0.35),inset_0_1px_0_rgba(255,255,255,0.3)] hover:-translate-y-0.5 active:scale-95 transition-all duration-200 rounded-xl px-4 py-2 flex items-center gap-1.5">
                        <HugeiconsIcon :icon="PlusSignIcon" :size="16" color="currentColor" /> {{ __('Create Position')
                        }}
                    </Button>
                </TextLink>
            </div>
        </div>

        <!-- Table Panel with Vue3Datatable & Pagination -->
        <div class="panel border-0 p-5">
            <!-- Search Control -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-4">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('Positions List') }}
                </div>

                <div class="relative w-full sm:w-64">
                    <input type="text" v-model="searchQuery" @input="onSearchInput"
                        :placeholder="__('Filter positions...')"
                        class="form-input text-xs pl-8 pr-3 py-1.5 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80 w-full" />
                    <HugeiconsIcon :icon="Search01Icon" :size="14" color="currentColor"
                        class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" />
                </div>
            </div>

            <!-- Vue3Datatable Component in Server Mode -->
            <div class="datatable">
                <Vue3Datatable ref="datatableRef" :isServerMode="true" :loading="loading"
                    :totalRows="props.totalRows ?? (props.positions || []).length" :rows="positions || []"
                    :columns="cols" :hasCheckbox="isRecruiter()" :search="searchQuery" :page="currentPage"
                    :pageSize="pageSize" :pageSizeOptions="[5, 10, 20, 50]" :showPageSize="true" :pagination="true"
                    :showNumbers="true" :showFirstPage="true" :showLastPage="true" :sortColumn="sortColumn"
                    :sortDirection="sortDirection" skin="bh-table-hover"
                    :paginationInfo="__('Showing') + ' {0} ' + __('to') + ' {1} ' + __('of') + ' {2} ' + __('entries')"
                    :noDataContent="__('No positions found.')" @change="onServerChange" @rowSelect="onRowSelect"
                    @rowDBClick="onRowDBClick">
                    <template #title="data">
                        <Link :href="`/positions/${data.value.id}`"
                            class="font-bold text-gray-900 dark:text-gray-100 hover:text-primary transition inline-flex items-center gap-1.5">
                            {{ data.value.title }}
                        </Link>
                    </template>
                    <template #company="data">
                        <span class="text-gray-600 dark:text-gray-400">{{ data.value.company || '-' }}</span>
                    </template>
                    <template #level="data">
                        <span class="text-gray-600 dark:text-gray-400">{{ data.value.level || '-' }}</span>
                    </template>
                    <template #cvCount="data">
                        <div class="flex justify-center items-center">
                            <span
                                class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary-light">
                                {{ data.value.cvCount }}
                            </span>
                        </div>
                    </template>
                    <template #isPublic="data">
                        <div class="flex justify-center items-center">
                            <span v-if="data.value.isPublic"
                                class="px-2.5 py-1 bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 rounded-lg text-xs font-semibold">{{
                                __('Public') }}</span>
                            <span v-else
                                class="px-2.5 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 rounded-lg text-xs font-semibold">{{
                                __('Restricted') }}</span>
                        </div>
                    </template>
                </Vue3Datatable>
            </div>
        </div>
    </div>
</template>
