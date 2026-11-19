# MitekStore — project instructions

## Project overview

B2B e-commerce site for **Mitek.ma** (STE MITEK SARL AU, Casablanca, Morocco): office supplies, toner and consumables, IT equipment, furniture, hygiene products, plus IT services (maintenance, network/Wi-Fi, cybersecurity, cloud, data recovery, custom PCs, audits, email setup).
**`Mitek.md` (project root) is the source of truth for all site copy, categories, FAQ, payment and delivery rules.** Never invent business facts; if something is missing, leave a visible `TODO` placeholder. The logo is `logo-mitek.png` (copy in `public/images/`).

## Tech stack

Installed and in use:
- Laravel 13.17, PHP ^8.3 (runs on 8.4), MySQL (tests use in-memory SQLite), Pest 5, Pint, Laravel Boost
- Storefront: Blade + Alpine.js 3 + Tailwind CSS 4 (Vite 8, theme in `resources/css/app.css`, brand colour = logo navy)
- Admin (`/admin`, `routes/admin.php`): **hand-built Vue 3 + Inertia 3** with Ziggy and Tailwind. Pages in `resources/js/admin/Pages`, shared components in `resources/js/admin/Components`. Admins sign in through the normal storefront login.
- spatie/laravel-permission (roles `admin`, `customer`), laravel-settings (`App\Settings\*`), laravel-medialibrary, laravel-sitemap, laravel-sluggable, laravel-honeypot, laravel-phone, resend, mpdf/mpdf (invoices)

Deliberately NOT used (decided with the owner; do not install): **Filament, Livewire, barryvdh/laravel-dompdf**.
Still to build: Phase 5 home page redesign (awaiting approval of the section structure), then Phase 6. Admin component kit (DataTable, Modal, ConfirmDialog, FileUpload, ImageInput, TagInput...) exists in `resources/js/admin/Components`.
Not wired yet: the existing 2FA services (`app/Services/Security`), planned for Phase 6.

## Common commands

- Dev (server + queue + Vite): `composer run dev` · Vite only: `npm run dev` · production assets: `npm run build` (needed if a view change is missing or "Unable to locate file in Vite manifest")
- Migrate/seed: `php artisan migrate`, `php artisan migrate:fresh --seed` (local seeder creates `admin@mitek.test` / `password`)
- Tests: `php artisan test --compact` (narrow with a path or `--filter`)
- Format: `vendor/bin/pint --dirty --format agent` (required after editing PHP)
- Clear caches: `php artisan optimize:clear`

## Business rules (from Mitek.md)

