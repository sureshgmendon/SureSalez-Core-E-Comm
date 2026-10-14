---
title: "Sprint 02: Checkout Extensibility, 1-Click Upsells, Cart Drawer & Wallet"
sprint: 2
status: "active"
tags: ["checkout", "upsells", "cart", "wallet", "recovery", "cron"]
---

# Sprint 02: Checkout Extensibility, 1-Click Upsells, Cart Drawer & Wallet

## Objective
Build a modern, friction-free checkout and cart experience with post-purchase upsells, slide-out cart drawer, and customer digital wallet.

## Deliverables
1. **Slide-Out Cart Drawer**:
   - AJAX-powered sliding cart drawer with free shipping progress threshold.
   - Dynamic cross-sell carousel inside the drawer based on cart contents.
2. **Checkout Extensibility**:
   - Multi-step vs single-page customizable checkout flow.
   - Express checkout integrations (Apple Pay, Google Pay, PayPal Fastlane).
   - Custom checkout fields manager (delivery notes, VAT/Tax ID, gift messaging).
3. **1-Click Post-Purchase Upsells**:
   - Tokenized payment upsell offers displayed between payment authorization and thank you page.
   - A/B testing framework for offer conversion optimization.
4. **Customer Store Wallet**:
   - Dedicated ledger table `suresalez_wallet_transactions`.
   - Store credit balance, partial redemption at checkout, and cashback rewards.
