<script setup lang="ts">
import IconEye from '@/components/icon/icon-eye.vue';
import IconHorizontalDots from '@/components/icon/icon-horizontal-dots.vue';
import IconPencil from '@/components/icon/icon-pencil.vue';
import IconSquareRotated from '@/components/icon/icon-square-rotated.vue';
import IconStar from '@/components/icon/icon-star.vue';
import IconTrashLines from '@/components/icon/icon-trash-lines.vue';
import IconUser from '@/components/icon/icon-user.vue';
import { useHumanDate } from '@/composables/DateFormat';
import { useTruncate } from '@/composables/useTruncate';
import { useAppStore } from '@/stores';
import { Note } from '@/types/note';
const store = useAppStore();
const { dateFormat } = useHumanDate();
const { truncateText } = useTruncate();
const props = withDefaults(defineProps<{ note: Note,tags: Array<{ id: string, name: string, color: string, color_name: string }> }>(), {

})
const emit = defineEmits(['deleteNote','editNote','switchTag','switchFavorite','viewNote']);
// console.log(props.note.user.user_detail?.image);
</script>
<template>

    <div>
        <div class="panel pb-12"
            :class="props.note.tag ? `bg-${props.note.tag.color_name}-light shadow-${props.note.tag.color_name}` : `dark:shadow-dark`">
            <div class="min-h-[142px]">
                <div class="flex justify-between">
                    <div class="flex items-center w-max">
                        <div class="flex-none">
                            <div class="p-0.5 bg-gray-300 dark:bg-gray-700 rounded-full">
                                <img class="h-8 w-8 rounded-full object-cover"
                                    :src="props.note.user.user_detail?.image ? '/storage/' + props.note.user.user_detail?.image : '/image/profile/user-1.jpg'" />
                            </div>
                        </div>
                        <div class="ltr:ml-2 rtl:mr-2">
                            <div class="font-semibold">{{ props.note.user.user_detail?.fullname }}</div>
                            <div class="text-sx text-white-dark">{{ dateFormat(props.note.created_at) }}</div>
                        </div>
                    </div>
                    <div class="dropdown">
                        <Popper :placement="store.rtlClass === 'rtl' ? 'bottom-start' : 'bottom-end'"
                            offsetDistance="0">
                            <button type="button"
                                :class="props.note.tag ? `text-${props.note.tag.color_name}` : `text-primary`">
                                <icon-horizontal-dots class="rotate-90 opacity-70 hover:opacity-100" />
                            </button>
                            <template #content="{ close }">
                                <ul @click="close()" class="text-sm font-medium">
                                    <li>
                                        <a href="javascript:;" class="w-full" @click="emit('editNote',props.note)">
                                            <icon-pencil class="w-4 h-4 ltr:mr-3 rtl:ml-3 shrink-0" />
                                            Edit
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:;" class="w-full" @click="emit('deleteNote',props.note.id)">
                                            <icon-trash-lines class="w-4.5 h-4.5 ltr:mr-3 rtl:ml-3 shrink-0" />
                                            Delete
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:;" class="w-full" @click="emit('viewNote',note)">
                                            <icon-eye class="w-4.5 h-4.5 ltr:mr-3 rtl:ml-3 shrink-0" />
                                            View
                                        </a>
                                    </li>
                                </ul>
                            </template>
                        </Popper>
                    </div>
                </div>
                <div>
                    <h4 class="font-semibold mt-4">{{ props.note.title }}</h4>
                    <p class="text-white-dark mt-2">{{ truncateText(props.note.description, 70) }}</p>
                </div>
            </div>
            <div class="absolute bottom-5 left-0 w-full px-5">
                <div class="flex items-center justify-between mt-2">
                    <div class="dropdown">
                        <Popper :placement="store.rtlClass === 'rtl' ? 'bottom-start' : 'bottom-end'"
                            offsetDistance="0">
                            <button type="button" :class="`text-${props.note.tag?.color_name || ''}`">
                                <icon-square-rotated :color="props.note.tag?.color || ''" :colorName="props.note.tag?.color_name || ''" />
                            </button>
                            <template #content="{ close }">
                                <ul @click="close()">
                                    <li v-for="tag in tags" :key="tag.id">
                                        <a href="javascript:;" @click="emit('switchTag',{id:note.id,tag_id:tag.id})" class="w-full flex items-center p-2 hover:bg-white-dark/10" :class="`text-${tag.color_name}`">
                                            <icon-square-rotated class="ltr:mr-2 rtl:ml-2" :color="tag.color" :colorName="tag.color_name" />
                                            {{ tag.name }}
                                        </a>
                                    </li>
                                </ul>
                            </template>
                        </Popper>
                    </div>
                    <div class="flex items-center">
                        <button type="button" class="text-danger" @click="emit('deleteNote',props.note.id)">
                            <icon-trash-lines />
                        </button>
                        <button type="button" class="text-warning group ltr:ml-2 rtl:mr-2">
                            <icon-star class="w-4.5 h-4.5 group-hover:fill-warning" :class="{ 'fill-warning': note.is_favorite }" @click="emit('switchFavorite',{id:note.id,is_favorite:!note.is_favorite})" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>