- **No online card payment.** Methods (enum `PaymentMethod`): bank transfer (Banque Populaire, validation 48–72 h), cash deposit at a branch (~24 h), cash on delivery, certified/bank cheque payable to STE MITEK SARL AU (cashing 8–20 days).
- **COD only if order total ≤ 2,000 DH TTC** (`PaymentSettings::cod_limit_cents`); enforce at checkout AND when placing the order.
- Customer uploads proof of payment (transfer, deposit, cheque copy); an admin validates it by hand in the back office.
- Order status flow (enum `OrderStatus`): `pending_payment` → `payment_submitted` → `paid` → `processing` → `shipped` → `delivered`, plus `cancelled`. Only legal transitions, each written to a status history, always through `TransitionOrderStatus`. Cash on delivery skips the payment steps (admin confirms: `pending_payment` → `processing`; the cash is recorded at `delivered`). A rejected proof sends the order back to `pending_payment`. Cancelling (before shipping) restocks. Stock is reserved when the order is placed.
- Orders are reached through a signed link (guests) or ownership (`OrderPolicy`), never by id. Proofs of payment live on the private `local` disk and are only streamed to admins.
- Shipping within 2 days of payment validation; admin sets the tracking number (required unless the customer uses their own carrier). Cost = zone of the typed city (unmapped cities use the default zone) + weight bracket, from the admin "Livraison" screen (`ShippingCalculator`). Own carrier = note + `ShippingSettings::own_carrier_fee_cents`.
- Every order gets a detailed PDF invoice: issued once, when the payment is validated (cash on delivery: when the admin confirms), numbered `FA-YYYY-00001` without gaps per year (`IssueInvoice`), seller details snapshotted at issue time, PDF rendered on demand by mPDF (`InvoicePdf`) and cached on the private disk. B2B customers have company name, **ICE, IF, RC**, shown on the invoice. Mitek's own ICE/IF/RC live in `GeneralSettings` (admin > Paramètres).
- Emails (French Markdown mailables in `app/Mail`, views in `resources/views/mail`) are sent by queued after-commit listeners on `OrderPlaced`, `OrderStatusChanged` and `QuoteReplied`: order received, proof received, payment validated (invoice attached) or COD confirmed, proof rejected, shipped, quote replied. **A queue worker must run** (`composer run dev` does) or nothing is sent.
- Cancelling an invoiced order issues a credit note `AV-YYYY-00001` (`IssueCreditNote`: full reversal, negative amounts, VAT breakdown, seller snapshot, own gapless yearly sequence) and emails the customer (`OrderCancelledMail`, credit note attached). Issued invoices and credit notes are immutable (`IsIssuedDocument`: no edit, no delete, FK `restrict`). Fiscal documents print the seller's ICE, IF, RC, TP, CNSS, legal form, capital and registered address from `GeneralSettings`, with `[À COMPLÉTER]` for any empty one.
- Admin notifications (queued, `NotifyAdmin`): new order, proof to validate, new quote request (later: service requests and contact messages) go to the `admin_notification_email` setting (default `contact@mitek.ma`).
- Guest orders are attached to an account only once its email is **verified** (`LinkGuestOrders`, on the `Verified`/`Registered` events): linking by an unverified address would expose someone else's orders.
- Customer area `/compte` (orders, invoices, quotes, addresses, profile with company details); checkout is pre-filled from it. Quote requests (`/devis`) are built from the cart; the admin answers in the back office and the customer is emailed a signed link.
- Quantity-based B2B price tiers per product, and a quote-request flow (from the cart; admin replies in the back office).
- IT services are not sold through the cart: each service page has a request form handled in the admin.
- Money: MAD (DH), VAT 20 % (setting), prices stored **HT as integer centimes**, displayed TTC. Use `App\Support\Money` and `App\Casts\MoneyCast`; never floats.
- **All product prices go through `App\Services\Core\Product\PriceCalculator`** (quantity tiers, VAT, rounding). The storefront, the price JSON endpoint and the cart (Phase 3) must all use it. Lazy loading is disabled: eager load `priceTiers`, `media`, `brand` before pricing or rendering cards.

## Coding conventions

- Thin controllers; validation in Form Requests; business logic in Actions/Services (`PlaceOrder`, `ValidatePayment`, `PriceCalculator`, `ShippingCalculator`, `TransitionOrderStatus`, `GenerateInvoice`...).
- PHP Enums (`app/Enums`) for statuses, payment methods and roles; Policies for authorization (customers only see their own orders, quotes, addresses).
- Models live in domain folders (`App\Models\Core\...`, `Security`, `User`); reuse existing traits (`UuidGenerator`, `HasSlug`, `Seoable`, `GetModelByKeyName`).
- No business value hard-coded in Blade or Vue (phone, bank details, COD limit, VAT, delays): read from `GeneralSettings`, `PaymentSettings`, `ShippingSettings`.
- Public URLs: slugs for catalog items, reference/signed link for orders (guests get a signed link). Use named routes (`route()`); storefront routes use French slugs.
- UI is French via `lang/fr` (+ `lang/fr.json`); keep strings in lang files so Arabic/RTL (`lang/ar`, `dir` already switched in the layout) can be added later. Admin Vue strings are currently hard-coded French.
- Storefront interactivity = Alpine only; admin = Vue/Inertia only. Keep Tailwind v4 conventions (no `tailwind.config.js`, `@theme` in CSS). Add dark-mode variants only if the page supports dark mode (storefront currently does not).
- Money-affecting and security-sensitive code (checkout, COD rule, payment validation, uploads, policies) always gets Pest tests. Use factories; check for existing states (`User::factory()->admin()`, `->customer()`).

## Header, search and mini-cart (Phase 6A: A2, A3)

