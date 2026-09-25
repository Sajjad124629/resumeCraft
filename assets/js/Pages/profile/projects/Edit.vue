<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/route';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Delete01Icon, PlusSignIcon } from '@hugeicons/core-free-icons';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    project: any;
    existingTags?: string[];
    candidateId?: number;
}>();

const newTagInput = ref('');

const form = useForm({
    name: props.project.name || '',
    description: props.project.description || '',
    periodStart: props.project.periodStart || '',
    periodEnd: props.project.periodEnd || '',
    tags: [...(props.project.tags || [])],
});

function addTag(tag?: string) {
    const val = (tag || newTagInput.value).trim();
    if (val && !form.tags.includes(val)) {
        form.tags.push(val);
    }
    newTagInput.value = '';
}

function removeTag(index: number) {
    form.tags.splice(index, 1);
}

const submit = () => {
    form.put(route('app_profile_project_edit', { id: props.project.id }));
};
</script>

<template>

    <Head :title="__('Edit Project')" />

    <div>
        <div class="panel pb-3 mt-6 bg-white dark:bg-gray-800 rounded shadow p-5">
            <div class="mb-8 border-b ml-[-1.3rem] mr-[-1.3rem] border-gray-200 dark:border-gray-700">
                <div class="ml-5 mb-4">
                    <h5 class="font-semibold text-lg">{{ __('Edit Project') }}</h5>
                    <small class="text-gray-500"><em>{{ __('The field labels marked with * are required input fields.')
                            }}</em></small>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-5">
                    <div class="lg:col-span-2">
                        <Label :isRequired="true" for="name">{{ __('Project Name') }}</Label>
                        <Input type="text" v-model.trim="form.name" :placeholder="__('Project Name')"
                            class="form-input mt-1 block w-full" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <Label :isRequired="true" for="periodStart">{{ __('Start Date') }}</Label>
                        <Input type="date" v-model="form.periodStart" class="form-input mt-1 block w-full" required />
                        <InputError :message="form.errors.periodStart" />
                    </div>

                    <div>
                        <Label for="periodEnd">{{ __('End Date (Leave empty if present)') }}</Label>
                        <Input type="date" v-model="form.periodEnd" class="form-input mt-1 block w-full" />
                        <InputError :message="form.errors.periodEnd" />
                    </div>

                    <div class="lg:col-span-2">
                        <Label for="description">{{ __('Description') }}</Label>
                        <Textarea id="description" v-model.trim="form.description"
                            :placeholder="__('Project Description')" class="mt-1 block w-full" :rows="4" />
                        <InputError :message="form.errors.description" />
                    </div>
                </div>

                <!-- Tags / Technologies Section -->
                <div class="mt-8 mb-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-lg mb-2">{{ __('Tags / Technologies') }}</h5>
                    <p class="text-xs text-gray-500 mb-4">{{ __('Add technologies, frameworks, and skills used in this project.') }}</p>
                    <!-- Active Tag Chips -->
                    <div v-if="form.tags.length > 0"
                        class="flex flex-wrap gap-2 mb-4 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                        <span v-for="(tag, index) in form.tags" :key="index"
                            class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 rounded-full text-xs font-semibold">
                            #{{ tag }}
                            <button type="button" @click="removeTag(index)"
                                class="hover:text-red-500 transition-colors ml-1">&times;</button>
                        </span>
                    </div>

                    <!-- Input and Datalist for Autocompletion -->
                    <div class="flex gap-2 mb-4">
                        <div class="flex-1">
                            <input list="existing-tags" v-model.trim="newTagInput" @keydown.enter.prevent="addTag()"
                                placeholder="Type a technology (e.g. PHP, Vue.js, Docker) and press Enter or Add..."
                                class="form-input w-full" />
                            <datalist id="existing-tags">
                                <option v-for="tag in existingTags" :key="tag" :value="tag" />
                            </datalist>
                        </div>
                        <Button type="button" @click="addTag()"
                            class="bg-blue-600 hover:bg-blue-700 text-white flex items-center gap-1">
                            <HugeiconsIcon :icon="PlusSignIcon" :size="18" color="currentColor" /> {{ __('Add') }}
                        </Button>
                    </div>

                    <!-- Existing Tags Suggestion Badges -->
                    <div v-if="existingTags && existingTags.length > 0" class="mt-2">
                        <span class="text-xs font-medium text-gray-500 block mb-2">{{ __('Existing technologies in your profile (click to add):') }}</span>
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="tag in existingTags.filter(t => !form.tags.includes(t))" :key="tag"
                                type="button" @click="addTag(tag)"
                                class="text-xs px-2.5 py-1 bg-gray-100 hover:bg-blue-50 hover:text-blue-600 dark:bg-gray-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-300 rounded-full border border-gray-200 dark:border-gray-600 transition">
                                + {{ tag }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end items-center mt-8 mb-4">
                    <TextLink :href="candidateId ? route('app_profile_candidate_admin', { id: candidateId }) : route('app_profile_index')">
                        <Button type="button" variant="outline"
                            class="btn btn-outline-danger flex gap-1 items-center">{{ __('Cancel') }}</Button>
                    </TextLink>
                    <Button type="submit" :disabled="form.processing"
                        class="btn btn-primary flex gap-1 items-center ml-4">
                        {{ form.processing ? __('Updating...') : __('Update Project') }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
