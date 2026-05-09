<script setup lang="ts">
import NavBar from './NavBar.vue'
</script>

<template>
  <div class="ogd:-ml-5 ogd:min-h-[calc(100vh-32px)] ogd:bg-[#f7f7f8] ogd:font-body ogd:text-gray-900">
    <NavBar />

    <main class="ogd:mx-auto ogd:w-[min(1280px,calc(100%-32px))] ogd:pt-10 ogd:pb-24 max-[900px]:ogd:pt-7 max-[900px]:ogd:pb-16">
      <slot />
    </main>
  </div>
</template>
