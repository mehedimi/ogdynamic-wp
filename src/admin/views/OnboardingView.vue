<script setup lang="ts">
import Logo from "../components/Logo.vue";
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
     <div class="ogd:flex ogd:flex-col ogd:items-center">
        <Logo />
        <h1
          class="ogd:mt-5 ogd:font-display ogd:text-[34px] ogd:font-bold ogd:tracking-[-0.04em] ogd:text-gray-900"
        >
          Connect ogdynamic
        </h1>
      </div>

      <p
        class="ogd:mt-0 ogd:mb-7 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500 ogd:text-center"
      >
        Transform every product page, blog post, and article into a stunning social preview.
        Use reusable templates to showcase prices, images, and branding automatically.
      </p>

      <div
        class="ogd:mt-8 ogd:w-full ogd:rounded-2xl ogd:border ogd:border-gray-100 ogd:bg-gray-50 ogd:p-5"
      >
        <h3
          class="ogd:mb-3 ogd:font-display ogd:text-sm ogd:font-semibold ogd:text-gray-900"
        >
          With ogdynamic, you can:
        </h3>
        <ul
          class="ogd:space-y-3"
        >
          <li
            class="ogd:flex ogd:items-start ogd:text-[13px] ogd:leading-relaxed ogd:text-gray-600"
          >
            <svg
              class="ogd:mr-3 ogd:mt-0.5 ogd:size-4 ogd:shrink-0 ogd:text-green-500"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M20 6 9 17l-5-5" />
            </svg>
            <span>Create branded social previews for <strong>products</strong> with price, image, and specs</span>
          </li>
          <li
            class="ogd:flex ogd:items-start ogd:text-[13px] ogd:leading-relaxed ogd:text-gray-600"
          >
            <svg
              class="ogd:mr-3 ogd:mt-0.5 ogd:size-4 ogd:shrink-0 ogd:text-green-500"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M20 6 9 17l-5-5" />
            </svg>
            <span>Generate dynamic OG images for <strong>blog posts</strong> and articles with metadata</span>
          </li>
          <li
            class="ogd:flex ogd:items-start ogd:text-[13px] ogd:leading-relaxed ogd:text-gray-600"
          >
            <svg
              class="ogd:mr-3 ogd:mt-0.5 ogd:size-4 ogd:shrink-0 ogd:text-green-500"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M20 6 9 17l-5-5" />
            </svg>
            <span>Use <strong>reusable templates</strong> to maintain brand consistency</span>
          </li>
          <li
            class="ogd:flex ogd:items-start ogd:text-[13px] ogd:leading-relaxed ogd:text-gray-600"
          >
            <svg
              class="ogd:mr-3 ogd:mt-0.5 ogd:size-4 ogd:shrink-0 ogd:text-green-500"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M20 6 9 17l-5-5" />
            </svg>
            <span>Auto-optimize previews for <strong>X, LinkedIn, Facebook</strong>, and messaging apps</span>
          </li>
        </ul>
      </div>

      <button
        class="ogd:mt-6 ogd:inline-flex ogd:w-full ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-2xl ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-5 ogd:py-4 ogd:text-sm ogd:font-semibold ogd:text-white ogd:transition ogd:hover:bg-rose-500 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
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

      <p
        class="ogd:mt-4 ogd:text-center ogd:text-[11px] ogd:leading-relaxed ogd:text-gray-400"
      >
        By connecting, you agree to our
        <a
          class="ogd:font-medium! ogd:text-rose-500! ogd:hover:underline!"
          href="https://ogdynamic.com/terms"
          target="_blank"
          rel="noopener noreferrer"
        >
          Terms of Service
        </a>
        and
        <a
          class="ogd:font-medium! ogd:text-rose-500! ogd:hover:underline!"
          href="https://ogdynamic.com/privacy"
          target="_blank"
          rel="noopener noreferrer"
        >
          Privacy Policy
        </a>
      </p>

      <div
        v-if="connection.error.value"
        class="ogd:mt-5 ogd:flex ogd:justify-center ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700"
      >
        {{ connection.error.value }}
      </div>
    </div>
  </section>
</template>
