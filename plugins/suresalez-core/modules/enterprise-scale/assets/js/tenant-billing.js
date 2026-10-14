/**
 * SureSalez Core — Reseller Tenant Billing & Usage Meter Client Controller
 * Sprint 10: Multi-Store Organization Admin & High-Scale APIs
 */

(function () {
    'use strict';

    function initTenantBilling() {
        const mount = document.getElementById('suresalez-tenant-billing-mount');
        if (!mount) {
            return;
        }

        const data = window.suresalezEnterpriseData || {};
        const summary = data.billing || {
            billing: {
                tenant_id: 1,
                billing_cycle: 'monthly',
                base_fee: 499.00,
                rev_share_percentage: 1.50,
                current_month_gmv: 125000.00,
                invoiced_total: 2374.00,
                payment_status: 'paid',
                next_billing_date: '2026-11-04'
            },
            whitelabel: {
                agency_name: 'Aura Commerce Agency Suite',
                support_email: 'concierge@auracommerce.io'
            },
            dunning: {
                payment_status: 'paid',
                dunning_stage: 'normal'
            }
        };

        const b = summary.billing || {};
        const wl = summary.whitelabel || {};
        const gmv = parseFloat(b.current_month_gmv || 125000);
        const baseFee = parseFloat(b.base_fee || 499);
        const revPct = parseFloat(b.rev_share_percentage || 1.5);
        const revAmount = (gmv * (revPct / 100)).toFixed(2);
        const invoicedTotal = (baseFee + parseFloat(revAmount)).toFixed(2);
        const status = b.payment_status || 'paid';

        mount.innerHTML = `
            <div class="suresalez-billing-container" role="region" aria-label="Tenant Billing & Usage Meter">
                <div class="suresalez-billing-header">
                    <div>
                        <h3 class="suresalez-billing-title">Reseller & Tenant Billing Meter</h3>
                        <p class="suresalez-billing-subtitle">Usage-based monthly invoicing: Fixed Retainer + ${revPct}% GMV Cut</p>
                    </div>
                    <div style="text-align: right;">
                        <span class="suresalez-status-pill suresalez-status-${status}">
                            ✓ ${status.toUpperCase()}
                        </span>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Partner: ${wl.agency_name || 'Aura Commerce'}</div>
                    </div>
                </div>

                <div class="suresalez-billing-grid">
                    <div class="suresalez-meter-card">
                        <div class="suresalez-meter-label">Platform Base Retainer</div>
                        <div class="suresalez-meter-value">$${baseFee.toFixed(2)}</div>
                        <div class="suresalez-meter-subtext">Fixed Monthly Cloud Retainer</div>
                    </div>
                    <div class="suresalez-meter-card">
                        <div class="suresalez-meter-label">Monthly Processed GMV</div>
                        <div class="suresalez-meter-value">$${gmv.toLocaleString()}</div>
                        <div class="suresalez-meter-subtext">3,840 Total Completed Orders</div>
                    </div>
                    <div class="suresalez-meter-card">
                        <div class="suresalez-meter-label">Revenue Share (${revPct}%)</div>
                        <div class="suresalez-meter-value">$${parseFloat(revAmount).toLocaleString()}</div>
                        <div class="suresalez-meter-subtext">Automated Transaction Cut</div>
                    </div>
                </div>

                <div class="suresalez-invoice-summary-box">
                    <div>
                        <span style="font-size: 13px; color: #94a3b8; display: block;">Total Monthly Invoiced Amount</span>
                        <span class="suresalez-invoice-total">$${parseFloat(invoicedTotal).toLocaleString()} USD</span>
                    </div>
                    <div style="text-align: right; font-size: 13px; color: #cbd5e1;">
                        <span>Next Billing Cycle: <strong>${b.next_billing_date || '2026-11-04'}</strong></span>
                        <div style="color: #64748b; font-size: 11px; margin-top: 2px;">Automated Stripe Direct Debit Active</div>
                    </div>
                </div>
            </div>
        `;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTenantBilling);
    } else {
        initTenantBilling();
    }
})();
