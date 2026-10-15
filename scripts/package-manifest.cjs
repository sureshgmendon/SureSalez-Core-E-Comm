const fs = require('fs');
const path = require('path');

function getAllFiles(dirPath, arrayOfFiles) {
  const files = fs.readdirSync(dirPath);
  arrayOfFiles = arrayOfFiles || [];
  files.forEach(function(file) {
    const full = path.join(dirPath, file);
    if (fs.statSync(full).isDirectory()) {
      arrayOfFiles = getAllFiles(full, arrayOfFiles);
    } else {
      arrayOfFiles.push(full);
    }
  });
  return arrayOfFiles;
}

const allFiles = getAllFiles('SureSalez Core E-Comm');
console.log('================================================================================');
console.log('            SURESALEZ CORE E-COMM — COMMERCIAL RELEASE PACKAGE MANIFEST          ');
console.log('================================================================================');
let totalBytes = 0;
allFiles.forEach(file => {
  const stat = fs.statSync(file);
  totalBytes += stat.size;
  const rel = path.relative('.', file).replace(/\\/g, '/');
  console.log(`  ${(stat.size / 1024).toFixed(2).padStart(8)} KB  |  ${rel}`);
});
console.log('--------------------------------------------------------------------------------');
console.log(`  TOTAL: ${(totalBytes / 1024).toFixed(2)} KB across ${allFiles.length} files.`);
console.log('================================================================================\n');
