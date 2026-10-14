<?php
/**
 * SureSalez Database Engine.
 *
 * @package SureSalez\Core
 */

namespace SureSalez\Core;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Database
 *
 * Manages custom high-performance database tables, indexing, migrations,
 * and initial seed data for multi-location inventory and enterprise review engines.
 */
class Database {

    /**
     * Database version.
     */
    public const DB_VERSION = '1.8.0';

    /**
     * Option key storing current schema version.
     */
    public const DB_VERSION_KEY = 'suresalez_db_version';

    /**
     * Install or update all custom database tables via dbDelta.
     *
     * @return void
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Inventory Fulfillment Locations Table (10 Hubs)
        $table_locations = $wpdb->prefix . 'suresalez_inventory_locations';
        $sql_locations = "CREATE TABLE {$table_locations} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            location_code varchar(32) NOT NULL,
            name varchar(191) NOT NULL,
            address varchar(255) DEFAULT '' NOT NULL,
            city varchar(100) DEFAULT '' NOT NULL,
            state varchar(100) DEFAULT '' NOT NULL,
            country varchar(2) DEFAULT '' NOT NULL,
            postal_code varchar(20) DEFAULT '' NOT NULL,
            priority int(11) DEFAULT 10 NOT NULL,
            is_active tinyint(1) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_location_code (location_code),
            KEY idx_country_priority (country, priority)
        ) {$charset_collate};";
        dbDelta($sql_locations);

        // 2. Inventory Stock Allocations Table
        $table_stock = $wpdb->prefix . 'suresalez_inventory_stock';
        $sql_stock = "CREATE TABLE {$table_stock} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            location_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            sku varchar(64) DEFAULT '' NOT NULL,
            stock_quantity int(11) DEFAULT 0 NOT NULL,
            reserved_quantity int(11) DEFAULT 0 NOT NULL,
            safety_buffer int(11) DEFAULT 0 NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_loc_product (location_id, product_id),
            KEY idx_product (product_id),
            KEY idx_sku (sku)
        ) {$charset_collate};";
        dbDelta($sql_stock);

        // 3. Inventory Stock Transfers & Audit Log Table
        $table_transfers = $wpdb->prefix . 'suresalez_inventory_transfers';
        $sql_transfers = "CREATE TABLE {$table_transfers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            transfer_number varchar(64) NOT NULL,
            source_location_id bigint(20) unsigned NOT NULL,
            target_location_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            sku varchar(64) DEFAULT '' NOT NULL,
            quantity int(11) NOT NULL,
            status varchar(32) DEFAULT 'pending' NOT NULL,
            notes text DEFAULT NULL,
            created_by bigint(20) unsigned DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_transfer_number (transfer_number),
            KEY idx_source_target (source_location_id, target_location_id),
            KEY idx_status (status)
        ) {$charset_collate};";
        dbDelta($sql_transfers);

        // 4. Verified Customer Reviews Table
        $table_reviews = $wpdb->prefix . 'suresalez_reviews';
        $sql_reviews = "CREATE TABLE {$table_reviews} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            author_name varchar(100) NOT NULL,
            author_email varchar(100) NOT NULL,
            rating tinyint(1) NOT NULL,
            title varchar(255) DEFAULT '' NOT NULL,
            content text NOT NULL,
            pros text DEFAULT NULL,
            cons text DEFAULT NULL,
            media_urls longtext DEFAULT NULL,
            is_verified_buyer tinyint(1) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'approved' NOT NULL,
            helpful_count int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_product_status (product_id, status),
            KEY idx_rating (rating)
        ) {$charset_collate};";
        dbDelta($sql_reviews);

        // 5. Customer Community Questions Table
        $table_questions = $wpdb->prefix . 'suresalez_questions';
        $sql_questions = "CREATE TABLE {$table_questions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            product_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            author_name varchar(100) NOT NULL,
            author_email varchar(100) NOT NULL,
            question text NOT NULL,
            status varchar(20) DEFAULT 'approved' NOT NULL,
            upvotes int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_product_status (product_id, status)
        ) {$charset_collate};";
        dbDelta($sql_questions);

        // 6. Community Answers Table (Merchant & User Replies)
        $table_answers = $wpdb->prefix . 'suresalez_answers';
        $sql_answers = "CREATE TABLE {$table_answers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            question_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            author_name varchar(100) NOT NULL,
            is_merchant tinyint(1) DEFAULT 0 NOT NULL,
            answer text NOT NULL,
            status varchar(20) DEFAULT 'approved' NOT NULL,
            upvotes int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_question_status (question_id, status)
        ) {$charset_collate};";
        dbDelta($sql_answers);

        // 7. Store Credit Wallet Transactions Ledger Table (Sprint 02)
        $table_wallet = $wpdb->prefix . 'suresalez_wallet_transactions';
        $sql_wallet = "CREATE TABLE {$table_wallet} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            type varchar(20) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD' NOT NULL,
            balance_after decimal(10,2) NOT NULL,
            reference_type varchar(50) DEFAULT 'manual' NOT NULL,
            reference_id varchar(100) DEFAULT '' NOT NULL,
            notes varchar(255) DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_user_id (user_id),
            KEY idx_type (type),
            KEY idx_reference (reference_type, reference_id)
        ) {$charset_collate};";
        dbDelta($sql_wallet);

        // 8. Abandoned Checkouts & Recovery Table (Sprint 02)
        $table_abandoned = $wpdb->prefix . 'suresalez_abandoned_checkouts';
        $sql_abandoned = "CREATE TABLE {$table_abandoned} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            cart_token varchar(64) NOT NULL,
            cart_contents longtext NOT NULL,
            customer_email varchar(100) DEFAULT '' NOT NULL,
            customer_phone varchar(30) DEFAULT '' NOT NULL,
            cart_total decimal(10,2) DEFAULT 0.00 NOT NULL,
            currency varchar(3) DEFAULT 'USD' NOT NULL,
            status varchar(30) DEFAULT 'abandoned' NOT NULL,
            recovery_token varchar(64) NOT NULL,
            reminder_count tinyint(1) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_cart_token (cart_token),
            KEY idx_recovery_token (recovery_token),
            KEY idx_status_created (status, created_at),
            KEY idx_customer_email (customer_email)
        ) {$charset_collate};";
        dbDelta($sql_abandoned);

        // 9. Post-Purchase 1-Click Upsell Offers Table (Sprint 02)
        $table_offers = $wpdb->prefix . 'suresalez_post_purchase_offers';
        $sql_offers = "CREATE TABLE {$table_offers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(191) NOT NULL,
            trigger_product_ids text NOT NULL,
            offer_product_id bigint(20) unsigned NOT NULL,
            discount_type varchar(20) DEFAULT 'percentage' NOT NULL,
            discount_value decimal(10,2) NOT NULL,
            urgency_timer_seconds int(11) DEFAULT 300 NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_status (status),
            KEY idx_offer_product (offer_product_id)
        ) {$charset_collate};";
        dbDelta($sql_offers);

        // 10. Multi-Currency Exchange Rates & Rounding Table (Sprint 03)
        $table_currencies = $wpdb->prefix . 'suresalez_currencies';
        $sql_currencies = "CREATE TABLE {$table_currencies} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(3) NOT NULL,
            symbol varchar(8) NOT NULL,
            name varchar(100) NOT NULL,
            rate_to_base decimal(12,6) DEFAULT 1.000000 NOT NULL,
            rounding_rule varchar(30) DEFAULT 'dot_99' NOT NULL,
            is_active tinyint(1) DEFAULT 1 NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_code (code),
            KEY idx_active (is_active)
        ) {$charset_collate};";
        dbDelta($sql_currencies);

        // 11. Global Tax Jurisdictions & GST/VAT Rules Table (Sprint 03)
        $table_tax = $wpdb->prefix . 'suresalez_tax_jurisdictions';
        $sql_tax = "CREATE TABLE {$table_tax} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            country_code varchar(2) NOT NULL,
            state_zone varchar(50) DEFAULT '' NOT NULL,
            tax_type varchar(30) NOT NULL,
            standard_rate decimal(5,2) DEFAULT 0.00 NOT NULL,
            reduced_rate decimal(5,2) DEFAULT 0.00 NOT NULL,
            hsn_sac_rules longtext DEFAULT NULL,
            is_duties_precollected tinyint(1) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_country_zone (country_code, state_zone),
            KEY idx_tax_type (tax_type)
        ) {$charset_collate};";
        dbDelta($sql_tax);

        // 12. CRM Customer Profiles Table (Sprint 04)
        $table_crm_profiles = $wpdb->prefix . 'suresalez_customer_profiles';
        $sql_crm_profiles = "CREATE TABLE {$table_crm_profiles} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            email varchar(191) NOT NULL,
            phone varchar(32) DEFAULT '' NOT NULL,
            total_spend decimal(14,2) DEFAULT 0.00 NOT NULL,
            order_count int(11) unsigned DEFAULT 0 NOT NULL,
            aov decimal(12,2) DEFAULT 0.00 NOT NULL,
            first_order_at datetime DEFAULT NULL,
            last_order_at datetime DEFAULT NULL,
            tags longtext DEFAULT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_email (email),
            KEY idx_user_id (user_id),
            KEY idx_spend_orders (total_spend, order_count),
            KEY idx_last_order (last_order_at)
        ) {$charset_collate};";
        dbDelta($sql_crm_profiles);

        // 13. CRM Customer Timeline Table (Sprint 04)
        $table_crm_timeline = $wpdb->prefix . 'suresalez_customer_timeline';
        $sql_crm_timeline = "CREATE TABLE {$table_crm_timeline} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            event_type varchar(50) NOT NULL,
            event_payload longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_customer_event (customer_id, event_type),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";
        dbDelta($sql_crm_timeline);

        // 14. CRM Behavioral Segments Table (Sprint 04)
        $table_crm_segments = $wpdb->prefix . 'suresalez_segments';
        $sql_crm_segments = "CREATE TABLE {$table_crm_segments} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            slug varchar(100) NOT NULL,
            conditions longtext NOT NULL,
            member_count int(11) unsigned DEFAULT 0 NOT NULL,
            is_dynamic tinyint(1) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_slug (slug),
            KEY idx_is_dynamic (is_dynamic)
        ) {$charset_collate};";
        dbDelta($sql_crm_segments);

        // 15. Flow Automation Workflows Table (Sprint 04)
        $table_flow_workflows = $wpdb->prefix . 'suresalez_flow_workflows';
        $sql_flow_workflows = "CREATE TABLE {$table_flow_workflows} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(191) NOT NULL,
            trigger_event varchar(100) NOT NULL,
            conditions longtext DEFAULT NULL,
            actions longtext NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            execution_count int(11) unsigned DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_trigger_status (trigger_event, status)
        ) {$charset_collate};";
        dbDelta($sql_flow_workflows);

        // 16. Flow Automation Execution Logs Table (Sprint 04)
        $table_flow_logs = $wpdb->prefix . 'suresalez_flow_logs';
        $sql_flow_logs = "CREATE TABLE {$table_flow_logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            workflow_id bigint(20) unsigned NOT NULL,
            trigger_payload longtext DEFAULT NULL,
            execution_status varchar(20) NOT NULL,
            log_message text DEFAULT NULL,
            executed_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_workflow_status (workflow_id, execution_status),
            KEY idx_executed_at (executed_at)
        ) {$charset_collate};";
        dbDelta($sql_flow_logs);

        // 17. Shoppable Videos Table (Sprint 05)
        $table_shoppable_videos = $wpdb->prefix . 'suresalez_shoppable_videos';
        $sql_shoppable_videos = "CREATE TABLE {$table_shoppable_videos} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            video_url text NOT NULL,
            thumbnail_url text DEFAULT NULL,
            duration int(10) unsigned DEFAULT 0 NOT NULL,
            tagged_product_ids longtext NOT NULL,
            views_count bigint(20) unsigned DEFAULT 0 NOT NULL,
            clicks_count bigint(20) unsigned DEFAULT 0 NOT NULL,
            sort_order int(11) DEFAULT 0 NOT NULL,
            status varchar(20) DEFAULT 'published' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_status_sort (status, sort_order)
        ) {$charset_collate};";
        dbDelta($sql_shoppable_videos);

        // 18. Campaign Schedules Table (Sprint 05)
        $table_campaign_schedules = $wpdb->prefix . 'suresalez_campaign_schedules';
        $sql_campaign_schedules = "CREATE TABLE {$table_campaign_schedules} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_name varchar(191) NOT NULL,
            start_time datetime NOT NULL,
            end_time datetime NOT NULL,
            theme_mods longtext DEFAULT NULL,
            price_rules longtext DEFAULT NULL,
            status varchar(20) DEFAULT 'scheduled' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_status_dates (status, start_time, end_time)
        ) {$charset_collate};";
        dbDelta($sql_campaign_schedules);

        // 19. CAPI Events Queue Table (Sprint 05)
        $table_capi_events = $wpdb->prefix . 'suresalez_capi_events';
        $sql_capi_events = "CREATE TABLE {$table_capi_events} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_name varchar(50) NOT NULL,
            event_id varchar(191) NOT NULL,
            user_data_hashed longtext DEFAULT NULL,
            custom_data longtext DEFAULT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            response_code int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_event_id (event_id),
            KEY idx_status_event (status, event_name)
        ) {$charset_collate};";
        dbDelta($sql_capi_events);

        // 20. Analytics BI Snapshots Table (Sprint 05)
        $table_analytics_snapshots = $wpdb->prefix . 'suresalez_analytics_snapshots';
        $sql_analytics_snapshots = "CREATE TABLE {$table_analytics_snapshots} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            snapshot_date date NOT NULL,
            metric_key varchar(100) NOT NULL,
            metric_value decimal(14,4) DEFAULT 0.0000 NOT NULL,
            dimensions longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_date_metric (snapshot_date, metric_key)
        ) {$charset_collate};";
        dbDelta($sql_analytics_snapshots);

        // 21. Order Fulfillments Table (Sprint 06)
        $table_fulfillments = $wpdb->prefix . 'suresalez_fulfillments';
        $sql_fulfillments = "CREATE TABLE {$table_fulfillments} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            fulfillment_number varchar(64) NOT NULL,
            location_id bigint(20) unsigned NOT NULL,
            carrier_name varchar(64) NOT NULL,
            tracking_number varchar(128) NOT NULL,
            tracking_url text DEFAULT NULL,
            status varchar(30) DEFAULT 'unfulfilled' NOT NULL,
            shipped_at datetime DEFAULT NULL,
            delivered_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_fulfillment_num (fulfillment_number),
            KEY idx_order_status (order_id, status),
            KEY idx_location (location_id),
            KEY idx_tracking (tracking_number)
        ) {$charset_collate};";
        dbDelta($sql_fulfillments);

        // 22. Fulfillment Items Table (Sprint 06)
        $table_fulfillment_items = $wpdb->prefix . 'suresalez_fulfillment_items';
        $sql_fulfillment_items = "CREATE TABLE {$table_fulfillment_items} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            fulfillment_id bigint(20) unsigned NOT NULL,
            order_item_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            variation_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            quantity int(11) unsigned DEFAULT 1 NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_fulfillment_order_item (fulfillment_id, order_item_id),
            KEY idx_product (product_id)
        ) {$charset_collate};";
        dbDelta($sql_fulfillment_items);

        // 23. Customer Self-Service RMA Requests Table (Sprint 06)
        $table_rma_requests = $wpdb->prefix . 'suresalez_rma_requests';
        $sql_rma_requests = "CREATE TABLE {$table_rma_requests} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            rma_number varchar(64) NOT NULL,
            order_id bigint(20) unsigned NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            status varchar(30) DEFAULT 'requested' NOT NULL,
            return_type varchar(30) DEFAULT 'store_credit' NOT NULL,
            reason_code varchar(64) NOT NULL,
            customer_notes text DEFAULT NULL,
            media_evidence longtext DEFAULT NULL,
            return_label_url text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_rma_number (rma_number),
            KEY idx_order_customer (order_id, customer_id),
            KEY idx_status (status)
        ) {$charset_collate};";
        dbDelta($sql_rma_requests);

        // 24. RMA Requested Items & Restock Inspection Table (Sprint 06)
        $table_rma_items = $wpdb->prefix . 'suresalez_rma_items';
        $sql_rma_items = "CREATE TABLE {$table_rma_items} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            rma_id bigint(20) unsigned NOT NULL,
            order_item_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            quantity int(11) unsigned DEFAULT 1 NOT NULL,
            inspection_status varchar(20) DEFAULT 'pending' NOT NULL,
            restock_location_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            refund_amount decimal(12,2) DEFAULT 0.00 NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_rma_item (rma_id, product_id)
        ) {$charset_collate};";
        dbDelta($sql_rma_items);

        // 25. Supplier Dropship & Print-on-Demand Integrations Table (Sprint 06)
        $table_supplier_dropships = $wpdb->prefix . 'suresalez_supplier_dropships';
        $sql_supplier_dropships = "CREATE TABLE {$table_supplier_dropships} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            supplier_name varchar(191) NOT NULL,
            supplier_email varchar(191) NOT NULL,
            webhook_url text DEFAULT NULL,
            sku_prefix varchar(32) NOT NULL,
            lead_time_days int(11) DEFAULT 3 NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_sku_prefix (sku_prefix),
            KEY idx_status (status)
        ) {$charset_collate};";
        dbDelta($sql_supplier_dropships);

        // 26. B2B Corporate Companies Table (Sprint 07)
        $table_b2b_companies = $wpdb->prefix . 'suresalez_b2b_companies';
        $sql_b2b_companies = "CREATE TABLE {$table_b2b_companies} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            company_name varchar(191) NOT NULL,
            company_tax_id varchar(64) NOT NULL,
            status varchar(30) DEFAULT 'pending' NOT NULL,
            credit_limit decimal(14,2) DEFAULT 0.00 NOT NULL,
            payment_terms varchar(30) DEFAULT 'prepaid' NOT NULL,
            approval_threshold decimal(14,2) DEFAULT 1000.00 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_tax_id (company_tax_id),
            KEY idx_status (status)
        ) {$charset_collate};";
        dbDelta($sql_b2b_companies);

        // 27. B2B Company Users & Permissions Table (Sprint 07)
        $table_b2b_users = $wpdb->prefix . 'suresalez_b2b_users';
        $sql_b2b_users = "CREATE TABLE {$table_b2b_users} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            company_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            role varchar(30) DEFAULT 'purchaser' NOT NULL,
            spending_limit_per_order decimal(14,2) DEFAULT 0.00 NOT NULL,
            is_active tinyint(1) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_company_user (company_id, user_id),
            KEY idx_user (user_id)
        ) {$charset_collate};";
        dbDelta($sql_b2b_users);

        // 28. B2B Tiered Price Lists Table (Sprint 07)
        $table_b2b_pricelists = $wpdb->prefix . 'suresalez_b2b_pricelists';
        $sql_b2b_pricelists = "CREATE TABLE {$table_b2b_pricelists} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(191) NOT NULL,
            company_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            discount_type varchar(30) DEFAULT 'percent_off' NOT NULL,
            pricing_rules longtext NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_company (company_id),
            KEY idx_status (status)
        ) {$charset_collate};";
        dbDelta($sql_b2b_pricelists);

        // 29. B2B Request For Quote (RFQ) Table (Sprint 07)
        $table_b2b_quotes = $wpdb->prefix . 'suresalez_b2b_quotes';
        $sql_b2b_quotes = "CREATE TABLE {$table_b2b_quotes} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            quote_number varchar(64) NOT NULL,
            company_id bigint(20) unsigned NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            items longtext NOT NULL,
            subtotal decimal(14,2) DEFAULT 0.00 NOT NULL,
            freight_amount decimal(14,2) DEFAULT 0.00 NOT NULL,
            total_quoted decimal(14,2) DEFAULT 0.00 NOT NULL,
            status varchar(30) DEFAULT 'submitted' NOT NULL,
            quote_token varchar(64) NOT NULL,
            notes text DEFAULT NULL,
            counter_notes text DEFAULT NULL,
            expiration_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_quote_num (quote_number),
            UNIQUE KEY uk_quote_token (quote_token),
            KEY idx_company_status (company_id, status)
        ) {$charset_collate};";
        dbDelta($sql_b2b_quotes);

        // 30. B2B Orders & Corporate PO Tracking Table (Sprint 07)
        $table_b2b_orders = $wpdb->prefix . 'suresalez_b2b_orders';
        $sql_b2b_orders = "CREATE TABLE {$table_b2b_orders} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            company_id bigint(20) unsigned NOT NULL,
            po_number varchar(64) NOT NULL,
            payment_terms varchar(30) DEFAULT 'net_30' NOT NULL,
            due_date date DEFAULT NULL,
            approval_status varchar(30) DEFAULT 'approved' NOT NULL,
            approved_by_user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_order (order_id),
            KEY idx_company_approval (company_id, approval_status),
            KEY idx_po (po_number)
        ) {$charset_collate};";
        dbDelta($sql_b2b_orders);

        // 31. POS Registers Table (Sprint 08)
        $table_pos_registers = $wpdb->prefix . 'suresalez_pos_registers';
        $sql_pos_registers = "CREATE TABLE {$table_pos_registers} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            location_id bigint(20) unsigned NOT NULL,
            register_name varchar(100) NOT NULL,
            cashier_user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            opening_cash decimal(14,2) DEFAULT 0.00 NOT NULL,
            closing_cash decimal(14,2) DEFAULT 0.00 NOT NULL,
            status varchar(20) DEFAULT 'open' NOT NULL,
            opened_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            closed_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY idx_loc_status (location_id, status),
            KEY idx_cashier (cashier_user_id)
        ) {$charset_collate};";
        dbDelta($sql_pos_registers);

        // 32. POS Transactions & Tender Table (Sprint 08)
        $table_pos_tx = $wpdb->prefix . 'suresalez_pos_transactions';
        $sql_pos_tx = "CREATE TABLE {$table_pos_tx} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            register_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned NOT NULL,
            payment_method varchar(30) DEFAULT 'cash' NOT NULL,
            amount_tendered decimal(14,2) DEFAULT 0.00 NOT NULL,
            change_due decimal(14,2) DEFAULT 0.00 NOT NULL,
            receipt_number varchar(64) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_receipt (receipt_number),
            KEY idx_register_order (register_id, order_id)
        ) {$charset_collate};";
        dbDelta($sql_pos_tx);

        // 33. Multi-Vendor Marketplace Profiles Table (Sprint 08)
        $table_vendors = $wpdb->prefix . 'suresalez_marketplace_vendors';
        $sql_vendors = "CREATE TABLE {$table_vendors} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            store_name varchar(191) NOT NULL,
            slug varchar(100) NOT NULL,
            commission_rate decimal(5,2) DEFAULT 15.00 NOT NULL,
            balance_available decimal(14,2) DEFAULT 0.00 NOT NULL,
            balance_pending decimal(14,2) DEFAULT 0.00 NOT NULL,
            payout_method_details longtext DEFAULT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_vendor_user (user_id),
            UNIQUE KEY uk_slug (slug),
            KEY idx_status (status)
        ) {$charset_collate};";
        dbDelta($sql_vendors);

        // 34. Multi-Vendor Commission Splits Ledger Table (Sprint 08)
        $table_commissions = $wpdb->prefix . 'suresalez_vendor_commissions';
        $sql_commissions = "CREATE TABLE {$table_commissions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            order_id bigint(20) unsigned NOT NULL,
            vendor_id bigint(20) unsigned NOT NULL,
            line_item_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            gross_amount decimal(14,2) DEFAULT 0.00 NOT NULL,
            commission_fee decimal(14,2) DEFAULT 0.00 NOT NULL,
            net_vendor_payout decimal(14,2) DEFAULT 0.00 NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_order_vendor (order_id, vendor_id),
            KEY idx_vendor_status (vendor_id, status)
        ) {$charset_collate};";
        dbDelta($sql_commissions);

        // 35. Native Subscriptions Table (Sprint 08)
        $table_subscriptions = $wpdb->prefix . 'suresalez_subscriptions';
        $sql_subscriptions = "CREATE TABLE {$table_subscriptions} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_token varchar(64) NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            variation_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            billing_interval varchar(20) DEFAULT 'monthly' NOT NULL,
            discount_percentage decimal(5,2) DEFAULT 10.00 NOT NULL,
            next_renewal_date datetime NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            card_token_hash varchar(64) DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_sub_token (subscription_token),
            KEY idx_cust_status (customer_id, status),
            KEY idx_renewal (next_renewal_date, status)
        ) {$charset_collate};";
        dbDelta($sql_subscriptions);

        // 36. Subscription Renewal & Dunning Logs Table (Sprint 08)
        $table_sub_logs = $wpdb->prefix . 'suresalez_subscription_logs';
        $sql_sub_logs = "CREATE TABLE {$table_sub_logs} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            subscription_id bigint(20) unsigned NOT NULL,
            renewal_order_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            attempt_count int(11) DEFAULT 1 NOT NULL,
            status varchar(20) DEFAULT 'success' NOT NULL,
            error_message text DEFAULT NULL,
            attempted_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_sub_attempt (subscription_id, attempt_count),
            KEY idx_status (status)
        ) {$charset_collate};";
        dbDelta($sql_sub_logs);

        // 37. Loyalty Points Accounts Table (Sprint 09)
        $table_loyalty = $wpdb->prefix . 'suresalez_loyalty_points';
        $sql_loyalty = "CREATE TABLE {$table_loyalty} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            points_balance int(11) DEFAULT 0 NOT NULL,
            lifetime_points int(11) DEFAULT 0 NOT NULL,
            tier_level varchar(30) DEFAULT 'bronze' NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_user_loyalty (user_id),
            KEY idx_tier (tier_level)
        ) {$charset_collate};";
        dbDelta($sql_loyalty);

        // 38. Loyalty Points Transactions Ledger Table (Sprint 09)
        $table_points_tx = $wpdb->prefix . 'suresalez_points_transactions';
        $sql_points_tx = "CREATE TABLE {$table_points_tx} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            points int(11) NOT NULL,
            type varchar(30) DEFAULT 'earned' NOT NULL,
            description varchar(255) DEFAULT '' NOT NULL,
            reference_order_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_user_type (user_id, type),
            KEY idx_ref_order (reference_order_id)
        ) {$charset_collate};";
        dbDelta($sql_points_tx);

        // 39. Referrals & Viral Program Table (Sprint 09)
        $table_referrals = $wpdb->prefix . 'suresalez_referrals';
        $sql_referrals = "CREATE TABLE {$table_referrals} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            referrer_user_id bigint(20) unsigned NOT NULL,
            referral_code varchar(64) NOT NULL,
            referred_user_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            reward_status varchar(30) DEFAULT 'pending' NOT NULL,
            order_id bigint(20) unsigned DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY idx_referral_code (referral_code),
            KEY idx_referrer_status (referrer_user_id, reward_status)
        ) {$charset_collate};";
        dbDelta($sql_referrals);

        // 40. Automated SEO 301 Redirects Table (Sprint 09)
        $table_redirects = $wpdb->prefix . 'suresalez_seo_redirects';
        $sql_redirects = "CREATE TABLE {$table_redirects} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            source_url varchar(255) NOT NULL,
            target_url varchar(255) NOT NULL,
            status_code int(11) DEFAULT 301 NOT NULL,
            hit_count int(11) DEFAULT 0 NOT NULL,
            last_accessed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_source_url (source_url),
            KEY idx_status (status_code)
        ) {$charset_collate};";
        dbDelta($sql_redirects);

        // 41. Headless Mobile Push Notification Devices Table (Sprint 09)
        $table_mobile_devices = $wpdb->prefix . 'suresalez_mobile_devices';
        $sql_mobile_devices = "CREATE TABLE {$table_mobile_devices} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            device_token varchar(255) NOT NULL,
            platform varchar(20) DEFAULT 'ios' NOT NULL,
            app_version varchar(32) DEFAULT '1.0.0' NOT NULL,
            last_active_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uk_device_token (device_token),
            KEY idx_user_platform (user_id, platform)
        ) {$charset_collate};";
        dbDelta($sql_mobile_devices);

        update_option(self::DB_VERSION_KEY, self::DB_VERSION);

        // Seed 10 warehouse fulfillment locations
        self::seed_inventory_locations();

        // Seed post-purchase offers
        self::seed_post_purchase_offers();

        // Seed multi-currency rates & tax jurisdictions (Sprint 03)
        self::seed_currencies();
        self::seed_tax_jurisdictions();

        // Seed default CRM segments & Flow workflows (Sprint 04)
        self::seed_default_segments();
        self::seed_default_flows();

        // Seed shoppable videos, campaigns & analytics snapshots (Sprint 05)
        self::seed_shoppable_videos();
        self::seed_campaign_schedules();
        self::seed_analytics_snapshots();

        // Seed fulfillments, suppliers & RMA data (Sprint 06)
        self::seed_fulfillments();
        self::seed_rma_data();
        self::seed_supplier_dropships();

        // Seed B2B corporate accounts, users, pricelists & quotes (Sprint 07)
        self::seed_b2b_companies();
        self::seed_b2b_users();
        self::seed_b2b_pricelists();
        self::seed_b2b_quotes();

        // Seed POS registers, vendors, commissions & subscriptions (Sprint 08)
        self::seed_pos_registers();
        self::seed_marketplace_vendors();
        self::seed_vendor_commissions();
        self::seed_subscriptions();

        // Seed Loyalty points, referrals & SEO redirects (Sprint 09)
        self::seed_loyalty_points();
        self::seed_referrals();
        self::seed_seo_redirects();
    }

    /**
     * Seed 10 Global Fulfillment Warehouse Hubs.
     *
     * @return void
     */
    public static function seed_inventory_locations(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_inventory_locations';

        // Check if locations already exist
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count >= 10) {
            return;
        }

