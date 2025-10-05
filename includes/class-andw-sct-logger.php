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
        $table = esc_sql( self::get_table_name() );
        $wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Schema cleanup on uninstall.
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

        $result = (bool) $wpdb->insert(
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

        if ( $result ) {
            wp_cache_delete( 'event_' . md5( $entry['event_id'] ), 'andw_sct_logger' );

            if ( ! empty( $entry['customer_id'] ) ) {
                wp_cache_delete( 'customer_' . md5( $entry['customer_id'] ) . '_20', 'andw_sct_logger' );
            }

            if ( ! empty( $entry['email'] ) ) {
                wp_cache_delete( 'email_' . md5( strtolower( $entry['email'] ) ) . '_20', 'andw_sct_logger' );
            }
        }

        return $result;
    }

    /**
     * Determines if an event has already been logged.
     */
    public static function event_exists( string $event_id ) : bool {
        $cache_key = 'event_' . md5( $event_id );
        $cached    = wp_cache_get( $cache_key, 'andw_sct_logger' );
        if ( false !== $cached ) {
            return (bool) $cached;
        }

        global $wpdb;
        $table = esc_sql( self::get_table_name() );
        $found = (bool) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE event_id = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe.
                $event_id
            )
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        wp_cache_set( $cache_key, $found ? 1 : 0, 'andw_sct_logger', HOUR_IN_SECONDS );

        return $found;
    }

    /**
     * Returns log entries for a given customer ID.
     */
    public static function get_by_customer( string $customer_id, int $limit = 20 ) : array {
        $cache_key = 'customer_' . md5( $customer_id ) . '_' . absint( $limit );
        $cached    = wp_cache_get( $cache_key, 'andw_sct_logger' );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table   = esc_sql( self::get_table_name() );
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE customer_id = %s ORDER BY created_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe.
                $customer_id,
                absint( $limit )
            ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        $results = $results ?: [];
        wp_cache_set( $cache_key, $results, 'andw_sct_logger', HOUR_IN_SECONDS );

        return $results;
    }

    /**
     * Returns log entries for a given email.
     */
    public static function get_by_email( string $email, int $limit = 20 ) : array {
        $normalized = strtolower( $email );
        $cache_key  = 'email_' . md5( $normalized ) . '_' . absint( $limit );
        $cached     = wp_cache_get( $cache_key, 'andw_sct_logger' );
        if ( false !== $cached ) {
            return $cached;
        }

        global $wpdb;
        $table   = esc_sql( self::get_table_name() );
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE email = %s ORDER BY created_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe.
                $normalized,
                absint( $limit )
            ),
            ARRAY_A
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

        $results = $results ?: [];
        wp_cache_set( $cache_key, $results, 'andw_sct_logger', HOUR_IN_SECONDS );

        return $results;
    }

    /**
     * Table name helper.
     */
    public static function get_table_name() : string {
        global $wpdb;
        return $wpdb->prefix . 'andw_sct_logs';
    }
}
