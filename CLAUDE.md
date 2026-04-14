<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/ai (AI) - v0
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v3
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `laravel-best-practices` — Apply this skill whenever writing, reviewing, or refactoring Laravel PHP code. This includes creating or modifying controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes, and Eloquent queries. Triggers for N+1 and query performance issues, caching strategies, authorization and security patterns, validation, error handling, queue and job configuration, route definitions, and architectural decisions. Also use for Laravel code reviews and refactoring existing Laravel code to follow best practices. Covers any task involving Laravel backend PHP code patterns.
- `wayfinder-development` — Use this skill for Laravel Wayfinder which auto-generates typed functions for Laravel controllers and routes. ALWAYS use this skill when frontend code needs to call backend routes or controller actions. Trigger when: connecting any React/Vue/Svelte/Inertia frontend to Laravel controllers, routes, building end-to-end features with both frontend and backend, wiring up forms or links to backend endpoints, fixing route-related TypeScript errors, importing from @/actions or @/routes, or running wayfinder:generate. Use Wayfinder route functions instead of hardcoded URLs. Covers: wayfinder() vite plugin, .url()/.get()/.post()/.form(), query params, route model binding, tree-shaking. Do not use for backend-only task
- `inertia-vue-development` — Develops Inertia.js v3 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, useHttp, setLayoutProps, or router; working with deferred props, prefetching, optimistic updates, instant visits, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation.
- `tailwindcss-development` — Always invoke when the user's message includes 'tailwind' in any form. Also invoke for: building responsive grid layouts (multi-column card grids, product grids), flex/grid page structures (dashboards with sidebars, fixed topbars, mobile-toggle navs), styling UI components (cards, tables, navbars, pricing sections, forms, inputs, badges), adding dark mode variants, fixing spacing or typography, and Tailwind v3/v4 work. The core use case: writing or fixing Tailwind utility classes in HTML templates (Blade, JSX, Vue). Skip for backend PHP logic, database queries, API routes, JavaScript with no HTML/CSS component, CSS file audits, build tool configuration, and vanilla CSS.
- `ai-sdk-development` — Builds AI agents, generates text and chat responses, produces images, synthesizes audio, transcribes speech, generates vector embeddings, reranks documents, and manages files and vector stores using the Laravel AI SDK (laravel/ai). Supports structured output, streaming, tools, conversation memory, middleware, queueing, broadcasting, and provider failover. Use when building, editing, updating, debugging, or testing any AI functionality, including agents, LLMs, chatbots, text generation, image generation, audio, transcription, embeddings, RAG, similarity search, vector stores, prompting, structured output, or any AI provider (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).
- `fortify-development` — ACTIVATE when the user works on authentication in Laravel. This includes login, registration, password reset, email verification, two-factor authentication (2FA/TOTP/QR codes/recovery codes), profile updates, password confirmation, or any auth-related routes and controllers. Activate when the user mentions Fortify, auth, authentication, login, register, signup, forgot password, verify email, 2FA, or references app/Actions/Fortify/, CreateNewUser, UpdateUserProfileInformation, FortifyServiceProvider, config/fortify.php, or auth guards. Fortify is the frontend-agnostic authentication backend for Laravel that registers all auth routes and controllers. Also activate when building SPA or headless authentication, customizing login redirects, overriding response contracts like LoginResponse, or configuring login throttling. Do NOT activate for Laravel Passport (OAuth2 API tokens), Socialite (OAuth social login), or non-auth Laravel features.
- `laravel-permission-development` — Build and work with Spatie Laravel Permission features, including roles, permissions, middleware, policies, teams, and Blade directives.

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

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

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

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
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

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== laravel/ai rules ===

## Laravel AI SDK

- This application uses the Laravel AI SDK (`laravel/ai`) for all AI functionality.
- Activate the `developing-with-ai-sdk` skill when building, editing, updating, debugging, or testing AI agents, text generation, chat, streaming, structured output, tools, image generation, audio, transcription, embeddings, reranking, vector stores, files, conversation memory, or any AI provider integration (OpenAI, Anthropic, Gemini, Cohere, Groq, xAI, ElevenLabs, Jina, OpenRouter).

</laravel-boost-guidelines>

# Minishop — Project Overview

## What is Minishop?

Minishop is a **headless ecommerce platform for small businesses**. The backend is a Laravel 13 app; the frontend admin dashboard is built with Inertia.js v3 + Vue 3 + Tailwind CSS v4.

## Admin Dashboard

- The main admin dashboard lives at `/dashboard` (`resources/js/pages/Dashboard.vue`).
- All store management (products, orders, customers, etc.) is done through this dashboard.
- Use `AppSidebarLayout` for all admin pages to stay consistent with the existing layout.
- Admin routes are grouped under the `auth`, `verified`, and `role:super-admin|admin|manager` middleware, prefixed `/dashboard`, and named `admin.*`.

## Implemented Features

