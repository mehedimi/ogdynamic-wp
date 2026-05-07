<script setup lang="ts">
import { computed, shallowRef } from "vue";
import { RouterLink } from "vue-router";
import { useOgdCloudApi } from "../composables/useOgdCloudApi";
import type { ApiData, User } from "../types";

const cloudApi = useOgdCloudApi();

const account = shallowRef<User>();

function loadAccount() {
  cloudApi
    .request<ApiData<User>>("/v1/me")
    .then(({ data }) => {
      account.value = data;
    })
    .catch(() => {
      //
    });
}

void loadAccount();

const isConnected = computed(() => {
  return Boolean(account.value) && !cloudApi.error.value;
});
</script>

<template>
  <section>
    <h1
      class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900"
    >
      Dashboard
    </h1>
    <p
      class="ogd:mt-2 ogd:mb-7 ogd:max-w-155 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
    >
      Review the live OG image state for this site, including the token owner
      and currently active template coverage.
    </p>

    <div class="ogd:grid ogd:grid-cols-1 ogd:gap-4.5">
      <article
        v-if="!cloudApi.loading.value"
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
      >
        <span
          class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:px-2.5 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide"
          :class="
            isConnected
              ? 'ogd:border-green-200 ogd:bg-green-50 ogd:text-green-600'
              : 'ogd:border-amber-200 ogd:bg-amber-50 ogd:text-amber-600'
          "
        >
          {{ isConnected ? "Connected" : "Not connected" }}
        </span>
        <h2
          class="ogd:mt-4 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold"
        >
          API connection
        </h2>
        <p class="ogd:mt-0 ogd:mb-4.5 ogd:text-gray-500">
          {{
            isConnected
              ? "The current API Token is valid and the site can generate OG images."
              : "Add an API key before configuring OG image templates."
          }}
        </p>
        <div
          class="ogd:grid ogd:grid-cols-2 ogd:gap-x-4 ogd:gap-y-3 ogd:text-sm max-[720px]:ogd:grid-cols-1"
          v-if="isConnected"
        >
          <div>
            <div class="ogd:text-gray-400">Token owner</div>
            <h3 class="ogd:text-sm! ogd:mb-0!">{{ account?.name }}</h3>
            <h3
              class="ogd:text-sm! ogd:text-gray-500! ogd:font-normal! ogd:my-0!"
            >
              {{ account?.email }}
            </h3>
          </div>
        </div>

        <div
          class="ogd:mt-5 ogd:grid ogd:grid-cols-2 ogd:gap-3 max-[720px]:ogd:grid-cols-1"
          v-if="isConnected"
        >
          <RouterLink
            class="ogd:inline-flex ogd:items-center ogd:justify-center ogd:rounded-full ogd:border! ogd:border-gray-900! ogd:bg-gray-900! ogd:px-4.5 ogd:py-2.5 ogd:text-[13px]! ogd:font-semibold ogd:text-white! ogd:no-underline ogd:transition ogd:hover:border-rose-500! ogd:hover:bg-rose-500! ogd:hover:text-white!"
            to="/templates"
          >
            Manage OG Templates
          </RouterLink>
          <RouterLink
            class="ogd:inline-flex ogd:items-center ogd:justify-center ogd:rounded-full ogd:border! ogd:border-gray-200! ogd:bg-white! ogd:px-4.5 ogd:py-2.5 ogd:text-[13px]! ogd:font-semibold ogd:text-gray-700! ogd:no-underline ogd:transition ogd:hover:border-rose-200! ogd:hover:bg-rose-50! ogd:hover:text-gray-900!"
            to="/connection"
          >
            Update connection
          </RouterLink>
        </div>

        <div
          v-if="cloudApi.error.value"
          class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700"
        >
          {{ cloudApi.error.value }}
        </div>
      </article>
      <article
        v-else
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
      >
        <div class="ogd:animate-pulse">
          <div class="ogd:h-6 ogd:w-24 ogd:rounded-full ogd:bg-gray-100"></div>
          <div
            class="ogd:mt-4 ogd:h-5 ogd:w-36 ogd:rounded-md ogd:bg-gray-100"
          ></div>
          <div class="ogd:mt-3 ogd:grid ogd:gap-2">
            <div
              class="ogd:h-4 ogd:w-full ogd:max-w-120 ogd:rounded-md ogd:bg-gray-100"
            ></div>
            <div
              class="ogd:h-4 ogd:w-full ogd:max-w-88 ogd:rounded-md ogd:bg-gray-100"
            ></div>
          </div>
          <div
            class="ogd:mt-5 ogd:grid ogd:grid-cols-2 ogd:gap-3 max-[720px]:ogd:grid-cols-1"
          >
            <div class="ogd:h-10 ogd:rounded-full ogd:bg-gray-100"></div>
            <div class="ogd:h-10 ogd:rounded-full ogd:bg-gray-100"></div>
          </div>
        </div>
      </article>
    </div>
  </section>
</template>
