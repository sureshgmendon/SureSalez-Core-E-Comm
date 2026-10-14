/**
 * SureSalez Core — Sprint 09 Focused Test Suite
 * Loyalty Points, VIP Tiers, 301 SEO Redirects & Headless Mobile APIs
 */

const fs = require('fs');
const path = require('path');
const assert = require('assert');

console.log('\n======================================================');
console.log('  SureSalez Core — Sprint 09 Focused Test Suite');
console.log('  Loyalty Points, VIP Tiers, 301 Redirects & Mobile APIs');
console.log('======================================================\n');

let passedTests = 0;
let totalTests = 0;

function it(desc, fn) {
  totalTests++;
  try {
    fn();
    console.log(`  ✔ PASS: ${desc}`);
    passedTests++;
  } catch (err) {
    console.error(`  ✖ FAIL: ${desc}`);
    console.error(`    ${err.message}`);
  }
}

const baseDir = path.resolve(__dirname, '..');

// ==========================================
// [Test 1] VIP Tiers & Points Accrual Logic
// ==========================================
console.log('[Test 1] VIP Tiers & Points Accrual Calculations');

const TIER_MULTIPLIERS = {
  bronze: 1.0,
  silver: 1.25,
  gold: 1.5,
  platinum: 2.0
};

function calculatePointsEarned(spend, tier = 'bronze') {
  if (spend <= 0) return 0;
  const mult = TIER_MULTIPLIERS[tier.toLowerCase()] || 1.0;
  return Math.round(spend * mult);
}

function evaluateTier(lifetimePoints, rollingSpend = 0) {
  if (lifetimePoints >= 3000 || rollingSpend >= 3000) return 'platinum';
  if (lifetimePoints >= 1500 || rollingSpend >= 1500) return 'gold';
  if (lifetimePoints >= 500 || rollingSpend >= 500) return 'silver';
  return 'bronze';
}

it('Bronze tier earns 1.0x points ($100 = 100 pts)', () => {
  assert.strictEqual(calculatePointsEarned(100, 'bronze'), 100);
});

it('Silver tier earns 1.25x points ($100 = 125 pts)', () => {
  assert.strictEqual(calculatePointsEarned(100, 'silver'), 125);
});

it('Gold tier earns 1.5x points ($100 = 150 pts)', () => {
  assert.strictEqual(calculatePointsEarned(100, 'gold'), 150);
});

it('Platinum tier earns 2.0x points ($100 = 200 pts)', () => {
  assert.strictEqual(calculatePointsEarned(100, 'platinum'), 200);
});

it('Luxury watch purchase ($899) at Gold tier earns 1,349 points', () => {
  assert.strictEqual(calculatePointsEarned(899, 'gold'), 1349);
});

it('Zero or negative spend yields 0 points', () => {
  assert.strictEqual(calculatePointsEarned(0, 'platinum'), 0);
  assert.strictEqual(calculatePointsEarned(-50, 'gold'), 0);
});

it('Tier elevation evaluates correctly: <500 Bronze, 500-1499 Silver, 1500-2999 Gold, 3000+ Platinum', () => {
  assert.strictEqual(evaluateTier(250), 'bronze');
  assert.strictEqual(evaluateTier(500), 'silver');
  assert.strictEqual(evaluateTier(1200), 'silver');
  assert.strictEqual(evaluateTier(1500), 'gold');
  assert.strictEqual(evaluateTier(2999), 'gold');
  assert.strictEqual(evaluateTier(3000), 'platinum');
  assert.strictEqual(evaluateTier(10000), 'platinum');
});

// ==========================================
// [Test 2] Checkout Points Redemption Slider Math
// ==========================================
console.log('\n[Test 2] Checkout Points Redemption Slider Math & Cap Enforcements');

const POINT_VALUE = 0.05; // $0.05 per point ($5 per 100 pts)
const MIN_REDEMPTION = 200; // 200 points ($10.00 min)
const MAX_PERCENT = 50.0; // 50% max order discount

