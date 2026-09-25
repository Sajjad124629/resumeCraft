<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import TextLink from '@/Components/TextLink.vue';
import { route } from '@/route';
import CloudImageUpload from '@/Components/CloudImageUpload.vue';
import Swal from 'sweetalert2';
import { HugeiconsIcon } from '@hugeicons/vue';
import {
    FavouriteIcon,
    Delete01Icon,
    PencilEdit02Icon,
    ViewIcon
} from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    cv: any;
    isOwner: boolean;
    isReadOnly: boolean;
    auth: any;
}>();

const showToast = (message: string, icon: 'success' | 'error' | 'warning' = 'success') => {
    Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    }).fire({ icon, title: message });
};

const showEditModal = ref(false);
const editingAttr = ref<any>(null);
const modalValue = ref<any>(null);
const modalPeriodStart = ref('');
const modalPeriodEnd = ref('');
const isSaving = ref(false);

function openEditModal(attr: any) {
    if (props.isReadOnly) return;
    editingAttr.value = attr;
    if (attr.type === 'period' && attr.value) {
        modalPeriodStart.value = attr.value.start || '';
        modalPeriodEnd.value = attr.value.end || '';
    } else {
        modalValue.value = attr.value;
    }
    showEditModal.value = true;
}

async function saveModalAttribute() {
    if (props.isReadOnly || !editingAttr.value) return;
    const attr = editingAttr.value;

    let finalVal = modalValue.value;
    if (attr.type === 'period') {
        finalVal = {
            start: modalPeriodStart.value,
            end: modalPeriodEnd.value,
        };
    }

    isSaving.value = true;
    try {
        const response = await fetch(route('app_profile_update_attr'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                attributeId: attr.attributeId,
                candidateId: props.cv.candidate.id,
                value: finalVal,
                version: attr.valVersion
            })
        });

        if (response.ok) {
            const data = await response.json();
            attr.value = finalVal;
            attr.valVersion = data.valVersion;
            showEditModal.value = false;
            editingAttr.value = null;
            showToast('Attribute saved & synchronized with your profile!');
        } else if (response.status === 409) {
            showToast('Conflict: The value was modified elsewhere. Please refresh.', 'warning');
        } else {
            showToast('Failed to save attribute.', 'error');
        }
    } catch (e) {
        console.error(e);
        showToast('Network error while saving attribute.', 'error');
    } finally {
        isSaving.value = false;
    }
}

const totalAttributes = computed(() => props.cv.attributes?.length || 0);

const completedAttributes = computed(() => {
    return (props.cv.attributes || []).filter((attr: any) => {
        if (attr.value === null || attr.value === undefined || attr.value === '') return false;
        if (attr.type === 'period') return !!attr.value.start;
        return true;
    }).length;
});

const progressPercent = computed(() => {
    if (totalAttributes.value === 0) return 100;
    return Math.round((completedAttributes.value / totalAttributes.value) * 100);
});

const canPublish = computed(() => {
    return props.cv.attributes.every((attr: any) => {
        if (attr.value === null || attr.value === undefined || attr.value === '') return false;
        if (attr.type === 'period') {
            return !!attr.value.start;
        }
        return true;
    });
});

function publishCv() {
    router.post(route('app_cv_publish', { id: props.cv.id }), {}, {
        onSuccess: () => showToast('CV published successfully! Recruiters can now review it.')
    });
}

function unpublishCv() {
    if (confirm('Are you sure you want to unpublish this CV? It will no longer be visible to recruiters.')) {
        router.post(route('app_cv_unpublish', { id: props.cv.id }), {}, {
            onSuccess: () => showToast('CV unpublished.')
        });
    }
}

function deleteCv() {
    if (confirm('Are you sure you want to delete this CV?')) {
        router.delete(route('app_cv_delete', { id: props.cv.id }));
    }
}

const isRecruiter = computed(() => props.auth?.user?.roles?.includes('ROLE_RECRUITER'));

async function toggleLike() {
    if (!isRecruiter.value) return;
    try {
        const response = await fetch(route('app_cv_like', { id: props.cv.id }), {
            method: 'POST',
        });
        if (response.ok) {
            const data = await response.json();
            props.cv.likes = data.likes;
            props.cv.hasLiked = !props.cv.hasLiked;
        }
    } catch (e) {
        console.error('Failed to toggle like', e);
    }
}
</script>

