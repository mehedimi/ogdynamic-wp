import { registerBlockType, type BlockConfiguration } from "@wordpress/blocks";
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
});
