<script setup lang="ts">
import { onMounted, shallowRef } from 'vue'
import type { OGDSettings } from '../types'
import { useOgdApi } from '../composables/useOgdApi'

type DebugPayload = {
  connection: OGDSettings['connection']
  seoPlugin: string
  woocommerce: boolean
  versions: Record<string, string>
  siteUrl: string
  templates: number
}

const api = useOgdApi()
const debug = shallowRef<DebugPayload | null>(null)
const copied = shallowRef(false)

async function loadDebug() {
  debug.value = await api.request<DebugPayload>('debug')
}

async function copyDebug() {
  if (!debug.value) return
  await navigator.clipboard.writeText(JSON.stringify(debug.value, null, 2))
  copied.value = true
}

onMounted(loadDebug)
</script>

<template>
  <section>
    <h1 class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900">Debug</h1>
    <p class="ogd:mt-2 ogd:mb-7 ogd:max-w-[620px] ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500">Copy diagnostic information for support and inspect the detected WordPress, SEO, WooCommerce, connection, and template state.</p>

    <div class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
      <div v-if="api.error" class="ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700">{{ api.error }}</div>
      <div v-else-if="!debug" class="ogd:rounded-2xl ogd:border ogd:border-dashed ogd:border-rose-200 ogd:bg-rose-50 ogd:p-[18px] ogd:text-rose-800">Loading debug information…</div>
      <template v-else>
        <div class="ogd:grid ogd:grid-cols-2 ogd:gap-[18px] max-[900px]:ogd:grid-cols-1">
          <div>
            <h2 class="ogd:mt-0 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Connection</h2>
            <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">{{ debug.connection.status }} {{ debug.connection.account_label }}</p>
          </div>
          <div>
            <h2 class="ogd:mt-0 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Environment</h2>
            <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">WP {{ debug.versions.wordpress }} · PHP {{ debug.versions.php }} · Plugin {{ debug.versions.plugin }}</p>
          </div>
          <div>
            <h2 class="ogd:mt-0 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Compatibility</h2>
            <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">SEO: {{ debug.seoPlugin }} · WooCommerce: {{ debug.woocommerce ? 'Active' : 'Inactive' }}</p>
          </div>
          <div>
            <h2 class="ogd:mt-0 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Templates</h2>
            <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">{{ debug.templates }} cached templates</p>
          </div>
        </div>

        <pre class="ogd:mt-5 ogd:overflow-auto ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">{{ debug }}</pre>
        <div class="ogd:mt-5 ogd:flex ogd:flex-wrap ogd:gap-2.5">
          <button class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white" type="button" @click="copyDebug">Copy debug information</button>
          <button class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-gray-700" type="button" @click="loadDebug">Refresh</button>
        </div>
        <div v-if="copied" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800">Debug information copied.</div>
      </template>
    </div>
  </section>
</template>
