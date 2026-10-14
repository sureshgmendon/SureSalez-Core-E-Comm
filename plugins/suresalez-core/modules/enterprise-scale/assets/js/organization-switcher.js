/**
 * SureSalez Core — Multi-Store Organization Switcher Client Controller
 * Sprint 10: Multi-Store Organization Admin & High-Scale APIs
 */

(function () {
    'use strict';

    function initOrgSwitcher() {
        const mount = document.getElementById('suresalez-organization-switcher-mount');
        if (!mount) {
            return;
        }

        const data = window.suresalezEnterpriseData || {
            current_tenant: {
                id: 1,
                store_name: 'SureSalez US Flagship Atelier',
                domain: 'us.suresalez.io',
                environment: 'production',
                plan_tier: 'enterprise'
            },
            overview: {
                organization_name: 'SureSalez Global Luxury Brands Holding Corp',
                active_tenants_count: 3,
                tenants: [
                    { id: 1, store_name: 'SureSalez US Flagship Atelier', domain: 'us.suresalez.io' },
                    { id: 2, store_name: 'SureSalez India Atelier', domain: 'in.suresalez.io' },
                    { id: 3, store_name: 'SureSalez Global B2B Outlet', domain: 'b2b.suresalez.io' }
                ],
                consolidated_kpis: {
                    total_gmv_monthly: 170000.00,
                    total_orders_volume: 3840
                }
            }
        };

        const current = data.current_tenant || {};
        const overview = data.overview || {};
        const tenants = overview.tenants || [];
        const kpis = overview.consolidated_kpis || {};

        const tenantOptions = tenants.map(function (t) {
            const isSelected = t.id === current.id ? 'selected' : '';
            return `<option value="${t.id}" ${isSelected}>${t.store_name} (${t.domain})</option>`;
        }).join('');

        mount.innerHTML = `
            <div class="suresalez-org-switcher-bar" role="navigation" aria-label="Organization Multi-Store Switcher">
                <div class="suresalez-org-brand">
                    <div class="suresalez-org-icon">🏢</div>
                    <div>
                        <h4 class="suresalez-org-title">${overview.organization_name || 'SureSalez Enterprise Holding'}</h4>
                        <p class="suresalez-org-subtitle">Central Organization Administration • ${tenants.length || 3} Connected Tenant Stores</p>
                    </div>
                </div>

                <div class="suresalez-tenant-select-box">
                    <div class="suresalez-kpi-pill">
                        <span>Consolidated GMV:</span>
                        <strong style="color: #38bdf8;">$${(kpis.total_gmv_monthly || 170000).toLocaleString()}</strong>
                    </div>
                    
                    <label for="suresalez-tenant-select" style="font-size: 13px; color: #94a3b8; display: none;">Select Store:</label>
                    <select id="suresalez-tenant-select" class="suresalez-tenant-dropdown">
                        ${tenantOptions}
                    </select>

                    <span class="suresalez-env-badge">${(current.environment || 'production').toUpperCase()}</span>
                </div>
            </div>
        `;

        const select = document.getElementById('suresalez-tenant-select');
        if (select) {
            select.addEventListener('change', function (e) {
                const selectedTenantId = parseInt(e.target.value, 10);
                window.dispatchEvent(new CustomEvent('suresalez:tenant:switched', {
                    detail: { tenant_id: selectedTenantId }
                }));
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOrgSwitcher);
    } else {
        initOrgSwitcher();
    }
})();
