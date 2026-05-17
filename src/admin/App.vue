<script setup lang="ts">
import { watch } from "vue";
import { RouterView, useRoute, useRouter } from "vue-router";
import Layout from "./components/Layout.vue";
import { isConnected } from "./state/connection";

const route = useRoute();
const router = useRouter();

watch(
  () => [isConnected.value, route.path] as const,
  ([connected, path]) => {
    if (!connected && path !== "/onboarding") {
      void router.replace("/onboarding");
      return;
    }

    if (connected && path === "/onboarding") {
      void router.replace("/");
    }
  },
  { immediate: true },
);
</script>

<template>
  <Layout v-if="isConnected">
    <RouterView />
  </Layout>
  <RouterView v-else />
</template>
