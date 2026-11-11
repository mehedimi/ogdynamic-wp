import { defineConfig } from "vite";

// The block runs inside the WordPress editor, which already ships React and
// the @wordpress/* packages as globals. They are externalised and the bundle
// is emitted as a classic IIFE script (WordPress 6.3 has no script modules).
const globals: Record<string, string> = {
  "@wordpress/api-fetch": "wp.apiFetch",
  "@wordpress/block-editor": "wp.blockEditor",
  "@wordpress/blocks": "wp.blocks",
  "@wordpress/components": "wp.components",
  "@wordpress/data": "wp.data",
  "@wordpress/element": "wp.element",
  "@wordpress/i18n": "wp.i18n",
};

export default defineConfig({
  oxc: {
    jsx: {
      runtime: "classic",
      pragma: "createElement",
      pragmaFrag: "Fragment",
    },
  },
  define: {
    "process.env.NODE_ENV": JSON.stringify("production"),
  },
  build: {
    outDir: "dist/block",
    emptyOutDir: true,
    lib: {
      entry: "src/block/index.tsx",
      name: "ogdynamicBlock",
      formats: ["iife"],
      fileName: () => "ogdynamic-block.js",
      cssFileName: "ogdynamic-block",
    },
    rolldownOptions: {
      external: Object.keys(globals),
      output: {
        globals,
      },
    },
  },
});
