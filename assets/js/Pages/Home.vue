<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { HugeiconsIcon } from '@hugeicons/vue';
import { UserGroup03Icon, DashboardSquare03Icon, Search01Icon } from '@hugeicons/core-free-icons';

defineProps<{
    app_name: string;
    auth: any;
    stats: any;
    latestPositions: any[];
    popularPositions: any[];
    tagCloud: any[];
}>();
</script>

<template>
    <Head title="Welcome to ResumeCraft" />

    <div class="min-h-screen bg-gray-50 dark:bg-[#060818] font-sans selection:bg-blue-500 selection:text-white">
        
        <!-- Navigation -->
        <nav class="absolute w-full top-0 left-0 z-50 py-6 px-6 sm:px-12 flex justify-between items-center">
            <div class="text-2xl font-extrabold tracking-tight text-blue-600 dark:text-blue-500">
                Resume<span class="text-gray-900 dark:text-white">Craft</span>
            </div>
            
            <div class="flex gap-4 items-center">
                <Link :href="route('app_position_index')" class="text-sm font-semibold text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">Browse Positions</Link>
                <Link :href="route('app_search')" class="text-sm font-semibold text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">Search</Link>
                <template v-if="auth?.user">
                    <Link :href="route('app_dashboard')" class="text-sm font-semibold text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">{{ __('Dashboard') }}</Link>
                </template>
                <template v-else>
                    <Link :href="route('app_login')" class="text-sm font-semibold text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 transition">{{ __('Log In') }}</Link>
                    <Link :href="route('app_register')" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-full text-sm font-semibold transition shadow-lg shadow-blue-500/30">{{ __('Sign Up') }}</Link>
                </template>
            </div>
        </nav>

        <!-- Hero Section -->
        <main class="relative flex flex-col items-center justify-center min-h-screen text-center px-4 overflow-hidden pt-20">
            <!-- Background Glow Elements -->
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl opacity-50 pointer-events-none"></div>

            <div class="relative z-10 max-w-4xl mx-auto space-y-8">
                <h1 class="text-5xl sm:text-7xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    Craft the Perfect <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-500">Resume</span><br> for Every Position
                </h1>
                
                <p class="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto font-medium">
                    The smartest way for Candidates to manage their profiles and generate tailored CVs, and for Recruiters to define strict technical requirements.
                </p>

                <div class="flex flex-col sm:flex-row justify-center gap-4 mt-8">
                    <Link :href="route('app_register')" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-full text-lg font-bold transition shadow-xl shadow-blue-500/30 flex items-center justify-center gap-2">
                        Get Started
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"></path><path d="m12 5 7 7-7 7"></path></svg>
                    </Link>
                    <Link :href="route('app_position_index')" class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-900 dark:text-white border border-gray-200 dark:border-gray-700 px-8 py-3.5 rounded-full text-lg font-bold transition flex items-center justify-center">
                        Browse Positions
                    </Link>
                </div>
            </div>

            <!-- Features Grid -->
            <div class="relative z-10 max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 mt-24">
                
                <div class="bg-white dark:bg-gray-800/50 backdrop-blur-sm p-8 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-lg text-left">
                    <div class="bg-blue-100 dark:bg-blue-900/30 w-12 h-12 rounded-xl flex items-center justify-center mb-6">
                        <HugeiconsIcon :icon="UserGroup03Icon" :size="24" class="text-blue-600 dark:text-blue-400" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">For Candidates</h3>
                    <p class="text-gray-600 dark:text-gray-400">Maintain a single, comprehensive profile. Generate tailored CVs for specific positions instantly.</p>
                </div>

                <div class="bg-white dark:bg-gray-800/50 backdrop-blur-sm p-8 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-lg text-left transform md:-translate-y-4">
                    <div class="bg-indigo-100 dark:bg-indigo-900/30 w-12 h-12 rounded-xl flex items-center justify-center mb-6">
                        <HugeiconsIcon :icon="DashboardSquare03Icon" :size="24" class="text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">For Recruiters</h3>
                    <p class="text-gray-600 dark:text-gray-400">Create positions, define strict attribute requirements, and manage incoming applications effortlessly.</p>
                </div>

                <div class="bg-white dark:bg-gray-800/50 backdrop-blur-sm p-8 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-lg text-left">
                    <div class="bg-pink-100 dark:bg-pink-900/30 w-12 h-12 rounded-xl flex items-center justify-center mb-6">
                        <HugeiconsIcon :icon="Search01Icon" :size="24" class="text-pink-600 dark:text-pink-400" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">Smart Matching</h3>
                    <p class="text-gray-600 dark:text-gray-400">Access Rules ensure candidates only see and apply to positions they are technically qualified for.</p>
                </div>

            </div>
            
            <!-- Dynamic Data Sections -->
            <div class="relative z-10 w-full max-w-5xl mx-auto mt-24 text-left space-y-16 pb-20">
                
                <!-- Statistics -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white dark:bg-gray-800/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm text-center">
                        <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400 mb-1">{{ stats.totalPositions }}</div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Positions</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm text-center">
                        <div class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-400 mb-1">{{ stats.totalCvs }}</div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Submitted CVs</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm text-center">
                        <div class="text-3xl font-extrabold text-pink-600 dark:text-pink-400 mb-1">{{ stats.cvsLast24h }}</div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">CVs (24h)</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm text-center">
                        <div class="text-3xl font-extrabold text-green-600 dark:text-green-400 mb-1">{{ stats.totalCandidates }}</div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Candidates</div>
                    </div>
                    <div class="bg-white dark:bg-gray-800/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-sm text-center">
                        <div class="text-3xl font-extrabold text-purple-600 dark:text-purple-400 mb-1">{{ stats.totalRecruiters }}</div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Recruiters</div>
                    </div>
                </div>

                <!-- Latest & Popular Positions in Table View (Rule Compliant) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Latest Positions Table -->
                    <div class="bg-white dark:bg-gray-800/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-blue-500"></span> Latest Positions
                            </h3>
                            <Link :href="route('app_position_index')" class="text-xs text-blue-600 hover:underline">View all &rarr;</Link>
                        </div>
                        <div class="table-responsive">
                            <table class="w-full text-left table-auto border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-500 text-xs font-semibold">
                                        <th class="pb-2">Position</th>
                                        <th class="pb-2">Company</th>
                                        <th class="pb-2 text-right">Level</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="pos in latestPositions" :key="pos.id" class="border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="py-2.5 font-medium">
                                            <Link :href="route('app_position_show', { id: pos.id })" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ pos.title }}
                                            </Link>
                                        </td>
                                        <td class="py-2.5 text-gray-500 text-xs">{{ pos.company || 'Confidential' }}</td>
                                        <td class="py-2.5 text-right">
                                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-xs text-gray-600 dark:text-gray-300">{{ pos.level || 'Any' }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="latestPositions.length === 0">
                                        <td colspan="3" class="py-4 text-center text-gray-500 text-xs">No positions available yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Popular Positions Table -->
                    <div class="bg-white dark:bg-gray-800/50 p-6 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-lg">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full bg-orange-500"></span> Most Popular Positions
                            </h3>
                            <Link :href="route('app_position_index')" class="text-xs text-blue-600 hover:underline">View all &rarr;</Link>
                        </div>
                        <div class="table-responsive">
                            <table class="w-full text-left table-auto border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-500 text-xs font-semibold">
                                        <th class="pb-2 w-8">#</th>
                                        <th class="pb-2">Position</th>
                                        <th class="pb-2">Company</th>
                                        <th class="pb-2 text-right">CVs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(pos, idx) in popularPositions" :key="pos.id" class="border-b border-gray-100 dark:border-gray-700/40 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="py-2.5 text-xs font-bold text-gray-400">#{{ idx + 1 }}</td>
                                        <td class="py-2.5 font-medium">
                                            <Link :href="route('app_position_show', { id: pos.id })" class="text-blue-600 dark:text-blue-400 hover:underline">
                                                {{ pos.title }}
                                            </Link>
                                        </td>
                                        <td class="py-2.5 text-gray-500 text-xs">{{ pos.company || 'Confidential' }}</td>
                                        <td class="py-2.5 text-right font-bold text-indigo-600 dark:text-indigo-400 text-xs">{{ pos.cvCount }}</td>
                                    </tr>
                                    <tr v-if="popularPositions.length === 0">
                                        <td colspan="4" class="py-4 text-center text-gray-500 text-xs">No data available yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Tag Cloud with Search Links -->
                <div class="bg-white dark:bg-gray-800/50 p-8 rounded-2xl border border-gray-100 dark:border-gray-700/50 shadow-lg text-center">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Trending Technologies</h3>
                    <p class="text-xs text-gray-500 mb-6">Click any technology to search matching candidates and positions</p>
                    <div v-if="tagCloud.length === 0" class="text-gray-500 text-sm">Not enough data to generate tag cloud.</div>
                    <div class="flex flex-wrap justify-center gap-3">
                        <Link v-for="item in tagCloud" :key="item.tag" 
                              :href="route('app_search', { q: item.tag })"
                              class="px-3 py-1.5 rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 transition hover:bg-blue-100 dark:hover:bg-blue-900/60 font-medium"
                              :style="{ fontSize: `${Math.max(0.8, Math.min(1.4, 0.8 + (item.count * 0.08)))}rem`, opacity: Math.max(0.65, Math.min(1, 0.45 + (item.count * 0.15))) }">
                            #{{ item.tag }}
                        </Link>
                    </div>
                </div>

            </div>
        </main>
        
        <footer class="text-center py-8 text-gray-500 dark:text-gray-400 text-sm border-t border-gray-200 dark:border-gray-800">
            &copy; 2026 ResumeCraft. All rights reserved.
        </footer>
    </div>
</template>
