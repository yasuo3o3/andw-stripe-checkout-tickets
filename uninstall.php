<?php
/**
 * Uninstall routines for andW Stripe Checkout Tickets.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

require_once __DIR__ . '/includes/class-andw-sct-logger.php';
require_once __DIR__ . '/includes/class-andw-sct-settings.php';

if ( ! defined( 'ANDW_SCT_OPTION_KEY' ) ) {
    define( 'ANDW_SCT_OPTION_KEY', 'andw_sct_settings' );
}

if ( ! defined( 'ANDW_SCT_DB_VERSION' ) ) {
    define( 'ANDW_SCT_DB_VERSION', '1.0.0' );
}

Andw_Sct_Logger::uninstall();

delete_option( ANDW_SCT_OPTION_KEY );
delete_option( 'andw_sct_version' );
delete_option( 'andw_sct_db_version' );

global ;
->query( ->prepare( "DELETE FROM {->usermeta} WHERE meta_key = %s", 'andw_sct_stripe_customer_id' ) );
