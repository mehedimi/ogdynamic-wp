<script setup lang="ts">
import { computed, shallowRef } from "vue";
import { isConnected } from "../state/connection";
import { useOgdConnection } from "../composables/useOgdConnection";

const connection = useOgdConnection();
const success = shallowRef("");
const connectionStatus = computed(() =>
  isConnected.value ? "connected" : "disconnected",
);

async function connect() {
  success.value = "";
  await connection.startOAuth();
}

async function disconnect() {
  success.value = "";
  await connection.disconnect();
  success.value = "Disconnected from ogdynamic.";
}
</script>

<template>
  <section>
    <h1
      class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900"
    >
      Connection
    </h1>
    <p
      class="ogd:mt-2 ogd:mb-7 ogd:max-w-155 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
    >
      Connect this WordPress site to ogdynamic to fetch your designs and
      generate dynamic OG image URLs.
    </p>

    <article
      class="ogd:rounded-[22px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
    >
      <div class="ogd:flex ogd:flex-wrap ogd:items-start ogd:justify-between ogd:gap-5">
        <div>
          <span
            class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:px-2.5 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide"
            :class="
              isConnected
                ? 'ogd:border-green-200 ogd:bg-green-50 ogd:text-green-600'
                : 'ogd:border-amber-200 ogd:bg-amber-50 ogd:text-amber-600'
            "
          >
            {{ connectionStatus }}
          </span>

          <h2
            class="ogd:mt-5 ogd:mb-2 ogd:font-display ogd:text-2xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900"
          >
            {{ isConnected ? "ogdynamic is connected" : "Connect with OAuth" }}
          </h2>
          <p class="ogd:m-0 ogd:max-w-130 ogd:text-sm ogd:leading-relaxed ogd:text-gray-500">
            {{
              isConnected
                ? "This site can access your ogdynamic designs with the approved account."
                : "You will be redirected to ogdynamic to approve access for this WordPress site."
            }}
          </p>
        </div>

        <button
          v-if="isConnected"
          class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-rose-200 ogd:bg-white ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-rose-500 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
          type="button"
          :disabled="connection.loading.value"
          @click="disconnect"
        >
          Disconnect
        </button>
      </div>

      <button
        v-if="!isConnected"
        class="ogd:mt-7 ogd:inline-flex ogd:w-full ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-2xl ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-5 ogd:py-4 ogd:text-sm ogd:font-semibold ogd:text-white ogd:transition ogd:hover:bg-rose-500 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
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
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700"
      >
        {{ connection.error.value }}
      </div>
      <div
        v-if="success"
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800"
      >
        {{ success }}
      </div>
    </article>
  </section>
</template>
