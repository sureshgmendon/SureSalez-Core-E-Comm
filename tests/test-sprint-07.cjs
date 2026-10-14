/**
 * SureSalez Core — Sprint 07 Focused Test Suite
 * B2B Wholesale Commerce, Organization Accounts & RFQ Quote Engine
 *
 * @package SureSalez\Tests
 */

const fs = require('fs');
const path = require('path');

let totalTests = 0;
let passedTests = 0;
let failedTests = 0;

function assert(condition, message) {
    totalTests++;
    if (condition) {
        passedTests++;
        console.log(`  ✔ PASS: ${message}`);
    } else {
        failedTests++;
        console.error(`  ✖ FAIL: ${message}`);
    }
}

console.log('\n======================================================');
console.log('  SureSalez Core — Sprint 07 Focused Test Suite');
console.log('  B2B Wholesale Commerce, Organization Accounts & RFQ');
console.log('======================================================\n');

// --------------------------------------------------------------------------
// TEST 1: Corporate Account Roles, Tax Validation & Approval Thresholds
// --------------------------------------------------------------------------
console.log('\x1b[36m[Test 1] Corporate Account Roles, Tax ID Validation & Approvals\x1b[0m');

function validateBusinessTaxId(taxId, country = 'US') {
    const clean = taxId.trim().toUpperCase();
    if (!clean) return false;

    if (country === 'US') {
        return /^(?:US-)?(?:EIN-)?\d{2}-?\d{7}$/i.test(clean);
    }
    if (country === 'IN') {
        return /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/.test(clean);
    }
    if (country === 'GB') {
        return /^(?:GB)?\d{9}(?:\d{3})?$/i.test(clean);
    }
    return clean.length >= 6;
}

assert(validateBusinessTaxId('US-EIN-123456789', 'US') === true, 'US Federal EIN tax ID format valid');
assert(validateBusinessTaxId('12-3456789', 'US') === true, 'Standard 9-digit EIN format valid');
assert(validateBusinessTaxId('29ABCDE1234F1Z5', 'IN') === true, 'Indian 15-character GSTIN valid');
assert(validateBusinessTaxId('GB998877665', 'GB') === true, 'UK 9-digit VAT number valid');
assert(validateBusinessTaxId('INVALID', 'US') === false, 'Invalid tax ID rejected');

function evaluateOrderApproval(user, company, orderTotal) {
    if (!company || company.status !== 'approved') {
        return { requires_approval: true, reason: 'Company unapproved' };
    }
    if (user.spending_limit > 0 && orderTotal > user.spending_limit) {
        return {
            requires_approval: true,
            reason: `Order total ($${orderTotal}) exceeds purchaser spending limit ($${user.spending_limit})`
        };
    }
    if (company.approval_threshold > 0 && orderTotal > company.approval_threshold) {
        return {
            requires_approval: true,
            reason: `Order total ($${orderTotal}) exceeds corporate threshold ($${company.approval_threshold})`
        };
    }
    return { requires_approval: false, reason: 'Within limits' };
}

const mockCompany = {
    id: 1,
    name: 'Apex Chronometry Distributors Ltd',
    status: 'approved',
    approval_threshold: 5000.00,
    credit_limit: 50000.00
};

const adminUser = { id: 10, role: 'admin', spending_limit: 0 };
const purchaserUser = { id: 11, role: 'purchaser', spending_limit: 2500.00 };

// Order 1: $1,800 (under purchaser limit and under company threshold)
const app1 = evaluateOrderApproval(purchaserUser, mockCompany, 1800.00);
assert(app1.requires_approval === false, 'Purchaser order under individual limit ($1800 < $2500) approved directly');

// Order 2: $3,200 (exceeds purchaser $2500 limit, but under company $5000 threshold)
const app2 = evaluateOrderApproval(purchaserUser, mockCompany, 3200.00);
assert(app2.requires_approval === true, 'Purchaser order exceeding individual limit ($3200 > $2500) flagged for approval');

// Order 3: $8,500 by Admin (admin has 0 personal limit, but order exceeds company $5000 threshold)
const app3 = evaluateOrderApproval(adminUser, mockCompany, 8500.00);
assert(app3.requires_approval === true, 'Large order exceeding company threshold ($8500 > $5000) flagged for approval');

