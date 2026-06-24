# Inventory, Manufacturing & Sales Platform — Project Documentation

> The single source of truth for building this Laravel application.
> Read this before starting any module. It encodes **what** we build and **how**,
> on top of the engineering conventions in `ENGINEERING_CONVENTIONS.md`.

**Stack:** PHP 8.2 · Laravel 12 · Livewire 4 · Tailwind v4 · Reverb · Redis (Predis)
· spatie/laravel-permission · DomPDF · simple-qrcode · Socialite
**Domain:** Inventory + light manufacturing + multi-channel sales. We buy raw materials
and trading goods, optionally **assemble** raw into finished products, and **sell** both
raw and finished items through three channels — the **public website**, an in-store
**POS** (no shift management), and **vendor / wholesale**. Customers can be quoted first
(**quotations**) and quotes convert into sales.
**Currency / locale:** PKR / Pakistan (all money, dates, timezone read from Settings — never hardcoded)

### Key decisions (locked)

- **Items are unified.** One `products` table holds everything — raw materials, manufactured
  goods, trading goods, services — separated by a `type` and capability flags. Raw materials
  are just products flagged not-web-listed.
- **A flag controls website listing.** `products.is_web_listed` decides what appears in the
  public storefront catalog. POS/vendor can sell items the website never shows.
- **Assembly: both modes.** Each manufacturable item is `to_stock` (assembled in batches in
  advance) or `to_order` (assembled when sold). Either way assembly is an explicit production
  record that consumes components and produces finished stock.
- **Costing: moving average.** Each purchase re-blends one average unit cost per variant; that
  cost is used for raw consumption and for COGS on every sale. (Switchable via Settings later.)
- **Pricing: markup default + manual override, two tiers.** Suggested price = `cost × (1 + markup%)`
  (markup defaults global → category → item); a manual price always wins. Prices exist in two
  tiers: **retail** (web + POS) and **wholesale** (vendor).
- **One sales document, three channels.** Web orders, POS sales, and vendor sales are all `orders`
  rows distinguished by `channel`. Quotations convert into the same document.
