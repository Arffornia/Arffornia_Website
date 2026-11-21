import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';

// Standalone build for pages outside the website (in-game WebGUI screen, launcher): one IIFE and one CSS file.
export default defineConfig({
  publicDir: false,
  build: {
    outDir: fileURLToPath(new URL('./dist', import.meta.url)),
    emptyOutDir: true,
    lib: {
      entry: fileURLToPath(new URL('./src/index.ts', import.meta.url)),
      name: 'ArfforniaProgressionGraph',
      formats: ['iife'],
      fileName: () => 'progression-graph.js',
      cssFileName: 'progression-graph',
    },
  },
});
