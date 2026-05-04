<script setup lang="ts">
import { reactive, shallowRef } from 'vue'
import type { OGDSettings, PostTypeOption } from '../types'
import { useOgdApi } from '../composables/useOgdApi'
import FormField from '../components/forms/FormField.vue'
import SelectInput from '../components/forms/SelectInput.vue'

const props = defineProps<{
  settings: OGDSettings
  postTypes: PostTypeOption[]
  woocommerceActive: boolean
}>()
const emit = defineEmits<{ settingsUpdated: [settings: OGDSettings] }>()

const api = useOgdApi()
const success = shallowRef('')
const draft = reactive(structuredClone(props.settings.defaults))

function templateLabel(template: { id?: string; name?: string; title?: string }) {
  return template.name || template.title || template.id || 'Untitled template'
}

function templateOptions(defaultLabel: string) {
  return [
    { label: defaultLabel, value: '' },
    ...settingsTemplates(),
  ]
}

function settingsTemplates() {
  return props.settings.templates.map((template) => ({
    label: templateLabel(template),
    value: template.id ?? '',
  }))
}

async function save() {
  const next = structuredClone(props.settings)
  next.defaults = structuredClone(draft)
  const payload = await api.request<{ settings: OGDSettings }>('settings', { method: 'POST', body: next })
  emit('settingsUpdated', payload.settings)
  success.value = 'Template settings saved.'
}

async function refreshTemplates() {
  const payload = await api.request<{ settings: OGDSettings }>('templates/refresh', { method: 'POST' })
  emit('settingsUpdated', payload.settings)
  success.value = 'Templates refreshed.'
}
</script>

<template>
  <section>
    <h1 class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900">Templates</h1>
    <p class="ogd:mt-2 ogd:mb-7 ogd:max-w-[620px] ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500">Choose the global template and content-specific fallbacks WordPress should use when generating social image URLs.</p>

    <div class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
      <div v-if="settings.templates.length === 0" class="ogd:mb-5 ogd:rounded-2xl ogd:border ogd:border-dashed ogd:border-rose-200 ogd:bg-rose-50 ogd:p-[18px] ogd:text-rose-800">
        No templates are cached yet. Connect an account, then refresh templates from ogdynamic.
      </div>

      <FormField label="Global default template">
        <SelectInput v-model="draft.global_template" :options="templateOptions('Select template')" />
      </FormField>

      <FormField v-for="postType in postTypes" :key="postType.name" :label="`${postType.label} template`">
        <SelectInput v-model="draft.post_templates[postType.name]" :options="templateOptions('Use global default')" />
      </FormField>

      <FormField v-if="woocommerceActive" label="WooCommerce product template">
        <SelectInput v-model="draft.product_template" :options="templateOptions('Use global default')" />
      </FormField>

      <div class="ogd:flex ogd:flex-wrap ogd:gap-2.5">
        <button class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white" type="button" @click="save">Save templates</button>
        <button class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-gray-700" type="button" @click="refreshTemplates">Refresh from ogdynamic</button>
      </div>
      <div v-if="api.error" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700">{{ api.error }}</div>
      <div v-if="success" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800">{{ success }}</div>
    </div>
  </section>
</template>
