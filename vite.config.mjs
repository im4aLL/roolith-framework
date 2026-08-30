import { existsSync } from "node:fs";
import path from "node:path";
import { defineConfig } from "vite";

const adminJsEntry = "source/js/admin/admin.js";
const adminScssEntry = "source/scss/admin/admin.scss";

const input = {
    app: "source/js/app.js",
    style: "source/scss/app.scss",
};

// Admin sources are installed by installer.zip, so they are optional
if (existsSync(adminJsEntry)) {
    input.admin = adminJsEntry;
}

if (existsSync(adminScssEntry)) {
    input.adminStyle = adminScssEntry;
}

export default defineConfig({
    plugins: [
        {
            name: "php-full-reload",
            configureServer(server) {
                server.watcher.on("change", (file) => {
                    if (file.endsWith(".php")) {
                        server.ws.send({ type: "full-reload" });
                    }
                });
            },
        },
    ],
    server: {
        port: 5173,
        // The PHP app on :8080 loads assets cross-origin when browsed directly
        cors: true,
        // Serve the PHP app through this server, except vite asset urls
        proxy: {
            "^/(?!@vite|@id|@fs|node_modules|source|__open-in-editor).*$": {
                target: "http://localhost:8080",
                changeOrigin: true,
            },
        },
        watch: {
            ignored: ["**/vendor/**", "**/node_modules/**"],
        },
    },
    css: {
        postcss: "./postcss.config.cjs",
    },
    build: {
        outDir: "assets",
        emptyOutDir: true,
        assetsDir: "",
        rollupOptions: {
            input,
            output: {
                entryFileNames: "js/[name].js",
                // Hashed names for dynamic chunks, they are not referenced by path
                chunkFileNames: "js/[name]-[hash].js",
                assetFileNames: (assetInfo) => {
                    const originalNames = assetInfo.originalFileNames ?? [];
                    const names = originalNames.length > 0 ? originalNames : (assetInfo.names ?? []);
                    const isCss = names.some((name) => /\.(css|scss|sass|less|styl|stylus|pcss|postcss)$/.test(name));

                    if (!isCss) {
                        return "media/[name].[ext]";
                    }

                    // Name the stylesheet after its source file, e.g. source/scss/app.scss -> css/app.css
                    const originalName = originalNames[0] ?? names[0] ?? "style.css";

                    return `css/${path.basename(originalName, path.extname(originalName))}.css`;
                },
            },
        },
    },
});
