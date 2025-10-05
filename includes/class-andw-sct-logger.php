<?php
/**
 * Database logger for Stripe events.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Logger {

    /**
     * Ensures log table exists.
     */
    public static function maybe_install() : void {
        global $wpdb;
        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id varchar(191) NOT NULL,
            type varchar(100) NOT NULL,
            session_id varchar(191) NOT NULL,
            customer_id varchar(191) DEFAULT '' NOT NULL,
            email varchar(191) DEFAULT '' NOT NULL,
            amount_total bigint(20) DEFAULT 0 NOT NULL,
            currency varchar(10) DEFAULT '' NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY event_id (event_id)
        ) {$charset_collate};";

        dbDelta( $sql );
        update_option( 'andw_sct_db_version', ANDW_SCT_DB_VERSION );
    }

    /**
     * Drops the log table.
     */
    public static function uninstall() : void {
        global $wpdb;
        $table = self::get_table_name();
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
        delete_option( 'andw_sct_db_version' );
    }

    /**
     * Inserts a log entry.
     */
    public static function insert( array $entry ) : bool {
        global $wpdb;
        $defaults = [
            'event_id'     => '',
            'type'         => '',
            'session_id'   => '',
            'customer_id'  => '',
            'email'        => '',
            'amount_total' => 0,
            'currency'     => '',
            'created_at'   => current_time( 'mysql', 1 ),
        ];
        $entry = wp_parse_args( $entry, $defaults );
        if ( empty( $entry['event_id'] ) ) {
            return false;
        }
        return (bool) $wpdb->insert(
            self::get_table_name(),
            [
                'event_id'     => sanitize_text_field( $entry['event_id'] ),
                'type'         => sanitize_text_field( $entry['type'] ),
                'session_id'   => sanitize_text_field( $entry['session_id'] ),
                'customer_id'  => sanitize_text_field( $entry['customer_id'] ),
                'email'        => sanitize_email( $entry['email'] ),
                'amount_total' => absint( $entry['amount_total'] ),
                'currency'     => sanitize_text_field( strtolower( $entry['currency'] ) ),
                'created_at'   => gmdate( 'Y-m-d H:i:s', strtotime( $entry['created_at'] ) ),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
            ]
        );
    }

    /**
     * Determines if an event has already been logged.
     */
    public static function event_exists( string $event_id ) : bool {
        global $wpdb;
        $table = self::get_table_name();
        $sql   = $wpdb->prepare( "SELECT id FROM {$table} WHERE event_id = %s LIMIT 1", $event_id );
        return (bool) $wpdb->get_var( $sql );
    }

    /**
     * Returns log entries for a given customer ID.
     */
    public static function get_by_customer( string $customer_id, int $limit = 20 ) : array {
        global $wpdb;
        $table = self::get_table_name();
        $sql   = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE customer_id = %s ORDER BY created_at DESC LIMIT %d",
            $customer_id,
            absint( $limit )
        );
        return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
    }

    /**
     * Returns log entries for a given email.
     */
    public static function get_by_email( string $email, int $limit = 20 ) : array {
        global $wpdb;
        $table = self::get_table_name();
        $sql   = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE email = %s ORDER BY created_at DESC LIMIT %d",
            $email,
            absint( $limit )
        );
        return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
    }

    /**
     * Table name helper.
     */
    public static function get_table_name() : string {
        global $wpdb;
        return $wpdb->prefix . 'andw_sct_logs';
    }
}
