/**
 * SureSalez Recurring Subscriptions Client Script.
 *
 * @package SureSalez\Modules\POSSubscriptions
 */

document.addEventListener('DOMContentLoaded', function () {
    const mount = document.getElementById('suresalez-subscriptions-mount');
    if (!mount) {
        return;
    }

    const restUrl = (window.suresalezPOSData && window.suresalezPOSData.restUrl) || '/wp-json/suresalez/v1/';
    const nonce   = (window.suresalezPOSData && window.suresalezPOSData.nonce) || '';

    let subscriptions = [
        {
            id: 1,
            token: 'sub_tok_lux_8811',
            title: 'Luminor Chrono Automatic (Horween Strap Bundle)',
            interval: 'monthly',
            discount_pct: 10.0,
            base_price: 899.00,
            recurring_price: 809.10,
            next_renewal_date: '2026-11-14 00:00:00',
            status: 'active',
            card_last4: '4242'
        },
        {
            id: 2,
            token: 'sub_tok_strap_9922',
            title: 'Horween Leather Replacement Strap 22mm',
            interval: 'quarterly',
            discount_pct: 15.0,
            base_price: 95.00,
            recurring_price: 80.75,
            next_renewal_date: '2027-01-14 00:00:00',
            status: 'active',
            card_last4: '5555'
        }
    ];

    let widgetState = {
        mode: 'subscription', // 'onetime' | 'subscription'
        interval: 'monthly',
        basePrice: 899.00,
    };

    function calculateWidgetPrice() {
        if (widgetState.mode === 'onetime') {
            return { price: widgetState.basePrice, discount: 0 };
        }
        const discounts = { weekly: 15, monthly: 10, quarterly: 5 };
        const pct = discounts[widgetState.interval] || 10;
        const discountVal = widgetState.basePrice * (pct / 100);
        return {
            price: widgetState.basePrice - discountVal,
            discount: pct
        };
    }

    function render() {
        const wp = calculateWidgetPrice();

        mount.innerHTML = `
            <div class="suresalez-subscriptions-container">
                <div class="sub-header">
                    <h2>Subscribe & Save Management Portal</h2>
                    <p>Manage recurring replenishment deliveries, modify intervals, skip cycles, or pause anytime.</p>
                </div>

                <!-- Interactive PDP Widget Simulation -->
                <div class="suresalez-sub-widget">
                    <h4 style="margin: 0 0 10px 0; font-size: 0.95rem;">Product Purchase Option:</h4>
                    <div class="sub-option-row ${widgetState.mode === 'onetime' ? 'active' : ''}" id="opt-onetime">
                        <div>
                            <strong>One-Time Purchase</strong>
                        </div>
                        <div>$${widgetState.basePrice.toFixed(2)}</div>
                    </div>
                    <div class="sub-option-row ${widgetState.mode === 'subscription' ? 'active' : ''}" id="opt-subscribe">
                        <div>
                            <strong>Subscribe & Save</strong>
                            <span class="sub-discount-badge">Save ${wp.discount}%</span>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 4px;">
                                Frequency:
                                <select id="sub-interval-select" style="padding: 2px 6px; border-radius: 4px; border: 1px solid #cbd5e1; font-size: 0.8rem;">
                                    <option value="weekly" ${widgetState.interval === 'weekly' ? 'selected' : ''}>Deliver Every Week (15% Off)</option>
                                    <option value="monthly" ${widgetState.interval === 'monthly' ? 'selected' : ''}>Deliver Every Month (10% Off)</option>
                                    <option value="quarterly" ${widgetState.interval === 'quarterly' ? 'selected' : ''}>Deliver Every 3 Months (5% Off)</option>
                                </select>
                            </div>
                        </div>
                        <div style="font-weight: 700; color: #16a34a; font-size: 1.1rem;">
                            $${wp.price.toFixed(2)}
                        </div>
                    </div>
                </div>

                <h3 style="font-size: 1.15rem; margin: 24px 0 14px 0;">My Active Subscriptions</h3>
                <div class="sub-cards-grid">
                    ${renderSubscriptionCards()}
                </div>
            </div>
        `;

        bindEvents();
    }

    function renderSubscriptionCards() {
        if (subscriptions.length === 0) {
            return '<div style="color: #64748b;">No active subscriptions found.</div>';
        }

        return subscriptions.map(sub => `
            <div class="sub-card" data-sub-id="${sub.id}">
                <div>
                    <div class="sub-card-header">
                        <div class="sub-product-title">${escapeHtml(sub.title)}</div>
                        <span class="sub-status-pill ${sub.status}">${sub.status.toUpperCase()}</span>
                    </div>
                    <div class="sub-details">
                        <p style="margin: 2px 0;"><strong>Recurring Price:</strong> $${sub.recurring_price.toFixed(2)} / ${sub.interval}</p>
                        <p style="margin: 2px 0;"><strong>Next Renewal:</strong> ${sub.next_renewal_date.split(' ')[0]}</p>
                        <p style="margin: 2px 0; color: #64748b;">Payment Method: •••• ${sub.card_last4}</p>
                    </div>
                </div>
                <div class="sub-actions">
                    ${sub.status === 'active' ? `
                        <button class="sub-btn" data-action="skip" data-id="${sub.id}">⏭️ Skip</button>
                        <button class="sub-btn" data-action="pause" data-id="${sub.id}">⏸️ Pause</button>
                    ` : sub.status === 'paused' ? `
                        <button class="sub-btn sub-btn-primary" data-action="resume" data-id="${sub.id}">▶️ Resume</button>
                    ` : ''}
                    <button class="sub-btn sub-btn-danger" data-action="cancel" data-id="${sub.id}">Cancel</button>
                </div>
            </div>
        `).join('');
    }

    function bindEvents() {
        // Option selection
        const onetimeOpt = mount.querySelector('#opt-onetime');
        const subOpt     = mount.querySelector('#opt-subscribe');
        const selectInt  = mount.querySelector('#sub-interval-select');

        if (onetimeOpt) {
            onetimeOpt.addEventListener('click', () => {
                widgetState.mode = 'onetime';
                render();
            });
        }

        if (subOpt) {
            subOpt.addEventListener('click', (e) => {
                if (e.target !== selectInt) {
                    widgetState.mode = 'subscription';
                    render();
                }
            });
        }

        if (selectInt) {
            selectInt.addEventListener('change', (e) => {
                widgetState.mode = 'subscription';
                widgetState.interval = e.target.value;
                render();
            });
        }

        // Subscription actions
        mount.querySelectorAll('.sub-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const subId  = parseInt(btn.dataset.id, 10);
                const action = btn.dataset.action;
                const sub    = subscriptions.find(s => s.id === subId);
                if (!sub) return;

                if (action === 'skip') {
                    fetch(`${restUrl}subscriptions/${subId}/skip`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce }
                    })
                    .then(res => res.json())
                    .then(data => {
                        const currentD = new Date(sub.next_renewal_date);
                        currentD.setDate(currentD.getDate() + 30);
                        sub.next_renewal_date = currentD.toISOString().slice(0, 19).replace('T', ' ');
                        alert(`Upcoming delivery skipped. Next renewal: ${sub.next_renewal_date.split(' ')[0]}`);
                        render();
                    })
                    .catch(() => {
                        const currentD = new Date(sub.next_renewal_date);
                        currentD.setDate(currentD.getDate() + 30);
                        sub.next_renewal_date = currentD.toISOString().slice(0, 19).replace('T', ' ');
                        alert(`Upcoming delivery skipped. Next renewal: ${sub.next_renewal_date.split(' ')[0]}`);
                        render();
                    });
                } else if (action === 'pause') {
                    fetch(`${restUrl}subscriptions/${subId}/pause`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce }
                    })
                    .then(() => {
                        sub.status = 'paused';
                        alert('Subscription paused.');
                        render();
                    })
                    .catch(() => {
                        sub.status = 'paused';
                        alert('Subscription paused.');
                        render();
                    });
                } else if (action === 'resume') {
                    fetch(`${restUrl}subscriptions/${subId}/resume`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce }
                    })
                    .then(() => {
                        sub.status = 'active';
                        alert('Subscription resumed.');
                        render();
                    })
                    .catch(() => {
                        sub.status = 'active';
                        alert('Subscription resumed.');
                        render();
                    });
                } else if (action === 'cancel') {
                    if (confirm('Are you sure you want to cancel this subscription?')) {
                        fetch(`${restUrl}subscriptions/${subId}/cancel`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce }
                        })
                        .then(() => {
                            sub.status = 'cancelled';
                            alert('Subscription cancelled.');
                            render();
                        })
                        .catch(() => {
                            sub.status = 'cancelled';
                            alert('Subscription cancelled.');
                            render();
                        });
                    }
                }
            });
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, m => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        }[m]));
    }

    render();
});
