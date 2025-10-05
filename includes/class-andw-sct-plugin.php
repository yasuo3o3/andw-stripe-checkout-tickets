<?php
/**
 * Bootstrap class for the plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Plugin {

    private static ?Andw_Sct_Plugin $instance = null;

    private Andw_Sct_Admin $admin;

    private Andw_Sct_Checkout $checkout;

    private Andw_Sct_Frontend $frontend;

    private Andw_Sct_Registration $registration;

    private Andw_Sct_Webhook $webhook;

    /**
     * Returns the singleton instance.
     */
    public static function instance() : Andw_Sct_Plugin {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Plugin constructor.
     */
    private function __construct() {
        $this->checkout     = new Andw_Sct_Checkout();
        $this->registration = new Andw_Sct_Registration( $this->checkout );
        $this->frontend     = new Andw_Sct_Frontend( $this->checkout, $this->registration );
        $this->admin        = new Andw_Sct_Admin();
        $this->webhook      = new Andw_Sct_Webhook();

        add_action( 'init', [ $this, 'maybe_upgrade_db' ] );
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
        $current_version = get_option( 'andw_sct_db_version' );
        if ( version_compare( (string) $current_version, ANDW_SCT_DB_VERSION, '<' ) ) {
            Andw_Sct_Logger::maybe_install();
        }
    }
}
