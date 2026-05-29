import { readonly, ref } from "vue";
import axios, { AxiosError } from "axios";
import { setApiKey } from "../state/connection";
import { useOgdApi } from "./useOgdApi";

type ApiErrorResponse = {
  message?: string;
};

const ogdApi = useOgdApi();
const client = axios.create({
  baseURL: `${window.ogdynamicAdmin.apiUrl.replace(/\/$/, "")}/`,
  headers: {
    Accept: "application/json",
    Authorization: `Bearer ${window.ogdynamicAdmin.apiKey}`,
  },
});

client.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      setApiKey("");
      try {
        await ogdApi.request<never>("connection", { method: "DELETE" });
      } catch {
      }
    }
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
