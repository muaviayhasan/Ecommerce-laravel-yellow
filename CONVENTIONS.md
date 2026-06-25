# Education ERP — Engineering Conventions

These are the agreed rules for building this project. They keep the system
consistent, secure, fast at scale, and admin-configurable. Follow them for every
new module, screen, and endpoint.

> Sections: [UI & Forms](#1-ui--forms) · [Data & Performance](#2-data--performance) ·
> [API](#3-api) · [Security & Access](#4-security--access) · [Finance](#5-finance) ·
> [Settings](#6-settings) · [Front-end Stack](#7-front-end-stack) ·
> [Building Admin Screens](#8-building-admin-screens) · [Testing & CI](#9-testing--ci)

---

## 1. UI & Forms

**1.1 Select2 on every `<select>`.** Searchable dropdowns are applied automatically
(opt out with `data-no-select2`). Inside a Livewire component, wrap selects in
`wire:ignore` and re-init with `window.ErpForms.init(el)` on `livewire:navigated` /
updates.

**1.2 Input masks.** Use `jquery-mask-plugin` via `data-mask`:
- CNIC → `data-mask="cnic"` → `00000-0000000-0` (e.g. `32301-0000000-0`).
- Phone → `data-mask="phone"` → `0000-0000000` (Pakistani mobile, `03` prefix).

**1.3 Max length everywhere.** Every text input and `<textarea>` has a `maxlength`
equal to its database column length, kept in sync with the FormRequest `max:` rule.

**1.4 File uploads use the drag-and-drop component** (`<x-file-drop>`), not a bare
`<input type="file">`.

**1.5 Clickable affordance.** Every clickable element (button, icon, link, toggle,
tab, dropdown trigger, clickable row) shows `cursor: pointer`. A global base rule in
`resources/css/app.css` covers native elements; add `cursor-pointer` to custom
clickable `<div>`/`<span>` elements. (Tailwind v4 defaults buttons to `default`.)

---

## 2. Data & Performance

**2.1 Index for scale.** The system must stay fast at 100k+ rows. Add a DB index on
**every** column used in a filter, sort, join, or lookup — not just foreign keys.
Add composite indexes for common multi-column filters.

**2.2 Optimized queries.** Always eager-load relations (no N+1), paginate every list
(never unbounded), and avoid unindexed `LIKE '%term%'` on large tables.

**2.3 Data integrity / type conventions.**
- Money → `decimal` (12,2 general; 15,2 accounting). Dates/booleans/json → model casts.
- Enums → stored as `string` + validated with `in:` (no MySQL `enum`).
- A text column's DB length, its Blade `maxlength`, and the FormRequest `max:` must match.

---

## 3. API

**3.1 List endpoints** paginate (default 15, max 100) and eager-load what they expose.
Reuse `HandlesResourceQuery` (allow-listed filters/sorts, `?with=`, `per_page`).

**3.2 Writes** always go through a **FormRequest** — persist only `$request->validated()`,
**never** `$request->all()`.

**3.3 Output** always goes through an **API Resource** with the standard envelope
(`success` / `message` / `data`; lists add `links` / `meta`).

---

## 4. Security & Access

**4.1 RBAC, fail-closed.** Every action is guarded by a `{resource}.{action}`
permission (`view`/`create`/`edit`/`delete`). API uses the `EnsureApiPermission`
middleware; web controllers use `can:{resource}.{action}` (via `HasMiddleware`).
`super-admin` bypasses via `Gate::before`. Authorization runs **before** route-model
binding (no 404 existence leaks).

**4.2 Permissions on module completion.** The moment a module is finished, add its
`{resource}.{action}` permissions to `RolePermissionSeeder::$groups`, grant them to
the right roles, and re-seed. Permission name = the resource URI (plural) + verb,
e.g. `campuses.view`, `academic-years.edit`. Custom actions map in
`EnsureApiPermission::ACTION_MAP`.

**4.3 Secrets.** Encrypt at rest (integration credentials, `two_factor_secret`),
hash passwords. **Mask** secrets in API/UI output (`********`), hide them from
serialization, and **never** write them to logs or the audit trail. New secret
columns use an `encrypted` cast on a `text` column (ciphertext isn't JSON).

**4.4 Audit logging.** Successful mutating requests are recorded to `activity_logs`
(API: `AuditApiActions`; web: `AuditWebActions`) with a **sanitized** payload —
secrets redacted.

---

## 5. Finance

**5.1 Ledger is the source of truth.** Every financial operation (fee payment,
expense, income, salary, refund, fine, scholarship) runs in the **service layer**,
inside a **DB transaction**, and **posts to the ledger** via `LedgerService`.

**5.2 Reuse existing services.** Domain logic lives under `app/Services/`
(`Fees/FeePaymentService`, `Finance/LedgerService`, `Academics/GpaService`,
`Attendance/…`). Controllers call the service; they never touch the ledger directly.
Check `app/Services/` before writing new financial/academic logic.

---

## 6. Settings

**6.1 Never hardcode admin-configurable values.** Read them via the global helpers
(`app/Support/helpers.php`):
- Money → `format_money($amount)` (currency symbol/position/decimals/separators).
- Dates/times → `format_date()`, `format_time()`, `format_datetime()` (format + timezone).
- Pagination → `per_page()` for every web `->paginate(...)`.
- Anything else → `setting($group, $key, $default)`.

Never hardcode currency, date/time format, timezone, locale, pagination size, or theme.

**6.2 Settings architecture.** `/settings/*` pages live in `SettingsController` (one
`show`/`update` pair per page), guarded by `settings.view`/`settings.edit`, built from
`<x-settings.*>` components. Storage is the key-value `settings` table via the `Setting`
model (`groupWithDefaults` / `putGroup`); **Academic** uses the columned
`AcademicSetting` table. Secret fields use type `encrypted` + keep-on-blank.

**6.3 New configurable values.** When a module introduces a default/toggle/format,
add it as a Setting (correct group + default in `SettingsController` + a field on the
matching page) and, if it affects runtime, wire it in `App\Support\SettingsApplier`.

---

## 7. Front-end Stack

- **Blade + Livewire 4** for interactive screens (live tables, inline edit, modals).
  Plain Blade + Alpine is fine for static/simple pages.
- **Alpine is provided by Livewire** (`@livewireScripts`). Do **not** import/start a
  second Alpine in `app.js`. The collapse plugin (sidebar) is bundled by Livewire.
- **jQuery + Select2 + jquery-mask** are initialized by `resources/js/erp-forms.js`
  (`window.ErpForms.init`). Inside Livewire, use `wire:ignore` + re-init on updates.
- **Vite + Tailwind v4**: design tokens live in `resources/css/app.css` `@theme`.
  Build with `npm run build` (or `npm run dev`). `public/build/` is git-ignored.
- Use the **design-token classes** (`primary`, `tertiary`, `surface-container-*`,
  `on-surface-variant`, `outline-variant`, …). Tokens from mockups that aren't defined
  (`*-fixed-variant`) must be substituted.
- **Three themes, one token set.** The storefront is theme #1 (yellow, `:root`). The
  admin is theme #2 **light** + #3 **dark**, scoped to `.admin-scope` (set on the admin
  `<html>`) with `.admin-scope.dark` overriding the same CSS variables. Dark mode is
  **class-based** (`@custom-variant dark`), toggled in the header and persisted to
  `localStorage` (applied pre-paint to avoid a flash). Palettes are the **official**
  `public/stich/admin/{light,dark}/DESIGN_*.md` frontmatter ("Core Admin" / "Midnight
  Executive") — copy those hexes verbatim; don't invent values.
- **Material dark elevation caveat.** In the dark ramp `surface-container-lowest` is the
  *darkest* surface (opposite of light). So chrome that uses `surface-container-lowest`
  in light must elevate in dark via a `dark:` variant (e.g. cards =
  `bg-surface-container-lowest dark:bg-surface-container`). The shared `x-admin.*`
  components already encode this — reuse them and new screens get dark for free.

---

## 8. Building Admin Screens

The ERP is **API-first** — for most screens the data layer already exists (models,
migrations, FormRequests, API controllers). Building a screen = **web UI layer**:
controller + Blade views + routes + nav, reusing the existing model/FormRequest.

- **Designs** live at `public/stich/<slug>/code.html` + `screen.png` (read the PNG for
  layout, the HTML for class details).
- **Web controllers** go in `app/Http/Controllers/Admin/`, implement `HasMiddleware`,
  guard each action with `can:{resource}.{action}` (permissions already seeded).
- **Routes:** `Route::resource(...)->except('show')` inside the `auth` group in
  `routes/web.php`. Define non-resource and distinct-prefix routes before a resource
  that could swallow them.
- **Views** extend `layouts.admin` (sidebar/header/footer/flash provided) and emit only
  `@section('content')`. Index = stats cards + GET filter form + table + paginator;
  full-page create/edit with a shared `_form.blade.php`.
- **Reusable components:** `x-settings.{page,section,field,input,select,toggle,textarea,secret}`
  (work for any form), `x-crud.form-page` (create/edit wrapper), `x-file-drop`.
  Toggles submit absent-when-off → resolve with `$request->boolean()`. Pivots
  (`campuses[]`) → add the rule to the shared FormRequest, `Arr::except` before create,
  `->sync()` after.
- Always use the settings helpers (§6) and wire the screen into `config/navigation.php`.
- **Every admin screen ships in both light and dark mode** (§7). Build with the design
  tokens / `x-admin.*` components so both themes work, and verify the screen in **both**
  before calling it done — never hardcode a hex that only reads in one mode.

**Recurring gotcha:** a `date` cast stores `Y-m-d 00:00:00`, so `updateOrCreate` /
`where('date','Y-m-d')` won't match — use `whereDate(...)` + explicit
`firstOrNew`/`fill`/`save` for idempotent upserts.

---

## 9. Testing & CI

Every completed module/workflow ships **feature tests** (`tests/Feature/`), and the CI
suite (`php artisan test`, GitHub Actions) stays green before merge. Authenticate in
tests via `$this->seedRbac()` + `admin@erp.test`; for Livewire use `Livewire::test()`.

---

_This document mirrors the project's working rules. Keep it updated when a convention
changes._
