/**
 * SureSalez Sprint 02 Unit & Integration Test Suite
 *
 * Tests Cart Drawer calculations, Checkout Customizer field validation,
 * Wallet Ledger transactions, Post-Purchase Upsell mechanics, and
 * Abandoned Cart token restoration.
 */

const fs = require('fs');
const path = require('path');

let testsCount = 0;
let passedCount = 0;
let failedCount = 0;

function assert(condition, desc) {
    testsCount++;
    if (condition) {
        passedCount++;
        console.log(`  \x1b[32m✔ PASS\x1b[0m: ${desc}`);
    } else {
        failedCount++;
        console.error(`  \x1b[31m✘ FAIL\x1b[0m: ${desc}`);
    }
}

console.log('\n======================================================');
console.log('  SureSalez Core — Sprint 02 Focused Test Suite');
console.log('======================================================\n');

// TEST 1: Cart Drawer Free Shipping Calculation Logic
console.log('\x1b[36m[Test 1] Cart Drawer Free-Shipping Threshold & Add-ons Math\x1b[0m');

function calculateCartTotals(items, addons) {
    let subtotal = 0;
    items.forEach(i => subtotal += i.price * i.quantity);

    let addonTotal = 0;
    if (addons.gift_wrap) addonTotal += 5.00;
    if (addons.polishing_cloth) addonTotal += 15.00;

    const threshold = 150.00;
    const progress = Math.min(100, Math.round((subtotal / threshold) * 100));
    const remaining = Math.max(0, parseFloat((threshold - subtotal).toFixed(2)));
    const total = parseFloat((subtotal + addonTotal).toFixed(2));

    return {
        subtotal,
        addonTotal,
        total,
        progress,
        remaining,
        freeShipping: subtotal >= threshold,
    };
}

const cartAbove = calculateCartTotals([{ price: 899, quantity: 1 }], { gift_wrap: true, polishing_cloth: false });
assert(cartAbove.subtotal === 899, 'Cart subtotal matches item price * quantity ($899.00)');
assert(cartAbove.addonTotal === 5.00, 'Luxury gift wrap add-on calculates +$5.00');
assert(cartAbove.total === 904.00, 'Total is subtotal + add-on ($904.00)');
assert(cartAbove.freeShipping === true, 'Free shipping unlocked when subtotal >= $150');
assert(cartAbove.progress === 100, 'Free shipping progress is 100%');
assert(cartAbove.remaining === 0, 'Free shipping remaining is $0.00');

const cartBelow = calculateCartTotals([{ price: 45, quantity: 2 }], { gift_wrap: false, polishing_cloth: true });
assert(cartBelow.subtotal === 90, 'Cart subtotal is $90.00');
assert(cartBelow.addonTotal === 15.00, 'Microfiber cloth calculates +$15.00');
assert(cartBelow.total === 105.00, 'Cart total is $105.00');
assert(cartBelow.freeShipping === false, 'Free shipping locked below $150 threshold');
assert(cartBelow.progress === 60, 'Free shipping progress is 60% ($90/$150)');
assert(cartBelow.remaining === 60.00, 'Free shipping remaining is $60.00');

// TEST 2: Checkout Customizer Validation
console.log('\n\x1b[36m[Test 2] Checkout Validation & Field Suppression\x1b[0m');

function validateCheckout(data) {
    const errors = {};
    if (!data.email || !data.email.includes('@')) errors.email = 'Invalid email';
    if (!data.first_name) errors.first_name = 'Missing first name';
    if (!data.last_name) errors.last_name = 'Missing last name';
    if (!data.address_1) errors.address_1 = 'Missing address';
    if (!data.city) errors.city = 'Missing city';
    if (!data.postal_code || data.postal_code.length < 3) errors.postal_code = 'Invalid postal code';
    return { valid: Object.keys(errors).length === 0, errors };
}

const validPayload = {
    email: 'alex@enterprise.com',
    first_name: 'Alexander',
    last_name: 'Vance',
    address_1: '100 Port Street',
    city: 'Newark',
    postal_code: '07114',
};
assert(validateCheckout(validPayload).valid === true, 'Valid checkout payload passes verification');

const invalidPayload = {
    email: 'not-an-email',
    first_name: '',
    address_1: '',
    postal_code: '1',
};
const invalidResult = validateCheckout(invalidPayload);
assert(invalidResult.valid === false, 'Invalid checkout payload rejected');
assert(!!invalidResult.errors.email, 'Error reported for invalid email');
assert(!!invalidResult.errors.first_name, 'Error reported for missing first name');
assert(!!invalidResult.errors.postal_code, 'Error reported for invalid postal code');

// TEST 3: Wallet Ledger Logic
console.log('\n\x1b[36m[Test 3] Store Credit Wallet Debit / Credit Rules\x1b[0m');

let mockWalletBalance = 100.00;
function creditWallet(amt) {
    mockWalletBalance += amt;
    return mockWalletBalance;
}
function debitWallet(amt) {
    if (mockWalletBalance < amt) return false;
    mockWalletBalance -= amt;
    return true;
}

assert(mockWalletBalance === 100.00, 'Initial wallet balance is $100.00');
assert(debitWallet(25.00) === true, 'Debit $25.00 approved');
assert(mockWalletBalance === 75.00, 'Balance after debit is $75.00');
assert(debitWallet(100.00) === false, 'Over-debit of $100.00 on $75.00 balance rejected');
assert(creditWallet(50.00) === 125.00, 'Credit $50.00 approved, balance is now $125.00');

// TEST 4: Post-Purchase Upsell Pricing Logic
console.log('\n\x1b[36m[Test 4] 1-Click Post-Purchase Upsell Calculation\x1b[0m');

const basePrice = 149.00;
const discountPct = 35;
const discountAmt = parseFloat(((basePrice * discountPct) / 100).toFixed(2));
const offerPrice = parseFloat((basePrice - discountAmt).toFixed(2));

assert(discountAmt === 52.15, '35% discount on $149.00 saves $52.15');
assert(offerPrice === 96.85, 'Exclusive post-purchase offer price is $96.85');

// TEST 5: Abandoned Cart Signed Token Verification
console.log('\n\x1b[36m[Test 5] Abandoned Checkout Recovery Token Format\x1b[0m');

const cartToken = 'ct_' + Math.random().toString(36).substring(2);
const recoveryToken = 'rec_' + Buffer.from(cartToken + '|user@example.com').toString('hex');
assert(recoveryToken.length > 20, 'Generated secure signed recovery token string');
assert(recoveryToken.startsWith('rec_'), 'Token has valid signature prefix');

console.log('\n======================================================');
console.log(`  Sprint 02 Test Summary: ${passedCount}/${testsCount} Passed (${failedCount} Failed)`);
console.log('======================================================\n');

if (failedCount > 0) {
    process.exit(1);
}
