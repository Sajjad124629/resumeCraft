<script setup lang="ts">
import IconLoader from '@/Components/icon/icon-loader.vue';
import InputError from '@/Components/InputError.vue';
import TextLink from '@/Components/TextLink.vue';
import Button from '@/Components/ui/button/Button.vue';
import InputFile from '@/Components/ui/input-file/InputFile.vue';
import Input from '@/Components/ui/input/Input.vue';
import Label from '@/Components/ui/label/Label.vue';
import DynamicSelect from '@/Components/ui/select/DynamicSelect.vue';
import StaticSelect from '@/Components/ui/select/StaticSelect.vue';
import { usePreviewImage } from '@/Composables/usePreviewImage';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Delete01Icon } from '@hugeicons/core-free-icons';

defineOptions({
    layout: AppLayout
});

const form = useForm({
    name: '',
    phone_number: '',
    email: '',
    address: '',
    department_id: '',
    designation_id: '',
    shift_id: '',
    staff_id: '',
    country_id: '',
    state_id: '',
    city_id: '',
    is_sales_agent: 0,
    basic_salary: '',
    sale_commission_percent: '',
    sales_target: [] as Array<{ total_sale_amount_from: string | number, total_sale_amount_to: string | number, commission_percent: string | number }>,
    image: null as File | null,
    add_user: false,
    username: '',
    password: '',
    role_id: '',
});

const addSalesTarget = () => {
    form.sales_target.push({ total_sale_amount_from: '', total_sale_amount_to: '', commission_percent: '' });
};

const removeSalesTarget = (index: number) => {
    form.sales_target.splice(index, 1);
};

defineProps<{
    roles: Array<{ id: number, name: string }>,
}>()

const isSalesAgentOptions = [
    { id: 1, name: 'Yes' },
    { id: 0, name: 'No' },
]

