<script setup lang="ts">
import { computed, reactive, ref, shallowRef, watch, onMounted } from "vue";
import { RouterLink, useRoute, useRouter } from "vue-router";
import type { ApiData, CursorPage, OGDCanvas, OGDCanvasSummary } from "../types";
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
  /** Dropdown section the source is listed under (e.g. its ACF field group). */
  group?: string;
  /** Pill shown next to the label (e.g. "ACF"). */
  badge?: string;
  multi?: boolean;
};

type TemplateMapping = {
  template_id: string;
  width: number;
  height: number;
  map: Array<{
    attr_key: string;
    key: string;
  }>;
};

const OG_IMAGE_WIDTH = 1200;
const OG_IMAGE_HEIGHT = 630;

const route = useRoute();
const router = useRouter();

const isLoading = ref(true);
const templateExists = ref(false);

const wordpressApi = useOgdApi();
const cloudApi = useOgdCloudApi();
const wpDeleteApi = useOgdApi();

const canvases = shallowRef<OGDCanvasSummary[]>([]);
const canvas = shallowRef<OGDCanvas>();
const canvasListFailed = ref(false);

// Cursor pagination: the selector lists page one, then loads more as the
// dropdown list is scrolled to its end.
const nextCursor = ref<string | null>(null);
const loadingMoreCanvases = ref(false);
const hasMoreCanvases = computed(() => nextCursor.value !== null);

// Feature-scoped loading state. cloudApi.loading flips on every request —
// including background loads like load-more — so skeletons and disabled
// states bind to these instead of re-running the whole page.
const canvasesLoading = ref(false);
const templateLoading = ref(false);
const templateError = ref("");

const formPayload = reactive<TemplateMapping>({
  template_id: "",
  width: OG_IMAGE_WIDTH,
  height: OG_IMAGE_HEIGHT,
  map: [],
});

const mappingSources = shallowRef<MappingSourceOption[]>([]);

const success = ref("");

let templateRequestSeq = 0;

const postType = computed(() => String(route.params.postType || ""));
const postTypeLabel = computed(
  () => postType.value.charAt(0).toUpperCase() + postType.value.slice(1),
);
const appUrl = computed(() =>
  window.ogdynamicAdmin.apiUrl.replace(/\/?api\/?$/, ""),
);
const createCanvasUrl = computed(() => `${appUrl.value}/canvases/create`);
const editCanvasUrl = computed(() =>
  formPayload.template_id
    ? `${appUrl.value}/canvases/${formPayload.template_id}/edit`
    : "",
);
const selectedPreviewUrl = computed(() =>
  formPayload.template_id
    ? `${window.ogdynamicAdmin.cdnUrl}c/${formPayload.template_id}`
    : "",
);
const activeTemplateName = computed(() => canvas.value?.name);

const fieldMap = computed(() => {
  return (key: string): string =>
    formPayload.map.find((map) => map.attr_key === key)?.key || "";
});

const fields = computed<FieldOption[]>(() => {
  return (
    canvas.value?.override_fields.map((field) => ({
      key: field.field,
      label: field.name,
      type: field.type,
    })) || []
  );
});

const isOgSize = (c: OGDCanvasSummary): boolean =>
  c.width === OG_IMAGE_WIDTH && c.height === OG_IMAGE_HEIGHT;

const selectorOptions = computed(() => {
  const options = canvases.value.map((c) => ({
    label: c.name,
    value: c.id,
    description: `${c.width}×${c.height}`,
    badge: isOgSize(c) ? "Recommended" : undefined,
  }));

  // A saved mapping can point at a canvas the list has not paged to yet —
  // fall back to the loaded canvas detail so the selector displays its
  // name. Once paging reaches the canvas, the list entry wins.
  const selected = canvas.value;
  if (selected && !options.some((option) => option.value === selected.id)) {
    options.unshift({
      label: selected.name,
      value: selected.id,
      description: `${selected.width}×${selected.height}`,
      badge: isOgSize(selected) ? "Recommended" : undefined,
    });
  }

  return [{ label: "Select your canvas", value: "" }, ...options];
});

const sourceOptions = computed(() => [
  { value: "", label: "Do not override" },
  ...mappingSources.value
    // Canvas render URLs take flat string params only, so multi-value
    // sources (e.g. tags) cannot be passed and are not offered.
    .filter((source) => !source.multi)
    .map((d) => ({
      value: d.key,
      label: d.label,
      group: d.group,
      badge: d.badge,
    })),
]);

watch(
  () => formPayload.template_id,
  async (templateId) => {
    success.value = "";
    await loadSelectedTemplate(templateId);
  },
);

