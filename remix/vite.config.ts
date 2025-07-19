import { vitePlugin as remix } from "@remix-run/dev";
import { installGlobals } from "@remix-run/node";
import { defineConfig } from "vite";
import tsconfigPaths from 'vite-tsconfig-paths';

installGlobals();

export default defineConfig({
    server: {
        port: 4000
    },
    plugins: [
        remix({
            appDirectory: "app",
            basename: "/app/",
        }),
        tsconfigPaths() // Add this line to enable path alias resolution
    ]
});
