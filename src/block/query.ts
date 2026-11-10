import type { BlockParams } from "./types";

/** Drops empty values so canvas defaults apply, and sorts keys for stable URLs. */
export function cleanParams(params: BlockParams): BlockParams {
  const clean: BlockParams = {};

  Object.keys(params)
    .sort()
    .forEach((key) => {
      const value = (params[key] ?? "").trim();

      if (value !== "") {
        clean[key] = value;
      }
    });

  return clean;
}

export function buildQuery(params: BlockParams, format = ""): string {
  const search = new URLSearchParams();
  const clean = cleanParams(params);

  Object.keys(clean).forEach((key) => search.append(key, clean[key]));

  // PNG is the render service default, so config[format] is only sent when
  // the format differs — keeps saved queries stable for default blocks.
  if (format !== "" && format !== "png") {
    search.append("config[format]", format);
  }

  return search.toString();
}

export function renderUrl(
  cdnUrl: string,
  canvasId: string,
  params: BlockParams,
  format = "",
): string {
  const query = buildQuery(params, format);
  const base = `${cdnUrl.replace(/\/?$/, "/")}c/${encodeURIComponent(canvasId)}`;

  return query === "" ? base : `${base}?${query}`;
}
