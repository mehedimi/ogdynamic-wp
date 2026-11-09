import { readonly, ref } from "vue";
import axios, { AxiosError, type AxiosRequestConfig } from "axios";
import { apiKey, setApiKey } from "../state/connection";
import { useOgdApi } from "./useOgdApi";

type ApiErrorResponse = {
  message?: string;
};

type ConnectionResponse = {
  data: {
    connected: boolean;
    api_key: string;
  };
};

const ogdApi = useOgdApi();
const client = axios.create({
  baseURL: `${window.ogdynamicAdmin.apiUrl.replace(/\/$/, "")}/`,
});

// Resolve the bearer token at request time so a token recovered after a
// 401 is picked up by subsequent calls without a page reload.
client.interceptors.request.use((config) => {
  if (apiKey.value) {
    config.headers.Authorization = `Bearer ${apiKey.value}`;
  }
  return config;
});

client.interceptors.response.use(
  (response) => response,
  async (error) => {
    const config = error.config as
      | (AxiosRequestConfig & { _ogdRetried?: boolean })
      | undefined;

    if (error.response?.status !== 401 || !config || config._ogdRetried) {
      return Promise.reject(error);
    }

    config._ogdRetried = true;

    // A 401 usually means the page has been open long enough for the
    // access token to lapse. GET /connection makes the server refresh
    // the token; the connection is only treated as lost when the server
    // says so — never deleted over one stale token.
    try {
      const payload = await ogdApi.request<ConnectionResponse>("connection");

      if (payload.data.connected && payload.data.api_key) {
        setApiKey(payload.data.api_key);
        return client.request(config);
      }
    } catch {
      // Fall through to disconnected state.
    }

    setApiKey("");
    return Promise.reject(error);
  }
);

export function useOgdCloudApi() {
  const loading = ref(false);
  const error = ref("");

  async function request<T>(path: string): Promise<T> {
    loading.value = true;
    error.value = "";

    try {
      const { data } = await client.get<T>(path.replace(/^\//, ""));
      return data;
    } catch (caught) {
      error.value = getErrorMessage(caught);
      throw caught;
    } finally {
      loading.value = false;
    }
  }

  return {
    loading: readonly(loading),
    error: readonly(error),
    request,
  };
}

function getErrorMessage(caught: unknown): string {
  if (caught instanceof AxiosError) {
    const data = caught.response?.data as ApiErrorResponse | undefined;
    return data?.message ?? caught.message;
  }

  return caught instanceof Error ? caught.message : "Request failed.";
}
