---
title: "Sprint 08: In-Store POS Touch Interface, Multi-Vendor Marketplace Split-Pay & Subscriptions Engine"
sprint: 8
status: "in-progress"
tags: ["pos", "marketplace", "multi-vendor", "subscriptions", "recurring", "bopis"]
---

# Sprint 08: In-Store POS Touch Interface, Multi-Vendor Marketplace Split-Pay & Subscriptions Engine

## Objective
Engineer enterprise omnichannel capabilities for SureSalez Core and SureSalez Theme:
1. In-Store Touch-First Point of Sale (POS) register interface with barcode scanner support, split-tender checkout (cash + wallet + card), thermal receipt formatting, and BOPIS (Click-and-Collect) pickup fulfillment.
2. Multi-Vendor Marketplace platform with line-item cart splitting, automated commission deduction ledger, and vendor portal controller.
3. Native Recurring Subscriptions Engine with "Subscribe & Save" discount tiers, tokenized background renewal dispatcher, automated dunning retry cycles (24h/48h/72h), and customer self-service management (pause/skip/cancel).

---

## Architectural Guardrails & Invariants
- **Decoupled Architecture**: All POS cashier logic, vendor commission calculations, and recurring billing engines reside strictly inside `plugins/suresalez-core/modules/pos-subscriptions/`.
- **Pure Presentation Theme**: `themes/suresalez-theme/` must remain a 100% logic-free FSE block theme with strictly 0 PHP files, providing only semantic mount points (`#suresalez-pos-screen-mount`, `#suresalez-vendor-portal-mount`, `#suresalez-subscriptions-mount`).
- **Zero-Bloat Gating**: When `pos-subscriptions` is toggled OFF, 0 POS endpoints, 0 vendor split hooks, 0 cron jobs, and 0 CSS/JS assets are enqueued in the DOM.
- **Data Integrity & Interoperability**:
  - Uses `dbDelta` in `class-database.php` (schema bumped to `v1.7.0`) with composite indexes on register IDs, vendor IDs, and subscription tokens.
  - Connects POS registers to the 10 global fulfillment hubs (`location_id`).
  - Integrates split-tender payments with Sprint 02's `suresalez_wallet_transactions`.

---

## Detailed Technical Specifications

### 1. Database Schemas (`class-database.php` v1.7.0)
1. `suresalez_pos_registers`:
   - `id`, `location_id` (FK to `suresalez_inventory_locations`), `register_name`, `cashier_user_id`, `opening_cash`, `closing_cash`, `status` (`open`, `closed`), `opened_at`, `closed_at`.
2. `suresalez_pos_transactions`:
   - `id`, `register_id`, `order_id`, `payment_method` (`cash`, `split_wallet_card`, `external_terminal`), `amount_tendered`, `change_due`, `receipt_number`, `created_at`.
3. `suresalez_marketplace_vendors`:
   - `id`, `user_id`, `store_name`, `slug`, `commission_rate` (e.g. 15.00%), `balance_available`, `balance_pending`, `payout_method_details` (JSON), `status` (`active`, `suspended`), `created_at`.
4. `suresalez_vendor_commissions`:
   - `id`, `order_id`, `vendor_id`, `line_item_id`, `gross_amount`, `commission_fee`, `net_vendor_payout`, `status` (`pending`, `available`, `paid`), `created_at`.
5. `suresalez_subscriptions`:
   - `id`, `subscription_token`, `customer_id`, `product_id`, `variation_id`, `billing_interval` (`weekly`, `monthly`, `quarterly`), `discount_percentage`, `next_renewal_date`, `status` (`active`, `paused`, `cancelled`, `dunning`), `card_token_hash`, `created_at`, `updated_at`.
6. `suresalez_subscription_logs`:
   - `id`, `subscription_id`, `renewal_order_id`, `attempt_count`, `status` (`success`, `failed`), `error_message`, `attempted_at`.

### 2. Backend Domain Engines (`plugins/suresalez-core/modules/pos-subscriptions/`)
- `class-pos-terminal.php`:
  - Register open/close cash reconciliation.
  - Barcode lookup & fast search for products.
  - Exact change & split payment tender calculation (wallet balance deduction + external card).
  - ESC/POS thermal receipt formatting (ASCII + HTML view) with line item breakdown, tax details, and barcode.
  - BOPIS (Click-and-Collect) pickup verification and order status transition to `completed`.
- `class-marketplace-vendor.php`:
  - Multi-vendor cart partitioning: splits order line items into discrete vendor packages.
  - Commission calculation: platform commission rate deduction (e.g. 15% platform cut, 85% vendor payout).
  - Vendor earnings ledger & withdrawal request management.
  - REST endpoints: `GET /wp-json/suresalez/v1/vendor/dashboard`, `POST /wp-json/suresalez/v1/vendor/payout-request`.
- `class-subscription-engine.php`:
  - "Subscribe & Save" discount calculation across billing intervals (e.g. weekly -15%, monthly -10%, quarterly -5%).
  - Daily renewal scheduler: scans `next_renewal_date <= NOW()`, initiates renewal order, and applies stored card token.
  - Dunning logic: on payment failure, transitions status to `dunning`, records attempt log, schedules retries at 24h, 48h, 72h, and auto-cancels if retry limit (3) is exceeded.
  - Customer self-service lifecycle: skip next delivery (`next_renewal_date + interval`), pause subscription, or cancel.
  - REST endpoints: `POST /wp-json/suresalez/v1/subscriptions/{id}/skip`, `POST /wp-json/suresalez/v1/subscriptions/{id}/pause`.
- `class-pos-subscriptions-module.php`:
  - Master module class extending `Module_Base`.
  - Registers REST endpoints, conditional hooks, and enqueues CSS/JS assets.

### 3. Frontend Client Assets & Themes
- `assets/css/pos-screen.css` & `assets/js/pos-screen.js`: Touch UI with product catalog, cart summary, numpad calculator, split-pay modal, and printable receipt.
- `assets/css/vendor-portal.css` & `assets/js/vendor-portal.js`: Vendor dashboard with sales stats, commission breakdown, and payout withdrawal button.
- `assets/css/subscription-portal.css` & `assets/js/subscription-portal.js`: Customer subscription management cards with 1-click skip, pause, and cancellation.
- Theme mount points in `themes/suresalez-theme/templates/index.html`:
  - `#suresalez-pos-screen-mount`
  - `#suresalez-vendor-portal-mount`
  - `#suresalez-subscriptions-mount`

### 4. Verification Suite
- Unit test suite: `tests/test-sprint-08.cjs`.
- Master test suite: `tests/verify-engine.cjs`.
- Headless Edge DOM validation: `tests/browser-test-runner.html`.