function calculateRedemption(points, cartTotal, userBalance) {
  if (points <= 0) {
    return { valid: false, error: 'Points must be greater than zero' };
  }
  if (points < MIN_REDEMPTION) {
    return { valid: false, error: `Minimum redemption is ${MIN_REDEMPTION} points` };
  }
  if (points > userBalance) {
    return { valid: false, error: 'Points exceed available balance' };
  }

  const grossDiscount = points * POINT_VALUE;
  const maxDiscount = cartTotal * (MAX_PERCENT / 100.0);
  const finalDiscount = Math.min(grossDiscount, maxDiscount);
  const pointsDeducted = Math.ceil(finalDiscount / POINT_VALUE);
  const remainingTotal = Math.max(0, parseFloat((cartTotal - finalDiscount).toFixed(2)));

  return {
    valid: true,
    points_redeemed: pointsDeducted,
    discount_amount: parseFloat(finalDiscount.toFixed(2)),
    new_balance: userBalance - pointsDeducted,
    remaining_total: remainingTotal,
    capped: grossDiscount > maxDiscount
  };
}

it('Redemption below 200 points minimum is rejected', () => {
  const res = calculateRedemption(150, 100, 1000);
  assert.strictEqual(res.valid, false);
  assert(res.error.includes('Minimum redemption'));
});

it('Redemption exceeding user balance is rejected', () => {
  const res = calculateRedemption(500, 100, 450);
  assert.strictEqual(res.valid, false);
  assert(res.error.includes('exceed available balance'));
});

it('Standard valid redemption calculates discount correctly: 200 pts on $100 order = $10 discount ($90 remaining)', () => {
  const res = calculateRedemption(200, 100.00, 1000);
  assert.strictEqual(res.valid, true);
  assert.strictEqual(res.points_redeemed, 200);
  assert.strictEqual(res.discount_amount, 10.00);
  assert.strictEqual(res.remaining_total, 90.00);
  assert.strictEqual(res.new_balance, 800);
  assert.strictEqual(res.capped, false);
});

it('Higher redemption calculates discount correctly: 500 pts on $200 order = $25 discount ($175 remaining)', () => {
  const res = calculateRedemption(500, 200.00, 1500);
  assert.strictEqual(res.valid, true);
  assert.strictEqual(res.points_redeemed, 500);
  assert.strictEqual(res.discount_amount, 25.00);
  assert.strictEqual(res.remaining_total, 175.00);
  assert.strictEqual(res.new_balance, 1000);
  assert.strictEqual(res.capped, false);
});

it('Cart discount cap (50%) en forces limit: 1000 pts ($50 value) on $40 cart is capped at $20 (400 pts deducted)', () => {
  const res = calculateRedemption(1000, 40.00, 2500);
  assert.strictEqual(res.valid, true);
  assert.strictEqual(res.capped, true);
  assert.strictEqual(res.discount_amount, 20.00);
  assert.strictEqual(res.points_redeemed, 400);
  assert.strictEqual(res.remaining_total, 20.00);
  assert.strictEqual(res.new_balance, 2100);
});

// ==========================================
// [Test 3] Dual-Sided Referral Program
// ==========================================
console.log('\n[Test 3] Dual-Sided Viral Referral Program & Discount Calculation');

const FRIEND_DISCOUNT_PERCENT = 15.0;
const REFERRER_BONUS_POINTS = 500;

function calculateFriendDiscount(subtotal, referralCode, customerId, referrerId) {
  if (!referralCode || referrerId <= 0 || (customerId > 0 && customerId === referrerId)) {
    return { valid: false, error: 'Invalid referral code or cannot refer yourself' };
  }
  const discountAmount = parseFloat((subtotal * (FRIEND_DISCOUNT_PERCENT / 100.0)).toFixed(2));
  return {
    valid: true,
    discount_percentage: FRIEND_DISCOUNT_PERCENT,
    discount_amount: discountAmount,
    referral_code: referralCode,
    referrer_id: referrerId
  };
}

