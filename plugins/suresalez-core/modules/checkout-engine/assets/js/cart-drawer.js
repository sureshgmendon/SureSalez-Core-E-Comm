/**
 * SureSalez Slide-Out Cart Drawer Client Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    let checkoutData = null;

    if (typeof SureSalezCheckoutData !== 'undefined') {
        checkoutData = SureSalezCheckoutData;
    }

    // Initialize Drawer Mount
    const mount = document.getElementById('suresalez-cart-drawer-mount');
    if (!mount) {
        return;
    }

    let cartState = (checkoutData && checkoutData.cart) ? checkoutData.cart : {
        items: [
            {
                id: 101,
                title: 'Apex Pro Chronograph — Titanium Edition',
                variant: 'Midnight Slate / Titanium Link',
                price: 899.00,
                quantity: 1,
                image: 'https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&w=300&q=80',
            }
        ],
        addons: { gift_wrap: false, polishing_cloth: false },
        order_notes: '',
        subtotal: 899.00,
        addon_total: 0.00,
        total: 899.00,
        shipping_threshold: 150.00,
        shipping_progress: 100,
        shipping_remaining: 0,
        free_shipping: true,
        item_count: 1,
    };

    function recalculate() {
        let subtotal = 0;
        cartState.items.forEach(i => {
            subtotal += parseFloat(i.price) * parseInt(i.quantity, 10);
        });

        let addonTotal = 0;
        if (cartState.addons.gift_wrap) addonTotal += 5.00;
        if (cartState.addons.polishing_cloth) addonTotal += 15.00;

        const threshold = 150.00;
        const progress = Math.min(100, Math.round((subtotal / threshold) * 100));
        const remaining = Math.max(0, parseFloat((threshold - subtotal).toFixed(2)));

        cartState.subtotal = parseFloat(subtotal.toFixed(2));
        cartState.addon_total = parseFloat(addonTotal.toFixed(2));
        cartState.total = parseFloat((subtotal + addonTotal).toFixed(2));
        cartState.shipping_progress = progress;
        cartState.shipping_remaining = remaining;
        cartState.free_shipping = subtotal >= threshold;
        cartState.item_count = cartState.items.reduce((acc, i) => acc + parseInt(i.quantity, 10), 0);

        updateHeaderBadge();
    }

    function updateHeaderBadge() {
        document.querySelectorAll('.suresalez-cart-badge').forEach(badge => {
            badge.textContent = cartState.item_count;
        });
    }

    function renderDrawer() {
        mount.innerHTML = `
            <div class="suresalez-cart-drawer-overlay" id="suresalez-drawer-overlay"></div>
            <aside class="suresalez-cart-drawer" id="suresalez-drawer-aside" role="dialog" aria-modal="true" aria-label="Shopping Cart">
                <header class="suresalez-drawer-header">
                    <h3>
                        <span>🛒 Shopping Cart</span>
                        <span style="font-size:13px; font-weight:500; color:#64748b;">(${cartState.item_count} items)</span>
                    </h3>
                    <button type="button" class="suresalez-drawer-close" id="suresalez-drawer-close-btn" aria-label="Close cart">&times;</button>
                </header>

                <div class="suresalez-shipping-meter">
                    <div class="suresalez-shipping-msg">
                        ${cartState.free_shipping 
                            ? '🎉 Unlocked Free Worldwide Express Delivery!' 
                            : `Add $${cartState.shipping_remaining.toFixed(2)} more to unlock Free Express Delivery!`}
                    </div>
                    <div class="suresalez-progress-bar-wrap">
                        <div class="suresalez-progress-bar-fill" style="width: ${cartState.shipping_progress}%;"></div>
                    </div>
                </div>

                <div class="suresalez-drawer-body">
                    ${cartState.items.length === 0 ? `
                        <div style="text-align:center; padding:40px 0; color:#64748b;">
                            <p style="font-size:16px; font-weight:600; margin:0 0 8px 0;">Your cart is empty.</p>
                            <p style="font-size:13px;">Explore our precision titanium catalog to add items.</p>
                        </div>
                    ` : cartState.items.map(item => `
                        <div class="suresalez-cart-item" data-product-id="${item.id}">
                            <img src="${item.image}" alt="${escapeHtml(item.title)}">
                            <div class="suresalez-cart-item-details">
                                <div>
                                    <h4 class="suresalez-cart-item-title">${escapeHtml(item.title)}</h4>
                                    <div class="suresalez-cart-item-variant">${escapeHtml(item.variant || '')}</div>
                                </div>
                                <div class="suresalez-cart-item-footer">
                                    <div class="suresalez-qty-stepper">
                                        <button type="button" class="suresalez-qty-btn" data-action="decrease" data-id="${item.id}">-</button>
                                        <span class="suresalez-qty-val">${item.quantity}</span>
                                        <button type="button" class="suresalez-qty-btn" data-action="increase" data-id="${item.id}">+</button>
                                    </div>
                                    <span class="suresalez-cart-item-price">$${(item.price * item.quantity).toFixed(2)}</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}

                    <!-- In-Drawer Add-Ons -->
                    <div class="suresalez-drawer-addons">
                        <div style="font-weight:700; font-size:12.5px; color:#0f172a;">Frequently Added Together:</div>
                        
                        <div class="suresalez-addon-row">
                            <label class="suresalez-addon-label">
                                <input type="checkbox" id="addon-gift-wrap" ${cartState.addons.gift_wrap ? 'checked' : ''}>
                                <span>🎁 Luxury Gift Wrap & Personalized Card</span>
                            </label>
                            <span class="suresalez-addon-price">+$5.00</span>
                        </div>

                        <div class="suresalez-addon-row">
                            <label class="suresalez-addon-label">
                                <input type="checkbox" id="addon-cloth" ${cartState.addons.polishing_cloth ? 'checked' : ''}>
                                <span>✨ Microfiber Titanium Polishing Cloth</span>
                            </label>
                            <span class="suresalez-addon-price">+$15.00</span>
                        </div>
                    </div>

                    <!-- Order Notes Accordion -->
                    <div class="suresalez-drawer-notes-wrap">
                        <button type="button" class="suresalez-drawer-notes-toggle" id="suresalez-notes-toggle">
                            ${cartState.order_notes ? 'Edit Delivery Instructions' : '+ Add Delivery Instructions / Note'}
                        </button>
                        <div id="suresalez-notes-field" style="display:${cartState.order_notes ? 'block' : 'none'};">
                            <textarea rows="2" placeholder="Special delivery instructions or gate code...">${escapeHtml(cartState.order_notes)}</textarea>
                        </div>
                    </div>
                </div>

                <footer class="suresalez-drawer-footer">
                    <div class="suresalez-summary-row">
                        <span>Items Subtotal:</span>
                        <span>$${cartState.subtotal.toFixed(2)}</span>
                    </div>
                    ${cartState.addon_total > 0 ? `
                        <div class="suresalez-summary-row">
                            <span>Add-Ons:</span>
                            <span>+$${cartState.addon_total.toFixed(2)}</span>
                        </div>
                    ` : ''}
                    <div class="suresalez-summary-row">
                        <span>Shipping:</span>
                        <span>${cartState.free_shipping ? '<strong style="color:#10b981;">FREE</strong>' : '$15.00'}</span>
                    </div>
                    <div class="suresalez-summary-row total">
                        <span>Estimated Total:</span>
                        <span>$${cartState.total.toFixed(2)}</span>
                    </div>

                    <a href="/checkout" class="suresalez-btn-checkout" id="suresalez-drawer-checkout-btn">
                        Proceed to Checkout ($${cartState.total.toFixed(2)}) →
                    </a>
                </footer>
            </aside>
        `;

        bindEvents();
    }

    function bindEvents() {
        const overlay = document.getElementById('suresalez-drawer-overlay');
        const aside = document.getElementById('suresalez-drawer-aside');
        const closeBtn = document.getElementById('suresalez-drawer-close-btn');

        const closeDrawer = () => {
            if (overlay) overlay.classList.remove('is-open');
            if (aside) aside.classList.remove('is-open');
        };

        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (overlay) overlay.addEventListener('click', closeDrawer);

        // Qty steppers
        mount.querySelectorAll('.suresalez-qty-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const action = btn.getAttribute('data-action');
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const item = cartState.items.find(i => i.id === id);
                if (item) {
                    if (action === 'increase') {
                        item.quantity += 1;
                    } else if (action === 'decrease') {
                        item.quantity -= 1;
                        if (item.quantity <= 0) {
                            cartState.items = cartState.items.filter(i => i.id !== id);
                        }
                    }
                    recalculate();
                    renderDrawer();
                    openDrawer();
                }
            });
        });

        // Addon checkboxes
        const giftCheck = document.getElementById('addon-gift-wrap');
        if (giftCheck) {
            giftCheck.addEventListener('change', (e) => {
                cartState.addons.gift_wrap = e.target.checked;
                recalculate();
                renderDrawer();
                openDrawer();
            });
        }

        const clothCheck = document.getElementById('addon-cloth');
        if (clothCheck) {
            clothCheck.addEventListener('change', (e) => {
                cartState.addons.polishing_cloth = e.target.checked;
                recalculate();
                renderDrawer();
                openDrawer();
            });
        }

        // Notes toggle & input
        const notesToggle = document.getElementById('suresalez-notes-toggle');
        const notesField = document.getElementById('suresalez-notes-field');
        if (notesToggle && notesField) {
            notesToggle.addEventListener('click', () => {
                const isHidden = notesField.style.display === 'none';
                notesField.style.display = isHidden ? 'block' : 'none';
            });
            const textarea = notesField.querySelector('textarea');
            if (textarea) {
                textarea.addEventListener('input', (e) => {
                    cartState.order_notes = e.target.value;
                });
            }
        }
    }

    function openDrawer() {
        const overlay = document.getElementById('suresalez-drawer-overlay');
        const aside = document.getElementById('suresalez-drawer-aside');
        if (overlay) overlay.classList.add('is-open');
        if (aside) aside.classList.add('is-open');
    }

    // Global Listeners for Opening Drawer
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.suresalez-cart-trigger');
        if (trigger) {
            e.preventDefault();
            openDrawer();
        }

        // Catch clicks on product "Add to Cart" buttons
        const addBtn = e.target.closest('[data-suresalez-action="add-to-cart"], .single_add_to_cart_button');
        if (addBtn) {
            e.preventDefault();
            // Open drawer
            openDrawer();
        }
    });

    // Custom event to programmatically open cart
    document.addEventListener('suresalez:open_cart', openDrawer);

    // Escape key closes drawer
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const overlay = document.getElementById('suresalez-drawer-overlay');
            const aside = document.getElementById('suresalez-drawer-aside');
            if (overlay) overlay.classList.remove('is-open');
            if (aside) aside.classList.remove('is-open');
        }
    });

    // Initial render
    recalculate();
    renderDrawer();
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
