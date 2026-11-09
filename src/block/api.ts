import apiFetch from "@wordpress/api-fetch";
import type {
  BlockParams,
  Canvas,
  CanvasSummary,
  GeneratedImage,
} from "./types";

type ApiData<T> = { data: T };

// The canvas list and details rarely change while an editor session is
// open, so each is fetched once and shared by every block instance.
let canvasListRequest: Promise<CanvasSummary[]> | null = null;
const canvasRequests = new Map<string, Promise<Canvas>>();

export function loadCanvases(force = false): Promise<CanvasSummary[]> {
  if (force || !canvasListRequest) {
    canvasListRequest = apiFetch<ApiData<CanvasSummary[]>>({
      path: "ogdynamic/v1/block/canvases",
    })
      .then((payload) => (Array.isArray(payload.data) ? payload.data : []))
      .catch((error: unknown) => {
        canvasListRequest = null;
        throw error;
      });
  }

  return canvasListRequest;
}

export function loadCanvas(id: string, force = false): Promise<Canvas> {
  let request = canvasRequests.get(id);

  if (force || !request) {
    request = apiFetch<ApiData<Canvas>>({
      path: `ogdynamic/v1/block/canvases/${encodeURIComponent(id)}`,
    })
      .then((payload) => payload.data)
      .catch((error: unknown) => {
        canvasRequests.delete(id);
        throw error;
      });
    canvasRequests.set(id, request);
  }

  return request;
}

export function generateImage(input: {
  canvasId: string;
  params: BlockParams;
  postId: number;
  alt: string;
}): Promise<GeneratedImage> {
  return apiFetch<ApiData<GeneratedImage>>({
    path: "ogdynamic/v1/block/generate",
    method: "POST",
    data: {
      canvas_id: input.canvasId,
      params: input.params,
      post_id: input.postId,
      alt: input.alt,
    },
  }).then((payload) => payload.data);
}

export function errorMessage(error: unknown, fallback: string): string {
  if (error && typeof error === "object" && "message" in error) {
    const message = (error as { message?: unknown }).message;

    if (typeof message === "string" && message !== "") {
      return message;
    }
  }

  return fallback;
}