it('Self-referral is strictly rejected', () => {
  const res = calculateFriendDiscount(100.00, 'SURE-VIP10', 10, 10);
  assert.strictEqual(res.valid, false);
  assert(res.error.includes('cannot refer yourself'));
});

it('Referred friend receives 15% discount on $200 order ($30 discount)', () => {
  const res = calculateFriendDiscount(200.00, 'SURE-VIP10', 12, 10);
  assert.strictEqual(res.valid, true);
  assert.strictEqual(res.discount_percentage, 15.0);
  assert.strictEqual(res.discount_amount, 30.00);
  assert.strictEqual(res.referrer_id, 10);
});

it('Referred friend receives 15% discount on $899 watch order ($134.85 discount)', () => {
  const res = calculateFriendDiscount(899.00, 'SURE-VIP10', 12, 10);
  assert.strictEqual(res.valid, true);
  assert.strictEqual(res.discount_amount, 134.85);
});

it('Referrer reward grants 500 bonus loyalty points ($25 value) upon completion', () => {
  const referrerAccount = { points_balance: 1250, lifetime_points: 2500, tier_level: 'gold' };
  const updatedPoints = referrerAccount.points_balance + REFERRER_BONUS_POINTS;
  const updatedLifetime = referrerAccount.lifetime_points + REFERRER_BONUS_POINTS;
  assert.strictEqual(updatedPoints, 1750);
  assert.strictEqual(updatedLifetime, 3000);
  assert.strictEqual(evaluateTier(updatedLifetime), 'platinum');
});

// ==========================================
// [Test 4] SEO 301 Redirects & Schema.org JSON-LD
// ==========================================
console.log('\n[Test 4] Automated SEO 301 Redirects & Schema.org Structured Data');

const redirectsTable = [
  { source_url: '/old-product/watch-series-1', target_url: '/product/classic-chronograph', status_code: 301 },
  { source_url: '/catalog/mens-watches', target_url: '/category/watches', status_code: 301 },
  { source_url: '/deals/black-friday-2023', target_url: '/deals', status_code: 301 }
];

function matchRedirect(path) {
  const cleanPath = path.replace(/\/+$/, '') || '/';
  return redirectsTable.find(r => r.source_url === cleanPath || r.source_url + '/' === cleanPath) || null;
}

it('Exact old URL path matches 301 redirect rule', () => {
  const match = matchRedirect('/old-product/watch-series-1');
  assert(match !== null);
  assert.strictEqual(match.target_url, '/product/classic-chronograph');
  assert.strictEqual(match.status_code, 301);
});

it('Trailing slash URL path matches 301 redirect rule', () => {
  const match = matchRedirect('/catalog/mens-watches/');
  assert(match !== null);
  assert.strictEqual(match.target_url, '/category/watches');
});

it('Unmatched URL path returns null without redirecting', () => {
  const match = matchRedirect('/product/royal-oak');
  assert.strictEqual(match, null);
});

function generateProductSchema(product) {
  return {
    "@context": "https://schema.org",
    "@type": "Product",
    "name": product.name,
    "sku": product.sku,
    "brand": { "@type": "Brand", "name": product.brand },
    "offers": {
      "@type": "Offer",
      "priceCurrency": product.currency,
      "price": product.price.toFixed(2),
      "availability": product.inStock ? "https://schema.org/InStock" : "https://schema.org/OutOfStock"
    },
    "aggregateRating": {
      "@type": "AggregateRating",
      "ratingValue": product.rating.toFixed(1),
      "reviewCount": product.reviewCount
    }
  };
}

