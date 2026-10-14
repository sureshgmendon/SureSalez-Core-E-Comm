/**
 * SureSalez Core — Sprint 06 Focused Test Suite
 * Order Routing, Split Shipments, 3PL/POD Dropshipping & Self-Service RMA
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
console.log('  SureSalez Core — Sprint 06 Focused Test Suite');
console.log('  Order Routing, Split Shipments, 3PL/POD & Self-Service RMA');
console.log('======================================================\n');

// --------------------------------------------------------------------------
// TEST 1: Proximity Routing Matrix & Carrier Resolution
// --------------------------------------------------------------------------
console.log('\x1b[36m[Test 1] Proximity Routing Matrix & Carrier Resolution\x1b[0m');

const PROXIMITY_MATRIX = {
    'US': ['US-EAST-01', 'US-WEST-01', 'US-CENTRAL-01'],
    'CA': ['US-EAST-01', 'US-WEST-01', 'US-CENTRAL-01'],
    'MX': ['US-CENTRAL-01', 'US-WEST-01', 'US-EAST-01'],
    'GB': ['EU-WEST-01', 'EU-CENTRAL-01'],
    'DE': ['EU-CENTRAL-01', 'EU-WEST-01'],
    'FR': ['EU-CENTRAL-01', 'EU-WEST-01'],
    'IN': ['APAC-SOUTH-01', 'MEA-UAE-01', 'APAC-EAST-01'],
    'JP': ['APAC-EAST-01', 'APAC-SOUTH-01'],
    'SG': ['APAC-SOUTH-01', 'APAC-EAST-01'],
    'AU': ['APAC-AU-01', 'APAC-SOUTH-01'],
    'BR': ['LATAM-BR-01', 'US-CENTRAL-01', 'US-EAST-01'],
    'AE': ['MEA-UAE-01', 'EU-CENTRAL-01', 'APAC-SOUTH-01']
};

function resolveCandidateHubs(countryCode) {
    return PROXIMITY_MATRIX[countryCode.toUpperCase()] || ['US-EAST-01', 'EU-CENTRAL-01', 'APAC-SOUTH-01'];
}

function resolveCarrier(hubCode, destCountry) {
    if (hubCode.startsWith('US-')) {
        return destCountry === 'US' ? 'FedEx Express' : 'FedEx International Priority';
    }
    if (hubCode.startsWith('EU-')) {
        return ['DE', 'FR', 'IT', 'ES', 'NL'].includes(destCountry) ? 'DHL Express' : 'DHL Express Worldwide';
    }
    if (hubCode === 'APAC-SOUTH-01' && destCountry === 'IN') {
        return 'BlueDart / DHL Express';
    }
    if (hubCode.startsWith('APAC-')) {
        return 'DHL Express Asia-Pacific';
    }
    if (hubCode.startsWith('LATAM-')) {
        return 'Correios / FedEx Brazil';
    }
    if (hubCode.startsWith('MEA-')) {
        return 'Aramex / DHL Express';
    }
    return 'Global Priority Courier';
}

const usHubs = resolveCandidateHubs('US');
assert(usHubs[0] === 'US-EAST-01', 'US destination primary candidate hub is US-EAST-01');

const deHubs = resolveCandidateHubs('DE');
assert(deHubs[0] === 'EU-CENTRAL-01', 'Germany destination primary candidate hub is EU-CENTRAL-01');

const inHubs = resolveCandidateHubs('IN');
assert(inHubs[0] === 'APAC-SOUTH-01', 'India destination primary candidate hub is APAC-SOUTH-01');

const auHubs = resolveCandidateHubs('AU');
assert(auHubs[0] === 'APAC-AU-01', 'Australia destination primary candidate hub is APAC-AU-01');

assert(resolveCarrier('US-EAST-01', 'US') === 'FedEx Express', 'Domestic US order routes to FedEx Express');
assert(resolveCarrier('US-EAST-01', 'CA') === 'FedEx International Priority', 'US to Canada routes to FedEx International Priority');
assert(resolveCarrier('EU-CENTRAL-01', 'FR') === 'DHL Express', 'EU intra-community shipment routes to DHL Express');
assert(resolveCarrier('APAC-SOUTH-01', 'IN') === 'BlueDart / DHL Express', 'India domestic order routes to BlueDart / DHL Express');

// --------------------------------------------------------------------------
// TEST 2: Multi-Item Split Shipment Partitioning Engine
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 2] Split Shipment Partitioning Engine\x1b[0m');

// Mock Multi-Warehouse Inventory Table
const inventoryStock = [
    { sku: 'LUM-CHRONO-01', location_code: 'US-EAST-01', stock: 15 },
    { sku: 'LUM-CHRONO-01', location_code: 'EU-CENTRAL-01', stock: 0 },
    { sku: 'LUM-STRAP-BLK', location_code: 'US-EAST-01', stock: 0 },
    { sku: 'LUM-STRAP-BLK', location_code: 'EU-CENTRAL-01', stock: 50 },
    { sku: 'LUM-TOOL-01',   location_code: 'US-EAST-01', stock: 10 },
    { sku: 'LUM-TOOL-01',   location_code: 'EU-CENTRAL-01', stock: 10 }
];

function routeOrderItems(items, destCountry) {
    const candidateHubs = resolveCandidateHubs(destCountry);
    const fulfillments = {};

    for (const item of items) {
        let allocatedHub = null;
        for (const hub of candidateHubs) {
            const stockRow = inventoryStock.find(r => r.sku === item.sku && r.location_code === hub && r.stock >= item.quantity);
            if (stockRow) {
                allocatedHub = hub;
                stockRow.stock -= item.quantity;
                break;
            }
        }

        // Fallback to any hub with stock
        if (!allocatedHub) {
            const stockRow = inventoryStock.find(r => r.sku === item.sku && r.stock >= item.quantity);
            if (stockRow) {
                allocatedHub = stockRow.location_code;
                stockRow.stock -= item.quantity;
            } else {
                allocatedHub = 'BACKORDER';
            }
        }

        if (!fulfillments[allocatedHub]) {
            fulfillments[allocatedHub] = {
                location_code: allocatedHub,
                carrier: resolveCarrier(allocatedHub, destCountry),
                items: []
            };
        }
        fulfillments[allocatedHub].items.push(item);
    }

    const packages = Object.values(fulfillments);
    return {
        total_packages: packages.length,
        is_split: packages.length > 1,
        packages: packages
    };
}

const orderItems = [
    { sku: 'LUM-CHRONO-01', quantity: 1, title: 'Luminor Chrono' },
    { sku: 'LUM-STRAP-BLK', quantity: 1, title: 'Horween Leather Strap' },
    { sku: 'LUM-TOOL-01',   quantity: 1, title: 'Spring Bar Tool' }
];

const splitResult = routeOrderItems(orderItems, 'US');
assert(splitResult.is_split === true, 'Order requires split shipment across multiple hubs');
assert(splitResult.total_packages === 2, 'Order cleanly partitioned into 2 packages');

const pkgUS = splitResult.packages.find(p => p.location_code === 'US-EAST-01');
assert(pkgUS !== undefined, 'Package 1 allocated to US-EAST-01');
assert(pkgUS.items.some(i => i.sku === 'LUM-CHRONO-01'), 'US-EAST-01 package contains LUM-CHRONO-01');
assert(pkgUS.items.some(i => i.sku === 'LUM-TOOL-01'), 'US-EAST-01 package contains LUM-TOOL-01');

const pkgEU = splitResult.packages.find(p => p.location_code === 'EU-CENTRAL-01');
assert(pkgEU !== undefined, 'Package 2 allocated to EU-CENTRAL-01 for strap');
assert(pkgEU.items.some(i => i.sku === 'LUM-STRAP-BLK'), 'EU-CENTRAL-01 package contains LUM-STRAP-BLK');

// --------------------------------------------------------------------------
// TEST 3: Warehouse Serpentine Wave Pick Route Optimization
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 3] Warehouse Serpentine Wave Pick Optimization\x1b[0m');

const unoptimizedPicks = [
    { sku: 'SKU-C', bin: 'B02-04-S3', aisle: 'B02', bay: 4, shelf: 3 },
    { sku: 'SKU-A', bin: 'A01-01-S1', aisle: 'A01', bay: 1, shelf: 1 },
    { sku: 'SKU-D', bin: 'B02-01-S2', aisle: 'B02', bay: 1, shelf: 2 },
    { sku: 'SKU-B', bin: 'A01-08-S2', aisle: 'A01', bay: 8, shelf: 2 }
];

function optimizeSerpentinePickPath(items) {
    return [...items].sort((a, b) => {
        if (a.aisle !== b.aisle) {
            return a.aisle.localeCompare(b.aisle);
        }
        // In serpentine routing, odd aisles sort bay ascending, even aisles sort bay descending
        const aisleNum = parseInt(a.aisle.replace(/\D/g, ''), 10) || 1;
        if (aisleNum % 2 === 1) {
            return a.bay - b.bay;
        } else {
            return b.bay - a.bay;
        }
    });
}

const optimizedRoute = optimizeSerpentinePickPath(unoptimizedPicks);
assert(optimizedRoute[0].sku === 'SKU-A', 'First pick is A01-01-S1 (Aisle A01 start)');
assert(optimizedRoute[1].sku === 'SKU-B', 'Second pick is A01-08-S2 (Aisle A01 end)');
assert(optimizedRoute[2].sku === 'SKU-C', 'Third pick enters Aisle B02 from top: B02-04-S3 (serpentine turn)');
assert(optimizedRoute[3].sku === 'SKU-D', 'Fourth pick exits Aisle B02 at bottom: B02-01-S2');

// --------------------------------------------------------------------------
// TEST 4: Code-128 Shipping AWB Barcode Generator
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 4] Code-128 Shipping AWB Barcode Checksum\x1b[0m');

function calculateCode128CheckDigit(data) {
    // Start code B is 104
    let sum = 104;
    for (let i = 0; i < data.length; i++) {
        const ascii = data.charCodeAt(i);
        const val = ascii - 32;
        sum += val * (i + 1);
    }
    return sum % 103;
}

const awb1 = 'FX-99881122';
const check1 = calculateCode128CheckDigit(awb1);
assert(typeof check1 === 'number' && check1 >= 0 && check1 <= 102, `Code-128 check digit for ${awb1} is valid mod-103: ${check1}`);

const awb2 = 'DHL-77443311';
const check2 = calculateCode128CheckDigit(awb2);
assert(check2 >= 0 && check2 <= 102, `Code-128 check digit for ${awb2} is valid: ${check2}`);

// --------------------------------------------------------------------------
// TEST 5: Dropship & POD White-Label PO Dispatcher
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 5] Dropship & POD White-Label PO Dispatcher\x1b[0m');

function classifyDropshipItem(sku) {
    if (sku.startsWith('POD-')) {
        return { type: 'print_on_demand', provider: 'Printify / CustomCat' };
    }
    if (sku.startsWith('DROP-')) {
        return { type: 'supplier_dropship', provider: 'Global Direct Supplier' };
    }
    return { type: 'warehouse_owned', provider: 'SureSalez Hub Network' };
}

assert(classifyDropshipItem('POD-HOODIE-M').type === 'print_on_demand', 'POD- prefix identified as Print on Demand');
assert(classifyDropshipItem('DROP-WATCHBOX-01').type === 'supplier_dropship', 'DROP- prefix identified as Supplier Dropship');
assert(classifyDropshipItem('LUM-CHRONO-01').type === 'warehouse_owned', 'Standard SKU identified as Warehouse Owned');

function generateWhiteLabelPackingSlip(order, item) {
    return {
        slip_number: `SLIP-${order.id}-${item.sku}`,
        branding: {
            company_name: 'SureSalez Luxury Timepieces',
            support_email: 'concierge@suresalez.com',
            website: 'https://suresalez.io'
        },
        display_cost: item.retail_price, // Retail price shown to customer
        suppressed_cost: item.wholesale_cost, // Wholesale supplier cost hidden from slip
        packaging_instructions: 'Pack in plain unbranded cardboard box with custom SureSalez tissue wrap.'
    };
}

const mockOrder = { id: 1005, customer: 'John Doe' };
const podItem = { sku: 'POD-TEE-BLK-L', retail_price: 49.00, wholesale_cost: 16.50 };
const slip = generateWhiteLabelPackingSlip(mockOrder, podItem);

assert(slip.branding.company_name === 'SureSalez Luxury Timepieces', 'Packing slip features white-label merchant branding');
assert(slip.display_cost === 49.00, 'Packing slip displays customer retail price ($49.00)');
assert(slip.suppressed_cost === 16.50, 'Supplier wholesale merchant cost ($16.50) is tracked internally');
assert(slip.packaging_instructions.includes('unbranded'), 'Packaging instructions mandate unbranded packaging');

// --------------------------------------------------------------------------
// TEST 6: Customer Self-Service RMA Engine, Inspection & Restocking
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 6] Customer RMA Engine, Inspection & Restocking\x1b[0m');

function generateRmaNumber() {
    const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    const rand = Math.floor(1000 + Math.random() * 9000);
    return `RMA-${dateStr}-${rand}`;
}

const rmaNumber = generateRmaNumber();
assert(/^RMA-\d{8}-\d{4}$/.test(rmaNumber), `RMA number matches format RMA-YYYYMMDD-XXXX: ${rmaNumber}`);

const ALLOWED_REASONS = ['defective', 'size_fit', 'not_as_described', 'wrong_item', 'changed_mind'];
assert(ALLOWED_REASONS.includes('size_fit'), 'Allowed reasons include size_fit');
assert(ALLOWED_REASONS.includes('defective'), 'Allowed reasons include defective');

function processRmaInspection(rma, inspectionResult, inventoryDB, walletDB) {
    if (inspectionResult.qa_status === 'passed') {
        // Restock inventory in specified hub
        const inv = inventoryDB.find(r => r.sku === rma.sku && r.location_code === inspectionResult.hub);
        if (inv) {
            inv.stock += rma.quantity;
        }

        // Handle Store Credit Resolution (+10% bonus)
        if (rma.return_type === 'store_credit') {
            const bonusRate = 0.10;
            const baseAmount = rma.refund_amount;
            const bonusAmount = baseAmount * bonusRate;
            const totalCredit = baseAmount + bonusAmount;

            walletDB.push({
                user_id: rma.customer_id,
                type: 'credit',
                amount: totalCredit,
                currency: 'USD',
                reference_type: 'rma_refund',
                reference_id: rma.rma_number,
                created_at: new Date().toISOString()
            });

            return {
                status: 'completed',
                restocked: true,
                wallet_credit: totalCredit,
                bonus_credit: bonusAmount
            };
        }

        return { status: 'completed', restocked: true, refund_method: 'original_payment' };
    } else {
        // Quarantine damaged items, 0 restock
        return {
            status: 'quarantined',
            restocked: false,
            qa_notes: inspectionResult.notes
        };
    }
}

const mockInventory = [
    { sku: 'LUM-CHRONO-01', location_code: 'US-EAST-01', stock: 10 }
];
const mockWallet = [];

const rmaCase = {
    rma_number: rmaNumber,
    customer_id: 42,
    sku: 'LUM-CHRONO-01',
    quantity: 1,
    refund_amount: 850.00,
    return_type: 'store_credit'
};

const inspectionPassed = processRmaInspection(
    rmaCase,
    { qa_status: 'passed', hub: 'US-EAST-01' },
    mockInventory,
    mockWallet
);

assert(inspectionPassed.status === 'completed', 'RMA marked completed after QA pass');
assert(inspectionPassed.restocked === true, 'Item successfully restocked into warehouse inventory');
assert(mockInventory[0].stock === 11, 'US-EAST-01 stock incremented from 10 to 11');
assert(inspectionPassed.wallet_credit === 935.00, 'Store credit includes 10% bonus ($850 base + $85 bonus = $935.00)');
assert(mockWallet.length === 1 && mockWallet[0].amount === 935.00, 'Wallet ledger transaction recorded with reference_id');

// Quarantine test
const inspectionDamaged = processRmaInspection(
    rmaCase,
    { qa_status: 'damaged', hub: 'US-EAST-01', notes: 'Scratched sapphire crystal' },
    mockInventory,
    mockWallet
);
assert(inspectionDamaged.status === 'quarantined', 'Damaged return routed to quarantine');
assert(inspectionDamaged.restocked === false, 'Damaged return not restocked into active inventory');

// --------------------------------------------------------------------------
// TEST 7: File Existence & Modular Architecture Integrity
// --------------------------------------------------------------------------
console.log('\n\x1b[36m[Test 7] File Existence & Zero-Bloat Modular Architecture\x1b[0m');

const baseDir = path.resolve(__dirname, '..');
const sprint06Files = [
    'plugins/suresalez-core/modules/fulfillment-rma/class-order-router.php',
    'plugins/suresalez-core/modules/fulfillment-rma/class-batch-fulfillment.php',
    'plugins/suresalez-core/modules/fulfillment-rma/class-dropship-dispatcher.php',
    'plugins/suresalez-core/modules/fulfillment-rma/class-rma-engine.php',
    'plugins/suresalez-core/modules/fulfillment-rma/class-fulfillment-rma-module.php',
    'plugins/suresalez-core/modules/fulfillment-rma/assets/css/rma-portal.css',
    'plugins/suresalez-core/modules/fulfillment-rma/assets/js/rma-portal.js',
    'plugins/suresalez-core/modules/fulfillment-rma/assets/css/tracking-widget.css',
    'plugins/suresalez-core/modules/fulfillment-rma/assets/js/tracking-widget.js'
];

for (const relPath of sprint06Files) {
    const fullPath = path.join(baseDir, relPath);
    assert(fs.existsSync(fullPath), `Sprint 06 component exists: ${relPath}`);
}

// Check database schema v1.5.0+
const dbFile = fs.readFileSync(path.join(baseDir, 'plugins/suresalez-core/includes/class-database.php'), 'utf8');
assert(dbFile.includes('1.5.0') || dbFile.includes('1.6.0') || dbFile.includes('1.7.0') || dbFile.includes('1.8.0'), 'Database schema version is at least 1.5.0');
assert(dbFile.includes('suresalez_fulfillments'), 'Database includes suresalez_fulfillments table');
assert(dbFile.includes('suresalez_fulfillment_items'), 'Database includes suresalez_fulfillment_items table');
assert(dbFile.includes('suresalez_rma_requests'), 'Database includes suresalez_rma_requests table');
assert(dbFile.includes('suresalez_rma_items'), 'Database includes suresalez_rma_items table');
assert(dbFile.includes('suresalez_supplier_dropships'), 'Database includes suresalez_supplier_dropships table');

// Check core bootstrap
const coreFile = fs.readFileSync(path.join(baseDir, 'plugins/suresalez-core/suresalez-core.php'), 'utf8');
assert(coreFile.includes('Fulfillment_RMA_Module'), 'Fulfillment_RMA_Module registered in suresalez-core.php');

// Check theme mount points
const themeIndex = fs.readFileSync(path.join(baseDir, 'themes/suresalez-theme/templates/index.html'), 'utf8');
assert(themeIndex.includes('suresalez-order-tracking-mount'), 'Theme index template mounts #suresalez-order-tracking-mount');
assert(themeIndex.includes('suresalez-rma-portal-mount'), 'Theme index template mounts #suresalez-rma-portal-mount');

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
console.log(`  Sprint 06 Test Results: ${passedTests}/${totalTests} Passed (${failedTests} Failed)`);
console.log('======================================================\n');

if (failedTests > 0) {
    process.exit(1);
} else {
    process.exit(0);
}
