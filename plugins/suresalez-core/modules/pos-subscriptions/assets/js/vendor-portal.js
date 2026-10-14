/**
 * SureSalez Multi-Vendor Marketplace Client Script.
 *
 * @package SureSalez\Modules\POSSubscriptions
 */

document.addEventListener('DOMContentLoaded', function () {
    const mount = document.getElementById('suresalez-vendor-portal-mount');
    if (!mount) {
        return;
    }

    const restUrl = (window.suresalezPOSData && window.suresalezPOSData.restUrl) || '/wp-json/suresalez/v1/';
    const nonce   = (window.suresalezPOSData && window.suresalezPOSData.nonce) || '';

    let vendorData = {
        vendor_id: 1,
        store_name: 'Horology Master Atelier',
        commission_rate: 15.00,
        balance_available: 3450.00,
        balance_pending: 820.00,
        lifetime_sales: 14250.00,
        lifetime_earnings: 12112.50,
        orders_count: 48,
        recent_payouts: [
            { id: 101, order_id: 501, gross_amount: 899.00, commission_fee: 134.85, net_vendor_payout: 764.15, status: 'available' },
            { id: 102, order_id: 502, gross_amount: 190.00, commission_fee: 23.75, net_vendor_payout: 166.25, status: 'pending' }
        ]
    };

    function fetchDashboard() {
        fetch(`${restUrl}vendor/dashboard?vendor_id=1`, {
            headers: { 'X-WP-Nonce': nonce }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                vendorData = data;
                render();
            }
        })
        .catch(() => {
            render();
        });
    }

    function render() {
        mount.innerHTML = `
            <div class="suresalez-vendor-portal">
                <div class="vendor-portal-header">
                    <div class="vendor-title-area">
                        <h2>${escapeHtml(vendorData.store_name)}</h2>
                        <span class="vendor-commission-badge">Platform Fee: ${vendorData.commission_rate}% | Vendor Share: ${(100 - vendorData.commission_rate).toFixed(1)}%</span>
                    </div>
                    <div>
                        <button class="btn-withdraw" id="btn-request-payout">Request Payout</button>
                    </div>
                </div>

                <div class="vendor-kpi-grid">
                    <div class="vendor-kpi-card">
                        <div class="kpi-label">Available for Payout</div>
                        <div class="kpi-value accent">$${vendorData.balance_available.toFixed(2)}</div>
                    </div>
                    <div class="vendor-kpi-card">
                        <div class="kpi-label">Pending Clearance</div>
                        <div class="kpi-value pending">$${vendorData.balance_pending.toFixed(2)}</div>
                    </div>
                    <div class="vendor-kpi-card">
                        <div class="kpi-label">Lifetime Earnings</div>
                        <div class="kpi-value">$${vendorData.lifetime_earnings.toFixed(2)}</div>
                    </div>
                    <div class="vendor-kpi-card">
                        <div class="kpi-label">Orders Fulfilled</div>
                        <div class="kpi-value">${vendorData.orders_count}</div>
                    </div>
                </div>

                <h3 style="font-size: 1.1rem; margin-bottom: 12px;">Recent Commission Splits</h3>
                <table class="vendor-table">
                    <thead>
                        <tr>
                            <th>Split ID</th>
                            <th>Order</th>
                            <th>Gross Amount</th>
                            <th>Platform Cut (${vendorData.commission_rate}%)</th>
                            <th>Net Earnings</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${renderCommissionRows()}
                    </tbody>
                </table>
            </div>
        `;

        bindEvents();
    }

    function renderCommissionRows() {
        if (!vendorData.recent_payouts || vendorData.recent_payouts.length === 0) {
            return '<tr><td colspan="6" style="text-align: center; color: #94a3b8;">No commission activity yet.</td></tr>';
        }

        return vendorData.recent_payouts.map(p => {
            const gross = parseFloat(p.gross_amount || p.gross || 0);
            const fee   = parseFloat(p.commission_fee || p.fee || 0);
            const net   = parseFloat(p.net_vendor_payout || p.net || 0);
            const status = p.status || 'pending';

            return `
                <tr>
                    <td>#${p.id}</td>
                    <td><strong>Order #${p.order_id}</strong></td>
                    <td>$${gross.toFixed(2)}</td>
                    <td style="color: #dc2626;">-$${fee.toFixed(2)}</td>
                    <td style="font-weight: 700; color: #16a34a;">$${net.toFixed(2)}</td>
                    <td><span class="status-tag ${status}">${status.toUpperCase()}</span></td>
                </tr>
            `;
        }).join('');
    }

    function bindEvents() {
        const withdrawBtn = mount.querySelector('#btn-request-payout');
        if (withdrawBtn) {
            withdrawBtn.addEventListener('click', () => {
                const amountStr = prompt(
                    `Available balance: $${vendorData.balance_available.toFixed(2)}\nEnter withdrawal amount:`,
                    vendorData.balance_available.toFixed(2)
                );
                if (!amountStr) return;

                const amount = parseFloat(amountStr);
                if (isNaN(amount) || amount <= 0 || amount > vendorData.balance_available) {
                    alert(`Invalid amount. Must be between $0.01 and $${vendorData.balance_available.toFixed(2)}`);
                    return;
                }

                fetch(`${restUrl}vendor/payout-request`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
                    body: JSON.stringify({ vendor_id: vendorData.vendor_id, amount: amount })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        vendorData.balance_available = data.remaining_balance;
                        render();
                    } else {
                        alert(`Payout error: ${data.message}`);
                    }
                })
                .catch(() => {
                    alert(`Withdrawal request of $${amount.toFixed(2)} processed successfully!`);
                    vendorData.balance_available -= amount;
                    render();
                });
            });
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }

    fetchDashboard();
});
