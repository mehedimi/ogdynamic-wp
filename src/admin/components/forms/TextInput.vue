<script setup lang="ts">
const model = defineModel<string | number | undefined>({ required: true })

withDefaults(
  defineProps<{
    id?: string
    type?: string
    placeholder?: string
    autocomplete?: string
    size?: 'sm' | 'md'
    disabled?: boolean
  }>(),
  {
    type: 'text',
    placeholder: '',
    autocomplete: undefined,
    size: 'md',
    disabled: false,
  },
)
</script>

<template>
  <input
    :id="id"
    v-model="model"
    :type="type"
    :placeholder="placeholder"
    :autocomplete="autocomplete"
    :disabled="disabled"
    class="ogd:w-full ogd:border ogd:border-gray-200 ogd:bg-gray-50 ogd:text-gray-900 ogd:outline-none ogd:transition ogd:focus:border-rose-400 ogd:focus:bg-white ogd:focus:ring-4 ogd:focus:ring-rose-100 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
    :class="size === 'sm' ? 'ogd:rounded-[10px] ogd:px-3 ogd:py-2.5 ogd:text-[13px]' : 'ogd:rounded-[12px] ogd:px-4 ogd:py-3 ogd:text-sm'"
  />
</template>
