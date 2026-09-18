<script setup lang="ts">
import { ref, inject } from 'vue';
import { Button } from '@/Components/ui/button';

const __ = inject<any>('__', (key: string) => key);

const props = defineProps<{
    achievements: Array<{ name: string, icon: string, description: string }>
}>();

const svgRef = ref<SVGElement | null>(null);

function downloadSVG() {
    if (!svgRef.value) return;
    const svgData = new XMLSerializer().serializeToString(svgRef.value);
    const blob = new Blob([svgData], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'achievements.svg';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}
</script>

<template>
    <div class="panel border-0 relative overflow-hidden">
        <div class="flex justify-between items-center mb-5 pb-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2.5">
                <span
                    class="inline-block w-2.5 h-6 rounded-full bg-gradient-to-b from-purple-500 to-indigo-600 shadow-[0_0_10px_rgba(168,85,247,0.5)]"></span>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white-light">{{ __('Badges & Achievements') }}</h3>
            </div>
            <Button @click="downloadSVG" variant="outline" size="sm"
                class="flex items-center gap-2 shadow-xs hover:shadow transition">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                {{ __('Download SVG') }}
            </Button>
        </div>

        <div
            class="w-full overflow-x-auto bg-gradient-to-br from-gray-50/70 to-gray-100/40 dark:from-[#111a2e]/70 dark:to-[#0e1726]/70 rounded-2xl p-6 border border-gray-200/50 dark:border-gray-800/60 shadow-inner">
            <svg ref="svgRef" xmlns="http://www.w3.org/2000/svg" :width="Math.max(800, achievements.length * 160 + 40)"
                height="170" class="mx-auto" style="background-color: transparent;">
                <defs>
                    <filter id="badge3dShadow" x="-20%" y="-20%" width="150%" height="150%">
                        <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#4361ee" flood-opacity="0.35" />
                    </filter>
                    <linearGradient id="badgeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" stop-color="#4f46e5" />
                        <stop offset="50%" stop-color="#7c3aed" />
                        <stop offset="100%" stop-color="#db2777" />
                    </linearGradient>
                    <linearGradient id="badgeRim" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.6" />
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0.05" />
                    </linearGradient>
                    <radialGradient id="badgeGlow" cx="30%" cy="30%" r="70%">
                        <stop offset="0%" stop-color="#ffffff" stop-opacity="0.35" />
                        <stop offset="100%" stop-color="#ffffff" stop-opacity="0" />
                    </radialGradient>
                </defs>

                <text v-if="achievements.length === 0" x="50%" y="85" font-family="system-ui, -apple-system, sans-serif"
                    font-size="15" font-weight="600" fill="#9ca3af" text-anchor="middle">
                    {{ __('No achievements yet. Keep creating projects and CVs!') }}
                </text>

                <g v-for="(badge, index) in achievements" :key="index" :transform="`translate(${25 + index * 160}, 20)`"
                    class="cursor-pointer">
                    <!-- Drop shadow & outer medal -->
                    <circle cx="55" cy="45" r="40" fill="url(#badgeGradient)" filter="url(#badge3dShadow)" />
                    <!-- Metallic outer bevel rim -->
                    <circle cx="55" cy="45" r="39" fill="none" stroke="url(#badgeRim)" stroke-width="2" />
                    <!-- Inner sphere highlight -->
                    <circle cx="55" cy="45" r="37" fill="url(#badgeGlow)" />

                    <!-- Icon -->
                    <text x="55" y="55" font-size="30" font-family="system-ui, -apple-system, sans-serif"
                        text-anchor="middle" fill="#ffffff" filter="drop-shadow(0 2px 4px rgba(0,0,0,0.3))">
                        {{ badge.icon }}
                    </text>

                    <!-- Title -->
                    <text x="55" y="108" font-family="system-ui, -apple-system, sans-serif" font-size="13"
                        font-weight="bold" fill="currentColor" class="fill-gray-900 dark:fill-gray-100"
                        text-anchor="middle">
                        {{ badge.name }}
                    </text>

                    <!-- Description -->
                    <text x="55" y="128" font-family="system-ui, -apple-system, sans-serif" font-size="10"
                        font-weight="500" fill="currentColor" class="fill-gray-500 dark:fill-gray-400"
                        text-anchor="middle">
                        {{ badge.description }}
                    </text>
                </g>
            </svg>
        </div>
    </div>
</template>
