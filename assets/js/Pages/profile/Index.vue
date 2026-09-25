<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router, Link, usePage } from '@inertiajs/vue3';
import { route } from '@/route';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import StaticSelect from '@/Components/ui/select/StaticSelect.vue';
import CloudImageUpload from '@/Components/CloudImageUpload.vue';
import Swal from 'sweetalert2';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    PlusSignIcon,
    PencilEdit02Icon,
    Delete01Icon,
    Search01Icon,
    Briefcase08Icon,
    UserAccountIcon,
    FilterIcon,
    ViewIcon
} from '@hugeicons/core-free-icons';
import { ref, watch, computed } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import AchievementPanel from './AchievementPanel.vue';

defineOptions({ layout: AppLayout });

const page = usePage();

const props = defineProps<{
    profile: any;
    projects: any[];
    candidateAttributes: any[];
    availableAttributes: any[];
    achievements: any[];
    cvs?: any[];
    isAdminEditing?: boolean;
    targetCandidateId?: number;
}>();

const headTitle = computed(() => {
    return props.isAdminEditing
        ? `Edit Profile - ${props.profile.firstName} ${props.profile.lastName}`
        : 'My Profile';
});

const headingText = computed(() => {
    return props.isAdminEditing
        ? `Candidate Profile: ${props.profile.firstName} ${props.profile.lastName}`
        : 'My Candidate Profile';
});

// --- Section 1: Me (Auto-saving) ---
const meForm = useForm({
    firstName: props.profile.firstName || '',
    lastName: props.profile.lastName || '',
    location: props.profile.location || '',
    photo: props.profile.photo || '',
    version: props.profile.version || 1,
    candidateId: props.targetCandidateId,
});

const isSaving = ref(false);
const saveError = ref('');

const performSaveMe = async () => {
    isSaving.value = true;
    saveError.value = '';

    try {
        const response = await fetch(route('app_profile_update_me'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(meForm.data()),
        });

        const data = await response.json();

        if (response.status === 409) {
            saveError.value = 'Conflict: This profile was updated in another session. Please reload to view latest changes.';
            return;
        }

        if (response.ok && data.success) {
            meForm.version = data.version;
            // Instantly sync with Inertia auth.user so the Header avatar updates in real-time
            if (page.props.auth && (page.props.auth as any).user) {
                const u = (page.props.auth as any).user;
                u.photo = meForm.photo;
                u.avatar = meForm.photo;
                if (u.user_detail) {
                    u.user_detail.image = meForm.photo;
                }
            }
        } else {
            saveError.value = data.error || 'Failed to save changes.';
        }
    } catch (e) {
        saveError.value = 'Network error occurred while saving.';
    } finally {
        isSaving.value = false;
    }
};

const saveMeSectionDebounced = useDebounceFn(performSaveMe, 6000);

watch(() => [meForm.firstName, meForm.lastName, meForm.location], () => {
    saveMeSectionDebounced();
});

watch(() => meForm.photo, () => {
    // When photo is uploaded, changed or removed, save immediately
    performSaveMe();
});


// --- Toast and Modal Confirm Helpers ---
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

async function confirmAction(title: string, text: string = "You won't be able to revert this!"): Promise<boolean> {
    const result = await Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete!',
        cancelButtonText: 'Cancel',
        padding: '2em',
        customClass: { popup: 'sweet-alerts' },
    });
    return !!result.isConfirmed;
}

// --- Section 2: Info (Attributes) ---
const showAddAttributeModal = ref(false);
const attributeSearchPrefix = ref('');
const selectedCategoryFilter = ref<string>('all');
const recentAttributeIds = ref<number[]>(JSON.parse(localStorage.getItem('rc_recent_attributes') || '[]'));
const selectedAttributes = ref<number[]>([]);

const newAttrForm = ref({
    attributeId: null as number | null,
    value: '' as any,
    periodStart: '',
    periodEnd: '',
});

const availableCategories = computed(() => {
    const cats = new Set<string>();
    props.availableAttributes.forEach((a: any) => {
        if (a.category?.name) cats.add(a.category.name);
    });
    return Array.from(cats);
});

const unaddedAttributes = computed(() => {
    return props.availableAttributes.filter((a: any) =>
        !props.candidateAttributes.some((ca: any) => ca.attributeId === a.id)
    );
});

const filteredAttributesToSelect = computed(() => {
    let list = unaddedAttributes.value;

    if (selectedCategoryFilter.value !== 'all') {
        list = list.filter((a: any) => a.category?.name === selectedCategoryFilter.value);
    }

    if (attributeSearchPrefix.value.trim()) {
        const q = attributeSearchPrefix.value.toLowerCase().trim();
        list = list.filter((a: any) => a.name.toLowerCase().startsWith(q) || a.name.toLowerCase().includes(q));
    }

    return list;
});

const recentAttributesList = computed(() => {
    return unaddedAttributes.value.filter((a: any) => recentAttributeIds.value.includes(a.id));
});