<template>

    <Head :title="`CV - ${cv.position.title}`" />

    <div class="pt-5 max-w-4xl mx-auto space-y-6">
        <!-- Top Navigation Bar -->
        <div class="flex items-center justify-between">
            <TextLink :href="route('app_profile_index')"
                class="text-xs font-semibold text-blue-600 hover:underline flex items-center gap-1">
                &larr; {{ __('Back to My Profile') }}
            </TextLink>
            <div class="flex items-center gap-2">
                <a :href="route('app_cv_public_show', { id: cv.id })" target="_blank">
                    <Button variant="outline" size="sm" class="h-8 text-xs flex gap-1 items-center">
                        <HugeiconsIcon :icon="ViewIcon" :size="14" />
                        {{ __('View Public Preview') }}
                    </Button>
                </a>
                <a :href="route('app_cv_pdf', { id: cv.id })" target="_blank">
                    <Button variant="outline" size="sm" class="h-8 text-xs flex gap-1 items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        {{ __('Download PDF') }}
                    </Button>
                </a>
            </div>
        </div>

        <!-- Main Card Header -->
        <div class="panel border-t-4" :class="cv.status === 'published' ? 'border-t-green-500' : 'border-t-blue-500'">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <img v-if="cv.candidate.photo" :src="cv.candidate.photo" :alt="cv.candidate.firstName"
                        class="w-16 h-16 rounded-full object-cover border-2 border-blue-500 shadow" />
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ cv.candidate.firstName }}
                                {{ cv.candidate.lastName }}</h2>
                            <span v-if="cv.status === 'draft'"
                                class="px-2.5 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-bold uppercase">
                                {{ __('Draft') }}
                            </span>
                            <span v-else
                                class="px-2.5 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-xs font-bold uppercase">
                                {{ __('Published') }}
                            </span>
                        </div>
                        <p class="text-gray-600 dark:text-gray-400 text-sm mt-0.5">
                            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ cv.position.title }}</span>
                            &bull; {{ cv.position.company || __('Confidential') }}
                            <span v-if="cv.candidate.location">&bull; {{ cv.candidate.location }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <div v-if="isOwner && cv.status === 'draft'">
                        <Button @click="publishCv" :disabled="!canPublish"
                            class="bg-green-600 hover:bg-green-700 text-white h-9 text-xs font-bold">
                            {{ __('Publish CV') }}
                        </Button>
                    </div>
                    <div v-if="isOwner && cv.status === 'published'">
                        <Button @click="unpublishCv" variant="outline" size="sm"
                            class="text-yellow-600 hover:text-yellow-700 h-9 text-xs">
                            {{ __('Unpublish CV') }}
                        </Button>
                    </div>

                    <!-- Likes Counter -->
                    <div
                        class="flex items-center gap-1.5 border rounded-full px-3 py-1 bg-gray-50 dark:bg-gray-800 h-9">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ cv.likes }}</span>
                        <button v-if="isRecruiter" @click="toggleLike"
                            class="transition-transform hover:scale-110 focus:outline-none" title="Like CV">
                            <svg v-if="cv.hasLiked" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                                fill="currentColor" class="w-4 h-4 text-red-500">
                                <path
                                    d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                            </svg>
                            <HugeiconsIcon v-else :icon="FavouriteIcon" :size="16"
                                class="text-gray-400 hover:text-red-400" />
                        </button>
                        <HugeiconsIcon v-else :icon="FavouriteIcon" :size="16" class="text-red-500" />
                    </div>

                    <Button v-if="isOwner || auth?.user?.roles?.includes('ROLE_ADMIN')" variant="destructive" size="sm"
                        class="h-9 px-2.5" @click="deleteCv" title="Delete CV">
                        <HugeiconsIcon :icon="Delete01Icon" :size="16" />
                    </Button>
                </div>
            </div>

            <!-- Progress / Requirement Checklist Banner -->
            <div v-if="isOwner" class="mt-5 p-4 rounded-xl border transition-all"
                :class="canPublish ? 'bg-green-50 dark:bg-green-950/20 border-green-200 dark:border-green-800' : 'bg-blue-50 dark:bg-blue-950/20 border-blue-200 dark:border-blue-800'">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-bold flex items-center gap-2"
                            :class="canPublish ? 'text-green-800 dark:text-green-300' : 'text-blue-900 dark:text-blue-200'">
                            <span v-if="canPublish">&check; {{ __('Ready to Publish!') }}</span>
                            <span v-else>&bull; {{ __('CV Preparation Progress') }}: {{ completedAttributes }} / {{
                                totalAttributes }} {{ __('attributes completed') }}</span>
                        </div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                            <span v-if="canPublish">{{ __('All required attributes are filled. Click "Publish CV" above so recruiters can find and review your application.') }}</span>
                            <span v-else>{{ __('Recruiters require all requested attributes to be completed. Click "Fill Value" on any red-highlighted item below to customize.') }}</span>
                        </p>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full sm:w-44 flex flex-col items-end gap-1">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 h-2 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300"
                                :class="canPublish ? 'bg-green-500' : 'bg-blue-600'"
                                :style="{ width: `${progressPercent}%` }">
                            </div>
                        </div>
                        <span class="text-[11px] font-bold text-gray-500">{{ progressPercent }}% {{ __('Completed')
                        }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attributes Section -->
        <div class="panel">
            <div class="border-b pb-3 mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('Requested Profile Attributes') }}
                </h3>
                <p class="text-xs text-gray-500">
                    {{ __('These attributes are specifically requested by the employer for this position. Values you edit here update your master profile.') }}
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="attr in cv.attributes" :key="attr.attributeId"
                    class="p-4 rounded-xl border transition-all flex flex-col justify-between" :class="(!attr.value && attr.value !== false)
                        ? 'border-red-300 bg-red-50/40 dark:bg-red-950/20 dark:border-red-800'
                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 hover:border-blue-300'">

                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{
                                attr.name }}</span>
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                {{ attr.type }}
                            </span>
                        </div>

                        <!-- Render Value -->
                        <div class="py-1">
                            <img v-if="attr.type === 'image' && attr.value" :src="attr.value"
                                class="h-16 w-16 object-cover rounded-lg border shadow-xs" alt="Attribute image" />

                            <span v-else-if="attr.type === 'boolean' && attr.value !== null && attr.value !== undefined"
                                class="px-2.5 py-1 rounded text-xs font-bold"
                                :class="attr.value ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                {{ attr.value ? __('Yes') : __('No') }}
                            </span>

                            <span v-else-if="attr.type === 'period' && attr.value"
                                class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {{ attr.value.start }} &rarr; {{ attr.value.end || __('Present') }}
                            </span>

                            <p v-else-if="attr.value" class="text-sm text-gray-800 dark:text-gray-200 line-clamp-3">
                                {{ attr.value }}
                            </p>

                            <!-- Missing / Empty: Clear Notice -->
                            <div v-else
                                class="flex items-center gap-1.5 text-xs font-bold text-red-600 dark:text-red-400">
                                <span>&bull; {{ __('Missing / Required to publish') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div v-if="!isReadOnly"
                        class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700/50 flex justify-end">
                        <Button size="sm" :variant="(!attr.value && attr.value !== false) ? 'default' : 'outline'"
                            class="h-7 text-xs flex gap-1 items-center"
                            :class="(!attr.value && attr.value !== false) ? 'bg-red-600 hover:bg-red-700 text-white' : ''"
                            @click="openEditModal(attr)">
                            <HugeiconsIcon :icon="PencilEdit02Icon" :size="12" />
                            <span>{{ (!attr.value && attr.value !== false) ? __('Fill Required Value') : __('Edit')
                            }}</span>
                        </Button>
                    </div>
                </div>

                <div v-if="cv.attributes.length === 0" class="col-span-2 p-6 text-center text-gray-500 text-sm">
                    {{ __('No specific attributes required for this position.') }}
                </div>
            </div>
        </div>

        <!-- Relevant Filtered Projects Section -->
        <div class="panel">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between border-b pb-3 mb-4 gap-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ __('Relevant Projects & Experience') }}</h3>
                    <p class="text-xs text-gray-500">
                        {{ __('Filtered automatically into this CV from your profile based on the position\'s required technology tags.') }}
                    </p>
                </div>
                <TextLink v-if="isOwner" :href="route('app_profile_index')">
                    <Button variant="outline" size="sm" class="h-8 text-xs flex gap-1 items-center">
                        <span>{{ __('Manage Projects in Profile') }} &rarr;</span>
                    </Button>
                </TextLink>
            </div>

            <div class="space-y-4">
                <div v-for="project in cv.projects" :key="project.id"
                    class="p-4 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700">
                    <h4 class="font-bold text-base text-gray-900 dark:text-gray-100">{{ project.name }}</h4>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2"
                        v-html="project.description"></div>
                    <div class="mt-2.5 flex gap-1.5 flex-wrap">
                        <span v-for="tag in project.tags" :key="tag"
                            class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded text-[11px] font-medium">
                            #{{ tag }}
                        </span>
                    </div>
                </div>
                <div v-if="cv.projects.length === 0" class="p-6 text-center text-gray-500 text-sm">
                    {{ __('No matching projects found with the position\'s tags.') }}
                    <div v-if="isOwner" class="mt-2">
                        <TextLink :href="route('app_profile_project_create_view')">
                            <Button size="sm" variant="outline" class="text-xs">{{ __('Add a Project') }}</Button>
                        </TextLink>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Attribute Modal -->
        <div v-if="showEditModal && editingAttr"
            class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 animate-fadeIn">
            <div
                class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[90vh] overflow-y-auto">
                <div class="flex justify-between items-center border-b pb-3">
                    <div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            {{ __('Customize Attribute') }}: {{ editingAttr.name }}
                        </h4>
                        <span class="text-xs text-gray-500 uppercase tracking-wide font-semibold">
                            {{ __('Type') }}: {{ editingAttr.type }}
                        </span>
                    </div>
                    <button @click="showEditModal = false"
                        class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <form @submit.prevent="saveModalAttribute" class="space-y-4">
                    <!-- Boolean -->
                    <div v-if="editingAttr.type === 'boolean'" class="py-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" v-model="modalValue"
                                class="form-checkbox h-5 w-5 text-blue-600 rounded" />
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Yes / Completed / Active') }}</span>
                        </label>
                    </div>

                    <!-- Image via Cloud Upload -->
                    <div v-else-if="editingAttr.type === 'image'" class="space-y-2">
                        <Label>{{ __('Attribute Image') }}</Label>
                        <CloudImageUpload v-model="modalValue" />
                    </div>

                    <!-- Period Date Range -->
                    <div v-else-if="editingAttr.type === 'period'" class="grid grid-cols-2 gap-3">
                        <div>
                            <Label :isRequired="true">{{ __('Start Date') }}</Label>
                            <Input type="date" v-model="modalPeriodStart" class="form-input mt-1" required />
                        </div>
                        <div>
                            <Label>{{ __('End Date (Leave blank if present)') }}</Label>
                            <Input type="date" v-model="modalPeriodEnd" class="form-input mt-1" />
                        </div>
                    </div>

                    <!-- Select Dropdown -->
                    <div v-else-if="editingAttr.type === 'select'" class="space-y-1">
                        <Label :isRequired="true">{{ __('Select Option') }}</Label>
                        <select v-model="modalValue" class="form-select w-full mt-1" required>
                            <option value="">{{ __('Select an option...') }}</option>
                            <option v-for="opt in editingAttr.options?.choices || []" :key="opt" :value="opt">{{ opt }}
                            </option>
                        </select>
                    </div>

                    <!-- Text (Markdown) -->
                    <div v-else-if="editingAttr.type === 'text'" class="space-y-1">
                        <Label :isRequired="true">{{ __('Description (Markdown supported)') }}</Label>
                        <Textarea v-model.trim="modalValue" rows="4" class="w-full mt-1"
                            placeholder="Enter detailed description..." required />
                    </div>

                    <!-- String / Number / Date -->
                    <div v-else class="space-y-1">
                        <Label :isRequired="true">{{ __('Value') }}</Label>
                        <Input
                            :type="editingAttr.type === 'number' ? 'number' : (editingAttr.type === 'date' ? 'date' : 'text')"
                            v-model.trim="modalValue" class="form-input w-full mt-1" :placeholder="__('Enter value...')"
                            :maxlength="editingAttr.options?.maxLength" :pattern="editingAttr.options?.regex"
                            :min="editingAttr.options?.min" :max="editingAttr.options?.max" required />
                    </div>

                    <div class="flex justify-end gap-2 pt-3 border-t">
                        <Button type="button" variant="outline" @click="showEditModal = false">
                            <span>{{ __('Cancel') }}</span>
                        </Button>
                        <Button type="submit" :disabled="isSaving">
                            <span>{{ isSaving ? __('Saving...') : __('Save Changes') }}</span>
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
