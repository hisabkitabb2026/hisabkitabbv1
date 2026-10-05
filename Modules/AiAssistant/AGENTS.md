# AGENTS.md

This is the official InvoiceShelf AI Assistant module. Read the parent devenv `AGENTS.md` and the InvoiceShelf 3.x `AGENTS.md` before making changes.

- PHP 8.4, Laravel 13, PHPUnit 12, Vue 3, TypeScript, and Vite.
- Keep business queries behind `InvoiceShelf\\Modules\\Contracts\\Host` interfaces; module code must not import InvoiceShelf Eloquent models.
- Use the SDK AI driver ABI under `InvoiceShelf\\Modules\\Ai`.
- Keep the module optional: all UI must register through the host extension API and all backend routes must disappear when disabled.
- Preserve the existing `ai_conversations` and `ai_messages` schema and migration filename.
- Run `composer run lint`, `composer run test`, `pnpm run build`, and package validation before release.
