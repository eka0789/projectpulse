import react from "@vitejs/plugin-react";
import { defineConfig } from "vite";

export default defineConfig({
  plugins: [react()],
  build: {
    // Ionic's optimized runtime is intentionally bundled as the shared app shell.
    // Route screens remain lazy chunks; the shell is ~300 kB gzip.
    chunkSizeWarningLimit: 1400,
  },
  server: {
    host: true,
    port: 5173,
  },
});