const isSubmittingAdd = ref(false);
const isSubmittingEdit = ref(false);

const showEditAttributeModal = ref(false);
const editingAttrObj = ref<any>(null);
const editModalValue = ref<any>('');
const editModalPeriodStart = ref('');
const editModalPeriodEnd = ref('');

function selectAttributeToAdd(attr: any) {
    newAttrForm.value.attributeId = attr.id;
    if (attr.type === 'boolean') {
        newAttrForm.value.value = false;
    } else if (attr.type === 'period') {
        newAttrForm.value.periodStart = '';
        newAttrForm.value.periodEnd = '';
    } else {
        newAttrForm.value.value = '';
    }
}

async function submitNewAttribute() {
    if (!newAttrForm.value.attributeId) return;

    let finalVal = newAttrForm.value.value;
    const selectedAttrObj = props.availableAttributes.find((a: any) => a.id === newAttrForm.value.attributeId);
    if (selectedAttrObj?.type === 'period') {
        finalVal = {
            start: newAttrForm.value.periodStart,
            end: newAttrForm.value.periodEnd,
        };
    }

    isSubmittingAdd.value = true;
    try {
        const response = await fetch(route('app_profile_update_attr'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                candidateId: props.targetCandidateId,
                attributeId: newAttrForm.value.attributeId,
                value: finalVal,
            })
        });

        if (response.ok) {
            const recent = Array.from(new Set([newAttrForm.value.attributeId, ...recentAttributeIds.value])).slice(0, 5);
            recentAttributeIds.value = recent;
            localStorage.setItem('rc_recent_attributes', JSON.stringify(recent));

            showAddAttributeModal.value = false;
            newAttrForm.value = { attributeId: null, value: '', periodStart: '', periodEnd: '' };
            showToast('Attribute added successfully!');
            router.reload({ only: ['candidateAttributes', 'availableAttributes'] });
        } else {
            showToast('Failed to add attribute.', 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('Network error while adding attribute.', 'error');
    } finally {
        isSubmittingAdd.value = false;
    }
}

function toggleAttributeSelection(id: number) {
    const idx = selectedAttributes.value.indexOf(id);
    if (idx === -1) selectedAttributes.value.push(id);
    else selectedAttributes.value.splice(idx, 1);
}

function toggleAllAttributes() {
    if (selectedAttributes.value.length === props.candidateAttributes.length) {
        selectedAttributes.value = [];
    } else {
        selectedAttributes.value = props.candidateAttributes.map(a => a.attributeId);
    }
}

function openEditModal(attr: any) {
    editingAttrObj.value = attr;
    if (attr.type === 'period' && attr.value) {
        editModalPeriodStart.value = attr.value.start || '';
        editModalPeriodEnd.value = attr.value.end || '';
    } else {
        editModalValue.value = attr.value;
    }
    showEditAttributeModal.value = true;
}

function editSelectedAttribute() {
    if (selectedAttributes.value.length === 1) {
        const attr = props.candidateAttributes.find(a => a.attributeId === selectedAttributes.value[0]);
        if (attr) openEditModal(attr);
    }
}

async function saveAttributeFromModal() {
    if (!editingAttrObj.value) return;
    const attr = editingAttrObj.value;

    let finalVal = editModalValue.value;
    if (attr.type === 'period') {
        finalVal = {
            start: editModalPeriodStart.value,
            end: editModalPeriodEnd.value,
        };
    }

    isSubmittingEdit.value = true;
    try {
        const response = await fetch(route('app_profile_update_attr'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                candidateId: props.targetCandidateId,
                attributeId: attr.attributeId,
                value: finalVal,
                version: attr.valVersion
            })
        });

        if (response.ok) {
            const data = await response.json();
            attr.value = finalVal;
            attr.valVersion = data.valVersion;
            showEditAttributeModal.value = false;
            editingAttrObj.value = null;
            showToast('Attribute saved successfully!');
        } else if (response.status === 409) {
            showToast('Conflict: The value was modified elsewhere. Please refresh.', 'warning');
        } else {
            showToast('Failed to save attribute.', 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('Network error while saving attribute.', 'error');
    } finally {
        isSubmittingEdit.value = false;
    }
}

async function deleteSelectedAttributes() {
    if (selectedAttributes.value.length === 0) return;
    const count = selectedAttributes.value.length;
    if (await confirmAction(`Remove ${count} attribute(s) from your profile?`)) {
        router.post(route('app_profile_batch_delete_attr'), {
            candidateId: props.targetCandidateId,
            ids: selectedAttributes.value,
        }, {
            onSuccess: () => {
                selectedAttributes.value = [];
                showToast(`${count} attribute(s) removed successfully!`);
            },
            onError: () => {
                showToast('Failed to remove attributes.', 'error');
            }
        });
    }
}

