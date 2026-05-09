<script setup lang="ts">
defineProps<{
  label: string
  hint?: string
  error?: string
  forId?: string
}>()
</script>

<template>
  <div class="ogd:mb-4 ogd:grid ogd:gap-1.5">
    <label v-if="forId" class="ogd:text-[12.5px] ogd:!text-[12.5px] ogd:font-medium ogd:!font-medium ogd:text-gray-700 ogd:!text-gray-700" :for="forId">{{ label }}</label>
    <span v-else class="ogd:text-[12.5px] ogd:!text-[12.5px] ogd:font-medium ogd:!font-medium ogd:text-gray-700 ogd:!text-gray-700">{{ label }}</span>

    <slot />

    <p v-if="hint" class="ogd:m-0 ogd:text-[11.5px] ogd:!text-[11.5px] ogd:font-light ogd:leading-relaxed ogd:text-gray-400 ogd:!text-gray-400">{{ hint }}</p>
    <p v-if="error" class="ogd:m-0 ogd:text-[11.5px] ogd:!text-[11.5px] ogd:leading-relaxed ogd:text-rose-500 ogd:!text-rose-500 ogd:flex ogd:items-center ogd:gap-1">{{ error }}</p>
  </div>
</template>
