<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { route } from '@/route';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Label } from '@/Components/ui/label';
import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import StaticSelect from '@/Components/ui/select/StaticSelect.vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Delete01Icon } from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    categories: any[];
}>();

const form = useForm({
    name: '',
    description: '',
    type: 'string',
    options: [] as string[],
    tuning: {
        maxLength: null as number | null,
        regex: '',
        min: null as number | null,
        max: null as number | null,
    },
    category_id: null as number | null,
});

const typeOptions = [
    { id: 'string', name: 'String (Single-line plain text)' },
    { id: 'text', name: 'Text (Markdown-formatted text)' },
    { id: 'image', name: 'Image (Cloud storage drag-n-drop)' },
    { id: 'number', name: 'Numeric' },
    { id: 'date', name: 'Date' },
    { id: 'period', name: 'Period (Date range)' },
    { id: 'boolean', name: 'Boolean (Checkbox)' },
    { id: 'select', name: 'One of many (Dropdown)' },
];

function addOption() {
    form.options.push('');
}

function removeOption(index: number) {
    form.options.splice(index, 1);
}

const submit = () => {
    const payload = form.transform((data) => {
        let finalOptions: any = {};
        if (data.type === 'select') {
            finalOptions.choices = data.options;
        } else if (data.type === 'string' || data.type === 'text') {
            finalOptions.maxLength = data.tuning.maxLength;
            finalOptions.regex = data.tuning.regex;
        } else if (data.type === 'number') {
            finalOptions.min = data.tuning.min;
            finalOptions.max = data.tuning.max;
        }
        return {
            ...data,
            options: finalOptions
        };
    });

    payload.post(route('app_attribute_create'));
};
</script>

<template>

    <Head :title="__('Create Attribute')" />

    <div>
        <div class="panel pb-3 mt-6 bg-white dark:bg-gray-800 rounded shadow p-5">
            <div class="mb-8 border-b ml-[-1.3rem] mr-[-1.3rem] border-gray-200 dark:border-gray-700">
                <div class="ml-5 mb-4">
                    <h5 class="font-semibold text-lg">{{ __('Create Attribute') }}</h5>
                    <small class="text-gray-500"><em>{{ __('The field labels marked with * are required input fields.')
                            }}</em></small>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-5">
                    <div>
                        <Label :isRequired="true" for="name">{{ __('Name') }}</Label>
                        <Input type="text" v-model.trim="form.name" :placeholder="__('Attribute Name')"
                            class="form-input mt-1 block w-full" required />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <Label :isRequired="true" for="category_id">{{ __('Category') }}</Label>
                        <StaticSelect v-model="form.category_id" :options="props.categories" label="name"
                            :placeholder="__('Select Category')" :reduce="(cat: any) => cat.id" class="mt-1" />
                        <InputError :message="form.errors.category_id" />
                    </div>

                    <div>
                        <Label :isRequired="true" for="type">{{ __('Type') }}</Label>
                        <StaticSelect v-model="form.type" :options="typeOptions" label="name"
                            :placeholder="__('Select Type')" :reduce="(opt: any) => opt.id" class="mt-1" />
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="lg:col-span-3">
                        <Label for="description">{{ __('Description') }}</Label>
                        <Textarea id="description" v-model.trim="form.description"
                            :placeholder="__('Short Description')" class="mt-1 block w-full" :rows="3" />
                        <InputError :message="form.errors.description" />
                    </div>
                </div>

                <!-- Select Options Dynamic Form -->
                <div v-if="form.type === 'select'" class="mt-8 mb-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-4">
                        <h5 class="font-semibold text-lg">{{ __('Select Options') }}</h5>
                        <Button type="button" variant="outline" size="sm" @click="addOption">
                            {{ __('Add Option') }}
                        </Button>
                    </div>

                    <div v-for="(option, index) in form.options" :key="index"
                        class="flex gap-4 items-end mb-4 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-md">
                        <div class="flex-grow">
                            <Label :isRequired="true" :for="'opt_' + index">{{ __('Option Value') }}</Label>
                            <Input type="text" v-model.trim="form.options[index]" :placeholder="__('Option')"
                                class="form-input w-full mt-1" required />
                        </div>
                        <div>
                            <Button type="button" variant="destructive"
                                class="flex justify-center items-center h-[42px]" @click="removeOption(index)"
                                title="Delete">
                                <HugeiconsIcon :icon="Delete01Icon" :size="20" color="currentColor"
                                    :stroke-width="1.5" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- String/Text Tuning Options -->
                <div v-if="form.type === 'string' || form.type === 'text'"
                    class="mt-8 mb-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-lg mb-4">{{ __('Validation Tuning') }}</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <Label for="maxLength">{{ __('Maximum Length') }}</Label>
                            <Input type="number" v-model.number="form.tuning.maxLength" placeholder="e.g. 255"
                                class="form-input mt-1 block w-full" />
                        </div>
                        <div>
                            <Label for="regex">{{ __('Regex Validator') }}</Label>
                            <Input type="text" v-model.trim="form.tuning.regex" placeholder="e.g. ^[A-Za-z]+$"
                                class="form-input mt-1 block w-full" />
                            <small class="text-gray-500">Pattern to validate against (HTML5 pattern format)</small>
                        </div>
                    </div>
                </div>

                <!-- Number Tuning Options -->
                <div v-if="form.type === 'number'" class="mt-8 mb-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-lg mb-4">{{ __('Validation Tuning') }}</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <Label for="min">{{ __('Minimum Value') }}</Label>
                            <Input type="number" v-model.number="form.tuning.min"
                                class="form-input mt-1 block w-full" />
                        </div>
                        <div>
                            <Label for="max">{{ __('Maximum Value') }}</Label>
                            <Input type="number" v-model.number="form.tuning.max"
                                class="form-input mt-1 block w-full" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end items-center mt-8 mb-4">
                    <TextLink :href="route('app_attribute_index')">
                        <Button type="button" variant="outline"
                            class="rounded-xl px-5 py-2.5 shadow-xs hover:shadow transition active:scale-95">
                            {{ __('Cancel') }}
                        </Button>
                    </TextLink>
                    <Button type="submit" :disabled="form.processing"
                        class="ml-4 bg-gradient-to-r from-primary to-blue-600 hover:from-primary/90 hover:to-blue-700 !text-white font-bold shadow-[0_4px_14px_rgba(67,97,238,0.35),inset_0_1px_0_rgba(255,255,255,0.3)] hover:-translate-y-0.5 active:scale-95 transition-all duration-200 rounded-xl px-5 py-2.5">
                        {{ form.processing ? __('Creating...') : __('Create Attribute') }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
