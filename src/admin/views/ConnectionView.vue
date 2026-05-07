<script setup lang="ts">
import { computed, reactive, shallowRef } from "vue";
import { useOgdApi } from "../composables/useOgdApi";
import { apiKey, setApiKey } from "../state/connection";
import FormField from "../components/forms/FormField.vue";
import TextInput from "../components/forms/TextInput.vue";

const api = useOgdApi();
const form = reactive({ api_key: "" });
const success = shallowRef("");
const connectionStatus = computed(() =>
  apiKey.value !== "" ? "connected" : "disconnected",
);

type ConnectionResponse = {
  data: {
    api_key: string;
  };
};

async function testConnection() {
  success.value = "";
  const payload = await api.request<ConnectionResponse>("connection", {
    method: "PUT",
    body: { api_key: form.api_key },
  });
  setApiKey(payload.data.api_key);
  success.value = "Connection verified and saved.";
  form.api_key = "";
}

async function disconnect() {
  const payload = await api.request<ConnectionResponse>("connection", {
    method: "DELETE",
  });
  setApiKey(payload.data.api_key);
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
      Connect this WordPress site to ogdynamic with an API key, validate the
      account, and refresh credentials when needed.
    </p>

    <div
      class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
    >
      <span
        class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:px-2.5 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide"
        :class="
          connectionStatus === 'connected'
            ? 'ogd:border-green-200 ogd:bg-green-50 ogd:text-green-600'
            : 'ogd:border-amber-200 ogd:bg-amber-50 ogd:text-amber-600'
        "
      >
        {{ connectionStatus }}
      </span>

      <div class="ogd:mt-6">
        <FormField label="API key" for-id="ogd-api-key">
          <TextInput
            required
            id="ogd-api-key"
            v-model="form.api_key"
            size="sm"
            type="password"
            placeholder="Paste your ogdynamic API key"
          />
        </FormField>
      </div>

      <div class="ogd:flex ogd:flex-wrap ogd:gap-2.5">
        <button
          class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
          type="button"
          :disabled="api.loading.value"
          @click="testConnection"
        >
          Test and save connection
        </button>
        <button
          class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-rose-200 ogd:bg-white ogd:px-[18px] ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-rose-500 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
          type="button"
          :disabled="api.loading.value"
          @click="disconnect"
        >
          Disconnect
        </button>
      </div>

      <div
        v-if="api.error.value"
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700"
      >
        {{ api.error }}
      </div>
      <div
        v-if="success"
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800"
      >
        {{ success }}
      </div>
    </div>
  </section>
</template>
