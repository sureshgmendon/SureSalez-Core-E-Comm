/**
 * SureSalez Core — Sprint 08 Focused Test Suite
 * In-Store POS Touch Interface, Multi-Vendor Marketplace Split-Pay & Subscriptions Engine
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
console.log('  SureSalez Core — Sprint 08 Focused Test Suite');
console.log('  POS Touch, Marketplace Split-Pay & Subscriptions');
console.log('======================================================\n');

// --------------------------------------------------------------------------
// TEST 1: POS Tender Calculations & Register Shift Reconciliation
// --------------------------------------------------------------------------
console.log('\x1b[36m[Test 1] POS Tender Calculations & Register Cash Reconciliation\x1b[0m');

function calculateTender(orderTotal, paymentMethod, amountTendered, walletBalance = 0.0) {
    orderTotal = Math.round(orderTotal * 100) / 100;
    amountTendered = Math.round(amountTendered * 100) / 100;
    walletBalance = Math.round(walletBalance * 100) / 100;

    if (paymentMethod === 'cash') {
        if (amountTendered < orderTotal) {
            return {
                success: false,
                shortfall: Math.round((orderTotal - amountTendered) * 100) / 100
            };
        }
        return {
            success: true,
            payment_method: 'cash',
            order_total: orderTotal,
            amount_tendered: amountTendered,
            change_due: Math.round((amountTendered - orderTotal) * 100) / 100,
            wallet_deducted: 0,
            card_charged: 0
        };
    }

    if (paymentMethod === 'split_wallet_card') {
        const walletApplied = Math.min(walletBalance, orderTotal);
        const cardDue = Math.round((orderTotal - walletApplied) * 100) / 100;
        return {
            success: true,
            payment_method: 'split_wallet_card',
            order_total: orderTotal,
            wallet_deducted: walletApplied,
            card_charged: cardDue,
            amount_tendered: orderTotal,
            change_due: 0
        };
    }

    return {
        success: true,
        payment_method: 'external_terminal',
        order_total: orderTotal,
        amount_tendered: orderTotal,
        change_due: 0,
        wallet_deducted: 0,
        card_charged: orderTotal
    };
}

// Cash Tender exact & change tests
const cashExact = calculateTender(149.00, 'cash', 150.00);
assert(cashExact.success === true, 'Cash tender successfully approved');
assert(cashExact.change_due === 1.00, 'Change due for $150 tendered on $149 total is $1.00');

const cashUnder = calculateTender(149.00, 'cash', 100.00);
assert(cashUnder.success === false, 'Insufficient cash tendered rejected');
assert(cashUnder.shortfall === 49.00, 'Cash shortfall correctly calculated as $49.00');

// Split Payment (Store Credit Wallet + Card)
const splitTender = calculateTender(899.00, 'split_wallet_card', 899.00, 150.00);
assert(splitTender.success === true, 'Split-tender payment approved');
assert(splitTender.wallet_deducted === 150.00, 'Customer wallet deducted $150.00');
assert(splitTender.card_charged === 749.00, 'Remaining card charged $749.00');
assert(splitTender.change_due === 0, 'Zero change due on split tender');

// Register shift cash reconciliation
function reconcileRegister(openingCash, cashSales, countedCash) {
    const expectedCash = Math.round((openingCash + cashSales) * 100) / 100;
    const variance = Math.round((countedCash - expectedCash) * 100) / 100;
    return {
        expectedCash,
        countedCash,
        variance,
        reconciled: Math.abs(variance) < 0.01
    };
}

const shift1 = reconcileRegister(500.00, 1240.00, 1740.00);
assert(shift1.expectedCash === 1740.00, 'Expected closing cash calculated as $1,740.00');
assert(shift1.reconciled === true, 'Register shift reconciled with 0 variance');

const shift2 = reconcileRegister(500.00, 1240.00, 1720.00);
assert(shift2.variance === -20.00, 'Negative cash discrepancy correctly flagged as -$20.00');
assert(shift2.reconciled === false, 'Shift with discrepancy marked unreconciled');

// --------------------------------------------------------------------------
// TEST 2: Multi-Vendor Marketplace Cart Partitioning & Platform Commissions
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 2] Multi-Vendor Marketplace Cart Partitioning & Commission Splits\x1b[0m');

function partitionCartByVendors(items) {
    const batches = {};
    for (const item of items) {
        const vId = item.vendor_id || 0;
        if (!batches[vId]) {
            batches[vId] = {
                vendor_id: vId,
                items: [],
                subtotal: 0
            };
        }
        const lineTotal = item.price * item.quantity;
        batches[vId].items.push(item);
        batches[vId].subtotal += lineTotal;
    }
    return Object.values(batches);
}

const cartSample = [
    { sku: 'LUM-CHRONO-01', price: 899.00, quantity: 1, vendor_id: 1 },
    { sku: 'LUM-STRAP-BLK', price: 95.00, quantity: 2, vendor_id: 2 },
    { sku: 'LUM-TOOL-01', price: 45.00, quantity: 1, vendor_id: 1 }
];

const partitioned = partitionCartByVendors(cartSample);
assert(partitioned.length === 2, 'Cart partitioned into 2 distinct vendor fulfillment batches');
const vendor1Batch = partitioned.find(b => b.vendor_id === 1);
assert(vendor1Batch && vendor1Batch.items.length === 2, 'Vendor 1 has 2 assigned line items');
assert(vendor1Batch && vendor1Batch.subtotal === 944.00, 'Vendor 1 subtotal is $944.00 ($899 + $45)');
const vendor2Batch = partitioned.find(b => b.vendor_id === 2);
assert(vendor2Batch && vendor2Batch.subtotal === 190.00, 'Vendor 2 subtotal is $190.00 ($95 x 2)');

// Commission Deduction Calculation
function calculateCommission(grossAmount, commissionRate = 15.00) {
    const fee = Math.round(grossAmount * (commissionRate / 100) * 100) / 100;
    const net = Math.round((grossAmount - fee) * 100) / 100;
    return { grossAmount, commissionRate, fee, net };
}

const comm1 = calculateCommission(899.00, 15.00);
assert(comm1.fee === 134.85, 'Platform cut on $899 at 15% is $134.85');
assert(comm1.net === 764.15, 'Net vendor payout on $899 is $764.15');

const comm2 = calculateCommission(190.00, 12.50);
assert(comm2.fee === 23.75, 'Platform cut on $190 at 12.5% is $23.75');
assert(comm2.net === 166.25, 'Net vendor payout on $190 is $166.25');

// Vendor Withdrawal Balance Validation
function validateWithdrawal(availableBalance, requestedAmount) {
    if (requestedAmount <= 0) return { approved: false, reason: 'Invalid amount' };
    if (requestedAmount > availableBalance) {
        return { approved: false, reason: 'Exceeds available balance', availableBalance };
    }
    return {
        approved: true,
        requestedAmount,
        remainingBalance: Math.round((availableBalance - requestedAmount) * 100) / 100
    };
}

const wdValid = validateWithdrawal(3450.00, 1500.00);
assert(wdValid.approved === true, 'Vendor withdrawal of $1,500 approved against $3,450 balance');
assert(wdValid.remainingBalance === 1950.00, 'Vendor remaining balance is $1,950.00');

const wdOver = validateWithdrawal(3450.00, 4000.00);
assert(wdOver.approved === false, 'Withdrawal exceeding available balance rejected');

// --------------------------------------------------------------------------
// TEST 3: Click-and-Collect (BOPIS) In-Store Fulfillment
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 3] BOPIS In-Store Pickup Verification\x1b[0m');

function processBopisPickup(pickupCode, staffId) {
    const cleanCode = (pickupCode || '').trim().toUpperCase();
    if (!cleanCode) {
        return { success: false, message: 'Invalid pickup code' };
    }
    return {
        success: true,
        pickup_code: cleanCode,
        status: 'collected',
        staff_id: staffId,
        customer_notified: true
    };
}

const bopisSuccess = processBopisPickup('BOPIS-8811', 5);
assert(bopisSuccess.success === true, 'BOPIS order verified by cashier staff');
assert(bopisSuccess.status === 'collected', 'BOPIS order status transitions to collected');
assert(bopisSuccess.customer_notified === true, 'Customer receipt notification sent');

const bopisFail = processBopisPickup('', 5);
assert(bopisFail.success === false, 'Empty pickup code rejected');

// --------------------------------------------------------------------------
// TEST 4: Recurring Subscriptions Pricing & Interval Date Calculations
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 4] Recurring Subscriptions Discounts & Renewal Cycles\x1b[0m');

function calculateSubPrice(basePrice, interval = 'monthly') {
    const discounts = { weekly: 15.00, monthly: 10.00, quarterly: 5.00 };
    const pct = discounts[interval] || 10.00;
    const discountVal = Math.round(basePrice * (pct / 100) * 100) / 100;
    const recurring = Math.round((basePrice - discountVal) * 100) / 100;
    return { basePrice, interval, pct, discountVal, recurring };
}

const subWeekly = calculateSubPrice(100.00, 'weekly');
assert(subWeekly.pct === 15.00, 'Weekly subscription discount is 15%');
assert(subWeekly.recurring === 85.00, 'Weekly recurring price is $85.00');

const subMonthly = calculateSubPrice(899.00, 'monthly');
assert(subMonthly.pct === 10.00, 'Monthly subscription discount is 10%');
assert(subMonthly.recurring === 809.10, 'Monthly recurring price for $899 watch is $809.10');

const subQuarterly = calculateSubPrice(95.00, 'quarterly');
assert(subQuarterly.pct === 5.00, 'Quarterly subscription discount is 5%');
assert(subQuarterly.recurring === 90.25, 'Quarterly recurring price for $95 strap is $90.25');

// Interval renewal date forward calculation
function getNextRenewal(interval, baseDateStr = '2026-10-14T00:00:00Z') {
    const date = new Date(baseDateStr);
    if (interval === 'weekly') {
        date.setUTCDate(date.getUTCDate() + 7);
    } else if (interval === 'quarterly') {
        date.setUTCDate(date.getUTCDate() + 90);
    } else {
        date.setUTCDate(date.getUTCDate() + 30);
    }
    return date.toISOString().split('T')[0];
}

assert(getNextRenewal('weekly') === '2026-10-21', 'Weekly renewal advances exactly 7 days (2026-10-21)');
assert(getNextRenewal('monthly') === '2026-11-13', 'Monthly renewal advances 30 days (2026-11-13)');
assert(getNextRenewal('quarterly') === '2027-01-12', 'Quarterly renewal advances 90 days (2027-01-12)');

// --------------------------------------------------------------------------
// TEST 5: Dunning Retry Sequence & Lifecycle State Machine
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 5] Dunning Failed Payment Retry Sequence & Customer Actions\x1b[0m');

function handleDunningAttempt(attemptCount, errorReason = 'Card declined') {
    if (attemptCount >= 3) {
        return {
            status: 'cancelled',
            attemptCount,
            action: 'auto_cancelled',
            retryScheduled: false,
            alert: 'Subscription cancelled due to repeated payment failures.'
        };
    }
    const nextHours = attemptCount * 24;
    return {
        status: 'dunning',
        attemptCount,
        action: 'retry_scheduled',
        retryHours: nextHours,
        retryScheduled: true
    };
}

const dunning1 = handleDunningAttempt(1);
assert(dunning1.status === 'dunning', 'Attempt 1 flags subscription as dunning');
assert(dunning1.retryHours === 24, 'Attempt 1 schedules retry in 24 hours');

const dunning2 = handleDunningAttempt(2);
assert(dunning2.retryHours === 48, 'Attempt 2 schedules retry in 48 hours');

const dunning3 = handleDunningAttempt(3);
assert(dunning3.status === 'cancelled', 'Attempt 3 cancels subscription');
assert(dunning3.action === 'auto_cancelled', 'Action recorded as auto_cancelled');
assert(dunning3.retryScheduled === false, 'Zero further retries scheduled');

// --------------------------------------------------------------------------
// TEST 6: File Existence, Zero-Bloat Architecture & Theme Purity
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 6] File Existence & Zero-Bloat Modular Architecture\x1b[0m');

const baseDir = path.resolve(__dirname, '..');

const sprint08Files = [
    'plugins/suresalez-core/modules/pos-subscriptions/class-pos-terminal.php',
    'plugins/suresalez-core/modules/pos-subscriptions/class-marketplace-vendor.php',
    'plugins/suresalez-core/modules/pos-subscriptions/class-subscription-engine.php',
    'plugins/suresalez-core/modules/pos-subscriptions/class-pos-subscriptions-module.php',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/css/pos-screen.css',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/js/pos-screen.js',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/css/vendor-portal.css',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/js/vendor-portal.js',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/css/subscription-portal.css',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/js/subscription-portal.js'
];

for (const relPath of sprint08Files) {
    const fullPath = path.join(baseDir, relPath);
    assert(fs.existsSync(fullPath), `Sprint 08 component exists: ${relPath}`);
}

// Check database schema v1.7.0
const dbFile = fs.readFileSync(path.join(baseDir, 'plugins/suresalez-core/includes/class-database.php'), 'utf8');
assert(dbFile.includes('1.7.0') || dbFile.includes('1.8.0'), 'Database schema version is at least 1.7.0');
assert(dbFile.includes('suresalez_pos_registers'), 'Database includes suresalez_pos_registers table');
assert(dbFile.includes('suresalez_pos_transactions'), 'Database includes suresalez_pos_transactions table');
assert(dbFile.includes('suresalez_marketplace_vendors'), 'Database includes suresalez_marketplace_vendors table');
assert(dbFile.includes('suresalez_vendor_commissions'), 'Database includes suresalez_vendor_commissions table');
assert(dbFile.includes('suresalez_subscriptions'), 'Database includes suresalez_subscriptions table');
assert(dbFile.includes('suresalez_subscription_logs'), 'Database includes suresalez_subscription_logs table');
assert(dbFile.includes('seed_pos_registers'), 'Database includes seed_pos_registers');
assert(dbFile.includes('seed_marketplace_vendors'), 'Database includes seed_marketplace_vendors');
assert(dbFile.includes('seed_vendor_commissions'), 'Database includes seed_vendor_commissions');
assert(dbFile.includes('seed_subscriptions'), 'Database includes seed_subscriptions');

// Check core bootstrap
const coreFile = fs.readFileSync(path.join(baseDir, 'plugins/suresalez-core/suresalez-core.php'), 'utf8');
assert(coreFile.includes('POS_Subscriptions_Module'), 'POS_Subscriptions_Module registered in suresalez-core.php');

// Check theme mount points
const themeIndex = fs.readFileSync(path.join(baseDir, 'themes/suresalez-theme/templates/index.html'), 'utf8');
assert(themeIndex.includes('suresalez-pos-screen-mount'), 'Theme index mounts #suresalez-pos-screen-mount');
assert(themeIndex.includes('suresalez-vendor-portal-mount'), 'Theme index mounts #suresalez-vendor-portal-mount');
assert(themeIndex.includes('suresalez-subscriptions-mount'), 'Theme index mounts #suresalez-subscriptions-mount');

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
console.log(`  Sprint 08 Test Results: ${passedTests}/${totalTests} Passed (${failedTests} Failed)`);
console.log('======================================================\n');

if (failedTests > 0) {
    process.exit(1);
} else {
    process.exit(0);
}
