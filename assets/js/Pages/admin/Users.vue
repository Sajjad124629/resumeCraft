<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Vue3Datatable from '@bhplugin/vue3-datatable';
import { Button } from '@/Components/ui/button';
import TextLink from '@/Components/TextLink.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { route } from '@/route';
import { 
    Delete01Icon, 
    SecurityBlockIcon, 
    PencilEdit02Icon,
    Search01Icon,
    PlusSignIcon,
    Cancel01Icon
} from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    users: any[];
    roles: any[];
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
const sortColumn = ref(props.sort || 'id');
const sortDirection = ref(props.sortDir || 'desc');
const selectedRole = ref<string>('ROLE_CANDIDATE');

const showCreateModal = ref(false);
const isSubmittingCreate = ref(false);
const createForm = ref({
    firstName: '',
    lastName: '',
    email: '',
    password: '',
    roleSlug: 'ROLE_CANDIDATE',
});

function submitCreateUser() {
    if (!createForm.value.email || !createForm.value.password) {
        alert('Email and Password are required.');
        return;
    }
    isSubmittingCreate.value = true;
    router.post(route('app_admin_user_create'), createForm.value, {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.value = {
                firstName: '',
                lastName: '',
                email: '',
                password: '',
                roleSlug: 'ROLE_CANDIDATE',
            };
        },
        onFinish: () => {
            isSubmittingCreate.value = false;
        }
    });
}

let searchTimer: any = null;