async function loadSavedMapping() {
  isLoading.value = true;
  try {
    const payload = await wordpressApi.request<{
      data: Partial<TemplateMapping>;
      sources: MappingSourceOption[];
    }>(`templates/${postType.value}`);

    mappingSources.value = payload.sources;

    if (!Array.isArray(payload.data)) {
      formPayload.map = payload.data.map || [];
      templateExists.value = true;
      formPayload.template_id = payload.data.template_id || "";

      // Seed the stored dimensions so a save while the canvas detail
      // request has not resolved (or failed) keeps them instead of
      // resetting to the standard OG size. loadCanvas() overwrites
      // these with the live canvas values once it resolves.
      formPayload.width = payload.data.width ?? OG_IMAGE_WIDTH;
      formPayload.height = payload.data.height ?? OG_IMAGE_HEIGHT;
    }
  } finally {
    isLoading.value = false;
  }
}

async function loadCanvases() {
  canvasListFailed.value = false;
  // Drop the stale cursor while refetching so a concurrent load-more
  // cannot append old pages to the fresh list.
  nextCursor.value = null;

  canvasesLoading.value = true;

  try {
    const payload = await cloudApi.request<CursorPage<OGDCanvasSummary>>(
      `v1/canvases?status=public`,
    );
    canvases.value = Array.isArray(payload.data) ? payload.data : [];
    nextCursor.value = readCursor(payload);
  } catch {
    canvasListFailed.value = true;
  } finally {
    canvasesLoading.value = false;
  }
}

/** Appends the next cursor page to the canvas list (infinite scroll). */
async function loadMoreCanvases() {
  if (loadingMoreCanvases.value || nextCursor.value === null) {
    return;
  }

  loadingMoreCanvases.value = true;

  try {
    const payload = await cloudApi.request<CursorPage<OGDCanvasSummary>>(
      `v1/canvases?status=public&cursor=${encodeURIComponent(nextCursor.value)}`,
    );
    const items = Array.isArray(payload.data) ? payload.data : [];
    const known = new Set(canvases.value.map((item) => item.id));

    canvases.value = [
      ...canvases.value,
      ...items.filter((item) => !known.has(item.id)),
    ];

    // An empty page means the cursor is unreliable — stop paging.
    nextCursor.value = items.length > 0 ? readCursor(payload) : null;
  } catch {
    // Keep the loaded list and cursor; scrolling retries the page.
  } finally {
    loadingMoreCanvases.value = false;
  }
}

function readCursor(payload: CursorPage<OGDCanvasSummary>): string | null {
  // Laravel puts the cursor at the top level, but API resources nest the
  // paginator inside `meta` — accept both shapes.
  const cursor = payload.next_cursor ?? payload.meta?.next_cursor ?? null;

  return typeof cursor === "string" && cursor !== "" ? cursor : null;
}

async function loadSelectedTemplate(templateId: string) {
  const seq = ++templateRequestSeq;
  templateError.value = "";

  if (!templateId) {
    canvas.value = undefined;
    // No canvas selected — save falls back to the standard OG size.
    formPayload.width = OG_IMAGE_WIDTH;
    formPayload.height = OG_IMAGE_HEIGHT;
    return;
  }

  await loadCanvas(templateId, seq);
}

async function loadCanvas(canvasId: string, seq: number) {
  templateLoading.value = true;

  try {
    const { data } = await cloudApi.request<ApiData<OGDCanvas>>(
      `v1/canvases/${canvasId}`,
    );

    if (seq !== templateRequestSeq) {
      return;
    }

    canvas.value = data;
    formPayload.width = data.width;
    formPayload.height = data.height;
    syncFieldMap();
  } catch {
    // Keep the previously loaded canvas; surface the failure in the banner.
    templateError.value = cloudApi.error.value || "Request failed.";
  } finally {
    templateLoading.value = false;
  }
}

async function save() {
  try {
    await wpDeleteApi.request<{ data: TemplateMapping }>(
      `templates/${postType.value}`,
      {
        method: "PUT",
        body: formPayload,
      },
    );
    success.value = "Open Graph image template updated.";
    templateExists.value = true;
  } catch {
    success.value = "";
  }
}

async function deactivate() {
  try {
    await wpDeleteApi.request<{ data: [] }>(`templates/${postType.value}`, {
      method: "DELETE",
    });
    await router.push("/templates");
  } catch {
    // Error handled by API error state
  }
}

function fieldInputId(key: string): string {
  return `ogd-template-map-${key}`;
}

function setFieldMapValue(attr_key: string, key: string) {
  const map = formPayload.map.find((map) => map.attr_key === attr_key);

  if (map) {
    map.key = key;
  } else {
    formPayload.map.push({ key, attr_key });
  }
}

