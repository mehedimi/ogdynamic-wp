import { createRouter, createWebHashHistory } from "vue-router";
import { adminNavigation } from "./navigation";

export const adminRoutes = [
  {
    ...adminNavigation[0],
    component: () => import("./views/DashboardView.vue"),
  },
  {
    ...adminNavigation[1],
    component: () => import("./views/TemplatesView.vue"),
  },
  {
    path: "/connection",
    name: "connection",
    label: "Connection",
    component: () => import("./views/ConnectionView.vue"),
  },
  {
    path: "/templates/:postType",
    name: "template-post-type",
    label: "Template Mapping",
    component: () => import("./views/TemplatePostTypeView.vue"),
  },
] as const;

export const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: "/onboarding",
      name: "onboarding",
      component: () => import("./views/OnboardingView.vue"),
    },
    ...adminRoutes,
    { path: "/:pathMatch(.*)*", redirect: "/" },
  ],
});