const { preview: imagePreview } = usePreviewImage(
    computed(() => form.image),
    null
)
</script>
<template>

    <Head :title="__('Add Employee')" />
    <div>
        <div class="panel pb-3 mt-6">
            <div class="mb-8 border-b ml-[-1.3rem] mr-[-1.3rem] border-gray dark:border-gray-dark">
                <div class="ml-5 mb-4">
                    <h5 class="font-semibold text-lg">{{ __('Add Employee') }}</h5>
                    <small><em>{{ __('The field labels marked with * are required input fields.') }}</em></small>
                </div>
            </div>
            <form @submit.prevent="form.post(route('hrm.employee.store'))">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-5">
                    <div>
                        <Label :isRequired="true" for="name">{{ __('Name') }}</Label>
                        <Input type="text" v-model.trim="form.name" :placeholder="__('Name')" class="form-input"
                            required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="email">{{ __('Email') }}</Label>
                        <Input type="email" v-model.trim="form.email" :placeholder="__('Email')" class="form-input"
                            required />
                        <InputError :message="form.errors.email" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="phone_number">{{ __('Phone Number') }}</Label>
                        <Input type="text" v-model.trim="form.phone_number" :placeholder="__('Phone Number')"
                            class="form-input" required />
                        <InputError :message="form.errors.phone_number" />
                    </div>

                    <div>
                        <Label :isRequired="true" for="department_id">{{ __('Department') }}</Label>
                        <DynamicSelect v-model="form.department_id" :url="route('hrm.departments.data')" label="name"
                            method="POST" :placeholder="__('Select Department')" :required="!form.department_id" />
                        <InputError :message="form.errors.department_id" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="designation_id">{{ __('Designation') }}</Label>
                        <DynamicSelect v-model="form.designation_id" :url="route('hrm.designation.data')" label="title"
                            method="POST" :placeholder="__('Select Designation')" :required="!form.designation_id" />
                        <InputError :message="form.errors.designation_id" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="shift_id">{{ __('Shift') }}</Label>
                        <DynamicSelect v-model="form.shift_id" :url="route('hrm.shifts.data')" label="display_name"
                            method="POST" :placeholder="__('Select Shift')" :required="!form.shift_id" />
                        <InputError :message="form.errors.shift_id" />
                    </div>

                    <div>
                        <Label for="staff_id">{{ __('Staff ID') }}</Label>
                        <Input type="text" v-model.trim="form.staff_id" :placeholder="__('Staff ID')"
                            class="form-input" />
                        <InputError :message="form.errors.staff_id" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="basic_salary">{{ __('Basic Salary') }}</Label>
                        <Input type="number" min="0" v-model.trim="form.basic_salary" :placeholder="__('Basic Salary')"
                            class="form-input" required />
                        <InputError :message="form.errors.basic_salary" />
                    </div>
                    <div>
                        <Label for="sale_commission_percent">{{ __('Sale Commission %') }}</Label>
                        <Input type="number" min="0" max="100" v-model.trim="form.sale_commission_percent"
                            :placeholder="__('Commission Percent')" class="form-input" />
                        <InputError :message="form.errors.sale_commission_percent" />
                    </div>

                    <div>
                        <Label for="country_id">{{ __('Country') }}</Label>
                        <DynamicSelect v-model="form.country_id" :url="route('country.data')" label="name" method="GET"
                            :placeholder="__('Select Country')" />
                        <InputError :message="form.errors.country_id" />
                    </div>
                    <div>
                        <Label for="state_id">{{ __('State') }}</Label>
                        <DynamicSelect v-model="form.state_id"
                            :url="route('state.data', { country_id: form.country_id })" label="name" method="GET"
                            :placeholder="__('Select State')" />
                        <InputError :message="form.errors.state_id" />
                    </div>
                    <div>
                        <Label for="city_id">{{ __('City') }}</Label>
                        <DynamicSelect v-model="form.city_id" :url="route('city.data', { state_id: form.state_id })"
                            label="name" method="GET" :placeholder="__('Select City')" />
                        <InputError :message="form.errors.city_id" />
                    </div>

                    <div>
                        <Label for="address">{{ __('Address') }}</Label>
                        <Input type="text" v-model.trim="form.address" :placeholder="__('Address')"
                            class="form-input" />
                        <InputError :message="form.errors.address" />
                    </div>
                    <div>
                        <Label for="is_sales_agent">{{ __('Is Sales Agent') }}</Label>
                        <StaticSelect v-model="form.is_sales_agent" :options="isSalesAgentOptions" label="name"
                            :placeholder="__('Is Sales Agent')" :reduce="(opt: { id: number }) => opt.id" />
                        <InputError :message="form.errors.is_sales_agent" />
                    </div>
                    <div class="flex justify-start items-center">
                        <div class="w-full">
                            <Label for="image">{{ __('Employee Image') }}</Label>
                            <InputFile v-model="form.image" accept="image/*"
                                class="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file-ml-5 file:text-white file:hover:bg-primary" />
                            <InputError :message="form.errors.image" />
                        </div>
                        <div class="ml-2 mt-6">
                            <img :src="imagePreview" alt="" class="w-10 h-10 object-cover rounded-full">
                        </div>
                    </div>
                </div>
                <!-- Sales Target Dynamic Form -->
                <div v-if="form.is_sales_agent === 1" class="mt-8 mb-4 border-t pt-4 border-gray dark:border-gray-dark">
                    <div class="flex justify-between items-center mb-4">
                        <h5 class="font-semibold text-lg">{{ __('Sales Targets') }}</h5>
                        <Button type="button" class="btn btn-primary btn-sm" @click="addSalesTarget">
                            {{ __('Add More') }}
                        </Button>
                    </div>

                    <div v-for="(target, index) in form.sales_target" :key="index"
                        class="grid grid-cols-1 lg:grid-cols-[1fr_1fr_1fr_auto] gap-6 mb-5 bg-gray-50 dark:bg-black/20 p-4 rounded-md items-end">
                        <div>
                            <Label :isRequired="true" :for="'total_sale_amount_from_' + index">{{ __('Amount From')
                                }}</Label>
                            <Input type="number" step="0.01" min="0" v-model.trim="target.total_sale_amount_from"
                                :placeholder="__('Amount From')" class="form-input" required />
                            <InputError :message="(form.errors as any)[`sales_target.${index}.total_sale_amount_from`]" />
                        </div>
                        <div>
                            <Label :isRequired="true" :for="'total_sale_amount_to_' + index">{{ __('Amount To') }}</Label>
                            <Input type="number" step="0.01" min="0" v-model.trim="target.total_sale_amount_to"
                                :placeholder="__('Amount To')" class="form-input" required />
                            <InputError :message="(form.errors as any)[`sales_target.${index}.total_sale_amount_to`]" />
                        </div>
                        <div>
                            <Label :isRequired="true" :for="'commission_percent_' + index">{{ __('Commission %')
                                }}</Label>
                            <Input type="number" step="0.01" min="0" max="100" v-model.trim="target.commission_percent"
                                :placeholder="__('Commission Percent')" class="form-input" required />
                            <InputError :message="(form.errors as any)[`sales_target.${index}.commission_percent`]" />
                        </div>
                        <div>
                            <Button type="button" class="btn btn-danger flex justify-center items-center h-[42px]"
                                @click="removeSalesTarget(index)" v-tippy='__("Delete")'>
                                <HugeiconsIcon :icon="Delete01Icon" :size="20" color="currentColor"
                                    :stroke-width="1.5" />
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Add User Section -->
                <div class="mt-8 mb-4 border-t pt-4 border-gray dark:border-gray-dark">
                    <Label class="flex items-center cursor-pointer" :isIcon="true" :iconMessage="__('If check employee will be able to login with username and password you set.')">
                        <input type="checkbox" v-model="form.add_user" class="form-checkbox" />
                        <span class="ml-2 font-semibold text-lg" >{{ __('Add User Account for Employee') }}</span>
                    </Label>
                </div>

                <div v-if="form.add_user"
                    class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-5 bg-gray-50 dark:bg-black/20 p-4 rounded-md">
                    <div>
                        <Label :isRequired="true" for="username">{{ __('Username') }}</Label>
                        <Input type="text" v-model.trim="form.username" :placeholder="__('Username')" class="form-input"
                            :required="form.add_user" />
                        <InputError :message="form.errors.username" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="password">{{ __('Password') }}</Label>
                        <Input type="password" v-model.trim="form.password" :placeholder="__('Password')"
                            class="form-input" :required="form.add_user" />
                        <InputError :message="form.errors.password" />
                    </div>
                    <div>
                        <Label :isRequired="true" for="role_id">{{ __('Role') }}</Label>
                        <StaticSelect v-model="form.role_id" :options="roles" label="name"
                            :placeholder="__('Select Role')" :reduce="(role: { id: number }) => role.id"
                            :required="form.add_user" />
                        <InputError :message="form.errors.role_id" />
                    </div>
                </div>

                <div class="flex justify-end items-center mt-8 mb-4">
                    <TextLink :href="route('hrm.employee')">
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
