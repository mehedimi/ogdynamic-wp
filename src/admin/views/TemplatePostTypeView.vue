<script setup lang="ts">
import { computed, reactive, ref, shallowRef, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import type { ApiData, OGDDesign, OGDDesignListItem } from "../types";
import { useOgdApi } from "../composables/useOgdApi";
import { useOgdCloudApi } from "../composables/useOgdCloudApi";
import FormField from "../components/forms/FormField.vue";
import SearchableSelectInput from "../components/forms/SearchableSelectInput.vue";

type FieldOption = {
  key: string;
  label: string;
  type?: string;
};

type MappingSourceOption = {
  key: string;
  label: string;
};

type TemplateMapping = {
  template_id: string;
  map: Array<{
    attr_key: string;
    key: string;
  }>;
};

const route = useRoute();
const router = useRouter();

const templateExists = ref(false);

const wordpressApi = useOgdApi();
const cloudApi = useOgdCloudApi();

const wpDeleteApi = useOgdApi();

const designs = shallowRef<OGDDesignListItem[]>([]);
const design = shallowRef<OGDDesign>();

const formPayload = reactive<TemplateMapping>({
  template_id: "",
  map: [],
});

const fieldMap = computed(() => {
  return (key: string): string => {
    return formPayload.map.find((map) => map.attr_key === key)?.key || "";
  };
});

const mappingSources = shallowRef<MappingSourceOption[]>([]);

const fields = computed<FieldOption[]>(() => {
  return (
    design.value?.template.schema.flatMap((section) => {
      return section.fields
        .filter((field) => field.allowOverride)
        .flatMap((field) => {
          return {
            key: field.key,
            label: field.label,
          };
        });
    }) || []
  );
});

const success = shallowRef("");
const postType = computed(() => String(route.params.postType || ""));

const postTypeLabel = computed(
  () => postType.value.charAt(0).toUpperCase() + postType.value.slice(1),
);
const createDesignUrl = computed(
  () =>
    `${window.ogdynamicAdmin.apiUrl.replace(/\/?api\/?$/, "")}/designs/create`,
);

const selectedDesignPreviewUrl = computed(() =>
  formPayload.template_id
    ? `https://cdn.ogdynamic.com/d/${formPayload.template_id}`
    : "",
);

const designOptions = computed(() =>
  [{ label: "Select your design", value: "" }].concat(
    designs.value.map((d) => ({ label: d.name, value: d.id })),
  ),
);

const sourceOptions = computed(() => {
  return [{ value: "", label: "Do not override" }].concat(
    mappingSources.value.map((d) => ({ value: d.key, label: d.label })),
  );
});

watch(
  () => formPayload.template_id,
  async (designId) => {
    success.value = "";
    await loadDesign(designId);
  },
);

async function load() {
  return Promise.all([loadDesigns(), loadSavedMapping()]);
}

async function loadSavedMapping() {
  const payload = await wordpressApi.request<{
    data: Partial<TemplateMapping>;
    sources: MappingSourceOption[];
  }>(`templates/${postType.value}`);

  mappingSources.value = payload.sources;

  if (!Array.isArray(payload.data)) {
    formPayload.map = payload.data.map || [];
    formPayload.template_id = payload.data.template_id || "";
    templateExists.value = true;
  }
}

async function loadDesigns() {
  const { data } = await cloudApi.request<ApiData<OGDDesign[]>>("v1/designs");

  designs.value = data;
}

async function loadDesign(designId: string) {
  if ("" === designId) {
    design.value = undefined;
    return;
  }

  const { data } = await cloudApi.request<ApiData<OGDDesign>>(
    `v1/designs/${designId}?include=template`,
  );

  design.value = data;
  autoMapEmptyFields();
}

async function save() {
  await wpDeleteApi.request<{ data: TemplateMapping }>(
    `templates/${postType.value}`,
    {
      method: "PUT",
      body: formPayload,
    },
  );
  success.value = "OG image template updated.";
  templateExists.value = true;
}

async function deactivate() {
  await wpDeleteApi.request<{ data: [] }>(`templates/${postType.value}`, {
    method: "DELETE",
  });

  await router.push("/templates");
}

function fieldInputId(key: string): string {
  return `ogd-template-map-${key}`;
}

function setFieldMapValue(attr_key: string, key: string) {
  let map = formPayload.map.find((map) => map.attr_key === attr_key);

  if (map) {
    map.key = key;
  } else {
    formPayload.map.push({
      key,
      attr_key,
    });
  }
}

function autoMapEmptyFields() {
  for (const field of fields.value) {
    const mapped = formPayload.map.find((map) => map.attr_key === field.key);

    if (mapped) {
      continue;
    }

    const source = findMatchingSource(field);

    if (source) {
      formPayload.map.push({
        attr_key: field.key,
        key: source.key,
      });
    }
  }
}

function findMatchingSource(field: FieldOption): MappingSourceOption | undefined {
  const normalizedField = normalizeMappingKey(field.key);
  const normalizedLabel = normalizeMappingKey(field.label);
  const fieldKeys = [normalizedField, normalizedLabel].filter(Boolean);

  return mappingSources.value.find((source) => {
    const sourceKeys = [
      normalizeMappingKey(source.key),
      normalizeMappingKey(source.label),
    ];

    return fieldKeys.some((fieldKey) =>
      sourceKeys.some(
        (sourceKey) =>
          sourceKey === fieldKey ||
          sourceKey.endsWith(fieldKey) ||
          fieldKey.endsWith(sourceKey),
      ),
    );
  });
}

function normalizeMappingKey(key: string): string {
  return key.replace(/url$/i, "").replace(/[^a-z0-9]/gi, "").toLowerCase();
}

load();
</script>

<template>
  <section>
    <!-- Page heading and return navigation. -->
    <RouterLink
      class="ogd:mb-5 ogd:inline-flex ogd:text-sm! ogd:font-semibold! ogd:text-rose-500! ogd:hover:text-rose-600!"
      to="/templates"
    >
      Back to post types
    </RouterLink>

    <h1
      class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900"
    >
      {{ postTypeLabel }} OG Image Template
    </h1>
    <p
      class="ogd:mt-2! ogd:mb-6 ogd:max-w-140 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
    >
      Map an ogdynamic design to WordPress sources for this post type.
    </p>

    <!-- Design selector skeleton while the WordPress mapping data loads. -->
    <div
      v-if="wordpressApi.loading.value"
      class="ogd:mb-4.5 ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
    >
      <div class="ogd:animate-pulse">
        <div
          class="ogd:grid ogd:grid-cols-[minmax(0,1fr)_auto] ogd:items-end ogd:gap-4 max-[720px]:ogd:grid-cols-1 max-[720px]:ogd:items-stretch"
        >
          <div>
            <div class="ogd:h-4 ogd:w-28 ogd:rounded-md ogd:bg-gray-100"></div>
            <div
              class="ogd:mt-2 ogd:h-11 ogd:w-full ogd:rounded-xl ogd:bg-gray-100"
            ></div>
          </div>
          <div
            class="ogd:flex ogd:flex-wrap ogd:justify-end ogd:gap-2.5 max-[720px]:ogd:justify-start"
          >
            <div
              class="ogd:h-10 ogd:w-32 ogd:rounded-full ogd:bg-gray-100"
            ></div>
            <div
              class="ogd:h-10 ogd:w-34 ogd:rounded-full ogd:bg-gray-100"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Design selector and helper actions. -->
    <div
      v-else
      class="ogd:mb-4.5 ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
    >
      <div
        class="ogd:flex ogd:items-center ogd:justify-between ogd:gap-4 max-[720px]:ogd:flex-col max-[720px]:ogd:items-stretch"
      >
        <div class="ogd:flex-1">
          <SearchableSelectInput
            id="design-id"
            v-model="formPayload.template_id"
            :options="designOptions"
            :disabled="cloudApi.loading.value"
            placeholder="Search designs"
          />
        </div>
        <div
          class="ogd:flex ogd:shrink-0 ogd:flex-wrap ogd:justify-end ogd:gap-2.5 max-[720px]:ogd:justify-start"
        >
          <button
            class="ogd:inline-flex ogd:size-10 ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:text-gray-700 ogd:transition ogd:hover:border-rose-200 ogd:hover:bg-rose-50 ogd:hover:text-gray-900 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
            type="button"
            title="Refresh designs"
            aria-label="Refresh designs"
            :disabled="cloudApi.loading.value"
            @click="loadDesigns"
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
              <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16" />
              <path d="M3 21v-5h5" />
              <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8" />
              <path d="M16 8h5V3" />
            </svg>
          </button>
          <a
            class="ogd:inline-flex ogd:size-10 ogd:items-center ogd:justify-center ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:text-gray-700 ogd:no-underline ogd:transition ogd:hover:border-rose-200 ogd:hover:bg-rose-50 ogd:hover:text-gray-900"
            :href="createDesignUrl"
            title="Create design"
            aria-label="Create design"
            target="_blank"
            rel="noreferrer"
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
              <path d="M5 12h14" />
              <path d="M12 5v14" />
            </svg>
          </a>
        </div>
      </div>
      <div
        v-if="cloudApi.error.value"
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700"
      >
        {{ cloudApi.error.value }}
      </div>
    </div>

    <div
      class="ogd:grid ogd:grid-cols-[minmax(0,1.1fr)_minmax(420px,1.6fr)] ogd:gap-6 max-[1024px]:ogd:grid-cols-1"
    >
      <!-- Mapping form skeleton while the selected design detail loads. -->
      <div
        v-if="formPayload.template_id && cloudApi.loading.value"
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
      >
        <div class="ogd:animate-pulse">
          <div class="ogd:h-5 ogd:w-36 ogd:rounded-md ogd:bg-gray-100"></div>
          <div
            class="ogd:mt-3 ogd:h-4 ogd:w-full ogd:rounded-md ogd:bg-gray-100"
          ></div>
          <div
            class="ogd:mt-2 ogd:h-4 ogd:w-4/5 ogd:rounded-md ogd:bg-gray-100"
          ></div>
          <div class="ogd:mt-5 ogd:grid ogd:gap-4">
            <div v-for="item in 4" :key="item">
              <div
                class="ogd:h-4 ogd:w-28 ogd:rounded-md ogd:bg-gray-100"
              ></div>
              <div
                class="ogd:mt-2 ogd:h-10 ogd:w-full ogd:rounded-xl ogd:bg-gray-100"
              ></div>
            </div>
          </div>
          <div class="ogd:mt-5 ogd:flex ogd:flex-wrap ogd:gap-2.5">
            <div
              class="ogd:h-10 ogd:w-34 ogd:rounded-full ogd:bg-gray-100"
            ></div>
            <div
              class="ogd:h-10 ogd:w-40 ogd:rounded-full ogd:bg-gray-100"
            ></div>
          </div>
        </div>
      </div>

      <!-- Field mapping form for the selected OG image design. -->
      <div
        v-else-if="formPayload.template_id && fields.length"
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
      >
        <h2
          class="ogd:m-0! ogd:font-display ogd:text-base ogd:font-bold ogd:text-gray-900"
        >
          OG image mapping
        </h2>
        <p
          class="ogd:mt-2! ogd:mb-5 ogd:text-sm ogd:leading-relaxed ogd:text-gray-500"
        >
          Link each overridable template field to a WordPress source used in the
          generated OG image.
        </p>

        <div class="ogd:grid ogd:gap-4">
          <FormField
            v-for="field in fields"
            :key="field.key"
            :label="field.label"
            :for-id="fieldInputId(field.key)"
          >
            <SearchableSelectInput
              :id="fieldInputId(field.key)"
              :modelValue="fieldMap(field.key)"
              @update:model-value="
                setFieldMapValue(field.key, $event as string)
              "
              :options="sourceOptions"
            />
          </FormField>
        </div>

        <div class="ogd:mt-5 ogd:flex ogd:flex-wrap ogd:gap-2.5">
          <button
            class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-4.5 ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
            type="button"
            :disabled="
              !formPayload.template_id ||
              wordpressApi.loading.value ||
              wpDeleteApi.loading.value
            "
            @click="save"
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
            {{ templateExists ? "Update Template" : "Activate Template" }}
          </button>
          <button
            v-if="templateExists"
            class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-rose-200 ogd:bg-white ogd:px-4.5 ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-rose-500 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
            type="button"
            :disabled="wpDeleteApi.loading.value"
            @click="deactivate"
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
              <path d="M18 6 6 18" />
              <path d="m6 6 12 12" />
            </svg>
            {{
              wpDeleteApi.loading.value
                ? "Deactivating..."
                : "Deactivate Template"
            }}
          </button>
        </div>

        <div
          v-if="wordpressApi.error.value"
          class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-rose-200 ogd:bg-rose-50 ogd:px-3.5 ogd:py-3 ogd:text-rose-700"
        >
          {{ wordpressApi.error.value }}
        </div>
        <div
          v-if="success"
          class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-green-200 ogd:bg-green-50 ogd:px-3.5 ogd:py-3 ogd:text-green-800"
        >
          {{ success }}
        </div>
      </div>

      <!-- Selected design preview. -->
      <div class="ogd:grid ogd:gap-4 ogd:self-start">
        <div
          v-if="formPayload.template_id && cloudApi.loading.value"
          class="ogd:overflow-hidden ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white"
        >
          <div class="ogd:animate-pulse">
            <div class="ogd:border-b ogd:border-gray-100 ogd:p-4">
              <div
                class="ogd:h-5 ogd:w-24 ogd:rounded-md ogd:bg-gray-100"
              ></div>
              <div
                class="ogd:mt-2 ogd:h-4 ogd:w-40 ogd:rounded-md ogd:bg-gray-100"
              ></div>
            </div>
            <div class="ogd:bg-gray-50 ogd:p-3">
              <div
                class="ogd:aspect-1200/630 ogd:w-full ogd:rounded-[14px] ogd:bg-gray-100"
              ></div>
            </div>
          </div>
        </div>
        <div
          v-else-if="formPayload.template_id"
          class="ogd:overflow-hidden ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white"
        >
          <div class="ogd:border-b ogd:border-gray-100 ogd:p-4">
            <h2
              class="ogd:m-0! ogd:font-display ogd:text-base ogd:font-bold ogd:text-gray-900"
            >
              Preview
            </h2>
            <p class="ogd:mt-1! ogd:mb-0! ogd:text-sm ogd:text-gray-500">
              {{ design?.name }}
            </p>
          </div>
          <div class="ogd:bg-w ogd:p-3">
            <img
              :src="selectedDesignPreviewUrl"
              :alt="`${postTypeLabel} design preview`"
              class="ogd:block ogd:w-full ogd:rounded-[14px] ogd:border! ogd:border-gray-100!"
            />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
