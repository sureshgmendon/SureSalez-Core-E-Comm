/**
 * SureSalez Multi-Location Inventory Client Hydrator
 */

document.addEventListener('DOMContentLoaded', () => {
    let inventoryData = null;

    if (typeof SureSalezInventoryData !== 'undefined') {
        inventoryData = SureSalezInventoryData.stockData;
    } else {
        const el = document.getElementById('suresalez-inventory-data-json');
        if (el) {
            try {
                inventoryData = JSON.parse(el.textContent);
            } catch (e) {
                console.error('Error parsing SureSalez Inventory JSON:', e);
            }
        }
    }

    if (!inventoryData) {
        return;
    }

    // Mount to main slot or any data-slot
    const mounts = [
        document.getElementById('suresalez-inventory-locations-mount'),
        document.querySelector('[data-suresalez-slot="multi-location-stock"]')
    ].filter(Boolean);

    if (!mounts.length) return;

    mounts.forEach(mount => {
        renderInventoryBadge(mount, inventoryData);
    });
});

function renderInventoryBadge(container, data) {
    const totalAvail = data.total_available || 0;
    const isInStock = totalAvail > 0;
    const locations = data.locations || [];
    const activeHubsWithStock = locations.filter(l => l.available > 0);

    let hubsListHtml = '';
    locations.forEach(loc => {
        const isAvail = loc.available > 0;
        hubsListHtml += `
            <div class="suresalez-hub-pill ${isAvail ? 'avail' : 'empty'}">
                <span class="hub-name" title="${escapeHtml(loc.name)}">${escapeHtml(loc.city || loc.code)}</span>
                <span class="hub-qty">${loc.available} ${loc.available === 1 ? 'unit' : 'units'}</span>
            </div>
        `;
    });

    container.innerHTML = `
        <div class="suresalez-stock-badge-card">
            <div class="suresalez-stock-header">
                <div class="suresalez-stock-status-pill ${isInStock ? 'in-stock' : 'out-of-stock'}">
                    <span class="suresalez-pulse-dot"></span>
                    <span>${isInStock ? `In Stock (${totalAvail} units across ${activeHubsWithStock.length} fulfillment hubs)` : 'Currently Out of Stock across all 10 Hubs'}</span>
                </div>
                <button type="button" class="suresalez-stock-toggle-btn" aria-expanded="false">
                    View 10 Hubs ▾
                </button>
            </div>
            <div class="suresalez-stock-breakdown hidden">
                ${hubsListHtml}
            </div>
        </div>
    `;

    const toggleBtn = container.querySelector('.suresalez-stock-toggle-btn');
    const header = container.querySelector('.suresalez-stock-header');
    const breakdown = container.querySelector('.suresalez-stock-breakdown');

    const toggleBreakdown = () => {
        const isHidden = breakdown.classList.contains('hidden');
        if (isHidden) {
            breakdown.classList.remove('hidden');
            toggleBtn.textContent = 'Hide Hubs ▴';
            toggleBtn.setAttribute('aria-expanded', 'true');
        } else {
            breakdown.classList.add('hidden');
            toggleBtn.textContent = 'View 10 Hubs ▾';
            toggleBtn.setAttribute('aria-expanded', 'false');
        }
    };

    header.addEventListener('click', toggleBreakdown);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
