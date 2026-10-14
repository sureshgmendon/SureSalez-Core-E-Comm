---
title: "Sprint 03: Global Localization, Multi-Currency & Compliance (GST / Duties)"
sprint: 3
status: "completed"
tags: ["localization", "currency", "tax", "gst", "duties"]
---

# Sprint 03: Global Localization, Multi-Currency & Compliance (GST / Duties)

## Objective
Enable cross-border global trade with automated multi-currency switching, localized rounding rules, automated tax calculation (US Sales Tax, EU VAT, India GST), and landed cost duty estimations.

## Deliverables
1. **Multi-Currency Engine**:
   - Real-time exchange rate sync from Open Exchange Rates / European Central Bank.
   - Price rounding rules (e.g. 9.99 charm pricing per country).
   - GeoIP automatic currency detection with manual user toggle widget.
2. **Tax & Compliance Engine**:
   - Automated EU VAT reverse charge validation with VIES API.
   - India GST compliant invoices (HSN/SAC codes, CGST/SGST/IGST breakdown).
   - US State sales tax nexus configuration.
3. **Landed Cost & Import Duty Calculator**:
   - HS Code lookup and import duty estimation on international checkouts.
   - Delivered Duty Paid (DDP) vs Delivered Duty Unpaid (DDU) checkout options.
