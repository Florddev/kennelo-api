import type { CapacitorConfig } from "@capacitor/cli";

const config: CapacitorConfig = {
    appId: "com.kennelo.app",
    appName: "Kennelo",
    webDir: "out",
    server: {
        androidScheme: "https",
    },
    plugins: {
        CapacitorHttp: {
            enabled: true,
        },
    },
};

export default config;
