/**
 * SureSalez Core — Dual-Sided Viral Referral Sharing Client Controller
 * Sprint 09: Loyalty, VIP Tiers & Headless Mobile APIs
 */

(function () {
    'use strict';

    function initReferralBanner() {
        const mount = document.getElementById('suresalez-referral-banner-mount');
        if (!mount) {
            return;
        }

        const data = window.suresalezLoyaltyData || {};
        const referral = data.referral || {
            referral_code: 'SURE-VIP10',
            referral_url: window.location.origin + '/?ref=SURE-VIP10',
            total_invites: 3,
            successful_orders: 2,
            total_points_earned: 1000,
            reward_per_referral: 500,
            friend_discount: 15.0
        };

        const code = referral.referral_code || 'SURE-VIP10';
        const url = referral.referral_url || (window.location.origin + '/?ref=' + code);
        const discount = referral.friend_discount || 15;
        const reward = referral.reward_per_referral || 500;
        const successful = referral.successful_orders || 0;

        mount.innerHTML = `
            <div class="suresalez-referral-banner" role="complementary" aria-label="SureSalez Viral Referral Program">
                <div class="suresalez-ref-content">
                    <h4 class="suresalez-ref-heading">
                        <span>🎁 Give ${discount}% Off, Get ${reward} Points</span>
                        <span class="suresalez-ref-pill">VIP Referral</span>
                    </h4>
                    <p class="suresalez-ref-subtext">
                        Share your bespoke invitation code with connoisseurs. They receive ${discount}% off their debut acquisition; you receive ${reward} points ($25.00 value) automatically upon order completion.
                    </p>
                    <div class="suresalez-ref-stats-badge">
                        <span>⚡ ${successful} successful friend orders rewarded</span>
                        <span>•</span>
                        <span>${(successful * reward).toLocaleString()} points earned</span>
                    </div>
                </div>

                <div class="suresalez-ref-action-box">
                    <div class="suresalez-ref-code" id="suresalez-ref-code-text">${code}</div>
                    <button type="button" class="suresalez-btn-copy" id="suresalez-btn-copy-ref" data-url="${url}">
                        Copy Link
                    </button>
                </div>
            </div>
        `;

        const copyBtn = document.getElementById('suresalez-btn-copy-ref');
        if (copyBtn) {
            copyBtn.addEventListener('click', function () {
                const targetUrl = copyBtn.getAttribute('data-url') || url;
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(targetUrl).then(function () {
                        copyBtn.textContent = 'Copied! ✓';
                        copyBtn.style.background = '#059669';
                        setTimeout(function () {
                            copyBtn.textContent = 'Copy Link';
                            copyBtn.style.background = '#4f46e5';
                        }, 2000);
                    }).catch(function () {
                        prompt('Copy your VIP referral link:', targetUrl);
                    });
                } else {
                    prompt('Copy your VIP referral link:', targetUrl);
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initReferralBanner);
    } else {
        initReferralBanner();
    }
})();
