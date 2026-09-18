<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { Button } from '@/Components/ui/button';
import { ref, computed, onMounted, onUnmounted } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    position: any;
    auth?: any;
}>();

const isCandidate = computed(() => props.auth?.user?.roles?.includes('ROLE_CANDIDATE') ?? false);
const isRecruiter = computed(() => (props.auth?.user?.roles?.includes('ROLE_RECRUITER') || props.auth?.user?.roles?.includes('ROLE_ADMIN')) ?? false);
const isLoggedIn = computed(() => !!props.auth?.user);

// Table selection logic for CVs
const selectedCvs = ref<number[]>([]);

function toggleCvSelection(id: number) {
    const index = selectedCvs.value.indexOf(id);
    if (index === -1) {
        selectedCvs.value.push(id);
    } else {
        selectedCvs.value.splice(index, 1);
    }
}

function toggleAllCvs() {
    if (selectedCvs.value.length === (props.position.cvs?.length || 0)) {
        selectedCvs.value = [];
    } else {
        selectedCvs.value = props.position.cvs.map((c: any) => c.id);
    }
}

function viewCv() {
    if (selectedCvs.value.length === 1) {
        router.visit(`/cvs/${selectedCvs.value[0]}`);
    }
}

function generateCv() {
    router.post(`/cvs/position/${props.position.id}/generate`);
}

// Discussions
const posts = ref<any[]>([]);
const newPost = ref('');
const isSubmitting = ref(false);
let pollingInterval: any = null;

function renderMarkdown(raw: string): string {
    if (!raw) return '';
    let escaped = raw
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    // Code blocks
    escaped = escaped.replace(/```([\s\S]*?)```/g, '<pre class="bg-gray-100 dark:bg-gray-900 p-2 rounded text-xs overflow-x-auto my-2"><code>$1</code></pre>');
    // Inline code
    escaped = escaped.replace(/`([^`]+)`/g, '<code class="bg-gray-100 dark:bg-gray-900 px-1 py-0.5 rounded text-xs text-pink-600 dark:text-pink-400">$1</code>');
    // Bold
    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    escaped = escaped.replace(/__(.*?)__/g, '<strong>$1</strong>');
    // Italic
    escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
    escaped = escaped.replace(/_(.*?)_/g, '<em>$1</em>');
    // Blockquote
    escaped = escaped.replace(/^>\s*(.+)$/gm, '<blockquote class="border-l-4 border-blue-500 pl-2 text-gray-600 dark:text-gray-400 italic my-1">$1</blockquote>');
    // Links
    escaped = escaped.replace(/\[([^\]]+)\]\((https?:\/\/[^\s)]+|\/[^\s)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline">$1</a>');
    // Headings
    escaped = escaped.replace(/^###\s+(.+)$/gm, '<h5 class="font-bold text-sm mt-2 mb-1">$1</h5>');
    escaped = escaped.replace(/^##\s+(.+)$/gm, '<h4 class="font-bold text-base mt-2 mb-1">$1</h4>');
    escaped = escaped.replace(/^#\s+(.+)$/gm, '<h3 class="font-bold text-lg mt-2 mb-1">$1</h3>');
    // Lists
    escaped = escaped.replace(/^[\*\-]\s+(.+)$/gm, '<li class="ml-4 list-disc">$1</li>');
    // Line breaks
    escaped = escaped.replace(/\n/g, '<br />');

    return escaped;
}

async function fetchPosts() {
    try {
        const response = await fetch(`/discussions/position/${props.position.id}`);
        if (response.ok) {
            posts.value = await response.json();
        }
    } catch (e) {
        console.error('Failed to fetch posts', e);
    }
}

async function submitPost() {
    if (!newPost.value.trim()) return;
    isSubmitting.value = true;
    try {
        await fetch(`/discussions/position/${props.position.id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ content: newPost.value }),
        });
        newPost.value = '';
        await fetchPosts();
    } finally {
        isSubmitting.value = false;
    }
}

