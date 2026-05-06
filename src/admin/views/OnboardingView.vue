<script setup lang="ts">
import { reactive, shallowRef } from 'vue'
import { useOgdApi } from '../composables/useOgdApi'
import { setApiKey } from '../state/connection'
import FormField from '../components/forms/FormField.vue'
import TextInput from '../components/forms/TextInput.vue'

const api = useOgdApi()
const form = reactive({ api_key: '' })
const success = shallowRef('')

type ConnectionResponse = {
  data: {
    api_key: string
  }
}

async function connect() {
  success.value = ''

  try {
    const payload = await api.request<ConnectionResponse>('connection', {
      method: 'PUT',
      body: { api_key: form.api_key },
    })

    setApiKey(payload.data.api_key)
    success.value = 'Connection verified. Loading your dashboard…'
    form.api_key = ''
  } catch {
    // Error state is handled by useOgdApi.
  }
}
</script>

<template>
  <section class="ogd:grid ogd:min-h-[calc(100vh-190px)] ogd:place-items-center">
    <div class="ogd:w-full ogd:max-w-140 ogd:rounded-[28px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-8 ogd:shadow-[0_24px_80px_rgba(17,24,39,0.08)]">
      <span class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:border-rose-100 ogd:bg-rose-50 ogd:px-3 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide ogd:text-rose-500">
        Setup required
      </span>

      <h1 class="ogd:mt-5 ogd:mb-2 ogd:font-display ogd:text-[34px] ogd:font-bold ogd:tracking-[-0.04em] ogd:text-gray-900">
        Connect ogdynamic
      </h1>

      <p class="ogd:mt-0 ogd:mb-7 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500">
        Add your ogdynamic API key to start generating social preview images from WordPress content.
      </p>

      <form class="ogd:grid ogd:gap-4" @submit.prevent="connect">
        <FormField label="API key" for-id="ogd-onboarding-api-key">
          <TextInput
            id="ogd-onboarding-api-key"
            v-model="form.api_key"
            type="password"
            autocomplete="off"
            placeholder="Paste your ogdynamic API key"
          />
          <p class="ogd:m-0 ogd:text-xs ogd:leading-relaxed ogd:text-gray-500">
            Need an API key?
            <a class="ogd:font-semibold ogd:text-rose-500 ogd:no-underline ogd:hover:text-rose-600" href="https://ogdynamic.com" target="_blank" rel="noreferrer">
              Open ogdynamic.com
            </a>
            and copy your key from your account settings.
          </p>
        </FormField>

        <button
          class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-5 ogd:py-3 ogd:text-sm ogd:font-semibold ogd:text-white ogd:transition ogd:hover:bg-rose-500 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
          type="submit"
          :disabled="api.loading.value || form.api_key === ''"
        >
          {{ api.loading.value ? 'Checking connection…' : 'Connect account' }}
        </button>
      </form>

      <div v-if="api.error.value" class="ogd:mt-5 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700">
        {{ api.error.value }}
      </div>

      <div v-if="success" class="ogd:mt-5 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800">
        {{ success }}
      </div>
    </div>
  </section>
</template>
