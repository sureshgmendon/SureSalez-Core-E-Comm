# Rule: Security, Data Sanitization & Database Performance

## Scope
Governs data storage, sanitization, authentication, and execution safety for SureSalez Core.

## Security Mandates

1. **Input Sanitization & Output Escaping**:
   - Every input parameter from `$_POST`, `$_GET`, `$_REQUEST`, or REST payloads must be sanitized according to its expected type:
     - Text / SKU: `sanitize_text_field()`
     - Textarea / Specs: `sanitize_textarea_field()`
     - URLs / Asset links: `esc_url_raw()`
     - Integers / Quantities / IDs: `absint()` or `intval()`
     - Floats / Prices / Rates: `floatval()` or `wc_format_decimal()`
     - HTML Content: `wp_kses_post()`
   - All output in HTML must be escaped with `esc_html()`, `esc_attr()`, or `esc_url()`.

2. **Nonce Verification & Access Control**:
   - Admin AJAX handlers and form submissions MUST verify nonces using `check_ajax_referer()` or `wp_verify_nonce()`.
   - REST API endpoints performing write operations (`POST`, `PUT`, `DELETE`) MUST implement `permission_callback` checking `current_user_can()`.
   - Nonce actions must follow the format `suresalez_{action_slug}_nonce`.

3. **Database & SQL Invariants**:
   - Custom tables must be declared in `class-database.php` using WordPress `dbDelta()`.
   - All table names must be dynamically prefixed: `{$wpdb->prefix}suresalez_{table_name}`.
   - Dynamic parameters in raw SQL queries MUST ALWAYS pass through `$wpdb->prepare()`. Never concatenate variables into SQL strings.
   - Tables must have foreign key-like consistency using composite indexes (e.g. `KEY idx_product_loc (product_id, location_id)`).

4. **REST API Security**:
   - Public read endpoints (e.g. public reviews, stock availability) must employ rate limiting and return sanitized payloads.
   - Internal/management endpoints require the `manage_woocommerce` or `manage_suresalez` capability.
