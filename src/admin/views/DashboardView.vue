<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { RouterLink } from "vue-router";
import { useOgdCloudApi } from "../composables/useOgdCloudApi";
import type { ApiData, User } from "../types";

const cloudApi = useOgdCloudApi();
const account = ref<User | undefined>();

const isLoading = ref(true);

async function loadAccount() {
  try {
    const { data } = await cloudApi.request<ApiData<User>>("/v1/me");
    account.value = data;
  } catch {
    account.value = undefined;
  } finally {
    isLoading.value = false;
  }
}

const isConnected = computed(() => Boolean(account.value) && !cloudApi.error.value);

onMounted(loadAccount);
</script>

<template>
  <section>
    <h1
      class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900"
    >
      Dashboard
    </h1>
    <p
      class="ogd:mt-2 ogd:mb-6 ogd:max-w-140 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
    >
      Manage the API connection and OG image templates for this site.
    </p>

    <div class="ogd:grid ogd:grid-cols-1 ogd:gap-4.5">
       <article
         v-if="!isLoading"
         class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
       >
        <div
          class="ogd:flex ogd:items-start ogd:justify-between ogd:gap-4 max-[720px]:ogd:flex-col"
        >
          <div>
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
              class="ogd:mt-4 ogd:mb-1.5 ogd:font-display ogd:text-lg ogd:font-bold ogd:text-gray-900"
            >
              API connection
            </h2>
            <p class="ogd:m-0 ogd:max-w-150 ogd:text-sm ogd:leading-relaxed ogd:text-gray-500">
              {{
                isConnected
                  ? "Your API token is valid. OG image templates can be configured now."
                  : "Add an API key before configuring OG image templates."
              }}
            </p>
          </div>

          <div
            v-if="isConnected"
            class="ogd:flex ogd:shrink-0 ogd:flex-wrap ogd:justify-end ogd:gap-2.5 max-[720px]:ogd:w-full max-[720px]:ogd:justify-start"
          >
            <RouterLink
              class="ogd:inline-flex ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border! ogd:border-gray-900! ogd:bg-gray-900! ogd:px-4.5 ogd:py-2.5 ogd:text-[13px]! ogd:font-semibold ogd:text-white! ogd:no-underline ogd:transition ogd:hover:border-rose-500! ogd:hover:bg-rose-500! ogd:hover:text-white!"
              to="/templates"
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
                <rect width="18" height="14" x="3" y="5" rx="2" />
                <path d="m3 15 4.5-4.5a2 2 0 0 1 2.8 0L15 15" />
                <path d="m14 14 1.5-1.5a2 2 0 0 1 2.8 0L21 15" />
                <circle cx="16" cy="9" r="1" />
              </svg>
              Manage OG Templates
            </RouterLink>
            <RouterLink
              class="ogd:inline-flex ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border! ogd:border-gray-200! ogd:bg-white! ogd:px-4.5 ogd:py-2.5 ogd:text-[13px]! ogd:font-semibold ogd:text-gray-700! ogd:no-underline ogd:transition ogd:hover:border-rose-200! ogd:hover:bg-rose-50! ogd:hover:text-gray-900!"
              to="/connection"
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
                <path d="M12 20h9" />
                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
              </svg>
              Update connection
            </RouterLink>
          </div>
        </div>

        <div
          v-if="isConnected"
          class="ogd:mt-5 ogd:rounded-2xl ogd:bg-gray-50 ogd:px-4 ogd:py-3"
        >
          <div class="ogd:text-xs ogd:font-medium ogd:text-gray-400">
            Token owner
          </div>
          <div class="ogd:mt-1 ogd:text-sm ogd:font-semibold ogd:text-gray-900">
            {{ account?.name }}
          </div>
          <div class="ogd:mt-0.5 ogd:text-sm ogd:text-gray-500">
            {{ account?.email }}
          </div>
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
         <div class="ogd:animate-pulse" aria-hidden="true">
           <div
             class="ogd:flex ogd:items-start ogd:justify-between ogd:gap-4 max-[720px]:ogd:flex-col"
           >
             <div class="ogd:w-full">
               <div
                 class="ogd:h-6 ogd:w-24 ogd:rounded-full ogd:bg-gray-100"
               ></div>
               <div
                 class="ogd:mt-4 ogd:h-6 ogd:w-36 ogd:rounded-md ogd:bg-gray-100"
               ></div>
               <div class="ogd:mt-3 ogd:grid ogd:gap-2">
                 <div
                   class="ogd:h-4 ogd:w-full ogd:max-w-150 ogd:rounded-md ogd:bg-gray-100"
                 ></div>
                 <div
                   class="ogd:h-4 ogd:w-full ogd:max-w-96 ogd:rounded-md ogd:bg-gray-100"
                 ></div>
               </div>
             </div>

             <div
               class="ogd:flex ogd:shrink-0 ogd:flex-wrap ogd:justify-end ogd:gap-2.5 max-[720px]:ogd:w-full max-[720px]:ogd:justify-start"
             >
               <div
                 class="ogd:h-10 ogd:w-40 ogd:rounded-full ogd:bg-gray-100"
               ></div>
               <div
                 class="ogd:h-10 ogd:w-36 ogd:rounded-full ogd:bg-gray-100"
               ></div>
             </div>
           </div>
           <div
             class="ogd:mt-5 ogd:rounded-2xl ogd:bg-gray-50 ogd:px-4 ogd:py-3"
           >
             <div
               class="ogd:h-3 ogd:w-20 ogd:rounded-md ogd:bg-gray-100"
             ></div>
             <div
               class="ogd:mt-2 ogd:h-4 ogd:w-38 ogd:rounded-md ogd:bg-gray-100"
             ></div>
             <div
               class="ogd:mt-2 ogd:h-4 ogd:w-56 ogd:rounded-md ogd:bg-gray-100"
             ></div>
           </div>
         </div>
       </article>
     </div>
   </section>
 </template>
