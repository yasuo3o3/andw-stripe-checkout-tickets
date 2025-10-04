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
        global ;
              = self::get_table_name();
         = ->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

         = "CREATE TABLE {} (
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
        ) {};";

        dbDelta(  );
        update_option( 'andw_sct_db_version', ANDW_SCT_DB_VERSION );
    }

    /**
     * Drops the log table.
     */
    public static function uninstall() : void {
        global ;
         = self::get_table_name();
        ->query( "DROP TABLE IF EXISTS {}" );
        delete_option( 'andw_sct_db_version' );
    }

    /**
     * Inserts a log entry.
     */
    public static function insert( array  ) : bool {
        global ;
         = [
            'event_id'    => '',
            'type'        => '',
            'session_id'  => '',
            'customer_id' => '',
            'email'       => '',
            'amount_total'=> 0,
            'currency'    => '',
            'created_at'  => current_time( 'mysql', 1 ),
        ];
         = wp_parse_args( ,  );
        if ( empty( ['event_id'] ) ) {
            return false;
        }
        return (bool) ->insert(
            self::get_table_name(),
            [
                'event_id'    => sanitize_text_field( ['event_id'] ),
                'type'        => sanitize_text_field( ['type'] ),
                'session_id'  => sanitize_text_field( ['session_id'] ),
                'customer_id' => sanitize_text_field( ['customer_id'] ),
                'email'       => sanitize_email( ['email'] ),
                'amount_total'=> absint( ['amount_total'] ),
                'currency'    => sanitize_text_field( strtolower( ['currency'] ) ),
                'created_at'  => gmdate( 'Y-m-d H:i:s', strtotime( ['created_at'] ) ),
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
    public static function event_exists( string  ) : bool {
        global ;
         = self::get_table_name();
         = ->get_var( ->prepare( "SELECT id FROM {} WHERE event_id = %s LIMIT 1",  ) );
        return ! empty(  );
    }

    /**
     * Returns log entries for a given customer ID.
     */
    public static function get_by_customer( string , int  = 20 ) : array {
        global ;
         = self::get_table_name();
        return ->get_results(
            ->prepare(
                "SELECT * FROM {} WHERE customer_id = %s ORDER BY created_at DESC LIMIT %d",
                ,
                absint(  )
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Returns log entries for a given email.
     */
    public static function get_by_email( string , int  = 20 ) : array {
        global ;
         = self::get_table_name();
        return ->get_results(
            ->prepare(
                "SELECT * FROM {} WHERE email = %s ORDER BY created_at DESC LIMIT %d",
                ,
                absint(  )
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * Table name helper.
     */
    public static function get_table_name() : string {
        global ;
        return ->prefix . 'andw_sct_logs';
    }
}