        $locations = [
            [
                'location_code' => 'US-EAST-01',
                'name'          => 'North America East Hub (Newark Gateway)',
                'address'       => '100 Port Street, Terminal 4',
                'city'          => 'Newark',
                'state'         => 'NJ',
                'country'       => 'US',
                'postal_code'   => '07114',
                'priority'      => 1,
            ],
            [
                'location_code' => 'US-WEST-01',
                'name'          => 'North America West Hub (Reno Logistics)',
                'address'       => '550 Logistics Blvd, Suite 200',
                'city'          => 'Reno',
                'state'         => 'NV',
                'country'       => 'US',
                'postal_code'   => '89502',
                'priority'      => 2,
            ],
            [
                'location_code' => 'US-CENTRAL-01',
                'name'          => 'North America Central Hub (Dallas Intermodal)',
                'address'       => '1400 Freeport Parkway',
                'city'          => 'Dallas',
                'state'         => 'TX',
                'country'       => 'US',
                'postal_code'   => '75261',
                'priority'      => 3,
            ],
            [
                'location_code' => 'EU-CENTRAL-01',
                'name'          => 'Europe Central Hub (Frankfurt Airport Cargo)',
                'address'       => 'Flughafen Bereich West, Gebaeude 450',
                'city'          => 'Frankfurt am Main',
                'state'         => 'Hessen',
                'country'       => 'DE',
                'postal_code'   => '60549',
                'priority'      => 4,
            ],
            [
                'location_code' => 'EU-WEST-01',
                'name'          => 'Europe West Hub (London Heathrow Logistics)',
                'address'       => 'Building 520, Cargo Terminal, Hounslow',
                'city'          => 'London',
                'state'         => 'Greater London',
                'country'       => 'GB',
                'postal_code'   => 'TW6 3SS',
                'priority'      => 5,
            ],
            [
                'location_code' => 'APAC-EAST-01',
                'name'          => 'East Asia Logistics Hub (Tokyo Narita Air Cargo)',
                'address'       => 'Sanrizuka Goryo Bokujo, Narita',
                'city'          => 'Chiba',
                'state'         => 'Kanto',
                'country'       => 'JP',
                'postal_code'   => '282-0011',
                'priority'      => 6,
            ],
            [
                'location_code' => 'APAC-SOUTH-01',
                'name'          => 'Southeast Asia Gateway (Singapore Changi ALPS)',
                'address'       => '5 Changi South Lane, #03-01',
                'city'          => 'Singapore',
                'state'         => 'Singapore',
                'country'       => 'SG',
                'postal_code'   => '486045',
                'priority'      => 7,
            ],
            [
                'location_code' => 'APAC-AU-01',
                'name'          => 'Australasia Distribution Hub (Sydney Port Botany)',
                'address'       => '22 Bumborah Point Road',
                'city'          => 'Port Botany',
                'state'         => 'NSW',
                'country'       => 'AU',
                'postal_code'   => '2036',
                'priority'      => 8,
            ],
            [
                'location_code' => 'LATAM-BR-01',
                'name'          => 'Latin America Central Hub (São Paulo Aeroporto)',
                'address'       => 'Rodovia Helio Smidt, Terminal de Cargas',
                'city'          => 'Guarulhos',
                'state'         => 'SP',
                'country'       => 'BR',
                'postal_code'   => '07190-100',
                'priority'      => 9,
            ],
            [
                'location_code' => 'MEA-UAE-01',
                'name'          => 'Middle East & Africa Hub (Dubai DWC Freezone)',
                'address'       => 'Logistics City, Cargo Village Gate 3',
                'city'          => 'Dubai',
                'state'         => 'Dubai',
                'country'       => 'AE',
                'postal_code'   => '00000',
                'priority'      => 10,
            ],
        ];

