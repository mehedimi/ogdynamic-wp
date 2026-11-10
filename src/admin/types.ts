export type OGDCanvasOverrideField = {
  field: string;
  name: string;
  type: string;
  default: string | null;
};

export type OGDCanvasSummary = {
  id: string;
  user_id: number;
  name: string;
  width: number;
  height: number;
  status: string | null;
  image_url: string;
  created_at: string;
  updated_at: string;
};

export type OGDCanvas = OGDCanvasSummary & {
  background: unknown;
  items: unknown;
  override_fields: OGDCanvasOverrideField[];
};

export type PostTypeOption = {
  name: string;
  label: string;
  description?: string;
  /** Pill shown on the template list card (e.g. "WooCommerce"). */
  badge?: string;
};

export type RuntimeConfig = {
  restUrl: string;
  apiUrl: string;
  cdnUrl: string;
  nonce: string;
  apiKey: string;
};

export type ApiData<T> = {
  data: T;
};

/** Laravel cursor pagination response from the cloud API. */
export type CursorPage<T> = {
  data: T[];
  next_cursor?: string | null;
  /** API resources wrap the paginator, nesting the cursor inside `meta`. */
  meta?: { next_cursor?: string | null };
};

export interface User {
  id: number;
  name: string;
  email: string;
}

declare global {
  interface Window {
    ogdynamicAdmin: RuntimeConfig;
  }
}
