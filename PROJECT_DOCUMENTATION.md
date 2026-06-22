# Ecommerce Platform — Project Documentation

> The single source of truth for building this Laravel ecommerce application.
> Read this before starting any module. It encodes **what** we build and **how**,
> on top of the engineering conventions in `ENGINEERING_CONVENTIONS.md`.

**Stack:** PHP 8.2 · Laravel 12 · Livewire 4 · Tailwind v4 · Reverb · Redis (Predis)
· spatie/laravel-permission · DomPDF · simple-qrcode · Socialite
**Domain:** Multi-category online store (electronics-style storefront + admin panel)
**Currency / locale:** PKR / Pakistan (all money, dates, timezone read from Settings — never hardcoded)

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Module Map & Build Order](#2-module-map--build-order)
3. [Database Schema](#3-database-schema)
4. [Product & Variant System](#4-product--variant-system) ★ core
5. [SEO System](#5-seo-system) ★ core
6. [Cart, Checkout & Orders](#6-cart-checkout--orders)
7. [Payments](#7-payments)
8. [Coupons, Reviews, Wishlist, Compare](#8-coupons-reviews-wishlist-compare)
9. [Blog Module](#9-blog-module)
10. [Inventory](#10-inventory)
11. [Finance: Ledger, Summary, Reports](#11-finance-ledger-summary-reports)
12. [Gallery & Media](#12-gallery--media)
13. [Settings (admin-managed .env values)](#13-settings-admin-managed-env-values)
14. [Roles & Permissions](#14-roles--permissions)
15. [Dashboard & Analytics](#15-dashboard--analytics)
16. [Packages To Add](#16-packages-to-add)
17. [Per-Module Definition of Done](#17-per-module-definition-of-done)

---

## 1. Architecture Overview

The system is **API-first** with a Blade + Livewire admin and a Blade storefront.

```
                 ┌────────────────────────────────────────────┐
                 │                Storefront (web)             │
                 │  Blade + Livewire 4 + Alpine + Tailwind v4  │
                 └───────────────┬────────────────────────────┘
                                 │
   ┌─────────────────────────────┼──────────────────────────────┐
   │            Admin Panel (web, /admin/*)                       │
   │  HasMiddleware + can:{resource}.{action}                    │
   └─────────────────────────────┼──────────────────────────────┘
                                 │
              ┌──────────────────▼───────────────────┐
              │            Service Layer              │   ← business logic lives here
              │  app/Services/* (DB transactions)     │
              │  e.g. Catalog, Orders, Finance/Ledger │
              └──────────────────┬───────────────────┘
                                 │
   ┌─────────────────────────────▼──────────────────────────────┐
   │  Eloquent Models · FormRequests · API Resources             │
   │  RBAC (fail-closed) · Audit log · Settings helpers          │
   └─────────────────────────────┬──────────────────────────────┘
                                 │
                         MySQL  ·  Redis (queue/cache)  ·  Reverb (realtime)
```

**Non-negotiable rules** (full detail in `ENGINEERING_CONVENTIONS.md`):

- Business logic in **Services**, wrapped in DB transactions. Controllers stay thin.
- All money operations **post to the ledger** via `LedgerService`.
- All writes go through a **FormRequest** (`$request->validated()`, never `->all()`).
- API output uses the **Resource envelope** (`success` / `message` / `data` [+ `links`/`meta`]).
- Every action guarded by a `{resource}.{action}` permission, **fail-closed**.
- **No hardcoded** currency, date format, timezone, locale, pagination, or theme — use `format_money()`, `format_date()`, `per_page()`, `setting()`.
- Index every column used in a filter/sort/join. Eager-load. Paginate everything.

---

## 2. Module Map & Build Order

Build in dependency order. Each module ships with feature tests before merge.

| # | Module | Depends on | Notes |
|---|--------|-----------|-------|
| 0 | Settings + Roles/Permissions | — | foundation; needed by everything |
| 1 | Media / Gallery | Settings | product & blog images |
| 2 | Categories & Brands | Media | taxonomy |
| 3 | **Attributes & Attribute Values** | — | drives variants |
| 4 | **Products & Variants** | 1,2,3 | ★ core module |
| 5 | SEO layer | 4,9 | meta, slugs, sitemap, schema |
| 6 | Inventory | 4 | stock at variant level |
| 7 | Cart | 4 | session/DB |
| 8 | Coupons | 4 | discounts |
| 9 | Blog | 1,5 | posts, categories, tags |
| 10 | Checkout & Orders | 4,6,7,8 | order state machine |
| 11 | Payments | 10 | COD now; JazzCash/Easypaisa later |
| 12 | Finance: Ledger + Summary | 10 | source of truth |
| 13 | Reviews / Wishlist / Compare | 4 | engagement |
| 14 | Reports | 10,12 | PDF/exports |
| 15 | Dashboard analytics | all | KPIs |

---

## 3. Database Schema

Conventions: money = `decimal(12,2)` (accounting `15,2`); enums = `string` + `in:` validation;
dates/json/bool via casts; FK + filter/sort columns indexed.

### Catalog core

```
brands
  id, name, slug (unique), logo_media_id NULL, description NULL,
  is_active bool, meta_title NULL, meta_description NULL,
  timestamps
  INDEX(is_active)

categories
  id, parent_id NULL FK->categories, name, slug (unique),
  image_media_id NULL, description NULL, sort_order int,
  is_active bool, meta_title NULL, meta_description NULL, meta_image_media_id NULL,
  timestamps
  INDEX(parent_id), INDEX(is_active), INDEX(sort_order)

products
  id, category_id FK, brand_id NULL FK,
  name, slug (unique), sku (unique),
  type ENUM-as-string('simple','variable') default 'simple',
  short_description NULL, description LONGTEXT NULL,
  base_price decimal(12,2),            -- display/from price; real price on variant
  is_active bool default true, is_featured bool default false,
  published_at timestamp NULL,         -- null = draft
  -- SEO (see §5)
  meta_title NULL, meta_description NULL, meta_keywords NULL,
  og_image_media_id NULL, canonical_url NULL, no_index bool default false,
  timestamps, softDeletes
  INDEX(category_id), INDEX(brand_id), INDEX(is_active),
  INDEX(is_featured), INDEX(published_at),
  COMPOSITE INDEX(is_active, category_id, published_at)

attributes                              -- Size, Color, Capacity, Model
  id, name, code (unique, e.g. 'color'),
  type ENUM-as-string('select','swatch','radio') default 'select',
  is_variation bool default true,       -- does it create variants?
  sort_order int, timestamps
  INDEX(is_variation)

attribute_values                        -- S/M/L, Red, 128GB, iPhone 15
  id, attribute_id FK, value, label,
  color_hex NULL,                       -- for swatch type
  image_media_id NULL,                  -- swatch image
  sort_order int, timestamps
  INDEX(attribute_id)

product_attribute                       -- which attributes a product varies on (pivot)
  product_id FK, attribute_id FK
  PRIMARY(product_id, attribute_id)

product_variants                        -- ONE row per unique value combination
  id, product_id FK, sku (unique),
  price decimal(12,2),                  -- actual sale price
  compare_at_price decimal(12,2) NULL,  -- strikethrough "was" price
  cost_price decimal(12,2) NULL,        -- for COGS in ledger
  stock_quantity int default 0,
  low_stock_threshold int default 0,
  weight decimal(8,3) NULL, barcode NULL,
  image_media_id NULL, is_active bool default true,
  is_default bool default false,        -- the variant shown first
  timestamps
  INDEX(product_id), INDEX(is_active), INDEX(price), INDEX(stock_quantity)

attribute_value_product_variant         -- variant <-> its defining values (pivot)
  product_variant_id FK, attribute_value_id FK
  PRIMARY(product_variant_id, attribute_value_id)
  INDEX(attribute_value_id)              -- for storefront filtering

product_media                            -- gallery images per product (ordered)
  id, product_id FK, media_id FK, sort_order int, is_primary bool
  INDEX(product_id)
```

### Commerce

```
carts                 id, user_id NULL, session_id NULL, timestamps  INDEX(user_id), INDEX(session_id)
cart_items            id, cart_id FK, product_variant_id FK, quantity, price_snapshot, timestamps
coupons               id, code (unique), type('percent','fixed'), value decimal(12,2),
                      min_subtotal NULL, max_uses NULL, used_count, starts_at NULL, expires_at NULL,
                      is_active bool, timestamps  INDEX(code), INDEX(is_active)
orders                id, order_number (unique), user_id NULL,
                      status('pending','paid','processing','shipped','delivered','completed','cancelled','refunded'),
                      payment_method('cod','card','qr'), payment_status('unpaid','paid','partially_refunded','refunded'),
                      subtotal, discount_total, tax_total, shipping_total, grand_total (all decimal 12,2),
                      coupon_id NULL FK, currency 'PKR', placed_at, timestamps
                      INDEX(status), INDEX(payment_status), INDEX(user_id), INDEX(placed_at)
order_items           id, order_id FK, product_variant_id FK,
                      name_snapshot, sku_snapshot, attributes_snapshot json,
                      unit_price, quantity, line_total, cost_snapshot  INDEX(order_id)
order_addresses       id, order_id FK, type('billing','shipping'), name, phone, line1, line2, city, state, zip, country
payments              id, order_id FK, gateway('cod','jazzcash','easypaisa','manual_qr'),
                      amount decimal(12,2), status('pending','succeeded','failed','refunded'),
                      transaction_ref NULL, payload json NULL, timestamps  INDEX(order_id), INDEX(status)
reviews               id, product_id FK, user_id FK, rating tinyint, title NULL, body,
                      is_approved bool default false, timestamps  INDEX(product_id), INDEX(is_approved)
wishlists             id, user_id FK, product_id FK, timestamps  UNIQUE(user_id, product_id)
```

### Content & system

```
blog_posts            id, author_id FK, title, slug (unique), excerpt NULL, body LONGTEXT,
                      cover_media_id NULL, status('draft','published'), published_at NULL,
                      meta_title, meta_description, og_image_media_id, no_index bool,
                      timestamps, softDeletes  INDEX(status), INDEX(published_at)
blog_categories       id, name, slug (unique), parent_id NULL, sort_order
blog_tags             id, name, slug (unique)
blog_post_category    post_id, category_id   |  blog_post_tag  post_id, tag_id
media                 id, disk, path, mime, size, width NULL, height NULL, alt NULL,
                      title NULL, folder NULL, uploaded_by FK, timestamps  INDEX(folder)
settings              id, group, key, value (text/json), type, UNIQUE(group, key)
activity_logs         (audit) — see conventions §4.4
ledger_entries        see §11
```

---

## 4. Product & Variant System ★

This is the heart of the app. Model it as an **attribute-driven variant system**
(the WooCommerce / Shopify pattern). Get this right first.

### 4.1 Core concepts

- A **Product** is the catalog entry the customer browses (name, description, images, SEO).
- An **Attribute** is a dimension of variation: `Color`, `Size`, `Capacity`, `Model`.
- An **Attribute Value** is one option of an attribute: `Red`, `XL`, `128GB`, `iPhone 15`.
- A **Variant** is **one purchasable combination** of attribute values, e.g.
  `Color=Red + Capacity=128GB`. **Price and stock live on the variant — never on the product.**

> **Golden rule:** every product has **at least one variant**. A "simple" product is just
> a product with a single default variant (no attributes). This keeps cart, inventory,
> orders, and the ledger uniform — they always reference a `product_variant_id`, never a bare product.

### 4.2 The two product types

| Type | Meaning | Variants |
|------|---------|----------|
| `simple` | No options (e.g. a cable) | 1 auto-created default variant holds price + stock |
| `variable` | Has options (e.g. a phone in colors/capacities) | N variants, one per chosen combination |

### 4.3 How a variant maps to its values

```
product_variants                          attribute_value_product_variant (pivot)
┌────┬───────────┬────────┬────────┐       ┌──────────────────┬───────────────────┐
│ id │ product_id│ price  │ stock  │       │ product_variant_id│ attribute_value_id│
├────┼───────────┼────────┼────────┤       ├──────────────────┼───────────────────┤
│ 11 │ 5         │ 1100.00│ 12     │ <──── │ 11               │ 3  (Color=Red)    │
│    │           │        │        │       │ 11               │ 7  (Capacity=128) │
└────┴───────────┴────────┴────────┘       └──────────────────┴───────────────────┘
```

Variant #11 = "Red, 128GB". To find a variant for a chosen combination, match the
**set** of `attribute_value_id`s. The storefront filter sidebar (Brand/Color/Price)
queries `attribute_value_product_variant` joined to active variants.

### 4.4 Generating variants

When the admin saves a variable product, the `VariantGenerator` service builds the
**cartesian product** of selected attribute values, then upserts variants:

```php
// app/Services/Catalog/VariantGenerator.php
public function generate(Product $product, array $attributeValueMap): void
{
    // $attributeValueMap = [colorAttrId => [redId, blueId], capacityAttrId => [v64, v128]]
    DB::transaction(function () use ($product, $attributeValueMap) {
        $combos = $this->cartesian(array_values($attributeValueMap)); // [[redId,v64],[redId,v128],...]

        $keep = [];
        foreach ($combos as $valueIds) {
            sort($valueIds);
            $signature = implode('-', $valueIds);            // stable key per combination
            $variant = $product->variants()
                ->whereHas('attributeValues', fn ($q) => $q->whereIn('attribute_values.id', $valueIds), '=', count($valueIds))
                ->first()
                ?? $product->variants()->create([
                    'sku'   => $product->sku.'-'.Str::upper(Str::random(5)),
                    'price' => $product->base_price,
                    'stock_quantity' => 0,
                ]);

            $variant->attributeValues()->sync($valueIds);
            $keep[] = $variant->id;
        }
        // soft-deactivate variants whose combination was removed (don't hard-delete: orders reference them)
        $product->variants()->whereNotIn('id', $keep)->update(['is_active' => false]);
    });
}
```

**Never hard-delete a variant** that has order history — deactivate it. Order items
carry a snapshot (`name_snapshot`, `sku_snapshot`, `attributes_snapshot`) so historical
orders stay readable even if a variant is later removed or renamed.

### 4.5 Pricing

- `variants.price` is the real price; `compare_at_price` renders the strikethrough "was".
- `products.base_price` is only the "from" display price (use `min(active variants.price)`).
- `cost_price` per variant feeds **COGS** into the ledger on each sale (§11).

### 4.6 Admin UI (Livewire)

The variant builder screen:
1. Select product type (`simple` / `variable`).
2. For `variable`: pick attributes, then tick the values to include.
3. "Generate variants" builds the grid; each row = one variant with editable price,
   compare-at, cost, stock, SKU, barcode, image, active toggle.
4. Bulk actions: set price/stock for all rows; set per-attribute (e.g. all "Red").
5. Mark one variant `is_default`.

Use `wire:ignore` + `window.ErpForms.init` for Select2 inside the component (conventions §1.1, §7).

### 4.7 Storefront product page (Image 4)

- Show default variant's price/stock; swatches/dropdowns for each attribute.
- On selection change, resolve the matching variant client-side (preload the variant
  map as JSON) and update price, stock badge, image, and SKU.
- "Add to Cart" sends `product_variant_id` + qty — never a bare product id.

### 4.8 Catalog listing & filters (Images 1, 2)

- Eager-load `brand`, `category`, default variant, primary media.
- Filters: brand, attribute values (color/size…), price range, on-sale, in-stock.
- Sort: featured, price asc/desc, newest, rating.
- Paginate with `per_page()`. Index `is_active`, `category_id`, `price`, and the
  `attribute_value_id` pivot column.

---

## 5. SEO System ★

SEO is a **cross-cutting layer**, applied uniformly to products, categories, shop pages,
and blog posts. Build it once as reusable pieces; every content model plugs in.

### 5.1 Per-record SEO fields (already in schema)

Every public entity (`products`, `categories`, `brands`, `blog_posts`) carries:
`slug`, `meta_title`, `meta_description`, `og_image_media_id`, `no_index`, and where
relevant `canonical_url`. Provide sensible **fallbacks** when fields are blank:

| Field | Fallback |
|-------|----------|
| `meta_title` | record name/title + ` — ` + `setting('seo','title_suffix')` |
| `meta_description` | trimmed `short_description`/`excerpt` (≤160 chars) |
| `og_image` | `og_image_media_id` → primary image → `setting('seo','default_og_image')` |
| `canonical_url` | the record's own absolute URL |

### 5.2 Slugs

Use **spatie/laravel-sluggable** on every public model. Slugs are unique, generated
from the title, and **immutable once published** (changing a live slug breaks links —
if you must, store the old slug and 301-redirect). Add a `redirects` table later if needed.

```php
public function getSlugOptions(): SlugOptions
{
    return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
}
```

URL scheme:

```
/                       home
/shop                   all products (filters via query string)
/category/{slug}        category landing
/brand/{slug}           brand landing
/product/{slug}         product detail
/blog                   blog index
/blog/{slug}            post detail
/blog/category/{slug}   blog category
/blog/tag/{slug}        blog tag
```

### 5.3 Meta tags — one reusable component

Build a single Blade component `<x-seo.meta />` that every page includes. It renders
title, description, canonical, robots, Open Graph, and Twitter cards from a `SeoData`
DTO produced by a `SeoService`:

```blade
{{-- resources/views/components/seo/meta.blade.php --}}
<title>{{ $seo->title }}</title>
<meta name="description" content="{{ $seo->description }}">
<link rel="canonical" href="{{ $seo->canonical }}">
@if($seo->noIndex)<meta name="robots" content="noindex,nofollow">@endif

<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:url" content="{{ $seo->canonical }}">
<meta property="og:image" content="{{ $seo->ogImage }}">
<meta property="og:site_name" content="{{ setting('general','app_name') }}">
<meta name="twitter:card" content="summary_large_image">

@if($seo->schema)
<script type="application/ld+json">{!! json_encode($seo->schema, JSON_UNESCAPED_SLASHES) !!}</script>
@endif
```

```php
// app/Services/Seo/SeoService.php
public function forProduct(Product $p): SeoData { /* fills title/desc/canonical/og + Product schema */ }
public function forCategory(Category $c): SeoData { /* + ItemList/CollectionPage */ }
public function forPost(BlogPost $post): SeoData { /* + Article schema */ }
public function forShop(): SeoData { /* generic, reads Settings */ }
```

Each controller builds its `SeoData` via the service and passes it to the layout, which
renders `<x-seo.meta :seo="$seo" />` in `<head>`.

### 5.4 Structured data (JSON-LD) — what to emit per page

| Page | Schema.org type | Key properties |
|------|-----------------|----------------|
| Product detail | `Product` + `Offer` + `AggregateRating` | name, image, sku, brand, price, availability, currency=PKR, reviewCount/ratingValue |
| Category / shop | `CollectionPage` + `ItemList` | list of product URLs |
| Blog post | `Article` (or `BlogPosting`) | headline, image, datePublished, author, publisher |
| All pages | `BreadcrumbList` | breadcrumb trail |
| Site-wide | `Organization` + `WebSite` (with `SearchAction`) | logo, name, sameAs social links from Settings |

Rich product results need `Offer.price`, `priceCurrency`, and `availability`
(`InStock`/`OutOfStock`) — pull these from the **default/selected variant**, not the product.

### 5.5 Sitemap & robots

Use **spatie/laravel-sitemap** to generate `sitemap.xml` on a schedule (queued job),
including products, categories, brands, and published posts (skip `no_index` records and
unpublished drafts). Serve a `robots.txt` that points to the sitemap and disallows
`/admin`, `/cart`, `/checkout`, `/account`.

```php
// scheduled daily
Sitemap::create()
  ->add(Product::active()->get()->map->sitemapUrl())
  ->add(Category::active()->get()->map->sitemapUrl())
  ->add(BlogPost::published()->get()->map->sitemapUrl())
  ->writeToFile(public_path('sitemap.xml'));
```

### 5.6 SEO Settings (admin-managed, §13 group `seo`)

`title_suffix`, `default_meta_description`, `default_og_image`, `google_analytics_id`,
`google_site_verification`, `facebook_app_id`, `social_links[]`, `robots_extra`,
`organization_name`, `organization_logo`. Read everywhere via `setting('seo', …)`.

### 5.7 SEO Definition of Done (per public page)

- [ ] Unique `<title>` and meta description (with fallbacks).
- [ ] Canonical URL set; pagination uses canonical to page 1 or self consistently.
- [ ] OG + Twitter tags with a real image.
- [ ] Correct JSON-LD for the page type.
- [ ] Breadcrumb schema.
- [ ] Slug is clean, unique, lowercase, hyphenated.
- [ ] In sitemap (if public) or `no_index` (if not).
- [ ] Drafts never indexed.

---

## 6. Cart, Checkout & Orders

**Cart:** DB-backed (`carts`/`cart_items`) keyed by `user_id` or guest `session_id`;
merge guest cart into user cart on login. Items reference `product_variant_id` and store
a `price_snapshot`. Re-validate stock and price at checkout.

**Checkout (Image 3):** collect billing + optional shipping address, coupon, shipping
method, payment method (COD / card / QR). All order creation happens in
`OrderService::place()` inside one DB transaction:

1. Re-check variant stock and prices.
2. Compute subtotal → apply coupon → tax → shipping → grand total.
3. Create `order` + `order_items` (with snapshots) + `order_addresses` + `payment` row.
4. Decrement variant stock (or reserve, then decrement on payment for card/QR).
5. Post to ledger on payment success (§11).
6. Fire `OrderPlaced` event → queued mail/notifications (Reverb for live admin updates).

**Order state machine:**

```
pending ─paid→ paid ─→ processing ─→ shipped ─→ delivered ─→ completed
   └────────────────────────────────────────────→ cancelled
                                          paid ──→ refunded
```

COD orders start `pending`/`unpaid` and are marked paid on delivery. Card/QR orders move
to `paid` only on confirmed payment. Consider `spatie/laravel-model-states` for transitions.

---

## 7. Payments

Abstract behind a contract so gateways are swappable. **COD is the only live method now;**
JazzCash and Easypaisa will be added later without touching order logic.

```php
interface PaymentGateway {
    public function charge(Order $order): PaymentResult;     // redirect URL or immediate result
    public function verify(array $callback): PaymentResult;  // webhook/return verification
}
```

- `CodGateway` — marks order `pending`, payment `unpaid`; admin confirms on delivery.
- `QrGateway` — `simple-qrcode` renders the QR; payment confirmed manually by admin or
  via provider callback (define the flow when the provider is chosen).
- `JazzCashGateway` / `EasypaisaGateway` — **stubs for now**; implement `charge()`/`verify()`
  later. Store every attempt in `payments` with `payload` json and `transaction_ref`.

Gateway selection driven by Settings (enabled methods, credentials encrypted at rest — §13/§4.3).

---

## 8. Coupons, Reviews, Wishlist, Compare

- **Coupons:** `percent` or `fixed`; validate active window, min subtotal, max uses;
  apply in `OrderService`. Increment `used_count` atomically inside the transaction.
- **Reviews:** one per user per product (enforce), `is_approved` defaults false →
  moderation queue; only approved reviews count toward `AggregateRating` (SEO §5.4).
- **Wishlist:** simple `user_id`+`product_id` unique pivot.
- **Compare:** session-based list of product ids; compares spec/attribute tables.

---

## 9. Blog Module

Posts with categories + tags, cover image, draft/published status, scheduled
`published_at`. Full SEO via the shared layer (§5) emitting `Article` schema.
Storefront blog index, category, and tag pages (Image 5). Admin CRUD with a rich-text
body (sanitize HTML on save).

---

## 10. Inventory

Stock is tracked **at the variant level** (`product_variants.stock_quantity`).

- **Stock movements** table logs every change (purchase, sale, adjustment, return) with
  reason, delta, resulting balance, and user — never silently mutate stock.
- **Low-stock** when `stock_quantity <= low_stock_threshold`; surface on dashboard
  (Image 6 style: Total / Low / Critical / Stock Value cards).
- **Purchases** (Image 7): a purchase entry restocks linked variants automatically and
  writes a movement per line; cost updates `cost_price` (moving average or last-cost —
  pick one and document it). Purchases post to the ledger as an expense/asset.

> Adapt the culinary mockups (flour, beef) to your product variants — same UI, your data.

---

## 11. Finance: Ledger, Summary, Reports

**The ledger is the source of truth.** Every money event runs through `LedgerService`
inside a transaction and writes balanced entries.

```
ledger_entries
  id, entry_date, account (e.g. 'sales_revenue','cogs','tax_payable','cash','refunds',
       'shipping_income','gateway_fees','inventory'),
  debit decimal(15,2) default 0, credit decimal(15,2) default 0,
  reference_type, reference_id (polymorphic to order/payment/purchase/refund),
  memo, created_by, timestamps
  INDEX(account), INDEX(entry_date), INDEX(reference_type, reference_id)
```

Event → postings:

| Event | Debit | Credit |
|-------|-------|--------|
| Order paid | Cash | Sales Revenue, Tax Payable, Shipping Income |
| Recognize COGS | COGS | Inventory |
| Refund | Sales Revenue (contra) | Cash |
| Purchase (restock) | Inventory | Cash / Payable |
| Gateway fee | Gateway Fees | Cash |

**Financial summary:** revenue, COGS, gross profit, refunds, net, by period — all derived
from `ledger_entries`, never recomputed ad-hoc from orders.

**Reports:** sales by day/category/product, best sellers, low stock, tax collected,
profit. Export via **DomPDF** (PDF) and CSV/XLSX. Respect `format_money`/`format_date`.

---

## 12. Gallery & Media

Use **spatie/laravel-medialibrary** + **intervention/image**. A single `media` model
backs product images, variant images, category/brand images, blog covers, and the
standalone Gallery. Generate responsive conversions (thumb / medium / large) and store
`alt`/`title` (alt text matters for SEO). Uploads use `<x-file-drop>` (conventions §1.4).

---

## 13. Settings (admin-managed .env values)

Admin controls runtime config from `/admin/settings/*` (key-value `settings` table via
the `Setting` model; secrets `encrypted` + keep-on-blank). Groups:

| Group | Keys (examples) |
|-------|-----------------|
| `general` | app_name, logo, favicon, currency, currency_symbol, currency_position, decimals, timezone, locale, items_per_page, theme |
| `mail` | mailer, host, port, username, password*, encryption, from_address, from_name |
| `seo` | title_suffix, default_meta_description, default_og_image, analytics_id, site_verification, social_links |
| `payment` | cod_enabled, qr_enabled, jazzcash_enabled+creds*, easypaisa_enabled+creds* |
| `shipping` | flat_rate, free_over, methods |
| `tax` | enabled, rate, inclusive |
| `social_login` | microsoft_enabled + creds* |
| `store` | address, phone, support_email, business_hours |

`*` = secret → encrypted at rest, masked in UI/API, never logged (conventions §4.3).

**SettingsApplier** pushes runtime-affecting values (mail, timezone, locale) into Laravel
config at boot so `.env`-style values are admin-editable without redeploying.
Read everywhere via `setting()` / `format_money()` / `format_date()` / `per_page()` — **never hardcode.**

---

## 14. Roles & Permissions

spatie/laravel-permission, fail-closed (conventions §4). Permission name = resource URI
(plural) + verb: `products.view|create|edit|delete`, `orders.view|edit`,
`variants.edit`, `coupons.*`, `blog-posts.*`, `reviews.moderate`, `settings.view|edit`,
`reports.view`, `ledger.view`. Suggested roles: `super-admin` (Gate::before bypass),
`admin`, `catalog-manager`, `order-manager`, `accountant`, `editor` (blog), `customer`.
Add permissions to `RolePermissionSeeder::$groups` the moment a module is done, then re-seed.

---

## 15. Dashboard & Analytics

Admin home KPIs: today/period revenue, orders by status, AOV, new customers, top products,
low-stock count, recent orders, revenue chart. Pull from ledger + orders; cache expensive
aggregates (Redis) and refresh via queued job. Live order ping via Reverb.

---

## 16. Packages To Add

Already installed: laravel/framework, livewire, reverb, predis, dompdf, simple-qrcode,
socialite(+microsoft), spatie/laravel-permission, tinker.

Add:

```bash
composer require spatie/laravel-medialibrary    # media/gallery + conversions
composer require intervention/image             # image processing
composer require spatie/laravel-sluggable        # slugs (SEO)
composer require spatie/laravel-sitemap          # sitemap.xml (SEO)
composer require spatie/laravel-model-states     # order state machine (optional)
composer require spatie/laravel-backup --dev     # prod backups (optional)
# laravel/scout + meilisearch — only if catalog search needs to scale later
```

---

## 17. Per-Module Definition of Done

A module is "done" only when **all** of these are true:

- [ ] Migration(s) with indexes on every filter/sort/join column.
- [ ] Eloquent model with casts, relations, scopes.
- [ ] FormRequest(s); writes persist only `validated()`.
- [ ] Service class for business logic (DB transactions; money → ledger).
- [ ] API Resource(s) with the standard envelope (if exposed via API).
- [ ] Web controller(s) in `app/Http/Controllers/Admin/`, `HasMiddleware`,
      `can:{resource}.{action}` on every action.
- [ ] Blade/Livewire views extending `layouts.admin`; storefront views as needed.
- [ ] Permissions added to `RolePermissionSeeder::$groups` and re-seeded.
- [ ] Settings helpers used (no hardcoded currency/date/timezone/pagination).
- [ ] Public pages: full SEO (§5.7) — meta, canonical, OG, JSON-LD, sitemap.
- [ ] Secrets encrypted + masked + never logged.
- [ ] Audit logging on mutations.
- [ ] Feature tests in `tests/Feature/`; CI green.
- [ ] Wired into `config/navigation.php`.

---

_Keep this document updated as modules ship. It pairs with `ENGINEERING_CONVENTIONS.md`._
