<?php
/**
 * Admin page handler.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Admin {

    public function __construct() {
        add_action( 'admin_menu', [ , 'register_menu' ] );
        add_action( 'admin_init', [ , 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ , 'enqueue_assets' ] );
    }

    public function register_menu() : void {
        add_menu_page(
            __( 'andW Tickets', 'andw-sct' ),
            __( 'andW Tickets', 'andw-sct' ),
            'manage_options',
            'andw-sct',
            [ , 'render_page' ],
            'dashicons-tickets-alt'
        );
    }

    public function register_settings() : void {
        register_setting( 'andw_sct_settings', ANDW_SCT_OPTION_KEY, [ , 'sanitize' ] );
    }

    public function sanitize( array  ) : array {
        return Andw_Sct_Settings::sanitize(  );
    }

    public function enqueue_assets( string  ) : void {
        if ( 'toplevel_page_andw-sct' !==  ) {
            return;
        }
        wp_enqueue_style( 'andw-sct-admin', ANDW_SCT_PLUGIN_URL . 'assets/css/admin.css', [], ANDW_SCT_VERSION );
    }

    public function render_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'andw-sct' ) );
        }

                    = Andw_Sct_Settings::get_settings();
               = ['sku_price_map'] ?? [];
          = ->format_button_groups( ['button_groups'] ?? [] );
          = ->get_environment_checks();

        include ANDW_SCT_PLUGIN_DIR . 'views/admin-page.php';
    }

    private function format_button_groups( array  ) : string {
         = [];
        foreach (  as  =>  ) {
            if ( empty(  ) ) {
                [] = sprintf( '%s|%s|%s|%d|%s', , '', '', 1, 'false' );
                continue;
            }
            foreach (  as  ) {
                [] = sprintf(
                    '%s|%s|%s|%d|%s',
                    ,
                    ['sku'] ?? '',
                    ['label'] ?? '',
                    isset( ['qty'] ) ? (int) ['qty'] : 1,
                    ! empty( ['require_login'] ) ? 'true' : 'false'
                );
            }
        }

        return implode( "\n",  );
    }

    private function get_environment_checks() : array {
        return [
            'php_version'        => [
                'label'  => __( 'PHPバージョン (推奨 8.1+)', 'andw-sct' ),
                'status' => version_compare( PHP_VERSION, '8.1', '>=' ),
                'value'  => PHP_VERSION,
            ],
            'curl'               => [
                'label'  => __( 'cURL拡張', 'andw-sct' ),
                'status' => function_exists( 'curl_version' ),
                'value'  => function_exists( 'curl_version' ) ? __( '利用可能', 'andw-sct' ) : __( '未インストール', 'andw-sct' ),
            ],
            'rest_available'     => [
                'label'  => __( 'WP HTTP API', 'andw-sct' ),
                'status' => function_exists( 'wp_remote_post' ),
                'value'  => function_exists( 'wp_remote_post' ) ? __( '利用可能', 'andw-sct' ) : __( '未定義', 'andw-sct' ),
            ],
            'https'              => [
                'label'  => __( 'サイトURL (HTTPS)', 'andw-sct' ),
                'status' => str_starts_with( get_site_url(), 'https://' ),
                'value'  => get_site_url(),
            ],
        ];
    }
}
