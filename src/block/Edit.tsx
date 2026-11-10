import { createElement, Fragment, useEffect, useMemo, useState } from "@wordpress/element";
import { __ } from "@wordpress/i18n";
import { select } from "@wordpress/data";
import type { BlockEditProps } from "@wordpress/blocks";
import {
  InspectorControls,
  MediaUpload,
  useBlockProps,
} from "@wordpress/block-editor";
import {
  Button,
  Notice,
  PanelBody,
  Placeholder,
  SelectControl,
  Spinner,
  TextControl,
} from "@wordpress/components";
import {
  errorMessage,
  generateImage,
  loadCanvas,
  loadCanvases,
} from "./api";
import { buildQuery, cleanParams, renderUrl } from "./query";
import type {
  BlockAttributes,
  BlockParams,
  Canvas,
  CanvasField,
  CanvasSummary,
} from "./types";

const config = window.ogdynamicBlock ?? {
  cdnUrl: "",
  connected: false,
  settingsUrl: "",
};

const formatOptions: { label: string; value: string }[] = [
  { label: "PNG", value: "png" },
  { label: "WebP", value: "webp" },
  { label: "JPEG", value: "jpeg" },
];

function currentPostId(): number {
  const editor = select("core/editor") as
    | { getCurrentPostId?: () => number | string | null }
    | undefined;
  const id = Number(editor?.getCurrentPostId?.() ?? 0);

  return Number.isFinite(id) ? id : 0;
}

