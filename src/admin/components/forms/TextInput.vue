<script setup lang="ts">
const model = defineModel<string | number | undefined>({ required: true });

withDefaults(
  defineProps<{
    id?: string;
    type?: string;
    placeholder?: string;
    autocomplete?: string;
    size?: "sm" | "md";
    disabled?: boolean;
  }>(),
  {
    type: "text",
    placeholder: "",
    autocomplete: undefined,
    size: "md",
    disabled: false,
  },
);
</script>

<template>
  <input
    :id="id"
    v-model="model"
    :type="type"
    :placeholder="placeholder"
    :autocomplete="autocomplete"
    :disabled="disabled"
    class="ogd:w-full ogd:border! ogd:border-gray-200! ogd:bg-gray-50 ogd:text-[13.5px]! ogd:text-gray-900 ogd:rounded-full! ogd:px-6! ogd:py-1.5! ogd:outline-none! ogd:transition ogd:focus:border-rose-400! ogd:focus:bg-white! ogd:focus:shadow-[0_0_0_3px_rgba(244,63,94,0.08)] ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
    :class="size === 'sm' ? 'ogd:text-[13px] ogd:py-2' : 'ogd:text-[13.5px]'"
  />
</template>
