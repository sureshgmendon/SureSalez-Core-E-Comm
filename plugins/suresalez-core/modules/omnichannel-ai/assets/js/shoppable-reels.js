/**
 * SureSalez Shoppable Video Reels, Hotspot Mapping & Campaign Banner Hydration.
 *
 * @package SureSalez\Modules\OmnichannelAI
 */

(function () {
    'use strict';

    const config = window.suresalezOmnichannelData || {
        restUrl: '/wp-json/suresalez/v1/',
        nonce: '',
        reels: [],
        campaign: null,
        cartUrl: '/cart/',
        checkoutUrl: '/checkout/',
    };

    /**
     * Initialize Omnichannel client features on DOMContentLoaded.
     */
    function init() {
        hydrateCampaignBanner();
        hydrateShoppableReels();
        bindGlobalEvents();
    }

    /* ==========================================================================
       1. Campaign Banner & Dynamic Countdown
       ========================================================================== */

    function hydrateCampaignBanner() {
        const mount = document.getElementById('suresalez-campaign-banner-mount');
        if (!mount || !config.campaign) return;

        const c = config.campaign;
        const mods = c.theme_mods || {};
        const bannerText = mods.banner_text || 'Special Promotion Active';
        const bannerCta = mods.banner_cta || 'Shop Now';
        const bannerUrl = mods.banner_cta_url || '#suresalez-shoppable-reels-mount';
        const bannerBg = mods.banner_bg || '#0f172a';
        const bannerColor = mods.banner_text_color || '#f8fafc';
        const badge = mods.badge || 'LIMITED TIME';

        mount.innerHTML = `
            <div class="suresalez-campaign-banner" style="background-color: ${escapeHtml(bannerBg)}; color: ${escapeHtml(bannerColor)};" role="alert">
                <div class="suresalez-campaign-banner-inner">
                    <span class="suresalez-campaign-badge">${escapeHtml(badge)}</span>
                    <span class="suresalez-campaign-text">${escapeHtml(bannerText)}</span>
                    <div class="suresalez-campaign-countdown" id="suresalez-campaign-countdown" aria-label="Sale countdown">
                        <span class="suresalez-timer-unit" data-unit="hours">--</span>h
                        <span class="suresalez-timer-unit" data-unit="minutes">--</span>m
                        <span class="suresalez-timer-unit" data-unit="seconds">--</span>s
                    </div>
                    <a href="${escapeHtml(bannerUrl)}" class="suresalez-campaign-cta">${escapeHtml(bannerCta)} &rarr;</a>
                </div>
            </div>
        `;

        startCountdown(c.end_time);
    }

    function startCountdown(endTimeStr) {
        if (!endTimeStr) return;
        const endTime = new Date(endTimeStr.replace(/-/g, '/')).getTime();

        function update() {
            const now = new Date().getTime();
            const diff = Math.max(0, endTime - now);

            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);

            const hEl = document.querySelector('.suresalez-timer-unit[data-unit="hours"]');
            const mEl = document.querySelector('.suresalez-timer-unit[data-unit="minutes"]');
            const sEl = document.querySelector('.suresalez-timer-unit[data-unit="seconds"]');

            if (hEl) hEl.textContent = String(hours).padStart(2, '0');
            if (mEl) mEl.textContent = String(minutes).padStart(2, '0');
            if (sEl) sEl.textContent = String(seconds).padStart(2, '0');
        }

        update();
        setInterval(update, 1000);
    }

    /* ==========================================================================
       2. Shoppable Video Reels Hydration & Interactions
       ========================================================================== */

    function hydrateShoppableReels() {
        const mount = document.getElementById('suresalez-shoppable-reels-mount');
        if (!mount) return;

        const reels = config.reels || [];
        if (!reels.length) return;

        let cardsHtml = '';
        reels.forEach((reel, index) => {
            const firstProd = reel.tagged_products && reel.tagged_products.length ? reel.tagged_products[0] : null;
            const count = reel.tagged_products ? reel.tagged_products.length : 0;

            let hotspotsHtml = '';
            if (reel.tagged_products) {
                reel.tagged_products.forEach(p => {
                    hotspotsHtml += `
                        <button class="suresalez-hotspot-pin" 
                            style="left: ${p.x_pct}%; top: ${p.y_pct}%;"
                            data-product-id="${p.product_id}"
                            data-product-title="${escapeHtml(p.title)}"
                            data-product-price="${p.price}"
                            aria-label="${escapeHtml(p.title)} - $${p.price}">
                            <span class="suresalez-hotspot-pulse"></span>
                            <span class="suresalez-hotspot-dot"></span>
                        </button>
                    `;
                });
            }

            cardsHtml += `
                <article class="suresalez-reel-card" data-reel-id="${reel.id}" data-reel-index="${index}" tabindex="0">
                    <div class="suresalez-reel-media">
                        <img src="${escapeHtml(reel.thumbnail_url)}" alt="${escapeHtml(reel.title)}" class="suresalez-reel-thumb" loading="lazy" />
                        <div class="suresalez-reel-overlay"></div>
                        <div class="suresalez-reel-play-btn" aria-label="Play Reel">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                        <span class="suresalez-reel-duration">0:${String(reel.duration).padStart(2, '0')}</span>
                        ${count > 0 ? `
                            <div class="suresalez-reel-hotspot-badge">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                    <line x1="3" y1="6" x2="21" y2="6"/>
                                    <path d="M16 10a4 4 0 0 1-8 0"/>
                                </svg>
                                <span>${count === 1 ? '1 Tagged Product' : count + ' Tagged Products'}</span>
                            </div>
                        ` : ''}
                        ${hotspotsHtml}
                    </div>
                    <div class="suresalez-reel-meta">
                        <h4 class="suresalez-reel-name">${escapeHtml(reel.title)}</h4>
                        ${firstProd ? `
                            <div class="suresalez-reel-lead-product">
                                <span class="suresalez-reel-prod-title">${escapeHtml(firstProd.title)}</span>
                                <span class="suresalez-reel-prod-price">$${Number(firstProd.price).toFixed(2)}</span>
                            </div>
                        ` : ''}
                        <div class="suresalez-reel-stats">
                            <span>${Number(reel.views_count).toLocaleString()} views</span>
                            <span class="suresalez-dot-sep">•</span>
                            <button type="button" class="suresalez-reel-quick-buy" 
                                data-product-id="${firstProd ? firstProd.product_id : 0}"
                                data-product-title="${firstProd ? escapeHtml(firstProd.title) : ''}"
                                data-product-price="${firstProd ? firstProd.price : 0}">
                                Quick Buy
                            </button>
                        </div>
                    </div>
                </article>
            `;
        });

        mount.innerHTML = `
            <div class="suresalez-reels-container" data-reels-count="${reels.length}">
                <div class="suresalez-reels-header">
                    <div class="suresalez-reels-badge">
                        <span class="suresalez-pulse-dot"></span>
                        <span>SHOP THE LOOK IN MOTION</span>
                    </div>
                    <h3 class="suresalez-reels-title">Interactive Shoppable Reels</h3>
                    <p class="suresalez-reels-subtitle">Tap any hotspot to view fine watchmaking details and add directly to your bag.</p>
                </div>
                <div class="suresalez-reels-carousel" role="region" aria-label="Shoppable Video Carousel">
                    ${cardsHtml}
                </div>
                <div id="suresalez-reel-modal" class="suresalez-reel-modal" role="dialog" aria-modal="true" aria-hidden="true" style="display: none;">
                    <div class="suresalez-reel-modal-backdrop"></div>
                    <div class="suresalez-reel-modal-content">
                        <button type="button" class="suresalez-reel-modal-close" aria-label="Close Reel">&times;</button>
                        <div class="suresalez-reel-viewport">
                            <video id="suresalez-active-video" class="suresalez-active-video" playsinline loop preload="metadata">
                                <source src="" type="video/mp4" />
                            </video>
                            <div id="suresalez-modal-hotspots" class="suresalez-modal-hotspots"></div>
                        </div>
                        <div id="suresalez-reel-product-drawer" class="suresalez-reel-product-drawer"></div>
                    </div>
                </div>
            </div>
        `;
    }

    /* ==========================================================================
       3. Global Event Delegation
       ========================================================================== */

    function bindGlobalEvents() {
        document.addEventListener('click', function (e) {
            // 1. Reel card click -> open modal
            const card = e.target.closest('.suresalez-reel-card');
            const isQuickBuy = e.target.closest('.suresalez-reel-quick-buy');
            const isPin = e.target.closest('.suresalez-hotspot-pin');

            if (card && !isQuickBuy && !isPin) {
                const reelId = parseInt(card.getAttribute('data-reel-id'), 10);
                openReelModal(reelId);
                return;
            }

            // 2. Hotspot pin click
            if (isPin) {
                e.stopPropagation();
                const pid = parseInt(isPin.getAttribute('data-product-id'), 10);
                const title = isPin.getAttribute('data-product-title');
                const price = parseFloat(isPin.getAttribute('data-product-price'));
                
                trackCapiEvent('ViewContent', { content_ids: [pid], content_name: title, value: price });
                openReelDrawerForProduct({ product_id: pid, title: title, price: price });
                return;
            }

            // 3. Quick Buy / Add to bag
            const buyBtn = e.target.closest('.suresalez-reel-quick-buy, .suresalez-drawer-add-to-bag');
            if (buyBtn) {
                e.stopPropagation();
                handleQuickBuy(buyBtn);
                return;
            }

            // 4. Close modal
            if (e.target.closest('.suresalez-reel-modal-close, .suresalez-reel-modal-backdrop')) {
                closeReelModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeReelModal();
            }
        });
    }

    function openReelModal(reelId) {
        const reel = (config.reels || []).find(r => r.id === reelId);
        if (!reel) return;

        const modal = document.getElementById('suresalez-reel-modal');
        const video = document.getElementById('suresalez-active-video');
        const hotspotsContainer = document.getElementById('suresalez-modal-hotspots');
        const drawer = document.getElementById('suresalez-reel-product-drawer');

        if (!modal || !video) return;

        // Set video source
        video.src = reel.video_url;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');

        // Increment view count via REST
        fetch(`${config.restUrl}reels/${reelId}/view`, { method: 'POST' }).catch(() => {});

        // Fire CAPI PageView/ViewContent beacon
        trackCapiEvent('ViewContent', { content_ids: [reelId], content_type: 'shoppable_reel', content_name: reel.title });

        // Populate hotspots in modal
        if (hotspotsContainer) {
            let pinsHtml = '';
            (reel.tagged_products || []).forEach(p => {
                pinsHtml += `
                    <button class="suresalez-hotspot-pin" 
                        style="left: ${p.x_pct}%; top: ${p.y_pct}%;"
                        data-product-id="${p.product_id}"
                        data-product-title="${escapeHtml(p.title)}"
                        data-product-price="${p.price}">
                        <span class="suresalez-hotspot-pulse"></span>
                        <span class="suresalez-hotspot-dot"></span>
                    </button>
                `;
            });
            hotspotsContainer.innerHTML = pinsHtml;
        }

        // Show first product in drawer
        if (reel.tagged_products && reel.tagged_products.length) {
            openReelDrawerForProduct(reel.tagged_products[0]);
        }

        // Play video
        video.play().catch(() => {});
    }

    function openReelDrawerForProduct(prod) {
        const drawer = document.getElementById('suresalez-reel-product-drawer');
        if (!drawer || !prod) return;

        drawer.innerHTML = `
            <div class="suresalez-drawer-product-card">
                <div class="suresalez-drawer-product-info">
                    <h5 class="suresalez-drawer-product-title">${escapeHtml(prod.title)}</h5>
                    <p class="suresalez-drawer-product-price">$${Number(prod.price).toFixed(2)}</p>
                </div>
                <button type="button" class="suresalez-drawer-add-to-bag" 
                    data-product-id="${prod.product_id}"
                    data-product-title="${escapeHtml(prod.title)}"
                    data-product-price="${prod.price}">
                    Add to Bag
                </button>
            </div>
        `;
    }

    function closeReelModal() {
        const modal = document.getElementById('suresalez-reel-modal');
        const video = document.getElementById('suresalez-active-video');
        if (!modal) return;

        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        if (video) {
            video.pause();
            video.src = '';
        }
    }

    function handleQuickBuy(btn) {
        const pid = parseInt(btn.getAttribute('data-product-id'), 10);
        const title = btn.getAttribute('data-product-title') || 'Featured Item';
        const price = parseFloat(btn.getAttribute('data-product-price') || '0');

        if (!pid) return;

        const origText = btn.textContent;
        btn.textContent = 'Adding...';
        btn.disabled = true;

        // 1. Fire CAPI AddToCart event
        trackCapiEvent('AddToCart', {
            content_ids: [pid],
            content_name: title,
            value: price,
            currency: 'USD',
        });

        // 2. Dispatch cross-module event to trigger Sprint 02 slide-out cart drawer
        const cartEvent = new CustomEvent('suresalez:cart-updated', {
            bubbles: true,
            detail: {
                productId: pid,
                quantity: 1,
                title: title,
                price: price,
            },
        });
        document.dispatchEvent(cartEvent);

        const openCartEvent = new CustomEvent('suresalez:open-cart', {
            bubbles: true,
            detail: { source: 'shoppable-reels' },
        });
        document.dispatchEvent(openCartEvent);

        setTimeout(() => {
            btn.textContent = 'Added ✓';
            setTimeout(() => {
                btn.textContent = origText;
                btn.disabled = false;
            }, 1500);
        }, 300);
    }

    function trackCapiEvent(eventName, customData) {
        const payload = {
            event_name: eventName,
            event_id: 'evt_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9),
            custom_data: customData || {},
        };

        if (window.fetch) {
            fetch(`${config.restUrl}capi/track`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': config.nonce || '',
                },
                body: JSON.stringify(payload),
            }).catch(() => {});
        }
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