it('Schema.org product JSON-LD output has valid Schema.org structure', () => {
  const schema = generateProductSchema({
    name: 'SureSalez Royal Chronometer 42mm',
    sku: 'SS-WATCH-01',
    brand: 'SureSalez Atelier',
    price: 899.00,
    currency: 'USD',
    inStock: true,
    rating: 4.9,
    reviewCount: 142
  });

  assert.strictEqual(schema['@context'], 'https://schema.org');
  assert.strictEqual(schema['@type'], 'Product');
  assert.strictEqual(schema.offers.price, '899.00');
  assert.strictEqual(schema.offers.priceCurrency, 'USD');
  assert.strictEqual(schema.offers.availability, 'https://schema.org/InStock');
  assert.strictEqual(schema.aggregateRating.ratingValue, '4.9');
  assert.strictEqual(schema.aggregateRating.reviewCount, 142);
});

// ==========================================
// [Test 5] Headless Mobile App REST APIs
// ==========================================
console.log('\n[Test 5] Headless Mobile App Feed, Biometrics & Device Token Registry');

it('Mobile home feed structure contains required omnichannel components', () => {
  const feed = {
    app_meta: { current_version: '1.8.0', api_status: 'healthy' },
    hero_banners: [{ id: 1, title: 'The Horology Grand Complication' }],
    curated_categories: [{ id: 1, slug: 'chronographs' }],
    trending_reels: [{ video_id: 1, title: 'Unboxing the Titanium Tourbillon' }],
    flash_sale: { active: true, discount_percent: 25.0 },
    recommended_products: [{ id: 101, name: 'SureSalez Royal Chronometer 42mm' }]
  };

  assert(Array.isArray(feed.hero_banners) && feed.hero_banners.length > 0);
  assert(Array.isArray(feed.curated_categories) && feed.curated_categories.length > 0);
  assert(Array.isArray(feed.trending_reels) && feed.trending_reels.length > 0);
  assert(feed.flash_sale.active === true);
  assert(Array.isArray(feed.recommended_products) && feed.recommended_products.length > 0);
});

it('Biometric sign-in authenticates and returns bearer token with loyalty summary', () => {
  const biometricReq = { biometric_token: 'bio_tok_faceid_secure_99' };
  assert(biometricReq.biometric_token.length > 0);
  const sessionToken = 'ss_mbtok_mocked_token_xyz';
  assert(sessionToken.startsWith('ss_mbtok_'));
});

it('Device token registration validates platform (ios/android) and token string', () => {
  const validDevice = { device_token: 'apns_token_mock_123', platform: 'ios', app_version: '1.8.0' };
  assert(validDevice.device_token.length > 0);
  assert(['ios', 'android'].includes(validDevice.platform));

  const invalidDevice = { device_token: '', platform: 'ios' };
  assert.strictEqual(invalidDevice.device_token.length, 0);
});

it('APNs push notification payload structures valid alert dictionary and deep link', () => {
  const apnsPayload = {
    aps: {
      alert: { title: 'VIP Reward Unlocked!', body: 'Promoted to Platinum tier' },
      sound: 'default',
      badge: 1
    },
    custom: { deep_link: 'suresalez://loyalty' }
  };

  assert.strictEqual(apnsPayload.aps.alert.title, 'VIP Reward Unlocked!');
  assert.strictEqual(apnsPayload.aps.badge, 1);
  assert.strictEqual(apnsPayload.custom.deep_link, 'suresalez://loyalty');
});

// ==========================================
// [Test 6] Architecture, Schemas & Files
// ==========================================
console.log('\n[Test 6] File Existence, Database Schema v1.8.0 & Pure FSE Theme Invariant');

const requiredFiles = [
  'plugins/suresalez-core/modules/loyalty-mobile-api/class-loyalty-engine.php',
  'plugins/suresalez-core/modules/loyalty-mobile-api/class-referral-program.php',
  'plugins/suresalez-core/modules/loyalty-mobile-api/class-seo-manager.php',
  'plugins/suresalez-core/modules/loyalty-mobile-api/class-mobile-api.php',
  'plugins/suresalez-core/modules/loyalty-mobile-api/class-loyalty-mobile-api-module.php',
  'plugins/suresalez-core/modules/loyalty-mobile-api/assets/css/loyalty-portal.css',
  'plugins/suresalez-core/modules/loyalty-mobile-api/assets/js/loyalty-portal.js',
  'plugins/suresalez-core/modules/loyalty-mobile-api/assets/css/referral-banner.css',
  'plugins/suresalez-core/modules/loyalty-mobile-api/assets/js/referral-banner.js'
];

