# AGENTS.md — SureSalez Enterprise Commerce Workspace Guide

Welcome to the SureSalez enterprise commerce engineering repository. This workspace contains the decoupled e-commerce engine **SureSalez Core** and its companion design-token block theme **SureSalez Theme**.

---

## 1. Architectural Guardrails & Principles

### Decoupled Logic & Clean Boundaries
- **Engine Dominance**: All business logic, custom database tables (`$wpdb->prefix . 'suresalez_*'`), background CRON jobs, REST API controllers (`/wp-json/suresalez/v1/`), GraphQL schema extensions, and admin menus live strictly inside `plugins/suresalez-core/`.
- **Pure Presentation Theme**: `themes/suresalez-theme/` is a WordPress Full Site Editing (FSE) block theme (`theme.json` + HTML block templates). It must never contain business logic, custom post type declarations, direct database queries, or e-commerce calculations.
- **Zero-Bloat Modular Execution**: Every functional capability is a self-contained module under `plugins/suresalez-core/modules/`. Modules must register with `SureSalez_Module_Manager`. When a module is toggled **OFF**:
  - No module classes are initialized.
  - Zero action or filter hooks are registered.
  - Zero CSS or JS assets are enqueued in the frontend or admin DOM.

---

## 2. Workspace Layout

```
.
├── .agents/
│   ├── rules/
│   │   ├── architecture.md          # Architectural invariants and boundaries
│   │   ├── modules.md               # Module lifecycle, registration, and toggles
│   │   └── security.md              # DB sanitization, nonces, and capability checks
│   └── workflows/
│       ├── sprint-01-catalog-pim.md
│       ├── sprint-02-checkout-upsell.md
│       ├── sprint-03-localization-compliance.md
│       ├── sprint-04-crm-flow-engine.md
│       ├── sprint-05-omnichannel-ai-reels.md
│       ├── sprint-06-orders-routing-rma.md
│       ├── sprint-07-b2b-rfq-wholesale.md
│       ├── sprint-08-pos-marketplace-subscriptions.md
│       ├── sprint-09-loyalty-redirects-mobile-apis.md
│       └── sprint-10-multistore-billing-scale.md
├── plugins/
│   └── suresalez-core/
│       ├── suresalez-core.php       # Bootstrap, autoloader, constants
│       ├── includes/
│       │   ├── class-module-manager.php
│       │   ├── class-module-base.php
│       │   ├── class-database.php
│       │   └── class-rest-api.php
│       ├── admin/                   # Core admin settings, module toggle dashboard
│       └── modules/                 # Modular feature engines
└── themes/
    └── suresalez-theme/
        ├── style.css                # Minimal metadata stylesheet
        ├── theme.json               # Palette, typography, layout tokens
        ├── templates/               # FSE block templates (index, single-product, etc.)
        └── parts/                   # Reusable template parts (header, footer, meta)
```

---

## 3. Sprint Roadmap

| Sprint | Focus Area | Key Deliverables |
|---|---|---|
| **01** | **Catalog, PIM & DAM** | Custom metafields (EAN, UPC, Brand, Specs), 10 Inventory Hubs, Visual Swatches, Verified Reviews & Q&A, DAM 360 viewer. |
| **02** | **Checkout & Cart** | Extensible checkout steps, 1-click post-purchase upsells, slide-out cart drawer, store credit wallet. |
| **03** | **Global Localization** | Real-time multi-currency switcher, automatic geolocated tax, GST / VAT compliance & import duties. |
| **04** | **CRM & Flow Engine** | Customer segmentation engine, automated SMS/email triggers, visual node-based workflow automation. |
| **05** | **Omnichannel & AI** | Google Merchant / Meta / TikTok catalog feeds, conversational AI sales assistant, shoppable video reels. |
| **06** | **Routing & RMA** | Intelligent multi-warehouse split order routing, 3PL/POD fulfillment sync, self-service customer RMA portal. |
| **07** | **B2B Wholesale** | Multi-tier price lists, B2B company accounts with parent/child seats, tax exemption, RFQ quote builder. |
| **08** | **POS & Marketplace** | Web-based touch POS interface, multi-vendor marketplace vendor payouts, recurring subscription engine. |
| **09** | **Loyalty & Headless** | VIP tiers, gamified reward points, automated 301 SEO migration redirects, headless mobile app REST/GraphQL APIs. |
| **10** | **Multi-Store & Scale** | Multi-store tenant management, reseller white-label billing, Redis edge object caching, high-throughput batch APIs. |

---

## 4. Engineering Standards & Quality Checklist

1. **Strict Types & Standards**: All PHP code strictly complies with PHP 8.1+ features and WordPress VIP Coding Standards (data validation, strict type casting, late static binding).
2. **Database Queries**: All direct SQL must use `$wpdb->prepare()`. Custom tables must use InnoDB with composite indexes on lookup columns (`sku`, `product_id`, `location_id`, `status`).
3. **Security Standards**:
   - Every state-mutating request must verify a cryptographic nonce via `check_admin_referer()` or `wp_verify_nonce()`.
   - Access control must verify `current_user_can('manage_woocommerce')` or custom capabilities.
   - All output must be escaped using `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()`.
4. **Performance**: Zero inline styles in production HTML; all assets loaded asynchronously with version query strings derived from `SURESALEZ_VERSION`.
