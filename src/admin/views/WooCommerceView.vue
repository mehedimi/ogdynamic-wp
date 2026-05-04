<script setup lang="ts">
import { reactive, shallowRef } from 'vue'
import type { OGDSettings } from '../types'
import { useOgdApi } from '../composables/useOgdApi'
import FormField from '../components/forms/FormField.vue'
import SelectInput from '../components/forms/SelectInput.vue'

const props = defineProps<{
  settings: OGDSettings
  woocommerceActive: boolean
}>()
const emit = defineEmits<{ settingsUpdated: [settings: OGDSettings] }>()

const api = useOgdApi()
const success = shallowRef('')
const draft = reactive(structuredClone(props.settings.woocommerce))

async function save() {
  const next = structuredClone(props.settings)
  next.woocommerce = structuredClone(draft)
  const payload = await api.request<{ settings: OGDSettings }>('settings', { method: 'POST', body: next })
  emit('settingsUpdated', payload.settings)
  success.value = 'WooCommerce settings saved.'
}
</script>

<template>
  <section>
    <h1 class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900">WooCommerce</h1>
    <p class="ogd:mt-2 ogd:mb-7 ogd:max-w-[620px] ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500">Configure product social images with price, sale, stock, image, SKU, rating, and product taxonomy data.</p>

    <div v-if="!woocommerceActive" class="ogd:rounded-2xl ogd:border ogd:border-dashed ogd:border-rose-200 ogd:bg-rose-50 ogd:p-[18px] ogd:text-rose-800">WooCommerce is not active. Product-specific image generation will stay inactive until WooCommerce is installed and activated.</div>

    <div class="ogd:mt-5 ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
      <FormField label="Enable WooCommerce integration">
        <SelectInput
          v-model="draft.enabled"
          :disabled="!woocommerceActive"
          :options="[
            { label: 'Enabled', value: true },
            { label: 'Disabled', value: false },
          ]"
        />
      </FormField>

      <FormField label="Variable product behavior">
        <SelectInput
          v-model="draft.variable_product_behavior"
          :disabled="!woocommerceActive"
          :options="[
            { label: 'Use parent product data', value: 'parent' },
            { label: 'Use default variation when available', value: 'default_variation' },
            { label: 'Use price range', value: 'price_range' },
          ]"
        />
      </FormField>

      <div class="ogd:mb-5 ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
        <h2 class="ogd:mt-0 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Supported product fields</h2>
        <p class="ogd:mt-0 ogd:mb-[18px] ogd:text-gray-500">Product title, short description, image, gallery image, price, regular price, sale price, currency, SKU, category, tags, stock status, rating, review count, and product URL.</p>
      </div>

      <div class="ogd:flex ogd:flex-wrap ogd:gap-2.5">
        <button class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50" type="button" :disabled="!woocommerceActive" @click="save">Save WooCommerce settings</button>
      </div>
      <div v-if="api.error" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700">{{ api.error }}</div>
      <div v-if="success" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800">{{ success }}</div>
    </div>
  </section>
</template>
