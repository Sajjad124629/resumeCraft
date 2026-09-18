<script setup lang="ts">
import { ref } from 'vue';
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
import { Delete01Icon, PlusSignIcon } from '@hugeicons/core-free-icons';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    availableAttributes: any[];
}>();

const form = useForm({
    title: '',
    shortDescription: '',
    company: '',
    level: '',
    isPublic: true,
    maxProjects: 3,
    projectTags: [] as string[],
    attributes: [] as number[],
    accessRules: [] as { attributeId: number | null, operator: string, value: string }[],
});

const tagsInput = ref('');

const isPublicOptions = [
    { id: 1, name: 'Yes' },
    { id: 0, name: 'No' },
];

function addAccessRule() {
    form.accessRules.push({ attributeId: null, operator: '=', value: '' });
}

function removeAccessRule(index: number) {
    form.accessRules.splice(index, 1);
}

const submit = () => {
    form.projectTags = tagsInput.value.split(',').map((t: string) => t.trim()).filter(Boolean);
    form.post(route('app_position_create'));
};
</script>

<template>

    <Head :title="__('Create Position')" />

    <div>
        <div class="panel pb-3 mt-6 bg-white dark:bg-gray-800 rounded shadow p-5">
            <div class="mb-8 border-b ml-[-1.3rem] mr-[-1.3rem] border-gray-200 dark:border-gray-700">
                <div class="ml-5 mb-4">
                    <h5 class="font-semibold text-lg">{{ __('Create Position') }}</h5>
                    <small class="text-gray-500"><em>{{ __('The field labels marked with * are required input fields.')
                            }}</em></small>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-5">
                    <div>
                        <Label :isRequired="true" for="title">{{ __('Title') }}</Label>
                        <Input type="text" v-model.trim="form.title" :placeholder="__('Position Title')"
                            class="form-input mt-1 block w-full" required />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div>
                        <Label for="company">{{ __('Company') }}</Label>
                        <Input type="text" v-model.trim="form.company" :placeholder="__('Company Name')"
                            class="form-input mt-1 block w-full" />
                        <InputError :message="form.errors.company" />
                    </div>

                    <div>
                        <Label for="level">{{ __('Level') }}</Label>
                        <Input type="text" v-model.trim="form.level" :placeholder="__('e.g. Senior, Junior')"
                            class="form-input mt-1 block w-full" />
                        <InputError :message="form.errors.level" />
                    </div>

                    <div class="lg:col-span-3">
                        <Label for="shortDescription">{{ __('Description') }}</Label>
                        <Textarea id="shortDescription" v-model.trim="form.shortDescription"
                            :placeholder="__('Short Description')" class="mt-1 block w-full" :rows="3" />
                        <InputError :message="form.errors.shortDescription" />
                    </div>

                    <div>
                        <Label for="maxProjects">{{ __('Max Projects Allowed') }}</Label>
                        <Input type="number" min="0" v-model.number="form.maxProjects"
                            class="form-input mt-1 block w-full" />
                        <InputError :message="form.errors.maxProjects" />
                    </div>

                    <div>
                        <Label for="isPublic">{{ __('Is Public') }}</Label>
                        <StaticSelect v-model="form.isPublic" :options="isPublicOptions" label="name"
                            :placeholder="__('Public?')" :reduce="(opt: any) => opt.id === 1" class="mt-1" />
                        <InputError :message="form.errors.isPublic" />
                    </div>

                    <div class="lg:col-span-3">
                        <Label for="projectTags">{{ __('Relevant Project Technology Tags') }}</Label>
                        <Input type="text" v-model.trim="tagsInput"
                            placeholder="e.g. PHP, Vue.js, Docker (comma-separated)"
                            class="form-input mt-1 block w-full" />
                        <small class="text-gray-500">Candidate projects containing these tags will be automatically
                            selected for the generated CV (up to max projects).</small>
                    </div>
                </div>

                <!-- Attributes Multiple Select -->
                <div class="mt-8 mb-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                    <h5 class="font-semibold text-lg mb-4">{{ __('Required Attributes') }}</h5>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <label v-for="attr in props.availableAttributes" :key="attr.id"
                            class="inline-flex items-center space-x-2 border p-2 rounded cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                            <input type="checkbox" :value="attr.id" v-model="form.attributes" class="form-checkbox" />
                            <span>{{ attr.name }}</span>
                        </label>
                    </div>
                </div>

                <!-- Access Rules Dynamic Form -->
                <div class="mt-8 mb-4 border-t pt-4 border-gray-200 dark:border-gray-700">
                    <div class="flex justify-between items-center mb-4">
                        <h5 class="font-semibold text-lg">{{ __('Access Rules') }}</h5>
                        <Button type="button" variant="outline" size="sm" @click="addAccessRule">
                            {{ __('Add Rule') }}
                        </Button>
                    </div>

                    <div v-for="(rule, index) in form.accessRules" :key="index"
                        class="grid grid-cols-1 lg:grid-cols-[1fr_1fr_1fr_auto] gap-6 mb-5 bg-gray-50 dark:bg-gray-900/50 p-4 rounded-md items-end">
                        <div>
                            <Label :isRequired="true" :for="'attr_' + index">{{ __('Attribute') }}</Label>
                            <select v-model="rule.attributeId" required
                                class="form-select w-full mt-1 border-gray-300 rounded-md">
                                <option :value="null">{{ __('Select Attribute') }}</option>
                                <option v-for="a in availableAttributes" :key="a.id" :value="a.id">{{ a.name }}</option>
                            </select>
                        </div>
                        <div>
                            <Label :isRequired="true" :for="'op_' + index">{{ __('Operator') }}</Label>
                            <select v-model="rule.operator" required
                                class="form-select w-full mt-1 border-gray-300 rounded-md">
                                <option value="=">=</option>
                                <option value=">">&gt;</option>
                                <option value="<">&lt;</option>
                            </select>
                        </div>
                        <div>
                            <Label :isRequired="true" :for="'val_' + index">{{ __('Value') }}</Label>
                            <Input type="text" v-model.trim="rule.value" :placeholder="__('Value')"
                                class="form-input w-full mt-1" required />
                        </div>
                        <div>
                            <Button type="button" variant="destructive"
                                class="flex justify-center items-center h-[42px]" @click="removeAccessRule(index)"
                                title="Delete">
                                <HugeiconsIcon :icon="Delete01Icon" :size="20" color="currentColor"
                                    :stroke-width="1.5" />
                            </Button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end items-center mt-8 mb-4">
                    <TextLink :href="route('app_position_index')">
                        <Button type="button" variant="outline"
                            class="rounded-xl px-5 py-2.5 shadow-xs hover:shadow transition active:scale-95">
                            {{ __('Cancel') }}
                        </Button>
                    </TextLink>
                    <Button type="submit" :disabled="form.processing"
                        class="ml-4 bg-gradient-to-r from-primary to-blue-600 hover:from-primary/90 hover:to-blue-700 !text-white font-bold shadow-[0_4px_14px_rgba(67,97,238,0.35),inset_0_1px_0_rgba(255,255,255,0.3)] hover:-translate-y-0.5 active:scale-95 transition-all duration-200 rounded-xl px-5 py-2.5">
                        {{ form.processing ? __('Creating...') : __('Create Position') }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
