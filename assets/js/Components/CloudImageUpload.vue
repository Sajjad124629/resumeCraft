<script setup lang="ts">
import { ref } from 'vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { CloudUploadIcon, Delete01Icon, Image01Icon } from '@hugeicons/core-free-icons';
import { route } from '@/route';

const props = defineProps<{
    modelValue?: string | null;
    label?: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const isDragging = ref(false);
const isUploading = ref(false);
const uploadError = ref('');
const isUrlMode = ref(false);
const directUrl = ref('');

function onDragOver(e: DragEvent) {
    e.preventDefault();
    isDragging.value = true;
}

function onDragLeave(e: DragEvent) {
    e.preventDefault();
    isDragging.value = false;
}

function onDrop(e: DragEvent) {
    e.preventDefault();
    isDragging.value = false;
    const files = e.dataTransfer?.files;
    if (files && files.length > 0) {
        handleFile(files[0]);
    }
}

function onFileInput(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        handleFile(target.files[0]);
    }
}

async function handleFile(file: File) {
    if (!file.type.startsWith('image/')) {
        uploadError.value = 'Please select a valid image file.';
        return;
    }

    uploadError.value = '';
    isUploading.value = true;

    try {
        const formData = new FormData();
        formData.append('file', file);

        const res = await fetch(route('app_upload_to_cloud'), {
            method: 'POST',
            body: formData,
        });

        const json = await res.json();
        if (res.ok && json.success && json.url) {
            emit('update:modelValue', json.url);
        } else {
            uploadError.value = json.error || 'Failed to upload image to external cloud.';
        }
    } catch (e: any) {
        uploadError.value = 'Failed to upload to external cloud storage. Please try pasting a direct image URL.';
    } finally {
        isUploading.value = false;
    }
}

function applyDirectUrl() {
    if (directUrl.value.trim()) {
        emit('update:modelValue', directUrl.value.trim());
        directUrl.value = '';
        isUrlMode.value = false;
    }
}

function removeImage() {
    emit('update:modelValue', '');
}

function onImgError(e: Event) {
    const target = e.target as HTMLImageElement;
    target.src = 'https://ui-avatars.com/api/?name=User&background=6366f1&color=fff&bold=true';
}
</script>

<template>
    <div class="w-full">
        <div class="flex items-center justify-between mb-1">
            <label v-if="label" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ label }}
            </label>
            <button 
                type="button" 
                @click="isUrlMode = !isUrlMode" 
                class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
            >
                {{ isUrlMode ? 'Switch to Drag-and-Drop' : (modelValue ? 'Change with URL' : 'Or Paste Cloud Image URL') }}
            </button>
        </div>

        <!-- Preview if image exists -->
        <div v-if="modelValue && !isUrlMode" class="relative group rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-gray-50 dark:bg-gray-800 p-2.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <img :src="modelValue" @error="onImgError" alt="Uploaded Preview" class="w-14 h-14 object-cover rounded-xl border border-gray-200 dark:border-gray-700 shadow-xs" />
                <div class="text-xs text-gray-500 truncate max-w-[200px]">
                    <span class="font-bold text-gray-800 dark:text-gray-200 block truncate">{{ __('External Cloud Photo') }}</span>
                    <span class="truncate text-[11px] block text-blue-600 dark:text-blue-400">{{ modelValue }}</span>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <button 
                    type="button" 
                    @click="($refs.fileInput as HTMLInputElement)?.click()" 
                    class="p-1.5 text-xs text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg font-semibold transition flex items-center gap-1"
                    title="Change Photo"
                >
                    <HugeiconsIcon :icon="CloudUploadIcon" :size="15" /> {{ __('Change') }}
                </button>
                <button 
                    type="button" 
                    @click="removeImage" 
                    class="p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition"
                    title="Remove Image"
                >
                    <HugeiconsIcon :icon="Delete01Icon" :size="16" />
                </button>
            </div>
            <input 
                ref="fileInput" 
                type="file" 
                accept="image/*" 
                class="hidden" 
                @change="onFileInput" 
            />
        </div>

        <!-- Direct URL input mode -->
        <div v-else-if="isUrlMode" class="p-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-800/50 space-y-2">
            <div class="flex gap-2">
                <input 
                    type="url" 
                    v-model="directUrl" 
                    placeholder="https://res.cloudinary.com/... or https://..." 
                    class="form-input text-xs flex-1" 
                    @keyup.enter="applyDirectUrl"
                />
                <button type="button" @click="applyDirectUrl" class="btn btn-primary btn-sm text-xs px-3">
                    Use URL
                </button>
            </div>
            <p class="text-[11px] text-gray-400">Paste any public cloud image URL (Cloudinary, ImgBB, Unsplash, etc.)</p>
        </div>

        <!-- Drag & drop zone -->
        <div 
            v-else
            class="border-2 border-dashed rounded-xl p-4 text-center cursor-pointer transition flex flex-col items-center justify-center gap-2"
            :class="{
                'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20': isDragging,
                'border-gray-300 dark:border-gray-700 hover:border-gray-400 bg-gray-50/50 dark:bg-gray-800/50': !isDragging
            }"
            @dragover="onDragOver"
            @dragleave="onDragLeave"
            @drop="onDrop"
            @click="($refs.fileInput as HTMLInputElement)?.click()"
        >
            <input 
                ref="fileInput" 
                type="file" 
                accept="image/*" 
                class="hidden" 
                @change="onFileInput" 
            />

            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <HugeiconsIcon :icon="CloudUploadIcon" :size="20" />
            </div>

            <div class="text-xs text-gray-600 dark:text-gray-300">
                <span v-if="isUploading">Uploading to external cloud storage...</span>
                <span v-else-if="isDragging">Drop image here</span>
                <span v-else>
                    <strong class="text-blue-600 dark:text-blue-400 font-semibold">Click to upload</strong> or drag-and-drop
                </span>
            </div>
            <p class="text-[11px] text-gray-400">Direct external cloud storage (PNG, JPG, WebP)</p>
        </div>

        <div v-if="uploadError" class="text-xs text-red-500 mt-1 font-medium">
            {{ uploadError }}
        </div>
    </div>
</template>
