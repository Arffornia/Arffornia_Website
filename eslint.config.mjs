import jestPlugin from "eslint-plugin-jest";
import globals from "globals";
import prettierPlugin from "eslint-plugin-prettier";
import eslintComments from "eslint-plugin-eslint-comments";
import js from "@eslint/js";

const cleanGlobals = (obj) =>
    Object.fromEntries(
        Object.entries(obj).map(([ key, val ]) => [ key.trim(), val ]),
    );

export default [
    js.configs.recommended,
    {
        files: [ "**/*.js", "**/*.mjs" ],
        languageOptions: {
            sourceType: "module",
            globals: {
                AudioWorkletGlobalScope: "readonly",
                ...cleanGlobals(globals.node),
                ...cleanGlobals(jestPlugin.environments.globals.globals),
                ...cleanGlobals(globals.browser),
            },
        },
        plugins: {
            prettier: prettierPlugin,
            jest: jestPlugin,
            "eslint-comments": eslintComments,
        },
        rules: {
            "eslint-comments/no-use": [ "error", { allow: [] } ],
            curly: [ "error", "all" ],
            "brace-style": [ "error", "1tbs" ],
            "max-statements-per-line": [ "error", { max: 1 } ],
            semi: [ "error", "always" ],
            "prefer-const": "error",
            "no-undef": "error",
            "no-unused-vars": [
                "error",
                { argsIgnorePattern: "^_", varsIgnorePattern: "^_" },
            ],
            "max-len": [
                "error",
                {
                    code: 80,
                    tabWidth: 4,
                    ignoreComments: true,
                    ignoreStrings: true,
                    ignoreTemplateLiterals: true,
                    ignoreRegExpLiterals: true,
                },
            ],
            "prettier/prettier": [ "error" ],
            "padding-line-between-statements": [
                "error",
                {
                    blankLine: "always",
                    prev: [ "const", "let", "var", "if", "for", "while", "do" ],
                    next: "*",
                },
                {
                    blankLine: "any",
                    prev: [ "const", "let", "var" ],
                    next: [ "const", "let", "var" ],
                },
            ],
        },
    },
];
