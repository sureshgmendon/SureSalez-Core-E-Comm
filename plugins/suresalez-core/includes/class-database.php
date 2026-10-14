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
    public const DB_VERSION = '1.0.0';

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

        update_option(self::DB_VERSION_KEY, self::DB_VERSION);

        // Seed 10 warehouse fulfillment locations
        self::seed_inventory_locations();
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
}
