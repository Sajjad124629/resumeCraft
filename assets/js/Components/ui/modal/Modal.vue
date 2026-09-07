<script setup lang="ts">
import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogOverlay } from '@headlessui/vue';
import iconX from '@/components/icon/icon-x.vue';
import { HtmlHTMLAttributes } from 'vue';
const props = withDefaults(defineProps<{
    isModalShow: boolean;
    title?: string;
    position?:string
    isStatic?:boolean
    width?:string
    animate?:string
    modalContainerClass?:string
}>(), {
    position: 'items-center',
    isStatic: false,
    width: 'max-w-lg', // default width -> extras-large:max-w-5xl,large:max-w-xl,sm:max-w-sm
})

const emit = defineEmits(['update:isModalShow']);
const closeModal = () => {
    if(!props.isStatic)
    emit('update:isModalShow', false);
}
</script>
<template>
    <div>
        <TransitionRoot appear :show="isModalShow" as="template">
            <Dialog as="div" @close="closeModal" class="relative z-[51]">
                <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0"
                    enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                    <DialogOverlay class="fixed inset-0 bg-[black]/60" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full justify-center px-4 py-8" :class="props.position">
                        <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100" leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                            <DialogPanel
                                class="panel border-0 p-0 rounded-lg overflow-hidden w-full  text-black dark:text-white-dark animate__animated animate__fadeInUp" :class="props.width">
                                <button type="button"
                                    class="absolute top-4 ltr:right-4 rtl:left-4 text-gray-400 hover:text-gray-800 dark:hover:text-gray-600 outline-none"
                                    @click="emit('update:isModalShow', false);">
                                    <iconX />
                                </button>
                                <div
                                    class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">
                                    {{props.title}}
                                </div>
                                <div class="p-5" :class="props.modalContainerClass">
                                    <slot/>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>
