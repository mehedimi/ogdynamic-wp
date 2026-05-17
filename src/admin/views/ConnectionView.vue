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

    <div
      v-if="isConnected"
      class="ogd:mt-8"
    >
      <h2
        class="ogd:font-display ogd:text-xl ogd:font-bold ogd:text-gray-900"
      >
        What you can do with ogdynamic
      </h2>
      <p
        class="ogd:mt-2 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
      >
        Once connected, you can create and manage dynamic OG image templates for your store and content.
      </p>

      <div
        class="ogd:mt-6 ogd:grid ogd:grid-cols-1 ogd:gap-4 ogd:sm:grid-cols-2"
      >
        <div
          class="ogd:rounded-[14px] ogd:border ogd:border-gray-200 ogd:bg-white ogd:p-5"
        >
          <div
            class="ogd:mb-3 ogd:flex ogd:h-10 ogd:w-10 ogd:items-center ogd:justify-center ogd:rounded-lg ogd:bg-rose-50 ogd:text-rose-600"
          >
            <svg
              class="ogd:size-5"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
              <circle cx="8.5" cy="8.5" r="1.5" />
              <line x1="21" y1="15" x2="8" y2="8" />
            </svg>
          </div>
          <h3
            class="ogd:font-display ogd:text-base ogd:font-semibold ogd:text-gray-900"
          >
            Product Templates
          </h3>
          <p
            class="ogd:mt-2 ogd:text-[13px] ogd:leading-relaxed ogd:text-gray-500"
          >
            Create branded social previews for products with price, image, discount, and key specs.
          </p>
        </div>

        <div
          class="ogd:rounded-[14px] ogd:border ogd:border-gray-200 ogd:bg-white ogd:p-5"
        >
          <div
            class="ogd:mb-3 ogd:flex ogd:h-10 ogd:w-10 ogd:items-center ogd:justify-center ogd:rounded-lg ogd:bg-rose-50 ogd:text-rose-600"
          >
            <svg
              class="ogd:size-5"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
              <line x1="4" y1="22" x2="4" y2="15" />
            </svg>
          </div>
          <h3
            class="ogd:font-display ogd:text-base ogd:font-semibold ogd:text-gray-900"
          >
            Blog & Articles
          </h3>
          <p
            class="ogd:mt-2 ogd:text-[13px] ogd:leading-relaxed ogd:text-gray-500"
          >
            Generate dynamic OG images for blog posts with title, author, and metadata.
          </p>
        </div>

        <div
          class="ogd:rounded-[14px] ogd:border ogd:border-gray-200 ogd:bg-white ogd:p-5"
        >
          <div
            class="ogd:mb-3 ogd:flex ogd:h-10 ogd:w-10 ogd:items-center ogd:justify-center ogd:rounded-lg ogd:bg-rose-50 ogd:text-rose-600"
          >
            <svg
              class="ogd:size-5"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="12" cy="12" r="10" />
              <path d="M8 14s1.5 2 4 2 4-2 4-2" />
              <line x1="9" y1="9" x2="9.01" y2="9" />
              <line x1="15" y1="9" x2="15.01" y2="9" />
            </svg>
          </div>
          <h3
            class="ogd:font-display ogd:text-base ogd:font-semibold ogd:text-gray-900"
          >
            Reusable Templates
          </h3>
          <p
            class="ogd:mt-2 ogd:text-[13px] ogd:leading-relaxed ogd:text-gray-500"
          >
            Design once and reuse across your entire catalog for consistent branding.
          </p>
        </div>

        <div
          class="ogd:rounded-[14px] ogd:border ogd:border-gray-200 ogd:bg-white ogd:p-5"
        >
          <div
            class="ogd:mb-3 ogd:flex ogd:h-10 ogd:w-10 ogd:items-center ogd:justify-center ogd:rounded-lg ogd:bg-rose-50 ogd:text-rose-600"
          >
            <svg
              class="ogd:size-5"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <circle cx="12" cy="12" r="10" />
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
              <path d="M2 12h20" />
            </svg>
          </div>
          <h3
            class="ogd:font-display ogd:text-base ogd:font-semibold ogd:text-gray-900"
          >
            Cross-Platform
          </h3>
          <p
            class="ogd:mt-2 ogd:text-[13px] ogd:leading-relaxed ogd:text-gray-500"
          >
            Automatically optimize previews for X, LinkedIn, Facebook, Slack, and iMessage.
          </p>
        </div>
      </div>
    </div>
  </section>
</template>
