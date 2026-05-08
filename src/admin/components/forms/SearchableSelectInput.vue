<script setup lang="ts">
import { computed, shallowRef } from "vue";
import {
  ComboboxAnchor,
  ComboboxContent,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxItem,
  ComboboxItemIndicator,
  ComboboxPortal,
  ComboboxRoot,
  ComboboxTrigger,
  ComboboxViewport,
} from "reka-ui";

type SelectOption = {
  label: string;
  value: string;
};

const EMPTY_VALUE = "__ogdynamic_empty_value__";

const model = defineModel<string>({ required: true });
const searchTerm = shallowRef("");

const props = withDefaults(
  defineProps<{
    id?: string;
    options: SelectOption[];
    disabled?: boolean;
    placeholder?: string;
  }>(),
  {
    id: undefined,
    disabled: false,
    placeholder: "Search and select",
  },
);

const comboboxModel = computed({
  get() {
    return "" === model.value ? EMPTY_VALUE : model.value;
  },
  set(value: string) {
    model.value = EMPTY_VALUE === value ? "" : value;
  },
});

const comboboxOptions = computed(() => {
  return props.options.map((option) => ({
    label: option.label,
    value: "" === option.value ? EMPTY_VALUE : option.value,
  }));
});

function displayValue(value: unknown): string {
  return (
    comboboxOptions.value.find((option) => option.value === value)?.label ?? ""
  );
}

function clearSearchTerm(): void {
  searchTerm.value = "";
}
</script>

<template>
  <ComboboxRoot
    v-model="comboboxModel"
    :disabled="disabled"
    :reset-search-term-on-select="true"
    :open-on-click="true"
    :open-on-focus="true"
  >
    <ComboboxAnchor class="ogd:relative ogd:block ogd:w-full">
      <ComboboxInput
        :id="id"
        v-model="searchTerm"
        :display-value="displayValue"
        :disabled="disabled"
        :placeholder="placeholder"
        class="ogd:w-full ogd:rounded-full! ogd:border ogd:border-gray-200! ogd:bg-gray-50 ogd:py-1.5! ogd:pr-11! ogd:pl-6! ogd:text-[13.5px]! ogd:text-gray-900! ogd:outline-none! ogd:transition ogd:placeholder:text-gray-400 ogd:focus:border-rose-400! ogd:focus:bg-white! ogd:focus:shadow-[0_0_0_3px_rgba(244,63,94,0.08)] ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
        @focus="clearSearchTerm"
        @click="clearSearchTerm"
      />
      <ComboboxTrigger
        class="ogd:absolute ogd:top-1/2 ogd:right-3 ogd:inline-flex ogd:size-7 ogd:-translate-y-1/2 ogd:items-center ogd:justify-center ogd:rounded-full ogd:text-gray-400 ogd:transition ogd:hover:bg-gray-100 ogd:hover:text-gray-700 ogd:disabled:cursor-not-allowed ogd:disabled:opacity-50"
        :disabled="disabled"
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
          <path d="m6 9 6 6 6-6" />
        </svg>
      </ComboboxTrigger>
    </ComboboxAnchor>

    <ComboboxPortal>
      <ComboboxContent
        position="popper"
        align="start"
        :side-offset="6"
        class="ogd:z-999999 ogd:max-h-72 ogd:w-(--reka-combobox-trigger-width) ogd:overflow-hidden ogd:rounded-2xl ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-1.5 ogd:shadow-[0_18px_48px_rgba(17,24,39,0.12)]"
      >
        <ComboboxViewport class="ogd:max-h-68 ogd:overflow-y-auto">
          <ComboboxEmpty
            class="ogd:px-3 ogd:py-2.5 ogd:text-sm ogd:text-gray-500"
          >
            No sources found.
          </ComboboxEmpty>

          <ComboboxItem
            v-for="option in comboboxOptions"
            :key="option.value"
            :value="option.value"
            :text-value="option.label"
            class="ogd:flex ogd:cursor-pointer ogd:items-center ogd:justify-between ogd:gap-3 ogd:rounded-xl ogd:px-3 ogd:py-2 ogd:text-sm ogd:text-gray-700 ogd:outline-none ogd:transition ogd:data-highlighted:bg-rose-50 ogd:data-highlighted:text-gray-900 ogd:data-[state=checked]:text-gray-900"
          >
            <span class="ogd:truncate">{{ option.label }}</span>
            <ComboboxItemIndicator class="ogd:shrink-0 ogd:text-rose-500">
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
                <path d="m20 6-11 11-5-5" />
              </svg>
            </ComboboxItemIndicator>
          </ComboboxItem>
        </ComboboxViewport>
      </ComboboxContent>
    </ComboboxPortal>
  </ComboboxRoot>
</template>
