---
title: "Sprint 06: Order Routing, Split Shipments, 3PL/POD & Self-Service RMA"
sprint: 6
status: "completed"
tags: ["orders", "routing", "split-shipments", "3pl", "pod", "rma", "fulfillment"]
completed_at: "2026-10-14"
---

# Sprint 06: Order Routing, Split Shipments, 3PL/POD & Self-Service RMA

## 1. Executive Summary & Architectural Achievements
Sprint 06 delivers an enterprise-grade order routing, multi-package split shipment, warehouse pick-pack optimization, 3PL/POD dropship PO dispatching, and customer self-service RMA return engine for the decoupled **SureSalez Core** (`plugins/suresalez-core/`) and design-token companion theme **SureSalez Theme** (`themes/suresalez-theme/`).

All e-commerce and logistics logic is strictly housed in `plugins/suresalez-core/modules/fulfillment-rma/`, completely gated behind `SureSalez_Module_Manager`. When the module is toggled OFF, zero hooks, background crons, or frontend assets execute or load into the DOM. The presentation theme remains 100% logic-free with strictly 0 PHP files.

---

## 2. Core Deliverables Implemented

### A. Database Schemas (`class-database.php` v1.5.0)
- `{$wpdb->prefix}suresalez_fulfillments`: Fulfillment tracking, origin hub (`location_id`), carrier (FedEx, DHL, BlueDart), tracking number, status pipeline (`unfulfilled`, `picking`, `packed`, `shipped`, `in_transit`, `delivered`), and estimated delivery.
- `{$wpdb->prefix}suresalez_fulfillment_items`: Association linking fulfillment packages to specific order items, SKU, quantity, and pick status.
- `{$wpdb->prefix}suresalez_rma_requests`: Customer returns tracking (`RMA-YYYYMMDD-XXXX`), order ID, customer ID, status (`requested`, `authorized`, `label_generated`, `in_transit`, `received`, `inspected`, `restocked`, `completed`, `rejected`), return type (`store_credit`, `original_payment`, `exchange`), reason code, notes, and carrier return label PDF URL.
- `{$wpdb->prefix}suresalez_rma_items`: Granular return items, SKU, quantity, reason code, QA condition (`unopened`, `opened_like_new`, `damaged`, `scrap`), and individual refund amounts.
- `{$wpdb->prefix}suresalez_supplier_dropships`: 3PL & Print-on-Demand (POD) purchase order dispatch ledger tracking supplier EDI/REST payloads, merchant white-label packing slip generation, retail MSRP vs. suppressed wholesale merchant cost.

### B. Backend Logistics & Return Engines (`plugins/suresalez-core/modules/fulfillment-rma/`)
1. **Intelligent Proximity Order Router (`class-order-router.php`)**:
   - Evaluates destination country codes against the 10 global fulfillment hubs (`US-EAST-01`, `EU-CENTRAL-01`, `APAC-SOUTH-01`, etc.).
   - Dynamically partitions multi-item orders into split shipments (`FUL-XXXX-01`, `FUL-XXXX-02`) when inventory is fragmented across warehouses.
   - Dynamic carrier resolution based on hub origin and customer destination.
   - REST API: `GET /wp-json/suresalez/v1/fulfillment/tracking/{order_id}` and `POST /wp-json/suresalez/v1/fulfillment/route`.

2. **Warehouse Batch Pick-Pack & Code-128 AWB Generator (`class-batch-fulfillment.php`)**:
   - Groups orders into fulfillment wave batches.
   - Serpentine aisle route optimization (alternating bay sorting per aisle: Odd aisles ascending, Even aisles descending) minimizing picker transit time.
   - Code-128 barcode check-digit calculation (mod-103) and thermal shipping label generation.
   - REST API: `POST /wp-json/suresalez/v1/fulfillment/batch-pick` and `POST /wp-json/suresalez/v1/fulfillment/pack`.

3. **Dropship & POD White-Label PO Dispatcher (`class-dropship-dispatcher.php`)**:
   - Prefix matching (`POD-` &rarr; Print-on-Demand, `DROP-` &rarr; Supplier Dropship, Standard &rarr; Warehouse Owned).
   - White-label packing slip generator: displays merchant retail branding and customer retail price, while strictly suppressing internal wholesale costs.
   - REST API: `POST /wp-json/suresalez/v1/fulfillment/dropship-dispatch`.

4. **Self-Service Customer RMA & Restocking Engine (`class-rma-engine.php`)**:
   - Self-service RMA request generator (`RMA-YYYYMMDD-XXXX`) supporting 5 reason codes.
   - Pre-paid return shipping label PDF generator.
   - Warehouse QA inspection workflow:
     - `passed`: restocks inventory directly into Sprint 01 `suresalez_inventory_stock` at designated hub.
     - `store_credit`: calculates refund + 10% bonus credit incentive and directly appends transaction to Sprint 02 `suresalez_wallet_transactions`.
     - `damaged` / `scrap`: routes items to quarantine buffer without active inventory restock.
   - REST API: `POST /wp-json/suresalez/v1/rma/request`, `POST /wp-json/suresalez/v1/rma/inspect`, `GET /wp-json/suresalez/v1/rma/{rma_number}`, `GET /wp-json/suresalez/v1/rma/reasons`.

### C. Frontend Presentation & Theme Mounts
- **Pure FSE Theme (`themes/suresalez-theme/`)**: 0 PHP files maintained. Semantic theme mounts `#suresalez-order-tracking-mount` and `#suresalez-rma-portal-mount` integrated into `templates/index.html`.
- **Client CSS**: `assets/css/rma-portal.css` and `assets/css/tracking-widget.css`.
- **Client JS**: `assets/js/rma-portal.js` and `assets/js/tracking-widget.js`.

---

## 3. Automated Verification Results
- **Suite 1 (`verify-engine.cjs`)**: 305/305 tests passing (architectural invariants, database schema, token linting, zero bloat, module gating).
- **Suite 2 (`test-sprint-02.cjs`)**: 32/32 tests passing (checkout, wallet, upsells).
- **Suite 3 (`test-sprint-03.cjs`)**: 50/50 tests passing (multi-currency, tax, gateway routing).
- **Suite 4 (`test-sprint-04.cjs`)**: 56/56 tests passing (CRM, segments, Flow automation).
- **Suite 5 (`test-sprint-05.cjs`)**: 61/61 tests passing (omnichannel feeds, AI chat, reels, CAPI, BI).
- **Suite 6 (`test-sprint-06.cjs`)**: 57/57 tests passing (proximity routing, split shipments, serpentine pick, Code-128, dropship slip, RMA restocking + wallet bonus).
- **Headless Browser Edge Validation**: 164KB rendered DOM verifying `#suresalez-rma-portal-mount` and `#suresalez-order-tracking-mount` in `tests/browser-test-runner.html`.
- **Combined Test Total**: 561/561 passing automated assertions across the entire platform.
