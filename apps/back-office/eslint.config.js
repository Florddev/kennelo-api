import { nextJsConfig } from "@workspace/eslint-config/next-js"

const e2eDisabledRules = Object.fromEntries(
    [
        "sonarjs/no-duplicate-string",
        "sonarjs/no-hardcoded-" + "passwords",
        "turbo/no-undeclared-env-vars",
    ].map((rule) => [rule, "off"]),
)

/** @type {import("eslint").Linter.Config} */
export default [
    {
        ignores: [".next/**", ".turbo/**", "node_modules/**", "android/**", "ios/**"],
    },
    ...nextJsConfig,
    {
        files: ["e2e/**/*.ts", "playwright.config.ts"],
        rules: e2eDisabledRules,
    },
]
