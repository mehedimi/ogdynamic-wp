<script setup lang="ts">
import { computed, reactive, shallowRef } from 'vue'
import type { FieldMapping, OGDSettings } from '../types'
import { useOgdApi } from '../composables/useOgdApi'
import FormField from '../components/forms/FormField.vue'
import SelectInput from '../components/forms/SelectInput.vue'
import TextInput from '../components/forms/TextInput.vue'

const props = defineProps<{ settings: OGDSettings }>()
const emit = defineEmits<{ settingsUpdated: [settings: OGDSettings] }>()

const api = useOgdApi()
const success = shallowRef('')
const selectedTemplate = shallowRef(props.settings.defaults.global_template)
const variableName = shallowRef('title')
const mapping = reactive<FieldMapping>({ source: 'post_title', fallback: '' })

const currentMappings = computed(() => props.settings.mappings[selectedTemplate.value] ?? {})
const sources = [
  { value: 'post_title', label: 'Post title' },
  { value: 'excerpt', label: 'Excerpt' },
  { value: 'trimmed_content', label: 'Trimmed content' },
  { value: 'featured_image', label: 'Featured image' },
  { value: 'author_name', label: 'Author name' },
  { value: 'published_date', label: 'Published date' },
  { value: 'modified_date', label: 'Modified date' },
  { value: 'category', label: 'Category' },
  { value: 'tags', label: 'Tags' },
  { value: 'permalink', label: 'Permalink' },
  { value: 'site_name', label: 'Site name' },
  { value: 'site_description', label: 'Site description' },
  { value: 'custom_meta', label: 'Custom/meta field' },
  { value: 'static', label: 'Static value' },
]

const templateOptions = computed(() => [
  { label: 'Select template', value: '' },
  ...props.settings.templates.map((template) => ({
    label: template.name || template.title || template.id || 'Untitled template',
    value: template.id ?? '',
  })),
])

async function saveMapping() {
  const next = structuredClone(props.settings)
  if (!next.mappings[selectedTemplate.value]) {
    next.mappings[selectedTemplate.value] = {}
  }
  next.mappings[selectedTemplate.value][variableName.value] = structuredClone(mapping)
  const payload = await api.request<{ settings: OGDSettings }>('settings', { method: 'POST', body: next })
  emit('settingsUpdated', payload.settings)
  success.value = `Mapped ${variableName.value}.`
}
</script>

<template>
  <section>
    <h1 class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900">Field Mapping</h1>
    <p class="ogd:mt-2 ogd:mb-7 ogd:max-w-[620px] ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500">Map WordPress fields into ogdynamic template variables. These mappings become URL parameters for generated images.</p>

    <div class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
      <FormField label="Template">
        <SelectInput v-model="selectedTemplate" :options="templateOptions" />
      </FormField>

      <FormField label="Variable name">
        <TextInput v-model="variableName" size="sm" placeholder="title" />
      </FormField>

      <FormField label="WordPress field">
        <SelectInput v-model="mapping.source" :options="sources" />
      </FormField>

      <FormField v-if="mapping.source === 'custom_meta'" label="Meta key">
        <TextInput v-model="mapping.meta_key" size="sm" placeholder="_custom_field_key" />
      </FormField>

      <FormField v-if="mapping.source === 'static'" label="Static value">
        <TextInput v-model="mapping.static" size="sm" />
      </FormField>

      <FormField label="Fallback value">
        <TextInput v-model="mapping.fallback" size="sm" placeholder="Used when the source field is empty" />
      </FormField>

      <div class="ogd:flex ogd:flex-wrap ogd:gap-2.5">
        <button class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50" type="button" :disabled="!selectedTemplate || !variableName" @click="saveMapping">Save mapping</button>
      </div>

      <div v-if="Object.keys(currentMappings).length" class="ogd:mt-5 ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6">
        <h2 class="ogd:mt-0 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold">Current mappings</h2>
        <pre>{{ currentMappings }}</pre>
      </div>
      <div v-if="api.error" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700">{{ api.error }}</div>
      <div v-if="success" class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800">{{ success }}</div>
    </div>
  </section>
</template>