export default function Edit({
  attributes,
  setAttributes,
}: BlockEditProps<BlockAttributes>) {
  const {
    canvasId,
    params,
    alt,
    attachmentId,
    imageUrl,
    savedQuery,
    width,
    height,
    format,
  } = attributes;

  const blockProps = useBlockProps({ className: "ogd-block" });

  const [canvases, setCanvases] = useState<CanvasSummary[]>([]);
  const [listLoading, setListLoading] = useState(false);
  const [listError, setListError] = useState("");

  const [canvas, setCanvas] = useState<Canvas | null>(null);
  const [canvasError, setCanvasError] = useState("");

  // Field values being typed. They only reach the block attributes (and so
  // the preview URL) on blur, which avoids a re-render of the image per key.
  const [drafts, setDrafts] = useState<BlockParams>(params);

  const [loadedUrl, setLoadedUrl] = useState("");
  const [failedUrl, setFailedUrl] = useState("");

  const [saving, setSaving] = useState(false);
  const [saveError, setSaveError] = useState("");

  function fetchCanvases(force: boolean) {
    setListLoading(true);
    setListError("");

    loadCanvases(force)
      .then(setCanvases)
      .catch((error: unknown) =>
        setListError(
          errorMessage(error, __("Your canvases could not be loaded.", "ogdynamic")),
        ),
      )
      .finally(() => setListLoading(false));
  }

  useEffect(() => {
    if (config.connected) {
      fetchCanvases(false);
    }
  }, []);

  useEffect(() => {
    setCanvas(null);
    setCanvasError("");

    if (canvasId === "") {
      return;
    }

    let active = true;

    loadCanvas(canvasId)
      .then((loaded) => active && setCanvas(loaded))
      .catch((error: unknown) => {
        if (active) {
          setCanvasError(
            errorMessage(error, __("This canvas could not be loaded.", "ogdynamic")),
          );
        }
      });

    return () => {
      active = false;
    };
  }, [canvasId]);

  // Keep the inputs in sync when the attributes change from outside
  // (undo/redo, canvas switch).
  useEffect(() => {
    setDrafts(params);
  }, [params]);

  const previewUrl = useMemo(
    () => (canvasId === "" ? "" : renderUrl(config.cdnUrl, canvasId, params, format)),
    [canvasId, params, format],
  );

  const currentQuery = useMemo(() => buildQuery(params, format), [params, format]);
  const isSaved = attachmentId > 0 && savedQuery === currentQuery;
  const previewLoading = previewUrl !== "" && loadedUrl !== previewUrl && failedUrl !== previewUrl;
  const previewFailed = previewUrl !== "" && failedUrl === previewUrl;

  const canvasOptions = useMemo(() => {
    const options = [
      { label: __("Select a canvas", "ogdynamic"), value: "" },
      ...canvases.map((item) => ({
        label: `${item.name} (${item.width}×${item.height})`,
        value: item.id,
      })),
    ];

    // Keep a saved canvas selectable even if it left the public list.
    if (canvasId !== "" && !canvases.some((item) => item.id === canvasId)) {
      options.push({
        label: attributes.canvasName || canvasId,
        value: canvasId,
      });
    }

    return options;
  }, [canvases, canvasId, attributes.canvasName]);

  function selectCanvas(id: string) {
    const picked = canvases.find((item) => item.id === id);

    setAttributes({
      canvasId: id,
      canvasName: picked?.name ?? "",
      width: picked?.width ?? 0,
      height: picked?.height ?? 0,
      params: {},
      attachmentId: 0,
      imageUrl: "",
      savedQuery: "",
      savedAt: "",
    });
    setSaveError("");
  }

  function commit(next: BlockParams) {
    const cleaned = cleanParams(next);

    if (buildQuery(cleaned) !== currentQuery) {
      setAttributes({ params: cleaned });
    }
  }

  function setDraft(field: string, value: string) {
    setDrafts((current) => ({ ...current, [field]: value }));
  }

  function commitField(field: string, value: string) {
    const next = { ...drafts, [field]: value };

    setDrafts(next);
    commit(next);
  }

  async function save() {
    // Include anything still focused/typed so the saved image matches the inputs.
    const next = cleanParams(drafts);
    const nextQuery = buildQuery(next, format);

    setSaving(true);
    setSaveError("");

    try {
      const image = await generateImage({
        canvasId,
        params: next,
        postId: currentPostId(),
        alt,
        format,
      });

      setAttributes({
        params: next,
        attachmentId: image.attachment_id,
        imageUrl: image.url,
        width: image.width || attributes.width,
        height: image.height || attributes.height,
        savedQuery: nextQuery,
        savedAt: new Date().toISOString(),
      });
    } catch (error) {
      setSaveError(
        errorMessage(error, __("The image could not be saved.", "ogdynamic")),
      );
    } finally {
      setSaving(false);
    }
  }

  function renderField(field: CanvasField) {
    const value = drafts[field.field] ?? "";

    if (field.type === "image") {
      return (
        <div className="ogd-block__field" key={field.field}>
          <TextControl
            __nextHasNoMarginBottom
            label={field.name}
            type="url"
            value={value}
            placeholder={field.default || "https://"}
            help={__("Image URL", "ogdynamic")}
            onChange={(next: string) => setDraft(field.field, next)}
            onBlur={() => commitField(field.field, value)}
          />
          <MediaUpload
            allowedTypes={["image"]}
            onSelect={(media: { url?: string }) =>
              commitField(field.field, media.url ?? "")
            }
            render={({ open }: { open: () => void }) => (
              <Button variant="secondary" size="small" onClick={open}>
                {__("Choose from media library", "ogdynamic")}
              </Button>
            )}
          />
        </div>
      );
    }

    return (
      <div className="ogd-block__field" key={field.field}>
        <TextControl
          __nextHasNoMarginBottom
          label={field.name}
          value={value}
          placeholder={field.default}
          onChange={(next: string) => setDraft(field.field, next)}
          onBlur={() => commitField(field.field, value)}
        />
      </div>
    );
  }

  const notConnected = !config.connected;

  return (
    <Fragment>
      <InspectorControls>
        <PanelBody title={__("Canvas", "ogdynamic")} initialOpen>
          {notConnected ? (
            <Notice status="warning" isDismissible={false}>
              {__("ogdynamic is not connected.", "ogdynamic")}{" "}
              {config.settingsUrl !== "" && (
                <a href={config.settingsUrl}>{__("Connect now", "ogdynamic")}</a>
              )}
            </Notice>
          ) : (
            <Fragment>
              <SelectControl
                __nextHasNoMarginBottom
                label={__("Public canvas", "ogdynamic")}
                value={canvasId}
                options={canvasOptions}
                disabled={listLoading}
                onChange={selectCanvas}
              />
              <Button
                variant="tertiary"
                size="small"
                disabled={listLoading}
                onClick={() => {
                  fetchCanvases(true);

                  // Re-fetch the selected canvas too so changes made in the
                  // ogdynamic editor show up without switching canvases.
                  if (canvasId !== "") {
                    loadCanvas(canvasId, true)
                      .then(setCanvas)
                      .catch((error: unknown) =>
                        setCanvasError(
                          errorMessage(error, __("This canvas could not be loaded.", "ogdynamic")),
                        ),
                      );
                  }
                }}
              >
                {listLoading
                  ? __("Loading…", "ogdynamic")
                  : __("Refresh canvases", "ogdynamic")}
              </Button>
              {listError !== "" && (
                <Notice status="error" isDismissible={false}>
                  {listError}
                </Notice>
              )}
            </Fragment>
          )}
          {canvas !== null && canvas.status !== "public" && (
            <Notice status="warning" isDismissible={false}>
              {__("This canvas is not public, so it cannot render images.", "ogdynamic")}
            </Notice>
          )}
          {canvasError !== "" && (
            <Notice status="error" isDismissible={false}>
              {canvasError}
            </Notice>
          )}
        </PanelBody>

        {canvasId !== "" && (
          <PanelBody title={__("Dynamic fields", "ogdynamic")} initialOpen>
            {canvas === null && canvasError === "" && <Spinner />}
            {canvas !== null && canvas.override_fields.length === 0 && (
              <p>{__("This canvas has no dynamic fields.", "ogdynamic")}</p>
            )}
            {canvas?.override_fields.map(renderField)}
            {canvas !== null && canvas.override_fields.length > 0 && (
              <p className="ogd-block__hint">
                {__("The preview updates when you leave a field.", "ogdynamic")}
              </p>
            )}
          </PanelBody>
        )}

        {canvasId !== "" && (
          <PanelBody title={__("Image", "ogdynamic")} initialOpen>
            <SelectControl
              __nextHasNoMarginBottom
              label={__("Image format", "ogdynamic")}
              value={format}
              onChange={(next: string) => setAttributes({ format: next })}
              options={formatOptions}
            />
            <TextControl
              __nextHasNoMarginBottom
              label={__("Alt text", "ogdynamic")}
              value={alt}
              onChange={(next: string) => setAttributes({ alt: next })}
            />
            <div className="ogd-block__actions">
              <Button
                variant="primary"
                isBusy={saving}
                disabled={saving || isSaved || (canvas !== null && canvas.status !== "public")}
                onClick={save}
              >
                {saving
                  ? __("Saving…", "ogdynamic")
                  : isSaved
                    ? __("Saved to media library", "ogdynamic")
                    : __("Generate", "ogdynamic")}
              </Button>
            </div>
            {saveError !== "" && (
              <Notice status="error" isDismissible={false}>
                {saveError}
              </Notice>
            )}
            {attachmentId > 0 && imageUrl !== "" && (
              <p className="ogd-block__hint">
                <a href={imageUrl} target="_blank" rel="noreferrer">
                  {__("View saved image", "ogdynamic")}
                </a>
              </p>
            )}
          </PanelBody>
        )}
      </InspectorControls>

      <figure {...blockProps}>
        {canvasId === "" ? (
          <Placeholder
            icon="format-image"
            label={__("ogdynamic Canvas Image", "ogdynamic")}
            instructions={
              notConnected
                ? __("Connect ogdynamic to choose one of your canvases.", "ogdynamic")
                : __("Select a public canvas in the block settings, then fill in its fields.", "ogdynamic")
            }
          >
            {!notConnected && (
              <SelectControl
                __nextHasNoMarginBottom
                label={__("Public canvas", "ogdynamic")}
                value={canvasId}
                options={canvasOptions}
                disabled={listLoading}
                onChange={selectCanvas}
              />
            )}
            {listError !== "" && (
              <Notice status="error" isDismissible={false}>
                {listError}
              </Notice>
            )}
          </Placeholder>
        ) : (
          <div className="ogd-block__preview">
            <img
              key={previewUrl}
              src={previewUrl}
              alt={alt}
              width={width > 0 ? width : undefined}
              height={height > 0 ? height : undefined}
              onLoad={() => setLoadedUrl(previewUrl)}
              onError={() => setFailedUrl(previewUrl)}
            />
            {previewLoading && (
              <div className="ogd-block__overlay">
                <Spinner />
              </div>
            )}
            {previewFailed && (
              <div className="ogd-block__overlay ogd-block__overlay--error">
                {__("The preview could not be rendered. Make sure the canvas is public.", "ogdynamic")}
              </div>
            )}
            <span
              className={`ogd-block__badge${isSaved ? " ogd-block__badge--saved" : ""}`}
            >
              {isSaved
                ? __("Saved", "ogdynamic")
                : attachmentId > 0
                  ? __("Unsaved changes", "ogdynamic")
                  : __("Not saved yet", "ogdynamic")}
            </span>
          </div>
        )}
      </figure>
    </Fragment>
  );
}
