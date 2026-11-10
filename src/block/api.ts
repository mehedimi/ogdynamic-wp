import apiFetch from "@wordpress/api-fetch";
import type {
  BlockParams,
  Canvas,
  CanvasSummary,
  GeneratedImage,
} from "./types";

type ApiData<T> = { data: T };

// The proxy passes through the cloud API's Laravel cursor pagination.
type CanvasPage = {
  data: CanvasSummary[];
  next_cursor: string | null;
};

// Safety cap so a misbehaving cursor can never loop unbounded.
const MAX_CANVAS_PAGES = 50;

// The canvas list and details rarely change while an editor session is
// open, so each is fetched once and shared by every block instance.
let canvasListRequest: Promise<CanvasSummary[]> | null = null;
const canvasRequests = new Map<string, Promise<Canvas>>();

export function loadCanvases(force = false): Promise<CanvasSummary[]> {
  if (force || !canvasListRequest) {
    canvasListRequest = fetchAllCanvasPages().catch((error: unknown) => {
      canvasListRequest = null;
      throw error;
    });
  }

  return canvasListRequest;
}

/**
 * Follows the cursor pages until the list is exhausted.
 *
 * The block picker is a native select with no scroll event, so pages are
 * loaded eagerly here instead of via infinite scroll.
 */
async function fetchAllCanvasPages(): Promise<CanvasSummary[]> {
  const all: CanvasSummary[] = [];
  let cursor: string | null = null;

  for (let page = 0; page < MAX_CANVAS_PAGES; page++) {
    // Explicit annotation: the loop's cursor narrowing would otherwise make
    // payload's inferred type reference itself (TS7022).
    const payload: CanvasPage = await apiFetch<CanvasPage>({
      path:
        "ogdynamic/v1/block/canvases" +
        (cursor ? `?cursor=${encodeURIComponent(cursor)}` : ""),
    });

    const seen = new Set(all.map((canvas) => canvas.id));

    if (Array.isArray(payload.data)) {
      all.push(...payload.data.filter((canvas) => !seen.has(canvas.id)));
    }

    cursor = payload.next_cursor;

    if (cursor === null) {
      break;
    }
  }

  return all;
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
  format: string;
}): Promise<GeneratedImage> {
  return apiFetch<ApiData<GeneratedImage>>({
    path: "ogdynamic/v1/block/generate",
    method: "POST",
    data: {
      canvas_id: input.canvasId,
      params: input.params,
      post_id: input.postId,
      alt: input.alt,
      format: input.format,
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