const fetchServerData = (page: number, limit: number, search: string, sort: string, dir: string) => {
    loading.value = true;
    router.get(route('app_admin_users'), {
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
    { field: 'fullName', title: __('User'), sort: true },
    { field: 'role', title: __('Role'), sort: true },
    { field: 'isBlocked', title: __('Status'), sort: true, width: '120px', minWidth: '120px', headerClass: 'justify-center text-center', cellClass: '!text-center text-center' },
    { field: 'isVerified', title: __('Verified'), sort: true, width: '110px', minWidth: '110px', headerClass: 'justify-center text-center', cellClass: '!text-center text-center' },
    { field: 'profile', title: __('Profile Link'), sort: false, width: '140px', minWidth: '140px', headerClass: 'justify-end text-right', cellClass: '!text-right text-right' },
]);

const onRowSelect = (rows: any[]) => {
    selectedRows.value = rows || [];
};

function getSelectedUser() {
    if (selectedRows.value.length === 1) {
        const item = selectedRows.value[0];
        return typeof item === 'object' && item !== null ? item : props.users.find(u => u.id === item);
    }
    return null;
}

function toggleBlockSelected() {
    if (selectedRows.value.length === 0) return;
    const user = getSelectedUser();
    if (!user) return;
    if (user.isSelf) {
        alert('You cannot block your own account.');
        return;
    }

    router.post(route('app_admin_user_toggle_block', { id: user.id }), {}, {
        onSuccess: () => {
            selectedRows.value = [];
            if (datatableRef.value) {
                datatableRef.value.clearSelectedRows();
            }
        }
    });
}

function changeRoleSelected() {
    if (selectedRows.value.length === 0) return;
    const user = getSelectedUser();
    if (!user) return;

    if (confirm(`Change role of ${user.email} to ${selectedRole.value}?`)) {
        router.post(route('app_admin_user_change_role', { id: user.id }), { roleSlug: selectedRole.value }, {
            onSuccess: () => {
                selectedRows.value = [];
                if (datatableRef.value) {
                    datatableRef.value.clearSelectedRows();
                }
            }
        });
    }
}

function deleteSelected() {
    if (selectedRows.value.length === 0) return;

    const rowObjects = selectedRows.value.map(item => {
        return typeof item === 'object' && item !== null ? item : props.users.find(u => u.id === item);
    }).filter(Boolean);

    const validTargets = rowObjects.filter(u => !u.isSelf);
    const hasSelf = rowObjects.some(u => u.isSelf);

    if (validTargets.length === 0) {
        alert('You cannot delete your own admin account while logged in.');
        return;
    }

    const warning = hasSelf ? ' (Note: Your own account will be excluded from deletion.)' : '';
    if (confirm(`Are you sure you want to delete ${validTargets.length} selected user(s)? This will cascade delete their profile, projects, and CVs.${warning}`)) {
        validTargets.forEach(u => {
            router.delete(route('app_admin_user_delete', { id: u.id }));
        });
        selectedRows.value = [];
        if (datatableRef.value) {
            datatableRef.value.clearSelectedRows();
        }
    }
}
</script>

<template>
    <Head :title="__('User Management')" />

    <div class="pt-5 space-y-6">
        <!-- Header & Action Toolbar -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-2xl font-black tracking-tight text-gray-900 dark:text-white-light flex items-center gap-2.5">
                    <span class="inline-block w-2.5 h-7 rounded-full bg-gradient-to-b from-primary to-blue-400 shadow-[0_0_12px_rgba(67,97,238,0.5)]"></span>
                    {{ __('User Management') }}
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 pl-5">
                    {{ __('Manage registered users, assign roles, block access, and inspect candidate profiles') }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <!-- Selection Toolbar -->
                <div v-if="selectedRows.length > 0" class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-xl shadow-xs transition-all animate-fadeIn">
                    <span class="text-xs text-blue-800 dark:text-blue-300 font-bold mr-1">{{ selectedRows.length }} {{ __('selected') }}</span>
                    
                    <!-- Single User Actions -->
                    <template v-if="selectedRows.length === 1">
                        <Button 
                            size="sm" 
                            variant="outline" 
                            class="flex gap-1 h-7 text-xs rounded-lg" 
                            :disabled="getSelectedUser()?.isSelf"
                            :class="{'opacity-50 cursor-not-allowed': getSelectedUser()?.isSelf}"
                            :title="getSelectedUser()?.isSelf ? __('You cannot block your own account') : ''"
                            @click="toggleBlockSelected"
                        >
                            <HugeiconsIcon :icon="SecurityBlockIcon" :size="13" color="currentColor" />
                            {{ getSelectedUser()?.isBlocked ? __('Unblock') : __('Block') }}
                        </Button>

                        <!-- Change Role -->
                        <div class="flex items-center gap-1">
                            <select v-model="selectedRole" class="form-select h-7 text-xs py-0 pl-2 pr-6 border-gray-300 rounded-lg">
                                <option v-for="r in roles" :key="r.id" :value="r.slug">{{ __(r.name) }}</option>
                            </select>
                            <Button size="sm" variant="outline" class="h-7 text-xs px-2.5 rounded-lg" @click="changeRoleSelected">
                                {{ __('Set Role') }}
                            </Button>
                        </div>

                        <!-- Edit Candidate Profile if user has one -->
                        <TextLink v-if="getSelectedUser()?.candidateProfileId" :href="route('app_profile_candidate_admin', { id: getSelectedUser()?.candidateProfileId })">
                            <Button size="sm" variant="outline" class="flex gap-1 h-7 text-xs rounded-lg">
                                <HugeiconsIcon :icon="PencilEdit02Icon" :size="13" color="currentColor" /> {{ __('Edit Profile') }}
                            </Button>
                        </TextLink>
                    </template>

                    <!-- Delete action -->
                    <Button 
                        size="sm" 
                        variant="destructive" 
                        :disabled="selectedRows.length === 1 && getSelectedUser()?.isSelf"
                        :class="{'opacity-50 cursor-not-allowed': selectedRows.length === 1 && getSelectedUser()?.isSelf}"
                        :title="selectedRows.length === 1 && getSelectedUser()?.isSelf ? __('You cannot delete your own account') : ''"
                        @click="deleteSelected" 
                        class="flex gap-1 h-7 text-xs rounded-lg"
                    >
                        <HugeiconsIcon :icon="Delete01Icon" :size="13" color="currentColor" /> {{ __('Delete') }}
                    </Button>
                </div>

                <!-- Add User Button -->
                <Button 
                    size="sm" 
                    class="bg-gradient-to-r from-primary to-blue-600 hover:from-primary/90 hover:to-blue-700 !text-white font-bold shadow-[0_4px_14px_rgba(67,97,238,0.35)] rounded-xl px-4 py-2 flex items-center gap-1.5 cursor-pointer"
                    @click="showCreateModal = true"
                >
                    <HugeiconsIcon :icon="PlusSignIcon" :size="16" color="currentColor" /> {{ __('Add User') }}
                </Button>
            </div>
        </div>

        <!-- Table Panel with Vue3Datatable & Pagination -->
        <div class="panel border-0 p-5">
            <!-- Search Control -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-4">
                <div class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                    {{ __('Registered Users') }}
                </div>

                <div class="relative w-full sm:w-64">
                    <input type="text" v-model="searchQuery" @input="onSearchInput"
                        :placeholder="__('Filter users...')"
                        class="form-input text-xs pl-8 pr-3 py-1.5 rounded-xl border-gray-200 dark:border-gray-700 bg-gray-50/80 dark:bg-gray-800/80 w-full" />
                    <HugeiconsIcon :icon="Search01Icon" :size="14" color="currentColor"
                        class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" />
                </div>
            </div>

            <!-- Vue3Datatable Component in Server Mode -->
            <div class="datatable">
                <Vue3Datatable 
                    ref="datatableRef" 
                    :isServerMode="true" 
                    :loading="loading"
                    :totalRows="props.totalRows ?? (props.users || []).length" 
                    :rows="users || []"
                    :columns="cols" 
                    :hasCheckbox="true" 
                    :search="searchQuery" 
                    :page="currentPage"
                    :pageSize="pageSize" 
                    :pageSizeOptions="[5, 10, 20, 50, 100]" 
                    :showPageSize="true" 
                    :pagination="true"
                    :showNumbers="true" 
                    :showFirstPage="true" 
                    :showLastPage="true" 
                    :sortColumn="sortColumn"
                    :sortDirection="sortDirection" 
                    skin="bh-table-hover"
                    :paginationInfo="__('Showing') + ' {0} ' + __('to') + ' {1} ' + __('of') + ' {2} ' + __('entries')"
                    :noDataContent="__('No users found.')" 
                    @change="onServerChange" 
                    @rowSelect="onRowSelect"
                >
                    <template #fullName="data">
                        <div class="font-medium text-gray-900 dark:text-white flex items-center gap-1.5">
                            {{ data.value.fullName }}
                            <span v-if="data.value.isSelf" class="px-1.5 py-0.5 text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded">YOU</span>
                        </div>
                        <div class="text-xs text-gray-500">{{ data.value.email }}</div>
                    </template>

                    <template #role="data">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                              :class="{
                                  'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300': data.value.role.slug === 'ROLE_ADMIN',
                                  'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': data.value.role.slug === 'ROLE_RECRUITER',
                                  'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': data.value.role.slug === 'ROLE_CANDIDATE',
                              }">
                            {{ __(data.value.role.name) }}
                        </span>
                    </template>

                    <template #isBlocked="data">
                        <span v-if="data.value.isBlocked" class="px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded text-xs font-medium">
                            {{ __('Blocked') }}
                        </span>
                        <span v-else class="px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded text-xs font-medium">
                            {{ __('Active') }}
                        </span>
                    </template>

                    <template #isVerified="data">
                        <span v-if="data.value.isVerified" class="text-green-600 text-xs font-semibold">{{ __('Yes') }}</span>
                        <span v-else class="text-yellow-600 text-xs font-semibold">{{ __('Pending') }}</span>
                    </template>

                    <template #profile="data">
                        <Link 
                            v-if="data.value.candidateProfileId" 
                            :href="route('app_profile_candidate_admin', { id: data.value.candidateProfileId })" 
                            class="text-blue-600 hover:underline text-xs font-medium"
                        >
                            {{ __('Open Profile') }} &rarr;
                        </Link>
                        <span v-else class="text-xs text-gray-400">{{ __('None') }}</span>
                    </template>
                </Vue3Datatable>
            </div>
        </div>

        <!-- Create User Modal -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs animate-fadeIn">
            <div class="bg-white dark:bg-[#0e1726] rounded-2xl shadow-2xl border border-gray-100 dark:border-gray-800 w-full max-w-md p-6 space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                    <h4 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="inline-block w-2 h-5 rounded-full bg-primary"></span>
                        {{ __('Add New User') }}
                    </h4>
                    <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition cursor-pointer">
                        <HugeiconsIcon :icon="Cancel01Icon" :size="18" />
                    </button>
                </div>

                <form @submit.prevent="submitCreateUser" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('First Name') }}</label>
                            <input type="text" v-model.trim="createForm.firstName" class="form-input text-xs w-full rounded-xl border-gray-200 dark:border-gray-700" placeholder="John" />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Last Name') }}</label>
                            <input type="text" v-model.trim="createForm.lastName" class="form-input text-xs w-full rounded-xl border-gray-200 dark:border-gray-700" placeholder="Doe" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Email Address') }} <span class="text-red-500">*</span></label>
                        <input type="email" v-model.trim="createForm.email" required class="form-input text-xs w-full rounded-xl border-gray-200 dark:border-gray-700" placeholder="user@example.com" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Password') }} <span class="text-red-500">*</span></label>
                        <input type="password" v-model="createForm.password" required minlength="6" class="form-input text-xs w-full rounded-xl border-gray-200 dark:border-gray-700" placeholder="Minimum 6 characters" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Role') }} <span class="text-red-500">*</span></label>
                        <select v-model="createForm.roleSlug" class="form-select text-xs w-full rounded-xl border-gray-200 dark:border-gray-700">
                            <option v-for="r in roles" :key="r.id" :value="r.slug">{{ __(r.name) }}</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <Button type="button" variant="outline" size="sm" class="rounded-xl text-xs" @click="showCreateModal = false">
                            {{ __('Cancel') }}
                        </Button>
                        <Button type="submit" size="sm" :disabled="isSubmittingCreate" class="bg-primary hover:bg-primary/90 text-white font-bold rounded-xl text-xs px-4">
                            <span v-if="isSubmittingCreate">{{ __('Creating...') }}</span>
                            <span v-else>{{ __('Create User') }}</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
