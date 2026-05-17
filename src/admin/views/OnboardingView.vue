<script setup lang="ts">
import { useOgdConnection } from "../composables/useOgdConnection";

const connection = useOgdConnection();

async function connect() {
  await connection.startOAuth();
}
</script>

<template>
  <section
    class="ogd:grid ogd:min-h-[calc(100vh-190px)] ogd:place-items-center"
  >
    <div
      class="ogd:w-full ogd:max-w-140 ogd:rounded-[28px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-8 ogd:shadow-[0_24px_80px_rgba(17,24,39,0.08)]"
    >
      <span
        class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:border-rose-100 ogd:bg-rose-50 ogd:px-3 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide ogd:text-rose-500"
      >
        Setup required
      </span>

      <h1
        class="ogd:mt-5 ogd:mb-2 ogd:font-display ogd:text-[34px] ogd:font-bold ogd:tracking-[-0.04em] ogd:text-gray-900"
      >
        Connect ogdynamic
      </h1>

      <p
        class="ogd:mt-0 ogd:mb-7 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
      >
        Connect with ogdynamic to access your saved template designs, map them
        to WordPress content, and use them to generate dynamic OG images for
        this site.
      </p>

      <button
        class="ogd:inline-flex ogd:w-full ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-2xl ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-5 ogd:py-4 ogd:text-sm ogd:font-semibold ogd:text-white ogd:transition ogd:hover:bg-rose-500 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
        type="button"
        :disabled="connection.loading.value"
        @click="connect"
      >
        <svg
          class="ogd:size-4"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="2"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M10 13a5 5 0 0 0 7.07 0l2.83-2.83a5 5 0 0 0-7.07-7.07L11 4.93" />
          <path d="M14 11a5 5 0 0 0-7.07 0L4.1 13.83a5 5 0 0 0 7.07 7.07L13 19.07" />
        </svg>
        {{ connection.loading.value ? "Starting connection..." : "Connect ogdynamic" }}
      </button>

      <div
        v-if="connection.error.value"
        class="ogd:mt-5 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700"
      >
        {{ connection.error.value }}
      </div>
    </div>
  </section>
</template>
