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
    public const DB_VERSION = '1.3.0';

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
}
