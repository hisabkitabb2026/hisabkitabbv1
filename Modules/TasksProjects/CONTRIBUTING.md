# Contributing to Tasks and Projects

Thank you for improving the official InvoiceShelf Tasks and Projects module. Keep it optional:
routes and UI must disappear when disabled, and business access must use
`InvoiceShelf\Modules\Contracts\Host` interfaces rather than InvoiceShelf application classes.

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

`pnpm run build` regenerates the compiled files in `dist/`. Commit those changes with the source
change; the final `git diff` check makes sure the package can be installed without building assets
on the target system.

## Pull requests

- Keep host data behind the module's `Contracts\Host` interfaces; do not import InvoiceShelf
  Eloquent models.
- Every module table is prefixed `tp_` and carries a `company_id` column; scope every query to the
  company from the `company` header, never from a request parameter.
- Money is stored and compared as integer minor units, matching the host's `invoices.total`
  convention.
- Migrations are reversible: one concrete class per file, a non-empty `up()` and `down()`, and no
  `drop*`, `rename*`, `raw`, or `statement` calls in `up()`.
- Include tests for behavior changes and run the checks above before requesting review.

## Releases

This repository releases through
[the SDK's reusable workflow](https://github.com/InvoiceShelf/modules/blob/3.4.0/.github/workflows/module-release.yml).
It is configured for the `stable` channel, so release only final SemVer versions.

1. Update the exact release version in `module.json`.
2. Build and commit any changed `dist/` files, run every check above, and merge the release change
   to `main`.
3. Create an unprefixed tag that exactly matches `module.json`, for example, `0.1.0`, and push it:

   ```bash
   git tag 0.1.0
   git push origin 0.1.0
   ```

The tag triggers `.github/workflows/release.yml`, which calls
`InvoiceShelf/modules/.github/workflows/module-release.yml@3.4.0`. CI verifies that the tag and
manifest version match, validates and packages the module, signs a deterministic release manifest in
the protected `module-release` environment, and submits the package to the InvoiceShelf marketplace.
Do not create tags with a `v` prefix or store marketplace/signing secrets in this repository.
