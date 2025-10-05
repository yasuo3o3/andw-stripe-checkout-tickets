<?php
/**
 * Plugin Name: andW Stripe Checkout Tickets
 * Plugin URI: https://yasuo-o.xyz/
 * Description: Stripe Checkoutを用いてチケット販売と簡易ユーザー登録導線を提供するプラグイン。
 * Version: 0.0.1
 * Author: yasuo3o3
 * Author URI: https://yasuo-o.xyz/
 * Text Domain: andw-stripe-checkout-tickets
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.5
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const ANDW_SCT_VERSION      = '0.0.1';
const ANDW_SCT_PLUGIN_FILE  = __FILE__;
const ANDW_SCT_PLUGIN_DIR   = __DIR__ . '/';
define( 'ANDW_SCT_PLUGIN_URL', plugin_dir_url( ANDW_SCT_PLUGIN_FILE ) );
const ANDW_SCT_OPTION_KEY   = 'andw_sct_settings';
const ANDW_SCT_DB_VERSION   = '1.0.0';

require_once ANDW_SCT_PLUGIN_DIR . 'includes/class-andw-sct-settings.php';
require_once ANDW_SCT_PLUGIN_DIR . 'includes/class-andw-sct-logger.php';
require_once ANDW_SCT_PLUGIN_DIR . 'includes/class-andw-sct-plugin.php';
require_once ANDW_SCT_PLUGIN_DIR . 'includes/class-andw-sct-admin.php';
require_once ANDW_SCT_PLUGIN_DIR . 'includes/class-andw-sct-checkout.php';
require_once ANDW_SCT_PLUGIN_DIR . 'includes/class-andw-sct-frontend.php';
require_once ANDW_SCT_PLUGIN_DIR . 'includes/class-andw-sct-registration.php';
require_once ANDW_SCT_PLUGIN_DIR . 'includes/class-andw-sct-webhook.php';

/**
 * Returns the plugin bootstrap instance.
 */
function andw_sct_plugin() : Andw_Sct_Plugin {
    return Andw_Sct_Plugin::instance();
}

register_activation_hook( ANDW_SCT_PLUGIN_FILE, [ 'Andw_Sct_Plugin', 'activate' ] );

register_deactivation_hook( ANDW_SCT_PLUGIN_FILE, [ 'Andw_Sct_Plugin', 'deactivate' ] );

add_action( 'plugins_loaded', static function () {
    andw_sct_plugin();
} );


