import { defineConfig } from 'vite'
import { readFileSync } from 'node:fs'
import { resolve } from 'path'

const moduleManifest = JSON.parse(
  readFileSync(new URL('./module.json', import.meta.url), 'utf8')
)

export default defineConfig({
  define: {
    __MODULE_VERSION__: JSON.stringify(moduleManifest.version),
  },
  build: {
    outDir: resolve(import.meta.dirname, 'dist'),
    emptyOutDir: true,
    lib: {
      entry: resolve(import.meta.dirname, 'resources/js/init.ts'),
      formats: ['es'],
      fileName: () => 'init.js',
    },
  },
})
