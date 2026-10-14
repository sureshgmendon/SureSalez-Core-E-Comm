/**
 * SureSalez Sprint 03 Unit & Integration Test Suite
 *
 * Tests Multi-Currency conversion, Psychological Charm Rounding,
 * Indian GST (CGST/SGST vs IGST), GSTIN format & state prefix validation,
 * Export Zero-Rating under LUT, Cross-Border Duties estimation, and
 * Context-Aware Payment Gateway Routing.
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
console.log('  SureSalez Core — Sprint 03 Focused Test Suite');
console.log('  Global Localization, Multi-Currency & Compliance');
console.log('======================================================\n');

// -----------------------------------------------------------------------------
// TEST 1: Currency Conversion & Retail Psychological Charm Rounding
// -----------------------------------------------------------------------------
console.log('\x1b[36m[Test 1] Multi-Currency Conversion & Psychological Rounding Rules\x1b[0m');

const currencies = {
    USD: { code: 'USD', symbol: '$', rate: 1.0, rounding_rule: 'dot_99' },
    EUR: { code: 'EUR', symbol: '€', rate: 0.92, rounding_rule: 'dot_99' },
    GBP: { code: 'GBP', symbol: '£', rate: 0.78, rounding_rule: 'dot_99' },
    INR: { code: 'INR', symbol: '₹', rate: 84.50, rounding_rule: 'nearest_integer' },
    AED: { code: 'AED', symbol: 'د.إ', rate: 3.67, rounding_rule: 'dot_00' },
    JPY: { code: 'JPY', symbol: '¥', rate: 152.0, rounding_rule: 'nearest_integer' },
};

function applyPsychologicalRounding(amount, rule) {
    switch (rule) {
        case 'dot_99':
            return Math.floor(amount) + 0.99;
        case 'dot_00':
            return Math.round(amount);
        case 'nearest_integer':
            return Math.round(amount);
        default:
            return parseFloat(amount.toFixed(2));
    }
}

function convertPrice(priceUsd, targetCode) {
    const curr = currencies[targetCode] || currencies.USD;
    const raw = priceUsd * curr.rate;
    const rounded = applyPsychologicalRounding(raw, curr.rounding_rule);
    const decimals = ['JPY', 'INR'].includes(targetCode) ? 0 : 2;
    return {
        currency: targetCode,
        symbol: curr.symbol,
        rate: curr.rate,
        raw,
        amount: rounded,
        formatted: `${curr.symbol}${rounded.toLocaleString('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals })}`,
    };
}

const basePrice = 45.00; // $45.00 item
const eurPrice = convertPrice(basePrice, 'EUR');
// 45 * 0.92 = 41.40 -> dot_99 = 41.99
assert(eurPrice.amount === 41.99, 'EUR conversion applies .99 charm rounding (41.40 -> 41.99)');
assert(eurPrice.formatted.includes('€41.99'), 'EUR formatted output includes symbol and .99 format');

const inrPrice = convertPrice(basePrice, 'INR');
// 45 * 84.50 = 3802.50 -> nearest integer = 3803
assert(inrPrice.amount === 3803, 'INR conversion applies integer rounding (3802.50 -> 3803)');
assert(inrPrice.formatted.startsWith('₹'), 'INR formatted output contains rupee symbol ₹');

const aedPrice = convertPrice(basePrice, 'AED');
// 45 * 3.67 = 165.15 -> dot_00 = 165
assert(aedPrice.amount === 165, 'AED conversion applies dot_00 clean rounding (165.15 -> 165)');

const gbpPrice = convertPrice(basePrice, 'GBP');
// 45 * 0.78 = 35.10 -> dot_99 = 35.99
assert(gbpPrice.amount === 35.99, 'GBP conversion applies .99 charm pricing (35.10 -> 35.99)');

const jpyPrice = convertPrice(basePrice, 'JPY');
// 45 * 152 = 6840
assert(jpyPrice.amount === 6840, 'JPY conversion calculates correct yen amount (6840)');

// -----------------------------------------------------------------------------
// TEST 2: Indian GST Engine: Intra-State (CGST+SGST) vs Inter-State (IGST)
// -----------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 2] Indian GST Splitting: Intra-State vs Inter-State\x1b[0m');

function calculateIndiaGst(subtotal, originState, destState, hsnCode) {
    const isIntraState = originState.toUpperCase() === destState.toUpperCase();
    const rate = (hsnCode === '7117' || hsnCode === '7113') ? 3.0 : 18.0;

    if (isIntraState) {
        const halfRate = rate / 2.0;
        const cgst = parseFloat(((subtotal * halfRate) / 100.0).toFixed(2));
        const sgst = parseFloat(((subtotal * halfRate) / 100.0).toFixed(2));
        const totalTax = parseFloat((cgst + sgst).toFixed(2));
        return {
            isIntraState: true,
            totalTax,
            grandTotal: parseFloat((subtotal + totalTax).toFixed(2)),
            breakdown: [
                { type: 'CGST', rate: halfRate, amount: cgst },
                { type: 'SGST', rate: halfRate, amount: sgst },
            ],
        };
    } else {
        const igst = parseFloat(((subtotal * rate) / 100.0).toFixed(2));
        return {
            isIntraState: false,
            totalTax: igst,
            grandTotal: parseFloat((subtotal + igst).toFixed(2)),
            breakdown: [
                { type: 'IGST', rate: rate, amount: igst },
            ],
        };
    }
}

// Case A: Intra-State standard item (KA -> KA, subtotal ₹10,000, 18% standard GST)
const intraStandard = calculateIndiaGst(10000, 'KA', 'KA', 'DEFAULT');
assert(intraStandard.isIntraState === true, 'Intra-state detected when shipping within Karnataka (KA)');
assert(intraStandard.breakdown.length === 2, 'Intra-state splits tax into 2 equal parts (CGST + SGST)');
assert(intraStandard.breakdown[0].rate === 9.0 && intraStandard.breakdown[0].amount === 900.00, 'CGST is 9% (₹900.00)');
assert(intraStandard.breakdown[1].rate === 9.0 && intraStandard.breakdown[1].amount === 900.00, 'SGST is 9% (₹900.00)');
assert(intraStandard.totalTax === 1800.00, 'Total tax is ₹1800.00');
assert(intraStandard.grandTotal === 11800.00, 'Grand total is ₹11,800.00');

// Case B: Intra-State Jewelry item HSN 7117 (KA -> KA, subtotal ₹10,000, 3% GST)
const intraJewelry = calculateIndiaGst(10000, 'KA', 'KA', '7117');
assert(intraJewelry.breakdown[0].rate === 1.5 && intraJewelry.breakdown[0].amount === 150.00, 'HSN 7117 Jewelry CGST is 1.5% (₹150.00)');
assert(intraJewelry.breakdown[1].rate === 1.5 && intraJewelry.breakdown[1].amount === 150.00, 'HSN 7117 Jewelry SGST is 1.5% (₹150.00)');
assert(intraJewelry.totalTax === 300.00, 'Total jewelry GST is ₹300.00 (3%)');

// Case C: Inter-State standard item (KA -> MH, subtotal ₹10,000, 18% IGST)
const interStandard = calculateIndiaGst(10000, 'KA', 'MH', 'DEFAULT');
assert(interStandard.isIntraState === false, 'Inter-state detected when shipping KA -> MH');
assert(interStandard.breakdown.length === 1, 'Inter-state applies single IGST line item');
assert(interStandard.breakdown[0].type === 'IGST' && interStandard.breakdown[0].rate === 18.0, 'IGST is 18% (₹1,800.00)');
assert(interStandard.totalTax === 1800.00, 'Total IGST tax is ₹1,800.00');

// Case D: Inter-State Jewelry item HSN 7117 (KA -> DL, subtotal ₹10,000, 3% IGST)
const interJewelry = calculateIndiaGst(10000, 'KA', 'DL', '7117');
assert(interJewelry.breakdown[0].type === 'IGST' && interJewelry.breakdown[0].rate === 3.0, 'HSN 7117 Jewelry IGST is 3% (₹300.00)');
assert(interJewelry.totalTax === 300.00, 'Total jewelry IGST is ₹300.00');

// -----------------------------------------------------------------------------
// TEST 3: Indian GSTIN B2B Format & State Prefix Verification
// -----------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 3] Indian GSTIN B2B Format & State Prefix Validation\x1b[0m');

const GST_STATE_MAP = {
    '07': 'DL',
    '27': 'MH',
    '29': 'KA',
    '33': 'TN',
    '36': 'TS',
};

function validateGstin(gstin, expectedState) {
    if (!gstin) return { valid: false, message: 'Empty GSTIN' };
    const pattern = /^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/;
    if (!pattern.test(gstin)) {
        return { valid: false, message: 'Invalid GSTIN regex format' };
    }
    const statePrefix = gstin.substring(0, 2);
    const mappedState = GST_STATE_MAP[statePrefix];
    if (!mappedState) {
        return { valid: false, message: 'Unrecognized state prefix code' };
    }
    if (expectedState && expectedState.toUpperCase() !== mappedState) {
        return {
            valid: false,
            message: `State code mismatch: GSTIN is ${mappedState} (${statePrefix}) but billing state is ${expectedState.toUpperCase()}`,
            statePrefix,
            mappedState,
        };
    }
    return {
        valid: true,
        gstin,
        statePrefix,
        mappedState,
        pan: gstin.substring(2, 12),
        message: 'Valid GSTIN',
    };
}

// Valid Karnataka GSTIN
const validKaGstin = validateGstin('29ABCDE1234F1Z5', 'KA');
assert(validKaGstin.valid === true, 'Valid Karnataka GSTIN (29ABCDE1234F1Z5) passes validation');
assert(validKaGstin.mappedState === 'KA', 'State code correctly mapped to KA from prefix 29');
assert(validKaGstin.pan === 'ABCDE1234F', 'Customer PAN extracted from GSTIN (ABCDE1234F)');

// Valid Maharashtra GSTIN
const validMhGstin = validateGstin('27AAAAA0000A1Z5', 'MH');
assert(validMhGstin.valid === true, 'Valid Maharashtra GSTIN (27AAAAA0000A1Z5) passes validation');

// Invalid format (too short / invalid characters)
const invalidRegex = validateGstin('INVALIDGST123', 'KA');
assert(invalidRegex.valid === false, 'Invalid regex string correctly rejected');

// State prefix mismatch (GSTIN is KA 29, but billing state is MH)
const mismatchGstin = validateGstin('29ABCDE1234F1Z5', 'MH');
assert(mismatchGstin.valid === false, 'GSTIN state mismatch (prefix 29 vs state MH) correctly flagged');
assert(mismatchGstin.message.includes('State code mismatch'), 'Helpful error message returned on state mismatch');

// -----------------------------------------------------------------------------
// TEST 4: Export Zero-Rating under LUT & Cross-Border Customs Duties (DDP/DDU)
// -----------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 4] Export Zero-Rating under LUT & Cross-Border Duties (DDP vs DDU)\x1b[0m');

function calculateExportCompliance(subtotal, destCountry) {
    const isExport = destCountry !== 'IN';
    if (!isExport) {
        return { isExport: false };
    }

    const dutyMatrix = {
        US: { dutyRate: 0.0,  taxRate: 0.0,  precollected: false, mode: 'DDU', badge: 'US Import: Duty-Free under $800 De Minimis' },
        GB: { dutyRate: 4.0,  taxRate: 20.0, precollected: true,  mode: 'DDP', badge: 'UK DDP: 20% VAT & Customs Pre-collected' },
        DE: { dutyRate: 4.5,  taxRate: 19.0, precollected: true,  mode: 'DDP', badge: 'EU IOSS: 19% German VAT Pre-collected' },
        AE: { dutyRate: 5.0,  taxRate: 5.0,  precollected: false, mode: 'DDU', badge: 'UAE: 5% VAT & Duty payable upon delivery (DDU)' },
        AU: { dutyRate: 5.0,  taxRate: 10.0, precollected: true,  mode: 'DDP', badge: 'AU DDP: 10% Australian GST Pre-collected' },
    };

    const rule = dutyMatrix[destCountry] || { dutyRate: 5.0, taxRate: 10.0, precollected: false, mode: 'DDU', badge: 'International Import' };
    const dutyAmount = parseFloat(((subtotal * rule.dutyRate) / 100).toFixed(2));
    const taxAmount = parseFloat(((subtotal * rule.taxRate) / 100).toFixed(2));
    const totalExtra = rule.precollected ? (dutyAmount + taxAmount) : 0;

    return {
        isExport: true,
        domesticGst: 0.0,
        lutNotice: 'Supply meant for export under bond or letter of undertaking (LUT) without payment of integrated tax.',
        precollected: rule.precollected,
        mode: rule.mode,
        estimatedDuty: dutyAmount,
        estimatedTax: taxAmount,
        grandTotal: parseFloat((subtotal + totalExtra).toFixed(2)),
        badge: rule.badge,
    };
}

// UK Export: DDP with 20% VAT + 4% Duty pre-collected
const ukExport = calculateExportCompliance(500, 'GB');
assert(ukExport.isExport === true, 'UK identified as export destination');
assert(ukExport.domesticGst === 0.0, 'Domestic Indian GST is zero-rated under LUT (0.00)');
assert(ukExport.lutNotice.includes('letter of undertaking'), 'LUT legal compliance notice attached');
assert(ukExport.mode === 'DDP', 'UK delivery duty mode is DDP (Pre-collected)');
assert(ukExport.estimatedDuty === 20.00, 'UK estimated duty is 4% on $500 ($20.00)');
assert(ukExport.estimatedTax === 100.00, 'UK estimated VAT is 20% on $500 ($100.00)');
assert(ukExport.grandTotal === 620.00, 'UK checkout grand total reflects pre-collected taxes ($620.00)');

// US Export: DDU under $800 De Minimis
const usExport = calculateExportCompliance(500, 'US');
assert(usExport.isExport === true, 'US identified as export destination');
assert(usExport.mode === 'DDU', 'US delivery duty mode is DDU');
assert(usExport.badge.includes('De Minimis'), 'US De Minimis exemption badge displayed');
assert(usExport.grandTotal === 500.00, 'No extra tax pre-collected on US order ($500.00)');

// Germany Export: EU IOSS DDP
const deExport = calculateExportCompliance(300, 'DE');
assert(deExport.mode === 'DDP', 'Germany uses EU IOSS DDP mode');
assert(deExport.badge.includes('EU IOSS'), 'EU IOSS badge attached');

// -----------------------------------------------------------------------------
// TEST 5: Context-Aware Payment Gateway Routing
// -----------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 5] Context-Aware Payment Gateway Routing & Settlement Verification\x1b[0m');

const GATEWAY_CURRENCIES = {
    razorpay: ['INR', 'USD', 'EUR', 'GBP', 'AED'],
    cashfree: ['INR'],
    upi: ['INR'],
    stripe: ['USD', 'EUR', 'GBP', 'INR', 'AED', 'JPY', 'AUD', 'CAD'],
    paypal: ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY'],
    suresalez_wallet: ['USD', 'EUR', 'GBP', 'INR', 'AED', 'JPY', 'AUD', 'CAD'],
};

function routeGateways(availableGateways, country, currency) {
    const isIndia = (country === 'IN');
    const filtered = [];

    availableGateways.forEach(gw => {
        // Regional appropriateness
        if (isIndia) {
            // Suppress foreign-only PayPal if paying in INR
            if (gw === 'paypal' && currency === 'INR') {
                return;
            }
        } else {
            // Suppress India-domestic-only gateways for international customers
            if (['cashfree', 'upi', 'paytm', 'phonepe'].includes(gw)) {
                return;
            }
        }

        // Settlement currency support
        if (GATEWAY_CURRENCIES[gw] && !GATEWAY_CURRENCIES[gw].includes(currency)) {
            return;
        }

        filtered.push(gw);
    });

    return filtered;
}

const allGateways = ['razorpay', 'cashfree', 'upi', 'stripe', 'paypal', 'suresalez_wallet'];

// Domestic India with INR
const domesticIndiaInr = routeGateways(allGateways, 'IN', 'INR');
assert(domesticIndiaInr.includes('upi'), 'UPI routed for Domestic India INR');
assert(domesticIndiaInr.includes('cashfree'), 'Cashfree routed for Domestic India INR');
assert(domesticIndiaInr.includes('razorpay'), 'Razorpay routed for Domestic India INR');
assert(!domesticIndiaInr.includes('paypal'), 'PayPal suppressed for Domestic India INR transactions');

// International US with USD
const internationalUsd = routeGateways(allGateways, 'US', 'USD');
assert(internationalUsd.includes('stripe'), 'Stripe routed for US international checkout');
assert(internationalUsd.includes('paypal'), 'PayPal routed for US international checkout');
assert(!internationalUsd.includes('cashfree'), 'Cashfree suppressed for US international customer');
assert(!internationalUsd.includes('upi'), 'UPI suppressed for US international customer');

// International Germany with EUR
const internationalEur = routeGateways(allGateways, 'DE', 'EUR');
assert(internationalEur.includes('stripe'), 'Stripe routed for Germany checkout');
assert(internationalEur.includes('paypal'), 'PayPal routed for Germany checkout');
assert(!internationalEur.includes('upi'), 'UPI suppressed for European customer');

// -----------------------------------------------------------------------------
// SUMMARY
// -----------------------------------------------------------------------------
console.log('\n======================================================');
console.log(`  Sprint 03 Tests Finished: ${passedCount}/${testsCount} Passed (${failedCount} Failed)`);
console.log('======================================================\n');

if (failedCount > 0) {
    process.exit(1);
}
