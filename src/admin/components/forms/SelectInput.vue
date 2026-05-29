<script setup lang="ts">
type SelectOption = {
  label: string;
  value: string | boolean | number;
};

const model = defineModel<string | boolean | number>({ required: true });

withDefaults(
  defineProps<{
    id?: string;
    options: SelectOption[];
    disabled?: boolean;
  }>(),
  {
    id: undefined,
    disabled: false,
  },
);
</script>

<template>
  <select
    :id="id"
    v-model="model"
    :disabled="disabled"
    class="ogd:w-full ogd:appearance-none ogd:rounded-full! ogd:border ogd:border-gray-200! ogd:bg-gray-50 ogd:px-6! ogd:py-1.5! ogd:text-[13.5px]! ogd:text-gray-900! ogd:outline-none! ogd:transition ogd:focus:border-rose-400! ogd:focus:bg-white! ogd:focus:shadow-[0_0_0_3px_rgba(244,63,94,0.08)] ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50 ogd:z-auto"
  >
    <option
      v-for="option in options"
      :key="String(option.value)"
      :value="option.value"
    >
      {{ option.label }}
    </option>
  </select>
</template>
