<script setup lang="ts">
import { computed } from 'vue'
import type { OGDSettings } from '../types'

const props = defineProps<{
  settings: OGDSettings
  seoPlugin: string
  woocommerceActive: boolean
}>()

const isConnected = computed(() => props.settings.connection.status === 'connected')
const hasDefaultTemplate = computed(() => props.settings.defaults.global_template !== '')
const templateCount = computed(() => props.settings.templates.length)
</script>

<template>
  <section>
    <h1 class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900">Dashboard</h1>
    <p class="ogd:mt-2 ogd:mb-7 ogd:max-w-[620px] ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500">Review connection health, template setup, SEO compatibility, and the next actions needed before frontend image injection is reliable.</p>

    <div class="ogd:grid ogd:grid-cols-2 ogd:gap-[18px] max-[900px]:ogd:grid-cols-1">
      <article class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
        <span class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:px-2.5 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide" :class="isConnected ? 'ogd:border-green-200 ogd:bg-green-50 ogd:text-green-600' : 'ogd:border-amber-200 ogd:bg-amber-50 ogd:text-amber-600'">
          {{ isConnected ? 'Connected' : 'Not connected' }}
        </span>
        <h2 class="ogd:mt-4 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Account connection</h2>
        <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">
          {{ isConnected ? `Connected ${settings.connection.account_label || 'to ogdynamic'}.` : 'Add an API key before fetching templates.' }}
        </p>
      </article>

      <article class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
        <span class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:px-2.5 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide" :class="hasDefaultTemplate ? 'ogd:border-green-200 ogd:bg-green-50 ogd:text-green-600' : 'ogd:border-amber-200 ogd:bg-amber-50 ogd:text-amber-600'">
          {{ hasDefaultTemplate ? 'Configured' : 'Needs setup' }}
        </span>
        <h2 class="ogd:mt-4 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Default template</h2>
        <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">{{ hasDefaultTemplate ? settings.defaults.global_template : 'Choose a global fallback template.' }}</p>
      </article>

      <article class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
        <span class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:border-rose-100 ogd:bg-rose-50 ogd:px-2.5 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide ogd:text-rose-500">{{ templateCount }}</span>
        <h2 class="ogd:mt-4 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Templates cached</h2>
        <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">Last refreshed: {{ settings.template_updated || 'Never' }}</p>
      </article>

      <article class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
        <span class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:border-gray-200 ogd:px-2.5 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide ogd:text-gray-500">Compatibility</span>
        <h2 class="ogd:mt-4 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">SEO and commerce</h2>
        <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">SEO plugin: {{ seoPlugin }}. WooCommerce: {{ woocommerceActive ? 'Active' : 'Inactive' }}.</p>
      </article>
    </div>
  </section>
</template>
