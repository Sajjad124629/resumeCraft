<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/Components/ui/button';
import TextLink from '@/Components/TextLink.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { route } from '@/route';
import { 
    UserAccountIcon, 
    Delete01Icon, 
    SecurityBlockIcon, 
    CheckmarkCircle02Icon, 
    PencilEdit02Icon,
    Shield01Icon
} from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    users: any[];
    roles: any[];
}>();

const selectedRows = ref<number[]>([]);
const selectedRole = ref<string>('ROLE_CANDIDATE');

function toggleSelection(id: number) {
    const index = selectedRows.value.indexOf(id);
    if (index === -1) {
        selectedRows.value.push(id);
    } else {
        selectedRows.value.splice(index, 1);
    }
}

function toggleAll() {
    if (selectedRows.value.length === props.users.length) {
        selectedRows.value = [];
    } else {
        selectedRows.value = props.users.map(u => u.id);
    }
}

function getSelectedUser() {
    if (selectedRows.value.length === 1) {
        return props.users.find(u => u.id === selectedRows.value[0]);
    }
    return null;
}

function toggleBlockSelected() {
    if (selectedRows.value.length === 0) return;
    const user = getSelectedUser();
    if (!user) return;

    router.post(route('app_admin_user_toggle_block', { id: user.id }), {}, {
        onSuccess: () => {
            selectedRows.value = [];
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
            }
        });
    }
}

function deleteSelected() {
    if (selectedRows.value.length === 0) return;
    const count = selectedRows.value.length;
    if (confirm(`Are you sure you want to delete ${count} selected user(s)? This will cascade delete their profile, projects, and CVs.`)) {
        selectedRows.value.forEach(id => {
            router.delete(route('app_admin_user_delete', { id }));
        });
        selectedRows.value = [];
    }
}
</script>

<template>
    <Head :title="__('User Management')" />

    <div class="pt-5">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h5 class="text-xl font-bold dark:text-white-light">{{ __('User Management') }}</h5>
                <p class="text-sm text-gray-500">Manage all registered users, roles, block access, and edit candidate profiles.</p>
            </div>
            <div class="flex gap-2 h-9 items-center">
                <!-- Toolbar for Selected Users -->
                <div v-if="selectedRows.length > 0" class="flex items-center gap-2 px-3 h-full bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                    <span class="text-xs text-blue-800 dark:text-blue-300 font-semibold mr-1">{{ selectedRows.length }} {{ __('selected') }}</span>
                    
                    <!-- Single User Actions -->
                    <template v-if="selectedRows.length === 1">
                        <Button size="sm" variant="outline" class="flex gap-1 h-7 text-xs" @click="toggleBlockSelected">
                            <HugeiconsIcon :icon="SecurityBlockIcon" :size="14" color="currentColor" />
                            {{ getSelectedUser()?.isBlocked ? __('Unblock') : __('Block') }}
                        </Button>

                        <!-- Change Role -->
                        <div class="flex items-center gap-1">
                            <select v-model="selectedRole" class="form-select h-7 text-xs py-0 pl-2 pr-6 border-gray-300 rounded">
                                <option v-for="r in roles" :key="r.id" :value="r.slug">{{ __(r.name) }}</option>
                            </select>
                            <Button size="sm" variant="outline" class="h-7 text-xs px-2" @click="changeRoleSelected">
                                {{ __('Set Role') }}
                            </Button>
                        </div>

                        <!-- Edit Candidate Profile if user has one -->
                        <TextLink v-if="getSelectedUser()?.candidateProfileId" :href="route('app_profile_candidate_admin', { id: getSelectedUser()?.candidateProfileId })">
                            <Button size="sm" variant="outline" class="flex gap-1 h-7 text-xs">
                                <HugeiconsIcon :icon="PencilEdit02Icon" :size="14" color="currentColor" /> {{ __('Edit Profile') }}
                            </Button>
                        </TextLink>
                    </template>

                    <!-- Delete action -->
                    <Button size="sm" variant="destructive" @click="deleteSelected" class="flex gap-1 h-7 text-xs">
                        <HugeiconsIcon :icon="Delete01Icon" :size="14" color="currentColor" /> {{ __('Delete') }}
                    </Button>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="table-responsive">
                <table class="w-full text-left table-auto border-collapse">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:bg-gray-800">
                            <th class="p-3 w-10">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedRows.length === users.length && users.length > 0" 
                                    @change="toggleAll" 
                                    class="form-checkbox" 
                                />
                            </th>
                            <th class="p-3 font-semibold">{{ __('User') }}</th>
                            <th class="p-3 font-semibold">{{ __('Role') }}</th>
                            <th class="p-3 font-semibold text-center">{{ __('Status') }}</th>
                            <th class="p-3 font-semibold text-center">{{ __('Verified') }}</th>
                            <th class="p-3 font-semibold text-right">{{ __('Profile Link') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr 
                            v-for="u in users" 
                            :key="u.id" 
                            class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50" 
                            :class="{'bg-blue-50/50 dark:bg-blue-900/10': selectedRows.includes(u.id)}"
                        >
                            <td class="p-3">
                                <input 
                                    type="checkbox" 
                                    :value="u.id" 
                                    :checked="selectedRows.includes(u.id)" 
                                    @change="toggleSelection(u.id)" 
                                    class="form-checkbox" 
                                />
                            </td>
                            <td class="p-3">
                                <div class="font-medium text-gray-900 dark:text-white flex items-center gap-1.5">
                                    {{ u.fullName }}
                                    <span v-if="u.isSelf" class="px-1.5 py-0.5 text-[10px] font-bold bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 rounded">YOU</span>
                                </div>
                                <div class="text-xs text-gray-500">{{ u.email }}</div>
                            </td>
                            <td class="p-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300': u.role.slug === 'ROLE_ADMIN',
                                          'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300': u.role.slug === 'ROLE_RECRUITER',
                                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300': u.role.slug === 'ROLE_CANDIDATE',
                                      }">
                                    {{ __(u.role.name) }}
                                </span>
                            </td>
                            <td class="p-3 text-center">
                                <span v-if="u.isBlocked" class="px-2 py-0.5 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 rounded text-xs font-medium">
                                    {{ __('Blocked') }}
                                </span>
                                <span v-else class="px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 rounded text-xs font-medium">
                                    {{ __('Active') }}
                                </span>
                            </td>
                            <td class="p-3 text-center">
                                <span v-if="u.isVerified" class="text-green-600 text-xs font-semibold">{{ __('Yes') }}</span>
                                <span v-else class="text-yellow-600 text-xs font-semibold">{{ __('Pending') }}</span>
                            </td>
                            <td class="p-3 text-right">
                                <Link 
                                    v-if="u.candidateProfileId" 
                                    :href="route('app_profile_candidate_admin', { id: u.candidateProfileId })" 
                                    class="text-blue-600 hover:underline text-xs font-medium"
                                >
                                    {{ __('Open Profile') }} &rarr;
                                </Link>
                                <span v-else class="text-xs text-gray-400">{{ __('None') }}</span>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="6" class="p-6 text-center text-gray-500">{{ __('No users found.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
