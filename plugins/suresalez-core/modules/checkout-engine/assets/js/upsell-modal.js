/**
 * SureSalez 1-Click Post-Purchase Upsell Client Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    let checkoutData = (typeof SureSalezCheckoutData !== 'undefined') ? SureSalezCheckoutData : null;

    // Listen for order completion event
    document.addEventListener('suresalez:order_completed', (e) => {
        const orderInfo = e.detail || { order_id: 98452, total: 899.00 };
        showPostPurchaseUpsell(orderInfo);
    });

    function showPostPurchaseUpsell(orderInfo) {
        let mount = document.getElementById('suresalez-post-purchase-modal-mount');
        if (!mount) {
            mount = document.createElement('div');
            mount.id = 'suresalez-post-purchase-modal-mount';
            document.body.appendChild(mount);
        }

        const offer = {
            id: 1,
            title: 'Wait! Complete Your Setup with 35% Off!',
            product_title: 'Grade 5 Titanium Milanese Deployment Loop',
            product_image: 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=400&q=80',
            original_price: 149.00,
            offer_price: 96.85,
            savings: 52.15,
            timer_seconds: 300,
        };

        let secondsLeft = offer.timer_seconds;
        let timerInterval = null;

        mount.innerHTML = `
            <div class="suresalez-upsell-overlay is-visible" id="suresalez-upsell-modal">
                <div class="suresalez-upsell-card" role="dialog" aria-modal="true">
                    <header class="suresalez-upsell-header">
                        <span class="suresalez-upsell-badge">Exclusive 1-Click Post-Purchase Deal</span>
                        <h2 class="suresalez-upsell-title">${escapeHtml(offer.title)}</h2>
                        <div class="suresalez-upsell-timer-pill">
                            <span>⏱ Limited Offer:</span>
                            <span id="suresalez-upsell-countdown">05:00</span>
                        </div>
                    </header>
                    <div class="suresalez-upsell-body" id="suresalez-upsell-content">
                        <img src="${offer.product_image}" alt="${escapeHtml(offer.product_title)}" class="suresalez-upsell-product-img">
                        <h3 class="suresalez-upsell-product-name">${escapeHtml(offer.product_title)}</h3>
                        <p style="font-size:13px; color:#64748b; margin:0 0 16px 0; max-width:400px;">
                            Precision crafted to match your titanium chronograph unibody with magnetic micro-adjustment and dual safety release.
                        </p>
                        
                        <div class="suresalez-upsell-pricing">
                            <span class="suresalez-original-price">$${offer.original_price.toFixed(2)}</span>
                            <span class="suresalez-deal-price">$${offer.offer_price.toFixed(2)}</span>
                            <span class="suresalez-savings-pill">Save $${offer.savings.toFixed(2)} (35% OFF)</span>
                        </div>

                        <button type="button" class="suresalez-btn-accept-upsell" id="suresalez-accept-upsell-btn">
                            ⚡ Add to My Order with 1-Click ($${offer.offer_price.toFixed(2)})
                        </button>
                        
                        <button type="button" class="suresalez-btn-decline-upsell" id="suresalez-decline-upsell-btn">
                            No thanks, continue to my order confirmation →
                        </button>
                    </div>
                </div>
            </div>
        `;

        const countdownEl = document.getElementById('suresalez-upsell-countdown');
        timerInterval = setInterval(() => {
            secondsLeft--;
            if (secondsLeft <= 0) {
                clearInterval(timerInterval);
                closeModal();
                return;
            }
            const mins = String(Math.floor(secondsLeft / 60)).padStart(2, '0');
            const secs = String(secondsLeft % 60).padStart(2, '0');
            if (countdownEl) countdownEl.textContent = `${mins}:${secs}`;
        }, 1000);

        const acceptBtn = document.getElementById('suresalez-accept-upsell-btn');
        const declineBtn = document.getElementById('suresalez-decline-upsell-btn');

        const closeModal = () => {
            clearInterval(timerInterval);
            const overlay = document.getElementById('suresalez-upsell-modal');
            if (overlay) {
                overlay.classList.remove('is-visible');
                setTimeout(() => overlay.remove(), 350);
            }
        };

        declineBtn.addEventListener('click', () => {
            closeModal();
            alert(`Order #${orderInfo.order_id} confirmed! Thank you for your purchase.`);
        });

        acceptBtn.addEventListener('click', async () => {
            acceptBtn.disabled = true;
            acceptBtn.textContent = 'Adding to order & charging saved card...';

            try {
                if (checkoutData && checkoutData.upsellEndpoints && checkoutData.upsellEndpoints.accept) {
                    await fetch(checkoutData.upsellEndpoints.accept, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            offer_id: offer.id,
                            order_id: orderInfo.order_id,
                        }),
                    });
                }
            } catch (err) {
                console.error('Upsell error:', err);
            }

            const bodyContent = document.getElementById('suresalez-upsell-content');
            if (bodyContent) {
                bodyContent.innerHTML = `
                    <div style="padding:20px 0; text-align:center;">
                        <div style="font-size:42px; margin-bottom:12px;">🎉</div>
                        <h3 style="font-size:20px; font-weight:800; color:#10b981; margin:0 0 8px 0;">Item Added to Your Order!</h3>
                        <p style="font-size:14px; color:#475569; margin-bottom:20px;">
                            ${escapeHtml(offer.product_title)} has been appended to Order #${orderInfo.order_id}. No additional shipping fees applied!
                        </p>
                        <button type="button" class="suresalez-btn-accept-upsell" id="suresalez-upsell-done-btn" style="background:#0f172a;">
                            View Final Order Confirmation
                        </button>
                    </div>
                `;

                document.getElementById('suresalez-upsell-done-btn').addEventListener('click', () => {
                    closeModal();
                    alert(`Order #${orderInfo.order_id} updated with your upsell item!`);
                });
            }
        });
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
