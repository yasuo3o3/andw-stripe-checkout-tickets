<?php
/**
 * Bootstrap class for the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Plugin {

    private static ?Andw_Sct_Plugin  = null;

    private Andw_Sct_Admin ;

    private Andw_Sct_Checkout ;

    private Andw_Sct_Frontend ;

    private Andw_Sct_Registration ;

    private Andw_Sct_Webhook ;

    /**
     * Returns the singleton instance.
     */
    public static function instance() : Andw_Sct_Plugin {
        if ( null === self:: ) {
            self:: = new self();
        }
        return self::;
    }

    /**
     * Plugin constructor.
     */
    private function __construct() {
        ->checkout     = new Andw_Sct_Checkout();
        ->registration = new Andw_Sct_Registration( ->checkout );
        ->frontend     = new Andw_Sct_Frontend( ->checkout, ->registration );
        ->admin        = new Andw_Sct_Admin();
        ->webhook      = new Andw_Sct_Webhook();

        add_action( 'init', [ , 'maybe_upgrade_db' ] );
    }

    /**
     * Creates database structures and stores version.
     */
    public static function activate() : void {
        Andw_Sct_Logger::maybe_install();
        if ( ! get_option( 'andw_sct_version' ) ) {
            update_option( 'andw_sct_version', ANDW_SCT_VERSION );
        }
    }

    /**
     * Deactivation hook placeholder.
     */
    public static function deactivate() : void {
        // No scheduled events yet.
    }

    /**
     * Ensures database schema is up-to-date.
     */
    public function maybe_upgrade_db() : void {
         = get_option( 'andw_sct_db_version' );
        if ( version_compare( (string) , ANDW_SCT_DB_VERSION, '<' ) ) {
            Andw_Sct_Logger::maybe_install();
        }
    }
}
