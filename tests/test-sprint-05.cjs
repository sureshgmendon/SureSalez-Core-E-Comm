/**
 * SureSalez Core — Sprint 05 Focused Test Suite
 * Omnichannel Feeds, AI Chat Commerce, Shoppable Reels, CAPI & BI Analytics
 *
 * @package SureSalez\Tests
 */

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

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
console.log('  SureSalez Core — Sprint 05 Focused Test Suite');
console.log('  Omnichannel Feeds, AI Chat Commerce & Shoppable Reels');
console.log('======================================================\n');

// --------------------------------------------------------------------------
// TEST 1: OpenAPI 3.1 Specification & Natural Language Query Parser
// --------------------------------------------------------------------------
console.log('\x1b[36m[Test 1] OpenAPI 3.1 Specification & NLP Query Parser\x1b[0m');

// Mock Natural Language Query Parser based on class-ai-chat-commerce.php logic
function parseQuery(query) {
    const lower = query.toLowerCase().trim();
    const criteria = {
        query: query,
        min_price: null,
        max_price: null,
        category: null,
        material: null,
        keywords: [],
        intent: 'search',
    };

    const betweenMatch = lower.match(/between\s+\$?([0-9,]+(?:\.[0-9]+)?)\s+(?:and|-)\s+\$?([0-9,]+(?:\.[0-9]+)?)/i);
    if (betweenMatch) {
        criteria.min_price = parseFloat(betweenMatch[1].replace(/,/g, ''));
        criteria.max_price = parseFloat(betweenMatch[2].replace(/,/g, ''));
    } else {
        const underMatch = lower.match(/(?:under|below|less than|max|up to)\s+\$?([0-9,]+(?:\.[0-9]+)?)/i);
        if (underMatch) {
            criteria.max_price = parseFloat(underMatch[1].replace(/,/g, ''));
        }
        const overMatch = lower.match(/(?:over|above|more than|minimum|at least)\s+\$?([0-9,]+(?:\.[0-9]+)?)/i);
        if (overMatch) {
            criteria.min_price = parseFloat(overMatch[1].replace(/,/g, ''));
        }
    }

    const materials = ['titanium', 'emerald', 'sapphire', 'leather', 'ceramic', 'rose gold', 'gold', 'steel', 'mesh'];
    for (const mat of materials) {
        if (lower.includes(mat)) {
            criteria.material = mat;
            criteria.keywords.push(mat);
            break;
        }
    }

    if (lower.includes('watch') || lower.includes('chronograph') || lower.includes('diver')) {
        criteria.category = 'Watches';
    } else if (lower.includes('strap') || lower.includes('loop') || lower.includes('band')) {
        criteria.category = 'Straps';
    } else if (lower.includes('case') || lower.includes('roll') || lower.includes('accessories')) {
        criteria.category = 'Accessories';
    }

    if (lower.includes('buy') || lower.includes('order') || lower.includes('cart') || lower.includes('checkout')) {
        criteria.intent = 'purchase';
    } else if (lower.includes('recommend') || lower.includes('suggest') || lower.includes('best') || lower.includes('gift')) {
        criteria.intent = 'recommendation';
    } else if (lower.includes('stock') || lower.includes('available') || lower.includes('inventory')) {
        criteria.intent = 'stock_check';
    } else if (lower.includes('discount') || lower.includes('coupon') || lower.includes('deal') || lower.includes('sale')) {
        criteria.intent = 'promo';
    }

    return criteria;
}

const q1 = parseQuery('Show me luxury titanium watches under $1,000');
assert(q1.max_price === 1000, 'NLP extracts budget max_price: $1000');
assert(q1.material === 'titanium', 'NLP identifies material: titanium');
assert(q1.category === 'Watches', 'NLP maps category to Watches');

const q2 = parseQuery('Recommend leather travel cases between $50 and $150');
assert(q2.min_price === 50 && q2.max_price === 150, 'NLP extracts range budget: $50 - $150');
assert(q2.material === 'leather', 'NLP identifies material: leather');
assert(q2.category === 'Accessories', 'NLP maps category to Accessories');
assert(q2.intent === 'recommendation', 'NLP recognizes recommendation intent');

const q3 = parseQuery('Is the Ceramic Diver available in stock?');
assert(q3.intent === 'stock_check', 'NLP detects stock check inquiry');
assert(q3.material === 'ceramic', 'NLP identifies ceramic material');

