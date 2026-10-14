---
title: "Sprint 09: Loyalty Points, VIP Tiers, 301 SEO Redirects & Headless Mobile APIs"
sprint: 9
status: "completed"
tags: ["loyalty", "vip", "referrals", "seo", "redirects", "mobile-api", "headless", "push-notifications"]
---

# Sprint 09: Loyalty Points, VIP Tiers, 301 SEO Redirects & Headless Mobile APIs

## Objective
Engineer enterprise customer retention, search engine optimization, and native mobile omnichannel capabilities for SureSalez Core and SureSalez Theme:
1. Gamified Loyalty Points & VIP Tiers Engine with tiered multipliers (Bronze 1x, Silver 1.25x, Gold 1.5x, Platinum 2.0x), points redemption sliders, and automated tier elevation.
2. Dual-Sided Viral Referral Program with personalized referral URLs (`?ref=SURE-XXXX`), automated friend welcome discount (15% off), and referrer bonus points issuance upon order completion.
3. Automated 301 Redirect Engine & SEO Schema.org Generator intercepting slug alterations to eliminate 404 dead links and generating valid JSON-LD structured data for Google Search.
4. Headless Mobile App REST APIs with curated home feeds, biometric token authentication, and APNs/FCM push token device registration.

---

## Architectural Guardrails & Invariants
- **Decoupled Architecture**: All loyalty points ledgers, referral handlers, 301 redirect daemons, JSON-LD schema injectors, and headless mobile endpoints reside strictly inside `plugins/suresalez-core/modules/loyalty-mobile-api/`.
- **Pure Presentation Theme**: `themes/suresalez-theme/` must remain a 100% logic-free FSE block theme with strictly 0 PHP files, providing only semantic mount points (`#suresalez-loyalty-rewards-mount`, `#suresalez-referral-banner-mount`).
- **Zero-Bloat Gating**: When `loyalty-mobile-api` is toggled OFF, 0 points calculation hooks, 0 redirect filters, and 0 mobile API routes execute or load into the DOM.
- **Data Integrity & Interoperability**:
  - Uses `dbDelta` in `class-database.php` (schema bumped to `v1.8.0`) with composite indexes on user IDs, referral codes, and source URL slugs.
  - Reconciles points redemptions alongside Sprint 02's wallet ledger and checkout totals.

---

## Detailed Technical Specifications

### 1. Database Schemas (`class-database.php` v1.8.0)
1. `suresalez_loyalty_points`:
   - `id`, `user_id`, `points_balance`, `lifetime_points`, `tier_level` (`bronze`, `silver`, `gold`, `platinum`), `updated_at`.
2. `suresalez_points_transactions`:
   - `id`, `user_id`, `points`, `type` (`earned`, `redeemed`, `expired`, `bonus`), `description`, `reference_order_id`, `created_at`.
3. `suresalez_referrals`:
   - `id`, `referrer_user_id`, `referral_code`, `referred_user_id`, `reward_status` (`pending`, `rewarded`), `order_id`, `created_at`.
4. `suresalez_seo_redirects`:
   - `id`, `source_url`, `target_url`, `status_code` (`301`, `302`), `hit_count`, `last_accessed_at`, `created_at`.
5. `suresalez_mobile_devices`:
   - `id`, `user_id`, `device_token`, `platform` (`ios`, `android`), `app_version`, `last_active_at`.

### 2. Backend Domain Engines (`plugins/suresalez-core/modules/loyalty-mobile-api/`)
- `class-loyalty-engine.php`:
  - Tier rules: Bronze (1x), Silver (1.25x), Gold (1.5x), Platinum (2.0x).
  - Points accrual formula: `points = order_spend * tier_multiplier`.
  - Checkout redemption formula: `discount = (points / 100) * 5.00` ($0.05/pt), min threshold 200 pts ($10.00), max 50% cart total.
  - Tier promotion check based on rolling spend or lifetime points.
- `class-referral-program.php`:
  - Referral code generator (`SURE-XXXX`).
  - Cookie attribution tracker.
  - Friend welcome discount calculation (15% off).
  - Referrer reward dispatcher upon order completion.
- `class-seo-manager.php`:
  - Pre-query 301 redirect daemon matching requested URI against `suresalez_seo_redirects`.
  - Automatic 301 rule creation on product slug updates.
  - Rich JSON-LD structured data generator (`Product`, `Offer`, `AggregateRating`, `BreadcrumbList`).
- `class-mobile-api.php`:
  - `GET /wp-json/suresalez/v1/mobile/home` (home feed aggregating hero banner, trending reels, and flash sale items).
  - `POST /wp-json/suresalez/v1/mobile/auth` (biometric / tokenized sign-in).
  - `POST /wp-json/suresalez/v1/mobile/devices/register` (device token registration).
  - `GET /wp-json/suresalez/v1/mobile/loyalty` (customer points, tier status, and perks).
- `class-loyalty-mobile-api-module.php`:
  - Master module class extending `Module_Base`.
  - Conditional hooks, REST route registration, and frontend asset enqueueing.

### 3. Frontend Client Assets & Themes
- `assets/css/loyalty-portal.css` & `assets/js/loyalty-portal.js`: Gamified loyalty balance, tier meter, and checkout redemption slider.
- `assets/css/referral-banner.css` & `assets/js/referral-banner.js`: Referral sharing card with copyable link and referral tracker.
- Theme mount points in `themes/suresalez-theme/templates/index.html`:
  - `#suresalez-loyalty-rewards-mount`
  - `#suresalez-referral-banner-mount`

### 4. Verification Suite
- Unit test suite: `tests/test-sprint-09.cjs`.
- Master test suite: `tests/verify-engine.cjs`.
- Headless Edge DOM validation: `tests/run-browser-dom.cjs`.
