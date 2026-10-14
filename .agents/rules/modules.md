# Rule: Module Lifecycle & Zero-Bloat Execution

## Scope
Governs all sub-modules located inside `plugins/suresalez-core/modules/`.

## Module Registration & Lifecycle

1. **Module Base Class**:
   - Every module must inherit from `SureSalez_Module_Base` and implement:
     - `get_id()`: Unique slug (e.g. `catalog-pim`, `multi-location-inventory`, `visual-swatches`, `reviews-qa`).
     - `get_name()`: Human-readable display label.
     - `get_description()`: Clear summary of the module's business purpose.
     - `get_category()`: Categorization (`Catalog`, `Inventory`, `Checkout`, `Marketing`, `Logistics`, `B2B`, etc.).
     - `is_default_active()`: Boolean indicating if it defaults to enabled.
     - `init()`: Runs ONLY when the module is active. Enqueues hooks, REST routes, and business logic.
     - `register_assets()`: Enqueues frontend/admin CSS and JS ONLY when needed and active.

2. **Zero-Bloat Invariant**:
   - When a module is toggled **OFF** in the SureSalez Module Manager:
     - The module's `init()` method is NEVER executed.
     - Zero action or filter hooks are bound to WordPress.
     - Zero CSS stylesheets or JavaScript scripts from that module may be injected into the response DOM.
     - Module-specific REST endpoints return `404 Not Found` or `403 Module Disabled`.

3. **Inter-Module Communication**:
   - Modules must remain loosely coupled.
   - Cross-module interaction must happen via WordPress action/filter hooks (e.g., `do_action('suresalez_inventory_updated', ...)`).
   - Never directly instantiate a peer module class without checking `SureSalez_Module_Manager::is_module_active('module-id')`.

4. **Directory Structure per Module**:
   ```
   modules/{module-slug}/
   ├── class-{module-slug}-module.php   # Module entrypoint (extends SureSalez_Module_Base)
   ├── assets/
   │   ├── css/{module-slug}.css
   │   └── js/{module-slug}.js
   └── (auxiliary class files, e.g. class-metafields.php, class-dam.php)
   ```