async function removeAttribute(attrId: number) {
    if (await confirmAction('Remove this attribute from your profile?')) {
        router.delete(route('app_profile_delete_attr', { attributeId: attrId, ...(props.targetCandidateId ? { candidateId: props.targetCandidateId } : {}) }), {
            onSuccess: () => {
                selectedAttributes.value = selectedAttributes.value.filter(id => id !== attrId);
                showToast('Attribute removed successfully!');
            },
            onError: () => {
                showToast('Failed to remove attribute.', 'error');
            }
        });
    }
}


// --- Section 3: Projects ---
const selectedProjects = ref<number[]>([]);

function toggleProjectSelection(id: number) {
    const idx = selectedProjects.value.indexOf(id);
    if (idx === -1) selectedProjects.value.push(id);
    else selectedProjects.value.splice(idx, 1);
}

function toggleAllProjects() {
    if (selectedProjects.value.length === props.projects.length) {
        selectedProjects.value = [];
    } else {
        selectedProjects.value = props.projects.map(p => p.id);
    }
}

function editSelectedProject() {
    if (selectedProjects.value.length === 1) {
        router.visit(route('app_profile_project_edit_view', { id: selectedProjects.value[0] }));
    }
}

async function deleteSelectedProjects() {
    if (selectedProjects.value.length === 0) return;
    const count = selectedProjects.value.length;
    if (await confirmAction(`Delete ${count} project(s)?`)) {
        router.post(route('app_profile_project_batch_delete'), {
            candidateId: props.targetCandidateId,
            ids: selectedProjects.value,
        }, {
            onSuccess: () => {
                selectedProjects.value = [];
                showToast(`${count} project(s) deleted successfully!`);
            },
            onError: () => {
                showToast('Failed to delete projects.', 'error');
            }
        });
    }
}


// --- Section 4: CVs Table & Toolbar ---
const selectedCvs = ref<number[]>([]);

function toggleCvSelection(id: number) {
    const idx = selectedCvs.value.indexOf(id);
    if (idx === -1) {
        selectedCvs.value.push(id);
    } else {
        selectedCvs.value.splice(idx, 1);
    }
}

function toggleAllCvs() {
    if (selectedCvs.value.length === (props.cvs?.length || 0)) {
        selectedCvs.value = [];
    } else {
        selectedCvs.value = (props.cvs || []).map((c: any) => c.id);
    }
}

function editSelectedCv() {
    if (selectedCvs.value.length === 1) {
        router.visit(route('app_cv_show', { id: selectedCvs.value[0] }));
    }
}

function viewSelectedCvPublic() {
    if (selectedCvs.value.length === 1) {
        window.open(route('app_cv_public_show', { id: selectedCvs.value[0] }), '_blank');
    }
}

function downloadSelectedCvPdf() {
    if (selectedCvs.value.length === 1) {
        window.open(route('app_cv_pdf', { id: selectedCvs.value[0] }), '_blank');
    }
}

async function deleteSelectedCvs() {
    if (selectedCvs.value.length === 0) return;
    const count = selectedCvs.value.length;
    if (await confirmAction(`Are you sure you want to delete ${count} CV(s)?`)) {
        router.post(route('app_cv_batch_delete'), {
            ids: selectedCvs.value,
        }, {
            onSuccess: () => {
                selectedCvs.value = [];
                showToast(`${count} CV(s) deleted successfully!`);
            },
            onError: () => {
                showToast('Failed to delete CV(s).', 'error');
            }
        });
    }
}
</script>

