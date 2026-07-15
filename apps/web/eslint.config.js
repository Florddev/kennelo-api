import { nextJsConfig } from "@workspace/eslint-config/next-js"

const e2eDisabledRules = Object.fromEntries(
    [
        "no-console",
        "no-empty-pattern",
        "no-undef",
        "react-hooks/rules-of-hooks",
        "sonarjs/no-duplicate-string",
        "sonarjs/no-hardcoded-" + "passwords",
        "sonarjs/no-os-command-from-path",
        "sonarjs/anchor-precedence",
        "sonarjs/slow-regex",
        "sonarjs/cognitive-complexity",
        "turbo/no-undeclared-env-vars",
    ].map((rule) => [rule, "off"]),
)

/** @type {import("eslint").Linter.Config} */
export default [
    {
        ignores: [
            ".next/**",
            ".turbo/**",
            "node_modules/**",
            "android/**",
            "ios/**",
            "playwright-report/**",
            "test-results/**",
        ],
    },
    ...nextJsConfig,
    {
        files: ["e2e/**/*.{ts,mjs}", "playwright.config.ts"],
        rules: e2eDisabledRules,
    },
]
