<script setup lang="ts">
import { computed, shallowRef, watch } from 'vue'
import { RouterView, useRoute, useRouter } from 'vue-router'
import Layout from './components/Layout.vue'
import { apiKey } from './state/connection'

const adminConfig = window.ogdynamicAdmin
const seoPlugin = shallowRef(adminConfig.seoPlugin)
const ecoPlugins = shallowRef<string[]>(adminConfig.ecoPlugins)
const route = useRoute()
const router = useRouter()
const isConnected = computed(() => '' !== apiKey.value)
const woocommerceActive = computed(() => ecoPlugins.value.includes('woocommerce'))

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
  <Layout v-if="route.path !== '/onboarding'">
      <RouterView v-slot="{ Component }">
        <component
          :is="Component"
          :seo-plugin="seoPlugin"
          :woocommerce-active="woocommerceActive"
        />
      </RouterView>
  </Layout>
  <RouterView v-else v-slot="{ Component }">
    <component
      :is="Component"
      :seo-plugin="seoPlugin"
      :woocommerce-active="woocommerceActive"
    />
  </RouterView>
</template>
