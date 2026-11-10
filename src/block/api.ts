import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";
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

// The canvas list is shared by every block instance in the editor and
// paged lazily: page one loads up front, further pages load when the
// picker's suggestion list is scrolled near its end.
type CanvasListState = {
  canvases: CanvasSummary[];
  nextCursor: string | null;
  loading: boolean;
  loadingMore: boolean;
  error: string | null;
};

let canvasListState: CanvasListState = {
  canvases: [],
  nextCursor: null,
  loading: false,
  loadingMore: false,
  error: null,
};

const canvasListListeners = new Set<() => void>();

// Bumped on every forced refresh so in-flight page requests can tell
// they have been superseded.
let canvasListEpoch = 0;

function setCanvasListState(patch: Partial<CanvasListState>): void {
  canvasListState = { ...canvasListState, ...patch };
  canvasListListeners.forEach((listener) => listener());
}

export function subscribeToCanvasList(listener: () => void): () => void {
  canvasListListeners.add(listener);
  return () => canvasListListeners.delete(listener);
}

export function getCanvasListSnapshot(): CanvasListState {
  return canvasListState;
}

function fetchCanvasPage(cursor: string | null, epoch: number): Promise<void> {
  return apiFetch<CanvasPage>({
    path:
      "ogdynamic/v1/block/canvases" +
      (cursor ? `?cursor=${encodeURIComponent(cursor)}` : ""),
  }).then((payload) => {
    if (epoch !== canvasListEpoch) {
      return;
    }

    const items = Array.isArray(payload.data) ? payload.data : [];
    const seen = new Set(canvasListState.canvases.map((canvas) => canvas.id));

    setCanvasListState({
      canvases: [
        ...canvasListState.canvases,
        ...items.filter((canvas) => !seen.has(canvas.id)),
      ],
      // An empty page means the cursor is unreliable — stop paging.
      nextCursor: items.length > 0 ? payload.next_cursor : null,
    });
  });
}

/** Fetches page one of the canvas list (no-op when already loaded). */
export function fetchCanvases(force = false): void {
  if (canvasListState.loading) {
    return;
  }

  if (
    !force &&
    (canvasListState.canvases.length > 0 || canvasListState.error !== null)
  ) {
    return;
  }

  canvasListEpoch += 1;
  const epoch = canvasListEpoch;

  setCanvasListState({
    canvases: [],
    nextCursor: null,
    loading: true,
    error: null,
  });

  fetchCanvasPage(null, epoch)
    .catch((error: unknown) => {
      if (epoch === canvasListEpoch) {
        setCanvasListState({
          error: errorMessage(
            error,
            __("Your canvases could not be loaded.", "ogdynamic"),
          ),
        });
      }
    })
    .finally(() => {
      if (epoch === canvasListEpoch) {
        setCanvasListState({ loading: false });
      }
    });
}

/** Appends the next cursor page; returns false while loading or exhausted. */
export function loadMoreCanvases(): boolean {
  if (
    canvasListState.loading ||
    canvasListState.loadingMore ||
    canvasListState.nextCursor === null
  ) {
    return false;
  }

  const epoch = canvasListEpoch;
  const cursor = canvasListState.nextCursor;

  setCanvasListState({ loadingMore: true });

  fetchCanvasPage(cursor, epoch)
    .catch(() => {
      // Keep the loaded list and cursor; scrolling retries the page.
    })
    .finally(() => {
      if (epoch === canvasListEpoch) {
        setCanvasListState({ loadingMore: false });
      }
    });

  return true;
}

// Canvas details rarely change while an editor session is open, so each
// is fetched once and shared by every block instance.
const canvasRequests = new Map<string, Promise<Canvas>>();

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