- **Ledger is the source of truth.** Purchase, production, and sale all post to the ledger.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Module Map & Build Order](#2-module-map--build-order)
3. [Database Schema](#3-database-schema) ★ primary reference
4. [Items & Catalog Model](#4-items--catalog-model) ★ core
5. [Product & Variant System](#5-product--variant-system)
6. [Purchasing & Suppliers](#6-purchasing--suppliers)
7. [Bill of Materials & Production](#7-bill-of-materials--production)
8. [Inventory & Stock Movements](#8-inventory--stock-movements)
9. [Costing & Pricing](#9-costing--pricing)
10. [Customers](#10-customers)
11. [Quotations](#11-quotations)
12. [Sales Channels: Web, POS, Vendor](#12-sales-channels-web-pos-vendor)
13. [Payments](#13-payments)
14. [SEO System](#14-seo-system) ★ core
15. [Coupons, Reviews, Wishlist, Compare](#15-coupons-reviews-wishlist-compare)
16. [Blog Module](#16-blog-module)
17. [Finance: Ledger, Summary, Reports](#17-finance-ledger-summary-reports)
18. [Gallery & Media](#18-gallery--media)
19. [Settings (admin-managed .env values)](#19-settings-admin-managed-env-values)
20. [Roles & Permissions](#20-roles--permissions)
21. [Dashboard & Analytics](#21-dashboard--analytics)
22. [Packages To Add](#22-packages-to-add)
23. [Per-Module Definition of Done](#23-per-module-definition-of-done)

---

## 1. Architecture Overview

API-first, with a Blade + Livewire admin/POS and a Blade public storefront. Business logic
lives in services; every money move posts to the ledger.

```
   PUBLIC STOREFRONT (web)        ADMIN PANEL + POS (/admin, /pos)
   Blade + Livewire 4             Blade + Livewire 4, HasMiddleware
        |                                |
        +----------------+---------------+
                         v
                 SERVICE LAYER (app/Services/*, DB transactions)
   Catalog · Purchasing · Production · Inventory · Pricing
   Quotations · Sales · Finance/LedgerService
                         |
   Eloquent Models · FormRequests · API Resources
   RBAC (fail-closed) · Audit log · Settings helpers
                         |
            MySQL  ·  Redis (queue/cache)  ·  Reverb (realtime)
```

Material & value flow:

```
  Purchase -> Raw materials -> Production (BOM) -> Finished goods -> Sale (web / POS / vendor)
                  |                                                        ^
                  +--------------- raw can be sold directly ---------------+

  Purchase, Production, and Sale all post to the LEDGER.
```

**Non-negotiable rules** (full detail in `ENGINEERING_CONVENTIONS.md`):

- Business logic in **Services**, wrapped in DB transactions. Controllers stay thin.
- All money operations **post to the ledger** via `LedgerService`.
- Stock changes **only** through `stock_movements` — never edit a stock number directly.
- All writes go through a **FormRequest** (`$request->validated()`, never `->all()`).
- API output uses the **Resource envelope** (`success` / `message` / `data` [+ `links`/`meta`]).
- Every action guarded by a `{resource}.{action}` permission, **fail-closed**.
- **No hardcoded** currency, date format, timezone, locale, pagination, or theme — use
  `format_money()`, `format_date()`, `per_page()`, `setting()`.
- Index every column used in a filter/sort/join. Eager-load. Paginate everything.

---

## 2. Module Map & Build Order

Build in dependency order. Each module ships with feature tests before merge.

| # | Module | Depends on | Notes |
|---|--------|-----------|-------|
| 0 | Settings + Roles/Permissions | — | foundation; needed by everything |
| 1 | Media / Gallery | Settings | item & blog images |
| 2 | Categories & Brands | Media | taxonomy |
| 3 | Attributes & Attribute Values | — | drives variants |
| 4 | **Items & Variants** | 1,2,3 | ★ unified catalog with role flags |
| 5 | Suppliers & Purchasing | 4 | restocks + moving-avg cost |
| 6 | Inventory & Stock Movements | 4,5 | single source of truth for stock |
| 7 | BOM & Production | 4,6 | assemble raw -> finished |
| 8 | Costing & Pricing | 5,7 | moving average + markup/override tiers |
| 9 | Customers | 0 | retail + wholesale/vendor |
| 10 | Quotations | 4,8,9 | quote -> convert to sale |
| 11 | Cart (web) | 4 | session/DB |
| 12 | Coupons | 4 | discounts |
| 13 | Sales — Web checkout & Orders | 6,8,11,12 | order state machine, channel=web |
| 14 | POS (no shift) | 6,8,9 | channel=pos, immediate payment |
| 15 | Vendor / Wholesale sales | 6,8,9 | channel=vendor, wholesale tier, credit |
| 16 | Payments | 13,14 | COD/cash/QR now; JazzCash/Easypaisa later |
| 17 | Finance: Ledger + Summary | 5,7,13 | source of truth |
| 18 | SEO layer | 4,16 | meta, slugs, sitemap, schema |
| 19 | Blog | 1,18 | posts, categories, tags |
| 20 | Reviews / Wishlist / Compare | 4 | web engagement |
| 21 | Reports | 13,17 | PDF/exports |
| 22 | Dashboard analytics | all | KPIs |

---

## 3. Database Schema

> This is the primary build reference. Conventions: money = `decimal(12,2)` (accounting
> `15,2`); quantities = `decimal(12,3)` (supports kg/litre/fractional); enums stored as
> `string` + validated with `in:`; dates/json/bool via casts; FK + every filter/sort/join
> column indexed. Stock is **only** mutated via `stock_movements`.

### 3.1 Catalog & items

```
brands
  id, name, slug (unique), logo_media_id NULL, description NULL,
  is_active bool, meta_title NULL, meta_description NULL, timestamps
  INDEX(is_active)

categories
  id, parent_id NULL FK->categories, name, slug (unique),
  image_media_id NULL, description NULL, sort_order int,
  markup_percent decimal(5,2) NULL,        -- category-level default markup (pricing)
  is_active bool, meta_title NULL, meta_description NULL, meta_image_media_id NULL,
  timestamps
  INDEX(parent_id), INDEX(is_active), INDEX(sort_order)

products   -- UNIFIED ITEMS: raw materials, manufactured goods, trading goods, services
  id, category_id FK, brand_id NULL FK,
  name, slug (unique), sku (unique),
  type ENUM-as-string('trading','manufactured','raw','service') default 'trading',
        -- trading      = bought and sold as-is
        -- manufactured = built from a BOM (assembled in-house)
        -- raw          = component/material consumed in production (may also be sold)
        -- service      = non-stock (e.g. fitting/labor charge)
  -- capability flags (defaults follow `type`, but can be overridden)
  is_stock_tracked  bool default true,     -- false for services
  is_purchasable    bool default true,     -- can appear on a purchase order
  is_manufacturable bool default false,    -- has a BOM (true for 'manufactured')
  is_sellable       bool default true,     -- can be sold on any channel
  is_web_listed     bool default false,    -- * shows in the PUBLIC WEBSITE catalog
  manufacture_mode  ENUM-as-string('to_stock','to_order') NULL,  -- only if manufacturable
  variant_mode      ENUM-as-string('simple','variable') default 'simple',
  short_description NULL, description LONGTEXT NULL,
  base_price decimal(12,2) NULL,           -- display "from" price = min(active variant retail)
  markup_percent decimal(5,2) NULL,        -- item-level markup override (pricing)
  is_active bool default true, is_featured bool default false,
  published_at timestamp NULL,             -- null = draft (web)
  -- SEO (see section 14)
  meta_title NULL, meta_description NULL, meta_keywords NULL,
  og_image_media_id NULL, canonical_url NULL, no_index bool default false,
  timestamps, softDeletes
  INDEX(type), INDEX(category_id), INDEX(brand_id),
  INDEX(is_active), INDEX(is_sellable), INDEX(is_web_listed),
  INDEX(is_featured), INDEX(published_at),
  COMPOSITE INDEX(is_web_listed, is_active, published_at, category_id)  -- website catalog query
```

> **What gets listed on the website** = `is_web_listed = true AND is_active = true AND
> is_sellable = true AND published_at <= now()`. The composite index above serves exactly
> this query. Everything else (raw materials, internal items) stays out of the storefront
> but is still purchasable, stockable, and sellable via POS/vendor.

### 3.2 Attributes & variants

```
attributes                              -- Size, Color, Capacity, Model
  id, name, code (unique), type ENUM-as-string('select','swatch','radio') default 'select',
  is_variation bool default true, sort_order int, timestamps  INDEX(is_variation)

attribute_values                        -- S/M/L, Red, 128GB, iPhone 15
  id, attribute_id FK, value, label, color_hex NULL, image_media_id NULL,
  sort_order int, timestamps  INDEX(attribute_id)

product_attribute                       -- which attributes a product varies on (pivot)
  product_id FK, attribute_id FK  PRIMARY(product_id, attribute_id)

product_variants                        -- the STOCKABLE + PRICED unit (raw = one 'simple' variant)
  id, product_id FK, sku (unique),
  -- pricing (two tiers; markup default + manual override)
  cost decimal(12,2) default 0,         -- MOVING-AVERAGE unit cost (system-maintained)
  retail_price decimal(12,2) default 0, -- web + POS
  wholesale_price decimal(12,2) NULL,   -- vendor channel
  compare_at_price decimal(12,2) NULL,  -- strikethrough "was"
  price_is_manual bool default false,   -- true = keep entered prices, ignore markup formula
  -- stock (mutated only via stock_movements)
  stock_quantity decimal(12,3) default 0,
  reserved_quantity decimal(12,3) default 0,  -- held by unpaid orders
  low_stock_threshold decimal(12,3) default 0,
  weight decimal(8,3) NULL, barcode NULL,
  image_media_id NULL, is_active bool default true, is_default bool default false,
  timestamps
  INDEX(product_id), INDEX(is_active), INDEX(retail_price),
  INDEX(stock_quantity), INDEX(barcode)

attribute_value_product_variant         -- variant <-> its defining values (pivot)
  product_variant_id FK, attribute_value_id FK
  PRIMARY(product_variant_id, attribute_value_id)  INDEX(attribute_value_id)

product_media
  id, product_id FK, media_id FK, sort_order int, is_primary bool  INDEX(product_id)
```

### 3.3 Suppliers & purchasing

```
suppliers
  id, name, company NULL, phone NULL, email NULL, address NULL, tax_number NULL,
  opening_balance decimal(15,2) default 0, is_active bool default true, notes NULL, timestamps
  INDEX(is_active)

purchases   -- goods received from a supplier; restocks + updates moving-avg cost
  id, purchase_number (unique), supplier_id FK,
  status ENUM-as-string('draft','received','cancelled') default 'draft',
  reference NULL,                       -- supplier invoice #
  purchase_date date,
  subtotal decimal(15,2), tax_total decimal(15,2), grand_total decimal(15,2),
  paid_total decimal(15,2) default 0,   -- drives supplier payable balance
  notes NULL, created_by FK, timestamps
  INDEX(supplier_id), INDEX(status), INDEX(purchase_date)

purchase_items
  id, purchase_id FK, product_variant_id FK,
  quantity decimal(12,3), unit_cost decimal(12,2), line_total decimal(15,2), timestamps
  INDEX(purchase_id), INDEX(product_variant_id)
```

### 3.4 Bill of Materials & production

```
boms   -- recipe for a manufacturable product
  id, product_id FK, product_variant_id NULL FK,   -- optional: BOM for a specific finished variant
  name NULL, output_quantity decimal(12,3) default 1,   -- finished units produced per run
  labor_cost decimal(12,2) default 0, overhead_cost decimal(12,2) default 0,
  is_active bool default true, version int default 1, timestamps
  INDEX(product_id), INDEX(is_active)

bom_items   -- components consumed per run
  id, bom_id FK, component_variant_id FK,          -- a raw / trading variant
  quantity decimal(12,3), waste_percent decimal(5,2) default 0, timestamps
  INDEX(bom_id), INDEX(component_variant_id)

production_orders   -- an assembly run: consumes components, produces finished stock
  id, production_number (unique), bom_id FK, product_variant_id FK,  -- finished variant produced
  quantity decimal(12,3),               -- finished units to produce
  status ENUM-as-string('draft','completed','cancelled') default 'draft',
  total_component_cost decimal(15,2) default 0, labor_cost decimal(12,2) default 0,
  overhead_cost decimal(12,2) default 0, unit_cost decimal(12,2) default 0,  -- resulting finished unit cost
  produced_at timestamp NULL, notes NULL, created_by FK, timestamps
  INDEX(status), INDEX(product_variant_id), INDEX(bom_id)

production_consumptions   -- per-component actuals at completion (traceability)
  id, production_order_id FK, component_variant_id FK,
  quantity decimal(12,3), unit_cost decimal(12,2), line_cost decimal(15,2)
  INDEX(production_order_id)
```

### 3.5 Inventory / stock movements

```
stock_movements   -- SINGLE SOURCE OF TRUTH for stock; never edit stock_quantity directly
  id, product_variant_id FK,
  type ENUM-as-string('purchase_in','sale_out','production_consume',
        'production_output','adjustment','return_in','transfer'),
  quantity decimal(12,3),               -- signed: + adds stock, - removes
  balance_after decimal(12,3),          -- resulting on-hand for audit
  unit_cost decimal(12,2) NULL,         -- cost at the moment of movement
  reference_type, reference_id,         -- polymorphic: purchase/order/production/adjustment
  reason NULL, created_by FK, timestamps
  INDEX(product_variant_id), INDEX(type),
  INDEX(reference_type, reference_id), INDEX(created_at)
```

### 3.6 Customers

```
customers
  id, user_id NULL FK,                  -- linked auth account for web customers (null for walk-in/vendor)
  name, phone NULL, email NULL, address NULL,
  type ENUM-as-string('retail','wholesale') default 'retail',   -- wholesale = vendor
  price_tier ENUM-as-string('retail','wholesale') default 'retail',
  opening_balance decimal(15,2) default 0,   -- receivable for credit/vendor sales
  is_active bool default true, notes NULL, timestamps
  INDEX(type), INDEX(user_id), INDEX(is_active)
```

### 3.7 Quotations

```
quotations
  id, quotation_number (unique), customer_id NULL FK,
  status ENUM-as-string('draft','sent','accepted','rejected','expired','converted') default 'draft',
  valid_until date NULL,
  price_tier ENUM-as-string('retail','wholesale') default 'retail',
  subtotal decimal(15,2), discount_total decimal(15,2), tax_total decimal(15,2), grand_total decimal(15,2),
  notes NULL, converted_order_id NULL FK,   -- the sale it became
  created_by FK, timestamps
  INDEX(status), INDEX(customer_id), INDEX(valid_until)

quotation_items   -- may contain raw OR finished variants
  id, quotation_id FK, product_variant_id FK,
  name_snapshot, description NULL,
  quantity decimal(12,3), unit_price decimal(12,2), line_total decimal(15,2)
  INDEX(quotation_id)
```

### 3.8 Sales (unified) & payments

```
orders   -- the UNIFIED SALE document: one row per sale, all channels
  id, order_number (unique),
  channel ENUM-as-string('web','pos','vendor') default 'web',
  customer_id NULL FK, user_id NULL FK,   -- user_id = web auth account
  quotation_id NULL FK,                   -- if converted from a quote
  price_tier ENUM-as-string('retail','wholesale') default 'retail',
  status ENUM-as-string('pending','paid','processing','shipped','delivered','completed','cancelled','refunded'),
  payment_method ENUM-as-string('cod','card','qr','cash','bank','credit'),
  payment_status ENUM-as-string('unpaid','partial','paid','partially_refunded','refunded'),
  subtotal decimal(15,2), discount_total decimal(15,2), tax_total decimal(15,2),
  shipping_total decimal(15,2), grand_total decimal(15,2), paid_total decimal(15,2) default 0,
  coupon_id NULL FK, currency default 'PKR', placed_at,
  created_by NULL FK,                     -- staff who rang it up (pos/vendor)
  notes NULL, timestamps
  INDEX(channel), INDEX(status), INDEX(payment_status),
  INDEX(customer_id), INDEX(placed_at)

order_items
  id, order_id FK, product_variant_id FK,
  name_snapshot, sku_snapshot, attributes_snapshot json,
  unit_price decimal(12,2), quantity decimal(12,3), line_total decimal(15,2),
  cost_snapshot decimal(12,2),            -- moving-avg cost at sale time -> COGS
  INDEX(order_id)

order_addresses   -- web/shipping; usually absent for pos
  id, order_id FK, type ENUM-as-string('billing','shipping'),
  name, phone, line1, line2 NULL, city, state, zip, country

payments
  id, order_id FK,
  gateway ENUM-as-string('cod','cash','manual_qr','jazzcash','easypaisa','bank','card'),
  amount decimal(15,2), status ENUM-as-string('pending','succeeded','failed','refunded'),
  transaction_ref NULL, payload json NULL, received_by NULL FK, timestamps
  INDEX(order_id), INDEX(status)
```

### 3.9 Commerce extras

```
carts        id, user_id NULL, session_id NULL, timestamps  INDEX(user_id), INDEX(session_id)
cart_items   id, cart_id FK, product_variant_id FK, quantity decimal(12,3), price_snapshot, timestamps
coupons      id, code (unique), type ENUM-as-string('percent','fixed'), value decimal(12,2),
             min_subtotal NULL, max_uses NULL, used_count int default 0,
             starts_at NULL, expires_at NULL, is_active bool, timestamps  INDEX(code), INDEX(is_active)
reviews      id, product_id FK, user_id FK, rating tinyint, title NULL, body,
             is_approved bool default false, timestamps  INDEX(product_id), INDEX(is_approved)
wishlists    id, user_id FK, product_id FK, timestamps  UNIQUE(user_id, product_id)
```

### 3.10 Content & system

```
blog_posts        id, author_id FK, title, slug (unique), excerpt NULL, body LONGTEXT,
                  cover_media_id NULL, status ENUM-as-string('draft','published'), published_at NULL,
                  meta_title, meta_description, og_image_media_id, no_index bool,
                  timestamps, softDeletes  INDEX(status), INDEX(published_at)
blog_categories   id, name, slug (unique), parent_id NULL, sort_order
blog_tags         id, name, slug (unique)
blog_post_category post_id, category_id   |   blog_post_tag  post_id, tag_id
media             id, disk, path, mime, size, width NULL, height NULL, alt NULL,
                  title NULL, folder NULL, uploaded_by FK, timestamps  INDEX(folder)
settings          id, group, key, value (text/json), type, UNIQUE(group, key)
activity_logs     (audit) — see conventions section 4.4
ledger_entries    see section 17
```

---

## 4. Items & Catalog Model ★

One `products` table is the **whole catalog** — raw, manufactured, trading, service — kept
coherent by a `type` plus capability flags. This is what lets you stock a bolt, assemble it
into a finished unit, and sell either one from the same machinery.

### 4.1 Type vs. flags

`type` is the human classification and sets sensible flag defaults; the flags are the real
behavior switches and can be overridden per item.

| `type` | Typical flags | Example |
|--------|---------------|---------|
| `trading` | purchasable, sellable, stock-tracked, web-listed | a finished phone bought to resell |
| `manufactured` | manufacturable, sellable, stock-tracked, web-listed (+ `manufacture_mode`) | an assembled kit you build |
| `raw` | purchasable, stock-tracked, *not* web-listed (sellable optional) | a component/material |
| `service` | sellable, *not* stock-tracked | a fitting/labor charge line |

### 4.2 The website-listing rule

`is_web_listed` is the gate for the storefront. A raw material can be `is_sellable = true`
(so it can go on a quote or a POS sale) while `is_web_listed = false` (never shown online).
The storefront catalog query is always:

```php
Product::where('is_web_listed', true)->where('is_active', true)
       ->where('is_sellable', true)->whereNotNull('published_at')
       ->where('published_at', '<=', now());
```

### 4.3 Stock & cost live on the variant

Every product — even a raw material or a simple trading good — has **at least one variant**.
The variant carries stock, moving-average cost, and the two price tiers. A raw material is a
product with `variant_mode = simple` and one default variant. This keeps purchasing, BOMs,
production, stock movements, and sales uniform: they always reference a `product_variant_id`.

---

## 5. Product & Variant System

> The attribute-driven variant details below are the working baseline. Product/variant
> specifics (UI, generation edge-cases) are yours to refine — the schema in section 3.2 is stable.

### 5.1 Core concepts

- A **Product** (item) is the catalog entry (name, description, images, SEO, flags).
- An **Attribute** is a dimension of variation: `Color`, `Size`, `Capacity`, `Model`.
- An **Attribute Value** is one option: `Red`, `XL`, `128GB`, `iPhone 15`.
- A **Variant** is **one stockable combination** of attribute values. **Stock, cost, and price
  live on the variant — never on the product.**

> **Golden rule:** every product has at least one variant. A `simple` product is a product with
> a single default variant. Cart, inventory, production, orders, and the ledger always reference
> a `product_variant_id`, never a bare product.

### 5.2 Variant modes

| `variant_mode` | Meaning | Variants |
|----------------|---------|----------|
| `simple` | No options (a cable, a raw bolt) | 1 default variant holds price + stock + cost |
| `variable` | Has options (a phone in colors/capacities) | N variants, one per chosen combination |

### 5.3 How a variant maps to its values

```
product_variants                          attribute_value_product_variant (pivot)
+----+-----------+--------+--------+       +------------------+-------------------+
| id | product_id| retail | stock  |       | product_variant_id| attribute_value_id|
+----+-----------+--------+--------+       +------------------+-------------------+
| 11 | 5         | 1100.00| 12     | <---- | 11               | 3  (Color=Red)    |
|    |           |        |        |       | 11               | 7  (Capacity=128) |
+----+-----------+--------+--------+       +------------------+-------------------+
```

To resolve a chosen combination, match the **set** of `attribute_value_id`s. Storefront
filters query `attribute_value_product_variant` joined to active variants.

### 5.4 Generating variants

The `VariantGenerator` service builds the cartesian product of selected attribute values and
upserts variants inside a transaction. **Never hard-delete a variant with sales/production
history — deactivate it.** Order and quotation items snapshot name/sku/attributes so history
stays readable.

### 5.5 Storefront product page & filters

- Show the default variant's price/stock; swatches/dropdowns for attributes; resolve the
  selected variant client-side from a preloaded JSON map; "Add to Cart" sends `product_variant_id`.
- Listing filters: brand, attribute values, price range, on-sale, in-stock; sort by featured/
  price/newest/rating; paginate with `per_page()`.

---

## 6. Purchasing & Suppliers

Buying raw materials and trading goods from suppliers, which restocks variants and updates
moving-average cost.

- A **purchase** has a supplier, a date, line items (`product_variant_id`, qty, unit cost), and
  a status (`draft` -> `received` -> optionally `cancelled`).
- **On receive** (`PurchaseService::receive()`, in a transaction): for each line, write a
  `stock_movements` row (`purchase_in`, +qty), recompute the variant's **moving-average cost**
  (section 9), and post to the ledger (Inventory debit; Cash or Accounts Payable credit). `paid_total`
  vs `grand_total` drives the supplier's payable balance.
- Only items with `is_purchasable = true` can be added to a purchase.
- Cancelling a received purchase reverses the movements and ledger entries (never deletes them).

---

## 7. Bill of Materials & Production

This is the "buy pieces -> assemble -> sell the made product" capability.

### 7.1 BOM (the recipe)

A manufacturable product has a **BOM**: which component variants and how many are consumed to
produce `output_quantity` finished units, plus `labor_cost` and `overhead_cost`. Components are
raw/trading variants. `waste_percent` accounts for material lost in assembly.

**Computed BOM unit cost** = `(sum(component.cost × qty × (1 + waste%)) + labor + overhead) / output_quantity`.
This feeds the suggested price (section 9) before any production has run.

### 7.2 Production (the assembly run)

A **production order** references a BOM and a finished variant, with a quantity to produce and a
status (`draft` -> `completed` -> optionally `cancelled`).

**On complete** (`ProductionService::complete()`, in a transaction):

1. For each component: write `stock_movements` (`production_consume`, -qty) at the component's
   current moving-average cost, and record a `production_consumptions` row.
2. Compute finished `unit_cost` = total consumed cost + labor + overhead, / quantity produced.
3. Increment the finished variant: `stock_movements` (`production_output`, +qty) at that
   `unit_cost`, which updates the finished variant's moving-average cost.
4. Post to the ledger: Finished Inventory debit; Raw Inventory + Labor + Overhead credit.

### 7.3 Both assembly modes

- `manufacture_mode = to_stock`: run production batches in advance; finished stock sits ready;
  sales just decrement it like any other item.
- `manufacture_mode = to_order`: don't pre-build; when the item sells, the fulfillment step
  triggers a production order to assemble the needed quantity, then ships/hands it over.

Same production mechanism, different timing — the ledger and stock history are identical.

---

## 8. Inventory & Stock Movements

`product_variants.stock_quantity` is a **cached running total**; the truth is the append-only
`stock_movements` log. Every change — purchase, sale, production consume/output, adjustment,
return, transfer — is one signed row carrying `balance_after`, `unit_cost`, and a polymorphic
reference. Services never write `stock_quantity` directly; they call `StockService::move()`,
which writes the movement and updates the cached total atomically.

- **Low stock** when `stock_quantity <= low_stock_threshold`; surface Total / Low / Critical /
  Stock Value cards on the inventory dashboard (Image 6 style).
- **Adjustments** (damage, count corrections) require a reason and post to the ledger as an
  inventory write-off/gain.
- **Negative stock** is rejected by default (`setting('inventory','allow_negative_stock')`).
- Stock valuation (Stock Value KPI) = `sum(stock_quantity × cost)` across active variants.

---

## 9. Costing & Pricing

### 9.1 Costing — moving average

Each variant keeps one **moving-average `cost`**, recomputed on every stock-in:

```
new_cost = ( (old_qty × old_cost) + (received_qty × received_unit_cost) )
           / (old_qty + received_qty)
```

Example: 10 @ Rs100 then 10 @ Rs120 -> 20 units at Rs110. This cost is used for raw consumption
in production and for `cost_snapshot` (COGS) on every sale. The method is a Setting
(`inventory.costing_method`) so it can change later without touching call sites — all costing
goes through `CostingService`.

### 9.2 Pricing — markup default + manual override, two tiers

Suggested retail price = `cost × (1 + markup%)`, where `markup%` resolves **item -> category ->
global** (`setting('pricing','default_markup_percent')`). Wholesale uses
`setting('pricing','wholesale_markup_percent')` (or its own override). A **manual price wins**:
when `price_is_manual = true`, the entered `retail_price` / `wholesale_price` are kept and the
formula is not applied.

Two tiers select by channel/customer:

| Channel | Tier | Price used |
|---------|------|-----------|
| web, pos | retail | `retail_price` |
| vendor | wholesale | `wholesale_price` (falls back to retail if null) |

A customer flagged `type = wholesale` always prices at the wholesale tier, even at the POS.
`PricingService` is the single place that resolves the price for a (variant, channel, customer)
tuple, so quotes, cart, POS, and orders all agree.

---

## 10. Customers

`customers` is the buyer record for all channels. Web sign-ups create a linked `users` (auth)
row plus a `customers` row; POS walk-ins use a default "Walk-in" customer (`setting('pos',
'default_customer')`); vendors are `type = wholesale` and price at the wholesale tier. Credit /
vendor sales accrue a receivable via `opening_balance` + unpaid order totals.

---

## 11. Quotations

A quotation is a draft sale you hand a customer before they commit. It can mix **raw and
finished** variants, prices at the chosen tier (retail/wholesale), and has a validity window.

- Statuses: `draft -> sent -> accepted | rejected | expired`, and `converted` once turned into a sale.
- **Accepting converts** to an `orders` row (`QuotationService::convert()`): copies line items,
  sets `quotation_id` and `converted_order_id`, and opens the sale. **No stock moves until the
  sale itself is created/fulfilled** — a quote never touches inventory.
- Generate a branded PDF via DomPDF. Numbering from `setting('quotation','number_prefix')`,
  default validity from `setting('quotation','default_validity_days')`.

---

## 12. Sales Channels: Web, POS, Vendor

All three are one `orders` document, distinguished by `channel`. `SalesService::place()` is the
single entry point and always runs in a transaction: resolve prices (section 9), compute totals,
create `orders` + `order_items` (with `cost_snapshot`), move stock (`sale_out`), record payment,
and post to the ledger (revenue + tax + shipping; and COGS from `cost_snapshot`).

### 12.1 Web (`channel = web`)

Full storefront flow (Image 3): cart -> billing/shipping address -> coupon -> shipping method ->
payment (COD / card / QR). Retail tier. COD starts `pending`/`unpaid`, marked paid on delivery;
card/QR move to `paid` on confirmed payment. Stock may be **reserved** at checkout
(`reserved_quantity`) and decremented on payment.

State machine:

```
pending --paid--> paid --> processing --> shipped --> delivered --> completed
   +--------------------------------------------> cancelled
                                        paid --> refunded
```

### 12.2 POS (`channel = pos`) — no shift management

A fast counter-sale screen (search/scan item -> add lines -> pick customer or Walk-in -> take
payment -> complete -> print receipt). **No cashier session / cash-drawer open-close.** A POS sale
is simply an order with `channel = pos`, immediate `payment_status = paid` (cash/card/QR), stock
decremented on completion, `created_by` = the staff user. Retail tier (or wholesale if the
chosen customer is a vendor). Receipt via DomPDF; footer from `setting('pos','receipt_footer')`.

### 12.3 Vendor / Wholesale (`channel = vendor`)

Sell to a wholesale customer at the wholesale tier, often **on credit** — `payment_method =
credit`, `payment_status = unpaid|partial`, balance tracked as a receivable on the customer.
Usually originates from an accepted quotation. No storefront; created by sales staff in admin.

---

## 13. Payments

Abstract behind a contract so gateways are swappable. **Live now:** COD, cash (POS), and manual
QR. **Planned:** JazzCash and Easypaisa — to be implemented without touching sales logic.

```php
interface PaymentGateway {
    public function charge(Order $order): PaymentResult;     // redirect URL or immediate result
    public function verify(array $callback): PaymentResult;  // webhook/return verification
}
```

- `CodGateway` — web COD: order `pending`/`unpaid`; confirmed on delivery.
- `CashGateway` — POS cash: immediate `paid`.
- `QrGateway` — `simple-qrcode` renders the QR; confirmed manually by staff or via callback.
- `JazzCashGateway` / `EasypaisaGateway` — **stubs now**; implement `charge()`/`verify()` later.
  Every attempt is stored in `payments` with `payload` json + `transaction_ref`.

Enabled methods and (encrypted) credentials come from Settings (section 19 / conventions 4.3).

---

## 14. SEO System ★

SEO is a **cross-cutting layer** applied uniformly to web-listed products, categories, shop
pages, and blog posts. Build it once; every public model plugs in. (Raw/internal items aren't
web-listed, so they're never indexed.)

### 14.1 Per-record SEO fields (in schema)

Public entities (`products` where web-listed, `categories`, `brands`, `blog_posts`) carry
`slug`, `meta_title`, `meta_description`, `og_image_media_id`, `no_index`, and where relevant
`canonical_url`, with fallbacks:

| Field | Fallback |
|-------|----------|
| `meta_title` | record name + ` — ` + `setting('seo','title_suffix')` |
| `meta_description` | trimmed `short_description`/`excerpt` (<=160 chars) |
| `og_image` | `og_image_media_id` -> primary image -> `setting('seo','default_og_image')` |
| `canonical_url` | the record's own absolute URL |

### 14.2 Slugs

Use **spatie/laravel-sluggable** on every public model. Slugs are unique, generated from the
name, and **immutable once published** (store old slug + 301-redirect if you must change one).

URL scheme:

```
/                       home
/shop                   web-listed products (filters via query string)
/category/{slug}        category landing
/brand/{slug}           brand landing
/product/{slug}         product detail
/blog                   blog index
/blog/{slug}            post detail
/blog/category/{slug}   blog category
/blog/tag/{slug}        blog tag
```

### 14.3 Meta tags — one reusable component

A single `<x-seo.meta :seo="$seo" />` renders title, description, canonical, robots, Open Graph,
and Twitter cards from a `SeoData` DTO produced by `SeoService` (`forProduct`, `forCategory`,
`forPost`, `forShop`). Each controller builds `SeoData` via the service and the layout renders
the component in `<head>`. Site name from `setting('general','app_name')`.

### 14.4 Structured data (JSON-LD)

| Page | Schema.org type | Key properties |
|------|-----------------|----------------|
| Product detail | `Product` + `Offer` + `AggregateRating` | name, image, sku, brand, price, availability, currency=PKR, reviewCount/ratingValue |
| Category / shop | `CollectionPage` + `ItemList` | product URLs |
| Blog post | `Article` / `BlogPosting` | headline, image, datePublished, author, publisher |
| All pages | `BreadcrumbList` | breadcrumb trail |
| Site-wide | `Organization` + `WebSite` (with `SearchAction`) | logo, name, social links from Settings |

`Offer.price` / `priceCurrency` / `availability` come from the **default/selected variant's
retail price + stock**, not the product.

### 14.5 Sitemap & robots

**spatie/laravel-sitemap** generates `sitemap.xml` on a queued schedule — web-listed products,
categories, brands, published posts (skip `no_index` and drafts). `robots.txt` points to the
sitemap and disallows `/admin`, `/pos`, `/cart`, `/checkout`, `/account`.

### 14.6 SEO Settings (group `seo`)

`title_suffix`, `default_meta_description`, `default_og_image`, `google_analytics_id`,
`google_site_verification`, `facebook_app_id`, `social_links[]`, `organization_name`,
`organization_logo`. Read via `setting('seo', ...)`.

### 14.7 SEO Definition of Done (per public page)

- [ ] Unique `<title>` + meta description (with fallbacks).
- [ ] Canonical URL; consistent pagination canonicalization.
- [ ] OG + Twitter tags with a real image.
- [ ] Correct JSON-LD for the page type + breadcrumb schema.
- [ ] Clean, unique, lowercase, hyphenated slug.
- [ ] In sitemap (if public) or `no_index`; drafts never indexed.

---

## 15. Coupons, Reviews, Wishlist, Compare

(Web-channel engagement features.)

- **Coupons:** `percent` or `fixed`; validate active window, min subtotal, max uses; apply in
  `SalesService`; increment `used_count` atomically. Web channel primarily.
- **Reviews:** one per user per product, `is_approved` defaults false -> moderation; only approved
  reviews count toward `AggregateRating` (section 14.4).
- **Wishlist:** `user_id` + `product_id` unique pivot.
- **Compare:** session list of product ids; compares spec/attribute tables.

---

## 16. Blog Module

Posts with categories + tags, cover image, draft/published, scheduled `published_at`. Full SEO
via the shared layer (section 14) emitting `Article` schema. Storefront blog index/category/tag
pages (Image 5). Admin CRUD with a rich-text body (sanitize HTML on save).

---

## 17. Finance: Ledger, Summary, Reports

**The ledger is the source of truth.** Every money event runs through `LedgerService` inside a
transaction and writes balanced entries.

```
ledger_entries
  id, entry_date,
  account (e.g. 'sales_revenue','cogs','tax_payable','cash','bank','accounts_receivable',
       'accounts_payable','inventory_raw','inventory_finished','labor','overhead',
       'inventory_adjustment','refunds','shipping_income','gateway_fees'),
  debit decimal(15,2) default 0, credit decimal(15,2) default 0,
  reference_type, reference_id,   -- polymorphic: order/payment/purchase/production/adjustment
  memo, created_by, timestamps
  INDEX(account), INDEX(entry_date), INDEX(reference_type, reference_id)
```

Event -> postings:

| Event | Debit | Credit |
|-------|-------|--------|
| Purchase received | Inventory (raw/finished) | Cash / Accounts Payable |
| Production completed | Inventory Finished | Inventory Raw, Labor, Overhead |
| Sale paid (any channel) | Cash / Bank / Accounts Receivable | Sales Revenue, Tax Payable, Shipping Income |
| COGS on sale | COGS | Inventory |
| Refund / return | Sales Revenue (contra); Inventory (if restocked) | Cash; COGS (if restocked) |
| Stock adjustment (write-off) | Inventory Adjustment | Inventory |
| Gateway fee | Gateway Fees | Cash |

**Financial summary:** revenue, COGS, gross profit, refunds, net — all derived from
`ledger_entries`, never recomputed ad-hoc. **Reports:** sales by day/channel/category/product,
best sellers, low stock, stock valuation, purchases by supplier, production cost, receivables/
payables, tax collected, profit. Export via DomPDF (PDF) and CSV/XLSX. Respect `format_money` /
`format_date`.

---

## 18. Gallery & Media

**spatie/laravel-medialibrary** + **intervention/image**. One `media` model backs product/
variant images, category/brand images, blog covers, and the standalone Gallery. Generate
responsive conversions (thumb/medium/large) and store `alt`/`title` (alt matters for SEO).
Uploads use `<x-file-drop>` (conventions 1.4).

---

## 19. Settings (admin-managed .env values)

Admin controls runtime config from `/admin/settings/*` (key-value `settings` table via the
`Setting` model; secrets `encrypted` + keep-on-blank). Groups:

| Group | Keys (examples) |
|-------|-----------------|
| `general` | app_name, logo, favicon, currency, currency_symbol, currency_position, decimals, timezone, locale, items_per_page, theme |
| `mail` | mailer, host, port, username, password*, encryption, from_address, from_name |
| `inventory` | costing_method (default `moving_average`), allow_negative_stock, low_stock_alerts, default_manufacture_mode |
| `pricing` | default_markup_percent, wholesale_markup_percent, prices_tax_inclusive, recalc_prices_on_cost_change |
| `pos` | default_customer, receipt_footer, quick_payment_methods, print_receipt |
| `quotation` | number_prefix, default_validity_days, terms_text |
| `numbering` | order_prefix, purchase_prefix, production_prefix, quotation_prefix, formats |
| `seo` | title_suffix, default_meta_description, default_og_image, analytics_id, site_verification, social_links |
| `payment` | cod_enabled, cash_enabled, qr_enabled, jazzcash_enabled+creds*, easypaisa_enabled+creds* |
| `shipping` | flat_rate, free_over, methods |
| `tax` | enabled, rate, inclusive |
| `social_login` | microsoft_enabled + creds* |
| `store` | address, phone, support_email, business_hours |

`*` = secret -> encrypted at rest, masked in UI/API, never logged (conventions 4.3).
**SettingsApplier** pushes runtime-affecting values (mail, timezone, locale) into Laravel config
at boot. Read everywhere via `setting()` / `format_money()` / `format_date()` / `per_page()`.

---

## 20. Roles & Permissions

spatie/laravel-permission, fail-closed (conventions section 4). Permission name = resource URI
(plural) + verb. Resources and verbs:

```
products.{view,create,edit,delete}      variants.{edit}
categories.* brands.* attributes.*
suppliers.*  purchases.{view,create,edit,delete,receive}
boms.*       production.{view,create,edit,delete,complete}
stock.{view,adjust,transfer}
customers.*  quotations.{view,create,edit,delete,convert}
pos.{access,sell,refund}                orders.{view,edit,refund,fulfil}
coupons.*    reviews.{view,moderate}
blog-posts.* gallery.*
reports.{view,export}                   ledger.{view}
settings.{view,edit}                    users.* roles.*
```

Suggested roles: `super-admin` (Gate::before bypass), `admin`, `catalog-manager`,
`procurement` (suppliers/purchases), `production-manager` (boms/production), `inventory-manager`
(stock), `cashier` (pos), `sales-rep` (quotations/vendor orders), `order-manager`, `accountant`
(ledger/reports), `editor` (blog), `customer`. Add permissions to `RolePermissionSeeder::$groups`
the moment a module is done, then re-seed.

---

## 21. Dashboard & Analytics

Admin home KPIs: today/period revenue (by channel), orders by status, AOV, gross profit, top
products, low-stock count, stock valuation, recent orders/quotations, receivables/payables,
revenue & profit charts. Pull from ledger + orders + stock; cache expensive aggregates (Redis),
refresh via queued job. Live order/POS ping via Reverb.

---

## 22. Packages To Add

Already installed: laravel/framework, livewire, reverb, predis, dompdf, simple-qrcode,
socialite(+microsoft), spatie/laravel-permission, tinker.

Add:

```bash
composer require spatie/laravel-medialibrary    # media/gallery + conversions
composer require intervention/image             # image processing
composer require spatie/laravel-sluggable        # slugs (SEO)
composer require spatie/laravel-sitemap          # sitemap.xml (SEO)
composer require spatie/laravel-model-states     # order/quote/purchase/production states (optional)
composer require spatie/laravel-backup --dev     # prod backups (optional)
# laravel/scout + meilisearch — only if catalog search needs to scale later
```

---

## 23. Per-Module Definition of Done

A module is "done" only when **all** of these hold:

- [ ] Migration(s) with indexes on every filter/sort/join column.
- [ ] Eloquent model with casts, relations, scopes.
- [ ] FormRequest(s); writes persist only `validated()`.
- [ ] Service class for business logic (DB transactions; money -> ledger; stock -> `stock_movements`).
- [ ] API Resource(s) with the standard envelope (if exposed via API).
- [ ] Web controller(s) in `app/Http/Controllers/Admin/`, `HasMiddleware`, `can:{resource}.{action}`.
- [ ] Blade/Livewire views extending `layouts.admin` (or POS layout); storefront views as needed.
- [ ] Permissions added to `RolePermissionSeeder::$groups` and re-seeded.
- [ ] Settings helpers used (no hardcoded currency/date/timezone/pagination/markup).
- [ ] Public pages: full SEO (section 14.7) — meta, canonical, OG, JSON-LD, sitemap.
- [ ] Secrets encrypted + masked + never logged.
- [ ] Audit logging on mutations.
- [ ] Feature tests in `tests/Feature/`; CI green.
- [ ] Wired into `config/navigation.php`.

---

_Keep this document updated as modules ship. It pairs with `ENGINEERING_CONVENTIONS.md`._