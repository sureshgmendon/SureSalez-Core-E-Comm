/**
 * SureSalez Checkout Extensibility & Multi-Step Client Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    const mount = document.getElementById('suresalez-checkout-mount');
    if (!mount) {
        return;
    }

    let checkoutData = (typeof SureSalezCheckoutData !== 'undefined') ? SureSalezCheckoutData : null;
    let config = (checkoutData && checkoutData.config) ? checkoutData.config : {
        layout: 'multi_step',
        suppress_company: true,
        suppress_address_2: true,
        enable_express_pay: true,
    };

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
        subtotal: 899.00,
        addon_total: 0.00,
        total: 899.00,
        free_shipping: true,
    };

    let currentStep = 'contact'; // 'contact' -> 'shipping' -> 'payment'
    let walletDiscount = 0.00;

    let formData = {
        email: 'alex.vance@enterprise.io',
        phone: '+1 (555) 234-5678',
        first_name: 'Alexander',
        last_name: 'Vance',
        address_1: '100 Port Street, Suite 400',
        city: 'Newark',
        country: 'US',
        postal_code: '07114',
        shipping_method: 'express_hub',
        payment_method: 'card',
        tax_id: 'US-EIN-94821045',
    };

    // Listen for wallet store credit application
    document.addEventListener('suresalez:wallet_applied', (e) => {
        if (e.detail && e.detail.applied) {
            walletDiscount = parseFloat(e.detail.amount || 0);
        } else {
            walletDiscount = 0.00;
        }
        renderOrderSummary();
    });

    function renderCheckout() {
        mount.innerHTML = `
            <div class="suresalez-checkout-layout">
                <!-- Left Column: Form & Steps -->
                <div class="suresalez-checkout-main">
                    <!-- Breadcrumbs -->
                    <nav class="suresalez-checkout-breadcrumbs" aria-label="Checkout Steps">
                        <span class="suresalez-step-item ${currentStep === 'contact' ? 'is-active' : 'is-completed'}">1. Information</span>
                        <span class="suresalez-step-separator">›</span>
                        <span class="suresalez-step-item ${currentStep === 'shipping' ? 'is-active' : (currentStep === 'payment' ? 'is-completed' : '')}">2. Shipping & Hubs</span>
                        <span class="suresalez-step-separator">›</span>
                        <span class="suresalez-step-item ${currentStep === 'payment' ? 'is-active' : ''}">3. Payment & Review</span>
                    </nav>

                    ${config.enable_express_pay ? `
                        <!-- Express Checkout -->
                        <div class="suresalez-express-checkout">
                            <div class="suresalez-express-title">Express Fastlane Checkout</div>
                            <div class="suresalez-express-buttons">
                                <button type="button" class="suresalez-btn-express" id="express-apple-pay">Pay</button>
                                <button type="button" class="suresalez-btn-express google" id="express-google-pay">G Pay</button>
                            </div>
                        </div>
                    ` : ''}

                    <form id="suresalez-checkout-form" novalidate>
                        <!-- Step 1: Contact Information -->
                        <div class="suresalez-form-section" id="section-contact" style="display:${currentStep === 'contact' ? 'block' : 'none'};">
                            <h3>Contact Information</h3>
                            <div class="suresalez-input-grid">
                                <div class="suresalez-field-wrap full-width">
                                    <label for="checkout-email">Email Address *</label>
                                    <input type="email" id="checkout-email" required value="${escapeHtml(formData.email)}" placeholder="you@company.com">
                                    <span class="suresalez-field-error" id="err-email"></span>
                                </div>
                                <div class="suresalez-field-wrap full-width">
                                    <label for="checkout-phone">Phone Number (For Tracking Updates) *</label>
                                    <input type="tel" id="checkout-phone" required value="${escapeHtml(formData.phone)}" placeholder="+1 (555) 000-0000">
                                </div>
                            </div>
                            <div style="margin-top:20px; display:flex; justify-content:flex-end;">
                                <button type="button" class="suresalez-btn-checkout" style="width:auto; padding:12px 28px;" id="btn-to-shipping">
                                    Continue to Shipping & Hubs →
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Shipping Address & Hubs -->
                        <div class="suresalez-form-section" id="section-shipping" style="display:${currentStep === 'shipping' ? 'block' : 'none'};">
                            <h3>Shipping Destination</h3>
                            <div class="suresalez-input-grid">
                                <div class="suresalez-field-wrap">
                                    <label for="checkout-fname">First Name *</label>
                                    <input type="text" id="checkout-fname" required value="${escapeHtml(formData.first_name)}">
                                </div>
                                <div class="suresalez-field-wrap">
                                    <label for="checkout-lname">Last Name *</label>
                                    <input type="text" id="checkout-lname" required value="${escapeHtml(formData.last_name)}">
                                </div>
                                
                                ${!config.suppress_company ? `
                                    <div class="suresalez-field-wrap full-width">
                                        <label for="checkout-company">Company Name (Optional)</label>
                                        <input type="text" id="checkout-company" placeholder="Acme Logistics LLC">
                                    </div>
                                ` : ''}

                                <div class="suresalez-field-wrap full-width">
                                    <label for="checkout-address1">Street Address *</label>
                                    <input type="text" id="checkout-address1" required value="${escapeHtml(formData.address_1)}">
                                </div>

                                ${!config.suppress_address_2 ? `
                                    <div class="suresalez-field-wrap full-width">
                                        <label for="checkout-address2">Apartment, Suite, Unit (Optional)</label>
                                        <input type="text" id="checkout-address2">
                                    </div>
                                ` : ''}

                                <div class="suresalez-field-wrap">
                                    <label for="checkout-city">City *</label>
                                    <input type="text" id="checkout-city" required value="${escapeHtml(formData.city)}">
                                </div>

                                <div class="suresalez-field-wrap">
                                    <label for="checkout-postal">Postal / ZIP Code *</label>
                                    <input type="text" id="checkout-postal" required value="${escapeHtml(formData.postal_code)}">
                                    <span class="suresalez-field-error" id="err-postal"></span>
                                </div>
                            </div>

                            <h3 style="margin-top:24px;">Fulfillment Route & Speed</h3>
                            <div class="suresalez-shipping-methods">
                                <label class="suresalez-shipping-method-card selected">
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <input type="radio" name="shipping_method" value="express_hub" checked>
                                        <div>
                                            <strong>Direct From US-EAST-01 (Newark Hub)</strong>
                                            <div style="font-size:12px; color:#64748b;">Next-Day Tracked Dispatch via Priority Courier</div>
                                        </div>
                                    </div>
                                    <strong style="color:#10b981;">FREE</strong>
                                </label>
                            </div>

                            <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
                                <button type="button" style="background:none; border:none; color:#2563eb; cursor:pointer;" id="btn-back-contact">← Back to Info</button>
                                <button type="button" class="suresalez-btn-checkout" style="width:auto; padding:12px 28px;" id="btn-to-payment">
                                    Continue to Payment →
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Payment & Store Credit -->
                        <div class="suresalez-form-section" id="section-payment" style="display:${currentStep === 'payment' ? 'block' : 'none'};">
                            <h3>Payment Method</h3>
                            
                            <!-- Mount slot for Store Credit Wallet application -->
                            <div id="suresalez-wallet-checkout-mount"></div>

                            <div class="suresalez-field-wrap" style="margin-top:16px;">
                                <label style="font-weight:700;">Credit / Debit Card</label>
                                <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:14px; font-size:13px; color:#334155;">
                                    🔒 Encrypted Token Gateway (Mock Card: •••• •••• •••• 4242)
                                </div>
                            </div>

                            <div class="suresalez-field-wrap" style="margin-top:14px;">
                                <label for="checkout-tax-id">VAT / Tax ID / GSTIN (Optional for Business Invoice)</label>
                                <input type="text" id="checkout-tax-id" value="${escapeHtml(formData.tax_id)}" placeholder="e.g. 29ABCDE1234F1Z5">
                            </div>

                            <div style="margin-top:24px; display:flex; justify-content:space-between; align-items:center;">
                                <button type="button" style="background:none; border:none; color:#2563eb; cursor:pointer;" id="btn-back-shipping">← Back to Shipping</button>
                                <button type="button" class="suresalez-btn-checkout" style="width:auto; padding:14px 36px; background:#10b981;" id="btn-place-order">
                                    Place Order Now ($${(cartState.total - walletDiscount).toFixed(2)}) 🔒
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="suresalez-checkout-sidebar" id="suresalez-order-summary-container"></div>
            </div>
        `;

        renderOrderSummary();
        bindCheckoutEvents();
    }

    function renderOrderSummary() {
        const container = document.getElementById('suresalez-order-summary-container');
        if (!container) return;

        const effectiveTotal = Math.max(0, cartState.total - walletDiscount);

        container.innerHTML = `
            <div class="suresalez-order-summary-box">
                <h3 style="margin:0 0 16px 0; font-size:16px; font-weight:700;">Order Summary (${cartState.items.length})</h3>
                
                <div class="suresalez-summary-items">
                    ${cartState.items.map(item => `
                        <div class="suresalez-summary-item">
                            <img src="${item.image}" alt="${escapeHtml(item.title)}">
                            <div class="suresalez-summary-item-info">
                                <div class="suresalez-summary-item-name">${escapeHtml(item.title)}</div>
                                <div style="font-size:11.5px; color:#64748b;">Qty: ${item.quantity} · ${escapeHtml(item.variant || '')}</div>
                            </div>
                            <div class="suresalez-summary-item-price">$${(item.price * item.quantity).toFixed(2)}</div>
                        </div>
                    `).join('')}
                </div>

                <div class="suresalez-summary-row">
                    <span>Subtotal:</span>
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
                    <span style="color:#10b981; font-weight:700;">FREE</span>
                </div>
                ${walletDiscount > 0 ? `
                    <div class="suresalez-summary-row" style="color:#15803d; font-weight:600;">
                        <span>Store Credit Applied:</span>
                        <span>-$${walletDiscount.toFixed(2)}</span>
                    </div>
                ` : ''}
                <div class="suresalez-summary-row total">
                    <span>Total Due:</span>
                    <span style="color:#2563eb;">$${effectiveTotal.toFixed(2)} USD</span>
                </div>
            </div>
        `;
    }

    function bindCheckoutEvents() {
        // Capture in-flight checkout on email blur for abandoned cart recovery
        const emailInput = document.getElementById('checkout-email');
        if (emailInput) {
            emailInput.addEventListener('blur', () => {
                const email = emailInput.value.trim();
                if (email && email.includes('@')) {
                    formData.email = email;
                    captureAbandonedCheckout();
                }
            });
        }

        // Step Navigation
        const toShippingBtn = document.getElementById('btn-to-shipping');
        if (toShippingBtn) {
            toShippingBtn.addEventListener('click', () => {
                const email = document.getElementById('checkout-email').value.trim();
                if (!email || !email.includes('@')) {
                    document.getElementById('err-email').textContent = 'Please enter a valid email address.';
                    return;
                }
                document.getElementById('err-email').textContent = '';
                formData.email = email;
                formData.phone = document.getElementById('checkout-phone').value.trim();
                currentStep = 'shipping';
                renderCheckout();
            });
        }

        const backContactBtn = document.getElementById('btn-back-contact');
        if (backContactBtn) {
            backContactBtn.addEventListener('click', () => {
                currentStep = 'contact';
                renderCheckout();
            });
        }

        const toPaymentBtn = document.getElementById('btn-to-payment');
        if (toPaymentBtn) {
            toPaymentBtn.addEventListener('click', () => {
                const fname = document.getElementById('checkout-fname').value.trim();
                const address1 = document.getElementById('checkout-address1').value.trim();
                const postal = document.getElementById('checkout-postal').value.trim();

                if (!fname || !address1 || !postal) {
                    alert('Please complete all required shipping fields.');
                    return;
                }

                formData.first_name = fname;
                formData.last_name = document.getElementById('checkout-lname').value.trim();
                formData.address_1 = address1;
                formData.city = document.getElementById('checkout-city').value.trim();
                formData.postal_code = postal;

                currentStep = 'payment';
                renderCheckout();
            });
        }

        const backShippingBtn = document.getElementById('btn-back-shipping');
        if (backShippingBtn) {
            backShippingBtn.addEventListener('click', () => {
                currentStep = 'shipping';
                renderCheckout();
            });
        }

        // Place Order Handler
        const placeOrderBtn = document.getElementById('btn-place-order');
        if (placeOrderBtn) {
            placeOrderBtn.addEventListener('click', async () => {
                placeOrderBtn.disabled = true;
                placeOrderBtn.textContent = 'Securing Order & Reserving Stock...';

                setTimeout(() => {
                    // Trigger custom event to display 1-Click Post-Purchase Upsell Modal!
                    document.dispatchEvent(new CustomEvent('suresalez:order_completed', {
                        detail: {
                            order_id: 98452,
                            total: (cartState.total - walletDiscount),
                            customer_email: formData.email,
                            product_ids: cartState.items.map(i => i.id),
                        }
                    }));
                }, 600);
            });
        }
    }

    async function captureAbandonedCheckout() {
        try {
            if (checkoutData && checkoutData.checkoutEndpoints && checkoutData.checkoutEndpoints.capture) {
                await fetch(checkoutData.checkoutEndpoints.capture, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: formData.email,
                        phone: formData.phone,
                        total: cartState.total,
                        items: cartState.items,
                    }),
                });
            }
        } catch (e) {
            // Silently capture
        }
    }

    renderCheckout();
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
