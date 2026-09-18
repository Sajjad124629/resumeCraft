<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Briefcase08Icon, Location01Icon, ArrowLeft02Icon } from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    candidate: {
        id: number;
        firstName: string;
        lastName: string;
        location?: string;
        photo?: string;
    };
    attributes: Array<{
        name: string;
        type: string;
        value: any;
    }>;
    projects: Array<{
        id: number;
        name: string;
        description: string;
        tags: string[];
        periodStart?: string;
        periodEnd?: string;
    }>;
    achievements: any[];
}>();
</script>

<template>
    <Head :title="`${candidate.firstName} ${candidate.lastName} - Public Profile`" />

    <div class="pt-5 max-w-4xl mx-auto space-y-6">
        <div class="flex items-center gap-2">
            <button @click="() => window.history.back()" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <HugeiconsIcon :icon="ArrowLeft02Icon" :size="16" /> Back
            </button>
        </div>

        <!-- Header Card -->
        <div class="panel p-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row items-center gap-6">
            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0 border-2 border-blue-500">
                <img 
                    v-if="candidate.photo" 
                    :src="candidate.photo" 
                    :alt="candidate.firstName" 
                    class="w-full h-full object-cover" 
                />
                <div v-else class="w-full h-full flex items-center justify-center text-3xl font-bold text-gray-400">
                    {{ candidate.firstName.charAt(0) }}{{ candidate.lastName.charAt(0) }}
                </div>
            </div>

            <div class="text-center sm:text-left flex-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ candidate.firstName }} {{ candidate.lastName }}
                </h1>
                <div v-if="candidate.location" class="flex items-center justify-center sm:justify-start gap-1 text-gray-500 text-sm mt-1">
                    <HugeiconsIcon :icon="Location01Icon" :size="16" />
                    <span>{{ candidate.location }}</span>
                </div>
            </div>
        </div>

        <!-- Info Attributes -->
        <div class="panel">
            <h3 class="text-lg font-semibold border-b pb-2 mb-4">Skills & Attributes</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="attr in attributes" :key="attr.name" class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                    <div class="text-xs text-gray-500 font-semibold mb-1">{{ attr.name }}</div>
                    <div v-if="attr.type === 'image' && attr.value">
                        <img :src="attr.value" class="max-h-32 rounded shadow-sm" alt="Attribute image" />
                    </div>
                    <div v-else-if="attr.type === 'boolean'">
                        <span class="px-2 py-0.5 rounded text-xs font-semibold" :class="attr.value ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                            {{ attr.value ? 'Yes' : 'No' }}
                        </span>
                    </div>
                    <div v-else-if="attr.type === 'period' && attr.value">
                        <span class="text-sm font-medium">{{ attr.value.start }} &rarr; {{ attr.value.end || 'Present' }}</span>
                    </div>
                    <div v-else class="text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ attr.value || 'Not set' }}
                    </div>
                </div>
                <div v-if="attributes.length === 0" class="text-gray-500 text-sm py-2">No additional attributes set.</div>
            </div>
        </div>

        <!-- Projects -->
        <div class="panel">
            <h3 class="text-lg font-semibold border-b pb-2 mb-4">Projects</h3>
            <div class="space-y-4">
                <div v-for="project in projects" :key="project.id" class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                    <div class="flex justify-between items-start">
                        <h4 class="font-bold text-base">{{ project.name }}</h4>
                        <span v-if="project.periodStart" class="text-xs text-gray-500">
                            {{ project.periodStart }} - {{ project.periodEnd || 'Present' }}
                        </span>
                    </div>
                    <div class="prose prose-sm dark:prose-invert max-w-none mt-2" v-html="project.description"></div>
                    <div class="mt-3 flex gap-2 flex-wrap">
                        <span v-for="tag in project.tags" :key="tag" class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">
                            {{ tag }}
                        </span>
                    </div>
                </div>
                <div v-if="projects.length === 0" class="text-gray-500 text-sm py-2">No projects listed.</div>
            </div>
        </div>
    </div>
</template>