### Core Ecommerce
- **Products** — full CRUD, images (product-level + variant-specific), variants (size/color option matrix), stock tracking, SKU, categories, sorting, export (CSV/PDF), bulk actions (activate, deactivate, assign category, update stock, update price, delete)
- **Product types** — `simple` (no variants), `variable` (option/variant matrix), `bundled` (component products with calculated stock). Type is immutable after creation. `ProductType` enum with `isSimple()` / `isVariable()` / `isBundled()` helpers on the model. Bundle stock is derived via `Product::getEffectiveStock()` from the least-available component. `BuildLineItemsAction` and `DecrementStockAction` handle all three types at checkout.
- **Related products** — many-to-many `product_related` pivot; displayed on storefront product detail pages
- **Categories** — hierarchical categories with slugs
- **Orders** — full lifecycle (`pending → processing → shipped → delivered → cancelled/refunded`), manual order creation, invoice PDF generation, bulk actions (update status, delete), email notifications on status changes
- **Customers** — profiles linked to `users`, order history
- **Cart** — persistent server-side cart per session/user
- **Checkout** — address collection, order summary, payment step
- **Returns & Refunds** — return requests, approval/rejection workflow, refund processing

### Inventory & Catalog
- **Stock management** — automated low-stock threshold alerts per product and per variant; `low_stock_notified` flag prevents duplicate notifications; bundled products are excluded (their stock is calculated)
- **Product variants** — structured option types (e.g. size + colour), nested CRUD under products; blocked for bundled products
- **Product images** — multiple images per product or variant; `variant_id IS NULL` = product-level image
- **SEO** — per-product `meta_title` and `meta_description` fields; slug auto-generated from name on create
- **Product tags** — colour-coded taggable system; full admin CRUD (`TagController`), many-to-many `product_tag` pivot, `TagBadge` component, storefront display on product cards and detail pages
- **Advanced product search** — storefront catalogue filters: price range (min/max), description text search, category, and availability; filters preserved across pagination and other filter changes
- **On-sale pricing** — per-product `on_sale` boolean flag; sitewide `sale_discount_percentage` in `StoreSettings`; storefront shows sale price with strikethrough original; `on_sale` products can be filtered in catalogue

### Payments & Finance
- **Stripe** — payment intent flow; keys read from `.env` via `config/services.php`
- **PayMongo** — checkout session flow for PH market; webhook handling
- **Invoices** — auto-generated PDF per order (barryvdh/laravel-dompdf)
- **Coupons** — percentage or fixed discount, expiry, usage limits

### Storefront
- **Public storefront** — home, product catalogue, product detail, cart, checkout
- **AI support chat** — powered by `laravel/ai` SDK

### Admin Dashboard Panels
- **Dashboard overview** — KPI cards (revenue, orders, customers, products), revenue chart (dark-mode reactive)
- **Order management** — list with search/filter/sort, status update, bulk status update, invoice download
- **Product management** — list with search/filter/sort/export, full CRUD, bulk actions, type filter and `ProductTypeBadge` component
- **Customer management** — list, order history view
- **Coupon management** — full CRUD
- **Returns management** — list, approve/reject/receive/refund workflow
- **User management** — admin user CRUD with roles
- **Settings** — store name, currency (CAD default), tax rate, shipping methods
- **Activity log** — admin action history via Spatie Activity Log

### Supporting Features
- **Shipping** — shipping method CRUD; Canada Post carrier integration for calculated rates at checkout (in addition to flat-rate methods)
- **Tax** — province-aware tax compliance engine; rates resolved per Canadian province at checkout; configurable via `StoreSettings`
- **Email notifications** — queued mailables for order confirmation and status changes (shipped/delivered/cancelled)
- **Roles & Permissions** — Spatie Laravel Permission; roles: `super-admin`, `admin`, `manager`, `customer`
- **Canadian localization** — CAD currency, CA country defaults

### Headless API (`/api/v1/`)
- **Sanctum auth** — `HasApiTokens` on `User`; token-based auth for external storefront clients
- `POST /api/v1/auth/register` — creates user + customer profile via `CreateNewUser` Fortify action, returns token
- `POST /api/v1/auth/login` — validates credentials, returns token
- `POST /api/v1/auth/logout` — revokes current access token
- `GET /api/v1/user` — authenticated user profile with customer data
- `GET /api/v1/orders` — paginated order list scoped to authenticated user
- `GET /api/v1/orders/{order}` — single order detail with ownership enforcement
- All auth + order routes protected with `auth:sanctum` middleware
- Register reuses `CreateNewUser` Fortify action for consistency with web flow

## Remaining / Planned Features

- **Discount improvements** — bulk coupon generation, referral codes

## Laravel Best Practices

Always activate the `laravel-best-practices` skill when writing or reviewing Laravel PHP code.

### Key Patterns in This Codebase

**Eager loading** — always eager-load relations to avoid N+1 queries:
```php
Order::query()->with(['customer.user', 'items', 'shippingMethod'])->get();
```

