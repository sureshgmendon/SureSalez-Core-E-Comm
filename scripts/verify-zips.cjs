const fs = require('fs');
const path = require('path');

function inspectZip(zipFilePath) {
  const buf = fs.readFileSync(zipFilePath);
  // Find EOCD (0x06054b50)
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
  const cdSize = buf.readUInt32LE(eocdOffset + 12);
  const cdOffset = buf.readUInt32LE(eocdOffset + 16);

  const entries = [];
  let pos = cdOffset;
  for (let i = 0; i < totalEntries; i++) {
    const sig = buf.readUInt32LE(pos);
    if (sig !== 0x02014b50) {
      throw new Error(`Invalid central directory signature at ${pos}: 0x${sig.toString(16)}`);
    }
    const nameLen = buf.readUInt16LE(pos + 28);
    const extraLen = buf.readUInt16LE(pos + 30);
    const commentLen = buf.readUInt16LE(pos + 32);
    const entryName = buf.toString('utf8', pos + 46, pos + 46 + nameLen);
    entries.push(entryName);
    pos += 46 + nameLen + extraLen + commentLen;
  }

  return { totalEntries, entries };
}

console.log('=== Inspecting Zip Archives (Pure JS Reader) ===');

const themeZip = path.resolve('SureSalez Core E-Comm/installable-archives/suresalez-theme.zip');
const coreZip = path.resolve('SureSalez Core E-Comm/installable-archives/suresalez-core.zip');

const themeInfo = inspectZip(themeZip);
console.log(`\n✔ Theme Zip (${(fs.statSync(themeZip).size / 1024).toFixed(2)} KB, ${themeInfo.totalEntries} entries):`);
themeInfo.entries.forEach(e => console.log('  - ' + e));

const coreInfo = inspectZip(coreZip);
console.log(`\n✔ Core Plugin Zip (${(fs.statSync(coreZip).size / 1024).toFixed(2)} KB, ${coreInfo.totalEntries} entries):`);
console.log('  First 10 entries:');
coreInfo.entries.slice(0, 10).forEach(e => console.log('  - ' + e));

// Validation checks
const hasThemeStyle = themeInfo.entries.includes('suresalez-theme/style.css');
const hasThemeJson = themeInfo.entries.includes('suresalez-theme/theme.json');
const hasCorePhp = coreInfo.entries.includes('suresalez-core/suresalez-core.php');

console.log('\n--- WordPress Installation Compatibility Checks ---');
console.log('Theme has style.css at root:', hasThemeStyle ? 'PASS ✔' : 'FAIL ❌');
console.log('Theme has theme.json at root:', hasThemeJson ? 'PASS ✔' : 'FAIL ❌');
console.log('Plugin has suresalez-core.php at root:', hasCorePhp ? 'PASS ✔' : 'FAIL ❌');

// Verify all paths use forward slashes
const themeBackslashes = themeInfo.entries.filter(e => e.includes('\\'));
const coreBackslashes = coreInfo.entries.filter(e => e.includes('\\'));
console.log('Theme zip uses 100% forward slashes:', themeBackslashes.length === 0 ? 'PASS ✔' : `FAIL (${themeBackslashes.length} backslashes) ❌`);
console.log('Core zip uses 100% forward slashes:', coreBackslashes.length === 0 ? 'PASS ✔' : `FAIL (${coreBackslashes.length} backslashes) ❌`);

if (hasThemeStyle && hasThemeJson && hasCorePhp && themeBackslashes.length === 0 && coreBackslashes.length === 0) {
  console.log('\n🎉 ALL INSTALLABLE ARCHIVE VALIDATIONS PASSED!');
} else {
  console.error('\n❌ Archive validations failed.');
  process.exit(1);
}
