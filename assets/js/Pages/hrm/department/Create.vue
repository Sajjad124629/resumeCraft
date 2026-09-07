<script setup lang="ts">
import IconLoader from '@/Components/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import Button from '@/Components/ui/button/Button.vue';
import Input from '@/Components/ui/input/Input.vue';
import Label from '@/Components/ui/label/Label.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({
    layout: AppLayout,
});

const form = useForm({
    name: '',
});
</script>

<template>

    <Head :title="__('Add Department')" />
    <div>
        <div class="panel pb-3 mt-6">
            <div class="mb-8 border-b ml-[-1.3rem] mr-[-1.3rem] border-gray dark:border-gray-dark">
                <div class="ml-5 mb-4">
                    <h5 class="font-semibold text-lg">{{ __('Add Department') }}</h5>
                    <small><em>{{ __('The field labels marked with * are required input fields.') }}</em></small>
                </div>
            </div>
            <form @submit.prevent="form.post(route('hrm.departments.store'))">
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <Label :isRequired="true" for="name">{{ __('Name') }}</Label>
                        <Input type="text" v-model.trim="form.name" :placeholder="__('Name')" class="form-input"
                            required />
                        <InputError :message="form.errors.name" />
                    </div>
                </div>
                <div class="flex justify-end items-center mt-8 mb-4">
                    <TextLink :href="route('hrm.departments')">
                        <Button type="button" class="btn btn-danger">{{ __('Cancel') }}</Button>
                    </TextLink>
                    <Button type="submit" :disabled="form.processing" class="btn btn-primary ltr:ml-4 rtl:mr-4">
                        <IconLoader v-if="form.processing"
                            class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
                        {{ __('Create') }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