- Layout partials live in `resources/views/components/layout/` (announcement, topbar, header, nav + mega menu, mobile drawer, cart drawer). Links to pages that are not built yet go through `nav_feature()` (`config/features.php` maps a feature to a route name): no dead links, no disabled stubs.
- Menu tree: `CatalogMenu::tree()` is ONE cache entry (`Category::MENU_CACHE_KEY`), flushed when a product or category is saved or deleted. Absolute image URLs are cached in it: after serving the app on another host (screenshots), run `php artisan cache:clear`.
- Search suggestions (`SearchSuggester`): grouped, max 5 per group, min 2 characters, 60 s cache per normalised term.
- Mini-cart drawer: the cart is JSON from `CartPayload` (same `CartService`/`PriceCalculator` as `/panier`; `GET panier/resume`, and the cart POST/PATCH/DELETE answer JSON when asked for it). The browser never computes a price; quantity changes and removals are optimistic and roll back to the last server state on error. The cash on delivery hint uses the items total against `PaymentSettings::cod_limit_cents` (the final decision stays at checkout, shipping included). Without JavaScript the cart icon is a normal link to `/panier` and the product form posts normally.

- WhatsApp (A4): every `wa.me` link goes through `App\Support\WhatsApp` / `whatsapp_url()` (number from `GeneralSettings::whatsapp`, digits only, message URL-encoded) and nothing renders while the number is empty. The floating button (bottom-left, label + `whatsapp_hours` only when set) is `layout/whatsapp-button`; "Commander par WhatsApp" is on product cards and the product page (prefilled with name, reference, TTC unit price from the PriceCalculator and URL; the product page also appends the chosen quantity).

## Settings groups, claims and delivery estimate (Phase 6A)

- **Never show an unconfirmed business claim.** `App\Settings\ClaimsSettings` holds booleans, all `false` by default (`quote_within_24h`, `dedicated_contact`, `admin_documents_on_request`, `proforma_available`, `pickup_casablanca`, `show_warranty_lines`); each controls where its claim appears. Claims confirmed in Mitek.md need no switch: certified technical team, customer service 6 days a week, monthly maintenance contracts, detailed invoice with every order, delivery everywhere in Morocco, preferential pricing by quantity. Values (opening hours, social URLs, warranty text, CNDP number) are settings that are **hidden while empty**.
- **Announcement bar**: `AnnouncementSettings` (text, https or internal link, optional period in the app timezone, dismissible); `isVisible()` decides.
- **Delivery estimate** = clock start (payment validation, or order confirmation for COD) + dispatch (`ShippingSettings::dispatch_days`, up to 2 working days) + transit (`transit_min_days`–`transit_max_days` of the shipping zone). Working days = `ShippingSettings::working_days` (ISO weekdays, default Monday–Saturday) minus `holidays`. Before payment the site says "Expédié sous 48 h après validation du paiement · livraison estimée X–Y jours ouvrés vers {ville}". The "24 à 72 h selon la ville" wording must stay consistent with it (delivery page, FAQ).
- **Best sellers**: a manual `is_best_seller` product flag; `CatalogSettings::best_seller_min_sales` (0 = off) adds sales-derived badges over the last 90 days later.

## Content, services and contact (Phase 5)

- **Services** (`services` table, admin Services): the 9 services of Mitek.md; `/services` (marketing, design-taste-frontend) and `/services/{slug}` with a request form (honeypot + `throttle:10,1`) → `ServiceRequest` (status new/contacted/quote_sent/done/closed, internal notes), customer confirmation email and admin notification.
- **CMS pages**: fixed set `App\Enums\PageKey` (À propos, Pourquoi Mitek, Modes de paiement, Modes de livraison, Mentions légales, CGV, Confidentialité) at fixed French URLs; admins edit title, Markdown content, SEO and the published flag. Content may use `{{tokens}}` (`ContentRenderer::values()`): the COD limit, delays, bank holder and company identifiers come from the settings, never typed in a page; an empty setting prints `[À COMPLÉTER]`. Markdown is rendered with raw HTML stripped.
- **FAQ**: categories + questions (admin FAQ); category slugs `paiement`, `livraison`, `services` feed the payment, delivery and service pages; `/faq` shows all.
- **Contact**: `/contact` form (honeypot + throttle) stores a `ContactMessage` (read/unread in admin) and notifies `admin_notification_email`; phone, WhatsApp and map come from the settings and are hidden while empty.

## SEO rules

