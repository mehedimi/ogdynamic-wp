import { readonly, shallowRef } from 'vue'

type RequestOptions = {
  method?: string
  body?: unknown
}

export function useOgdApi() {
  const loading = shallowRef(false)
  const error = shallowRef('')

  async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
    loading.value = true
    error.value = ''

    try {
      const response = await fetch(`${window.ogdynamicAdmin.restUrl}${path}`, {
        method: options.method ?? 'GET',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.ogdynamicAdmin.nonce,
        },
        body: options.body === undefined ? undefined : JSON.stringify(options.body),
      })

      const payload = await response.json().catch(() => ({}))

      if (!response.ok) {
        const message = payload?.message ?? 'Request failed.'
        throw new Error(message)
      }

      return payload as T
    } catch (caught) {
      error.value = caught instanceof Error ? caught.message : 'Request failed.'
      throw caught
    } finally {
      loading.value = false
    }
  }

  return {
    loading: readonly(loading),
    error: readonly(error),
    request,
  }
}
