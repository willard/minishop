# Minishop

A headless ecommerce platform for small businesses. Built with Laravel 13, Inertia.js v3, Vue 3, and Tailwind CSS v4.

## Features

**Admin Dashboard**
- Product management — CRUD, variants, images, categories, stock tracking, bulk actions, CSV/PDF export
- Order management — full lifecycle, manual order creation, invoice PDF, bulk status updates, email notifications
- Customer management — profiles, purchase history
- Coupon management — percentage and fixed discounts, expiry, usage limits
- Returns & refunds — approval/rejection workflow, refund processing
- Shipping methods — flat-rate configuration
- Store settings — name, currency, tax rate
- Revenue chart and KPI dashboard
- Activity log
- Role-based access — `super-admin`, `admin`, `manager`, `customer`

**Storefront**
- Public product catalogue, detail pages, cart, and checkout
- Stripe and PayMongo payment integration
- AI-powered support chat (Laravel AI SDK)

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 13 |
| Frontend | Vue 3, Inertia.js v3, Tailwind CSS v4 |
| Auth | Laravel Fortify |
| Permissions | Spatie Laravel Permission |
| Payments | Stripe, PayMongo |
| AI | Laravel AI SDK (`laravel/ai`) |
| PDF | barryvdh/laravel-dompdf |
| Routing (TS) | Laravel Wayfinder |
| Testing (PHP) | PHPUnit 12 |
| Testing (JS) | Vitest + @vue/test-utils |
| Database | SQLite (dev), MySQL/PostgreSQL (prod) |

## Requirements

- PHP 8.2+
- Node.js 20+
- Composer

## Getting Started

**1. Clone and install**

```bash
git clone https://github.com/willard/minishop.git
cd minishop
composer setup
```

The `composer setup` command installs dependencies, copies `.env.example` to `.env`, generates an app key, runs migrations, installs npm packages, and builds assets.

**2. Seed the database**

```bash
php artisan db:seed
```

This runs all seeders including roles/permissions, sample products, categories, orders, coupons, and shipping methods.

**3. Start the development server**

```bash
composer run dev
```

This starts Laravel (`localhost:8000`), Vite, the queue worker, and Pail log viewer concurrently.

## Environment Variables

Copy `.env.example` to `.env` and configure:

```env
# Stripe
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# PayMongo (optional)
PAYMONGO_PUBLIC_KEY=pk_test_...
PAYMONGO_SECRET_KEY=sk_test_...

# Mail (use log driver in development)
MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@minishop.com

# AI support chat
ANTHROPIC_API_KEY=sk-ant-...
```

## Testing

**PHP**

```bash
php artisan test --compact
```

**JavaScript**

```bash
npm run test:run
```

**Both (with Pint lint check)**

```bash
composer test
```

## Project Structure

```
app/
├── Actions/           # Single-purpose business logic classes
├── Enums/             # PHP 8.1 backed enums (OrderStatus, etc.)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/     # Admin dashboard controllers
│   │   ├── Account/   # Customer account controllers
│   │   ├── Storefront/
│   │   └── Webhooks/  # Stripe & PayMongo webhook handlers
│   └── Requests/Admin/
├── Mail/              # Queued mailables
├── Models/
└── Policies/

resources/js/
├── actions/           # Wayfinder-generated TypeScript route functions
├── components/        # Shared Vue components (ui/, layout/)
├── pages/
│   ├── admin/         # Admin dashboard pages
│   └── storefront/    # Public storefront pages
└── tests/             # Vitest tests mirroring pages/ structure

routes/
├── web.php            # All web routes (admin + storefront + account)
└── api.php            # API routes
```

## Roles

| Role | Access |
|---|---|
| `super-admin` | Full access including delete operations |
| `admin` | Full access except destructive actions |
| `manager` | Read/write access, no delete |
| `customer` | Storefront and account area only |

## Key Conventions

- All monetary values are stored as **integers (cents)**
- `Order` route key is `order_number`, not `id`
- Admin routes are prefixed `/dashboard` and named `admin.*`
- Wayfinder generates typed TypeScript functions for all routes — never hardcode URLs in Vue files
- Run `vendor/bin/pint --dirty` after modifying PHP files
- Run `php artisan wayfinder:generate` after adding or changing routes

## License

MIT