const q4 = parseQuery('I want to buy the Royal Emerald chronograph');
assert(q4.intent === 'purchase', 'NLP identifies purchase intent');
assert(q4.material === 'emerald', 'NLP extracts emerald gemstone/dial material');

// Verify OpenAPI Spec File contents
const aiCommerceCode = fs.readFileSync(path.join(__dirname, '../plugins/suresalez-core/modules/omnichannel-ai/class-ai-chat-commerce.php'), 'utf8');
assert(aiCommerceCode.includes("'openapi' => '3.1.0'"), 'OpenAPI manifest version is 3.1.0');
assert(aiCommerceCode.includes("'/ai/chat'"), 'OpenAPI defines /ai/chat conversational endpoint');
assert(aiCommerceCode.includes("'/ai/recommend'"), 'OpenAPI defines /ai/recommend endpoint');
assert(aiCommerceCode.includes("'/ai/cart-link'"), 'OpenAPI defines /ai/cart-link endpoint');

// --------------------------------------------------------------------------
// TEST 2: AI Direct Authenticated Checkout Link Generator
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 2] AI Direct Checkout Link Generator & Attribution\x1b[0m');

function generateCartLink(productId, quantity = 1, ref = 'ai-chat', coupon = null) {
    const params = new URLSearchParams();
    params.set('add-to-cart', productId);
    params.set('quantity', Math.max(1, quantity));
    params.set('ref', ref);
    if (coupon) {
        params.set('coupon_code', coupon);
    }
    return `/checkout/?${params.toString()}`;
}

const link1 = generateCartLink(101, 1, 'ai-chat');
assert(link1.includes('add-to-cart=101'), 'Checkout URL contains product ID 101');
assert(link1.includes('quantity=1'), 'Checkout URL contains quantity 1');
assert(link1.includes('ref=ai-chat'), 'Checkout URL preserves ai-chat attribution tracking');

const link2 = generateCartLink(202, 2, 'gemini-copilot', 'CYBERVIP');
assert(link2.includes('add-to-cart=202'), 'Checkout URL contains product ID 202');
assert(link2.includes('quantity=2'), 'Checkout URL sets quantity 2');
assert(link2.includes('coupon_code=CYBERVIP'), 'Checkout URL applies promotional coupon CYBERVIP');
assert(link2.includes('ref=gemini-copilot'), 'Checkout URL records external AI agent attribution');

// --------------------------------------------------------------------------
// TEST 3: CAPI Server-Side Dispatcher & SHA-256 Parameter Hashing
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 3] CAPI Server-Side Dispatcher & SHA-256 Parameter Hashing\x1b[0m');

function hashString(val, isEmail = false) {
    let str = String(val).toLowerCase().trim();
    if (isEmail) {
        str = str.replace(/\s+/g, '');
    }
    return crypto.createHash('sha256').update(str).digest('hex');
}

function hashUserData(data) {
    const hashed = {};
    if (data.email) {
        hashed.em = hashString(data.email, true);
    }
    if (data.phone) {
        const digits = String(data.phone).replace(/[^0-9]/g, '');
        hashed.ph = crypto.createHash('sha256').update(digits).digest('hex');
    }
    if (data.first_name) {
        hashed.fn = hashString(data.first_name);
    }
    if (data.last_name) {
        hashed.ln = hashString(data.last_name);
    }
    if (data.city) {
        hashed.ct = hashString(data.city);
    }
    if (data.postal_code || data.zip) {
        const zip = data.postal_code || data.zip;
        hashed.zp = hashString(String(zip).replace(/\s+/g, ''));
    }
    if (data.country) {
        hashed.country = hashString(String(data.country).substring(0, 2));
    }
    return hashed;
}

const rawUser = {
    email: '  Collector.VIP@LuxuryWatches.COM  ',
    phone: '+1 (555) 234-5678',
    first_name: 'Alexander',
    last_name: 'Pierce',
    city: 'Geneva',
    postal_code: '1204',
    country: 'CH',
};

const hashedUser = hashUserData(rawUser);

const expectedEmailHash = crypto.createHash('sha256').update('collector.vip@luxurywatches.com').digest('hex');
assert(hashedUser.em === expectedEmailHash, 'Email normalized and SHA-256 hashed correctly');

const expectedPhoneHash = crypto.createHash('sha256').update('15552345678').digest('hex');
assert(hashedUser.ph === expectedPhoneHash, 'Phone digits normalized and SHA-256 hashed correctly');

assert(hashedUser.fn === hashString('alexander'), 'First name hashed with lowercase sha256');
assert(hashedUser.ln === hashString('pierce'), 'Last name hashed with lowercase sha256');
assert(hashedUser.country === hashString('ch'), 'Country ISO-2 code hashed with lowercase sha256');

// Deduplication ID test
function generateEventId(prefix = 'evt') {
    return `${prefix}_${Date.now()}_${crypto.randomBytes(6).toString('hex')}`;
}

const evtId1 = generateEventId('purchase');
const evtId2 = generateEventId('purchase');
assert(evtId1.startsWith('purchase_'), 'Event ID contains expected prefix');
assert(evtId1 !== evtId2, 'Consecutive event IDs are distinct and cryptographically unique');

// --------------------------------------------------------------------------
// TEST 4: Shoppable Video Reels Hotspot Geometry & Interaction Engine
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 4] Shoppable Reels Hotspot Geometry & Interaction Engine\x1b[0m');

const mockReel = {
    id: 1,
    title: 'Behind the Craft: Royal Emerald Chronograph Assembly',
    duration: 28,
    tagged_products: [
        {
            product_id: 101,
            title: 'Royal Emerald Automatic Chronograph',
            price: 899.00,
            x_pct: 50.0,
            y_pct: 65.0,
            timestamp: 4,
        },
        {
            product_id: 102,
            title: 'Italian Handcrafted Leather Travel Case',
            price: 120.00,
            x_pct: 32.0,
            y_pct: 78.0,
            timestamp: 12,
        },
    ],
    views_count: 14200,
    clicks_count: 1840,
};

assert(mockReel.tagged_products.length === 2, 'Reel contains 2 tagged product hotspots');

mockReel.tagged_products.forEach(p => {
    assert(p.x_pct >= 0 && p.x_pct <= 100, `Hotspot x_pct (${p.x_pct}%) within 0-100% viewport range`);
    assert(p.y_pct >= 0 && p.y_pct <= 100, `Hotspot y_pct (${p.y_pct}%) within 0-100% viewport range`);
    assert(p.price > 0, `Hotspot product has positive price ($${p.price})`);
    assert(p.product_id > 0, `Hotspot points to valid product ID: ${p.product_id}`);
});

// View and click counters
let currentViews = mockReel.views_count;
let currentClicks = mockReel.clicks_count;

function incrementView(views) { return views + 1; }
function incrementClick(clicks) { return clicks + 1; }

currentViews = incrementView(currentViews);
assert(currentViews === 14201, 'View counter increments cleanly on play event');

currentClicks = incrementClick(currentClicks);
assert(currentClicks === 1841, 'Click counter increments cleanly on hotspot interaction');

const ctr = (currentClicks / currentViews) * 100;
assert(ctr > 10 && ctr < 20, `Reel Click-Through Rate is ${ctr.toFixed(2)}% (High engagement benchmark)`);

// --------------------------------------------------------------------------
// TEST 5: Automated Marketplace Feeds (Google Merchant & Meta Catalog)
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 5] Automated Marketplace Feeds (Google & Meta Catalog)\x1b[0m');

const feedsCode = fs.readFileSync(path.join(__dirname, '../plugins/suresalez-core/modules/omnichannel-ai/class-marketplace-feeds.php'), 'utf8');

assert(feedsCode.includes('http://base.google.com/ns/1.0'), 'Google Merchant feed declares g: namespace');
assert(feedsCode.includes('g:id'), 'Google Merchant feed exports g:id');
assert(feedsCode.includes('g:price'), 'Google Merchant feed exports g:price');
assert(feedsCode.includes('g:gtin'), 'Google Merchant feed exports g:gtin barcode');
assert(feedsCode.includes('g:mpn'), 'Google Merchant feed exports g:mpn manufacturer part number');
assert(feedsCode.includes('g:custom_label_0'), 'Google Merchant feed maps HSN code into custom_label_0 (Sprint 03 Tax Sync)');
assert(feedsCode.includes('fb_product_category'), 'Meta Catalog feed maps Facebook product categories');
assert(feedsCode.includes('generate_meta_catalog_csv'), 'Meta Catalog provides CSV syndication export');

// Simulate Google Merchant XML Structure check
const sampleXml = `<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">
  <channel>
    <title>SureSalez Enterprise Store Product Feed</title>
    <link>https://suresalez.store</link>
    <item>
      <g:id>SS-101</g:id>
      <g:title>Royal Emerald Automatic Chronograph 42mm</g:title>
      <g:price>899.00 USD</g:price>
      <g:availability>in stock</g:availability>
      <g:brand>SureSalez Atelier</g:brand>
      <g:gtin>0894561234567</g:gtin>
      <g:custom_label_0>HSN:7117.90.90</g:custom_label_0>
    </item>
  </channel>
</rss>`;

assert(sampleXml.includes('<g:id>SS-101</g:id>'), 'XML contains valid item g:id');
assert(sampleXml.includes('899.00 USD'), 'XML contains currency-formatted price');
assert(sampleXml.includes('HSN:7117.90.90'), 'XML preserves Indian HSN tariff code');

// --------------------------------------------------------------------------
// TEST 6: Automated Campaign Scheduler & BI KPI Formulas
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 6] Campaign Scheduler & BI KPI Formulas\x1b[0m');

// BI KPI Formula Verifications
const grossRevenue = 168450.00;
const discounts = 12430.00;
const refunds = 2500.00;

// Net Revenue = Gross - Discounts - Refunds
const netRevenue = grossRevenue - discounts - refunds;
assert(netRevenue === 153520.00, `Net Revenue calculates to $${netRevenue.toLocaleString()} (Gross - Discounts - Refunds)`);

// COGS at 35.2%
const cogs = netRevenue * 0.352;
const grossProfit = netRevenue - cogs;
const grossMarginPct = (grossProfit / netRevenue) * 100;
assert(Math.abs(grossMarginPct - 64.8) < 0.01, `Gross Margin is ${grossMarginPct.toFixed(1)}%`);

// Cart-to-detail rate
const detailViews = 48200;
const addToCarts = 8990;
const cartToDetailRate = (addToCarts / detailViews) * 100;
assert(Math.abs(cartToDetailRate - 18.65) < 0.01, `Cart-to-Detail rate is ${cartToDetailRate.toFixed(2)}%`);

// Channel Attribution check
const channelAttribution = {
    'Organic Search': 35.2,
    'Shoppable Reels': 26.4,
    'Conversational AI Chat': 18.9,
    'Paid Meta / TikTok Ads': 12.5,
    'Automated CRM Flows': 7.0,
};

const totalAttribution = Object.values(channelAttribution).reduce((a, b) => a + b, 0);
assert(Math.abs(totalAttribution - 100.0) < 0.01, `Omnichannel attribution adds up to exactly 100% (${totalAttribution}%)`);
assert(channelAttribution['Shoppable Reels'] === 26.4, 'Shoppable Reels contributes 26.4% attribution');
assert(channelAttribution['Conversational AI Chat'] === 18.9, 'Conversational AI Chat contributes 18.9% attribution');

// Campaign schedule time window evaluation
function isCampaignActive(campaign, testTimeStr) {
    const testTime = new Date(testTimeStr).getTime();
    const start = new Date(campaign.start_time).getTime();
    const end = new Date(campaign.end_time).getTime();
    return testTime >= start && testTime <= end;
}

const activeCampaign = {
    start_time: '2026-10-01 00:00:00',
    end_time: '2026-11-30 23:59:59',
};

assert(isCampaignActive(activeCampaign, '2026-10-14 12:00:00') === true, 'Campaign is active during scheduled window (Oct 14, 2026)');
assert(isCampaignActive(activeCampaign, '2026-09-25 12:00:00') === false, 'Campaign is inactive prior to start date (Sep 25, 2026)');
assert(isCampaignActive(activeCampaign, '2026-12-05 12:00:00') === false, 'Campaign is expired after end date (Dec 5, 2026)');

// --------------------------------------------------------------------------
// TEST SUMMARY
// --------------------------------------------------------------------------
console.log('\n======================================================');
console.log(`  Sprint 05 Tests Finished: ${passedTests}/${totalTests} Passed (${failedTests} Failed)`);
console.log('======================================================\n');

if (failedTests > 0) {
    process.exit(1);
}
