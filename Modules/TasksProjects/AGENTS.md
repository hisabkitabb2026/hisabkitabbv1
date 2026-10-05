# AGENTS.md

This is the official InvoiceShelf Tasks and Projects module. Read the parent devenv `AGENTS.md` and
the InvoiceShelf 3.x `AGENTS.md` before making changes.

- PHP 8.4, Laravel 13, PHPUnit 12, Vue 3, TypeScript, and Vite.
- Keep business queries behind `InvoiceShelf\\Modules\\Contracts\\Host` interfaces; module code must
  not import InvoiceShelf Eloquent models.
- Keep the module optional: all UI must register through the host extension API and all backend
  routes must disappear when disabled.
- Every module table is prefixed `tp_`, and every table carries a `company_id` column. Scope every
  query to the company from the `company` header, never from a request parameter.
- Abilities are namespaced `tasks-projects:` by the SDK: register them with
  `Registry::registerAbility()` and build ids with `Registry::abilityId()`. The bare names live in
  `app/Support/Abilities.php`; dependencies on host abilities stay un-namespaced.
- Money is stored and compared as integer minor units, matching the host's `invoices.total`
  convention. Rates are minor units per hour.
- Migrations are reversible: one concrete class per file, a non-empty `up()` and `down()`, and no
  `drop*`, `rename*`, `raw`, or `statement` calls in `up()`.
- Run `composer run lint`, `composer run test`, `pnpm run build`, and package validation before
  release.
- `composer.json` takes `invoiceshelf/modules` `^3.4` from the SDK's GitHub repository through a
  plain `vcs` entry.
- The module must run on every host its `module.json` compatibility range admits, so it uses only
  Base* components and props that exist on the oldest of them. A newer prop is allowed only when
  an older host that ignores it still renders correctly: the empty placeholder's `ghost` rows
  (host 3.0.0-alpha.4) show on newer hosts and are simply absent on older ones. Name icon-only
  controls with `aria-label`, and write layout classes in their logical form (`ms-`, `pe-`,
  `start-`, `text-end`) so right-to-left languages mirror; `pnpm run lint` checks accessibility.
