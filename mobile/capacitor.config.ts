import type { CapacitorConfig } from "@capacitor/cli";

const config: CapacitorConfig = {
  appId: "com.projectpulse.app",
  appName: "ProjectPulse",
  webDir: "dist",
  server: {
    androidScheme: "https"
  }
};

export default config;

