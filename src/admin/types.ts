export type OGDTemplate = {
  id?: string
  name?: string
  title?: string
  thumbnail?: string
  thumbnail_url?: string
  variables?: string[]
}

export type OGDSettings = {
  api_key: string
  connection: {
    status: string
    account_label: string
    plan: string
    usage: unknown
    last_checked_at: string
    last_error: string
  }
  templates: OGDTemplate[]
  template_updated: string
  defaults: {
    global_template: string
    post_templates: Record<string, string>
    product_template: string
    homepage_template: string
    archive_template: string
    fallback_image_url: string
    fallback_mode: string
    meta_mode: string
    seo_mode: string
    enabled_post_types: string[]
    editor_overrides: boolean
    cleanup_on_uninstall: boolean
  }
  mappings: Record<string, Record<string, FieldMapping>>
  woocommerce: {
    enabled: boolean
    variable_product_behavior: string
  }
}

export type FieldMapping = {
  source: string
  meta_key?: string
  static?: string
  fallback?: string
}

export type PostTypeOption = {
  name: string
  label: string
}

export type RuntimeConfig = {
  restUrl: string
  nonce: string
  apiKey: string
  postTypes: PostTypeOption[]
  seoPlugin: string
  ecoPlugins: string[]
}

declare global {
  interface Window {
    ogdynamicAdmin: RuntimeConfig
  }
}
