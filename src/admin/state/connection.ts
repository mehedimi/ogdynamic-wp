import { shallowRef } from 'vue'

export const apiKey = shallowRef(window.ogdynamicAdmin.apiKey ?? '')

export function setApiKey(value: string) {
  apiKey.value = value
  window.ogdynamicAdmin.apiKey = value
}
