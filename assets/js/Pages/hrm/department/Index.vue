<script setup lang="ts">
import Vue3Datatable from '@bhplugin/vue3-datatable';
import { HugeiconsIcon } from '@hugeicons/vue';
import { Csv01Icon, Delete01Icon, DocumentAttachmentIcon, Download01Icon, FileAddIcon, Pdf02Icon, PencilEdit02Icon, PlusSignIcon, PrinterIcon, Search01Icon } from '@hugeicons/core-free-icons';
import { useDatatable } from '@/Composables/useDatatable';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import Input from '@/Components/ui/input/Input.vue';
import Button from '@/Components/ui/button/Button.vue';
import TextLink from '@/Components/TextLink.vue';
import Modal from '@/Components/ui/modal/Modal.vue';
import IconTrashLines from '@/Components/icon/icon-trash-lines.vue';
import IconLoader from '@/Components/icon/icon-loader.vue';
import InputFile from '@/Components/ui/input-file/InputFile.vue';
import Label from '@/Components/ui/label/Label.vue';
import InputError from '@/Components/InputError.vue';

const { loading, totalRows, rows, params, getData, changeServer, exportTable } = useDatatable('hrm.departments.data');

defineOptions({
    layout: AppLayout,
});

const isDeleteModalShow = ref(false);
const isImportModalShow = ref(false);
const isDeleteMultipleModalShow = ref(false);
const datatableRef = ref<any>(null);
const selectedIds = ref<Array<number>>([]);
const cols = ref([
    { field: 'id', title: '', isUnique: true, hide: true, export: false },
    { field: 'name', title: 'Name' },
    { field: 'action', title: 'Action', sort: false, export: false },
]) || [];
const form = useForm({
    id: '',
    file: null as File | null,
});
const multiForm = useForm({ ids: [] as number[] });

onMounted(() => {
    getData();
});

const deletedModalShow = (id: string) => {
    form.id = id;
    isDeleteModalShow.value = true;
};

const deleteRow = () => {
    form.delete(route('hrm.departments.destroy', form.id), {
        onSuccess: () => {
            isDeleteModalShow.value = false;
            getData();
        },
    });
};

const importCSVForm = () => {
    form.post(route('hrm.departments.import'), {
        onSuccess: () => {
            isImportModalShow.value = false;
            getData();
        },
    });
};

const onRowSelect = (rows: Array<any>) => {
    selectedIds.value = rows.map((r: any) => r.id);
};

const deleteMultiple = () => {
    if (!selectedIds.value.length) return;
    multiForm.ids = selectedIds.value;
    multiForm.post(route('hrm.departments.destroy.multiple'), {
        onSuccess: () => {
            isDeleteMultipleModalShow.value = false;
            if (datatableRef.value && typeof datatableRef.value.clearSelectedRows === 'function') {
                datatableRef.value.clearSelectedRows();
            }
            selectedIds.value = [];
            getData();
        },
    });
};
</script>

