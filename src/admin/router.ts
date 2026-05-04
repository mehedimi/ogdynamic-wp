import { createRouter, createWebHashHistory } from 'vue-router'
import { adminNavigation } from './navigation'

export const adminRoutes = [
  { ...adminNavigation[0], component: () => import('./views/DashboardView.vue') },
  { ...adminNavigation[1], component: () => import('./views/ConnectionView.vue') },
  { ...adminNavigation[2], component: () => import('./views/TemplatesView.vue') },
  { ...adminNavigation[3], component: () => import('./views/FieldMappingView.vue') },
  { ...adminNavigation[4], component: () => import('./views/WooCommerceView.vue') },
  { ...adminNavigation[5], component: () => import('./views/AdvancedView.vue') },
  { ...adminNavigation[6], component: () => import('./views/DebugView.vue') },
] as const

export const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/onboarding', name: 'onboarding', component: () => import('./views/OnboardingView.vue') },
    ...adminRoutes,
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})
