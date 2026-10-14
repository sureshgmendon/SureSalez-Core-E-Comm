const { execSync } = require('child_process');
const path = require('path');
const fs = require('fs');

const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const htmlPath = path.resolve(__dirname, 'browser-test-runner.html');

console.log('Testing Headless Edge DOM rendering against:', htmlPath);

try {
    const cmd = `"${edgePath}" --headless --disable-gpu --dump-dom "file:///${htmlPath.replace(/\\/g, '/')}"`;
    const dom = execSync(cmd, { maxBuffer: 20 * 1024 * 1024, encoding: 'utf8' });

    console.log(`Rendered DOM size: ${(dom.length / 1024).toFixed(1)} KB (${dom.length} characters)`);

    // Verify critical mounts are present in DOM
    const checks = [
        'suresalez-pos-screen-mount',
        'suresalez-vendor-portal-mount',
        'suresalez-subscriptions-mount',
        'suresalez-loyalty-rewards-mount',
        'suresalez-referral-banner-mount',
        'suresalez-organization-switcher-mount',
        'suresalez-tenant-billing-mount',
        'suresalez-b2b-bulk-order-mount',
        'suresalez-rfq-form-mount',
        'suresalez-rma-portal-mount',
        'suresalez-order-tracking-mount',
        'suresalez-shoppable-reels-mount',
        'suresalez-live-chat-mount'
    ];

    let allPassed = true;
    for (const c of checks) {
        if (dom.includes(c)) {
            console.log(`  ✔ Found mount: #${c}`);
        } else {
            console.error(`  ✖ Missing mount: #${c}`);
            allPassed = false;
        }
    }

    if (allPassed) {
        console.log('\n✔ All DOM mount points verified successfully in live headless browser output.');
        process.exit(0);
    } else {
        process.exit(1);
    }
} catch (err) {
    console.error('Browser verification error:', err.message);
    process.exit(1);
}
