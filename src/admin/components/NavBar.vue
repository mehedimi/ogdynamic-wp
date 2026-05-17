<script setup lang="ts">
import { RouterLink } from "vue-router";
import { adminNavigation } from "../navigation";
import Logo from "./Logo.vue";
</script>

<template>
  <header
    class="ogd:mx-auto ogd:mt-4 ogd:sticky ogd:top-9 ogd:flex ogd:h-18 ogd:w-[min(1280px,calc(100%-32px))] ogd:items-center ogd:justify-between ogd:gap-4 ogd:overflow-x-auto ogd:rounded-full ogd:border ogd:border-rose-100 ogd:bg-white/90 ogd:px-6 ogd:shadow-[0_2px_24px_rgba(244,63,94,0.08),0_1px_4px_rgba(0,0,0,0.04)] ogd:backdrop-blur-[18px] ogd:z-50"
  >
    <a
      class="ogd:shrink-0 ogd:no-underline ogd:transition-opacity ogd:duration-150 ogd:hover:opacity-80"
      href="https://ogdynamic.com"
      target="_blank"
      rel="noreferrer"
    >
      <Logo/>
    </a>

    <nav
      class="ogd:flex ogd:flex-1 ogd:items-center ogd:justify-center ogd:gap-1"
      aria-label="ogdynamic admin sections"
    >
      <RouterLink
        v-for="item in adminNavigation"
        :key="item.name"
        :to="item.path"
        class="ogd:flex ogd:shrink-0 ogd:items-center ogd:gap-2 ogd:rounded-full ogd:px-4 ogd:py-1.5 ogd:text-sm ogd:font-medium ogd:text-gray-500! ogd:no-underline ogd:transition-all ogd:duration-150! ogd:hover:bg-rose-50! ogd:hover:text-gray-900!"
        active-class="ogd:bg-rose-50! ogd:text-gray-900!"
      >
        <svg
          v-if="item.name === 'dashboard'"
          class="ogd:size-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <rect width="7" height="9" x="3" y="3" rx="1" />
          <rect width="7" height="5" x="14" y="3" rx="1" />
          <rect width="7" height="9" x="14" y="12" rx="1" />
          <rect width="7" height="5" x="3" y="16" rx="1" />
        </svg>
        <svg
          v-else-if="item.name === 'templates'"
          class="ogd:size-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <rect width="18" height="14" x="3" y="5" rx="2" />
          <path d="m3 15 4.5-4.5a2 2 0 0 1 2.8 0L15 15" />
          <path d="m14 14 1.5-1.5a2 2 0 0 1 2.8 0L21 15" />
          <circle cx="16" cy="9" r="1" />
        </svg>
        {{ item.label }}
      </RouterLink>
    </nav>

    <a
      class="ogd:flex ogd:shrink-0 ogd:items-center ogd:justify-center ogd:rounded-full ogd:bg-gray-900! ogd:px-4 ogd:py-2 ogd:text-sm! ogd:font-semibold ogd:text-white! ogd:no-underline ogd:transition-all ogd:duration-150! ogd:hover:bg-rose-500!"
      href="https://ogdynamic.com/designs"
      target="_blank"
      rel="noreferrer"
    >
      <svg
        class="ogd:size-4 ogd:mr-2"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
      >
        <path d="M15 3h6v6" />
        <path d="M10 14 21 3" />
        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
      </svg>
      My Designs
    </a>
  </header>
</template>
