<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Vue3Datatable from '@bhplugin/vue3-datatable';
import { Button } from '@/Components/ui/button';
import TextLink from '@/Components/TextLink.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { 
    PlusSignIcon, 
    PencilEdit02Icon, 
    Delete01Icon, 
    Search01Icon
} from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    attributes: any[];
    categories: any[];
    totalRows?: number;
    currentPage?: number;
    pageSize?: number;
    search?: string;
    sort?: string;
    sortDir?: string;
}>();

const datatableRef = ref<any>(null);
const selectedRows = ref<any[]>([]);
const loading = ref(false);
const currentPage = ref(props.currentPage || 1);
const pageSize = ref(props.pageSize || 10);
const searchQuery = ref(props.search || '');
const sortColumn = ref(props.sort || 'name');
const sortDirection = ref(props.sortDir || 'asc');

let searchTimer: any = null;

const fetchServerData = (page: number, limit: number, search: string, sort: string, dir: string) => {
    loading.value = true;
    router.get('/attributes', {
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
    sortColumn.value = data.sort_column || 'name';
    sortDirection.value = data.sort_direction || 'asc';

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
    { field: 'name', title: __('Name'), sort: true },
    { field: 'category', title: __('Category'), sort: true },
    { field: 'type', title: __('Type'), sort: true },
    { field: 'description', title: __('Description'), sort: true },
]);

const onRowSelect = (rows: any[]) => {
    selectedRows.value = rows || [];
};

function getSelectedAttributeId(): number | null {
    if (selectedRows.value.length !== 1) return null;
    const row = selectedRows.value[0];
    return row?.id ?? (typeof row === 'number' ? row : null);
}

function editAttribute() {
    const id = getSelectedAttributeId();
    if (id) {
        router.visit(`/attributes/${id}/edit`);
    }
}

function deleteAttributes() {
    if (selectedRows.value.length === 0) return;
    const count = selectedRows.value.length;
    if (confirm(`Are you sure you want to delete ${count} attribute(s)?`)) {
        selectedRows.value.forEach((row: any) => {
            const id = row?.id ?? (typeof row === 'number' ? row : null);
            if (id) {
                router.delete(`/attributes/${id}`);
            }
        });
        selectedRows.value = [];
        datatableRef.value?.clearSelectedRows();
    }
}

function onRowDBClick(row: any) {
    const id = row?.id ?? (typeof row === 'number' ? row : null);
    if (id) {
        router.visit(`/attributes/${id}/edit`);
    }
}
</script>

<template>
    <Head :title="__('Attribute Library')" />

    <div class="pt-5 space-y-6">
        <!-- Header & Action Toolbar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white-light flex items-center gap-2.5">
                    <span class="inline-block w-2.5 h-7 rounded-full bg-gradient-to-b from-purple-500 to-indigo-600 shadow-[0_0_12px_rgba(168,85,247,0.5)]"></span>
                    {{ __('Attribute Library') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 pl-5">
                    {{ __('Define reusable custom fields, validation rules, and candidate attributes') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Selection Toolbar -->
                <div v-if="selectedRows.length > 0" class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl shadow-xs transition-all animate-fadeIn">
                    <span class="text-xs text-blue-800 dark:text-blue-300 font-bold mr-1">{{ selectedRows.length }} {{ __('selected') }}</span>
                    <Button v-if="selectedRows.length === 1" size="sm" variant="outline" class="flex gap-1 h-7 text-xs rounded-lg" @click="editAttribute">
                        <HugeiconsIcon :icon="PencilEdit02Icon" :size="13" color="currentColor" /> {{ __('Edit') }}
                    </Button>
                    <Button size="sm" variant="destructive" @click="deleteAttributes" class="flex gap-1 h-7 text-xs rounded-lg">
                        <HugeiconsIcon :icon="Delete01Icon" :size="13" color="currentColor" /> {{ __('Delete') }}
                    </Button>
                </div>
                
                <!-- 3D Create Attribute Button -->
                <TextLink :href="route('app_attribute_create_view')">
                    <Button size="sm" class="bg-gradient-to-r from-primary to-blue-600 hover:from-primary/90 hover:to-blue-700 !text-white font-bold shadow-[0_4px_14px_rgba(67,97,238,0.35),inset_0_1px_0_rgba(255,255,255,0.3)] hover:-translate-y-0.5 active:scale-95 transition-all duration-200 rounded-xl px-4 py-2 flex items-center gap-1.5">
                        <HugeiconsIcon :icon="PlusSignIcon" :size="16" color="currentColor" /> {{ __('Create Attribute') }}
                    </Button>
                </TextLink>
            </div>
        </div>

        <!-- Table Panel with Vue3Datatable & Pagination -->
        <div class="panel border-0 p-5">
            <!-- Search Control -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-4">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('Attributes List') }}
                </div>

                <div class="relative w-full sm:w-64">
                    <input 
                        type="text" 
                        v-model="searchQuery" 
                        @input="onSearchInput"
                        :placeholder="__('Filter attributes...')"
                        class="form-input text-xs pl-8 pr-3 py-1.5 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80 w-full"
                    />
                    <HugeiconsIcon :icon="Search01Icon" :size="14" color="currentColor" class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" />
                </div>
            </div>

            <!-- Vue3Datatable Component -->
            <div class="datatable">
                <Vue3Datatable
                    ref="datatableRef"
                    :isServerMode="true"
                    :loading="loading"
                    :totalRows="props.totalRows ?? (props.attributes || []).length"
                    :rows="attributes || []"
                    :columns="cols"
                    :hasCheckbox="true"
                    :search="searchQuery"
                    :page="currentPage"
                    :pageSize="pageSize"
                    :pageSizeOptions="[5, 10, 20, 50]"
                    :showPageSize="true"
                    :pagination="true"
                    :showNumbers="true"
                    :showFirstPage="true"
                    :showLastPage="true"
                    :sortColumn="sortColumn"
                    :sortDirection="sortDirection"
                    skin="bh-table-hover"
                    :paginationInfo="__('Showing') + ' {0} ' + __('to') + ' {1} ' + __('of') + ' {2} ' + __('entries')"
                    :noDataContent="__('No attributes found.')"
                    @change="onServerChange"
                    @rowSelect="onRowSelect"
                    @rowDBClick="onRowDBClick"
                >
                    <template #name="data">
                        <span class="font-bold text-gray-900 dark:text-gray-100">{{ data.value.name }}</span>
                    </template>
                    <template #category="data">
                        <span class="px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            {{ data.value.category?.name || '-' }}
                        </span>
                    </template>
                    <template #type="data">
                        <span class="px-2.5 py-0.5 bg-blue-50 text-primary dark:bg-blue-900/30 dark:text-blue-300 rounded-lg text-xs font-bold uppercase tracking-wider">
                            {{ __(data.value.type) }}
                        </span>
                    </template>
                    <template #description="data">
                        <span class="text-gray-500 text-xs">{{ data.value.description || '-' }}</span>
                    </template>
                </Vue3Datatable>
            </div>
        </div>
    </div>
</template>
