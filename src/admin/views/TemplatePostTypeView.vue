<script setup lang="ts">
import { computed, onMounted, reactive, shallowRef, watch } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import { postTypeMappingSources } from "../data/postTypeMappingSources";
import type { OGDDesign, OGDDesignSchemaField } from "../types";
import { useOgdApi } from "../composables/useOgdApi";
import { useOgdCloudApi } from "../composables/useOgdCloudApi";
import FormField from "../components/forms/FormField.vue";
import SelectInput from "../components/forms/SelectInput.vue";

type ApiListResponse<T> = {
  data?: T[];
};

type ApiItemResponse<T> = {
  data?: T;
};

type FieldOption = {
  key: string;
  label: string;
  type?: string;
};

type MappingSourceOption = {
  value_key: string;
  label: string;
};

type TemplateMapping = {
  template_id: string;
  map: Array<{
    attr_key: string;
    value_key: string;
  }>;
};

defineProps<{
  woocommerceActive: boolean;
}>();

const route = useRoute();
const router = useRouter();
const wordpressApi = useOgdApi();
const cloudApi = useOgdCloudApi();
const designs = shallowRef<OGDDesign[]>([]);
const fields = shallowRef<FieldOption[]>([]);
const savedFieldMap = shallowRef<Record<string, string>>({});
const savedTemplateId = shallowRef("");
const selectedDesign = shallowRef("");
const fieldMap = reactive<Record<string, string>>({});
const success = shallowRef("");

const postType = computed(() => String(route.params.postType || ""));
const postTypeLabel = computed(() => postType.value.charAt(0).toUpperCase() + postType.value.slice(1));
const createDesignUrl = computed(
  () =>
    `${window.ogdynamicAdmin.apiUrl.replace(/\/?api\/?$/, "")}/designs/create`,
);
const selectedDesignPreviewUrl = computed(() =>
  selectedDesign.value
    ? `https://cdn.ogdynamic.com/d/${selectedDesign.value}`
    : "",
);
const designOptions = computed(() => [
  { label: "Select design", value: "" },
  ...designs.value.map((design) => ({
    label: designLabel(design),
    value: design.id ?? "",
  })),
]);
const sourceOptions = computed(() => {
  const sources = postTypeMappingSources as Record<string, MappingSourceOption[]>;
  return [
    { value_key: "", label: "Do not override" },
    ...(sources[postType.value] ?? sources.default ?? []),
  ].map((option) => ({
    value: option.value_key,
    label: option.label,
  }));
});

watch(selectedDesign, async (designId) => {
  success.value = "";
  fields.value = [];
  clearFieldMap();

  if (!designId) {
    return;
  }

  await loadDesign(designId);
});

async function load() {
  await loadDesigns();
  await loadSavedMapping();
}

async function loadSavedMapping() {
  const payload = await wordpressApi.request<{
    data: Partial<TemplateMapping>;
  }>(`templates/${postType.value}`);
  const savedMap: Record<string, string> = {};

  if (payload.data && !Array.isArray(payload.data)) {
    for (const item of Array.isArray(payload.data.map)
      ? payload.data.map
      : []) {
      savedMap[item.attr_key] = item.value_key;
    }

    savedTemplateId.value =
      typeof payload.data.template_id === "string"
        ? payload.data.template_id
        : "";
    selectedDesign.value = savedTemplateId.value;
  } else {
    savedTemplateId.value = "";
    selectedDesign.value = "";
  }

  savedFieldMap.value = savedMap;

  Object.assign(fieldMap, savedMap);
}

async function loadDesigns() {
  const payload = await cloudApi.request<ApiListResponse<OGDDesign> | OGDDesign[]>(
    "v1/designs",
  );
  designs.value = Array.isArray(payload) ? payload : (payload.data ?? []);
}

async function refreshDesigns() {
  await loadDesigns();
}

async function loadDesign(designId: string) {
  const payload = await cloudApi.request<ApiItemResponse<OGDDesign> | OGDDesign>(
    `v1/designs/${designId}?include=template`,
  );
  const design = unwrapItem(payload);
  fields.value = designFields(design);

  for (const field of fields.value) {
    fieldMap[field.key] =
      savedFieldMap.value[field.key] ?? fieldMap[field.key] ?? "";
  }

  savedFieldMap.value = {};
}

function unwrapItem(payload: ApiItemResponse<OGDDesign> | OGDDesign): OGDDesign {
  if ("data" in payload && payload.data) {
    return payload.data;
  }

  return payload as OGDDesign;
}

async function save() {
  const map = fields.value.map((field) => ({
    attr_key: field.key,
    value_key: fieldMap[field.key] ?? "",
  }));

  await wordpressApi.request<{ data: TemplateMapping }>(
    `templates/${postType.value}`,
    {
      method: "PUT",
      body: {
        template_id: selectedDesign.value,
        map,
      },
    },
  );

  savedTemplateId.value = selectedDesign.value;
  success.value = "OG image template updated.";
}

async function deactivate() {
  await wordpressApi.request<{ data: [] }>(`templates/${postType.value}`, {
    method: "DELETE",
  });

  selectedDesign.value = "";
  fields.value = [];
  savedFieldMap.value = {};
  savedTemplateId.value = "";
  clearFieldMap();
  success.value = "OG image template deactivated.";
  await router.push("/templates");
}

function clearFieldMap() {
  for (const key of Object.keys(fieldMap)) {
    delete fieldMap[key];
  }
}

function designLabel(design: OGDDesign): string {
  return design.name || design.title || design.id || "Untitled design";
}

