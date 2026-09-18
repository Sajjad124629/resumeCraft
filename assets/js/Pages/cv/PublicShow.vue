<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    cv: any;
}>();
</script>

<template>
    <Head :title="`CV - ${cv.candidate.firstName} ${cv.candidate.lastName}`" />

    <div class="min-h-screen bg-gray-50 dark:bg-[#060818] py-12 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-4xl mx-auto bg-white dark:bg-[#0e1726] shadow-xl rounded-2xl overflow-hidden">
            
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-700 p-8 sm:p-12 text-white">
                <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                    <div v-if="cv.candidate.photo" class="shrink-0">
                        <img :src="cv.candidate.photo" alt="Profile Photo" class="w-24 h-24 sm:w-32 sm:h-32 rounded-full border-4 border-white/20 object-cover shadow-lg" />
                    </div>
                    <div class="text-center sm:text-left flex-1">
                        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight">{{ cv.candidate.firstName }} {{ cv.candidate.lastName }}</h1>
                        <p class="text-xl sm:text-2xl text-blue-100 mt-2 font-medium">{{ cv.position.title }}</p>
                        
                        <div class="mt-4 flex flex-wrap justify-center sm:justify-start gap-4 text-sm text-blue-100">
                            <div v-if="cv.candidate.location" class="flex items-center gap-1.5 bg-black/20 px-3 py-1.5 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                {{ cv.candidate.location }}
                            </div>
                            <div class="flex items-center gap-1.5 bg-black/20 px-3 py-1.5 rounded-full">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                                Applying to: {{ cv.position.company }}
                            </div>
                        </div>
                    </div>
                    
                    <div class="shrink-0 flex flex-col items-center sm:items-end mt-4 sm:mt-0">
                        <div class="flex items-center gap-2 bg-white/10 px-4 py-2 rounded-xl backdrop-blur-sm border border-white/10">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6 text-pink-400">
                                <path d="M11.645 20.91l-.007-.003-.022-.012a15.247 15.247 0 01-.383-.218 25.18 25.18 0 01-4.244-3.17C4.688 15.36 2.25 12.174 2.25 8.25 2.25 5.322 4.714 3 7.688 3A5.5 5.5 0 0112 5.052 5.5 5.5 0 0116.313 3c2.973 0 5.437 2.322 5.437 5.25 0 3.925-2.438 7.111-4.739 9.256a25.175 25.175 0 01-4.244 3.17 15.247 15.247 0 01-.383.219l-.022.012-.007.004-.003.001a.752.752 0 01-.704 0l-.003-.001z" />
                            </svg>
                            <span class="text-xl font-bold">{{ cv.likes }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Section -->
            <div class="p-8 sm:p-12 space-y-12 text-gray-800 dark:text-gray-200">
                
                <!-- Attributes / Skills Grid -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-3 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600 dark:text-blue-400"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                        Professional Profile
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div v-for="(attr, index) in cv.attributes" :key="index" 
                             class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-4 border border-gray-100 dark:border-gray-700 hover:shadow-md transition-shadow">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">{{ attr.name }}</h3>
                            <p class="font-medium text-lg">{{ attr.value || 'Not specified' }}</p>
                        </div>
                    </div>
                </section>

                <!-- Projects / Experience -->
                <section>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2 border-b pb-3 dark:border-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-blue-600 dark:text-blue-400"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        Highlighted Experience & Projects
                    </h2>
                    
                    <div class="space-y-8">
                        <div v-for="(project, index) in cv.projects" :key="index" class="relative pl-6 sm:pl-8">
                            <!-- Timeline line -->
                            <div class="absolute left-0 top-2 bottom-0 w-px bg-gray-200 dark:bg-gray-700" v-if="index !== cv.projects.length - 1"></div>
                            <!-- Timeline dot -->
                            <div class="absolute left-[-4px] top-2 w-2 h-2 rounded-full bg-blue-600 ring-4 ring-white dark:ring-[#0e1726]"></div>
                            
                            <div class="flex flex-col sm:flex-row sm:items-baseline justify-between mb-2">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ project.name }}</h3>
                                <span class="text-sm font-medium text-gray-500 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-full w-fit mt-1 sm:mt-0">
                                    {{ new Date(project.periodStart).toLocaleDateString(undefined, { month: 'short', year: 'numeric' }) }} 
                                    - 
                                    {{ project.periodEnd ? new Date(project.periodEnd).toLocaleDateString(undefined, { month: 'short', year: 'numeric' }) : 'Present' }}
                                </span>
                            </div>
                            
                            <div class="prose prose-sm sm:prose-base dark:prose-invert max-w-none text-gray-600 dark:text-gray-300 mb-4" v-html="project.description"></div>
                            
                            <div class="flex flex-wrap gap-2">
                                <span v-for="tag in project.tags" :key="tag" 
                                      class="px-2.5 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50 rounded-md text-xs font-semibold shadow-sm">
                                    {{ tag }}
                                </span>
                            </div>
                        </div>
                        
                        <div v-if="cv.projects.length === 0" class="text-center py-8 text-gray-500 bg-gray-50 dark:bg-gray-800/30 rounded-xl border border-dashed border-gray-200 dark:border-gray-700">
                            No projects highlighted for this position.
                        </div>
                    </div>
                </section>
                
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-900 px-8 py-6 text-center text-sm text-gray-500 border-t border-gray-100 dark:border-gray-800">
                Created with <strong>ResumeCraft</strong>
            </div>
        </div>
    </div>
</template>
