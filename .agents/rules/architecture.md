# Rule: Architecture Boundaries & Decoupling

## Scope
Governs the separation of concerns between `plugins/suresalez-core/` and `themes/suresalez-theme/`.

## Architectural Invariants

1. **Theme Purity**:
   - The theme directory `themes/suresalez-theme/` must remain a pure Full Site Editing (FSE) block theme.
   - It MUST NOT register Custom Post Types (CPTs), custom taxonomies, REST API routes, shortcodes, or database schemas.
   - It MUST NOT call `$wpdb` or mutate any persistent option.
   - All visual design tokens (colors, font families, line heights, spacing scales, fluid layouts) MUST be declared in `theme.json`.

2. **Core Plugin Responsibility**:
   - All backend commerce functionality, cart/checkout controllers, pricing algorithms, product information management (PIM), and external integrations MUST reside inside `plugins/suresalez-core/`.
   - Plugin templates or block wrappers must expose standardized block attributes or template tags that the theme consumes via standard Gutenberg blocks.

3. **Stateless Frontend & Asynchronous Hydration**:
   - Dynamic UI components (e.g. variation swatches, review forms, inventory stock badges) must render clean HTML semantic markups and hydrate asynchronously using vanilla ES6+ modules.
   - Zero dependence on legacy libraries such as jQuery or bulky external frameworks.

4. **REST API Standard**:
   - All plugin endpoints must use namespace `suresalez/v1`.
   - Responses must return standard HTTP status codes, structured JSON payloads, and ISO 8601 timestamps.
