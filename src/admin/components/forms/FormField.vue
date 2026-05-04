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
    <label v-if="forId" class="ogd:text-xs ogd:font-semibold ogd:text-gray-700" :for="forId">{{ label }}</label>
    <span v-else class="ogd:text-xs ogd:font-semibold ogd:text-gray-700">{{ label }}</span>

    <slot />

    <p v-if="hint" class="ogd:m-0 ogd:text-xs ogd:leading-relaxed ogd:text-gray-500">{{ hint }}</p>
    <p v-if="error" class="ogd:m-0 ogd:text-xs ogd:leading-relaxed ogd:text-rose-600">{{ error }}</p>
  </div>
</template>
