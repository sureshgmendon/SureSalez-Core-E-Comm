/**
 * SureSalez Catalog PIM & DAM Interactive Client Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Retrieve Data from localized global or embedded script tag
    let pimData = null;
    if (typeof SureSalezCatalogPIMData !== 'undefined') {
        pimData = SureSalezCatalogPIMData;
    } else {
        const embeddedEl = document.getElementById('suresalez-pim-data-json');
        if (embeddedEl) {
            try {
                pimData = JSON.parse(embeddedEl.textContent);
            } catch (e) {
                console.error('Error parsing SureSalez PIM JSON:', e);
            }
        }
    }

    if (!pimData) {
        return;
    }

    // 2. Hydrate PIM Specifications Table
    const specsMount = document.getElementById('suresalez-pim-specs-mount');
    if (specsMount && pimData.metafields) {
        renderSpecsTable(specsMount, pimData.metafields);
    }

    // 3. Hydrate DAM 360 Spin Viewer & Datasheets
    const damMount = document.getElementById('suresalez-dam-gallery-mount');
    if (damMount && pimData.dam) {
        renderDamSection(damMount, pimData.dam);
    }
});

function renderSpecsTable(container, meta) {
    const fieldsToDisplay = [
        { label: 'Brand', val: meta.suresalez_brand || meta.brand },
        { label: 'MPN', val: meta.suresalez_mpn || meta.mpn },
        { label: 'EAN-13 Barcode', val: meta.suresalez_ean || meta.ean },
        { label: 'UPC Barcode', val: meta.suresalez_upc || meta.upc },
        { label: 'GTIN', val: meta.suresalez_gtin || meta.gtin },
        { label: 'HS Tariff Code', val: meta.suresalez_hs_code || meta.hs_code },
        { label: 'Material', val: meta.suresalez_material || meta.material },
        { label: 'Dimensions', val: meta.suresalez_dimensions || meta.dimensions },
        { label: 'Origin', val: meta.suresalez_country_origin || meta.country_origin },
        { label: 'Warranty', val: meta.suresalez_warranty || meta.warranty },
    ];

    // Append JSON specs if any
    if (meta.specs && typeof meta.specs === 'object') {
        Object.entries(meta.specs).forEach(([k, v]) => {
            fieldsToDisplay.push({ label: k, val: v });
        });
    }

    const validRows = fieldsToDisplay.filter(item => item.val);
    if (!validRows.length) {
        return;
    }

    let rowsHtml = '';
    validRows.forEach(row => {
        rowsHtml += `
            <tr>
                <th>${escapeHtml(row.label)}</th>
                <td>${escapeHtml(String(row.val))}</td>
            </tr>
        `;
    });

    container.innerHTML = `
        <div class="suresalez-pim-specs-card">
            <h3>Technical Specifications & Standards</h3>
            <table class="suresalez-specs-table">
                <tbody>${rowsHtml}</tbody>
            </table>
        </div>
    `;
}

function renderDamSection(container, dam) {
    let html = '';

    // 360 Viewer
    if (dam.images_360 && Array.isArray(dam.images_360) && dam.images_360.length > 0) {
        html += `
            <div class="suresalez-360-viewer" id="suresalez-spin-box" data-frames='${JSON.stringify(dam.images_360)}'>
                <div class="suresalez-360-badge">
                    <span>↻</span> 360° Interactive Spin
                </div>
                <img src="${escapeHtml(dam.images_360[0])}" alt="360 View" class="suresalez-360-image" id="suresalez-spin-img">
            </div>
            <p class="suresalez-360-instructions">← Drag or swipe horizontally to rotate product 360° →</p>
        `;
    }

    // Datasheets
    if (dam.datasheets && Array.isArray(dam.datasheets) && dam.datasheets.length > 0) {
        html += `
            <div class="suresalez-datasheets-list">
                <p style="font-size: 13px; font-weight: 700; margin: 8px 0 4px 0;">Engineering Documents & Certifications:</p>
        `;
        dam.datasheets.forEach(doc => {
            html += `
                <a href="${escapeHtml(doc.url)}" target="_blank" rel="noopener noreferrer" class="suresalez-datasheet-item">
                    <span class="doc-title">
                        📄 ${escapeHtml(doc.title || 'Technical Datasheet')}
                    </span>
                    <span class="doc-meta">${escapeHtml(doc.type || 'PDF')} · ${escapeHtml(doc.size || 'Download')}</span>
                </a>
            `;
        });
        html += `</div>`;
    }

    if (html) {
        container.innerHTML = html;
        init360Viewer();
    }
}

function init360Viewer() {
    const viewer = document.getElementById('suresalez-spin-box');
    const img = document.getElementById('suresalez-spin-img');
    if (!viewer || !img) return;

    let frames = [];
    try {
        frames = JSON.parse(viewer.getAttribute('data-frames') || '[]');
    } catch (e) {
        return;
    }

    if (!frames.length) return;

    let currentIndex = 0;
    let isDragging = false;
    let startX = 0;
    const sensitivity = 8; // pixels per frame

    // Preload images for buttery smooth rotation
    frames.forEach(src => {
        const preloadImg = new Image();
        preloadImg.src = src;
    });

    const onPointerDown = (e) => {
        isDragging = true;
        startX = e.clientX || (e.touches && e.touches[0].clientX) || 0;
        viewer.classList.add('is-dragging');
    };

    const onPointerMove = (e) => {
        if (!isDragging) return;
        const currentX = e.clientX || (e.touches && e.touches[0].clientX) || 0;
        const deltaX = currentX - startX;

        if (Math.abs(deltaX) >= sensitivity) {
            const steps = Math.floor(deltaX / sensitivity);
            currentIndex = (currentIndex - steps) % frames.length;
            if (currentIndex < 0) {
                currentIndex += frames.length;
            }
            img.src = frames[currentIndex];
            startX = currentX;
        }
    };

    const onPointerUp = () => {
        isDragging = false;
        viewer.classList.remove('is-dragging');
    };

    viewer.addEventListener('mousedown', onPointerDown);
    window.addEventListener('mousemove', onPointerMove);
    window.addEventListener('mouseup', onPointerUp);

    viewer.addEventListener('touchstart', onPointerDown, { passive: true });
    window.addEventListener('touchmove', onPointerMove, { passive: true });
    window.addEventListener('touchend', onPointerUp);
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
