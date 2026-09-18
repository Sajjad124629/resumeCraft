<script setup lang="ts">
import { useVModel } from '@vueuse/core';
import vSelect from 'vue-select';
import { h } from 'vue';
import iconCaretDown from '@/components/icon/icon-caret-down.vue';
import iconX from '@/components/icon/icon-x.vue';
interface Option {
    [key: string]: number | string;
}
type SelectOption = Record<string, any>;
const props = defineProps<{

    required?: boolean
    options: SelectOption
    label: string
    reduce?: Function
    placeholder?: string
    // autocomplete?: string
    // placeholder?: string
    // required?: boolean
    // disabled?: boolean
    // readonly?: boolean
    defaultValue?: string | number
    modelValue?: string | number
    class?: string
}>()
vSelect.props.components.default = () => ({
    Deselect: {
        render() {
            return h('span', {}, [
                h(iconX, { width: '16', height: '16' })
            ])
        },
    },
    OpenIndicator: {
        render: () => h('span', h(iconCaretDown)),
    },
});
const emit = defineEmits<{
    (e: 'update:modelValue', payload: string | number): void
}>()
const modelValue = useVModel(props, 'modelValue', emit, {
    passive: true,
    defaultValue: props.defaultValue,
})
</script>
<template>
    <v-select v-model="modelValue" :options="options" :label="label" :placeholder="placeholder" :reduce="reduce"
        :class="class">
        <template #search="{ attributes, events }">
            <input class="vs__search customSelectStyle" :required="required && (
                (Array.isArray(modelValue) && modelValue.length === 0)
                || (!Array.isArray(modelValue) && !modelValue)
            )" v-bind="attributes" v-on="events" />
        </template>
    </v-select>
</template>
