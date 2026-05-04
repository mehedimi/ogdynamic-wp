<script setup lang="ts">
import { reactive, shallowRef } from 'vue'
import type { OGDSettings } from '../types'
import { useOgdApi } from '../composables/useOgdApi'
import FormField from '../components/forms/FormField.vue'
import SelectInput from '../components/forms/SelectInput.vue'
import TextInput from '../components/forms/TextInput.vue'

const props = defineProps<{ settings: OGDSettings }>()
const emit = defineEmits<{ settingsUpdated: [settings: OGDSettings] }>()

const api = useOgdApi()
const success = shallowRef('')
const draft = reactive(structuredClone(props.settings.defaults))

async function save() {
  const next = structuredClone(props.settings)
  next.defaults = structuredClone(draft)
  const payload = await api.request<{ settings: OGDSettings }>('settings', { method: 'POST', body: next })
  emit('settingsUpdated', payload.settings)
  success.value = 'Advanced settings saved.'
}

async function clearCache() {
  await api.request<{ cleared: boolean }>('cache/clear', { method: 'POST' })
  success.value = 'Cache cleared.'
}
</script>

<template>
  <section>
    <h1 class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900">Advanced</h1>
    <p class="ogd:mt-2 ogd:mb-7 ogd:max-w-[620px] ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500">Control meta injection, SEO compatibility, fallback images, editor override behavior, cache, and uninstall cleanup.</p>

    <div class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
      <FormField label="SEO compatibility mode">
        <SelectInput
          v-model="draft.seo_mode"
          :options="[
            { label: 'Auto compatibility', value: 'auto' },
            { label: 'Image-only injection', value: 'image_only' },
            { label: 'Full meta injection', value: 'full' },
            { label: 'Disabled injection', value: 'disabled' },
          ]"
        />
      </FormField>

      <FormField label="Meta mode">
        <SelectInput
          v-model="draft.meta_mode"
          :options="[
            { label: 'Image tags only', value: 'image_only' },
            { label: 'Full Open Graph and Twitter tags', value: 'full' },
          ]"
        />
      </FormField>

      <FormField label="Fallback image URL">
        <TextInput v-model="draft.fallback_image_url" size="sm" type="url" placeholder="https://example.com/default-og.jpg" />
      </FormField>

      <FormField label="Fallback mode">
        <SelectInput
          v-model="draft.fallback_mode"
          :options="[
            { label: 'Featured image, then fallback URL', value: 'featured_image' },
            { label: 'Fallback URL only', value: 'fallback_image' },
            { label: 'No fallback', value: 'none' },
          ]"
        />
      </FormField>

      <FormField label="Editor overrides">
        <SelectInput
          v-model="draft.editor_overrides"
          :options="[
            { label: 'Allow editors to override content images', value: true },
            { label: 'Admins only', value: false },
          ]"
        />
      </FormField>

      <FormField label="Uninstall cleanup">
        <SelectInput
          v-model="draft.cleanup_on_uninstall"
          :options="[
            { label: 'Keep plugin settings by default', value: false },
            { label: 'Remove plugin settings, meta, and cache on uninstall', value: true },
          ]"
        />
      </FormField>

      <div class="ogd:flex ogd:flex-wrap ogd:gap-2.5">
        <button class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white" type="button" @click="save">Save advanced settings</button>
        <button class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-gray-700" type="button" @click="clearCache">Clear cache</button>
      </div>
      <div v-if="api.error" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700">{{ api.error }}</div>
      <div v-if="success" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800">{{ success }}</div>
    </div>
  </section>
</template>
