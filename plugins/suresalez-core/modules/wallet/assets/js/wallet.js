/**
 * SureSalez Store Credit Wallet Client Hydrator
 */

document.addEventListener('DOMContentLoaded', () => {
    let walletData = null;

    if (typeof SureSalezWalletData !== 'undefined') {
        walletData = SureSalezWalletData;
    } else {
        const el = document.getElementById('suresalez-wallet-data-json');
        if (el) {
            try {
                walletData = JSON.parse(el.textContent);
            } catch (e) {
                console.error('Error parsing SureSalez Wallet JSON:', e);
            }
        }
    }

    if (!walletData) {
        return;
    }

    // 1. Mount to Account / Dedicated Wallet Dashboard
    const walletMount = document.getElementById('suresalez-wallet-mount');
    if (walletMount) {
        renderWalletDashboard(walletMount, walletData);
    }

    // 2. Mount to Checkout Wallet Widget
    const checkoutMount = document.getElementById('suresalez-wallet-checkout-mount');
    if (checkoutMount) {
        renderCheckoutWalletWidget(checkoutMount, walletData);
    }
});

function renderWalletDashboard(container, data) {
    const balance = parseFloat(data.balance || 0).toFixed(2);
    const txs = data.transactions || [];

    let rowsHtml = '';
    if (txs.length === 0) {
        rowsHtml = `<tr><td colspan="5" style="text-align:center; color:#94a3b8; padding:20px;">No wallet transactions recorded yet.</td></tr>`;
    } else {
        txs.forEach(tx => {
            const isCredit = tx.type === 'credit';
            const sign = isCredit ? '+' : '-';
            const amountFormatted = `${sign}$${parseFloat(tx.amount).toFixed(2)}`;
            rowsHtml += `
                <tr>
                    <td><span class="suresalez-type-pill ${escapeHtml(tx.type)}">${escapeHtml(tx.type)}</span></td>
                    <td style="font-weight:700; color:${isCredit ? '#15803d' : '#b91c1c'};">${amountFormatted}</td>
                    <td>$${parseFloat(tx.balance_after).toFixed(2)}</td>
                    <td>
                        <div style="font-weight:600;">${escapeHtml(tx.reference_type || 'manual')}</div>
                        <div style="font-size:11px; color:#64748b;">${escapeHtml(tx.notes || tx.reference_id || '')}</div>
                    </td>
                    <td style="font-size:12px; color:#64748b;">${escapeHtml((tx.created_at || '').substring(0, 16))}</td>
                </tr>
            `;
        });
    }

    container.innerHTML = `
        <div class="suresalez-wallet-card">
            <div class="suresalez-wallet-hero">
                <div class="suresalez-wallet-balance-wrap">
                    <div class="label">Available Store Credit</div>
                    <div class="balance-amount">
                        $${balance}
                        <span class="currency-code">USD</span>
                    </div>
                </div>
                <div class="suresalez-wallet-badge">
                    ✓ Verified Customer Wallet
                </div>
            </div>

            <h3 style="font-size:16px; font-weight:700; margin:0 0 16px 0; color:#0f172a;">Transaction History Ledger</h3>
            <div style="overflow-x:auto;">
                <table class="suresalez-wallet-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance After</th>
                            <th>Reference & Notes</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
        </div>
    `;
}

function renderCheckoutWalletWidget(container, data) {
    const balance = parseFloat(data.balance || 0);
    if (balance <= 0) {
        return; // No credit to apply
    }

    let isApplied = false;

    const render = () => {
        container.innerHTML = `
            <div class="suresalez-checkout-wallet-box ${isApplied ? 'applied' : ''}">
                <div class="suresalez-wallet-info">
                    <h4>💳 Store Credit Available ($${balance.toFixed(2)})</h4>
                    <p>${isApplied ? `Applied $${balance.toFixed(2)} discount from your wallet.` : 'Apply your store credit to this order with 1-click.'}</p>
                </div>
                <button type="button" class="suresalez-wallet-btn ${isApplied ? 'remove' : ''}" id="suresalez-toggle-wallet-apply">
                    ${isApplied ? 'Remove Credit' : `Apply $${balance.toFixed(2)}`}
                </button>
            </div>
        `;

        const btn = container.querySelector('#suresalez-toggle-wallet-apply');
        btn.addEventListener('click', () => {
            isApplied = !isApplied;
            render();

            // Dispatch global event for checkout summary to recalculate totals
            document.dispatchEvent(new CustomEvent('suresalez:wallet_applied', {
                detail: {
                    applied: isApplied,
                    amount: isApplied ? balance : 0,
                }
            }));
        });
    };

    render();
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
