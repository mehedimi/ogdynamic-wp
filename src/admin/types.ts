export type OGDColor = [number, number, number, number];
export type OGDSpacing = [number, number, number, number];

export type OGDDesignSpecItem = {
  label: string;
  value: string;
};

export type OGDDesignTextStyle = {
  font?: string;
  size?: number;
  color?: OGDColor;
  weight?: number;
  letterSpacing?: number;
  lineHeight?: number;
  display?: string;
  margin?: OGDSpacing;
  padding?: OGDSpacing;
  borderWidth?: number;
  borderRadius?: number;
  bgColor?: OGDColor;
  objectFit?: string;
  width?: number;
  height?: number;
};

export type OGDDesignSpecStyle = {
  item?: OGDDesignTextStyle;
  label?: OGDDesignTextStyle;
  value?: OGDDesignTextStyle;
};

export type OGDDesignStyle = {
  bgColor?: OGDColor;
  accentColor?: OGDColor;
  title?: OGDDesignTextStyle;
  description?: OGDDesignTextStyle;
  category?: OGDDesignTextStyle;
  readTime?: OGDDesignTextStyle;
  cta?: OGDDesignTextStyle;
  price?: OGDDesignTextStyle;
  priceLabel?: OGDDesignTextStyle;
  storeName?: OGDDesignTextStyle;
  logoUrl?: OGDDesignTextStyle;
  productImageUrl?: OGDDesignTextStyle;
  specs?: OGDDesignSpecStyle;
  [key: string]: OGDColor | OGDDesignTextStyle | OGDDesignSpecStyle | undefined;
};

export type OGDDesignSchemaField = {
  key: string;
  type: string;
  label: string;
  rules: string[];
  allowOverride: boolean;
};

export type OGDDesignSchemaGroup = {
  label: string;
  fields: OGDDesignSchemaField[];
};

export type OGDDesignTemplate = {
  id: string;
  ident_name: string;
  name: string;
  description: string;
  category: string;
  schema: OGDDesignSchemaGroup[];
  content: Record<string, string | OGDDesignSpecItem[] | undefined>;
  style: OGDDesignStyle;
  created_at: string;
  updated_at: string;
};

export type OGDDesign = {
  id: string;
  user_id: number;
  ident_name: string;
  name: string;
  title?: string;
  content: Record<string, string | OGDDesignSpecItem[] | undefined>;
  style: OGDDesignStyle;
  config: {
    format: string;
  };
  is_active: boolean;
  created_at: string;
  updated_at: string;
  template: OGDDesignTemplate;
  fields?: unknown[];
  variables?: unknown[];
  editable_fields?: unknown[];
  available_fields?: unknown[];
};

export type PostTypeOption = {
  name: string;
  label: string;
  description?: string;
};

export type RuntimeConfig = {
  restUrl: string;
  apiUrl: string;
  nonce: string;
  apiKey: string;
  seoPlugin: string;
  ecoPlugins: string[];
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
