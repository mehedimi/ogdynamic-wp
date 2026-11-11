import { createBlock, registerBlockType, type BlockConfiguration } from "@wordpress/blocks";
import Edit from "./Edit";
import metadata from "../../blocks/canvas-image/block.json";
import type { BlockAttributes } from "./types";
import "./editor.css";

const { name, ...settings } = metadata;

registerBlockType<BlockAttributes>(name, {
  ...(settings as unknown as BlockConfiguration<BlockAttributes>),
  edit: Edit,
  // Rendered on the server (Block::render) from the saved media image.
  save: () => null,
  // A saved image can be handed off to the core image block, which brings
  // the full image toolset (sizing, crop, caption, link, lightbox).
  transforms: {
    to: [
      {
        type: "block",
        blocks: ["core/image"],
        // Only blocks with an image saved to the media library can convert.
        isMatch: (attributes) => (attributes as BlockAttributes).attachmentId > 0,
        transform: (attributes) => {
          const { attachmentId, imageUrl, alt } = attributes as BlockAttributes;
          return createBlock("core/image", {
            id: attachmentId,
            url: imageUrl,
            alt,
          });
        },
      },
    ],
  },
});