Implemented (keep them true): every page passes `title`, `description`, `canonical`, `robots`, `image`, `ogType` and `schemas` to `<x-layouts.app>`; descriptions fall back to generated text (`App\Support\Seo::description`). JSON-LD (`App\Services\Seo\StructuredData`): Organization + LocalBusiness on every page, BreadcrumbList (from `<x-breadcrumb>`), Product + Offer (price TTC in MAD, availability), FAQPage, Service. Category URLs: a single filter group is indexable; two or more groups or a non-default sort are `noindex,follow` with the clean category URL as canonical (`CatalogSeo`). `/robots.txt` and `/sitemap.xml` are served by the app (`config/seo.php`); the sitemap is rebuilt daily and by a queued, de-duplicated job when public content is published. Private/utility pages are `noindex` and disallowed. One `h1` per page, no skipped heading level, an `alt` on every image, a branded 404/500 that never reads the database.

## Open items / TODO

From `Mitek.md` and the project (placeholders live in settings, empty until the owner supplies them):
- `[À COMPLÉTER]` phone number; Google Maps embed (placeholder); WhatsApp number
- Bank RIB/IBAN for STE MITEK SARL AU; Mitek's own ICE/IF/RC/legal mentions for invoices and legal pages
- Shipping rate grid: the seeded zones, cities and prices are DEMO values; the real grid and own-carrier fee are still needed
- Seeded catalogue prices/specs are DEMO values too
- Mitek's own ICE/IF/RC (invoices print `[À COMPLÉTER]` until filled in Paramètres)
- Legal pages text (mentions légales, CGV, confidentialité), return policy, customer-service hours
- B2B price-tier rules/discounts, real product catalogue beyond the examples in `Mitek.md`, product images
- Wording for quote requests and service request emails (not covered in `Mitek.md`)
- **Priced quotes** (DV numbering, PDF, customer accepts → order): not built; quote replies are text only for now
- Mitek's TP, CNSS, share capital and registered address (fields exist in Paramètres, empty until supplied)

## Mail deliverability (set at deploy, for mitek.ma)