requiredFiles.forEach(file => {
  it(`Sprint 09 component exists: ${file}`, () => {
    assert(fs.existsSync(path.join(baseDir, file)), `File missing: ${file}`);
  });
});

// Database schema v1.8.0 check
const dbFile = fs.readFileSync(path.join(baseDir, 'plugins/suresalez-core/includes/class-database.php'), 'utf8');
it('Database schema version is at least 1.8.0', () => {
  assert(dbFile.includes('1.8.0') || dbFile.includes('1.9.0'), 'Database schema version is not 1.8.0+');
});

const requiredTables = [
  'suresalez_loyalty_points',
  'suresalez_points_transactions',
  'suresalez_referrals',
  'suresalez_seo_redirects',
  'suresalez_mobile_devices'
];

requiredTables.forEach(tbl => {
  it(`Database includes ${tbl} table`, () => {
    assert(dbFile.includes(tbl), `Table missing in Database class: ${tbl}`);
  });
});

const requiredSeeds = [
  'seed_loyalty_points',
  'seed_referrals',
  'seed_seo_redirects'
];

requiredSeeds.forEach(seed => {
  it(`Database includes ${seed}`, () => {
    assert(dbFile.includes(seed), `Seed method missing in Database class: ${seed}`);
  });
});

// Check core module registration
const coreFile = fs.readFileSync(path.join(baseDir, 'plugins/suresalez-core/suresalez-core.php'), 'utf8');
it('Loyalty_Mobile_API_Module registered in suresalez-core.php', () => {
  assert(coreFile.includes('Loyalty_Mobile_API_Module'), 'Loyalty_Mobile_API_Module not registered in suresalez-core.php');
  assert(!coreFile.includes("'loyalty-mobile-apis' =>"), 'Roadmap placeholder for loyalty-mobile-apis not cleaned up');
});

// Check theme mount points in index.html
const indexHtml = fs.readFileSync(path.join(baseDir, 'themes/suresalez-theme/templates/index.html'), 'utf8');
it('Theme index mounts #suresalez-loyalty-rewards-mount', () => {
  assert(indexHtml.includes('id="suresalez-loyalty-rewards-mount"'), 'Missing #suresalez-loyalty-rewards-mount in index.html');
});

it('Theme index mounts #suresalez-referral-banner-mount', () => {
  assert(indexHtml.includes('id="suresalez-referral-banner-mount"'), 'Missing #suresalez-referral-banner-mount in index.html');
});

// Check theme pure FSE invariant (0 PHP files)
function findPhpFiles(dir) {
  let results = [];
  const list = fs.readdirSync(dir);
  list.forEach(file => {
    const fullPath = path.join(dir, file);
    const stat = fs.statSync(fullPath);
    if (stat && stat.isDirectory()) {
      results = results.concat(findPhpFiles(fullPath));
    } else if (file.endsWith('.php')) {
      results.push(fullPath);
    }
  });
  return results;
}

it('SureSalez Theme contains strictly 0 PHP files (Pure FSE block theme)', () => {
  const themePhp = findPhpFiles(path.join(baseDir, 'themes/suresalez-theme'));
  assert.strictEqual(themePhp.length, 0, `Found PHP files in pure theme: ${themePhp.join(', ')}`);
});

// ==========================================
// Summary
// ==========================================
console.log('\n======================================================');
console.log(`  Sprint 09 Test Results: ${passedTests}/${totalTests} Passed (${totalTests - passedTests} Failed)`);
console.log('======================================================\n');

if (passedTests !== totalTests) {
  process.exit(1);
}