**Authorization** — every controller action calls `$this->authorize()` against the model policy. Permissions follow the pattern `resource.action` (e.g. `orders.update`, `products.delete`):
```php
$this->authorize('update', $order);
```

**Form Requests** — all validation and authorization lives in Form Request classes, never inline:
```php
public function __invoke(BulkOrderActionRequest $request): RedirectResponse
```

**Single-purpose Action classes** — complex business logic is extracted to `app/Actions/`:
```php
$order = app(CreateOrderAction::class)->execute($data);
```

**Enum-driven state** — status values are PHP 8.1 backed enums with helper methods. Shared logic (e.g. allowed transitions) lives on the enum itself:
```php
// app/Enums/OrderStatus.php
public static function transitions(): array { ... }
```

**Bulk actions pattern** — bulk operations follow a consistent pattern:
- Invokable controller: `OrderBulkActionController` (one `__invoke` method, `match` on action)
- Dedicated Form Request: `BulkOrderActionRequest` (validates IDs + action + action-specific fields)
- Route registered **before** `Route::resource()` to avoid parameter capture
- Frontend: checkbox selection → bulk toolbar → optional modal for parameterised actions

**Queued email notifications** — always use `Mail::queue()` or `->queue()` for mailables triggered by user actions:
```php
Mail::to($email)->queue(new OrderStatusChangedMail($order));
```

**Money storage** — all monetary values are stored as integers (cents). Format for display with `number / 100`.

**Route key binding** — the `Order` model uses `order_number` as its route key, not `id`. Use `$order->order_number` in URLs.

**`StoreSettings::current()`** — singleton accessor for store configuration (tax rate, store name, currency). Use this instead of reading config directly.

## Naming & Route Conventions

- Admin Inertia pages: `resources/js/pages/admin/` (e.g. `admin/Products/Index.vue`)
- Storefront Inertia pages: `resources/js/pages/storefront/`
- Wayfinder actions: `resources/js/actions/App/Http/Controllers/`
- Admin web routes: `routes/web.php` grouped under `/dashboard`, named `admin.*`
- API routes: `routes/api.php` under `/api/v1/` prefix; auth routes (`register`, `login`, `logout`, `user`) + orders (`index`, `show`) protected by `auth:sanctum`
- Use `Route::resource()` for all CRUD entities
- Register standalone routes (export, bulk) **before** the resource route to avoid `{model}` capture

## Git & GitHub Workflow

Every feature or task must follow this branching workflow — no exceptions:

1. **Create a branch** from `main` using a descriptive name:
   - `feature/product-crud` — new features
   - `fix/order-status-bug` — bug fixes
   - `chore/update-dependencies` — maintenance tasks
2. **Commit** changes to that branch with clear, descriptive commit messages.
3. **Push** the branch to GitHub: `git push -u origin <branch-name>`
4. **Open a Pull Request** targeting `main` using `gh pr create`. The PR must include:
   - A short, clear title (under 70 characters)
   - A summary of what was changed and why
   - A test plan checklist
5. **Wait for review** — do not merge the PR. The user (willard) will review and merge on GitHub.
6. **Never push directly to `main`** — all changes go through PRs.

Branch naming: lowercase kebab-case (`feature/add-product-variants`, not `Feature/AddProductVariants`).

## PHP Testing (PHPUnit)

- Feature tests live in `tests/Feature/Admin/`, `tests/Feature/Storefront/`, etc.
- Use `RefreshDatabase` and seed `RoleAndPermissionSeeder` in `setUp()` for any test touching permissions.
- Use factory states (`->pending()`, `->processing()`, `->superAdmin()`, `->manager()`) before manually setting attributes.
- Always assert both the happy path and authorization failure (`assertForbidden()`).
- Run: `php artisan test --compact tests/Feature/Admin/SomeTest.php`

## Frontend Testing (Vitest)

Vue components must be tested with **Vitest** + **@vue/test-utils**.

### Setup
- Vitest is configured in `vite.config.ts` with `environment: 'jsdom'` and `globals: true`.
- Test files live in `resources/js/tests/` mirroring the source structure:
  - `resources/js/tests/components/` — component tests
  - `resources/js/tests/composables/` — composable tests
  - `resources/js/tests/pages/` — page-level tests

### File naming
- Test files must end in `.test.ts` (e.g. `AppLogo.test.ts`)

### Running tests
- `npm run test` — watch mode during development
- `npm run test:run` — single run (CI / before PR)
- `npm run test:coverage` — generate coverage report

### Conventions
- Every new Vue component, composable, or page with logic must have a corresponding test file.
- Every change to an existing component must include a test update.
- Use `describe` + `it` blocks. Keep test descriptions readable as plain English.
- Test what the component renders and how it behaves — avoid testing implementation details.
- Mock Inertia (`vi.mock('@inertiajs/vue3')`) — always include `router: { get, post, delete: vi.fn() }`.
- Mock Wayfinder action files (`vi.mock('@/actions/...')`).
- Run `npm run test:run` before opening a PR — all tests must pass.
