---
title: "Sprint 07: B2B Wholesale Commerce, Organization Accounts & RFQ Quote Engine"
sprint: 7
status: "completed"
tags: ["b2b", "wholesale", "rfq", "company-accounts", "tier-pricing", "net-terms", "bulk-order"]
completed_at: "2026-10-14"
---

# Sprint 07: B2B Wholesale Commerce, Organization Accounts & RFQ Quote Engine

## 1. Executive Summary & Architectural Achievements
Sprint 07 implements an enterprise-grade B2B wholesale commerce, multi-seat corporate organization, contracted tiered pricing, Net 15/30/60 purchase order checkout, quick bulk matrix CSV importer, and Request-For-Quote (RFQ) negotiation engine for **SureSalez Core** (`plugins/suresalez-core/`) and **SureSalez Theme** (`themes/suresalez-theme/`).

All business logic, database tables, and REST API controllers live strictly within `plugins/suresalez-core/modules/b2b-wholesale/`, dynamically gated behind `SureSalez_Module_Manager`. When the module is toggled OFF, zero B2B price overrides, wholesale checkout gates, or RFQ scripts execute or load into the DOM. The presentation theme remains 100% logic-free with strictly 0 PHP files.

---

## 2. Core Deliverables Implemented

### A. Database Schemas (`class-database.php` v1.6.0)
- `{$wpdb->prefix}suresalez_b2b_companies`: Corporate organization records with tax ID (EIN/GSTIN/VAT), approval status (`pending`, `approved`, `suspended`), credit limit, payment terms (`prepaid`, `net_15`, `net_30`, `net_60`), and managerial approval threshold.
- `{$wpdb->prefix}suresalez_b2b_users`: Multi-seat company user hierarchy mapping seats to roles (`admin`, `purchaser`, `billing`), individual order spending limits, and active seat toggles.
- `{$wpdb->prefix}suresalez_b2b_pricelists`: Contract price list engine with fixed price or percent-off matrices, volume break tiers (e.g., 1-9 @ -20%, 10-49 @ -30%, 50+ @ -40%), and company-specific or global wholesale scopes.
- `{$wpdb->prefix}suresalez_b2b_quotes`: Request For Quote (RFQ) lifecycle tracking (`RFQ-YYYYMMDD-XXXX`), company ID, customer ID, items JSON, subtotal, freight overrides, total quoted, status (`submitted`, `under_review`, `counter_offered`, `accepted`, `expired`, `converted`), and secure 1-click acceptance token (`quote_token`).
- `{$wpdb->prefix}suresalez_b2b_orders`: Corporate order tracking recording WooCommerce order ID, company ID, mandatory PO number, payment terms, due date, approval status (`approved`, `pending_manager_approval`, `rejected`), and approving manager user ID.

### B. Backend B2B Engines (`plugins/suresalez-core/modules/b2b-wholesale/`)
1. **B2B Organization & Approval Pipeline (`class-b2b-organization.php`)**:
   - Multi-country business tax ID validator (US EIN, Indian GSTIN, UK VAT).
   - Order approval interceptor: automatically flags orders exceeding individual purchaser spending limits or company approval thresholds as `pending_manager_approval`.
   - Managerial order approval REST API: `POST /wp-json/suresalez/v1/b2b/orders/{id}/approve`.
   - Company profile REST API: `GET /wp-json/suresalez/v1/b2b/company/{id}`.

2. **Dynamic Wholesale Pricing & MOQ Engine (`class-b2b-pricing.php`)**:
   - Contextual price evaluator replacing standard retail MSRP with contracted volume tier pricing for authenticated B2B accounts.
   - Preserves public retail MSRP unaffected for public DTC shoppers.
   - Minimum Order Quantity (MOQ) & case-pack increment validator: enforces purchasing rules in cart with clear error notices.
   - Integrated with WooCommerce filters: `woocommerce_get_price_html`, `woocommerce_before_calculate_totals`, `woocommerce_check_cart_items`.

3. **Net Terms & Purchase Order Checkout (`class-b2b-checkout.php`)**:
   - Injects B2B Net Terms (`net_15`, `net_30`, `net_60`) dynamically into WooCommerce checkout.
   - Credit limit gate: calculates current open invoice balances and blocks Net Terms if remaining credit line is exceeded.
   - Mandatory PO Number collection and payment due-date computation based on order completion date.
   - Pro-forma invoice generator with statutory banking details and tax declarations.

4. **Quick Bulk Order Matrix & CSV Importer (`class-bulk-order.php`)**:
   - High-volume SKU procurement matrix with real-time subtotal and savings calculations.
   - Automated CSV file reader parsing `SKU,Quantity` rows with column auto-detection.
   - Inventory cross-verification against the 10 global warehouse fulfillment hubs from Sprint 01.
   - Single-request batch add to cart via `POST /wp-json/suresalez/v1/b2b/bulk-add`.

5. **Request For Quote (RFQ) Negotiation Engine (`class-rfq-engine.php`)**:
   - Cart-to-quote conversion flow generating standard `RFQ-YYYYMMDD-XXXX` codes and secure acceptance tokens.
   - Merchant negotiation console enabling counter-offers, line-item price adjustments, custom freight overrides, and validity expiry timestamps (`POST /wp-json/suresalez/v1/b2b/quotes/{id}/counter`).
   - 1-Click quote acceptance link delivering the buyer straight to an authenticated checkout session with locked negotiated rates (`POST /wp-json/suresalez/v1/b2b/quotes/{id}/accept`).

6. **Master Module Controller (`class-b2b-wholesale-module.php`)**:
   - Extends `Module_Base` and hooks into `SureSalez_Module_Manager`.
   - Enqueues assets only when `b2b-wholesale` module is toggled active.

### C. Frontend Presentation & Theme Mounts
- **Pure FSE Theme (`themes/suresalez-theme/`)**: 0 PHP files maintained.
- **Theme Mounts**: `#suresalez-b2b-bulk-order-mount` and `#suresalez-rfq-form-mount` mounted in `templates/index.html`.
- **Client CSS**: `assets/css/bulk-order.css` and `assets/css/rfq-portal.css`.
- **Client JS**: `assets/js/bulk-order.js` and `assets/js/rfq-portal.js`.

---

## 3. Automated Verification Results
- **Suite 1 (`verify-engine.cjs`)**: 350/350 tests passing (architectural invariants, database schema v1.6.0, token linting, zero bloat, module gating).
- **Suite 2 (`test-sprint-02.cjs`)**: 32/32 tests passing (checkout, wallet, upsells).
- **Suite 3 (`test-sprint-03.cjs`)**: 50/50 tests passing (multi-currency, tax, gateway routing).
- **Suite 4 (`test-sprint-04.cjs`)**: 56/56 tests passing (CRM, segments, Flow automation).
- **Suite 5 (`test-sprint-05.cjs`)**: 61/61 tests passing (omnichannel feeds, AI chat, reels, CAPI, BI).
- **Suite 6 (`test-sprint-06.cjs`)**: 57/57 tests passing (routing, split shipments, serpentine pick, RMA).
- **Suite 7 (`test-sprint-07.cjs`)**: 66/66 tests passing (B2B roles, tax validation, tiered pricing, MOQ, Net Terms, bulk order matrix, RFQ negotiation).
- **Headless Browser Edge Validation**: 177KB rendered DOM verifying `#suresalez-b2b-bulk-order-mount` and `#suresalez-rfq-form-mount` in `tests/browser-test-runner.html`.
- **Combined Test Total**: 672/672 passing automated assertions across the entire platform.
