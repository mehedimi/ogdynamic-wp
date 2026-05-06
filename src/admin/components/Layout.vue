<script setup lang="ts">
import NavBar from './NavBar.vue'
</script>

<template>
  <div class="ogd:-ml-5 ogd:min-h-[calc(100vh-32px)] ogd:bg-[#f7f7f8] ogd:font-body ogd:text-gray-900">
    <NavBar />

    <main class="ogd:px-14 ogd:pt-10 ogd:pb-24 max-[900px]:ogd:px-5 max-[900px]:ogd:pt-7 max-[900px]:ogd:pb-16">
      <slot />
    </main>
  </div>
</template>
