<script setup lang="ts">
import { computed, reactive, shallowRef, watch } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import type { OGDSettings, PostTypeOption } from './types'
import { adminNavigation } from './navigation'

const settings = reactive<OGDSettings>(window.ogdynamicAdmin.settings)
const postTypes = shallowRef<PostTypeOption[]>(window.ogdynamicAdmin.postTypes)
const seoPlugin = shallowRef(window.ogdynamicAdmin.seoPlugin)
const woocommerceActive = shallowRef(window.ogdynamicAdmin.woocommerce)
const route = useRoute()
const router = useRouter()
const isConnected = computed(() => settings.connection.status === 'connected')

function replaceSettings(next: OGDSettings) {
  Object.assign(settings, next)
}

watch(
  () => [isConnected.value, route.path] as const,
  ([connected, path]) => {
    if (!connected && path !== '/onboarding') {
      void router.replace('/onboarding')
      return
    }

    if (connected && path === '/onboarding') {
      void router.replace('/')
    }
  },
  { immediate: true },
)
</script>

<template>
  <div class="ogd:-ml-5 ogd:min-h-[calc(100vh-32px)] ogd:bg-[#f7f7f8] ogd:text-gray-900 ogd:font-body">
    <header class="ogd:sticky ogd:top-8 ogd:z-20 ogd:flex ogd:items-center ogd:justify-between ogd:gap-6 ogd:border-b ogd:border-gray-100 ogd:bg-white ogd:px-14 ogd:py-[18px] max-[900px]:ogd:static max-[900px]:ogd:flex-col max-[900px]:ogd:items-start max-[900px]:ogd:gap-3.5 max-[900px]:ogd:px-5">
      <div class="ogd:min-w-[180px]">
        <a class="ogd:block ogd:font-display ogd:text-lg ogd:font-bold ogd:text-gray-900 ogd:no-underline" href="https://ogdynamic.com" target="_blank" rel="noreferrer">og<span class="ogd:text-rose-500">dynamic</span></a>
        <p class="ogd:mt-1 ogd:mb-0 ogd:text-[11px] ogd:text-gray-400">Social image automation</p>
      </div>

      <nav v-if="isConnected" class="ogd:flex ogd:items-center ogd:gap-1.5 ogd:overflow-x-auto ogd:pb-0.5" aria-label="ogdynamic admin sections">
        <RouterLink
          v-for="item in adminNavigation"
          :key="item.name"
          :to="item.path"
          class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:whitespace-nowrap ogd:rounded-full ogd:border-0 ogd:bg-transparent ogd:px-3.5 ogd:py-2 ogd:text-[13px] ogd:text-gray-500 ogd:hover:bg-gray-50 ogd:hover:text-gray-900"
          active-class="ogd:bg-rose-50 ogd:font-semibold ogd:text-rose-500"
        >
          {{ item.label }}
        </RouterLink>
      </nav>
    </header>

    <main class="ogd:max-w-[1120px] ogd:px-14 ogd:pt-10 ogd:pb-24 max-[900px]:ogd:px-5 max-[900px]:ogd:pt-7 max-[900px]:ogd:pb-16">
      <RouterView v-slot="{ Component }">
        <component
          :is="Component"
          :settings="settings"
          :post-types="postTypes"
          :seo-plugin="seoPlugin"
          :woocommerce-active="woocommerceActive"
          @settings-updated="replaceSettings"
        />
      </RouterView>
    </main>
  </div>
</template>
