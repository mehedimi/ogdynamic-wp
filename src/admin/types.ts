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
