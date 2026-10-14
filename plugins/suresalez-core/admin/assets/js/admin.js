/**
 * SureSalez Admin Module Manager Interactive JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    const toggles = document.querySelectorAll('.suresalez-toggle-input');
    if (!toggles.length) {
        return;
    }

    toggles.forEach(toggle => {
        toggle.addEventListener('change', async (e) => {
            const input = e.target;
            const moduleId = input.getAttribute('data-module-id');
            const isChecked = input.checked;
            const card = input.closest('.suresalez-module-card');
            const badge = card ? card.querySelector('.runtime-badge') : null;

            // Immediate visual feedback
            if (card) {
                if (isChecked) {
                    card.classList.remove('inactive');
                    card.classList.add('active');
                } else {
                    card.classList.remove('active');
                    card.classList.add('inactive');
                }
            }

            if (badge) {
                badge.textContent = isChecked ? 'Activating...' : 'Deactivating...';
            }

            try {
                if (typeof SureSalezAdminData === 'undefined') {
                    return; // Fallback to form submit
                }

                const formData = new FormData();
                formData.append('action', 'suresalez_toggle_module');
                formData.append('module_id', moduleId);
                formData.append('state', isChecked ? 'true' : 'false');
                formData.append('nonce', SureSalezAdminData.nonce);

                const response = await fetch(SureSalezAdminData.ajax_url, {
                    method: 'POST',
                    body: formData,
                });

                const data = await response.json();

                if (data.success) {
                    if (badge) {
                        badge.className = isChecked ? 'runtime-badge loaded' : 'runtime-badge unloaded';
                        badge.textContent = isChecked ? 'Active & Enqueued' : 'Unloaded (Zero Bloat)';
                    }
                    updateHeaderCount();
                } else {
                    alert('Error toggling module: ' + (data.data?.message || 'Server error'));
                    input.checked = !isChecked; // Revert
                }
            } catch (err) {
                console.error('SureSalez Module Toggle Exception:', err);
                input.checked = !isChecked; // Revert
            }
        });
    });

    function updateHeaderCount() {
        const activeCards = document.querySelectorAll('.suresalez-module-card.active');
        const totalCards = document.querySelectorAll('.suresalez-module-card');
        const statusPill = document.querySelector('.suresalez-status-pill');
        if (statusPill) {
            const dot = statusPill.querySelector('.status-dot');
            statusPill.innerHTML = '';
            if (dot) statusPill.appendChild(dot);
            statusPill.appendChild(document.createTextNode(` ${activeCards.length} of ${totalCards.length} Modules Active`));
        }
    }
});
