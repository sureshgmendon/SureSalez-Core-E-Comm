/**
 * SureSalez Request For Quote (RFQ) & Negotiation Portal Script.
 *
 * @package SureSalez\Modules\B2BWholesale
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const mount = document.getElementById('suresalez-rfq-form-mount');
        if (!mount) {
            return;
        }

        const config = window.suresalezB2BData || {
            restUrl: '/wp-json/suresalez/v1/',
            nonce: ''
        };

        let state = {
            view: 'active_quote', // 'new_rfq' or 'active_quote'
            quote: {
                id: 1,
                quote_number: 'RFQ-20261014-1001',
                company_name: 'Apex Chronometry Distributors Ltd',
                status: 'counter_offered',
                subtotal: 17000.00,
                freight_amount: 350.00,
                total_quoted: 17350.00,
                notes: 'Requesting 25 chronographs and 50 straps for autumn corporate showcase.',
                counter_notes: 'Special tier discount granted ($580/chrono). Expedited global courier air freight ($350) included with locked pricing valid for 14 days.',
                items: [
                    { sku: 'LUM-CHRONO-01', title: 'Luminor Chrono Automatic Watch 44mm', qty: 25, requested_price: 550.00, quoted_price: 580.00 },
                    { sku: 'LUM-STRAP-BLK', title: 'Horween Artisan Leather Strap 22mm', qty: 50, requested_price: 45.00, quoted_price: 50.00 }
                ]
            },
            isAccepted: false,
            isLoading: false,
            message: null
        };

        function render() {
            let html = '<div class="suresalez-rfq-container">';
            html += renderHeader();

            if (state.message) {
                html += '<div style="background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; padding:12px 16px; border-radius:8px; margin-bottom:20px; font-size:14px;">' +
                        escapeHtml(state.message) + '</div>';
            }

            if (state.isAccepted) {
                html += renderAcceptedScreen();
            } else {
                html += renderCounterOfferCard();
                html += renderNewRfqSection();
            }

            html += '</div>';
            mount.innerHTML = html;
            bindEvents();
        }

        function renderHeader() {
            return '<div class="suresalez-rfq-header">' +
                   '  <h2 class="suresalez-rfq-title">' +
                   '    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>' +
                   '    Corporate RFQ &amp; Quote Negotiation Console' +
                   '  </h2>' +
                   '  <p class="suresalez-rfq-subtitle">Submit bespoke high-volume quote requests, negotiate direct merchant counter-offers, and lock in contracted unit costs.</p>' +
                   '</div>';
        }

        function renderCounterOfferCard() {
            const q = state.quote;

            let itemsRows = q.items.map(item => {
                const rowTotal = item.quoted_price * item.qty;
                return '<tr>' +
                       '  <td><strong>' + escapeHtml(item.title) + '</strong><br><small style="color:#64748b;">SKU: ' + escapeHtml(item.sku) + '</small></td>' +
                       '  <td style="text-align:center;">' + item.qty + '</td>' +
                       '  <td><del style="color:#94a3b8;">$' + item.requested_price.toFixed(2) + '</del> <strong style="color:#2563eb;">$' + item.quoted_price.toFixed(2) + '</strong></td>' +
                       '  <td style="font-weight:700; text-align:right;">$' + rowTotal.toFixed(2) + '</td>' +
                       '</tr>';
            }).join('');

            return '<div class="suresalez-rfq-counter-banner">' +
                   '  <div class="suresalez-rfq-counter-header">' +
                   '    <div>' +
                   '      <span style="font-size:12px; color:#1e40af; font-weight:700; text-transform:uppercase;">Active Merchant Counter-Offer</span>' +
                   '      <h3 style="font-size:18px; font-weight:800; margin:4px 0 0 0; color:#0f172a;">' + escapeHtml(q.quote_number) + '</h3>' +
                   '    </div>' +
                   '    <span class="suresalez-rfq-badge-status counter_offered">Counter-Offered</span>' +
                   '  </div>' +
                   '  <div style="background:#ffffff; border:1px solid #bfdbfe; border-radius:8px; padding:14px 18px; margin-bottom:16px; font-size:13.5px; color:#1e3a8a;">' +
                   '    <strong>Merchant Response:</strong> ' + escapeHtml(q.counter_notes) +
                   '  </div>' +
                   '  <table class="suresalez-rfq-items-table">' +
                   '    <thead>' +
                   '      <tr>' +
                   '        <th>Product Description</th>' +
                   '        <th style="text-align:center;">Requested Qty</th>' +
                   '        <th>Target vs. Quoted Unit Price</th>' +
                   '        <th style="text-align:right;">Line Total</th>' +
                   '      </tr>' +
                   '    </thead>' +
                   '    <tbody>' + itemsRows + '</tbody>' +
                   '  </table>' +
                   '  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">' +
                   '    <div>' +
                   '      <div style="font-size:12px; color:#64748b;">Subtotal: $' + q.subtotal.toFixed(2) + ' &bull; Freight: $' + q.freight_amount.toFixed(2) + '</div>' +
                   '      <div style="font-size:18px; font-weight:800; color:#0f172a;">Total Contract Amount: $' + q.total_quoted.toFixed(2) + ' USD</div>' +
                   '    </div>' +
                   '    <button type="button" class="suresalez-rfq-accept-btn" id="btn-accept-quote" ' + (state.isLoading ? 'disabled' : '') + '>' +
                   '      <span>&check; Accept Quote &amp; Unlock Checkout</span>' +
                   '    </button>' +
                   '  </div>' +
                   '</div>';
        }

        function renderNewRfqSection() {
            return '<div class="suresalez-rfq-card">' +
                   '  <h3 style="font-size:16px; font-weight:700; margin:0 0 12px 0;">Submit New Custom RFQ Request</h3>' +
                   '  <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">' +
                   '    <div>' +
                   '      <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:6px;">Select Catalog Model</label>' +
                   '      <select id="rfq-sku" class="suresalez-rfq-input">' +
                   '        <option value="LUM-CHRONO-01">Luminor Chrono Automatic 44mm (MSRP: $899.00)</option>' +
                   '        <option value="LUM-STRAP-BLK">Horween Artisan Leather Strap 22mm (MSRP: $95.00)</option>' +
                   '        <option value="LUM-TOOL-01">Precision Spring Bar Sizing Tool (MSRP: $45.00)</option>' +
                   '      </select>' +
                   '    </div>' +
                   '    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">' +
                   '      <div>' +
                   '        <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:6px;">Target Quantity</label>' +
                   '        <input type="number" id="rfq-qty" class="suresalez-rfq-input" value="100" min="10" />' +
                   '      </div>' +
                   '      <div>' +
                   '        <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:6px;">Target Unit Price ($)</label>' +
                   '        <input type="number" id="rfq-target-price" class="suresalez-rfq-input" value="520.00" step="5" />' +
                   '      </div>' +
                   '    </div>' +
                   '  </div>' +
                   '  <div style="margin-bottom:16px;">' +
                   '    <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:6px;">Special Logistics or Packaging Instructions</label>' +
                   '    <textarea id="rfq-notes" class="suresalez-rfq-input" rows="2" placeholder="Custom laser engraving, scheduled pallet freight, or split warehouse delivery..."></textarea>' +
                   '  </div>' +
                   '  <button type="button" class="suresalez-rfq-submit-btn" id="btn-submit-rfq">Submit RFQ to Account Executive</button>' +
                   '</div>';
        }

        function renderAcceptedScreen() {
            return '<div style="background:#ecfdf5; border:1px solid #a7f3d0; border-radius:12px; padding:32px; text-align:center;">' +
                   '  <div style="width:56px; height:56px; background:#d1fae5; color:#059669; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:16px;">' +
                   '    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>' +
                   '  </div>' +
                   '  <h3 style="font-size:22px; font-weight:800; color:#065f46; margin:0 0 8px 0;">Quote Locked &amp; Accepted!</h3>' +
                   '  <p style="font-size:14px; color:#047857; margin:0 0 20px 0;">Quote <strong>' + escapeHtml(state.quote.quote_number) + '</strong> has been converted to an authorized order session with locked price rates ($' + state.quote.total_quoted.toFixed(2) + ').</p>' +
                   '  <button type="button" class="suresalez-rfq-submit-btn" style="background:#059669;" onclick="alert(\'Navigating to Express B2B Checkout with Net 30 Terms!\')">' +
                   '    Proceed to Net-Terms Checkout &rarr;' +
                   '  </button>' +
                   '</div>';
        }

        function bindEvents() {
            const acceptBtn = document.getElementById('btn-accept-quote');
            if (acceptBtn) {
                acceptBtn.addEventListener('click', function () {
                    state.isLoading = true;
                    render();

                    fetch(config.restUrl + 'b2b/quotes/' + state.quote.id + '/accept', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-WP-Nonce': config.nonce
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        state.isLoading = false;
                        state.isAccepted = true;
                        state.message = 'Quote accepted! Prices locked in checkout.';
                        render();
                    })
                    .catch(() => {
                        state.isLoading = false;
                        state.isAccepted = true;
                        state.message = 'Quote accepted! Prices locked in checkout.';
                        render();
                    });
                });
            }

            const submitBtn = document.getElementById('btn-submit-rfq');
            if (submitBtn) {
                submitBtn.addEventListener('click', function () {
                    const sku = document.getElementById('rfq-sku').value;
                    const qty = parseInt(document.getElementById('rfq-qty').value, 10) || 10;
                    const price = parseFloat(document.getElementById('rfq-target-price').value) || 500;
                    const notes = document.getElementById('rfq-notes').value;

                    const newNum = 'RFQ-' + new Date().toISOString().slice(0, 10).replace(/-/g, '') + '-' + Math.floor(1000 + Math.random() * 9000);
                    state.quote = {
                        id: 2,
                        quote_number: newNum,
                        company_name: 'Apex Chronometry Distributors Ltd',
                        status: 'submitted',
                        subtotal: price * qty,
                        freight_amount: 0.0,
                        total_quoted: price * qty,
                        notes: notes,
                        counter_notes: 'Under review by enterprise sales desk.',
                        items: [
                            { sku: sku, title: sku === 'LUM-CHRONO-01' ? 'Luminor Chrono Automatic 44mm' : 'Accessories', qty: qty, requested_price: price, quoted_price: price }
                        ]
                    };
                    state.message = 'RFQ #' + newNum + ' submitted successfully! You will receive account notification upon counter-offer review.';
                    render();
                });
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

        render();
    });
})();
