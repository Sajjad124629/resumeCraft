<script setup lang="ts">
import IconLoader from '@/Components/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import Button from '@/Components/ui/button/Button.vue';
import Input from '@/Components/ui/input/Input.vue';
import Label from '@/Components/ui/label/Label.vue';
import FlatPicker from '@/Components/ui/date/FlatPicker.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

interface Shift {
    id: number;
    name: string;
    start_time: string;
    end_time: string;
    grace_in: number | string;
    grace_out: number | string;
    is_active: number;
}

const props = defineProps<{ shift: Shift }>();

const form = useForm({
    name: props.shift.name || '',
    start_time: props.shift.start_time || '',
    end_time: props.shift.end_time || '',
    grace_in: props.shift.grace_in || '',
    grace_out: props.shift.grace_out || '',
    is_active: props.shift.is_active ?? 1,
});
</script>

<template>

    <Head :title="__('Edit Shift')" />
    <div>
        <div class="panel pb-3 mt-6">
            <div class="mb-8 border-b ml-[-1.3rem] mr-[-1.3rem] border-gray dark:border-gray-dark">
                <div class="ml-5 mb-4">
                    <h5 class="font-semibold text-lg">{{ __('Edit Shift') }}</h5>
                    <small><em>{{ __('The field labels marked with * are required input fields.') }}</em></small>
                </div>
            </div>
            <form @submit.prevent="form.post(route('hrm.shifts.update', props.shift.id))">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div>
                        <Label :isRequired="true" for="name" :isIcon="true" :iconMessage="__('Enter the name of the shift,eg:Morning,Evening')">{{ __('Name') }}</Label>
                        <Input type="text" v-model.trim="form.name" :placeholder="__('Name')" class="form-input"
                            required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="start_time" :isIcon="true" :iconMessage="__('Select the start time of the shift,eg:09:00AM')">{{ __('Start Time') }}</Label>
                        <FlatPicker v-model="form.start_time" :placeholder="__('09:00AM')" inputClass="form-input"
                            :enableTime="true" :noCalendar="true" dateFormat="h:i K" altInput altFormat="h:i K"
                            required />
                        <InputError :message="form.errors.start_time" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="end_time" :isIcon="true" :iconMessage="__('Select the end time of the shift,eg:06:00PM')">{{ __('End Time') }}</Label>
                        <FlatPicker v-model="form.end_time" :placeholder="__('06:00PM')" inputClass="form-input"
                            :enableTime="true" :noCalendar="true" dateFormat="h:i K" altInput altFormat="h:i K"
                            required />
                        <InputError :message="form.errors.end_time" />
                    </div>
                    <div>
                        <Label for="grace_in" :isIcon="true" :iconMessage="__('Enter the grace in time for the shift,eg:10')">{{ __('Grace In (Minutes)') }}</Label>
                        <Input type="number" min="0" v-model.trim="form.grace_in" :placeholder="__('Grace In')" class="form-input" />
                        <InputError :message="form.errors.grace_in" />
                    </div>
                    <div>
                        <Label for="grace_out" :isIcon="true" :iconMessage="__('Enter the grace out time for the shift,eg:10')">{{ __('Grace Out (Minutes)') }}</Label>
                        <Input type="number" min="0" v-model.trim="form.grace_out" :placeholder="__('Grace Out')" class="form-input" />
                        <InputError :message="form.errors.grace_out" />
                    </div>
                </div>
                <div class="flex justify-end items-center mt-8 mb-4">
                    <TextLink :href="route('hrm.shifts')">
                        <Button type="button" class="btn btn-danger">{{ __('Cancel') }}</Button>
                    </TextLink>
                    <Button type="submit" :disabled="form.processing" class="btn btn-primary ltr:ml-4 rtl:mr-4">
                        <IconLoader v-if="form.processing"
                            class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
                        {{ __('Update') }}
                    </Button>
                </div>
            </form>
        </div>
    </div>
</template>
