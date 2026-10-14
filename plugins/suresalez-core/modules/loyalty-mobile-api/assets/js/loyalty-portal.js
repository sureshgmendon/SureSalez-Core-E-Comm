/**
 * SureSalez Core — Gamified Loyalty Rewards & VIP Tiers Client Controller
 * Sprint 09: Loyalty, VIP Tiers & Headless Mobile APIs
 */

(function () {
    'use strict';

    function initLoyaltyPortal() {
        const mount = document.getElementById('suresalez-loyalty-rewards-mount');
        if (!mount) {
            return;
        }

        const data = window.suresalezLoyaltyData || {
            loyalty: {
                points_balance: 1250,
                lifetime_points: 2500,
                tier_level: 'gold',
                tier_multiplier: 1.5,
                redemption_value: 62.50,
                next_tier_target: 3000,
                points_to_next_tier: 500
            },
            point_value: 0.05,
            min_points: 200,
            max_percent: 50.0,
            rest_url: '/wp-json/suresalez/v1/',
            nonce: ''
        };

        const loyalty = data.loyalty;
        const balance = loyalty.points_balance || 0;
        const tier = loyalty.tier_level || 'bronze';
        const multiplier = loyalty.tier_multiplier || 1.0;
        const lifetime = loyalty.lifetime_points || 0;
        const target = loyalty.next_tier_target || 3000;
        const progressPercent = Math.min(100, Math.round((lifetime / target) * 100));

        mount.innerHTML = `
            <div class="suresalez-loyalty-container" role="region" aria-label="SureSalez VIP Loyalty Portal">
                <div class="suresalez-loyalty-header">
                    <div>
                        <h2 class="suresalez-loyalty-title">SureSalez VIP Atelier Rewards</h2>
                        <div class="suresalez-loyalty-tagline">Earn points on every purchase & unlock bespoke privileges</div>
                    </div>
                    <div class="suresalez-tier-badge suresalez-tier-${tier}">
                        <span>✦</span> ${tier.toUpperCase()} VIP (${multiplier}x Points)
                    </div>
                </div>

                <div class="suresalez-loyalty-grid">
                    <div class="suresalez-stat-card">
                        <div class="suresalez-stat-label">Available Balance</div>
                        <div class="suresalez-stat-value" id="suresalez-points-display">${balance.toLocaleString()} Pts</div>
                        <div class="suresalez-stat-subtext">Cashback Value: $${(balance * data.point_value).toFixed(2)} USD</div>
                    </div>
                    <div class="suresalez-stat-card">
                        <div class="suresalez-stat-label">Lifetime Points & Tier</div>
                        <div class="suresalez-stat-value">${lifetime.toLocaleString()} Pts</div>
                        <div class="suresalez-stat-subtext">${target - lifetime > 0 ? (target - lifetime).toLocaleString() + ' pts to next tier' : 'Top Tier Achieved!'}</div>
                    </div>
                </div>

                <div class="suresalez-progress-section">
                    <div class="suresalez-progress-header">
                        <span>Tier Progress: <strong>${tier.toUpperCase()}</strong></span>
                        <span>${progressPercent}% towards next tier</span>
                    </div>
                    <div class="suresalez-progress-bar-bg">
                        <div class="suresalez-progress-bar-fill" style="width: ${progressPercent}%;"></div>
                    </div>
                </div>

                <div class="suresalez-redemption-box">
                    <h3 class="suresalez-redemption-title">Redeem Points at Checkout</h3>
                    <p class="suresalez-redemption-desc">Slide to choose how many points to redeem. Min: ${data.min_points} pts ($${(data.min_points * data.point_value).toFixed(2)}). Up to ${data.max_percent}% cart discount.</p>
                    
                    <div class="suresalez-slider-controls">
                        <input type="range" class="suresalez-points-slider" id="suresalez-points-slider" 
                               min="0" max="${balance}" step="50" value="${balance >= data.min_points ? data.min_points : 0}"
                               ${balance < data.min_points ? 'disabled' : ''}>
                        <div class="suresalez-slider-labels">
                            <span>0 Pts ($0.00)</span>
                            <span id="suresalez-slider-current-label">${balance >= data.min_points ? data.min_points : 0} Pts</span>
                            <span>${balance.toLocaleString()} Pts ($${(balance * data.point_value).toFixed(2)})</span>
                        </div>
                    </div>

                    <div class="suresalez-redemption-summary">
                        <div>
                            <span style="font-size: 13px; color: #94a3b8; display: block;">Instant Order Discount</span>
                            <span class="suresalez-discount-display" id="suresalez-discount-val">
                                $${((balance >= data.min_points ? data.min_points : 0) * data.point_value).toFixed(2)}
                            </span>
                        </div>
                        <button type="button" class="suresalez-btn-redeem" id="suresalez-btn-redeem"
                                ${balance < data.min_points ? 'disabled' : ''}>
                            Apply ${balance >= data.min_points ? data.min_points : 0} Points
                        </button>
                    </div>
                </div>
            </div>
        `;

        const slider = document.getElementById('suresalez-points-slider');
        const sliderLabel = document.getElementById('suresalez-slider-current-label');
        const discountVal = document.getElementById('suresalez-discount-val');
        const redeemBtn = document.getElementById('suresalez-btn-redeem');

        if (slider && sliderLabel && discountVal && redeemBtn) {
            slider.addEventListener('input', function (e) {
                const val = parseInt(e.target.value, 10) || 0;
                sliderLabel.textContent = val.toLocaleString() + ' Pts';
                const discount = (val * data.point_value).toFixed(2);
                discountVal.textContent = '$' + discount;
                redeemBtn.textContent = 'Apply ' + val.toLocaleString() + ' Points';

                if (val > 0 && val < data.min_points) {
                    redeemBtn.disabled = true;
                    redeemBtn.title = 'Minimum ' + data.min_points + ' points required';
                } else if (val === 0) {
                    redeemBtn.disabled = true;
                } else {
                    redeemBtn.disabled = false;
                    redeemBtn.title = '';
                }
            });

            redeemBtn.addEventListener('click', function () {
                const val = parseInt(slider.value, 10);
                if (val < data.min_points || val > balance) {
                    return;
                }

                redeemBtn.disabled = true;
                redeemBtn.textContent = 'Applying...';

                // Dispatch global event for checkout engine integration
                window.dispatchEvent(new CustomEvent('suresalez:loyalty:redeemed', {
                    detail: {
                        points: val,
                        discount: val * data.point_value
                    }
                }));

                setTimeout(function () {
                    redeemBtn.textContent = 'Applied ✓';
                    redeemBtn.style.background = '#059669';
                }, 400);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoyaltyPortal);
    } else {
        initLoyaltyPortal();
    }
})();
