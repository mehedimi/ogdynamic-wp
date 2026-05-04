<script setup lang="ts">
type SelectOption = {
  label: string
  value: string | boolean | number
}

const model = defineModel<string | boolean | number>({ required: true })

withDefaults(
  defineProps<{
    id?: string
    options: SelectOption[]
    disabled?: boolean
  }>(),
  {
    id: undefined,
    disabled: false,
  },
)
</script>

<template>
  <select
    :id="id"
    v-model="model"
    :disabled="disabled"
    class="ogd:w-full ogd:rounded-[10px] ogd:border ogd:border-gray-200 ogd:bg-gray-50 ogd:px-3 ogd:py-2.5 ogd:text-[13px] ogd:text-gray-900 ogd:outline-none ogd:transition ogd:focus:border-rose-400 ogd:focus:bg-white ogd:focus:ring-4 ogd:focus:ring-rose-100 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
  >
    <option v-for="option in options" :key="String(option.value)" :value="option.value">
      {{ option.label }}
    </option>
  </select>
</template>
