const fs = require('fs');
const path = require('path');

console.log('===========================================================');
console.log('  SureSalez Core E-Comm — Commercial Packaging Verification');
console.log('===========================================================\n');

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
    console.error(`  ❌ FAIL: ${message}`);
  }
}

const packageRoot = path.resolve('SureSalez Core E-Comm');

// 1. Structure Verification
console.log('[Test 1] Package Directory & Resource Existence');
const expectedFiles = [
  'installable-archives/suresalez-theme.zip',
  'installable-archives/suresalez-core.zip',
  'documentation/index.html',
  'documentation/README.md',
  'documentation/assets/docs.css',
  'documentation/assets/docs.js',
  'licensing/LICENSE.txt',
  'licensing/ENVATO-LICENSE-TERMS.txt',
  'sample-data/demo-products.json',
  'sample-data/demo-theme-settings.json',
  'sample-data/demo-suresalez-modules.json',
  'README_FIRST.txt'
];

for (const rel of expectedFiles) {
  const fullPath = path.join(packageRoot, rel);
  assert(fs.existsSync(fullPath), `Found package asset: ${rel}`);
}

// 2. Pure JS Zip Header & Entry Structure Verification
console.log('\n[Test 2] Zip Archive Header & WordPress Structure Compatibility');

function inspectZipEntries(zipFilePath) {
  const buf = fs.readFileSync(zipFilePath);
  let eocdOffset = -1;
  for (let i = buf.length - 22; i >= 0; i--) {
    if (buf.readUInt32LE(i) === 0x06054b50) {
      eocdOffset = i;
      break;
    }
  }
  if (eocdOffset === -1) {
    throw new Error('Not a valid zip archive (EOCD not found): ' + zipFilePath);
  }

  const totalEntries = buf.readUInt16LE(eocdOffset + 10);
  const cdOffset = buf.readUInt32LE(eocdOffset + 16);

  const entries = [];
  let pos = cdOffset;
  for (let i = 0; i < totalEntries; i++) {
    const sig = buf.readUInt32LE(pos);
    if (sig !== 0x02014b50) {
      throw new Error(`Invalid central directory signature at ${pos}`);
    }
    const nameLen = buf.readUInt16LE(pos + 28);
    const extraLen = buf.readUInt16LE(pos + 30);
    const commentLen = buf.readUInt16LE(pos + 32);
    const entryName = buf.toString('utf8', pos + 46, pos + 46 + nameLen);
    entries.push(entryName);
    pos += 46 + nameLen + extraLen + commentLen;
  }
  return { size: buf.length, totalEntries, entries };
}

const themeZipPath = path.join(packageRoot, 'installable-archives/suresalez-theme.zip');
const coreZipPath = path.join(packageRoot, 'installable-archives/suresalez-core.zip');

const themeZip = inspectZipEntries(themeZipPath);
const coreZip = inspectZipEntries(coreZipPath);

assert(themeZip.size > 2000, `suresalez-theme.zip is healthy (${(themeZip.size / 1024).toFixed(2)} KB)`);
assert(themeZip.entries.includes('suresalez-theme/style.css'), 'suresalez-theme.zip contains suresalez-theme/style.css at root');
assert(themeZip.entries.includes('suresalez-theme/theme.json'), 'suresalez-theme.zip contains suresalez-theme/theme.json');
assert(themeZip.entries.every(e => !e.includes('\\')), 'suresalez-theme.zip uses standard Unix forward slashes');

assert(coreZip.size > 50000, `suresalez-core.zip is healthy (${(coreZip.size / 1024).toFixed(2)} KB)`);
assert(coreZip.entries.includes('suresalez-core/suresalez-core.php'), 'suresalez-core.zip contains suresalez-core/suresalez-core.php bootstrap');
assert(coreZip.entries.some(e => e.startsWith('suresalez-core/modules/')), 'suresalez-core.zip packages all modular engines');
assert(coreZip.entries.every(e => !e.includes('\\')), 'suresalez-core.zip uses standard Unix forward slashes');

// 3. Documentation HTML & Assets Verification
console.log('\n[Test 3] Offline Documentation Integrity');
const htmlPath = path.join(packageRoot, 'documentation/index.html');
const htmlContent = fs.readFileSync(htmlPath, 'utf8');