<template>

    <Head :title="headTitle" />

    <div class="pt-5 space-y-6">
        <!-- Admin Banner if admin is editing another candidate -->
        <div v-if="isAdminEditing"
            class="p-4 bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-800 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="px-2 py-0.5 bg-purple-600 text-white rounded text-xs font-bold uppercase">Admin Mode</span>
                <span class="text-sm font-semibold text-purple-900 dark:text-purple-200">
                    You are editing candidate: {{ profile.firstName }} {{ profile.lastName }}
                </span>
            </div>
            <TextLink :href="route('app_admin_users')">
                <Button size="sm" variant="outline">&larr; Back to Users</Button>
            </TextLink>
        </div>

        <div class="flex justify-between items-center mb-4">
            <h2
                class="text-2xl font-black tracking-tight text-gray-900 dark:text-white-light flex items-center gap-2.5">
                <span
                    class="inline-block w-2.5 h-7 rounded-full bg-gradient-to-b from-primary to-blue-400 shadow-[0_0_12px_rgba(67,97,238,0.5)]"></span>
                {{ headingText }}
            </h2>
            <TextLink v-if="profile.id" :href="route('app_profile_public', { id: profile.id })">
                <Button size="sm" variant="outline" class="flex gap-1.5 items-center">
                    <HugeiconsIcon :icon="ViewIcon" :size="16" /> View Public Profile
                </Button>
            </TextLink>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Column: Section 1 (Me) + Badges (Wider: 5 cols on lg, 4 cols on xl) -->
            <div class="lg:col-span-5 xl:col-span-4 space-y-6">
                <!-- Section 1: Me (Auto-Saving) -->
                <div class="panel relative">
                    <div class="flex items-center gap-2.5 border-b pb-3 mb-4">
                        <span
                            class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-blue-500 to-cyan-400 shadow-[0_0_10px_rgba(59,130,246,0.5)]"></span>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white-light">1. Me (Built-in Attributes)
                        </h3>
                    </div>

                    <div class="absolute top-4 right-4 text-xs font-semibold"
                        :class="{ 'text-gray-400': !isSaving && !saveError, 'text-green-500': isSaving, 'text-red-500': saveError }">
                        <span v-if="saveError">{{ saveError }}</span>
                        <span v-else-if="isSaving">{{ __('Saving...') }}</span>
                        <span v-else>{{ __('Saved') }}</span>
                    </div>

                    <div class="space-y-4">
                        <!-- Photo Drag-n-drop Cloud Upload -->
                        <div class="mb-4">
                            <CloudImageUpload v-model="meForm.photo" :label="__('Personal Photo')" />
                        </div>

                        <div class="mb-4">
                            <Label :isRequired="true" for="firstName">{{ __('First Name') }}</Label>
                            <Input id="firstName" type="text" v-model.trim="meForm.firstName"
                                :placeholder="__('First Name')" class="form-input mt-1 block w-full" required />
                            <InputError :message="meForm.errors.firstName" />
                        </div>
                        <div class="mb-4">
                            <Label :isRequired="true" for="lastName">{{ __('Last Name') }}</Label>
                            <Input id="lastName" type="text" v-model.trim="meForm.lastName"
                                :placeholder="__('Last Name')" class="form-input mt-1 block w-full" required />
                            <InputError :message="meForm.errors.lastName" />
                        </div>
                        <div class="mb-4">
                            <Label for="location">{{ __('Location') }}</Label>
                            <Input id="location" type="text" v-model.trim="meForm.location"
                                :placeholder="__('e.g. Remote, NY')" class="form-input mt-1 block w-full" />
                            <InputError :message="meForm.errors.location" />
                        </div>
                    </div>
                </div>

                <!-- Badges & Achievements -->
                <AchievementPanel :achievements="props.achievements" />
            </div>

            <!-- Right Column: Section 2 (Info) + Section 3 (Projects) + Section 4 (CVs) -->
            <div class="lg:col-span-7 xl:col-span-8 space-y-6">
                <!-- Section 2: Info (Attributes Table - Anti-Penalty Compliant) -->
                <div class="panel">
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b pb-3 mb-4 gap-3">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-indigo-500 to-purple-500 shadow-[0_0_10px_rgba(99,102,241,0.5)]"></span>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white-light">2. Info (Skills &
                                    Custom Attributes)</h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 pl-5">Add or edit attributes from the reusable library.
                                Select a row to edit or delete.</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Top Toolbar for Multi-select -->
                            <div v-if="selectedAttributes.length > 0"
                                class="flex items-center gap-2 px-3 h-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg animate-fadeIn">
                                <span class="text-xs text-blue-800 dark:text-blue-300 font-semibold mr-1">{{
                                    selectedAttributes.length }} {{ __('selected') }}</span>
                                <Button v-if="selectedAttributes.length === 1" size="sm" variant="outline"
                                    class="h-6 text-xs px-2" @click="editSelectedAttribute">
                                    <HugeiconsIcon :icon="PencilEdit02Icon" :size="12" class="mr-1" /> {{ __('Edit') }}
                                </Button>
                                <Button size="sm" variant="destructive" class="h-6 text-xs px-2"
                                    @click="deleteSelectedAttributes">
                                    <HugeiconsIcon :icon="Delete01Icon" :size="12" class="mr-1" /> {{ __('Delete') }}
                                </Button>
                            </div>

                            <Button variant="default" size="sm" class="h-8 text-xs flex gap-1 items-center"
                                @click="showAddAttributeModal = true">
                                <HugeiconsIcon :icon="PlusSignIcon" :size="16" color="currentColor" />
                                <span>{{ __('Add Attribute') }}</span>
                            </Button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="w-full text-left table-auto border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-800 text-xs">
                                    <th class="p-2.5 w-10">
                                        <input type="checkbox"
                                            :checked="selectedAttributes.length === candidateAttributes.length && candidateAttributes.length > 0"
                                            @change="toggleAllAttributes" class="form-checkbox" />
                                    </th>
                                    <th class="p-2.5 font-semibold">{{ __('Attribute') }}</th>
                                    <th class="p-2.5 font-semibold">{{ __('Type') }}</th>
                                    <th class="p-2.5 font-semibold">{{ __('Value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-for="attr in candidateAttributes" :key="attr.attributeId">
                                    <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer"
                                        :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': selectedAttributes.includes(attr.attributeId) }"
                                        @click="toggleAttributeSelection(attr.attributeId)"
                                        @dblclick="openEditModal(attr)">
                                        <td class="p-2.5" @click.stop>
                                            <input type="checkbox" :value="attr.attributeId"
                                                :checked="selectedAttributes.includes(attr.attributeId)"
                                                @change="toggleAttributeSelection(attr.attributeId)"
                                                class="form-checkbox" />
                                        </td>
                                        <td class="p-2.5 font-semibold text-gray-900 dark:text-gray-100">
                                            {{ attr.name }}
                                        </td>
                                        <td class="p-2.5">
                                            <span
                                                class="px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                                {{ attr.type }}
                                            </span>
                                        </td>
                                        <td class="p-2.5">
                                            <div class="truncate max-w-md">
                                                <img v-if="attr.type === 'image' && attr.value" :src="attr.value"
                                                    class="h-8 w-8 object-cover rounded border" alt="Value" />
                                                <span v-else-if="attr.type === 'boolean'"
                                                    class="px-2 py-0.5 rounded text-xs font-semibold"
                                                    :class="attr.value ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                                    {{ attr.value ? __('Yes') : __('No') }}
                                                </span>
                                                <span v-else-if="attr.type === 'period' && attr.value"
                                                    class="text-sm font-medium">
                                                    {{ attr.value.start }} &rarr; {{ attr.value.end || __('Present') }}
                                                </span>
                                                <span v-else class="text-sm text-gray-800 dark:text-gray-200">
                                                    {{ attr.value || __('Not set') }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr v-if="candidateAttributes.length === 0">
                                    <td colspan="4" class="p-6 text-center text-gray-500 text-sm">
                                        {{ __('No attributes added yet. Click "Add Attribute" to select from the library!') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section 3: Projects (Anti-Penalty Compliant Table + Top Toolbar) -->
                <div class="panel">
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b pb-3 mb-4 gap-3">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-emerald-500 to-teal-400 shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white-light">3. Projects</h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 pl-5">Filtered automatically into generated CVs using
                                technology tags.
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <!-- Top Toolbar for Multi-select -->
                            <div v-if="selectedProjects.length > 0"
                                class="flex items-center gap-2 px-3 h-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg animate-fadeIn">
                                <span class="text-xs text-blue-800 dark:text-blue-300 font-semibold mr-1">{{
                                    selectedProjects.length }} {{ __('selected') }}</span>
                                <Button v-if="selectedProjects.length === 1" size="sm" variant="outline"
                                    class="h-6 text-xs px-2" @click="editSelectedProject">
                                    <HugeiconsIcon :icon="PencilEdit02Icon" :size="12" class="mr-1" /> {{ __('Edit') }}
                                </Button>
                                <Button size="sm" variant="destructive" class="h-6 text-xs px-2"
                                    @click="deleteSelectedProjects">
                                    <HugeiconsIcon :icon="Delete01Icon" :size="12" class="mr-1" /> {{ __('Delete') }}
                                </Button>
                            </div>

                            <TextLink :href="route('app_profile_project_create_view')">
                                <Button variant="default" size="sm" class="h-8 text-xs flex gap-1 items-center">
                                    <HugeiconsIcon :icon="PlusSignIcon" :size="16" /> {{ __('Add Project') }}
                                </Button>
                            </TextLink>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="w-full text-left table-auto border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-800 text-xs">
                                    <th class="p-2.5 w-10">
                                        <input type="checkbox"
                                            :checked="selectedProjects.length === projects.length && projects.length > 0"
                                            @change="toggleAllProjects" class="form-checkbox" />
                                    </th>
                                    <th class="p-2.5 font-semibold">{{ __('Name') }}</th>
                                    <th class="p-2.5 font-semibold">{{ __('Period') }}</th>
                                    <th class="p-2.5 font-semibold">{{ __('Technologies') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="p in projects" :key="p.id"
                                    class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer"
                                    :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': selectedProjects.includes(p.id) }"
                                    @click="toggleProjectSelection(p.id)"
                                    @dblclick="router.visit(route('app_profile_project_edit_view', { id: p.id }))">
                                    <td class="p-2.5" @click.stop>
                                        <input type="checkbox" :value="p.id" :checked="selectedProjects.includes(p.id)"
                                            @change="toggleProjectSelection(p.id)" class="form-checkbox" />
                                    </td>
                                    <td class="p-2.5">
                                        <div class="font-bold text-gray-900 dark:text-gray-100">{{ p.name }}</div>
                                        <div class="text-xs text-gray-500 line-clamp-1" v-html="p.description"></div>
                                    </td>
                                    <td class="p-2.5 text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap">
                                        {{ p.periodStart }} &rarr; {{ p.periodEnd || 'Present' }}
                                    </td>
                                    <td class="p-2.5">
                                        <div class="flex gap-1.5 flex-wrap">
                                            <span v-for="tag in p.tags" :key="tag"
                                                class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded text-[11px] font-medium">
                                                {{ tag }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="projects.length === 0">
                                    <td colspan="4" class="p-6 text-center text-gray-500 text-sm">
                                        {{ __("You haven't added any projects yet.") }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Section 4: CVs (Required 4th Section - Anti-Penalty Compliant Table + Top Toolbar) -->
                <div class="panel">
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b pb-3 mb-4 gap-3">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-amber-500 to-orange-400 shadow-[0_0_10px_rgba(245,158,11,0.5)]"></span>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white-light">4. My Generated CVs
                                </h3>
                            </div>
                            <p class="text-xs text-gray-500 mt-1 pl-5">CVs tailored for positions you have access to.
                            </p>
                        </div>

                        <!-- Toolbar for CV Selection (Anti-Penalty compliant) -->
                        <div class="flex items-center gap-2">
                            <div v-if="selectedCvs.length > 0"
                                class="flex items-center gap-2 px-3 h-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg animate-fadeIn">
                                <span class="text-xs text-blue-800 dark:text-blue-300 font-semibold mr-1">{{
                                    selectedCvs.length }} {{ __('selected') }}</span>
                                <Button v-if="selectedCvs.length === 1" size="sm" variant="outline"
                                    class="h-6 text-xs px-2" @click="editSelectedCv">
                                    <HugeiconsIcon :icon="PencilEdit02Icon" :size="12" class="mr-1" />
                                    <span>{{ __('Edit CV') }}</span>
                                </Button>
                                <Button v-if="selectedCvs.length === 1" size="sm" variant="outline"
                                    class="h-6 text-xs px-2" @click="viewSelectedCvPublic">
                                    <HugeiconsIcon :icon="ViewIcon" :size="12" class="mr-1" />
                                    <span>{{ __('View Public') }}</span>
                                </Button>
                                <Button v-if="selectedCvs.length === 1" size="sm" variant="outline"
                                    class="h-6 text-xs px-2" @click="downloadSelectedCvPdf">
                                    <span>{{ __('PDF') }}</span>
                                </Button>
                                <Button size="sm" variant="destructive" class="h-6 text-xs px-2"
                                    @click="deleteSelectedCvs">
                                    <HugeiconsIcon :icon="Delete01Icon" :size="12" class="mr-1" />
                                    <span>{{ __('Delete') }}</span>
                                </Button>
                            </div>

                            <TextLink :href="route('app_position_index')">
                                <Button variant="default" size="sm" class="h-8 text-xs flex gap-1 items-center">
                                    <HugeiconsIcon :icon="PlusSignIcon" :size="16" />
                                    <span>{{ __('Browse Positions to Apply') }}</span>
                                </Button>
                            </TextLink>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="w-full text-left table-auto border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-800 text-xs">
                                    <th class="p-2.5 w-10">
                                        <input type="checkbox"
                                            :checked="selectedCvs.length === (cvs?.length || 0) && (cvs?.length || 0) > 0"
                                            @change="toggleAllCvs" class="form-checkbox" />
                                    </th>
                                    <th class="p-2.5 font-semibold">{{ __('Position') }}</th>
                                    <th class="p-2.5 font-semibold">{{ __('Company') }}</th>
                                    <th class="p-2.5 font-semibold text-center">{{ __('Status') }}</th>
                                    <th class="p-2.5 font-semibold text-center">{{ __('Likes') }}</th>
                                    <th class="p-2.5 font-semibold text-right">{{ __('Created') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cv in cvs" :key="cv.id"
                                    class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer"
                                    :class="{ 'bg-blue-50/50 dark:bg-blue-900/10': selectedCvs.includes(cv.id) }"
                                    @click="toggleCvSelection(cv.id)" @dblclick="router.visit(route('app_cv_show', { id: cv.id }))">
                                    <td class="p-2.5" @click.stop>
                                        <input type="checkbox" :value="cv.id" :checked="selectedCvs.includes(cv.id)"
                                            @change="toggleCvSelection(cv.id)" class="form-checkbox" />
                                    </td>
                                    <td class="p-2.5">
                                        <Link :href="route('app_cv_show', { id: cv.id })" class="text-blue-600 hover:underline font-medium"
                                            @click.stop>
                                            {{ cv.positionTitle }}
                                        </Link>
                                    </td>
                                    <td class="p-2.5 text-gray-600 dark:text-gray-300">{{ cv.company ? cv.company :
                                        __('Confidential') }}</td>
                                    <td class="p-2.5 text-center">
                                        <span v-if="cv.status === 'draft'"
                                            class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs font-medium">{{
                                                __('Draft') }}</span>
                                        <span v-else
                                            class="px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs font-medium">{{
                                                __('Published') }}</span>
                                    </td>
                                    <td class="p-2.5 text-center font-semibold">{{ cv.likes }}</td>
                                    <td class="p-2.5 text-right text-xs text-gray-500">{{ cv.createdAt }}</td>
                                </tr>
                                <tr v-if="!cvs || cvs.length === 0">
                                    <td colspan="6" class="p-6 text-center text-gray-500 text-sm">
                                        {{ __('No CVs created yet. Explore available positions to apply!') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Attribute Modal with Prefix Lookup & Category Filtering -->
        <div v-if="showAddAttributeModal" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-xl w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b pb-3">
                    <h4 class="text-lg font-bold">{{ __('Add Attribute from Library') }}</h4>
                    <button @click="showAddAttributeModal = false"
                        class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <!-- Prefix Search & Category Filter -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <Label class="text-xs">{{ __('Lookup by Prefix') }}</Label>
                        <Input type="text" v-model="attributeSearchPrefix" :placeholder="__('Lookup by Prefix')"
                            class="form-input mt-1 h-8 text-xs w-full" />
                    </div>
                    <div>
                        <Label class="text-xs">{{ __('Filter by Category') }}</Label>
                        <select v-model="selectedCategoryFilter" class="form-select mt-1 h-8 text-xs w-full">
                            <option value="all">{{ __('All Categories') }}</option>
                            <option v-for="cat in availableCategories" :key="cat" :value="cat">{{ cat }}</option>
                        </select>
                    </div>
                </div>

                <!-- Recently Used Attributes -->
                <div v-if="recentAttributesList.length > 0 && !attributeSearchPrefix" class="space-y-1">
                    <span class="text-xs font-bold text-gray-400 uppercase">{{ __('Recently Used') }}</span>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="ra in recentAttributesList" :key="ra.id" type="button"
                            @click="selectAttributeToAdd(ra)" class="px-2.5 py-1 text-xs rounded-full border transition"
                            :class="newAttrForm.attributeId === ra.id ? 'bg-blue-600 text-white border-blue-600' : 'bg-gray-50 dark:bg-gray-700 hover:bg-gray-100'">
                            {{ ra.name }}
                        </button>
                    </div>
                </div>

                <!-- Filtered Attributes List -->
                <div class="space-y-1">
                    <span class="text-xs font-bold text-gray-400 uppercase">{{ __('Matching Attributes') }} ({{
                        filteredAttributesToSelect.length }})</span>
                    <div class="max-h-40 overflow-y-auto divide-y border rounded-lg dark:border-gray-700">
                        <div v-for="attr in filteredAttributesToSelect" :key="attr.id"
                            @click="selectAttributeToAdd(attr)"
                            class="p-2 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer flex justify-between items-center text-sm"
                            :class="{ 'bg-blue-100 dark:bg-blue-900/50 font-semibold': newAttrForm.attributeId === attr.id }">
                            <span>{{ attr.name }}</span>
                            <span
                                class="text-xs px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                {{ __(attr.type) }}
                            </span>
                        </div>
                        <div v-if="filteredAttributesToSelect.length === 0"
                            class="p-4 text-center text-gray-400 text-xs">
                            {{ __('No matching unadded attributes found.') }}
                        </div>
                    </div>
                </div>

                <!-- Value Input depending on selected attribute type -->
                <div v-if="newAttrForm.attributeId" class="border-t pt-4 space-y-3">
                    <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                        {{ __('Enter Value for:') }} {{availableAttributes.find(a => a.id ===
                            newAttrForm.attributeId)?.name}}
                    </div>

                    <!-- Boolean -->
                    <label v-if="availableAttributes.find(a => a.id === newAttrForm.attributeId)?.type === 'boolean'"
                        class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="newAttrForm.value"
                            class="form-checkbox h-5 w-5 text-blue-600 rounded" />
                        <span class="text-sm font-medium">{{ __('Yes') }} / Checked</span>
                    </label>

                    <!-- Image Cloud Drag-n-drop -->
                    <CloudImageUpload
                        v-else-if="availableAttributes.find(a => a.id === newAttrForm.attributeId)?.type === 'image'"
                        v-model="newAttrForm.value" />

                    <!-- Period -->
                    <div v-else-if="availableAttributes.find(a => a.id === newAttrForm.attributeId)?.type === 'period'"
                        class="grid grid-cols-2 gap-3">
                        <div>
                            <Label>{{ __('Start Date') }}</Label>
                            <Input type="date" v-model="newAttrForm.periodStart" class="form-input mt-1" required />
                        </div>
                        <div>
                            <Label>{{ __('End Date') }}</Label>
                            <Input type="date" v-model="newAttrForm.periodEnd" class="form-input mt-1" />
                        </div>
                    </div>

                    <!-- Dropdown select -->
                    <select
                        v-else-if="availableAttributes.find(a => a.id === newAttrForm.attributeId)?.type === 'select'"
                        v-model="newAttrForm.value" class="form-select w-full" required>
                        <option value="">{{ __('Select option') }}</option>
                        <option
                            v-for="c in availableAttributes.find(a => a.id === newAttrForm.attributeId)?.options?.choices || []"
                            :key="c" :value="c">{{ c }}</option>
                    </select>

                    <!-- Text (Markdown) -->
                    <textarea
                        v-else-if="availableAttributes.find(a => a.id === newAttrForm.attributeId)?.type === 'text'"
                        v-model.trim="newAttrForm.value" class="form-textarea w-full" rows="3"
                        placeholder="Markdown text..." required></textarea>

                    <!-- String / Number / Date -->
                    <Input v-else
                        :type="availableAttributes.find(a => a.id === newAttrForm.attributeId)?.type === 'number' ? 'number' : (availableAttributes.find(a => a.id === newAttrForm.attributeId)?.type === 'date' ? 'date' : 'text')"
                        v-model.trim="newAttrForm.value" class="form-input w-full" :placeholder="__('Value')"
                        required />
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t">
                    <Button type="button" variant="outline" @click="showAddAttributeModal = false">
                        <span>{{ __('Cancel') }}</span>
                    </Button>
                    <Button type="button" :disabled="isSubmittingAdd || !newAttrForm.attributeId"
                        @click="submitNewAttribute">
                        <span>{{ isSubmittingAdd ? __('Adding...') : __('Add to Profile') }}</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Edit Attribute Modal -->
        <div v-if="showEditAttributeModal && editingAttrObj"
            class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 animate-fadeIn">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b pb-3">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Edit Attribute') }}: {{ editingAttrObj.name }}
                        </h4>
                        <span class="text-xs text-gray-500 uppercase tracking-wide font-semibold">
                            {{ __('Type') }}: {{ editingAttrObj.type }}
                        </span>
                    </div>
                    <button @click="showEditAttributeModal = false"
                        class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <p class="text-xs text-gray-500">
                    {{ __('Update your personal value for this attribute. To choose a different attribute instead, delete this row and use "Add Attribute".') }}
                </p>

                <form @submit.prevent="saveAttributeFromModal" class="space-y-4">
                    <!-- Boolean -->
                    <div v-if="editingAttrObj.type === 'boolean'" class="py-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" v-model="editModalValue"
                                class="form-checkbox h-5 w-5 text-blue-600 rounded" />
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Yes / Active / Completed') }}</span>
                        </label>
                    </div>

                    <!-- Image via Cloud Upload -->
                    <div v-else-if="editingAttrObj.type === 'image'" class="space-y-2">
                        <Label>{{ __('Attribute Image') }}</Label>
                        <CloudImageUpload v-model="editModalValue" />
                    </div>

                    <!-- Period Date Range -->
                    <div v-else-if="editingAttrObj.type === 'period'" class="grid grid-cols-2 gap-3">
                        <div>
                            <Label :isRequired="true">{{ __('Start Date') }}</Label>
                            <Input type="date" v-model="editModalPeriodStart" class="form-input mt-1" required />
                        </div>
                        <div>
                            <Label>{{ __('End Date (Leave blank if present)') }}</Label>
                            <Input type="date" v-model="editModalPeriodEnd" class="form-input mt-1" />
                        </div>
                    </div>

                    <!-- Select Dropdown -->
                    <div v-else-if="editingAttrObj.type === 'select'" class="space-y-1">
                        <Label :isRequired="true">{{ __('Select Option') }}</Label>
                        <select v-model="editModalValue" class="form-select w-full mt-1" required>
                            <option value="">{{ __('Select an option...') }}</option>
                            <option v-for="opt in editingAttrObj.options?.choices || []" :key="opt" :value="opt">{{ opt
                            }}
                            </option>
                        </select>
                    </div>

                    <!-- Text (Markdown) -->
                    <div v-else-if="editingAttrObj.type === 'text'" class="space-y-1">
                        <Label :isRequired="true">{{ __('Description (Markdown supported)') }}</Label>
                        <Textarea v-model.trim="editModalValue" rows="4" class="w-full mt-1"
                            placeholder="Enter detailed description..." required />
                    </div>

                    <!-- String / Number / Date -->
                    <div v-else class="space-y-1">
                        <Label :isRequired="true">{{ __('Value') }}</Label>
                        <Input
                            :type="editingAttrObj.type === 'number' ? 'number' : (editingAttrObj.type === 'date' ? 'date' : 'text')"
                            v-model.trim="editModalValue" class="form-input w-full mt-1"
                            :placeholder="__('Enter value...')" :maxlength="editingAttrObj.options?.maxLength"
                            :pattern="editingAttrObj.options?.regex" :min="editingAttrObj.options?.min"
                            :max="editingAttrObj.options?.max" required />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <Button type="button" variant="outline" @click="showEditAttributeModal = false">
                            <span>{{ __('Cancel') }}</span>
                        </Button>
                        <Button type="submit" :disabled="isSubmittingEdit">
                            <span>{{ isSubmittingEdit ? __('Saving...') : __('Save Changes') }}</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
