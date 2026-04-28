import js from "@eslint/js"
import pluginNext from "@next/eslint-plugin-next"
import eslintConfigPrettier from "eslint-config-prettier"
import pluginReact from "eslint-plugin-react"
import pluginReactHooks from "eslint-plugin-react-hooks"
import pluginSonarjs from "eslint-plugin-sonarjs"
import globals from "globals"
import tseslint from "typescript-eslint"

import { config as baseConfig } from "./base.js"

/**
 * A custom ESLint configuration for libraries that use Next.js.
 *
 * @type {import("eslint").Linter.Config}
 * */
export const nextJsConfig = [
    ...baseConfig,
    js.configs.recommended,
    eslintConfigPrettier,
    ...tseslint.configs.recommended,
    {
        ...pluginReact.configs.flat.recommended,
        languageOptions: {
            ...pluginReact.configs.flat.recommended.languageOptions,
            globals: {
                ...globals.serviceworker,
            },
        },
    },
    {
        plugins: {
            "@next/next": pluginNext,
        },
        rules: {
            ...pluginNext.configs.recommended.rules,
            ...pluginNext.configs["core-web-vitals"].rules,
        },
    },
    {
        plugins: {
            "react-hooks": pluginReactHooks,
        },
        settings: { react: { version: "detect" } },
        rules: {
            ...pluginReactHooks.configs.recommended.rules,
            "react/react-in-jsx-scope": "off",
            "react/prop-types": "off",
        },
    },
    {
        plugins: {
            sonarjs: pluginSonarjs,
        },
        rules: {
            ...pluginSonarjs.configs.recommended.rules,
            "sonarjs/cognitive-complexity": ["error", 15],
            "sonarjs/no-duplicate-string": ["error", { threshold: 3 }],
        },
    },
    {
        ignores: [
            ".next/**",
            ".turbo/**",
            "out/**",
            "build/**",
            "next-env.d.ts",
            "node_modules/**",
            "dist/**",
        ],
    },
    {
        rules: {
            "no-console": "warn",
            "no-unused-vars": "off",
            "@typescript-eslint/no-unused-vars": [
                "error",
                {
                    argsIgnorePattern: "^_",
                    varsIgnorePattern: "^_",
                    caughtErrorsIgnorePattern: "^_",
                },
            ],
            "eqeqeq": ["error", "always"],
            "prefer-const": "error",
            "prefer-template": "error",
            "no-else-return": "error",
            "no-var": "error",
            "object-shorthand": "error",
            "quote-props": ["error", "as-needed"],
            "prefer-arrow-callback": "error",
            "arrow-body-style": ["error", "as-needed"],
            "no-nested-ternary": "warn",
            "max-depth": ["warn", 3],
            "complexity": ["warn", 15],
        },
    },
]