        foreach ($locations as $loc) {
            $wpdb->replace(
                $table,
                [
                    'location_code' => $loc['location_code'],
                    'name'          => $loc['name'],
                    'address'       => $loc['address'],
                    'city'          => $loc['city'],
                    'state'         => $loc['state'],
                    'country'       => $loc['country'],
                    'postal_code'   => $loc['postal_code'],
                    'priority'      => $loc['priority'],
                    'is_active'     => 1,
                    'created_at'    => current_time('mysql'),
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s']
            );
        }
    }

    /**
     * Seed high-converting 1-Click Post-Purchase Upsell Offers.
     *
     * @return void
     */
    public static function seed_post_purchase_offers(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_post_purchase_offers';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $offers = [
            [
                'title'                 => 'Exclusive 35% Off: Grade 5 Titanium Milanese Deployment Loop',
                'trigger_product_ids'   => json_encode([101, 102, 103]),
                'offer_product_id'      => 201,
                'discount_type'         => 'percentage',
                'discount_value'        => 35.00,
                'urgency_timer_seconds' => 300,
                'status'                => 'active',
                'created_at'            => current_time('mysql'),
            ],
            [
                'title'                 => 'Add Precision Ultrasonic Polishing & Microfiber Care Kit for $29 (Save 40%)',
                'trigger_product_ids'   => json_encode([101, 102, 104]),
                'offer_product_id'      => 202,
                'discount_type'         => 'fixed',
                'discount_value'        => 20.00,
                'urgency_timer_seconds' => 180,
                'status'                => 'active',
                'created_at'            => current_time('mysql'),
            ],
        ];