function designFields(design: OGDDesign): FieldOption[] {
  const schemaFields =
    design.template?.schema?.flatMap((group) => group.fields ?? []) ?? [];
  const overrideFields = schemaFields
    .filter((field) => true === field.allowOverride)
    .map((field) => normalizeSchemaField(field))
    .filter((field) => field.key !== "");

  if (overrideFields.length > 0) {
    return overrideFields;
  }

  const rawFields =
    design.available_fields ??
    design.editable_fields ??
    design.fields ??
    design.variables ??
    [];

  if (!Array.isArray(rawFields)) {
    return [];
  }

  return rawFields
    .map((field) => normalizeField(field))
    .filter((field): field is FieldOption => field !== null);
}

function normalizeSchemaField(field: OGDDesignSchemaField): FieldOption {
  return {
    key: field.key ?? "",
    label: field.label ?? field.key ?? "",
    type: field.type,
  };
}

function normalizeField(field: unknown): FieldOption | null {
  if (typeof field === "string") {
    return { key: field, label: field };
  }

  if (!field || typeof field !== "object") {
    return null;
  }

  const item = field as Record<string, unknown>;
  const key = item.key ?? item.name ?? item.id ?? item.attr_key;
  const label = item.label ?? item.title ?? item.name ?? key;

  if (typeof key !== "string") {
    return null;
  }

  return {
    key,
    label: typeof label === "string" ? label : key,
  };
}

function fieldInputId(key: string): string {
  return `ogd-template-map-${key}`;
}

onMounted(load);
</script>

<template>
  <section>
    <RouterLink
      class="ogd:mb-5 ogd:inline-flex ogd:text-sm ogd:font-semibold ogd:text-rose-500 ogd:no-underline ogd:hover:text-rose-600"
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
      class="ogd:mt-2 ogd:mb-7 ogd:max-w-155 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
    >
      Select an OG image design, then map its override fields to WordPress
      values for this post type.
    </p>

    <div
      class="ogd:mb-4.5 ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
    >
      <div
        class="ogd:grid ogd:grid-cols-[minmax(0,1fr)_auto] ogd:items-end ogd:gap-4 max-[720px]:ogd:grid-cols-1 max-[720px]:ogd:items-stretch"
      >
        <FormField forId="design-id" label="OG image design">
          <SelectInput
            id="design-id"
            v-model="selectedDesign"
            :options="designOptions"
            :disabled="cloudApi.loading.value"
          />
        </FormField>
        <div
          class="ogd:flex ogd:flex-wrap ogd:justify-end ogd:gap-2.5 max-[720px]:ogd:justify-start"
        >
          <a
            class="ogd:inline-flex ogd:items-center ogd:justify-center ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:px-4.5 ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-gray-700 ogd:no-underline ogd:transition ogd:hover:border-rose-200 ogd:hover:bg-rose-50 ogd:hover:text-gray-900"
            :href="createDesignUrl"
            target="_blank"
            rel="noreferrer"
          >
            Create design
          </a>
          <button
            class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:px-4.5 ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-gray-700 ogd:transition ogd:hover:border-rose-200 ogd:hover:bg-rose-50 ogd:hover:text-gray-900 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
            type="button"
            :disabled="cloudApi.loading.value"
            @click="refreshDesigns"
          >
            Refresh designs
          </button>
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
      <div
        v-if="selectedDesign"
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
      >
        <h2
          class="ogd:m-0 ogd:font-display ogd:text-base ogd:font-bold ogd:text-gray-900"
        >
          OG image mapping
        </h2>
        <p
          class="ogd:mt-2 ogd:mb-5 ogd:text-sm ogd:leading-relaxed ogd:text-gray-500"
        >
          Link each overridable template field to a WordPress source used in the
          generated OG image.
        </p>

        <div
          v-if="fields.length === 0"
          class="ogd:rounded-2xl ogd:border ogd:border-dashed ogd:border-rose-200 ogd:bg-rose-50 ogd:p-4.5 ogd:text-rose-800"
        >
          No override fields found for this design.
        </div>

        <div v-else class="ogd:grid ogd:gap-4">
          <FormField
            v-for="field in fields"
            :key="field.key"
            :label="field.label"
            :for-id="fieldInputId(field.key)"
          >
            <SelectInput
              :id="fieldInputId(field.key)"
              v-model="fieldMap[field.key]"
              :options="sourceOptions"
            />
          </FormField>
        </div>

        <div class="ogd:mt-5 ogd:flex ogd:flex-wrap ogd:gap-2.5">
          <button
            class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-gray-900 ogd:px-4.5 ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
            type="button"
            :disabled="!selectedDesign || wordpressApi.loading.value"
            @click="save"
          >
            {{ savedTemplateId ? "Update Template" : "Activate Template" }}
          </button>
          <button
            v-if="savedTemplateId"
            class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-rose-200 ogd:bg-white ogd:px-4.5 ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-rose-500 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
            type="button"
            :disabled="wordpressApi.loading.value"
            @click="deactivate"
          >
            Deactivate Template
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

      <div class="ogd:grid ogd:gap-4 ogd:self-start">
        <div
          v-if="selectedDesign"
          class="ogd:overflow-hidden ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white"
        >
          <img
            :src="selectedDesignPreviewUrl"
            :alt="`${postTypeLabel} design preview`"
            class="ogd:block ogd:w-full ogd:max-h-105 ogd:object-cover"
          />
        </div>
      </div>
    </div>
  </section>
</template>
