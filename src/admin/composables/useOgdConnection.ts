import { useOgdApi } from "./useOgdApi";
import { setApiKey } from "../state/connection";

type ConnectionResponse = {
  data: {
    api_key: string;
    connected: boolean;
  };
};

type OAuthStartResponse = {
  data: {
    authorize_url: string;
  };
};

export function useOgdConnection() {
  const api = useOgdApi();

  async function startOAuth() {
    const payload = await api.request<OAuthStartResponse>("connection/oauth/start", {
      method: "POST",
    });

    window.location.assign(payload.data.authorize_url);
  }

  async function disconnect() {
    const payload = await api.request<ConnectionResponse>("connection", {
      method: "DELETE",
    });

    setApiKey(payload.data.api_key);
  }

  return {
    loading: api.loading,
    error: api.error,
    startOAuth,
    disconnect,
  };
}
