export type BlockParams = Record<string, string>;

export type BlockAttributes = {
  canvasId: string;
  canvasName: string;
  params: BlockParams;
  width: number;
  height: number;
  format: string;
  alt: string;
  attachmentId: number;
  imageUrl: string;
  savedQuery: string;
  savedAt: string;
};

export type CanvasSummary = {
  id: string;
  name: string;
  width: number;
  height: number;
  status: string;
  image_url: string;
};

export type CanvasField = {
  field: string;
  name: string;
  type: string;
  default: string;
};

export type Canvas = CanvasSummary & {
  override_fields: CanvasField[];
};

export type GeneratedImage = {
  attachment_id: number;
  url: string;
  width: number;
  height: number;
  reused: boolean;
};

export type BlockRuntimeConfig = {
  cdnUrl: string;
  connected: boolean;
  settingsUrl: string;
};

declare global {
  interface Window {
    ogdynamicBlock?: BlockRuntimeConfig;
  }
}
