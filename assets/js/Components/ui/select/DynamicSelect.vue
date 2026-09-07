<script setup lang="ts">
import { ref, watch } from 'vue'
import vSelect from 'vue-select'
import { h } from 'vue'
import { useVModel } from '@vueuse/core'
import iconCaretDown from '@/components/icon/icon-caret-down.vue'
import iconX from '@/components/icon/icon-x.vue'

interface Option {
  [key: string]: any
}

const props = defineProps<{
  required?: boolean
  label: string
  placeholder?: string
  defaultValue?: string | number
  modelValue?: string | number | null
  class?: string
  url: string
  optionLabel?: string
  optionValue?: string
  method?: 'GET' | 'POST' 
  extraParams?: Record<string, any>
  headers?: Record<string, string>
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', payload: string | number | null): void
  (e: 'loading', payload: boolean): void
  (e: 'error', payload: any): void
}>()

const selectedOption = ref<Option | null>(null)
const options = ref<Option[]>([])
const loading = ref(false)
let page = 1
let searchTerm = ''

// Default method is GET
const method = props.method || 'GET'

async function fetchData(reset = false, customParams?: Record<string, any>) {
  if (!props.url) return

  loading.value = true
  emit('loading', true)

  try {
    let url = props.url
    const fetchOptions: RequestInit = {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        ...props.headers,
      },
    }

    if (method === 'POST') {
      // POST request - send data in body
      const body = {
        search: searchTerm,
        page: page,
        ...props.extraParams,
        ...customParams,
      }
      fetchOptions.body = JSON.stringify(body)
    } else {
      // GET request - append to URL
      const params = new URLSearchParams()
      if (searchTerm) params.append('search', searchTerm)
      if (page) params.append('page', String(page))
      if (props.extraParams) {
        Object.entries(props.extraParams).forEach(([key, value]) => {
          if (value !== undefined && value !== null) {
            params.append(key, String(value))
          }
        })
      }
      if (customParams) {
        Object.entries(customParams).forEach(([key, value]) => {
          if (value !== undefined && value !== null) {
            params.append(key, String(value))
          }
        })
      }

      const queryString = params.toString()
      if (queryString) {
        url += (url.includes('?') ? '&' : '?') + queryString
      }
    }

    const res = await fetch(url, fetchOptions)

    if (!res.ok) {
      throw new Error(`HTTP error! status: ${res.status}`)
    }

    const data = await res.json()
    const rows = data.data ?? data

    if (reset) {
      options.value = rows
    } else {
      options.value = [...options.value, ...rows]
    }

    // Handle selected value
    await handleSelectedValue()

  } catch (error) {
    console.error('Fetch error:', error)
    emit('error', error)
  } finally {
    loading.value = false
    emit('loading', false)
  }
}

async function handleSelectedValue() {
  if (props.modelValue) {
    const found = options.value.find(
      (o) => o[props.optionValue || 'id'] === props.modelValue
    )
    if (!found && method === 'GET') {
      // Only fetch selected item for GET requests
      try {
        const separator = props.url.includes('?') ? '&' : '?'
        const resSelected = await fetch(`${props.url}${separator}id=${props.modelValue}`)
        const selected = await resSelected.json()
        if (selected) {
          options.value.push(selected)
          selectedOption.value = selected
        }
      } catch (error) {
        console.error('Error fetching selected item:', error)
      }
    } else if (found) {
      selectedOption.value = found
    } else {
      selectedOption.value = null
    }
  } else {
    selectedOption.value = null
  }
}

// Watch for URL changes
watch(
  () => props.url,
  () => {
    page = 1
    fetchData(true)
  },
  { immediate: true }
)

// Watch for method changes
watch(
  () => props.method,
  () => {
    page = 1
    fetchData(true)
  }
)

// Watch selected option and emit update
watch(selectedOption, (val) => {
  emit('update:modelValue', val ? val[props.optionValue || 'id'] : null)
})

// Search handler
async function handleSearch(search: string) {
  searchTerm = search
  page = 1
  await fetchData(true)
}

// Scroll to end handler (infinite scroll)
function handleScrollToEnd() {
  page++
  fetchData()
}

// Expose methods for parent component
defineExpose({
  refresh: (params?: Record<string, any>) => fetchData(true, params),
  search: handleSearch,
  reset: () => {
    searchTerm = ''
    page = 1
    fetchData(true)
  },
  getOptions: () => options.value,
  getSelected: () => selectedOption.value,
})

// Customize vSelect components
vSelect.props.components.default = () => ({
  Deselect: {
    render() {
      return h('span', {}, [h(iconX, { width: '16', height: '16' })])
    },
  },
  OpenIndicator: {
    render: () => h('span', h(iconCaretDown)),
  },
})
</script>

<template>
  <v-select
    v-model="selectedOption"
    :options="options"
    :label="props.label || 'name'"
    :placeholder="props.placeholder"
    :class="props.class"
    :filterable="false"
    :get-option-label="(option: any) => option[props.label || 'name']"
    :get-option-key="(option: any) => option[props.optionValue || 'id']"
    @search="handleSearch"
    @scroll-bottom="handleScrollToEnd"
    :loading="loading"
  >
    <template #search="{ attributes, events }">
      <input
        class="vs__search customSelectStyle"
        :required="props.required && !selectedOption"
        v-bind="attributes"
        v-on="events"
      />
    </template>
  </v-select>
</template>
