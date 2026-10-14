/**
 * SureSalez Visual Swatches Client Engine
 */

document.addEventListener('DOMContentLoaded', () => {
    let swatchesConfig = null;

    if (typeof SureSalezSwatchesData !== 'undefined') {
        swatchesConfig = SureSalezSwatchesData.swatchesData;
    } else {
        const el = document.getElementById('suresalez-swatches-data-json');
        if (el) {
            try {
                swatchesConfig = JSON.parse(el.textContent);
            } catch (e) {
                console.error('Error parsing SureSalez Swatches JSON:', e);
            }
        }
    }

    if (!swatchesConfig || !Array.isArray(swatchesConfig)) {
        return;
    }

    const mount = document.getElementById('suresalez-swatches-mount');
    if (!mount) return;

    renderSwatches(mount, swatchesConfig);
});

function renderSwatches(container, groups) {
    container.innerHTML = '';

    groups.forEach((group, groupIdx) => {
        const groupEl = document.createElement('div');
        groupEl.className = 'suresalez-swatch-group';
        groupEl.setAttribute('data-attribute-name', group.attribute_name);

        const headerEl = document.createElement('div');
        headerEl.className = 'suresalez-swatch-header';

        const titleSpan = document.createElement('span');
        titleSpan.textContent = group.attribute_name + ':';
        headerEl.appendChild(titleSpan);

        const selectedLabel = document.createElement('span');
        selectedLabel.className = 'suresalez-swatch-selected-label';
        const defaultSelected = group.options.find(opt => opt.in_stock) || group.options[0];
        selectedLabel.textContent = defaultSelected ? defaultSelected.label : '';
        headerEl.appendChild(selectedLabel);

        groupEl.appendChild(headerEl);

        const optionsContainer = document.createElement('div');
        optionsContainer.className = 'suresalez-swatch-options';
        optionsContainer.setAttribute('role', 'radiogroup');
        optionsContainer.setAttribute('aria-label', group.attribute_name);

        group.options.forEach((opt, optIdx) => {
            const isSelected = defaultSelected && (defaultSelected.value === opt.value);
            const item = document.createElement('button');
            item.type = 'button';
            item.className = `suresalez-swatch-item type-${group.type} ${isSelected ? 'is-selected' : ''} ${opt.in_stock ? '' : 'out-of-stock'}`;
            item.setAttribute('role', 'radio');
            item.setAttribute('aria-checked', isSelected ? 'true' : 'false');
            item.setAttribute('title', `${opt.label}${opt.in_stock ? '' : ' (Out of Stock)'}`);
            item.setAttribute('data-value', opt.value);
            item.setAttribute('data-label', opt.label);

            if (group.type === 'color') {
                const fill = document.createElement('span');
                fill.className = 'suresalez-swatch-color-fill';
                fill.style.backgroundColor = opt.value;
                item.appendChild(fill);
            } else if (group.type === 'image' && opt.image_url) {
                const img = document.createElement('img');
                img.src = opt.image_url;
                img.alt = opt.label;
                item.appendChild(img);
            } else {
                // Button / Pill
                item.textContent = opt.label;
            }

            // Selection Event
            item.addEventListener('click', () => {
                if (!opt.in_stock) return;

                optionsContainer.querySelectorAll('.suresalez-swatch-item').forEach(btn => {
                    btn.classList.remove('is-selected');
                    btn.setAttribute('aria-checked', 'false');
                });

                item.classList.add('is-selected');
                item.setAttribute('aria-checked', 'true');
                selectedLabel.textContent = opt.label;

                // Dispatch custom event for theme or peer modules
                document.dispatchEvent(new CustomEvent('suresalez:swatch_selected', {
                    detail: {
                        attribute: group.attribute_name,
                        value: opt.value,
                        label: opt.label,
                    }
                }));
            });

            // Keyboard navigation
            item.addEventListener('keydown', (e) => {
                const buttons = Array.from(optionsContainer.querySelectorAll('.suresalez-swatch-item:not(.out-of-stock)'));
                const currentIndex = buttons.indexOf(item);
                let nextIndex = null;

                if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
                    nextIndex = (currentIndex + 1) % buttons.length;
                } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
                    nextIndex = (currentIndex - 1 + buttons.length) % buttons.length;
                }

                if (nextIndex !== null) {
                    e.preventDefault();
                    buttons[nextIndex].focus();
                    buttons[nextIndex].click();
                }
            });

            optionsContainer.appendChild(item);
        });

        groupEl.appendChild(optionsContainer);
        container.appendChild(groupEl);
    });
}
