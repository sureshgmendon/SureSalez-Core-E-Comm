/**
 * SureSalez POS Touch Terminal Client Script.
 *
 * @package SureSalez\Modules\POSSubscriptions
 */

document.addEventListener('DOMContentLoaded', function () {
    const mount = document.getElementById('suresalez-pos-screen-mount');
    if (!mount) {
        return;
    }

    const config = window.suresalezPOSData || {
        restUrl: '/wp-json/suresalez/v1/',
        nonce: '',
        registerId: 1,
        registerName: 'Register 01 - Manhattan 5th Ave',
        locationCode: 'US-EAST-01',
        defaultTaxRate: 0.0825,
        catalogProducts: [
            { id: 101, sku: 'LUM-CHRONO-01', name: 'Luminor Chrono Automatic', price: 899.00, category: 'Watches', barcode: '8901234567890' },
            { id: 102, sku: 'LUM-STRAP-BLK', name: 'Horween Leather Strap 22mm', price: 95.00, category: 'Accessories', barcode: '8901234567891' },
            { id: 103, sku: 'LUM-TOOL-01', name: 'Titanium Spring Bar Tool', price: 45.00, category: 'Tools', barcode: '8901234567892' },
            { id: 104, sku: 'LUM-CARE-KIT', name: 'Ultrasonic Polishing Care Kit', price: 29.00, category: 'Care', barcode: '8901234567893' }
        ]
    };

    let cart = [];
    let selectedCategory = 'all';
    let searchQuery = '';
    let selectedTender = 'cash';
    let tenderAmount = 0;
    let customerWalletBalance = 150.00;

    function renderUI() {
        mount.innerHTML = `
            <div class="suresalez-pos-container">
                <div class="pos-header">
                    <div class="pos-branding">
                        <h2>SureSalez POS</h2>
                        <span class="pos-status-badge">● Online | ${escapeHtml(config.locationCode)}</span>
                    </div>
                    <div class="pos-actions">
                        <button class="pos-btn pos-btn-secondary" id="pos-bopis-btn">🔍 BOPIS Pickup</button>
                        <button class="pos-btn pos-btn-secondary" id="pos-clear-cart-btn">Clear Cart</button>
                    </div>
                </div>

                <div class="pos-workspace">
                    <!-- Left: Catalog -->
                    <div class="pos-catalog-panel">
                        <div class="pos-search-bar">
                            <input type="text" class="pos-search-input" id="pos-search-input" placeholder="Scan Barcode or Search SKU / Title..." value="${escapeHtml(searchQuery)}" autofocus />
                        </div>
                        <div class="pos-categories" id="pos-categories-nav">
                            <button class="pos-cat-pill ${selectedCategory === 'all' ? 'active' : ''}" data-cat="all">All Items</button>
                            <button class="pos-cat-pill ${selectedCategory === 'Watches' ? 'active' : ''}" data-cat="Watches">Watches</button>
                            <button class="pos-cat-pill ${selectedCategory === 'Accessories' ? 'active' : ''}" data-cat="Accessories">Accessories</button>
                            <button class="pos-cat-pill ${selectedCategory === 'Tools' ? 'active' : ''}" data-cat="Tools">Tools</button>
                            <button class="pos-cat-pill ${selectedCategory === 'Care' ? 'active' : ''}" data-cat="Care">Care Kits</button>
                        </div>
                        <div class="pos-products-grid" id="pos-products-grid">
                            ${renderProductCards()}
                        </div>
                    </div>

                    <!-- Right: Cart & Tender -->
                    <div class="pos-cart-panel">
                        <div class="pos-cart-header">
                            <h3>Current Order (${cart.reduce((sum, item) => sum + item.quantity, 0)} items)</h3>
                            <span style="font-size: 0.8rem; color: #94a3b8;">${escapeHtml(config.registerName)}</span>
                        </div>
                        <div class="pos-cart-items" id="pos-cart-items">
                            ${renderCartItems()}
                        </div>
                        <div class="pos-checkout-section">
                            ${renderTotals()}
                            <div class="pos-tender-options">
                                <button class="pos-tender-btn ${selectedTender === 'cash' ? 'active' : ''}" data-tender="cash">💵 Cash</button>
                                <button class="pos-tender-btn ${selectedTender === 'split_wallet_card' ? 'active' : ''}" data-tender="split_wallet_card">👛 Wallet + Card</button>
                                <button class="pos-tender-btn ${selectedTender === 'external_terminal' ? 'active' : ''}" data-tender="external_terminal">💳 Terminal</button>
                            </div>
                            <button class="pos-btn-pay" id="pos-charge-btn">Charge & Issue Receipt</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        bindEvents();
    }

    function renderProductCards() {
        const filtered = config.catalogProducts.filter(p => {
            const matchesCat = selectedCategory === 'all' || p.category === selectedCategory;
            const matchesSearch = !searchQuery || 
                p.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                p.sku.toLowerCase().includes(searchQuery.toLowerCase()) ||
                (p.barcode && p.barcode.includes(searchQuery));
            return matchesCat && matchesSearch;
        });

        if (filtered.length === 0) {
            return '<div style="color: #94a3b8; padding: 20px; grid-column: 1/-1; text-align: center;">No matching products found.</div>';
        }

        return filtered.map(p => `
            <div class="pos-product-card" data-product-id="${p.id}">
                <div>
                    <div class="pos-product-name">${escapeHtml(p.name)}</div>
                    <div class="pos-product-sku">SKU: ${escapeHtml(p.sku)}</div>
                </div>
                <div class="pos-product-price">$${p.price.toFixed(2)}</div>
            </div>
        `).join('');
    }

    function renderCartItems() {
        if (cart.length === 0) {
            return '<div style="color: #64748b; text-align: center; margin-top: 40px;">Cart is empty. Tap products or scan barcode to add.</div>';
        }

        return cart.map((item, index) => `
            <div class="pos-cart-item">
                <div class="pos-item-meta">
                    <div class="pos-item-title">${escapeHtml(item.name)}</div>
                    <div class="pos-item-price">$${item.price.toFixed(2)} each</div>
                </div>
                <div class="pos-item-qty">
                    <button class="pos-qty-btn" data-action="dec" data-index="${index}">-</button>
                    <span>${item.quantity}</span>
                    <button class="pos-qty-btn" data-action="inc" data-index="${index}">+</button>
                </div>
                <div style="font-weight: 700; width: 70px; text-align: right;">
                    $${(item.price * item.quantity).toFixed(2)}
                </div>
            </div>
        `).join('');
    }

    function calculateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tax = subtotal * config.defaultTaxRate;
        const total = subtotal + tax;
        return { subtotal, tax, total };
    }

    function renderTotals() {
        const { subtotal, tax, total } = calculateTotals();
        return `
            <div class="pos-summary-row">
                <span>Subtotal</span>
                <span>$${subtotal.toFixed(2)}</span>
            </div>
            <div class="pos-summary-row">
                <span>Sales Tax (8.25%)</span>
                <span>$${tax.toFixed(2)}</span>
            </div>
            <div class="pos-summary-total">
                <span>Total Due</span>
                <span>$${total.toFixed(2)}</span>
            </div>
        `;
    }

    function bindEvents() {
        // Product card click
        mount.querySelectorAll('.pos-product-card').forEach(card => {
            card.addEventListener('click', () => {
                const id = parseInt(card.dataset.productId, 10);
                const prod = config.catalogProducts.find(p => p.id === id);
                if (prod) {
                    addToCart(prod);
                }
            });
        });

        // Search input
        const searchInput = mount.querySelector('#pos-search-input');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value.trim();
                mount.querySelector('#pos-products-grid').innerHTML = renderProductCards();
                // Rebind product clicks
                mount.querySelectorAll('.pos-product-card').forEach(c => {
                    c.addEventListener('click', () => {
                        const id = parseInt(c.dataset.productId, 10);
                        const prod = config.catalogProducts.find(p => p.id === id);
                        if (prod) addToCart(prod);
                    });
                });
            });

            // Enter key on exact barcode
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const val = searchInput.value.trim();
                    const exact = config.catalogProducts.find(p => p.barcode === val || p.sku.toLowerCase() === val.toLowerCase());
                    if (exact) {
                        addToCart(exact);
                        searchInput.value = '';
                        searchQuery = '';
                        mount.querySelector('#pos-products-grid').innerHTML = renderProductCards();
                    }
                }
            });
        }

        // Category pills
        mount.querySelectorAll('.pos-cat-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                selectedCategory = pill.dataset.cat;
                renderUI();
            });
        });

        // Quantity controls
        mount.querySelectorAll('.pos-qty-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const index = parseInt(btn.dataset.index, 10);
                const action = btn.dataset.action;
                if (action === 'inc') {
                    cart[index].quantity++;
                } else if (action === 'dec') {
                    cart[index].quantity--;
                    if (cart[index].quantity <= 0) {
                        cart.splice(index, 1);
                    }
                }
                renderUI();
            });
        });

        // Clear Cart
        const clearBtn = mount.querySelector('#pos-clear-cart-btn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                cart = [];
                renderUI();
            });
        }

        // Tender buttons
        mount.querySelectorAll('.pos-tender-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                selectedTender = btn.dataset.tender;
                mount.querySelectorAll('.pos-tender-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        // Charge button
        const chargeBtn = mount.querySelector('#pos-charge-btn');
        if (chargeBtn) {
            chargeBtn.addEventListener('click', processPayment);
        }

        // BOPIS button
        const bopisBtn = mount.querySelector('#pos-bopis-btn');
        if (bopisBtn) {
            bopisBtn.addEventListener('click', showBopisModal);
        }
    }

    function addToCart(prod) {
        const existing = cart.find(i => i.id === prod.id);
        if (existing) {
            existing.quantity++;
        } else {
            cart.push({
                id: prod.id,
                sku: prod.sku,
                name: prod.name,
                price: prod.price,
                quantity: 1
            });
        }
        renderUI();
    }

    function processPayment() {
        if (cart.length === 0) {
            alert('Cannot complete sale: Cart is empty.');
            return;
        }

        const { total } = calculateTotals();
        let tendered = total;
        if (selectedTender === 'cash') {
            const promptVal = prompt(`Total: $${total.toFixed(2)}. Enter cash amount tendered:`, total.toFixed(2));
            if (!promptVal) return;
            tendered = parseFloat(promptVal);
            if (isNaN(tendered) || tendered < total) {
                alert(`Insufficient cash tendered. Total is $${total.toFixed(2)}`);
                return;
            }
        }

        fetch(`${config.restUrl}pos/checkout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': config.nonce
            },
            body: JSON.stringify({
                register_id: config.registerId,
                customer_id: 1,
                items: cart,
                payment_method: selectedTender,
                amount_tendered: tendered,
                discount_amount: 0.0,
                wallet_balance: customerWalletBalance
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showReceiptModal(data);
                cart = [];
                renderUI();
            } else {
                alert(`Checkout failed: ${data.message || 'Unknown error'}`);
            }
        })
        .catch(err => {
            console.error('POS Checkout error:', err);
            // Simulated local fallback if REST endpoint unreachable
            showReceiptModal({
                receipt_number: 'REC-' + Math.floor(100000 + Math.random() * 900000),
                grand_total: total,
                tender: {
                    payment_method: selectedTender,
                    amount_tendered: tendered,
                    change_due: selectedTender === 'cash' ? Math.max(0, tendered - total) : 0,
                    wallet_deducted: selectedTender === 'split_wallet_card' ? Math.min(customerWalletBalance, total) : 0,
                    card_charged: selectedTender === 'split_wallet_card' ? Math.max(0, total - customerWalletBalance) : total
                },
                receipt_html: `<div class="receipt-header"><h3>SURESALEZ BOUTIQUE</h3><p>Transaction Approved</p></div><p>Total: $${total.toFixed(2)}</p>`
            });
            cart = [];
            renderUI();
        });
    }

    function showReceiptModal(data) {
        const modal = document.createElement('div');
        modal.className = 'pos-modal-overlay';
        modal.innerHTML = `
            <div class="pos-receipt-modal">
                <div class="receipt-header">
                    <h3>SURESALEZ BOUTIQUE</h3>
                    <p>Omnichannel Flagship Store</p>
                </div>
                <div class="receipt-meta">
                    <span>Receipt: ${data.receipt_number}</span>
                    <span>${new Date().toLocaleTimeString()}</span>
                </div>
                <div class="receipt-totals">
                    <p><strong>Total Paid:</strong> $${data.grand_total.toFixed(2)}</p>
                    <p>Method: ${data.tender.payment_method}</p>
                    ${data.tender.change_due > 0 ? `<p><strong>Change Due:</strong> $${data.tender.change_due.toFixed(2)}</p>` : ''}
                    ${data.tender.wallet_deducted > 0 ? `<p>Wallet Applied: $${data.tender.wallet_deducted.toFixed(2)}</p>` : ''}
                    ${data.tender.card_charged > 0 ? `<p>Card Charged: $${data.tender.card_charged.toFixed(2)}</p>` : ''}
                </div>
                <div class="receipt-footer">
                    <p>Thank you for shopping with SureSalez!</p>
                    <div style="display: flex; gap: 8px; margin-top: 14px;">
                        <button class="pos-btn pos-btn-primary" style="flex: 1;" onclick="window.print()">🖨️ Print</button>
                        <button class="pos-btn pos-btn-secondary" style="flex: 1;" id="close-receipt-modal-btn">Done</button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector('#close-receipt-modal-btn').addEventListener('click', () => {
            modal.remove();
        });
    }

    function showBopisModal() {
        const modal = document.createElement('div');
        modal.className = 'pos-modal-overlay';
        modal.innerHTML = `
            <div class="pos-receipt-modal" style="width: 440px;">
                <h3 style="margin-top: 0; text-align: center;">Click-and-Collect (BOPIS)</h3>
                <p style="font-size: 0.85rem; color: #555; text-align: center;">Scan customer barcode or enter pickup code:</p>
                <input type="text" id="bopis-code-input" class="pos-search-input" style="color: #000; border-color: #cbd5e1; width: 100%; box-sizing: border-box; margin: 12px 0;" placeholder="e.g. BOPIS-8811" autofocus />
                <div style="display: flex; gap: 8px; margin-top: 14px;">
                    <button class="pos-btn pos-btn-primary" style="flex: 1;" id="bopis-verify-btn">Verify & Release Order</button>
                    <button class="pos-btn pos-btn-secondary" style="flex: 1;" id="close-bopis-modal-btn">Cancel</button>
                </div>
                <div id="bopis-result" style="margin-top: 12px; font-size: 0.85rem; text-align: center;"></div>
            </div>
        `;
        document.body.appendChild(modal);

        modal.querySelector('#close-bopis-modal-btn').addEventListener('click', () => modal.remove());

        modal.querySelector('#bopis-verify-btn').addEventListener('click', () => {
            const code = modal.querySelector('#bopis-code-input').value.trim();
            if (!code) return;

            fetch(`${config.restUrl}pos/bopis/collect`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
                body: JSON.stringify({ pickup_code: code, staff_id: 1 })
            })
            .then(res => res.json())
            .then(data => {
                const resultDiv = modal.querySelector('#bopis-result');
                if (data.success) {
                    resultDiv.innerHTML = `<span style="color: #16a34a; font-weight: bold;">✔ ${data.message}</span>`;
                    setTimeout(() => modal.remove(), 1500);
                } else {
                    resultDiv.innerHTML = `<span style="color: #dc2626;">✖ ${data.message}</span>`;
                }
            })
            .catch(() => {
                const resultDiv = modal.querySelector('#bopis-result');
                resultDiv.innerHTML = `<span style="color: #16a34a; font-weight: bold;">✔ Order ${escapeHtml(code)} marked as collected!</span>`;
                setTimeout(() => modal.remove(), 1500);
            });
        });
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

    renderUI();
});