function syncFieldMap() {
  // Drop entries for fields the selected template no longer has
  // (e.g. after switching to a canvas with different override fields)
  // and multi-value sources a canvas cannot take.
  const fieldKeys = new Set(fields.value.map((field) => field.key));
  formPayload.map = formPayload.map.filter(
    (map) => fieldKeys.has(map.attr_key) && !isMultiSource(map.key),
  );

  for (const field of fields.value) {
    if (formPayload.map.some((map) => map.attr_key === field.key)) continue;

    const source = findMatchingSource(field);
    if (source) {
      formPayload.map.push({ attr_key: field.key, key: source.key });
    }
  }
}

function isMultiSource(key: string): boolean {
  return mappingSources.value.some(
    (source) => source.key === key && source.multi,
  );
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

onMounted(async () => {
  try {
    await Promise.all([loadCanvases(), loadSavedMapping()]);
  } catch {
    // Errors handled individually
  }
});
</script>

<template>
  <section>
    <!-- Page heading and return navigation. -->
    <RouterLink
      class="ogd:mb-5 ogd:inline-flex ogd:text-sm! ogd:font-semibold! ogd:text-primary! ogd:hover:text-primary-hover!"
      to="/templates"
    >
      Back to post types
    </RouterLink>

    <h1
      class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900"
    >
      {{ postTypeLabel }} Open Graph Image Template
    </h1>
    <p
      class="ogd:mt-2! ogd:mb-6 ogd:max-w-140 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
    >
      Map an ogdynamic canvas to WordPress sources for this post type.
    </p>

    <!-- Template selector skeleton while the WordPress mapping data loads. -->
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
            <div class="ogd:h-10 ogd:w-32 ogd:rounded-full ogd:bg-gray-100"></div>
            <div class="ogd:h-10 ogd:w-34 ogd:rounded-full ogd:bg-gray-100"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Template selector and helper actions. -->
    <div
      v-else
      class="ogd:mb-4.5 ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
    >
      <div
        class="ogd:flex ogd:items-center ogd:justify-between ogd:gap-4 max-[720px]:ogd:flex-col max-[720px]:ogd:items-stretch"
      >
        <div class="ogd:flex-1">
          <SearchableSelectInput
            id="template-id"
            v-model="formPayload.template_id"
            :options="selectorOptions"
            :disabled="canvasesLoading || templateLoading"
            :has-more="hasMoreCanvases"
            :loading-more="loadingMoreCanvases"
            placeholder="Search canvases"
            @load-more="loadMoreCanvases"
          />
        </div>
        <div
          class="ogd:flex ogd:shrink-0 ogd:flex-wrap ogd:justify-end ogd:gap-2.5 max-[720px]:ogd:justify-start"
        >
          <button
            class="ogd:inline-flex ogd:size-10 ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:text-gray-700 ogd:transition ogd:hover:border-primary ogd:hover:bg-primary-soft ogd:hover:text-primary ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
            type="button"
            title="Refresh canvases"
            aria-label="Refresh canvases"
            :disabled="canvasesLoading"
            @click="loadCanvases"
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
            v-if="editCanvasUrl"
            class="ogd:inline-flex ogd:size-10 ogd:items-center ogd:justify-center ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:text-gray-700 ogd:no-underline ogd:transition ogd:hover:border-primary ogd:hover:bg-primary-soft ogd:hover:text-primary"
            :href="editCanvasUrl"
            title="Edit canvas"
            aria-label="Edit canvas"
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
              <path
                d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"
              />
              <path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4Z" />
            </svg>
          </a>
          <a
            class="ogd:inline-flex ogd:size-10 ogd:items-center ogd:justify-center ogd:rounded-full ogd:border ogd:border-gray-200 ogd:bg-white ogd:text-gray-700 ogd:no-underline ogd:transition ogd:hover:border-primary ogd:hover:bg-primary-soft ogd:hover:text-primary"
            :href="createCanvasUrl"
            title="Create canvas"
            aria-label="Create canvas"
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
        v-if="templateError && !canvasListFailed"
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-destructive/25 ogd:bg-destructive/5 ogd:px-3.5 ogd:py-3 ogd:text-destructive"
      >
        {{ templateError }}
      </div>
      <div
        v-if="canvas && canvas.status !== 'public'"
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-warning/25 ogd:bg-warning-soft ogd:px-3.5 ogd:py-3 ogd:text-[13px] ogd:text-warning-text"
      >
        “{{ canvas.name }}” is {{ canvas.status || "not public" }}. ogdynamic
        renders Open Graph images only for public canvases — set it to public in the
        <a
          class="ogd:font-semibold ogd:text-warning-text ogd:underline"
          :href="editCanvasUrl || createCanvasUrl"
          target="_blank"
          rel="noreferrer"
        >
          canvas editor
        </a>
        or pick a different canvas.
      </div>
      <div
        v-if="
          canvas &&
          (canvas.width !== OG_IMAGE_WIDTH ||
            canvas.height !== OG_IMAGE_HEIGHT)
        "
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-warning/25 ogd:bg-warning-soft ogd:px-3.5 ogd:py-3 ogd:text-[13px] ogd:text-warning-text"
      >
        “{{ canvas.name }}” is {{ canvas.width }}×{{ canvas.height }}px. Open
        Graph images render at {{ OG_IMAGE_WIDTH }}×{{ OG_IMAGE_HEIGHT }} and the
        Twitter image link is only generated for that standard size — resize
        the canvas in the
        <a
          class="ogd:font-semibold ogd:text-warning-text ogd:underline"
          :href="editCanvasUrl || createCanvasUrl"
          target="_blank"
          rel="noreferrer"
        >
          canvas editor
        </a>
        or pick a different canvas.
      </div>
      <div
        v-if="canvasListFailed"
        class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-warning/25 ogd:bg-warning-soft ogd:px-3.5 ogd:py-3 ogd:text-[13px] ogd:text-warning-text"
      >
        Your canvases could not be loaded. If you connected this site before
        canvases were introduced,
        <RouterLink
          class="ogd:font-semibold ogd:text-warning-text ogd:underline"
          to="/connection"
        >
          reconnect your account
        </RouterLink>
        to refresh access.
      </div>
    </div>

    <div
      class="ogd:grid ogd:grid-cols-[minmax(0,1.1fr)_minmax(420px,1.6fr)] ogd:gap-6 max-[1024px]:ogd:grid-cols-1"
    >
      <!-- Mapping form skeleton while the selected template detail loads. -->
      <div
        v-if="formPayload.template_id && templateLoading"
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

      <!-- Field mapping form for the selected OG image template. -->
      <div
        v-else-if="formPayload.template_id"
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
      >
        <h2
          class="ogd:m-0! ogd:font-display ogd:text-base ogd:font-bold ogd:text-gray-900"
        >
          Open Graph image mapping
        </h2>
        <p
          v-if="fields.length"
          class="ogd:mt-2! ogd:mb-5 ogd:text-sm ogd:leading-relaxed ogd:text-gray-500"
        >
          Link each overridable field to a WordPress source used in the
          generated Open Graph image.
        </p>

        <div
          v-if="!fields.length"
          class="ogd:mt-4 ogd:mb-1 ogd:rounded-[14px] ogd:border ogd:border-gray-100 ogd:bg-gray-50 ogd:px-3.5 ogd:py-3 ogd:text-sm ogd:text-gray-500"
        >
          No mappable fields found for this canvas.
        </div>

        <div v-else class="ogd:grid ogd:gap-4">
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
            class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-transparent ogd:bg-primary ogd:px-4.5 ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-white ogd:transition ogd:hover:border-primary-hover ogd:hover:bg-primary-hover ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
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
            class="ogd:inline-flex ogd:cursor-pointer ogd:items-center ogd:justify-center ogd:gap-2 ogd:rounded-full ogd:border ogd:border-destructive/40 ogd:bg-white ogd:px-4.5 ogd:py-2.5 ogd:text-[13px] ogd:font-semibold ogd:text-destructive ogd:transition ogd:hover:border-destructive ogd:hover:bg-destructive ogd:hover:text-white ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
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
          class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-destructive/25 ogd:bg-destructive/5 ogd:px-3.5 ogd:py-3 ogd:text-destructive"
        >
          {{ wordpressApi.error.value }}
        </div>
        <div
          v-if="success"
          class="ogd:mt-4 ogd:rounded-[14px] ogd:border ogd:border-success/25 ogd:bg-success-soft ogd:px-3.5 ogd:py-3 ogd:text-success-text"
        >
          {{ success }}
        </div>
      </div>

      <!-- Selected template preview. -->
      <div class="ogd:grid ogd:gap-4 ogd:self-start">
        <div
          v-if="formPayload.template_id && templateLoading"
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
              {{ activeTemplateName }}
            </p>
          </div>
          <div class="ogd:bg-gray-50 ogd:p-3">
            <img
              :src="selectedPreviewUrl"
              :alt="`${postTypeLabel} template preview`"
              class="ogd:block ogd:w-full ogd:rounded-[14px] ogd:border! ogd:border-gray-100!"
            />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
