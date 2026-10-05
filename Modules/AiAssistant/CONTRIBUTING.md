# Contributing to AI Assistant

Thank you for improving the official InvoiceShelf AI Assistant module. Keep it optional: routes and UI must disappear when disabled, business access must use `InvoiceShelf\Modules\Contracts\Host` interfaces, and providers must use the SDK's `InvoiceShelf\Modules\Ai` ABI rather than InvoiceShelf application classes.

## Local setup and checks

Use PHP 8.4, Node.js 24, and pnpm. Install dependencies, then run the same checks as CI:

```bash
composer install
pnpm install --frozen-lockfile

composer run lint
composer run test
vendor/bin/invoiceshelf-module validate-module module.json
vendor/bin/invoiceshelf-module validate-package .

pnpm run lint
pnpm exec tsc --noEmit
pnpm run build
git diff --exit-code -- dist
```

`pnpm run build` regenerates the compiled files in `dist/`. Commit those changes with the source change; the final `git diff` check makes sure the package can be installed without building assets on the target system.

## Pull requests

- Keep AI configuration and requests behind the module's host contracts; do not import InvoiceShelf Eloquent models.
- Preserve the `ai_conversations` and `ai_messages` schema and its migration filename unless a reviewed migration change is required.
- Keep AI tools read-only and company/user scoped.
- Include tests for behavior changes and run the checks above before requesting review.

## Releases

This repository releases through [the SDK's reusable workflow](https://github.com/InvoiceShelf/modules/blob/3.4.0/.github/workflows/module-release.yml). It is configured for the `stable` channel, so release only final SemVer versions.

1. Update the exact release version in `module.json`.
2. Build and commit any changed `dist/` files, run every check above, and merge the release change to `main`.
3. Create an unprefixed tag that exactly matches `module.json`—for example, `1.0.1`—and push it:

   ```bash
   git tag 1.0.1
   git push origin 1.0.1
   ```

The tag triggers `.github/workflows/release.yml`, which calls `InvoiceShelf/modules/.github/workflows/module-release.yml@3.4.0`. CI verifies that the tag and manifest version match, validates and packages the module, signs a deterministic release manifest in the protected `module-release` environment, and submits the package to the InvoiceShelf marketplace. Do not create tags with a `v` prefix or store marketplace/signing secrets in this repository.
