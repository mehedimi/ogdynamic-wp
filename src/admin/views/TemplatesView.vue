<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import { useOgdApi } from "../composables/useOgdApi";
import type { PostTypeOption } from "../types";

import defaultIcon from "../assets/icons/default.svg?raw";
import postIcon from "../assets/icons/post.svg?raw";
import pageIcon from "../assets/icons/page.svg?raw";
import productIcon from "../assets/icons/product.svg?raw";
import homeIcon from "../assets/icons/home.svg?raw";
import blogIcon from "../assets/icons/blog.svg?raw";
import categoryIcon from "../assets/icons/category.svg?raw";
import tagIcon from "../assets/icons/tag.svg?raw";
import authorIcon from "../assets/icons/author.svg?raw";
import dateIcon from "../assets/icons/date.svg?raw";
import searchIcon from "../assets/icons/search.svg?raw";

type TemplatesResponse = {
  data: PostTypeOption[];
  templates: string[];
};

const wordpressApi = useOgdApi();
const activatedTemplates = ref<string[]>([]);
const postTypes = ref<PostTypeOption[]>([]);
const isLoading = ref(true);
const activatedPostTypes = computed(() => new Set(activatedTemplates.value));
const isLoadingState = computed(() => wordpressApi.loading.value || isLoading.value);

// Decorative soft-chip accents (mirrors the app's tone badges); primary
// stays reserved for actions and selection. Each post type maps to a
// Lucide-style SVG icon (stroke, 24x24) instead of a text badge.
const NOT_ACTIVATED_TONE = "ogd:text-gray-400";
const ACTIVATED_TONE = "ogd:text-primary";

// Each post type renders its SVG file from assets/icons/ (Vite handles
// loading at build time) instead of a text badge.
const POST_TYPE_ICONS: Record<string, string> = {
  default: defaultIcon,
  post: postIcon,
  page: pageIcon,
  product: productIcon,
  home: homeIcon,
  blog: blogIcon,
  category: categoryIcon,
  tag: tagIcon,
  author: authorIcon,
  date: dateIcon,
  search: searchIcon,
};

function postTypeTone(postType: string): string {
  return isActivated(postType) ? ACTIVATED_TONE : NOT_ACTIVATED_TONE;
}

function postTypeIcon(postType: string): string {
  return POST_TYPE_ICONS[postType] ?? POST_TYPE_ICONS.default;
}

function isActivated(postType: string): boolean {
  return activatedPostTypes.value.has(postType);
}

async function loadData() {
  isLoading.value = true;
  try {
    const payload = await wordpressApi.request<TemplatesResponse>("templates");
    postTypes.value = Array.isArray(payload.data) ? payload.data : [];
    activatedTemplates.value = Array.isArray(payload.templates)
      ? payload.templates
      : [];
  } catch {
    postTypes.value = [];
    activatedTemplates.value = [];
  } finally {
    isLoading.value = false;
  }
}

onMounted(loadData);
</script>

<template>
  <section>
    <h1
      class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900"
    >
      Open Graph Templates
    </h1>
    <p
      class="ogd:mt-2 ogd:mb-7 ogd:max-w-155 ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500"
    >
      Choose a post type to configure the Open Graph image template WordPress
      should use when generating social share images.
    </p>

    <div
      v-if="isLoadingState"
      class="ogd:grid ogd:grid-cols-3 ogd:gap-4.5 max-[1100px]:ogd:grid-cols-2 max-[720px]:ogd:grid-cols-1"
    >
      <article
        v-for="item in 6"
        :key="item"
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
        aria-hidden="true"
      >
        <div class="ogd:animate-pulse">
          <div class="ogd:flex ogd:items-start ogd:justify-between ogd:gap-3">
            <div
              class="ogd:h-6 ogd:w-16 ogd:rounded-full ogd:bg-gray-100"
            ></div>
            <div
              class="ogd:h-6 ogd:w-24 ogd:rounded-full ogd:bg-gray-100"
            ></div>
          </div>
          <div
            class="ogd:mt-4 ogd:h-5 ogd:w-32 ogd:rounded-md ogd:bg-gray-100"
          ></div>
          <div class="ogd:mt-3 ogd:grid ogd:gap-2">
            <div
              class="ogd:h-4 ogd:w-full ogd:rounded-md ogd:bg-gray-100"
            ></div>
            <div class="ogd:h-4 ogd:w-4/5 ogd:rounded-md ogd:bg-gray-100"></div>
          </div>
        </div>
      </article>
    </div>

     <div
       v-else-if="postTypes.length > 0"
       class="ogd:grid ogd:grid-cols-3 ogd:gap-4.5 max-[1100px]:ogd:grid-cols-2 max-[720px]:ogd:grid-cols-1"
     >
       <RouterLink
         v-for="(postType, index) in postTypes"
        :key="postType.name"
        :to="`/templates/${postType.name}`"
        :class="[
          'ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6 ogd:no-underline ogd:transition-all! ogd:hover:border-primary/30 ogd:hover:shadow-[0_18px_48px_rgba(17,24,39,0.08)]',
          !isActivated(postType.name) ? 'ogd:opacity-60 ogd:hover:opacity-100' : '',
        ]"
      >
        <div class="ogd:flex ogd:items-start ogd:justify-between ogd:gap-3">
          <span
            class="ogd:inline-flex ogd:size-9 ogd:items-center ogd:justify-center [&>svg]:ogd:h-8 [&>svg]:ogd:w-auto [&>svg]:ogd:max-w-9 [&>svg]:ogd:shrink-0"
            :class="postTypeTone(postType.name)"
            aria-hidden="true"
            v-html="postTypeIcon(postType.name)"
          />
          <span
            class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:px-2.5 ogd:py-1 ogd:text-[11px] ogd:font-semibold ogd:tracking-wide"
            :class="
              isActivated(postType.name)
                ? 'ogd:border ogd:border-success/20 ogd:bg-success-soft ogd:text-success'
                : 'ogd:border ogd:border-gray-200 ogd:bg-gray-50 ogd:text-gray-500'
            "
          >
            {{ isActivated(postType.name) ? "Activated" : "Not activated" }}
          </span>
        </div>
        <h2
          class="ogd:mt-4 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold ogd:text-gray-900"
        >
          {{ postType.label }}
        </h2>
        <p
          class="ogd:mt-0 ogd:mb-0 ogd:text-sm ogd:leading-relaxed ogd:text-gray-500"
        >
          {{ postType.description }}
        </p>
      </RouterLink>
    </div>

    <div
      v-else
      class="ogd:flex ogd:items-center ogd:justify-center ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-12"
    >
      <p class="ogd:text-sm ogd:text-gray-500">
        No post types found
      </p>
    </div>
  </section>
</template>
