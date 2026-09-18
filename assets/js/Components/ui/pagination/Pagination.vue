<script setup lang="ts">
import { defineProps } from 'vue'
import TextLink from '@/components/TextLink.vue'
import IconCaretDown from '@/components/icon/icon-caret-down.vue'
import IconCaretsDown from '@/components/icon/icon-carets-down.vue'

interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

const props = defineProps<{
  links: PaginationLink[]
  prev_page_url?: string | null
  next_page_url?: string | null
}>()

const currentQuery = new URLSearchParams(window.location.search)
const mergeUrl = (url: string | null) => {
  if (!url) return null
  const [base, query] = url.split('?')
  const urlParams = new URLSearchParams(query || '')
  currentQuery.forEach((value, key) => {
    if (key !== 'page') {
      urlParams.set(key, value)
    }
  })
  return `${base}?${urlParams.toString()}`
}

</script>

<template>
  <ul class="inline-flex items-end space-x-1 rtl:space-x-reverse m-auto mb-4">
    <li>
      <TextLink v-if="links[0]?.url" :href="mergeUrl(links[0].url)"
        class="flex justify-center font-semibold p-2 rounded-full transition bg-white-light text-dark
               hover:text-white hover:bg-primary dark:text-white-light dark:bg-[#191e3a] dark:hover:bg-primary">
        <IconCaretsDown class="rotate-90 rtl:-rotate-90" />
      </TextLink>
      <span v-else class="flex justify-center font-semibold p-2 rounded-full bg-white-light text-gray-400">
        <IconCaretsDown class="rotate-90 rtl:-rotate-90" />
      </span>
    </li>
    <li>
      <TextLink v-if="prev_page_url" :href="mergeUrl(prev_page_url)"
        class="flex justify-center font-semibold p-2 rounded-full transition bg-white-light text-dark
               hover:text-white hover:bg-primary dark:text-white-light dark:bg-[#191e3a] dark:hover:bg-primary">
        <IconCaretDown class="w-5 h-5 rotate-90 rtl:-rotate-90" />
      </TextLink>
      <span v-else class="flex justify-center font-semibold p-2 rounded-full bg-white-light text-gray-400">
        <IconCaretDown class="w-5 h-5 rotate-90 rtl:-rotate-90" />
      </span>
    </li>
    <template v-for="(link, index) in links" :key="index">
      <li v-if="!isNaN(Number(link.label))">
        <TextLink :href="mergeUrl(link.url)"
          class="flex justify-center font-semibold px-3.5 py-2 rounded-full transition"
          :class="{
            'bg-primary text-white dark:text-white-light dark:bg-primary': link.active,
            'bg-white-light text-dark hover:text-white hover:bg-primary dark:text-white-light dark:bg-[#191e3a] dark:hover:bg-primary':
              !link.active
          }">
          {{ link.label }}
        </TextLink>
      </li>
      <li v-else-if="link.label === '...'">
        <span class="px-3 py-2 text-gray-400">{{ link.label }}</span>
      </li>
    </template>
    <li>
      <TextLink v-if="next_page_url" :href="mergeUrl(next_page_url)"
        class="flex justify-center font-semibold p-2 rounded-full transition bg-white-light text-dark
               hover:text-white hover:bg-primary dark:text-white-light dark:bg-[#191e3a] dark:hover:bg-primary">
        <IconCaretDown class="w-5 h-5 -rotate-90 rtl:rotate-90" />
      </TextLink>
      <span v-else class="flex justify-center font-semibold p-2 rounded-full bg-white-light text-gray-400">
        <IconCaretDown class="w-5 h-5 -rotate-90 rtl:rotate-90" />
      </span>
    </li>
    <li>
      <TextLink v-if="links[links.length - 1]?.url" :href="mergeUrl(links[links.length - 1].url)"
        class="flex justify-center font-semibold p-2 rounded-full transition bg-white-light text-dark
               hover:text-white hover:bg-primary dark:text-white-light dark:bg-[#191e3a] dark:hover:bg-primary">
        <IconCaretsDown class="-rotate-90 rtl:rotate-90" />
      </TextLink>
      <span v-else class="flex justify-center font-semibold p-2 rounded-full bg-white-light text-gray-400">
        <IconCaretsDown class="-rotate-90 rtl:rotate-90" />
      </span>
    </li>
  </ul>
</template>
