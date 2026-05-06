import { readonly, shallowRef } from 'vue'
import axios, { AxiosError } from 'axios'

type RestErrorResponse = {
  message?: string
  data?: {
    params?: Record<string, string>
    details?: Record<string, { message?: string }>
  }
}

type RequestOptions = {
  method?: string
  body?: unknown
}

export function useOgdApi() {
  const loading = shallowRef(false)
  const error = shallowRef('')
  const client = axios.create({
    baseURL: window.ogdynamicAdmin.restUrl,
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': window.ogdynamicAdmin.nonce,
    },
    withCredentials: true,
  })

  async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
    loading.value = true
    error.value = ''

    try {
      const response = await client.request<T>({
        url: path,
        method: options.method ?? 'GET',
        data: options.body,
      })

      return response.data
    } catch (caught) {
      error.value = getErrorMessage(caught)
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

function getErrorMessage(caught: unknown): string {
  if (caught instanceof AxiosError) {
    const data = caught.response?.data as RestErrorResponse | undefined
    const detailMessage = getRestDetailMessage(data)

    return detailMessage ?? data?.message ?? caught.message
  }

  return caught instanceof Error ? caught.message : 'Request failed.'
}

function getRestDetailMessage(data: RestErrorResponse | undefined): string | undefined {
  if (!data?.data) {
    return undefined
  }

  const details = data.data.details
  if (details) {
    for (const detail of Object.values(details)) {
      if (detail.message) {
        return detail.message
      }
    }
  }

  const params = data.data.params
  if (params) {
    return Object.values(params).find((message) => message !== '')
  }

  return undefined
}