Mail uses the `log` driver until deploy (Resend later). Before sending real email, add these DNS records for `mitek.ma`; the exact values come from the mail provider's dashboard:
- **SPF**: one `TXT` record on the sending domain (or the provider's return-path subdomain), e.g. `v=spf1 include:<provider> ~all`. Only one SPF record per name.
- **DKIM**: the `TXT`/`CNAME` record(s) the provider gives (selector `<selector>._domainkey.mitek.ma`). Enable signing and verify the domain in the dashboard.
- **DMARC**: `TXT` on `_dmarc.mitek.ma`, start with `v=DMARC1; p=none; rua=mailto:<mailbox>` to collect reports, then tighten to `p=quarantine` and later `p=reject` once SPF and DKIM align.
- Set `MAIL_MAILER`, `MAIL_FROM_ADDRESS=contact@mitek.ma` and the provider key in the production `.env`, and keep a **queue worker** running (all emails are queued).

## Workflow rules

1. Plan before coding; build phase by phase (Phases 1–5 are done. Phase 6A = global layout and P1 infrastructure (A1 settings foundation done; A2–A10 next), Phase 6B = pages (B1–B12 incl. the toner finder B9b, Home last), see docs/pages-blueprint.md. The old Phase 6 is now Phase 7: performance, security review, 2FA. Original list: 4 invoices/emails/customer area/quotes, 5 services/CMS/contact/SEO, 6 tests/performance/security).
2. At the end of each phase: run migrations and seeders, run `php artisan test`, run Pint, list the files changed, commit (with a message shown to the owner), then stop for review.
3. Ask before anything hard to undo: schema shape, adding/removing packages, switching away from Vue/Inertia or Alpine.
4. Follow the Boost guidelines below (activate the matching skills, use `search-docs` for version-specific APIs).

## Frontend rules (strict)
- Storefront: Blade + Alpine.js only. Never install or use Livewire.
- Admin (/admin): Inertia + Vue only.
- Storefront interactivity = Alpine + fetch to JSON endpoints, or classic forms with redirect. Pages must stay server-rendered for SEO.
- 
## Design skills (mandatory for any UI work)

Installed in `.agents/skills/` and symlinked into `.claude/skills/` (do not edit them, so updates keep working).

- Before creating or modifying any storefront Blade view, Blade component, Tailwind style, or admin Vue page, load and follow:
  - `better-ui`: exact surface and icon polish (nested border radius = inner radius + padding, optical alignment, layered shadows for depth, `currentColor` icons with stroke matching text weight, image outlines, `hover:` only on hover-capable pointers, `overscroll-behavior: contain` on overlays). **Applies to all UI, storefront and admin.**
  - `design-taste-frontend`: visual direction (typography, spacing, hero and section composition, copy tone, avoiding the generic AI look). **Applies ONLY to the marketing pages: home, À propos, services overview (`/services`) and Pourquoi Mitek.** Never to the catalogue, product, cart, checkout, account, legal pages or the admin.
- Apply them within our constraints:
  - Storefront = Blade + Alpine.js only (never Livewire). Admin = Inertia + Vue only.
  - Translate any React/JSX/shadcn examples from the skills into Blade components or Vue SFCs.
  - Pages stay server-rendered, fast, and SEO-friendly; animations only where they add clarity.
- **Project rules in this file override any installed skill** when they conflict, and the conflict resolutions listed below always win.
- Known conflicts and how we resolve them:
  - **Stack:** `design-taste-frontend` builds from its own kit (`starter.html`, `system.css`, `system.js`, copied into `public/assets`). We do not copy the kit: its patterns become Blade components styled with our Tailwind v4 tokens.
  - **Palette and fonts:** the skill deals palettes and font pairings (`start.mjs deal/css`). Never run them: the Mitek navy palette (`--color-brand-*`) and Instrument Sans are fixed.
  - **Business facts (its house rule 9):** the skill forbids register/VAT numbers, real phone numbers, postcodes and real contacts. Not applicable here: invoices and legal pages need ICE/IF/RC, and the footer uses the real `contact@mitek.ma`. Facts come from `Mitek.md` and the settings, never invented.
  - **Copy:** the skill imposes its own voice and `copy-lint`. `Mitek.md` stays the source of truth, in French. Do not rewrite its copy to satisfy a linter; `copy-lint` is advisory on new copy only.
  - **Images and icons:** the skill fetches stand-in photographs (Wikimedia Commons) and logo icons (jsDelivr) over the network. Do not run `photos.mjs` or `icons.mjs` without asking; product images are Mitek's own or clearly marked placeholders.
  - **Layout rules** ("one section per screen", hero alone on the first screen, no caption under an image): apply to marketing pages (home, about, services, delivery, payment, FAQ). They do not apply to utility pages (catalogue, product, cart, checkout, account, order, admin), where dense, functional layouts win. A product name and price under a card image is data, not a caption.
  - **Carousels (decided with the owner):** `design-taste-frontend` forbids them. The home hero is **one static, server-rendered, LCP-optimised hero** by default. The `slides` table may hold several slides: when more than one is active, render a **manual** carousel (arrows, dots, swipe, keyboard) with **no autoplay at all**. The deals and best-sellers blocks are a horizontal scroll-snap row with arrow buttons, not a carousel.
  - **Motion:** the skill mandates its motion layer, a hero entrance and one or two scroll-driven "dealt moments". We allow only CSS hover/focus/press feedback and Alpine state changes, always respecting `prefers-reduced-motion`. No scroll-linked or pinned sections, parallax, shaders or WebGL.
  - **Finish gate:** the skill's `Stop` hook (it ran `gate.mjs`: headless Chrome, up to 10 minutes, up to 3 fix rounds) is **disabled on purpose**: its `hooks:` block was removed from the frontmatter of `.agents/skills/design-taste-frontend/SKILL.md`, the only place it was registered. Never arm the gate (`gate.mjs --start`, `start.mjs deal`). Our checks are Pest, `npm run build` and Pint. `.taste-skill/` is git-ignored. **Re-check after any `npx skills update`, since updates may re-enable the hook or change `gate.mjs`** (look for a `hooks:` block in that SKILL.md again).
  - **Missing siblings:** `better-ui` defers typography, accessibility, layout, colour and motion to `better-typography`, `better-accessibility`, `better-layout`, `better-colors` and `better-motion`, which are not installed. Those concerns follow this file and the `tailwindcss-development` skill.
  - **Cards:** our cards use `ring-1 ring-slate-200` as a border. `better-ui` accepts a consistent project convention, so we keep it and do not convert it to shadows piecemeal.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== spatie/laravel-medialibrary/core rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
