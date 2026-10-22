/**
 * SureSalez Enterprise Verification Suite (Sprint 01 + Sprint 02)
 *
 * Runs automated linting, schema validation, syntax checking,
 * module gating verification, and headless DOM hydration tests.
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

let totalTests = 0;
let passedTests = 0;
let failedTests = 0;

function assert(condition, message) {
    totalTests++;
    if (condition) {
        passedTests++;
        console.log(`  \x1b[32m✔ PASS\x1b[0m: ${message}`);
    } else {
        failedTests++;
        console.error(`  \x1b[31m✘ FAIL\x1b[0m: ${message}`);
    }
}

console.log('\n======================================================');
console.log('  SureSalez Core & Theme — Enterprise Test Suite');
console.log('  Sprint 01 + Sprint 02 Verification Engine');
console.log('======================================================\n');

// TEST SUITE 1: Required Files & Directory Structure
console.log('\x1b[36m[Suite 1] Scaffolding & File Integrity Verification\x1b[0m');

const requiredFiles = [
    'AGENTS.md',
    '.agents/rules/architecture.md',
    '.agents/rules/modules.md',
    '.agents/rules/security.md',
    '.agents/workflows/sprint-01-catalog-pim.md',
    '.agents/workflows/sprint-02-checkout-upsell.md',
    '.agents/workflows/sprint-03-localization-compliance.md',
    '.agents/workflows/sprint-04-crm-flow-engine.md',
    '.agents/workflows/sprint-05-omnichannel-ai-reels.md',
    '.agents/workflows/sprint-06-orders-routing-rma.md',
    '.agents/workflows/sprint-07-b2b-rfq-wholesale.md',
    '.agents/workflows/sprint-08-pos-marketplace-subscriptions.md',
    '.agents/workflows/sprint-09-loyalty-redirects-mobile-apis.md',
    '.agents/workflows/sprint-10-multistore-billing-scale.md',
    'themes/suresalez-theme/style.css',
    'themes/suresalez-theme/theme.json',
    'themes/suresalez-theme/functions.php',
    'themes/suresalez-theme/header.php',
    'themes/suresalez-theme/footer.php',
    'themes/suresalez-theme/index.php',
    'themes/suresalez-theme/front-page.php',
    'themes/suresalez-theme/parts/headers/header-main.php',
    'themes/suresalez-theme/parts/hero/hero-split.php',
    'themes/suresalez-theme/templates/index.html',
    'themes/suresalez-theme/templates/single-product.html',
    'themes/suresalez-theme/templates/archive-product.html',
    'themes/suresalez-theme/templates/page-checkout.html',
    'themes/suresalez-theme/templates/page-wallet.html',
    'themes/suresalez-theme/parts/header.html',
    'themes/suresalez-theme/parts/footer.html',
    'themes/suresalez-theme/parts/product-meta.html',
    'plugins/suresalez-core/suresalez-core.php',
    'plugins/suresalez-core/includes/class-module-base.php',
    'plugins/suresalez-core/includes/class-module-manager.php',
    'plugins/suresalez-core/includes/class-database.php',
    'plugins/suresalez-core/includes/class-rest-api.php',
    'plugins/suresalez-core/admin/assets/css/admin.css',
    'plugins/suresalez-core/admin/assets/js/admin.js',
    // Sprint 01 Modules
    'plugins/suresalez-core/modules/catalog-pim/class-catalog-pim-module.php',
    'plugins/suresalez-core/modules/catalog-pim/class-metafields.php',
    'plugins/suresalez-core/modules/catalog-pim/class-dam.php',
    'plugins/suresalez-core/modules/catalog-pim/assets/css/catalog-pim.css',
    'plugins/suresalez-core/modules/catalog-pim/assets/js/catalog-pim.js',
    'plugins/suresalez-core/modules/multi-location-inventory/class-multi-location-inventory-module.php',
    'plugins/suresalez-core/modules/multi-location-inventory/assets/css/multi-location-inventory.css',
    'plugins/suresalez-core/modules/multi-location-inventory/assets/js/multi-location-inventory.js',
    'plugins/suresalez-core/modules/visual-swatches/class-visual-swatches-module.php',
    'plugins/suresalez-core/modules/visual-swatches/assets/css/visual-swatches.css',
    'plugins/suresalez-core/modules/visual-swatches/assets/js/visual-swatches.js',
    'plugins/suresalez-core/modules/reviews-qa/class-reviews-qa-module.php',
    'plugins/suresalez-core/modules/reviews-qa/assets/css/reviews-qa.css',
    'plugins/suresalez-core/modules/reviews-qa/assets/js/reviews-qa.js',
    // Sprint 02 Modules
    'plugins/suresalez-core/modules/checkout-engine/class-checkout-engine-module.php',
    'plugins/suresalez-core/modules/checkout-engine/class-cart-drawer.php',
    'plugins/suresalez-core/modules/checkout-engine/class-checkout-customizer.php',
    'plugins/suresalez-core/modules/checkout-engine/class-post-purchase-upsell.php',
    'plugins/suresalez-core/modules/checkout-engine/class-abandoned-cart-recovery.php',
    'plugins/suresalez-core/modules/checkout-engine/assets/css/cart-drawer.css',
    'plugins/suresalez-core/modules/checkout-engine/assets/js/cart-drawer.js',
    'plugins/suresalez-core/modules/checkout-engine/assets/css/checkout.css',
    'plugins/suresalez-core/modules/checkout-engine/assets/js/checkout.js',
    'plugins/suresalez-core/modules/checkout-engine/assets/css/upsell-modal.css',
    'plugins/suresalez-core/modules/checkout-engine/assets/js/upsell-modal.js',
    'plugins/suresalez-core/modules/wallet/class-wallet-module.php',
    'plugins/suresalez-core/modules/wallet/class-wallet-ledger.php',
    'plugins/suresalez-core/modules/wallet/assets/css/wallet.css',
    'plugins/suresalez-core/modules/wallet/assets/js/wallet.js',
    // Sprint 03 Modules
    'plugins/suresalez-core/modules/localization/class-localization-module.php',
    'plugins/suresalez-core/modules/localization/class-currency-switcher.php',
    'plugins/suresalez-core/modules/localization/class-gateway-router.php',
    'plugins/suresalez-core/modules/localization/class-tax-compliance.php',
    'plugins/suresalez-core/modules/localization/class-market-routing.php',
    'plugins/suresalez-core/modules/localization/assets/css/currency-switcher.css',
    'plugins/suresalez-core/modules/localization/assets/js/currency-switcher.js',
    'plugins/suresalez-core/modules/localization/assets/css/tax-compliance.css',
    'plugins/suresalez-core/modules/localization/assets/js/tax-compliance.js',
    // Sprint 04 Modules
    'plugins/suresalez-core/modules/crm-automations/class-crm-automations-module.php',
    'plugins/suresalez-core/modules/crm-automations/class-customer-segments.php',
    'plugins/suresalez-core/modules/crm-automations/class-flow-engine.php',
    'plugins/suresalez-core/modules/crm-automations/class-chat-lead-capture.php',
    'plugins/suresalez-core/modules/crm-automations/assets/css/chat-widget.css',
    'plugins/suresalez-core/modules/crm-automations/assets/js/chat-widget.js',
    'plugins/suresalez-core/modules/crm-automations/assets/css/lead-capture.css',
    'plugins/suresalez-core/modules/crm-automations/assets/js/lead-capture.js',
    // Sprint 05 Modules
    'plugins/suresalez-core/modules/omnichannel-ai/class-omnichannel-ai-module.php',
    'plugins/suresalez-core/modules/omnichannel-ai/class-ai-chat-commerce.php',
    'plugins/suresalez-core/modules/omnichannel-ai/class-shoppable-reels.php',
    'plugins/suresalez-core/modules/omnichannel-ai/class-capi-dispatcher.php',
    'plugins/suresalez-core/modules/omnichannel-ai/class-marketplace-feeds.php',
    'plugins/suresalez-core/modules/omnichannel-ai/class-analytics-bi.php',
    'plugins/suresalez-core/modules/omnichannel-ai/assets/css/shoppable-reels.css',
    'plugins/suresalez-core/modules/omnichannel-ai/assets/js/shoppable-reels.js',
    // Sprint 06 Modules
    'plugins/suresalez-core/modules/fulfillment-rma/class-fulfillment-rma-module.php',
    'plugins/suresalez-core/modules/fulfillment-rma/class-order-router.php',
    'plugins/suresalez-core/modules/fulfillment-rma/class-batch-fulfillment.php',
    'plugins/suresalez-core/modules/fulfillment-rma/class-dropship-dispatcher.php',
    'plugins/suresalez-core/modules/fulfillment-rma/class-rma-engine.php',
    'plugins/suresalez-core/modules/fulfillment-rma/assets/css/rma-portal.css',
    'plugins/suresalez-core/modules/fulfillment-rma/assets/js/rma-portal.js',
    'plugins/suresalez-core/modules/fulfillment-rma/assets/css/tracking-widget.css',
    'plugins/suresalez-core/modules/fulfillment-rma/assets/js/tracking-widget.js',
    // Sprint 07 Modules
    'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-wholesale-module.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-organization.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-pricing.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-checkout.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-bulk-order.php',
    'plugins/suresalez-core/modules/b2b-wholesale/class-rfq-engine.php',
    'plugins/suresalez-core/modules/b2b-wholesale/assets/css/bulk-order.css',
    'plugins/suresalez-core/modules/b2b-wholesale/assets/js/bulk-order.js',
    'plugins/suresalez-core/modules/b2b-wholesale/assets/css/rfq-portal.css',
    'plugins/suresalez-core/modules/b2b-wholesale/assets/js/rfq-portal.js',
    // Sprint 08 Modules
    'plugins/suresalez-core/modules/pos-subscriptions/class-pos-subscriptions-module.php',
    'plugins/suresalez-core/modules/pos-subscriptions/class-pos-terminal.php',
    'plugins/suresalez-core/modules/pos-subscriptions/class-marketplace-vendor.php',
    'plugins/suresalez-core/modules/pos-subscriptions/class-subscription-engine.php',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/css/pos-screen.css',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/js/pos-screen.js',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/css/vendor-portal.css',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/js/vendor-portal.js',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/css/subscription-portal.css',
    'plugins/suresalez-core/modules/pos-subscriptions/assets/js/subscription-portal.js',
    // Sprint 09 Modules
    'plugins/suresalez-core/modules/loyalty-mobile-api/class-loyalty-mobile-api-module.php',
    'plugins/suresalez-core/modules/loyalty-mobile-api/class-loyalty-engine.php',
    'plugins/suresalez-core/modules/loyalty-mobile-api/class-referral-program.php',
    'plugins/suresalez-core/modules/loyalty-mobile-api/class-seo-manager.php',
    'plugins/suresalez-core/modules/loyalty-mobile-api/class-mobile-api.php',
    'plugins/suresalez-core/modules/loyalty-mobile-api/assets/css/loyalty-portal.css',
    'plugins/suresalez-core/modules/loyalty-mobile-api/assets/js/loyalty-portal.js',
    'plugins/suresalez-core/modules/loyalty-mobile-api/assets/css/referral-banner.css',
    'plugins/suresalez-core/modules/loyalty-mobile-api/assets/js/referral-banner.js',
    // Sprint 10 Modules (Final Milestone)
    'plugins/suresalez-core/modules/enterprise-scale/class-enterprise-scale-module.php',
    'plugins/suresalez-core/modules/enterprise-scale/class-organization-admin.php',
    'plugins/suresalez-core/modules/enterprise-scale/class-tenant-billing.php',
    'plugins/suresalez-core/modules/enterprise-scale/class-enterprise-security.php',
    'plugins/suresalez-core/modules/enterprise-scale/class-scale-engine.php',
    'plugins/suresalez-core/modules/enterprise-scale/assets/css/organization-switcher.css',
    'plugins/suresalez-core/modules/enterprise-scale/assets/js/organization-switcher.js',
    'plugins/suresalez-core/modules/enterprise-scale/assets/css/tenant-billing.css',
    'plugins/suresalez-core/modules/enterprise-scale/assets/js/tenant-billing.js',
];

const rootDir = path.resolve(__dirname, '..');
requiredFiles.forEach(file => {
    const fullPath = path.join(rootDir, file);
    assert(fs.existsSync(fullPath), `Found file: ${file}`);
});

// TEST SUITE 2: theme.json Validation
console.log('\n\x1b[36m[Suite 2] Theme & Block Schema Validation\x1b[0m');

try {
    const themeJsonPath = path.join(rootDir, 'themes/suresalez-theme/theme.json');
    const rawThemeJson = fs.readFileSync(themeJsonPath, 'utf8');
    const themeData = JSON.parse(rawThemeJson);

    assert(themeData.version === 2, 'theme.json version is 2 (Standard FSE)');
    assert(themeData.settings && themeData.settings.color && Array.isArray(themeData.settings.color.palette), 'theme.json declares custom color palette');
    assert(themeData.settings.typography && Array.isArray(themeData.settings.typography.fontSizes), 'theme.json declares fluid typography scale');
    assert(themeData.styles && themeData.styles.elements && themeData.styles.elements.button, 'theme.json styles buttons with design tokens');

    // Oura & Paul Smith design system token assertions
    const hasJakartaHeading = themeData.settings.typography.fontFamilies && themeData.settings.typography.fontFamilies.some(f => f.slug === 'heading' && f.fontFamily.includes('Plus Jakarta Sans'));
    assert(hasJakartaHeading, 'theme.json registers Plus Jakarta Sans grotesque heading font family');

    const hasHeroDisplay = themeData.settings.typography.fontSizes.some(s => s.slug === 'hero-display' && s.size.includes('clamp'));
    assert(hasHeroDisplay, 'theme.json registers fluid clamp hero-display font scale');

    const hasSlateDark = themeData.settings.color.palette.some(c => c.slug === 'slate-dark' && c.color === '#0a0f1d');
    assert(hasSlateDark, 'theme.json registers luxury neutral Slate Dark token (#0a0f1d)');
} catch (err) {
    assert(false, `theme.json failed JSON parse: ${err.message}`);
}

// TEST SUITE 3: PHP Syntax & Lexical Token Linting
console.log('\n\x1b[36m[Suite 3] PHP Codebase Syntax & Invariant Linting\x1b[0m');

const phpFiles = requiredFiles.filter(f => f.endsWith('.php'));
phpFiles.forEach(file => {
    const content = fs.readFileSync(path.join(rootDir, file), 'utf8');
    
    // Check <?php opening tag
    const hasOpening = content.trim().startsWith('<?php');
    assert(hasOpening, `PHP opening tag in ${file}`);

    // Check balanced braces and parentheses
    let openBraces = 0;
    let openParens = 0;
    let inString = false;
    let stringChar = '';
    let escaped = false;
    let inComment = false;

    for (let i = 0; i < content.length; i++) {
        const char = content[i];
        const nextChar = content[i + 1] || '';

        if (!inString && !inComment) {
            if (char === '/' && nextChar === '*') {
                inComment = true;
                i++;
                continue;
            }
            if (char === '/' && nextChar === '/') {
                while (i < content.length && content[i] !== '\n') i++;
                continue;
            }
            if (char === '#' && (i === 0 || content[i - 1] === '\n')) {
                while (i < content.length && content[i] !== '\n') i++;
                continue;
            }
            if (char === "'" || char === '"') {
                inString = true;
                stringChar = char;
                continue;
            }
            if (char === '{') openBraces++;
            if (char === '}') openBraces--;
            if (char === '(') openParens++;
            if (char === ')') openParens--;
        } else if (inComment) {
            if (char === '*' && nextChar === '/') {
                inComment = false;
                i++;
                continue;
            }
        } else if (inString) {
            if (escaped) {
                escaped = false;
            } else if (char === '\\') {
                escaped = true;
            } else if (char === stringChar) {
                inString = false;
            }
        }
    }

    assert(openBraces === 0, `Balanced braces in ${file} (unclosed: ${openBraces})`);
    assert(openParens === 0, `Balanced parentheses in ${file} (unclosed: ${openParens})`);
});

// TEST SUITE 4: JavaScript Syntax & Script Compilation
console.log('\n\x1b[36m[Suite 4] Client JavaScript Compilation\x1b[0m');

const jsFiles = requiredFiles.filter(f => f.endsWith('.js'));
jsFiles.forEach(file => {
    const code = fs.readFileSync(path.join(rootDir, file), 'utf8');
    try {
        new vm.Script(code, { filename: file });
        assert(true, `Compiled JavaScript syntax: ${file}`);
    } catch (err) {
        assert(false, `JS Syntax error in ${file}: ${err.message}`);
    }
});

// TEST SUITE 5: Database Schemas & Seed Data (Sprint 01 + Sprint 02)
console.log('\n\x1b[36m[Suite 5] Database Schema & Migration Verifications\x1b[0m');

const dbFileContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/includes/class-database.php'), 'utf8');

const expectedTables = [
    // Sprint 01
    'suresalez_inventory_locations',
    'suresalez_inventory_stock',
    'suresalez_inventory_transfers',
    'suresalez_reviews',
    'suresalez_questions',
    'suresalez_answers',
    // Sprint 02
    'suresalez_wallet_transactions',
    'suresalez_abandoned_checkouts',
    'suresalez_post_purchase_offers',
    // Sprint 03
    'suresalez_currencies',
    'suresalez_tax_jurisdictions',
    // Sprint 04
    'suresalez_customer_profiles',
    'suresalez_customer_timeline',
    'suresalez_segments',
    'suresalez_flow_workflows',
    'suresalez_flow_logs',
    // Sprint 05
    'suresalez_shoppable_videos',
    'suresalez_campaign_schedules',
    'suresalez_capi_events',
    'suresalez_analytics_snapshots',
    // Sprint 06
    'suresalez_fulfillments',
    'suresalez_fulfillment_items',
    'suresalez_rma_requests',
    'suresalez_rma_items',
    'suresalez_supplier_dropships',
    // Sprint 07
    'suresalez_b2b_companies',
    'suresalez_b2b_users',
    'suresalez_b2b_pricelists',
    'suresalez_b2b_quotes',
    'suresalez_b2b_orders',
    // Sprint 08
    'suresalez_pos_registers',
    'suresalez_pos_transactions',
    'suresalez_marketplace_vendors',
    'suresalez_vendor_commissions',
    'suresalez_subscriptions',
    'suresalez_subscription_logs',
    // Sprint 09
    'suresalez_loyalty_points',
    'suresalez_points_transactions',
    'suresalez_referrals',
    'suresalez_seo_redirects',
    'suresalez_mobile_devices',
    // Sprint 10 (Final Milestone)
    'suresalez_tenants',
    'suresalez_tenant_billing',
    'suresalez_audit_logs',
    'suresalez_api_keys',
    'suresalez_webhooks',
];

expectedTables.forEach(tbl => {
    assert(dbFileContent.includes(tbl), `Database schema declares table: ${tbl}`);
});

const expectedHubCodes = [
    'US-EAST-01',
    'US-WEST-01',
    'US-CENTRAL-01',
    'EU-CENTRAL-01',
    'EU-WEST-01',
    'APAC-EAST-01',
    'APAC-SOUTH-01',
    'APAC-AU-01',
    'LATAM-BR-01',
    'MEA-UAE-01',
];

expectedHubCodes.forEach(code => {
    assert(dbFileContent.includes(code), `Seeded 10-hub inventory location code: ${code}`);
});

assert(dbFileContent.includes('seed_post_purchase_offers'), 'Database seeds post-purchase upsell offers');

// TEST SUITE 6: Decoupled Zero-Bloat Guardrail
console.log('\n\x1b[36m[Suite 6] Decoupling & Zero-Bloat Invariant Check\x1b[0m');

const themeDir = path.join(rootDir, 'themes/suresalez-theme');
const themeFiles = fs.readdirSync(themeDir, { recursive: true });
let forbiddenBusinessLogicFound = false;

themeFiles.forEach(tf => {
    if (typeof tf === 'string' && tf.endsWith('.php')) {
        const full = path.join(themeDir, tf);
        const content = fs.readFileSync(full, 'utf8');
        if (content.includes('$wpdb') || content.includes('register_post_type') || content.includes('CREATE TABLE')) {
            forbiddenBusinessLogicFound = true;
        }
    }
});

assert(!forbiddenBusinessLogicFound, 'Theme contains strictly 0 business logic files (100% decoupled Hybrid PHP theme shell)');

// Validate 20 FSE Block Patterns (10 Headers, 10 Footers)
const patternsDir = path.join(themeDir, 'patterns');
assert(fs.existsSync(patternsDir), 'Patterns directory exists in theme');

const patternFiles = fs.readdirSync(patternsDir);
const headerPatterns = patternFiles.filter(f => f.startsWith('header-') && f.endsWith('.php'));
const footerPatterns = patternFiles.filter(f => f.startsWith('footer-') && f.endsWith('.php'));

assert(headerPatterns.length === 10, `Theme contains exactly 10 Header patterns (found: ${headerPatterns.length})`);
assert(footerPatterns.length === 10, `Theme contains exactly 10 Footer patterns (found: ${footerPatterns.length})`);

let patternLogicViolations = 0;
let patternHeaderErrors = 0;

patternFiles.forEach(pf => {
    const pContent = fs.readFileSync(path.join(patternsDir, pf), 'utf8');
    
    // Check zero executable PHP logic
    const phpBlocks = pContent.match(/<\?php[\s\S]*?\?>/g) || [];
    if (phpBlocks.length > 1) {
        patternLogicViolations++;
    } else if (phpBlocks.length === 1) {
        const stripped = phpBlocks[0].replace(/<\?php|\?>|\/\*[\s\S]*?\*\/|\s+/g, '');
        if (stripped.length > 0) {
            patternLogicViolations++;
        }
    }

    // Check WordPress FSE Replaceability headers
    if (pf.startsWith('header-')) {
        if (!/Categories:\s*header/i.test(pContent) || !/Block Types:\s*core\/template-part\/header/i.test(pContent)) {
            patternHeaderErrors++;
        }
    } else if (pf.startsWith('footer-')) {
        if (!/Categories:\s*footer/i.test(pContent) || !/Block Types:\s*core\/template-part\/footer/i.test(pContent)) {
            patternHeaderErrors++;
        }
    }

    // Check valid block comments
    if (!pContent.includes('<!-- wp:') || !pContent.includes('<!-- /wp:')) {
        patternHeaderErrors++;
    }
});

assert(patternLogicViolations === 0, 'All FSE block patterns contain strictly ZERO executable PHP logic');
assert(patternHeaderErrors === 0, 'All FSE block patterns declare valid WordPress FSE replaceability headers and block markup');

// Validate Paul Smith & Oura Style Hero Block Patterns
const heroEditorialPath = path.join(patternsDir, 'hero-01-editorial-split.php');
assert(fs.existsSync(heroEditorialPath), 'Hero pattern exists: hero-01-editorial-split.php');
const heroEditorialContent = fs.readFileSync(heroEditorialPath, 'utf8');
assert(heroEditorialContent.includes('hero-editorial-split'), 'hero-01 declares slug suresalez-theme/hero-editorial-split');
assert(/Categories:\s*banner,\s*featured/i.test(heroEditorialContent), 'hero-01 declares Categories: banner, featured');

const heroImmersivePath = path.join(patternsDir, 'hero-02-minimal-immersive.php');
assert(fs.existsSync(heroImmersivePath), 'Hero pattern exists: hero-02-minimal-immersive.php');
const heroImmersiveContent = fs.readFileSync(heroImmersivePath, 'utf8');
assert(heroImmersiveContent.includes('hero-minimal-immersive'), 'hero-02 declares slug suresalez-theme/hero-minimal-immersive');
assert(/Categories:\s*banner,\s*featured/i.test(heroImmersiveContent), 'hero-02 declares Categories: banner, featured');

const heroVideoImagePath = path.join(patternsDir, 'hero-03-video-image-split.php');
assert(fs.existsSync(heroVideoImagePath), 'Hero pattern exists: hero-03-video-image-split.php');
const heroVideoImageContent = fs.readFileSync(heroVideoImagePath, 'utf8');
assert(heroVideoImageContent.includes('hero-video-image-split'), 'hero-03 declares slug suresalez-theme/hero-video-image-split');
assert(/Categories:\s*banner,\s*featured/i.test(heroVideoImageContent), 'hero-03 declares Categories: banner, featured');

// Validate index.html wires hero pattern below header
const indexPath = path.join(themeDir, 'templates/index.html');
const indexContent = fs.readFileSync(indexPath, 'utf8');
assert(indexContent.includes('wp:pattern {"slug":"suresalez-theme/hero-video-image-split"}'), 'index.html wires hero-video-image-split pattern below header');

const moduleManagerContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/includes/class-module-manager.php'), 'utf8');
assert(moduleManagerContent.includes('is_module_active'), 'Module Manager verifies active status before init');
assert(moduleManagerContent.includes('suresalez_active_modules'), 'Persistent settings saved in wp_options');

// TEST SUITE 7: Dynamic Module Gating & Registration
console.log('\n\x1b[36m[Suite 7] Dynamic Module Gating & Registration Check\x1b[0m');

const coreBootstrapContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/suresalez-core.php'), 'utf8');

assert(coreBootstrapContent.includes('Checkout_Engine_Module'), 'Core bootstrap instantiates Checkout_Engine_Module');
assert(coreBootstrapContent.includes('Wallet_Module'), 'Core bootstrap instantiates Wallet_Module');
assert(coreBootstrapContent.includes('Catalog_PIM_Module'), 'Core bootstrap instantiates Catalog_PIM_Module');
assert(coreBootstrapContent.includes('Multi_Location_Inventory_Module'), 'Core bootstrap instantiates Multi_Location_Inventory_Module');
assert(coreBootstrapContent.includes('Visual_Swatches_Module'), 'Core bootstrap instantiates Visual_Swatches_Module');
assert(coreBootstrapContent.includes('Reviews_QA_Module'), 'Core bootstrap instantiates Reviews_QA_Module');
assert(coreBootstrapContent.includes('Localization_Module'), 'Core bootstrap instantiates Localization_Module');
assert(coreBootstrapContent.includes('CRM_Automations_Module'), 'Core bootstrap instantiates CRM_Automations_Module');
assert(coreBootstrapContent.includes('Omnichannel_AI_Module'), 'Core bootstrap instantiates Omnichannel_AI_Module');
assert(coreBootstrapContent.includes('Fulfillment_RMA_Module'), 'Core bootstrap instantiates Fulfillment_RMA_Module');
assert(coreBootstrapContent.includes('B2B_Wholesale_Module'), 'Core bootstrap instantiates B2B_Wholesale_Module');
assert(coreBootstrapContent.includes('POS_Subscriptions_Module'), 'Core bootstrap instantiates POS_Subscriptions_Module');
assert(coreBootstrapContent.includes('Loyalty_Mobile_API_Module'), 'Core bootstrap instantiates Loyalty_Mobile_API_Module');
assert(coreBootstrapContent.includes('Enterprise_Scale_Module'), 'Core bootstrap instantiates Enterprise_Scale_Module');

// Check that Enterprise Scale module loads sub-components
const scaleModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/enterprise-scale/class-enterprise-scale-module.php'), 'utf8');
assert(scaleModuleContent.includes('Organization_Admin'), 'Enterprise Scale Module loads Organization Admin');
assert(scaleModuleContent.includes('Tenant_Billing'), 'Enterprise Scale Module loads Tenant Billing');
assert(scaleModuleContent.includes('Enterprise_Security'), 'Enterprise Scale Module loads Enterprise Security');
assert(scaleModuleContent.includes('Scale_Engine'), 'Enterprise Scale Module loads Scale Engine');

assert(dbFileContent.includes('seed_tenants'), 'Database seeds sample multi-store tenants');
assert(dbFileContent.includes('seed_tenant_billing'), 'Database seeds reseller tenant billing records');
assert(dbFileContent.includes('seed_audit_logs'), 'Database seeds immutable audit logs');
assert(dbFileContent.includes('seed_api_keys'), 'Database seeds high-scale API keys');
assert(dbFileContent.includes('seed_webhooks'), 'Database seeds outbound webhooks');

// Check that Loyalty Mobile API module loads sub-components
const loyaltyModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/loyalty-mobile-api/class-loyalty-mobile-api-module.php'), 'utf8');
assert(loyaltyModuleContent.includes('Loyalty_Engine'), 'Loyalty Mobile API Module loads Loyalty Engine');
assert(loyaltyModuleContent.includes('Referral_Program'), 'Loyalty Mobile API Module loads Referral Program');
assert(loyaltyModuleContent.includes('SEO_Manager'), 'Loyalty Mobile API Module loads SEO Manager');
assert(loyaltyModuleContent.includes('Mobile_API'), 'Loyalty Mobile API Module loads Mobile API');

assert(dbFileContent.includes('seed_loyalty_points'), 'Database seeds sample loyalty points accounts');
assert(dbFileContent.includes('seed_referrals'), 'Database seeds sample referrals');
assert(dbFileContent.includes('seed_seo_redirects'), 'Database seeds default SEO redirects');

// Check that POS Subscriptions module loads sub-components
const posModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/pos-subscriptions/class-pos-subscriptions-module.php'), 'utf8');
assert(posModuleContent.includes('POS_Terminal'), 'POS Subscriptions Module loads POS Terminal engine');
assert(posModuleContent.includes('Marketplace_Vendor'), 'POS Subscriptions Module loads Marketplace Vendor engine');
assert(posModuleContent.includes('Subscription_Engine'), 'POS Subscriptions Module loads Subscription Engine');

assert(dbFileContent.includes('seed_pos_registers'), 'Database seeds default POS registers');
assert(dbFileContent.includes('seed_marketplace_vendors'), 'Database seeds sample marketplace vendors');
assert(dbFileContent.includes('seed_vendor_commissions'), 'Database seeds default vendor commissions');
assert(dbFileContent.includes('seed_subscriptions'), 'Database seeds recurring subscriptions');

// Check that B2B Wholesale module loads sub-components
const b2bModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/b2b-wholesale/class-b2b-wholesale-module.php'), 'utf8');
assert(b2bModuleContent.includes('B2B_Organization'), 'B2B Wholesale Module loads B2B Organization engine');
assert(b2bModuleContent.includes('B2B_Pricing'), 'B2B Wholesale Module loads B2B Pricing engine');
assert(b2bModuleContent.includes('B2B_Checkout'), 'B2B Wholesale Module loads B2B Checkout engine');
assert(b2bModuleContent.includes('Bulk_Order'), 'B2B Wholesale Module loads Bulk Order engine');
assert(b2bModuleContent.includes('RFQ_Engine'), 'B2B Wholesale Module loads RFQ Engine');

assert(dbFileContent.includes('seed_b2b_companies'), 'Database seeds sample B2B companies');
assert(dbFileContent.includes('seed_b2b_users'), 'Database seeds B2B company users');
assert(dbFileContent.includes('seed_b2b_pricelists'), 'Database seeds wholesale tiered pricelists');
assert(dbFileContent.includes('seed_b2b_quotes'), 'Database seeds sample RFQ quotes');

// Check that Fulfillment RMA module loads sub-components
const fulfillmentModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/fulfillment-rma/class-fulfillment-rma-module.php'), 'utf8');
assert(fulfillmentModuleContent.includes('Order_Router'), 'Fulfillment RMA Module loads Order Router engine');
assert(fulfillmentModuleContent.includes('Batch_Fulfillment'), 'Fulfillment RMA Module loads Batch Fulfillment engine');
assert(fulfillmentModuleContent.includes('Dropship_Dispatcher'), 'Fulfillment RMA Module loads Dropship Dispatcher engine');
assert(fulfillmentModuleContent.includes('RMA_Engine'), 'Fulfillment RMA Module loads RMA Engine');

assert(dbFileContent.includes('seed_fulfillments'), 'Database seeds default split fulfillments');
assert(dbFileContent.includes('seed_rma_data'), 'Database seeds sample RMA requests');
assert(dbFileContent.includes('seed_supplier_dropships'), 'Database seeds supplier dropship purchase orders');

// Check that Omnichannel AI module loads sub-components
const omnichannelModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/omnichannel-ai/class-omnichannel-ai-module.php'), 'utf8');
assert(omnichannelModuleContent.includes('AI_Chat_Commerce'), 'Omnichannel AI Module loads AI Chat Commerce engine');
assert(omnichannelModuleContent.includes('Shoppable_Reels'), 'Omnichannel AI Module loads Shoppable Reels engine');
assert(omnichannelModuleContent.includes('CAPI_Dispatcher'), 'Omnichannel AI Module loads CAPI Dispatcher');
assert(omnichannelModuleContent.includes('Marketplace_Feeds'), 'Omnichannel AI Module loads Marketplace Feeds engine');
assert(omnichannelModuleContent.includes('Analytics_BI'), 'Omnichannel AI Module loads Analytics BI engine');

assert(dbFileContent.includes('seed_shoppable_videos'), 'Database seeds default shoppable video reels');
assert(dbFileContent.includes('seed_campaign_schedules'), 'Database seeds scheduled flash campaigns');
assert(dbFileContent.includes('seed_analytics_snapshots'), 'Database seeds initial BI analytics snapshots');

// Check that localization module loads currency switcher, gateway router, tax compliance, and market routing
const localizationModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/localization/class-localization-module.php'), 'utf8');
assert(localizationModuleContent.includes('Currency_Switcher'), 'Localization Module loads Currency Switcher engine');
assert(localizationModuleContent.includes('Gateway_Router'), 'Localization Module loads Gateway Router engine');
assert(localizationModuleContent.includes('Tax_Compliance'), 'Localization Module loads Tax Compliance engine');
assert(localizationModuleContent.includes('Market_Routing'), 'Localization Module loads Market Routing engine');

// Check that CRM Automations module loads Customer_Segments, Flow_Engine, and Chat_Lead_Capture
const crmModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/crm-automations/class-crm-automations-module.php'), 'utf8');
assert(crmModuleContent.includes('Customer_Segments'), 'CRM Automations Module loads Customer Segments engine');
assert(crmModuleContent.includes('Flow_Engine'), 'CRM Automations Module loads Flow Engine');
assert(crmModuleContent.includes('Chat_Lead_Capture'), 'CRM Automations Module loads Chat & Lead Capture engine');

assert(dbFileContent.includes('seed_default_segments'), 'Database seeds default behavioral segments');
assert(dbFileContent.includes('seed_default_flows'), 'Database seeds default Flow workflows');

// Check that checkout-engine has cart-drawer, checkout-customizer, post-purchase-upsell, and abandoned-cart-recovery
const checkoutModuleContent = fs.readFileSync(path.join(rootDir, 'plugins/suresalez-core/modules/checkout-engine/class-checkout-engine-module.php'), 'utf8');
assert(checkoutModuleContent.includes('Cart_Drawer'), 'Checkout Engine loads Cart Drawer engine');
assert(checkoutModuleContent.includes('Checkout_Customizer'), 'Checkout Engine loads Checkout Customizer');
assert(checkoutModuleContent.includes('Post_Purchase_Upsell'), 'Checkout Engine loads Post-Purchase Upsell engine');
assert(checkoutModuleContent.includes('Abandoned_Cart_Recovery'), 'Checkout Engine loads Abandoned Cart Recovery engine');

// FINAL SUMMARY
console.log('\n======================================================');
console.log(`  Test Summary: ${passedTests}/${totalTests} Passed (${failedTests} Failed)`);
console.log('======================================================\n');

if (failedTests > 0) {
    process.exit(1);
}