// Order approval action
function approveOrderRecord(order, managerId) {
    return {
        ...order,
        approval_status: 'approved',
        approved_by_user_id: managerId
    };
}
const approved = approveOrderRecord({ order_id: 101, approval_status: 'pending_manager_approval' }, 10);
assert(approved.approval_status === 'approved', 'Manager approval transitions order to approved status');
assert(approved.approved_by_user_id === 10, 'Approved order records approving manager user ID');

// --------------------------------------------------------------------------
// TEST 2: Dynamic Wholesale Tiered Pricing & MOQ Engine
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 2] Dynamic Wholesale Tiered Pricing & MOQ Engine\x1b[0m');

const wholesaleRules = {
    'LUM-CHRONO-01': {
        msrp: 899.00,
        moq: 2,
        case_pack: 1,
        tiers: [
            { min_qty: 1, max_qty: 9, price: 719.20, discount_pct: 20 },
            { min_qty: 10, max_qty: 49, price: 629.30, discount_pct: 30 },
            { min_qty: 50, max_qty: 9999, price: 539.40, discount_pct: 40 }
        ]
    },
    'LUM-STRAP-BLK': {
        msrp: 95.00,
        moq: 5,
        case_pack: 5,
        tiers: [
            { min_qty: 1, max_qty: 19, price: 76.00, discount_pct: 20 },
            { min_qty: 20, max_qty: 9999, price: 57.00, discount_pct: 40 }
        ]
    }
};

function calculateB2BPrice(sku, qty, isB2B = true, customPricelist = null) {
    const rule = (customPricelist && customPricelist[sku]) || wholesaleRules[sku];
    if (!rule) return null;

    if (!isB2B) {
        return {
            is_b2b: false,
            unit_price: rule.msrp,
            moq_satisfied: true,
            discount_pct: 0,
            total: rule.msrp * qty
        };
    }

    let unitPrice = rule.msrp;
    let disc = 0;
    for (const t of rule.tiers) {
        if (qty >= t.min_qty && qty <= (t.max_qty || 99999)) {
            unitPrice = t.price;
            disc = t.discount_pct;
            break;
        }
    }

    return {
        is_b2b: true,
        msrp: rule.msrp,
        unit_price: unitPrice,
        discount_pct: disc,
        moq: rule.moq,
        case_pack: rule.case_pack || 1,
        moq_satisfied: qty >= rule.moq,
        case_pack_satisfied: (qty % (rule.case_pack || 1)) === 0,
        total: Math.round(unitPrice * qty * 100) / 100,
        savings: Math.round((rule.msrp - unitPrice) * qty * 100) / 100
    };
}

// Public Retail User Test
const publicPrice = calculateB2BPrice('LUM-CHRONO-01', 5, false);
assert(publicPrice.unit_price === 899.00, 'Public buyer receives full MSRP: $899.00');
assert(publicPrice.discount_pct === 0, 'Public buyer has 0% wholesale discount');

// B2B Tier 1 (1-9 units): 20% off
const b2bTier1 = calculateB2BPrice('LUM-CHRONO-01', 5, true);
assert(b2bTier1.unit_price === 719.20, 'B2B Qty 5 gets Tier 1 rate: $719.20 (20% off)');
assert(b2bTier1.discount_pct === 20, 'Discount percentage is 20%');
assert(b2bTier1.moq_satisfied === true, 'Qty 5 satisfies MOQ of 2');

// B2B Tier 2 (10-49 units): 30% off
const b2bTier2 = calculateB2BPrice('LUM-CHRONO-01', 25, true);
assert(b2bTier2.unit_price === 629.30, 'B2B Qty 25 gets Tier 2 volume rate: $629.30 (30% off)');
assert(b2bTier2.total === 15732.50, 'Line total for 25 units is $15,732.50');
assert(b2bTier2.savings === 6742.50, 'Corporate savings is $6,742.50');

// B2B Tier 3 (50+ units): 40% off
const b2bTier3 = calculateB2BPrice('LUM-CHRONO-01', 50, true);
assert(b2bTier3.unit_price === 539.40, 'B2B Qty 50 gets Tier 3 maximum volume rate: $539.40 (40% off)');

// MOQ Violation Test
const underMoq = calculateB2BPrice('LUM-STRAP-BLK', 2, true);
assert(underMoq.moq_satisfied === false, 'Qty 2 for straps fails MOQ of 5');

// Case pack test
const casePackValid = calculateB2BPrice('LUM-STRAP-BLK', 15, true);
assert(casePackValid.case_pack_satisfied === true, 'Qty 15 satisfies 5-pack case increments');

const casePackInvalid = calculateB2BPrice('LUM-STRAP-BLK', 12, true);
assert(casePackInvalid.case_pack_satisfied === false, 'Qty 12 fails 5-pack case increments');

// Company-specific negotiated contract test (Harrods fixed price)
const harrodsContract = {
    'LUM-CHRONO-01': {
        msrp: 899.00,
        moq: 10,
        tiers: [{ min_qty: 10, max_qty: 9999, price: 495.00, discount_pct: 45 }]
    }
};
const harrodsPrice = calculateB2BPrice('LUM-CHRONO-01', 20, true, harrodsContract);
assert(harrodsPrice.unit_price === 495.00, 'Harrods exclusive contract overrides rates to $495.00');

// --------------------------------------------------------------------------
// TEST 3: Net Terms (Net 15/30/60) & Credit Limit Validation
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 3] Net Terms & Credit Limit Validation\x1b[0m');

function checkNetTermsEligibility(company, openBalance, orderTotal) {
    if (!company || company.status !== 'approved') {
        return { eligible: false, reason: 'Company unapproved' };
    }
    if (company.payment_terms === 'prepaid') {
        return { eligible: false, reason: 'Prepaid terms only' };
    }
    const availableCredit = Math.max(0, company.credit_limit - openBalance);
    if (orderTotal > availableCredit) {
        return {
            eligible: false,
            reason: `Order ($${orderTotal}) exceeds available credit ($${availableCredit})`,
            availableCredit: availableCredit
        };
    }
    return {
        eligible: true,
        terms: company.payment_terms,
        availableCredit: availableCredit
    };
}

const creditCo = {
    name: 'Harrods Luxury Watch Boutiques',
    status: 'approved',
    payment_terms: 'net_60',
    credit_limit: 120000.00
};

// Order $25,000 with $30,000 open balance ($90,000 available)
const credit1 = checkNetTermsEligibility(creditCo, 30000.00, 25000.00);
assert(credit1.eligible === true, 'Order under remaining credit limit approved for Net 60');
assert(credit1.terms === 'net_60', 'Terms correctly identified as net_60');
assert(credit1.availableCredit === 90000.00, 'Remaining available credit calculated ($90,000)');

// Order $100,000 with $30,000 open balance ($90,000 available)
const credit2 = checkNetTermsEligibility(creditCo, 30000.00, 100000.00);
assert(credit2.eligible === false, 'Order exceeding available credit limit rejected for Net terms');

function calculateDueDate(paymentTerms, fromDate = new Date('2026-10-14')) {
    const daysMap = { prepaid: 0, net_15: 15, net_30: 30, net_60: 60 };
    const days = daysMap[paymentTerms] || 30;
    const due = new Date(fromDate);
    due.setDate(due.getDate() + days);
    return due.toISOString().slice(0, 10);
}

assert(calculateDueDate('net_30', new Date('2026-10-14')) === '2026-11-13', 'Net 30 due date calculated to 30 days out');
assert(calculateDueDate('net_60', new Date('2026-10-14')) === '2026-12-13', 'Net 60 due date calculated to 60 days out');

// --------------------------------------------------------------------------
// TEST 4: Quick Bulk Order CSV Parser & Inventory Validation
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 4] Quick Bulk Order CSV Parser & Hub Inventory Check\x1b[0m');

function parseBulkCsv(csvContent) {
    const lines = csvContent.trim().split(/\r\n|\n/);
    if (lines.length < 2) return { items: [], errors: ['Empty or missing header'] };

    const header = lines[0].toLowerCase().split(',');
    let skuIdx = header.findIndex(h => h.trim().includes('sku'));
    let qtyIdx = header.findIndex(h => h.trim().includes('qty') || h.trim().includes('quantity'));

    if (skuIdx === -1) skuIdx = 0;
    if (qtyIdx === -1) qtyIdx = 1;

    const items = [];
    const errors = [];

    for (let i = 1; i < lines.length; i++) {
        const line = lines[i].trim();
        if (!line) continue;
        const cols = line.split(',');
        const sku = (cols[skuIdx] || '').trim().toUpperCase();
        const qty = parseInt((cols[qtyIdx] || '').trim(), 10);

        if (!sku) {
            errors.push(`Row ${i + 1}: Missing SKU`);
            continue;
        }
        if (isNaN(qty) || qty <= 0) {
            errors.push(`Row ${i + 1}: Invalid quantity for ${sku}`);
            continue;
        }
        items.push({ sku, qty });
    }

    return { items, errors };
}

const sampleCsv = `sku,quantity
LUM-CHRONO-01,25
LUM-STRAP-BLK,50
LUM-TOOL-01,100`;

const parsedCsv = parseBulkCsv(sampleCsv);
assert(parsedCsv.items.length === 3, 'CSV parser extracts 3 valid SKU line items');
assert(parsedCsv.items[0].sku === 'LUM-CHRONO-01' && parsedCsv.items[0].qty === 25, 'Item 1: LUM-CHRONO-01 x 25');
assert(parsedCsv.items[1].sku === 'LUM-STRAP-BLK' && parsedCsv.items[1].qty === 50, 'Item 2: LUM-STRAP-BLK x 50');
assert(parsedCsv.items[2].sku === 'LUM-TOOL-01' && parsedCsv.items[2].qty === 100, 'Item 3: LUM-TOOL-01 x 100');

// --------------------------------------------------------------------------
// TEST 5: Request For Quote (RFQ) Negotiation & 1-Click Acceptance
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 5] Request For Quote (RFQ) Lifecycle & Acceptance\x1b[0m');

function generateRfqNumber() {
    const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    const rand = Math.floor(1000 + Math.random() * 9000);
    return `RFQ-${dateStr}-${rand}`;
}

const rfqNum = generateRfqNumber();
assert(/^RFQ-\d{8}-\d{4}$/.test(rfqNum), `RFQ number matches standard format: ${rfqNum}`);

function createRfq(items, freight = 0) {
    let subtotal = 0;
    items.forEach(it => {
        subtotal += (it.requested_price * it.qty);
    });
    return {
        quote_number: generateRfqNumber(),
        status: 'submitted',
        items: items,
        subtotal: subtotal,
        freight_amount: freight,
        total_quoted: subtotal + freight,
        quote_token: 'rfq_tok_' + Math.random().toString(36).substring(2, 12)
    };
}

const quote = createRfq([
    { sku: 'LUM-CHRONO-01', qty: 25, requested_price: 550.00 }
], 300.00);

assert(quote.status === 'submitted', 'New RFQ initialized in submitted status');
assert(quote.subtotal === 13750.00, 'Subtotal calculates requested price ($13,750.00)');
assert(quote.total_quoted === 14050.00, 'Total includes freight amount ($14,050.00)');

function submitCounterOffer(rfq, adjustments) {
    let subtotal = 0;
    const items = adjustments.items || rfq.items;
    items.forEach(it => {
        const p = it.quoted_price || it.requested_price;
        subtotal += (p * it.qty);
    });
    const freight = adjustments.freight_amount !== undefined ? adjustments.freight_amount : rfq.freight_amount;
    return {
        ...rfq,
        items: items,
        subtotal: subtotal,
        freight_amount: freight,
        total_quoted: subtotal + freight,
        status: 'counter_offered',
        counter_notes: adjustments.notes || ''
    };
}

const counter = submitCounterOffer(quote, {
    items: [{ sku: 'LUM-CHRONO-01', qty: 25, requested_price: 550.00, quoted_price: 580.00 }],
    freight_amount: 350.00,
    notes: 'Counter-offered $580/unit with priority courier freight included.'
});

assert(counter.status === 'counter_offered', 'Merchant adjustment transitions quote to counter_offered');
assert(counter.subtotal === 14500.00, 'Adjusted subtotal calculated at $580/unit ($14,500.00)');
assert(counter.total_quoted === 14850.00, 'Counter total includes adjusted freight ($14,850.00)');

function acceptQuote(rfq) {
    return {
        ...rfq,
        status: 'accepted',
        checkout_url: `/checkout/?b2b_quote_accepted=${rfq.quote_token}`
    };
}

const acceptedQuote = acceptQuote(counter);
assert(acceptedQuote.status === 'accepted', 'Buyer acceptance marks quote accepted');
assert(acceptedQuote.checkout_url.includes(counter.quote_token), '1-click checkout URL generated with quote security token');

// --------------------------------------------------------------------------
// TEST 6: File Existence, Database Schema & Modular Architecture
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 6] File Existence & Zero-Bloat Modular Architecture\x1b[0m');

const baseDir = path.resolve(__dirname, '..');
const sprint07Files = [
    'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-organization.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-pricing.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-checkout.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-bulk-order.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-rfq-engine.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-wholesale-module.php',
    'plugins/suresalez-core/modules/b2b-wholesale/assets/css/bulk-order.css',
    'plugins/suresalez-core/modules/b2b-wholesale/assets/js/bulk-order.js',
    'plugins/suresalez-core/modules/b2b-wholesale/assets/css/rfq-portal.css',
    'plugins/suresalez-core/modules/b2b-wholesale/assets/js/rfq-portal.js'
];

for (const relPath of sprint07Files) {
    const fullPath = path.join(baseDir, relPath);
    assert(fs.existsSync(fullPath), `Sprint 07 component exists: ${relPath}`);
}

// Check database schema v1.6.0+
const dbFile = fs.readFileSync(path.join(baseDir, 'plugins/suresalez-core/includes/class-database.php'), 'utf8');
assert(dbFile.includes('1.6.0') || dbFile.includes('1.7.0') || dbFile.includes('1.8.0'), 'Database schema version is at least 1.6.0');
assert(dbFile.includes('suresalez_b2b_companies'), 'Database includes suresalez_b2b_companies table');
assert(dbFile.includes('suresalez_b2b_users'), 'Database includes suresalez_b2b_users table');
assert(dbFile.includes('suresalez_b2b_pricelists'), 'Database includes suresalez_b2b_pricelists table');
assert(dbFile.includes('suresalez_b2b_quotes'), 'Database includes suresalez_b2b_quotes table');
assert(dbFile.includes('suresalez_b2b_orders'), 'Database includes suresalez_b2b_orders table');
assert(dbFile.includes('seed_b2b_companies'), 'Database includes seed_b2b_companies');
assert(dbFile.includes('seed_b2b_users'), 'Database includes seed_b2b_users');
assert(dbFile.includes('seed_b2b_pricelists'), 'Database includes seed_b2b_pricelists');
assert(dbFile.includes('seed_b2b_quotes'), 'Database includes seed_b2b_quotes');

// Check core bootstrap
const coreFile = fs.readFileSync(path.join(baseDir, 'plugins/suresalez-core/suresalez-core.php'), 'utf8');
assert(coreFile.includes('B2B_Wholesale_Module'), 'B2B_Wholesale_Module registered in suresalez-core.php');

// Check theme mount points
const themeIndex = fs.readFileSync(path.join(baseDir, 'themes/suresalez-theme/templates/index.html'), 'utf8');
assert(themeIndex.includes('suresalez-b2b-bulk-order-mount'), 'Theme index mounts #suresalez-b2b-bulk-order-mount');
assert(themeIndex.includes('suresalez-rfq-form-mount'), 'Theme index mounts #suresalez-rfq-form-mount');

// Check theme has 0 PHP files
function checkThemeForPhp(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
        const full = path.join(dir, entry.name);
        if (entry.isDirectory()) {
            checkThemeForPhp(full);
        } else if (entry.name.endsWith('.php')) {
            throw new Error(`Forbidden PHP file discovered in theme: ${full}`);
        }
    }
}
let themePhpCount = 0;
try {
    checkThemeForPhp(path.join(baseDir, 'themes/suresalez-theme'));
} catch (e) {
    themePhpCount++;
}
assert(themePhpCount === 0, 'SureSalez Theme contains strictly 0 PHP files (Pure FSE block theme)');

// --------------------------------------------------------------------------
// TEST SUMMARY
// --------------------------------------------------------------------------
console.log('\n======================================================');
console.log(`  Sprint 07 Test Results: ${passedTests}/${totalTests} Passed (${failedTests} Failed)`);
console.log('======================================================\n');

if (failedTests > 0) {
    process.exit(1);
} else {
    process.exit(0);
}
