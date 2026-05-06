<script setup lang="ts">
import { computed, reactive, shallowRef, watch } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import type { OGDSettings, PostTypeOption } from './types'
import Layout from './components/Layout.vue'

const adminConfig = window.ogdynamicAdmin
const settings = reactive<OGDSettings>(createEmptySettings(adminConfig.apiKey))
const postTypes = shallowRef<PostTypeOption[]>(adminConfig.postTypes)
const seoPlugin = shallowRef(adminConfig.seoPlugin)
const ecoPlugins = shallowRef<string[]>(adminConfig.ecoPlugins)
const route = useRoute()
const router = useRouter()
const isConnected = computed(() => '' !== settings.api_key)
const woocommerceActive = computed(() => ecoPlugins.value.includes('woocommerce'))

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

function createEmptySettings(apiKey = ''): OGDSettings {
  return {
    api_key: apiKey,
    connection: {
      status: apiKey !== '' ? 'connected' : 'disconnected',
      account_label: '',
      plan: '',
      usage: null,
      last_checked_at: '',
      last_error: '',
    },
    templates: [],
    template_updated: '',
    defaults: {
      global_template: '',
      post_templates: {},
      product_template: '',
      homepage_template: '',
      archive_template: '',
      fallback_image_url: '',
      fallback_mode: 'featured_image',
      meta_mode: 'image_only',
      seo_mode: 'auto',
      enabled_post_types: ['post', 'page'],
      editor_overrides: true,
      cleanup_on_uninstall: false,
    },
    mappings: {},
    woocommerce: {
      enabled: true,
      variable_product_behavior: 'parent',
    },
  }
}
</script>

<template>
  <Layout v-if="route.path !== '/onboarding'">
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
  </Layout>
  <RouterView v-else v-slot="{ Component }">
    <component
      :is="Component"
      :settings="settings"
      :post-types="postTypes"
      :seo-plugin="seoPlugin"
      :woocommerce-active="woocommerceActive"
      @settings-updated="replaceSettings"
    />
  </RouterView>
</template>
