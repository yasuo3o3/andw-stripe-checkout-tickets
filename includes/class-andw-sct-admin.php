<?php
/**
 * Admin page handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    public function register_menu() : void {
        add_menu_page(
            __( 'andW Tickets', 'andw-stripe-checkout-tickets' ),
            __( 'andW Tickets', 'andw-stripe-checkout-tickets' ),
            'manage_options',
            'andw-sct',
            [ $this, 'render_page' ],
            'dashicons-tickets-alt'
        );
    }

    public function register_settings() : void {
        register_setting( 'andw_sct_settings', ANDW_SCT_OPTION_KEY, [ $this, 'sanitize' ] );
    }

    public function sanitize( array $input ) : array {
        return Andw_Sct_Settings::sanitize( $input );
    }

    public function enqueue_assets( string $hook_suffix ) : void {
        if ( 'toplevel_page_andw-sct' !== $hook_suffix ) {
            return;
        }
        wp_enqueue_style( 'andw-sct-admin', ANDW_SCT_PLUGIN_URL . 'assets/css/admin.css', [], ANDW_SCT_VERSION );
    }

    public function render_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'andw-stripe-checkout-tickets' ) );
        }

        $settings           = Andw_Sct_Settings::get_settings();
        $sku_price_map      = $settings['sku_price_map'] ?? [];
        $button_groups_text = $this->format_button_groups( $settings['button_groups'] ?? [] );
        $environment_checks = $this->get_environment_checks();

        include ANDW_SCT_PLUGIN_DIR . 'views/admin-page.php';
    }

    private function format_button_groups( array $groups ) : string {
        $lines = [];
        foreach ( $groups as $group_slug => $buttons ) {
            if ( empty( $buttons ) ) {
                $lines[] = sprintf( '%s|%s|%s|%d|%s', $group_slug, '', '', 1, 'false' );
                continue;
            }
            foreach ( $buttons as $button ) {
                $lines[] = sprintf(
                    '%s|%s|%s|%d|%s',
                    $group_slug,
                    $button['sku'] ?? '',
                    $button['label'] ?? '',
                    isset( $button['qty'] ) ? (int) $button['qty'] : 1,
                    ! empty( $button['require_login'] ) ? 'true' : 'false'
                );
            }
        }

        return implode( "\n", $lines );
    }

    private function get_environment_checks() : array {
        return [
            'php_version'    => [
                'label'  => __( 'PHP version (requires 8.1+)', 'andw-stripe-checkout-tickets' ),
                'status' => version_compare( PHP_VERSION, '8.1', '>=' ),
                'value'  => PHP_VERSION,
            ],
            'curl'           => [
                'label'  => __( 'cURL extension', 'andw-stripe-checkout-tickets' ),
                'status' => function_exists( 'curl_version' ),
                'value'  => function_exists( 'curl_version' ) ? __( 'Available', 'andw-stripe-checkout-tickets' ) : __( 'Not installed', 'andw-stripe-checkout-tickets' ),
            ],
            'rest_available' => [
                'label'  => __( 'WP HTTP API', 'andw-stripe-checkout-tickets' ),
                'status' => function_exists( 'wp_remote_post' ),
                'value'  => function_exists( 'wp_remote_post' ) ? __( 'Available', 'andw-stripe-checkout-tickets' ) : __( 'Unavailable', 'andw-stripe-checkout-tickets' ),
            ],
            'https'          => [
                'label'  => __( 'Site URL (HTTPS)', 'andw-stripe-checkout-tickets' ),
                'status' => str_starts_with( get_site_url(), 'https://' ),
                'value'  => get_site_url(),
            ],
        ];
    }
}
