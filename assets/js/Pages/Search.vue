<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Search01Icon } from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

defineProps<{
    query: string;
    positions: any[];
    cvs: any[];
}>();
</script>

<template>
    <Head title="Search Results" />

    <div class="pt-5 max-w-5xl mx-auto space-y-6">
        <div class="flex items-center gap-3 mb-8">
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg">
                <HugeiconsIcon :icon="Search01Icon" :size="28" />
            </div>
            <div>
                <h2 class="text-3xl font-bold">Search Results</h2>
                <p class="text-gray-500">Showing results for "<span class="font-semibold text-gray-900 dark:text-gray-200">{{ query }}</span>"</p>
            </div>
        </div>

        <div v-if="!query" class="text-center py-12 text-gray-500">
            Please enter a search term above to begin.
        </div>
        <div v-else>
            <!-- Positions Results -->
            <div class="panel mb-8">
                <h3 class="text-xl font-semibold mb-4 border-b pb-2 flex items-center justify-between">
                    Positions
                    <span class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-full text-gray-600 dark:text-gray-400">{{ positions.length }}</span>
                </h3>
                
                <div v-if="positions.length === 0" class="text-gray-500 py-4">No positions found.</div>
                <div class="table-responsive" v-else>
                    <table class="w-full text-left table-auto border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 dark:bg-gray-800 text-xs text-gray-500 uppercase">
                                <th class="p-2.5 font-semibold">Position Title</th>
                                <th class="p-2.5 font-semibold">Company</th>
                                <th class="p-2.5 font-semibold">Level</th>
                                <th class="p-2.5 font-semibold">Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pos in positions" :key="pos.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-2.5 font-medium">
                                    <Link :href="`/positions/${pos.id}`" class="text-blue-600 hover:underline">
                                        {{ pos.title }}
                                    </Link>
                                </td>
                                <td class="p-2.5 text-gray-600 dark:text-gray-400 text-sm">{{ pos.company || 'Confidential' }}</td>
                                <td class="p-2.5">
                                    <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-300">{{ pos.level || 'Any' }}</span>
                                </td>
                                <td class="p-2.5 text-xs text-gray-500 max-w-xs truncate">{{ pos.shortDescription }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CV Results -->
            <div class="panel">
                <h3 class="text-xl font-semibold mb-4 border-b pb-2 flex items-center justify-between">
                    CVs
                    <span class="text-sm bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-full text-gray-600 dark:text-gray-400">{{ cvs.length }}</span>
                </h3>
                
                <div v-if="cvs.length === 0" class="text-gray-500 py-4">No CVs found.</div>
                
                <div class="table-responsive" v-else>
                    <table class="w-full text-left table-auto border-collapse">
                        <thead>
                            <tr class="border-b bg-gray-50 dark:bg-gray-800">
                                <th class="p-2 font-semibold">Candidate</th>
                                <th class="p-2 font-semibold">Position</th>
                                <th class="p-2 font-semibold text-center">Status</th>
                                <th class="p-2 font-semibold text-center">Likes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cv in cvs" :key="cv.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="p-2 font-medium">
                                    <Link :href="`/cvs/${cv.id}`" class="text-blue-600 hover:underline">
                                        {{ cv.candidateName }}
                                    </Link>
                                </td>
                                <td class="p-2 text-gray-600 dark:text-gray-400">{{ cv.positionTitle }}</td>
                                <td class="p-2 text-center">
                                    <span v-if="cv.status === 'draft'" class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs">Draft</span>
                                    <span v-else class="px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">Published</span>
                                </td>
                                <td class="p-2 text-center font-bold text-red-500">{{ cv.likes }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