<template>

    <Head :title="__('Departments')" />
    <Modal v-model:isModalShow="isImportModalShow" :isStatic="true" :title="__('Import Department')" width="max-w-2xl">
        <form @submit.prevent="importCSVForm">
            <div class="mb-5">
                <small><em>{{ __('The field labels marked with * are required input fields.') }}</em></small>
                <p class="mt-5">{{ __('The correct column order is (name*) and you must follow this.') }}</p>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="w-full">
                    <Label :isRequired="true"  for="image">{{ __('Upload CSV File') }}</Label>
                    <InputFile v-model="form.file" accept=".csv"
                        class="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file-ml-5 file:text-white file:hover:bg-primary" required/>
                    <InputError :message="form.errors.file" />
                </div>
                <div class="w-full">
                     <Label for="image">{{ __('Sample File') }}</Label>
                      <a href="/sampleFile/sample_department.csv" download>
                          <Button type="button" :disabled="form.processing" class="btn btn-success w-full">
                              <HugeiconsIcon :icon="Download01Icon" :size="24" color="currentColor" :stroke-width="1.5" class="mr-3"/>
                              {{ __('Download Sample') }}
                          </Button>
                      </a>
                </div>
            </div>
            <div class="flex justify-end items-center mt-8">
                <Button type="button" class="btn btn-danger" @click="isImportModalShow = false">Cancel</Button>
                <Button type="submit" :disabled="form.processing" class="btn btn-primary ltr:ml-4 rtl:mr-4">
                    <IconLoader v-if="form.processing"
                        class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
                    {{ __('Submit') }}
                </Button>
            </div>
        </form>
    </Modal>
    <Modal v-model:isModalShow="isDeleteModalShow" :isStatic="false" :title="__('Delete Department')"
        modalContainerClass="text-center">
        <form @submit.prevent="deleteRow">
            <div class="text-white bg-danger ring-4 ring-danger/30 p-4 rounded-full w-fit mx-auto">
                <IconTrashLines />
            </div>
            <div class="text-base sm:w-3/4 mx-auto mt-5">{{ __('Are you sure you want to delete this row?') }}</div>
            <div class="flex justify-center items-center mt-8">
                <Button type="button" class="btn btn-danger" @click="isDeleteModalShow = false">Cancel</Button>
                <Button type="submit" class="btn btn-primary ltr:ml-4 rtl:mr-4" :disabled="form.processing">
                    <IconLoader v-if="form.processing"
                        class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
                    {{ __('Delete') }}
                </Button>
            </div>
        </form>
    </Modal>

    <Modal v-model:isModalShow="isDeleteMultipleModalShow" :isStatic="false" :title="__('Delete Selected Departments')"
        modalContainerClass="text-center">
        <form @submit.prevent="deleteMultiple">
            <div class="text-white bg-danger ring-4 ring-danger/30 p-4 rounded-full w-fit mx-auto">
                <IconTrashLines />
            </div>
            <div class="text-base sm:w-3/4 mx-auto mt-5">{{ __('Are you sure you want to delete selected rows?') }}
            </div>
            <div class="flex justify-center items-center mt-8">
                <Button type="button" class="btn btn-danger" @click="isDeleteMultipleModalShow = false">Cancel</Button>
                <Button type="submit" class="btn btn-primary ltr:ml-4 rtl:mr-4" :disabled="multiForm.processing">
                    <IconLoader v-if="multiForm.processing"
                        class="animate-[spin_2s_linear_infinite] inline-block align-middle ltr:mr-2 rtl:ml-2 shrink-0" />
                    {{ __('Delete') }}
                </Button>
            </div>
        </form>
    </Modal>

    <div>
        <div class="panel pb-0 mt-6">
            <div class="mb-8 border-b mx-[-1.3rem] sm:mx-[-1.3rem] border-gray dark:border-gray-dark">
                <div class="px-5 mb-4 flex sm:items-center sm:gap-3 gap-4 w-full sm:w-auto justify-between">
                    <h4 class="font-semibold mb-2 md:mb-0 text-xl dark:text-white-light">{{ __('Department List') }}
                    </h4>
                    <div class="flex items-center gap-3">
                        <Button type="button" class="btn btn-primary btn-sm" @click="isImportModalShow = true">
                            <HugeiconsIcon :icon="FileAddIcon" :size="20" color="currentColor" :stroke-width="1.5"
                                class="me-1" />
                            {{ __('Import Department') }}
                        </Button>
                        <TextLink :href="route('hrm.departments.create')">
                            <Button type="button" class="btn btn-primary btn-sm">
                                <HugeiconsIcon :icon="PlusSignIcon" :size="20" color="currentColor" :stroke-width="1.5"
                                    class="me-1" />
                                {{ __('Add Department') }}
                            </Button>
                        </TextLink>
                    </div>
                </div>
            </div>
            <div class="sm:flex items-center justify-between mb-5">
                <div class="flex sm:flex-row flex-col sm:items-center sm:gap-3 gap-4 w-full sm:w-auto mb-3 sm:mb-0">
                    <div class="relative">
                        <Input v-model="params.search" type="text" placeholder="Search..."
                            class="form-input py-2 ltr:pr-11 rtl:pl-11 peer" />
                        <div
                            class="absolute ltr:right-[11px] rtl:left-[11px] top-1/2 -translate-y-1/2 peer-focus:text-primary">
                            <HugeiconsIcon :icon="Search01Icon" :size="16" color="currentColor" :stroke-width="1.5" />
                        </div>
                    </div>
                </div>
                <div class="flex items-center flex-wrap justify-center">
                    <Button class="btn btn-info btn-sm me-2" @click="exportTable('csv', cols, __('Departments'))"
                        v-tippy='__("CSV")'>
                        <HugeiconsIcon :icon="Csv01Icon" :size="18" color="currentColor" :stroke-width="1.5" />
                    </Button>
                    <Button class="btn btn-success btn-sm me-2" @click="exportTable('excel', cols, __('Departments'))"
                        v-tippy='__("EXCEL")'>
                        <HugeiconsIcon :icon="DocumentAttachmentIcon" :size="18" color="currentColor"
                            :stroke-width="1.5" />
                    </Button>
                    <Button class="btn btn-warning btn-sm me-2" @click="exportTable('pdf', cols, __('Departments'))"
                        v-tippy='__("PDF")'>
                        <HugeiconsIcon :icon="Pdf02Icon" :size="18" color="currentColor" :stroke-width="1.5" />
                    </Button>
                    <Button class="btn btn-primary btn-sm me-2" @click="exportTable('print', cols, __('Departments'))"
                        v-tippy='__("PRINT")'>
                        <HugeiconsIcon :icon="PrinterIcon" :size="18" color="currentColor" :stroke-width="1.5" />
                    </Button>
                    <Button class="btn btn-danger btn-sm me-2" :disabled="selectedIds.length === 0"
                        @click="isDeleteMultipleModalShow = true" v-tippy='__("Delete Selected")'>
                        <HugeiconsIcon :icon="Delete01Icon" :size="18" color="currentColor" :stroke-width="1.5" />
                    </Button>
                </div>
            </div>
            <div class="datatable">
                <Vue3Datatable ref="datatableRef" skin="whitespace-nowrap bh-table-hover" :rows="rows" :columns="cols"
                    :loading="loading" :totalRows="totalRows" :isServerMode="true" :pageSize="params.pagesize"
                    :search="params.search" :pageSizeOptions="[10, 20, 30, 50, 100]" :sortable="true"
                    :sortColumn="params.sort_column" :sortDirection="params.sort_direction" @change="changeServer"
                    hasCheckbox @row-select="onRowSelect">
                    <template #action="data">
                        <div class="flex items-center gap-5">
                            <TextLink :href="route('hrm.departments.edit', data.value.id)">
                                <Button class="cursor-pointer hover:text-info" v-tippy='__("Edit")'>
                                    <HugeiconsIcon :icon="PencilEdit02Icon" :size="18" color="currentColor"
                                        :stroke-width="1.5" />
                                </Button>
                            </TextLink>
                            <Button class="cursor-pointer hover:text-danger pb-2"
                                @click="deletedModalShow(data.value.id)" v-tippy='__("Delete")'>
                                <HugeiconsIcon :icon="Delete01Icon" :size="18" color="currentColor"
                                    :stroke-width="1.5" />
                            </Button>
                        </div>
                    </template>
                </Vue3Datatable>
            </div>
        </div>
    </div>
</template>
