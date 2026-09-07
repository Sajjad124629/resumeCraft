<script setup lang="ts">
import { computed, ref } from 'vue';
import { useVModel } from '@vueuse/core';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
import { useAppStore } from '@/stores/index';

// Props
interface Props {
  modelValue?: string | Date | null;
  label?: string;
  placeholder?: string;
  disabled?: boolean;
  error?: string;
  inputClass?: string;
  dateFormat?: string;
  minDate?: string | Date;
  maxDate?: string | Date;
  enableTime?: boolean;
  time24hr?: boolean;
  altInput?: boolean;
  altFormat?: string;
  disable?: Array<{ from: string; to: string }> | string[];
  enable?: Array<string | Date> | ((date: Date) => boolean);
  mode?: 'single' | 'multiple' | 'range';
  inline?: boolean;
  static?: boolean;
  noCalendar?: boolean;
  weekNumbers?: boolean;
  locale?: any;
  defaultValue?: string | Date | null;
  required?: boolean;
  requiredMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: null,
  label: '',
  placeholder: 'Select date',
  disabled: false,
  error: '',
  inputClass: '',
  dateFormat: 'Y-m-d',
  minDate: undefined,
  maxDate: undefined,
  enableTime: false,
  time24hr: false,
  altInput: false,
  altFormat: 'F j, Y',
  noCalendar: false,
  disable: () => [],
  enable: () => [],
  mode: 'single',
  inline: false,
  static: false,
  weekNumbers: false,
  locale: undefined,
  defaultValue: null,
  required: false,
  requiredMessage: 'This field is required',
});

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string | Date | null];
  'on-change': [selectedDates: Date[], dateStr: string, instance: any];
  'on-open': [selectedDates: Date[], dateStr: string, instance: any];
  'on-close': [selectedDates: Date[], dateStr: string, instance: any];
  'on-blur': [event: FocusEvent];
}>();

// Track if field has been touched
const touched = ref(false);

// Use useVModel for two-way binding
const modelValue = useVModel(props, 'modelValue', emit, {
  passive: true,
  defaultValue: props.defaultValue,
});

const store = useAppStore();

// Merge config with store settings
const mergedConfig = computed(() => {
  const config: any = {
    dateFormat: props.dateFormat,
    position: store.rtlClass === 'rtl' ? 'auto right' : 'auto left',
    mode: props.mode,
    inline: props.inline,
    static: props.static,
    weekNumbers: props.weekNumbers,
    allowInput: true,
  };

  // Optional configs
  if (props.minDate) config.minDate = props.minDate;
  if (props.maxDate) config.maxDate = props.maxDate;
  if (props.enableTime) config.enableTime = true;
  if (props.time24hr) config.time24hr = true;
  if (props.noCalendar) config.noCalendar = true;
  if (props.altInput) config.altInput = true;
  if (props.altFormat) config.altFormat = props.altFormat;
  if (props.disable && props.disable.length) config.disable = props.disable;
  if (props.enable && props.enable.length) config.enable = props.enable;
  if (props.locale) config.locale = props.locale;

  return config;
});

// Event handlers
const handleChange = (selectedDates: Date[], dateStr: string, instance: any) => {
  modelValue.value = dateStr;
  touched.value = true;
  emit('on-change', selectedDates, dateStr, instance);
};

const handleOpen = (selectedDates: Date[], dateStr: string, instance: any) => {
  emit('on-open', selectedDates, dateStr, instance);
};

const handleClose = (selectedDates: Date[], dateStr: string, instance: any) => {
  emit('on-close', selectedDates, dateStr, instance);
};

const handleBlur = (event: FocusEvent) => {
  touched.value = true;
  emit('on-blur', event);
};

// Validation helper - can be used for form validation
const validate = (): boolean => {
  touched.value = true;
  if (props.required && !modelValue.value) {
    return false;
  }
  return true;
};

// Expose validate method for parent components
defineExpose({
  validate,
  resetValidation: () => { touched.value = false; },
});
</script>
<template>
    <flat-pickr
      v-model="modelValue"
      class="form-input"
      :class="[
        inputClass,
        { 'error': error || (required && !modelValue && touched) }
      ]"
      :config="mergedConfig"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      @on-change="handleChange"
      @on-open="handleOpen"
      @on-close="handleClose"
      @blur="handleBlur"
    />
   
</template>