assert(htmlContent.includes('<link rel="stylesheet" href="assets/docs.css">'), 'Documentation references local assets/docs.css');
assert(htmlContent.includes('<script src="assets/docs.js"></script>'), 'Documentation references local assets/docs.js');
assert(htmlContent.includes('System Requirements'), 'Documentation includes System Requirements chapter');
assert(htmlContent.includes('Installation Guide'), 'Documentation includes Installation Guide chapter');
assert(htmlContent.includes('SureSalez Module Manager Guide'), 'Documentation includes Module Manager chapter');
assert(htmlContent.includes('HSN Code 7117'), 'Documentation includes Indian GST HSN 7117 compliance');
assert(htmlContent.includes('B2B Wholesale Commerce'), 'Documentation includes B2B Wholesale & RFQ chapter');
assert(htmlContent.includes('In-Store Touch POS'), 'Documentation includes Touch POS & Subscriptions chapter');
assert(htmlContent.includes('Troubleshooting & Frequently Asked Questions'), 'Documentation includes Troubleshooting chapter');

// 4. Sample Data JSON Validation
console.log('\n[Test 4] Sample Demo Data JSON Validation');
const productsJsonPath = path.join(packageRoot, 'sample-data/demo-products.json');
const productsData = JSON.parse(fs.readFileSync(productsJsonPath, 'utf8'));

assert(Array.isArray(productsData.products), 'demo-products.json has products array');
assert(productsData.products.length === 50, `demo-products.json contains exactly 50 SKUs (got: ${productsData.products.length})`);

const foundCategories = new Set(productsData.products.map(p => p.category));
assert(foundCategories.has('Chains'), 'Sample data includes Chains category');
assert(foundCategories.has('Earrings'), 'Sample data includes Earrings category');
assert(foundCategories.has('Bangles/Bracelets'), 'Sample data includes Bangles/Bracelets category');
assert(foundCategories.has('Pendants'), 'Sample data includes Pendants category');

const allHaveHsn = productsData.products.every(p => p.hsn_code === '7117');
assert(allHaveHsn, 'All 50 products specify HSN Code 7117');

const allHaveDualCurrency = productsData.products.every(p => p.pricing && p.pricing.USD && p.pricing.INR);
assert(allHaveDualCurrency, 'All 50 products have dual USD and INR pricing');

const allHaveInventory = productsData.products.every(p => p.stock_by_hub && p.stock_by_hub['us-flagship'] && p.stock_by_hub['in-atelier']);
assert(allHaveInventory, 'All 50 products have inventory allocated across global hubs');

const themeSettingsJsonPath = path.join(packageRoot, 'sample-data/demo-theme-settings.json');
const themeSettings = JSON.parse(fs.readFileSync(themeSettingsJsonPath, 'utf8'));
assert(themeSettings.settings && themeSettings.settings.color && themeSettings.settings.typography, 'demo-theme-settings.json contains valid FSE tokens');

const modulesJsonPath = path.join(packageRoot, 'sample-data/demo-suresalez-modules.json');
const modulesData = JSON.parse(fs.readFileSync(modulesJsonPath, 'utf8'));
assert(modulesData.modules && modulesData.modules['b2b-wholesale'] && modulesData.modules['pos-subscriptions'], 'demo-suresalez-modules.json contains complete module roster');

// 5. Licensing & Buyer Orientation Validation
console.log('\n[Test 5] Licensing & Buyer Orientation Guide Validation');
const licenseGpl = fs.readFileSync(path.join(packageRoot, 'licensing/LICENSE.txt'), 'utf8');
assert(licenseGpl.includes('GNU GENERAL PUBLIC LICENSE') && licenseGpl.includes('Version 2'), 'LICENSE.txt contains valid GPL v2 text');

const licenseEnvato = fs.readFileSync(path.join(packageRoot, 'licensing/ENVATO-LICENSE-TERMS.txt'), 'utf8');
assert(licenseEnvato.includes('REGULAR LICENSE') && licenseEnvato.includes('EXTENDED LICENSE'), 'ENVATO-LICENSE-TERMS.txt contains Regular and Extended commercial terms');

const readmeFirst = fs.readFileSync(path.join(packageRoot, 'README_FIRST.txt'), 'utf8');
assert(readmeFirst.includes('QUICK 5-MINUTE INSTALLATION') && readmeFirst.includes('PACKAGE CONTENTS BREAKDOWN'), 'README_FIRST.txt contains clear buyer orientation steps');

// 6. Summary Table
console.log('\n===========================================================');
console.log(`  Package Verification Summary: ${passedTests}/${totalTests} Passed (${failedTests} Failed)`);
console.log('===========================================================\n');

if (failedTests > 0) {
  process.exit(1);
}
