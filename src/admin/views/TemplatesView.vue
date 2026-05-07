<script setup lang="ts">
import { computed, onMounted, shallowRef } from 'vue'
import { RouterLink } from 'vue-router'
import { useOgdApi } from '../composables/useOgdApi'
import type { PostTypeOption } from '../types'

type TemplatesResponse = {
  data: PostTypeOption[]
  templates: string[]
}

const wordpressApi = useOgdApi()
const activatedTemplates = shallowRef<string[]>([])
const postTypes = shallowRef<PostTypeOption[]>([])

const displayPostTypes = computed(() =>
  postTypes.value.filter((postType) => 'attachment' !== postType.name),
)

const activatedPostTypes = computed(() => {
  return new Set(activatedTemplates.value)
})

function isActivated(postType: string): boolean {
  return activatedPostTypes.value.has(postType)
}

function loadData() {
  return wordpressApi
    .request<TemplatesResponse>('templates')
    .then((payload) => {
      postTypes.value = Array.isArray(payload.data) ? payload.data : []
      activatedTemplates.value = Array.isArray(payload.templates) ? payload.templates : []
    })
    .catch(() => {
      postTypes.value = []
      activatedTemplates.value = []
    })
}

onMounted(loadData)
</script>

<template>
  <section>
    <h1 class="ogd:m-0 ogd:font-display ogd:text-3xl ogd:font-bold ogd:tracking-[-0.03em] ogd:text-gray-900">Templates</h1>
    <p class="ogd:mt-2 ogd:mb-7 ogd:max-w-[620px] ogd:text-[15px] ogd:leading-relaxed ogd:text-gray-500">Choose a post type to configure the OG image template WordPress should use when generating social share images.</p>

    <div v-if="wordpressApi.loading.value" class="ogd:grid ogd:grid-cols-3 ogd:gap-[18px] max-[1100px]:ogd:grid-cols-2 max-[720px]:ogd:grid-cols-1">
      <article
        v-for="item in 6"
        :key="item"
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6"
      >
        <div class="ogd:animate-pulse">
          <div class="ogd:flex ogd:items-start ogd:justify-between ogd:gap-3">
            <div class="ogd:h-6 ogd:w-16 ogd:rounded-full ogd:bg-gray-100"></div>
            <div class="ogd:h-6 ogd:w-24 ogd:rounded-full ogd:bg-gray-100"></div>
          </div>
          <div class="ogd:mt-4 ogd:h-5 ogd:w-32 ogd:rounded-md ogd:bg-gray-100"></div>
          <div class="ogd:mt-3 ogd:grid ogd:gap-2">
            <div class="ogd:h-4 ogd:w-full ogd:rounded-md ogd:bg-gray-100"></div>
            <div class="ogd:h-4 ogd:w-4/5 ogd:rounded-md ogd:bg-gray-100"></div>
          </div>
        </div>
      </article>
    </div>

    <div v-else class="ogd:grid ogd:grid-cols-3 ogd:gap-[18px] max-[1100px]:ogd:grid-cols-2 max-[720px]:ogd:grid-cols-1">
      <RouterLink
        v-for="postType in displayPostTypes"
        :key="postType.name"
        :to="`/templates/${postType.name}`"
        class="ogd:rounded-[20px] ogd:border ogd:border-gray-100 ogd:bg-white ogd:p-6 ogd:no-underline ogd:transition ogd:hover:border-rose-100 ogd:hover:shadow-[0_18px_48px_rgba(17,24,39,0.08)]"
      >
        <div class="ogd:flex ogd:items-start ogd:justify-between ogd:gap-3">
          <span class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:border ogd:border-rose-100 ogd:bg-rose-50 ogd:px-2.5 ogd:py-1 ogd:font-display ogd:text-[11px] ogd:font-bold ogd:uppercase ogd:tracking-wide ogd:text-rose-500">
            {{ postType.name }}
          </span>
          <span
            class="ogd:inline-flex ogd:items-center ogd:rounded-full ogd:px-2.5 ogd:py-1 ogd:text-[11px] ogd:font-semibold ogd:tracking-wide"
            :class="
              isActivated(postType.name)
                ? 'ogd:border ogd:border-emerald-200 ogd:bg-emerald-50 ogd:text-emerald-700'
                : 'ogd:border ogd:border-gray-200 ogd:bg-gray-50 ogd:text-gray-500'
            "
          >
            {{ isActivated(postType.name) ? 'Activated' : 'Not activated' }}
          </span>
        </div>
        <h2 class="ogd:mt-4 ogd:mb-1.5 ogd:font-display ogd:text-base ogd:font-bold ogd:text-gray-900">{{ postType.label }}</h2>
        <p class="ogd:mt-0 ogd:mb-0 ogd:text-sm ogd:leading-relaxed ogd:text-gray-500">
          {{ postType.description }}
        </p>
      </RouterLink>
    </div>
  </section>
</template>