        foreach ($offers as $offer) {
            $wpdb->insert(
                $table,
                $offer,
                ['%s', '%s', '%d', '%s', '%f', '%d', '%s', '%s']
            );
        }
    }

    /**
     * Seed active global currencies and psychological rounding rules.
     *
     * @return void
     */
    public static function seed_currencies(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_currencies';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $currencies = [
            ['code' => 'USD', 'symbol' => '$',   'name' => 'US Dollar',        'rate' => 1.000000, 'rounding' => 'dot_99'],
            ['code' => 'EUR', 'symbol' => '€',   'name' => 'Euro',             'rate' => 0.920000, 'rounding' => 'dot_99'],
            ['code' => 'GBP', 'symbol' => '£',   'name' => 'British Pound',    'rate' => 0.780000, 'rounding' => 'dot_99'],
            ['code' => 'INR', 'symbol' => '₹',   'name' => 'Indian Rupee',     'rate' => 84.500000,'rounding' => 'nearest_integer'],
            ['code' => 'AED', 'symbol' => 'د.إ', 'name' => 'UAE Dirham',       'rate' => 3.670000, 'rounding' => 'dot_00'],
            ['code' => 'JPY', 'symbol' => '¥',   'name' => 'Japanese Yen',     'rate' => 152.000000,'rounding' => 'nearest_integer'],
            ['code' => 'AUD', 'symbol' => 'A$',  'name' => 'Australian Dollar','rate' => 1.540000, 'rounding' => 'dot_99'],
            ['code' => 'CAD', 'symbol' => 'CA$', 'name' => 'Canadian Dollar',  'rate' => 1.380000, 'rounding' => 'dot_99'],
        ];

        foreach ($currencies as $curr) {
            $wpdb->replace(
                $table,
                [
                    'code'          => $curr['code'],
                    'symbol'        => $curr['symbol'],
                    'name'          => $curr['name'],
                    'rate_to_base'  => $curr['rate'],
                    'rounding_rule' => $curr['rounding'],
                    'is_active'     => 1,
                    'updated_at'    => current_time('mysql'),
                ],
                ['%s', '%s', '%s', '%f', '%s', '%d', '%s']
            );
        }
    }

    /**
     * Seed global tax jurisdictions, Indian GST rules, and EU VAT rates.
     *
     * @return void
     */
    public static function seed_tax_jurisdictions(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_tax_jurisdictions';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $jurisdictions = [
            [
                'country_code'           => 'IN',
                'state_zone'             => 'ALL',
                'tax_type'               => 'GST',
                'standard_rate'          => 18.00,
                'reduced_rate'           => 3.00,
                'hsn_sac_rules'          => json_encode([
                    '7117' => 3.00,  // Imitation Jewelry
                    '9102' => 18.00, // Wristwatches
                    '8471' => 18.00, // Computing equipment
                ]),
                'is_duties_precollected' => 0,
                'created_at'             => current_time('mysql'),
            ],
            [
                'country_code'           => 'DE',
                'state_zone'             => 'EU_OSS',
                'tax_type'               => 'VAT',
                'standard_rate'          => 19.00,
                'reduced_rate'           => 7.00,
                'hsn_sac_rules'          => null,
                'is_duties_precollected' => 1,
                'created_at'             => current_time('mysql'),
            ],
            [
                'country_code'           => 'GB',
                'state_zone'             => 'UK_HMRC',
                'tax_type'               => 'VAT',
                'standard_rate'          => 20.00,
                'reduced_rate'           => 5.00,
                'hsn_sac_rules'          => null,
                'is_duties_precollected' => 1,
                'created_at'             => current_time('mysql'),
            ],
            [
                'country_code'           => 'US',
                'state_zone'             => 'CA',
                'tax_type'               => 'US_NEXUS',
                'standard_rate'          => 7.25,
                'reduced_rate'           => 0.00,
                'hsn_sac_rules'          => null,
                'is_duties_precollected' => 0,
                'created_at'             => current_time('mysql'),
            ],
            [
                'country_code'           => 'US',
                'state_zone'             => 'NY',
                'tax_type'               => 'US_NEXUS',
                'standard_rate'          => 8.875,
                'reduced_rate'           => 0.00,
                'hsn_sac_rules'          => null,
                'is_duties_precollected' => 0,
                'created_at'             => current_time('mysql'),
            ],
        ];

        foreach ($jurisdictions as $item) {
            $wpdb->insert(
                $table,
                $item,
                ['%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s']
            );
        }
    }

    /**
     * Seed default behavioral customer segments (Sprint 04).
     *
     * @return void
     */
    public static function seed_default_segments(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_segments';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $segments = [
            [
                'name'         => 'VIP High Spenders',
                'slug'         => 'vip-high-spenders',
                'conditions'   => json_encode([
                    'operator' => 'OR',
                    'rules'    => [
                        ['field' => 'total_spend', 'operator' => 'greater_than_or_equal', 'value' => 1000.00],
                        ['field' => 'order_count', 'operator' => 'greater_than_or_equal', 'value' => 5],
                    ],
                ]),
                'member_count' => 0,
                'is_dynamic'   => 1,
                'created_at'   => current_time('mysql'),
            ],
            [
                'name'         => 'At-Risk 60+ Days Inactive',
                'slug'         => 'at-risk-inactive',
                'conditions'   => json_encode([
                    'operator' => 'AND',
                    'rules'    => [
                        ['field' => 'order_count', 'operator' => 'greater_than_or_equal', 'value' => 1],
                        ['field' => 'days_since_last_order', 'operator' => 'greater_than_or_equal', 'value' => 60],
                    ],
                ]),
                'member_count' => 0,
                'is_dynamic'   => 1,
                'created_at'   => current_time('mysql'),
            ],
            [
                'name'         => 'First-Time Buyers',
                'slug'         => 'first-time-buyers',
                'conditions'   => json_encode([
                    'operator' => 'AND',
                    'rules'    => [
                        ['field' => 'order_count', 'operator' => 'equals', 'value' => 1],
                    ],
                ]),
                'member_count' => 0,
                'is_dynamic'   => 1,
                'created_at'   => current_time('mysql'),
            ],
            [
                'name'         => 'Repeat Jewelry Collectors',
                'slug'         => 'repeat-jewelry-collectors',
                'conditions'   => json_encode([
                    'operator' => 'OR',
                    'rules'    => [
                        ['field' => 'tags', 'operator' => 'contains', 'value' => 'jewelry'],
                        ['field' => 'tags', 'operator' => 'contains', 'value' => 'collector'],
                        ['field' => 'order_count', 'operator' => 'greater_than_or_equal', 'value' => 3],
                    ],
                ]),
                'member_count' => 0,
                'is_dynamic'   => 1,
                'created_at'   => current_time('mysql'),
            ],
        ];

        foreach ($segments as $item) {
            $wpdb->insert(
                $table,
                $item,
                ['%s', '%s', '%s', '%d', '%d', '%s']
            );
        }
    }

    /**
     * Seed default Flow automation workflows (Sprint 04).
     *
     * @return void
     */
    public static function seed_default_flows(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_flow_workflows';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $flows = [
            [
                'title'           => 'VIP Tagging & Exclusive Welcome',
                'trigger_event'   => 'order.completed',
                'conditions'      => json_encode([
                    ['field' => 'order_total', 'operator' => 'greater_than_or_equal', 'value' => 1000.00],
                ]),
                'actions'         => json_encode([
                    ['action' => 'customer.add_tag', 'params' => ['tag' => 'vip']],
                    ['action' => 'notification.send_email', 'params' => ['template' => 'vip_welcome', 'subject' => 'Welcome to the SureSalez VIP Inner Circle']],
                ]),
                'status'          => 'active',
                'execution_count' => 0,
                'created_at'      => current_time('mysql'),
            ],
            [
                'title'           => 'High-Value Fraud Review Hold',
                'trigger_event'   => 'order.completed',
                'conditions'      => json_encode([
                    ['field' => 'order_total', 'operator' => 'greater_than_or_equal', 'value' => 5000.00],
                ]),
                'actions'         => json_encode([
                    ['action' => 'order.hold_fulfillment', 'params' => ['reason' => 'High-value threshold review']],
                    ['action' => 'webhook.dispatch', 'params' => ['url' => 'https://api.suresalez.internal/risk-review', 'channel' => 'security']],
                ]),
                'status'          => 'active',
                'execution_count' => 0,
                'created_at'      => current_time('mysql'),
            ],
            [
                'title'           => 'Abandoned Cart 1-Hour Recovery Alert',
                'trigger_event'   => 'cart.abandoned',
                'conditions'      => json_encode([
                    ['field' => 'cart_total', 'operator' => 'greater_than_or_equal', 'value' => 75.00],
                ]),
                'actions'         => json_encode([
                    ['action' => 'notification.send_email', 'params' => ['template' => 'cart_recovery', 'subject' => 'Complete your order before reserve expires']],
                    ['action' => 'notification.send_sms', 'params' => ['message' => 'Your SureSalez bag is reserved. Complete checkout now with your saved credit.']],
                ]),
                'status'          => 'active',
                'execution_count' => 0,
                'created_at'      => current_time('mysql'),
            ],
        ];

        foreach ($flows as $flow) {
            $wpdb->insert(
                $table,
                $flow,
                ['%s', '%s', '%s', '%s', '%s', '%d', '%s']
            );
        }
    }

    /**
     * Seed default shoppable video reels (Sprint 05).
     *
     * @return void
     */
    public static function seed_shoppable_videos(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_shoppable_videos';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $reels = [
            [
                'title'              => 'Behind the Craft: Royal Emerald Chronograph Assembly',
                'video_url'          => 'https://assets.suresalez.internal/videos/reels/emerald-craft.mp4',
                'thumbnail_url'      => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop',
                'duration'           => 28,
                'tagged_product_ids' => json_encode([
                    [
                        'product_id' => 101,
                        'title'      => 'Royal Emerald Automatic Chronograph',
                        'price'      => 899.00,
                        'x_pct'      => 50.0,
                        'y_pct'      => 65.0,
                        'timestamp'  => 4,
                    ],
                    [
                        'product_id' => 102,
                        'title'      => 'Italian Handcrafted Leather Travel Case',
                        'price'      => 120.00,
                        'x_pct'      => 32.0,
                        'y_pct'      => 78.0,
                        'timestamp'  => 12,
                    ],
                ]),
                'views_count'        => 14200,
                'clicks_count'       => 1840,
                'sort_order'         => 1,
                'status'             => 'published',
                'created_at'         => current_time('mysql'),
            ],
            [
                'title'              => 'Milanese Loop & Titanium Bezel: Water Resistance Test',
                'video_url'          => 'https://assets.suresalez.internal/videos/reels/milanese-water-test.mp4',
                'thumbnail_url'      => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=600&auto=format&fit=crop',
                'duration'           => 34,
                'tagged_product_ids' => json_encode([
                    [
                        'product_id' => 201,
                        'title'      => 'Milanese Magnetic Mesh Strap - Grade 5 Titanium',
                        'price'      => 96.85,
                        'x_pct'      => 45.0,
                        'y_pct'      => 55.0,
                        'timestamp'  => 6,
                    ],
                    [
                        'product_id' => 202,
                        'title'      => 'Ceramic Diver 300M Master Chronometer',
                        'price'      => 1250.00,
                        'x_pct'      => 58.0,
                        'y_pct'      => 48.0,
                        'timestamp'  => 15,
                    ],
                ]),
                'views_count'        => 9800,
                'clicks_count'       => 1120,
                'sort_order'         => 2,
                'status'             => 'published',
                'created_at'         => current_time('mysql'),
            ],
            [
                'title'              => 'Sunset Unboxing & Dial Macro Reflections',
                'video_url'          => 'https://assets.suresalez.internal/videos/reels/sunset-unboxing.mp4',
                'thumbnail_url'      => 'https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?w=600&auto=format&fit=crop',
                'duration'           => 22,
                'tagged_product_ids' => json_encode([
                    [
                        'product_id' => 301,
                        'title'      => 'Rose Gold Sunburst Dress Watch',
                        'price'      => 649.00,
                        'x_pct'      => 52.0,
                        'y_pct'      => 60.0,
                        'timestamp'  => 5,
                    ],
                ]),
                'views_count'        => 22500,
                'clicks_count'       => 3100,
                'sort_order'         => 3,
                'status'             => 'published',
                'created_at'         => current_time('mysql'),
            ],
        ];

        foreach ($reels as $reel) {
            $wpdb->insert(
                $table,
                $reel,
                ['%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s', '%s']
            );
        }
    }

    /**
     * Seed scheduled flash campaigns (Sprint 05).
     *
     * @return void
     */
    public static function seed_campaign_schedules(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_campaign_schedules';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $campaigns = [
            [
                'campaign_name' => 'Cyber Flash Drop & VIP Access',
                'start_time'    => '2026-10-01 00:00:00',
                'end_time'      => '2026-11-30 23:59:59',
                'theme_mods'    => json_encode([
                    'banner_text'       => '⚡ FLASH SALE: Extra 20% off with code CYBERVIP — Free Worldwide Express Shipping on all orders above $150',
                    'banner_cta'        => 'Shop The Drop',
                    'banner_cta_url'    => '/#suresalez-shoppable-reels-mount',
                    'banner_bg'         => '#0f172a',
                    'banner_text_color' => '#f8fafc',
                    'badge'             => 'LIMITED TIME',
                ]),
                'price_rules'   => json_encode([
                    'discount_pct' => 20,
                    'coupon'       => 'CYBERVIP',
                    'min_subtotal' => 150.00,
                ]),
                'status'        => 'active',
                'created_at'    => current_time('mysql'),
            ],
            [
                'campaign_name' => 'Holiday Luxury Gala 2026',
                'start_time'    => '2026-12-01 00:00:00',
                'end_time'      => '2026-12-31 23:59:59',
                'theme_mods'    => json_encode([
                    'banner_text'       => '✨ Holiday Gift Suite: Complimentary Diamond-Grade Gift Packaging & Velvet Travel Roll Included',
                    'banner_cta'        => 'Explore Gifts',
                    'banner_cta_url'    => '/shop',
                    'banner_bg'         => '#1e1b4b',
                    'banner_text_color' => '#fef08a',
                    'badge'             => 'HOLIDAY EXCLUSIVE',
                ]),
                'price_rules'   => json_encode([
                    'gift_threshold' => 300.00,
                ]),
                'status'        => 'scheduled',
                'created_at'    => current_time('mysql'),
            ],
        ];

        foreach ($campaigns as $camp) {
            $wpdb->insert(
                $table,
                $camp,
                ['%s', '%s', '%s', '%s', '%s', '%s', '%s']
            );
        }
    }

    /**
     * Seed initial BI analytics snapshots (Sprint 05).
     *
     * @return void
     */
    public static function seed_analytics_snapshots(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_analytics_snapshots';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $today = current_time('Y-m-d');
        $snapshots = [
            [
                'snapshot_date' => $today,
                'metric_key'    => 'net_revenue',
                'metric_value'  => 148520.0000,
                'dimensions'    => json_encode(['channel' => 'omnichannel', 'currency' => 'USD']),
                'created_at'    => current_time('mysql'),
            ],
            [
                'snapshot_date' => $today,
                'metric_key'    => 'gross_margin_pct',
                'metric_value'  => 64.8000,
                'dimensions'    => json_encode(['period' => 'trailing_30d', 'target' => 60.0]),
                'created_at'    => current_time('mysql'),
            ],
            [
                'snapshot_date' => $today,
                'metric_key'    => 'cart_to_detail_rate',
                'metric_value'  => 18.6500,
                'dimensions'    => json_encode(['benchmark' => 'luxury_ecommerce', 'industry_avg' => 10.5]),
                'created_at'    => current_time('mysql'),
            ],
            [
                'snapshot_date' => $today,
                'metric_key'    => 'channel_attribution',
                'metric_value'  => 42.5000,
                'dimensions'    => json_encode([
                    'organic'         => 35.2,
                    'shoppable_reels' => 26.4,
                    'ai_chat'         => 18.9,
                    'paid_social'     => 12.5,
                    'email'           => 7.0,
                ]),
                'created_at'    => current_time('mysql'),
            ],
        ];

        foreach ($snapshots as $snap) {
            $wpdb->insert(
                $table,
                $snap,
                ['%s', '%s', '%f', '%s', '%s']
            );
        }
    }

    /**
     * Seed sample order fulfillments & split shipments (Sprint 06).
     *
     * @return void
     */
    public static function seed_fulfillments(): void {
        global $wpdb;

        $table_f = $wpdb->prefix . 'suresalez_fulfillments';
        $table_i = $wpdb->prefix . 'suresalez_fulfillment_items';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_f}");
        if ($count > 0) {
            return;
        }

        $now = current_time('mysql');

        // Fulfillment 1: Order #98455 - Split Package 1 (US-EAST-01 Newark)
        $wpdb->insert(
            $table_f,
            [
                'order_id'           => 98455,
                'fulfillment_number' => 'FUL-98455-1',
                'location_id'        => 1,
                'carrier_name'       => 'FedEx Express',
                'tracking_number'    => 'FDX9823412039US',
                'tracking_url'       => 'https://www.fedex.com/fedextrack/?trknbr=FDX9823412039US',
                'status'             => 'shipped',
                'shipped_at'         => date('Y-m-d H:i:s', strtotime('-1 day')),
                'delivered_at'       => null,
                'created_at'         => $now,
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        $f1_id = (int) $wpdb->insert_id;
        $wpdb->insert(
            $table_i,
            [
                'fulfillment_id' => $f1_id,
                'order_item_id'  => 1011,
                'product_id'     => 101,
                'variation_id'   => 0,
                'quantity'       => 1,
            ],
            ['%d', '%d', '%d', '%d', '%d']
        );

        // Fulfillment 2: Order #98455 - Split Package 2 (EU-CENTRAL-01 Frankfurt)
        $wpdb->insert(
            $table_f,
            [
                'order_id'           => 98455,
                'fulfillment_number' => 'FUL-98455-2',
                'location_id'        => 4,
                'carrier_name'       => 'DHL Express',
                'tracking_number'    => 'DHL7712390492DE',
                'tracking_url'       => 'https://www.dhl.com/en/express/tracking.html?AWB=DHL7712390492DE',
                'status'             => 'in_transit',
                'shipped_at'         => date('Y-m-d H:i:s', strtotime('-12 hours')),
                'delivered_at'       => null,
                'created_at'         => $now,
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        $f2_id = (int) $wpdb->insert_id;
        $wpdb->insert(
            $table_i,
            [
                'fulfillment_id' => $f2_id,
                'order_item_id'  => 1012,
                'product_id'     => 102,
                'variation_id'   => 0,
                'quantity'       => 1,
            ],
            ['%d', '%d', '%d', '%d', '%d']
        );

        // Fulfillment 3: Order #98210 - Single Package (APAC-SOUTH-01 Singapore)
        $wpdb->insert(
            $table_f,
            [
                'order_id'           => 98210,
                'fulfillment_number' => 'FUL-98210-1',
                'location_id'        => 7,
                'carrier_name'       => 'BlueDart / DHL',
                'tracking_number'    => 'BD3309124488IN',
                'tracking_url'       => 'https://www.bluedart.com/tracking?track=BD3309124488IN',
                'status'             => 'delivered',
                'shipped_at'         => date('Y-m-d H:i:s', strtotime('-4 days')),
                'delivered_at'       => date('Y-m-d H:i:s', strtotime('-1 day')),
                'created_at'         => $now,
            ],
            ['%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        $f3_id = (int) $wpdb->insert_id;
        $wpdb->insert(
            $table_i,
            [
                'fulfillment_id' => $f3_id,
                'order_item_id'  => 9901,
                'product_id'     => 201,
                'variation_id'   => 0,
                'quantity'       => 2,
            ],
            ['%d', '%d', '%d', '%d', '%d']
        );
    }

    /**
     * Seed sample customer RMA requests and return inspection items (Sprint 06).
     *
     * @return void
     */
    public static function seed_rma_data(): void {
        global $wpdb;

        $table_r = $wpdb->prefix . 'suresalez_rma_requests';
        $table_i = $wpdb->prefix . 'suresalez_rma_items';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_r}");
        if ($count > 0) {
            return;
        }

        $now = current_time('mysql');

        // RMA 1: Order #98210 (Inspected & Approved for Store Credit)
        $wpdb->insert(
            $table_r,
            [
                'rma_number'       => 'RMA-20261012-001',
                'order_id'         => 98210,
                'customer_id'      => 1,
                'status'           => 'inspected',
                'return_type'      => 'store_credit',
                'reason_code'      => 'size_fit',
                'customer_notes'   => 'Strap is 20mm, needed 22mm for Apex watch. Exchanging for store credit.',
                'media_evidence'   => json_encode(['https://assets.suresalez.internal/rma/returns/strap-scale.jpg']),
                'return_label_url' => 'https://shipping.suresalez.internal/labels/rma-return-001.pdf',
                'created_at'       => date('Y-m-d H:i:s', strtotime('-2 days')),
                'updated_at'       => $now,
            ],
            ['%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        $rma1_id = (int) $wpdb->insert_id;
        $wpdb->insert(
            $table_i,
            [
                'rma_id'              => $rma1_id,
                'order_item_id'       => 9901,
                'product_id'          => 201,
                'quantity'            => 1,
                'inspection_status'   => 'pass',
                'restock_location_id' => 7,
                'refund_amount'       => 96.85,
            ],
            ['%d', '%d', '%d', '%d', '%s', '%d', '%f']
        );

        // RMA 2: Order #98455 (Requested for Direct Refund)
        $wpdb->insert(
            $table_r,
            [
                'rma_number'       => 'RMA-20261014-002',
                'order_id'         => 98455,
                'customer_id'      => 2,
                'status'           => 'requested',
                'return_type'      => 'refund',
                'reason_code'      => 'defective',
                'customer_notes'   => 'Small hairline finish mark on bezel upon initial unboxing.',
                'media_evidence'   => json_encode(['https://assets.suresalez.internal/rma/returns/bezel-macro.jpg']),
                'return_label_url' => 'https://shipping.suresalez.internal/labels/rma-return-002.pdf',
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            ['%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
        $rma2_id = (int) $wpdb->insert_id;
        $wpdb->insert(
            $table_i,
            [
                'rma_id'              => $rma2_id,
                'order_item_id'       => 1011,
                'product_id'          => 101,
                'quantity'            => 1,
                'inspection_status'   => 'pending',
                'restock_location_id' => 1,
                'refund_amount'       => 899.00,
            ],
            ['%d', '%d', '%d', '%d', '%s', '%d', '%f']
        );
    }

    /**
     * Seed dropship and print-on-demand supplier partners (Sprint 06).
     *
     * @return void
     */
    public static function seed_supplier_dropships(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_supplier_dropships';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $suppliers = [
            [
                'supplier_name'  => 'Atelier Artisan Leatherworks',
                'supplier_email' => 'dropship@atelierleather.internal',
                'webhook_url'    => 'https://api.atelierleather.internal/v1/orders/dispatch',
                'sku_prefix'     => 'POD-LTH-',
                'lead_time_days' => 3,
                'status'         => 'active',
                'created_at'     => current_time('mysql'),
            ],
            [
                'supplier_name'  => 'Apex Swiss Precision Horology',
                'supplier_email' => 'fulfillment@apexhorology.internal',
                'webhook_url'    => 'https://api.apexhorology.internal/edi/purchase-orders',
                'sku_prefix'     => 'DROP-APX-',
                'lead_time_days' => 2,
                'status'         => 'active',
                'created_at'     => current_time('mysql'),
            ],
            [
                'supplier_name'  => 'Milano Fine Mesh Foundry',
                'supplier_email' => 'logistics@milanomesh.internal',
                'webhook_url'    => 'https://api.milanomesh.internal/dispatch',
                'sku_prefix'     => 'POD-MSH-',
                'lead_time_days' => 4,
                'status'         => 'active',
                'created_at'     => current_time('mysql'),
            ],
        ];

        foreach ($suppliers as $sup) {
            $wpdb->insert(
                $table,
                $sup,
                ['%s', '%s', '%s', '%s', '%d', '%s', '%s']
            );
        }
    }

    /**
     * Seed sample B2B Corporate Accounts.
     *
     * @return void
     */
    public static function seed_b2b_companies(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_companies';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $companies = [
            [
                'company_name'       => 'Apex Chronometry Distributors Ltd',
                'company_tax_id'     => 'US-EIN-123456789',
                'status'             => 'approved',
                'credit_limit'       => 50000.00,
                'payment_terms'      => 'net_30',
                'approval_threshold' => 5000.00,
                'created_at'         => current_time('mysql'),
            ],
            [
                'company_name'       => 'Harrods Luxury Watch Boutiques',
                'company_tax_id'     => 'GB-VAT-998877665',
                'status'             => 'approved',
                'credit_limit'       => 120000.00,
                'payment_terms'      => 'net_60',
                'approval_threshold' => 10000.00,
                'created_at'         => current_time('mysql'),
            ],
            [
                'company_name'       => 'Pacific Horology Network',
                'company_tax_id'     => 'SG-UEN-20261199X',
                'status'             => 'pending',
                'credit_limit'       => 0.00,
                'payment_terms'      => 'prepaid',
                'approval_threshold' => 2500.00,
                'created_at'         => current_time('mysql'),
            ],
        ];

        foreach ($companies as $comp) {
            $wpdb->insert(
                $table,
                $comp,
                ['%s', '%s', '%s', '%f', '%s', '%f', '%s']
            );
        }
    }

    /**
     * Seed B2B Company User Seats and Permissions.
     *
     * @return void
     */
    public static function seed_b2b_users(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_users';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $users = [
            [
                'company_id'               => 1,
                'user_id'                  => 10,
                'role'                     => 'admin',
                'spending_limit_per_order' => 0.00,
                'is_active'                => 1,
                'created_at'               => current_time('mysql'),
            ],
            [
                'company_id'               => 1,
                'user_id'                  => 11,
                'role'                     => 'purchaser',
                'spending_limit_per_order' => 2500.00,
                'is_active'                => 1,
                'created_at'               => current_time('mysql'),
            ],
            [
                'company_id'               => 2,
                'user_id'                  => 12,
                'role'                     => 'admin',
                'spending_limit_per_order' => 0.00,
                'is_active'                => 1,
                'created_at'               => current_time('mysql'),
            ],
        ];

        foreach ($users as $u) {
            $wpdb->insert(
                $table,
                $u,
                ['%d', '%d', '%s', '%f', '%d', '%s']
            );
        }
    }

    /**
     * Seed Wholesale Tiered Price Lists.
     *
     * @return void
     */
    public static function seed_b2b_pricelists(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_pricelists';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $pricelists = [
            [
                'name'          => 'Authorized Global Wholesale Tier 1',
                'company_id'    => 0,
                'discount_type' => 'percent_off',
                'pricing_rules' => wp_json_encode([
                    'LUM-CHRONO-01' => [
                        'msrp'   => 899.00,
                        'moq'    => 2,
                        'tiers'  => [
                            ['min_qty' => 1, 'max_qty' => 9, 'price' => 719.20, 'discount_pct' => 20],
                            ['min_qty' => 10, 'max_qty' => 49, 'price' => 629.30, 'discount_pct' => 30],
                            ['min_qty' => 50, 'max_qty' => 9999, 'price' => 539.40, 'discount_pct' => 40],
                        ],
                    ],
                    'LUM-STRAP-BLK' => [
                        'msrp'   => 95.00,
                        'moq'    => 5,
                        'tiers'  => [
                            ['min_qty' => 1, 'max_qty' => 19, 'price' => 76.00, 'discount_pct' => 20],
                            ['min_qty' => 20, 'max_qty' => 9999, 'price' => 57.00, 'discount_pct' => 40],
                        ],
                    ],
                    'LUM-TOOL-01'   => [
                        'msrp'   => 45.00,
                        'moq'    => 10,
                        'tiers'  => [
                            ['min_qty' => 1, 'max_qty' => 49, 'price' => 36.00, 'discount_pct' => 20],
                            ['min_qty' => 50, 'max_qty' => 9999, 'price' => 27.00, 'discount_pct' => 40],
                        ],
                    ],
                ]),
                'status'        => 'active',
                'created_at'    => current_time('mysql'),
            ],
            [
                'name'          => 'Harrods Knightsbridge Exclusive Contract',
                'company_id'    => 2,
                'discount_type' => 'fixed_price',
                'pricing_rules' => wp_json_encode([
                    'LUM-CHRONO-01' => [
                        'msrp'   => 899.00,
                        'moq'    => 10,
                        'tiers'  => [
                            ['min_qty' => 10, 'max_qty' => 9999, 'price' => 495.00, 'discount_pct' => 45],
                        ],
                    ],
                ]),
                'status'        => 'active',
                'created_at'    => current_time('mysql'),
            ],
        ];

        foreach ($pricelists as $pl) {
            $wpdb->insert(
                $table,
                $pl,
                ['%s', '%d', '%s', '%s', '%s', '%s']
            );
        }
    }

    /**
     * Seed Sample B2B Quotes (RFQs).
     *
     * @return void
     */
    public static function seed_b2b_quotes(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_b2b_quotes';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $quotes = [
            [
                'quote_number'    => 'RFQ-20261014-1001',
                'company_id'      => 1,
                'customer_id'     => 11,
                'items'           => wp_json_encode([
                    ['sku' => 'LUM-CHRONO-01', 'title' => 'Luminor Chrono Automatic', 'qty' => 25, 'requested_price' => 550.00, 'quoted_price' => 580.00],
                    ['sku' => 'LUM-STRAP-BLK', 'title' => 'Horween Leather Strap 22mm', 'qty' => 50, 'requested_price' => 45.00, 'quoted_price' => 50.00],
                ]),
                'subtotal'        => 17000.00,
                'freight_amount'  => 350.00,
                'total_quoted'    => 17350.00,
                'status'          => 'counter_offered',
                'quote_token'     => 'rfq_tok_99882211',
                'notes'           => 'Requesting 25 chronographs and 50 straps for autumn showcase.',
                'counter_notes'   => 'Offered $580/unit on chronos with expedited air courier freight included.',
                'expiration_date' => date('Y-m-d H:i:s', strtotime('+14 days')),
                'created_at'      => current_time('mysql'),
            ],
            [
                'quote_number'    => 'RFQ-20261014-1002',
                'company_id'      => 2,
                'customer_id'     => 12,
                'items'           => wp_json_encode([
                    ['sku' => 'LUM-CHRONO-01', 'title' => 'Luminor Chrono Automatic', 'qty' => 100, 'requested_price' => 500.00, 'quoted_price' => 520.00],
                ]),
                'subtotal'        => 52000.00,
                'freight_amount'  => 0.00,
                'total_quoted'    => 52000.00,
                'status'          => 'submitted',
                'quote_token'     => 'rfq_tok_77441133',
                'notes'           => 'Bulk stocking order for Knightsbridge flagship boutique.',
                'counter_notes'   => '',
                'expiration_date' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'created_at'      => current_time('mysql'),
            ],
        ];

        foreach ($quotes as $q) {
            $wpdb->insert(
                $table,
                $q,
                ['%s', '%d', '%d', '%s', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s']
            );
        }
    }

    /**
     * Seed Initial POS Registers.
     *
     * @return void
     */
    public static function seed_pos_registers(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_pos_registers';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $registers = [
            [
                'location_id'     => 1, // US-EAST-01
                'register_name'   => 'Register 01 - Manhattan 5th Ave Boutique',
                'cashier_user_id' => 5,
                'opening_cash'    => 500.00,
                'closing_cash'    => 0.00,
                'status'          => 'open',
                'opened_at'       => current_time('mysql'),
                'closed_at'       => null,
            ],
            [
                'location_id'     => 5, // EU-WEST-01
                'register_name'   => 'Register 01 - London Bond Street Flagship',
                'cashier_user_id' => 6,
                'opening_cash'    => 400.00,
                'closing_cash'    => 0.00,
                'status'          => 'open',
                'opened_at'       => current_time('mysql'),
                'closed_at'       => null,
            ],
        ];

        foreach ($registers as $r) {
            $wpdb->insert(
                $table,
                $r,
                ['%d', '%s', '%d', '%f', '%f', '%s', '%s', '%s']
            );
        }
    }

    /**
     * Seed Marketplace Vendors.
     *
     * @return void
     */
    public static function seed_marketplace_vendors(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_marketplace_vendors';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $vendors = [
            [
                'user_id'               => 20,
                'store_name'            => 'Horology Master Atelier',
                'slug'                  => 'horology-master',
                'commission_rate'       => 15.00,
                'balance_available'     => 3450.00,
                'balance_pending'       => 820.00,
                'payout_method_details' => wp_json_encode([
                    'type'         => 'stripe_connect',
                    'account_id'   => 'acct_1HoroMasterAtelier99',
                    'currency'     => 'USD',
                ]),
                'status'                => 'active',
                'created_at'            => current_time('mysql'),
            ],
            [
                'user_id'               => 21,
                'store_name'            => 'Bespoke Leatherworks Milan',
                'slug'                  => 'bespoke-leather',
                'commission_rate'       => 12.50,
                'balance_available'     => 1890.00,
                'balance_pending'       => 340.00,
                'payout_method_details' => wp_json_encode([
                    'type'         => 'bank_iban',
                    'iban'         => 'IT60X0542811101000000123456',
                    'currency'     => 'EUR',
                ]),
                'status'                => 'active',
                'created_at'            => current_time('mysql'),
            ],
        ];

        foreach ($vendors as $v) {
            $wpdb->insert(
                $table,
                $v,
                ['%d', '%s', '%s', '%f', '%f', '%f', '%s', '%s', '%s']
            );
        }
    }

    /**
     * Seed Vendor Commissions.
     *
     * @return void
     */
    public static function seed_vendor_commissions(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_vendor_commissions';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $commissions = [
            [
                'order_id'          => 501,
                'vendor_id'         => 1,
                'line_item_id'      => 101,
                'gross_amount'      => 899.00,
                'commission_fee'    => 134.85,
                'net_vendor_payout' => 764.15,
                'status'            => 'available',
                'created_at'        => current_time('mysql'),
            ],
            [
                'order_id'          => 502,
                'vendor_id'         => 2,
                'line_item_id'      => 102,
                'gross_amount'      => 190.00,
                'commission_fee'    => 23.75,
                'net_vendor_payout' => 166.25,
                'status'            => 'pending',
                'created_at'        => current_time('mysql'),
            ],
        ];

        foreach ($commissions as $c) {
            $wpdb->insert(
                $table,
                $c,
                ['%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s']
            );
        }
    }

    /**
     * Seed Sample Recurring Subscriptions.
     *
     * @return void
     */
    public static function seed_subscriptions(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_subscriptions';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $subscriptions = [
            [
                'subscription_token'  => 'sub_tok_lux_8811',
                'customer_id'         => 10,
                'product_id'          => 101,
                'variation_id'        => 0,
                'billing_interval'    => 'monthly',
                'discount_percentage' => 10.00,
                'next_renewal_date'   => date('Y-m-d H:i:s', strtotime('+30 days')),
                'status'              => 'active',
                'card_token_hash'     => 'tok_hash_visa_4242',
                'created_at'          => current_time('mysql'),
            ],
            [
                'subscription_token'  => 'sub_tok_strap_9922',
                'customer_id'         => 11,
                'product_id'          => 102,
                'variation_id'        => 0,
                'billing_interval'    => 'quarterly',
                'discount_percentage' => 15.00,
                'next_renewal_date'   => date('Y-m-d H:i:s', strtotime('+90 days')),
                'status'              => 'active',
                'card_token_hash'     => 'tok_hash_mc_5555',
                'created_at'          => current_time('mysql'),
            ],
        ];

        foreach ($subscriptions as $s) {
            $wpdb->insert(
                $table,
                $s,
                ['%s', '%d', '%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s']
            );
        }
    }

    /**
     * Seed Sample Loyalty Points & Transactions (Sprint 09).
     *
     * @return void
     */
    public static function seed_loyalty_points(): void {
        global $wpdb;

        $table_points = $wpdb->prefix . 'suresalez_loyalty_points';
        $table_tx = $wpdb->prefix . 'suresalez_points_transactions';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_points}");
        if ($count > 0) {
            return;
        }

        $loyalty_data = [
            [
                'user_id'         => 10,
                'points_balance'  => 1250,
                'lifetime_points' => 2500,
                'tier_level'      => 'gold',
                'updated_at'      => current_time('mysql'),
            ],
            [
                'user_id'         => 11,
                'points_balance'  => 450,
                'lifetime_points' => 950,
                'tier_level'      => 'silver',
                'updated_at'      => current_time('mysql'),
            ],
            [
                'user_id'         => 12,
                'points_balance'  => 100,
                'lifetime_points' => 100,
                'tier_level'      => 'bronze',
                'updated_at'      => current_time('mysql'),
            ],
        ];

        foreach ($loyalty_data as $l) {
            $wpdb->insert(
                $table_points,
                $l,
                ['%d', '%d', '%d', '%s', '%s']
            );
        }

        $transactions = [
            [
                'user_id'            => 10,
                'points'             => 500,
                'type'               => 'earned',
                'description'        => 'Points earned for order #401 (Gold 1.5x multiplier)',
                'reference_order_id' => 401,
                'created_at'         => current_time('mysql'),
            ],
            [
                'user_id'            => 10,
                'points'             => -200,
                'type'               => 'redeemed',
                'description'        => 'Redeemed at checkout on order #420 ($10 discount)',
                'reference_order_id' => 420,
                'created_at'         => current_time('mysql'),
            ],
            [
                'user_id'            => 11,
                'points'             => 250,
                'type'               => 'bonus',
                'description'        => 'Birthday promotional bonus points',
                'reference_order_id' => 0,
                'created_at'         => current_time('mysql'),
            ],
        ];

        foreach ($transactions as $t) {
            $wpdb->insert(
                $table_tx,
                $t,
                ['%d', '%d', '%s', '%s', '%d', '%s']
            );
        }
    }

    /**
     * Seed Sample Referrals (Sprint 09).
     *
     * @return void
     */
    public static function seed_referrals(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_referrals';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $referrals = [
            [
                'referrer_user_id' => 10,
                'referral_code'    => 'SURE-VIP10',
                'referred_user_id' => 12,
                'reward_status'    => 'rewarded',
                'order_id'         => 425,
                'created_at'       => current_time('mysql'),
            ],
            [
                'referrer_user_id' => 11,
                'referral_code'    => 'SURE-SILVER11',
                'referred_user_id' => 0,
                'reward_status'    => 'pending',
                'order_id'         => 0,
                'created_at'       => current_time('mysql'),
            ],
        ];

        foreach ($referrals as $r) {
            $wpdb->insert(
                $table,
                $r,
                ['%d', '%s', '%d', '%s', '%d', '%s']
            );
        }
    }

    /**
     * Seed Common SEO 301 Redirects (Sprint 09).
     *
     * @return void
     */
    public static function seed_seo_redirects(): void {
        global $wpdb;

        $table = $wpdb->prefix . 'suresalez_seo_redirects';

        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        if ($count > 0) {
            return;
        }

        $redirects = [
            [
                'source_url'       => '/old-product/watch-series-1',
                'target_url'       => '/product/classic-chronograph',
                'status_code'      => 301,
                'hit_count'        => 42,
                'last_accessed_at' => current_time('mysql'),
                'created_at'       => current_time('mysql'),
            ],
            [
                'source_url'       => '/catalog/mens-watches',
                'target_url'       => '/category/watches',
                'status_code'      => 301,
                'hit_count'        => 128,
                'last_accessed_at' => current_time('mysql'),
                'created_at'       => current_time('mysql'),
            ],
            [
                'source_url'       => '/deals/black-friday-2023',
                'target_url'       => '/deals',
                'status_code'      => 301,
                'hit_count'        => 19,
                'last_accessed_at' => current_time('mysql'),
                'created_at'       => current_time('mysql'),
            ],
        ];

        foreach ($redirects as $rd) {
            $wpdb->insert(
                $table,
                $rd,
                ['%s', '%s', '%d', '%d', '%s', '%s']
            );
        }
    }
}