onMounted(() => {
    fetchPosts();
    pollingInterval = setInterval(() => {
        fetchPosts();
    }, 3000);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

</script>

<template>
    <Head :title="position.title" />

    <div class="pt-5 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">{{ position.title }}</h2>
                <p class="text-gray-500">{{ position.company }} &bull; {{ position.level }}</p>
            </div>
            <Button v-if="isCandidate" @click="generateCv" variant="default" size="default" class="shadow-sm">Generate CV</Button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="md:col-span-2 space-y-6">
                <!-- CVs Table (Recruiter Only) -->
                <div v-if="isRecruiter && position.cvs" class="panel">
                    <div class="flex items-center justify-between border-b pb-2 mb-4">
                        <h3 class="text-lg font-semibold">Submitted CVs</h3>
                        <!-- CV Toolbar -->
                        <div class="flex items-center gap-2">
                            <a :href="`/positions/${position.id}/export`" target="_blank" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-md text-xs font-semibold border border-green-300 dark:border-green-800 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/40 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="13"></line><line x1="8" y1="17" x2="16" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                                Export CSV
                            </a>
                            <div v-if="selectedCvs.length > 0" class="flex items-center gap-2 px-3 h-8 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                                <span class="text-xs text-blue-800 dark:text-blue-300 mr-2 font-medium">{{ selectedCvs.length }} selected</span>
                                <Button v-if="selectedCvs.length === 1" size="sm" variant="outline" class="h-6 text-xs px-2" @click="viewCv">View CV</Button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="w-full text-left table-auto border-collapse">
                            <thead>
                                <tr class="border-b bg-gray-50 dark:bg-gray-800">
                                    <th class="p-2 w-10">
                                        <input type="checkbox" :checked="selectedCvs.length === position.cvs.length && position.cvs.length > 0" @change="toggleAllCvs" class="form-checkbox" />
                                    </th>
                                    <th class="p-2 font-semibold">Candidate</th>
                                    <th class="p-2 font-semibold text-center">Status</th>
                                    <th class="p-2 font-semibold text-center">Likes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="cv in position.cvs" :key="cv.id" class="border-b hover:bg-gray-50 dark:hover:bg-gray-800/50" :class="{'bg-blue-50/50 dark:bg-blue-900/10': selectedCvs.includes(cv.id)}">
                                    <td class="p-2">
                                        <input type="checkbox" :value="cv.id" :checked="selectedCvs.includes(cv.id)" @change="toggleCvSelection(cv.id)" class="form-checkbox" />
                                    </td>
                                    <td class="p-2 font-medium">
                                        <Link :href="`/cvs/${cv.id}`" class="text-blue-600 hover:underline">
                                            {{ cv.candidateName }}
                                        </Link>
                                    </td>
                                    <td class="p-2 text-center">
                                        <span v-if="cv.status === 'draft'" class="px-2 py-0.5 bg-yellow-100 text-yellow-800 rounded text-xs">Draft</span>
                                        <span v-else class="px-2 py-0.5 bg-green-100 text-green-800 rounded text-xs">Published</span>
                                    </td>
                                    <td class="p-2 text-center">{{ cv.likes }}</td>
                                </tr>
                                <tr v-if="position.cvs.length === 0">
                                    <td colspan="4" class="p-4 text-center text-gray-500 text-sm">No CVs submitted yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="panel">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Description</h3>
                    <div class="prose max-w-none text-gray-700 dark:text-gray-300">
                        {{ position.shortDescription || 'No description provided.' }}
                    </div>
                </div>

                <div class="panel">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Discussions</h3>
                    <div class="space-y-4 mb-4 max-h-[400px] overflow-y-auto">
                        <div v-for="post in posts" :key="post.id" class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div class="flex justify-between items-center mb-1">
                                <div>
                                    <Link 
                                        v-if="isRecruiter && post.author.candidateProfileId" 
                                        :href="`/profile/public/${post.author.candidateProfileId}`" 
                                        class="font-semibold text-sm text-blue-600 hover:underline flex items-center gap-1.5"
                                    >
                                        <span>{{ post.author.name }}</span>
                                        <span class="text-[10px] bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 px-1.5 py-0.5 rounded font-normal">Candidate Profile</span>
                                    </Link>
                                    <span v-else class="font-semibold text-sm">{{ post.author.name }}</span>
                                </div>
                                <span class="text-xs text-gray-500">{{ new Date(post.createdAt).toLocaleString() }}</span>
                            </div>
                            <div class="text-gray-700 dark:text-gray-300 text-sm leading-relaxed" v-html="renderMarkdown(post.content)"></div>
                        </div>
                        <div v-if="posts.length === 0" class="text-center text-gray-500 py-4">No discussions yet.</div>
                    </div>
                    
                    <form v-if="isLoggedIn" @submit.prevent="submitPost" class="flex gap-2">
                        <input v-model="newPost" type="text" class="form-input flex-1" placeholder="Type message (Markdown supported: **bold**, *italic*, `code`, [link](url))..." />
                        <Button type="submit" :disabled="isSubmitting || !newPost.trim()">Post</Button>
                    </form>
                    <div v-else class="p-3 bg-gray-50 dark:bg-gray-800 text-center text-sm text-gray-500 rounded">
                        <Link href="/login" class="text-blue-600 hover:underline font-medium">Log in</Link> to participate in the discussion.
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Project Tags (Technologies) -->
                <div v-if="position.projectTags && position.projectTags.length > 0" class="panel">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Required Technologies</h3>
                    <div class="flex flex-wrap gap-2">
                        <Link 
                            v-for="tag in position.projectTags" 
                            :key="tag" 
                            :href="`/search?q=${encodeURIComponent(tag)}`"
                            class="px-2.5 py-1 bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border border-blue-200 dark:border-blue-800 rounded-full text-xs font-medium hover:bg-blue-100 transition-colors"
                        >
                            #{{ tag }}
                        </Link>
                    </div>
                </div>

                <div class="panel">
                    <h3 class="text-lg font-semibold border-b pb-2 mb-4">Required Attributes</h3>
                    <ul class="space-y-2">
                        <li v-for="attr in position.attributes" :key="attr.id" class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                            <span>{{ attr.name }}</span>
                            <span class="text-xs bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">{{ attr.type }}</span>
                        </li>
                        <li v-if="position.attributes.length === 0" class="text-gray-500">No specific attributes required.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
