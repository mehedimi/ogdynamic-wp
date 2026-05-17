import { computed, shallowRef } from 'vue'

export const apiKey = shallowRef(window.ogdynamicAdmin.apiKey ?? '')
export const isConnected = computed(() => apiKey.value !== '')

export function setApiKey(value: string) {
  apiKey.value = value
  window.ogdynamicAdmin.apiKey = value
}
