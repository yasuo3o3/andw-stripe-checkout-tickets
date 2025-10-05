<?php
/**
 * Settings helper for andW Stripe Checkout Tickets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Settings {

    /**
     * Returns the full settings array merged with defaults.
     */
    public static function get_settings() : array {
        $settings = get_option( ANDW_SCT_OPTION_KEY, [] );
        if ( ! is_array( $settings ) ) {
            $settings = [];
        }

        return wp_parse_args( $settings, self::get_defaults() );
    }

    /**
     * Returns a single setting by key.
     */
    public static function get( string $key, $default = null ) {
        $settings = self::get_settings();
        return $settings[ $key ] ?? $default;
    }

    /**
     * Updates settings after sanitizing.
     */
    public static function update_settings( array $settings ) : array {
        $sanitized = self::sanitize( $settings );
        update_option( ANDW_SCT_OPTION_KEY, $sanitized, true );
        return $sanitized;
    }

    /**
     * Provides default settings structure.
     */
    public static function get_defaults() : array {
        return [
            'publishable_key'      => '',
            'secret_key'           => '',
            'webhook_secret'       => '',
            'default_success_url'  => '',
            'default_cancel_url'   => '',
            'consent_enabled'      => false,
            'consent_text'         => __( 'チケット購入に際し、利用規約とプライバシーポリシーに同意します。', 'andw-stripe-checkout-tickets' ),
            'sku_price_map'        => [],
            'button_groups'        => [
                'default' => [],
            ],
            'default_button_group' => 'default',
            'meeting_form_url'     => '',
            'support_link_url'     => '',
            'support_link_text'    => __( 'ご不明点がございましたらこちらよりお問い合わせください。', 'andw-stripe-checkout-tickets' ),
            'line_url'             => '',
            'chat_url'             => '',
        ];
    }

    /**
     * Sanitizes the incoming settings payload.
     */
    public static function sanitize( array $settings ) : array {
        $defaults = self::get_defaults();

        $clean = [
            'publishable_key'      => isset( $settings['publishable_key'] ) ? sanitize_text_field( $settings['publishable_key'] ) : $defaults['publishable_key'],
            'secret_key'           => isset( $settings['secret_key'] ) ? sanitize_text_field( $settings['secret_key'] ) : $defaults['secret_key'],
            'webhook_secret'       => isset( $settings['webhook_secret'] ) ? sanitize_text_field( $settings['webhook_secret'] ) : $defaults['webhook_secret'],
            'default_success_url'  => isset( $settings['default_success_url'] ) ? esc_url_raw( $settings['default_success_url'] ) : $defaults['default_success_url'],
            'default_cancel_url'   => isset( $settings['default_cancel_url'] ) ? esc_url_raw( $settings['default_cancel_url'] ) : $defaults['default_cancel_url'],
            'consent_enabled'      => ! empty( $settings['consent_enabled'] ),
            'consent_text'         => isset( $settings['consent_text'] ) ? wp_kses_post( $settings['consent_text'] ) : $defaults['consent_text'],
            'default_button_group' => isset( $settings['default_button_group'] ) ? sanitize_key( $settings['default_button_group'] ) : $defaults['default_button_group'],
            'meeting_form_url'     => isset( $settings['meeting_form_url'] ) ? esc_url_raw( $settings['meeting_form_url'] ) : $defaults['meeting_form_url'],
            'support_link_url'     => isset( $settings['support_link_url'] ) ? esc_url_raw( $settings['support_link_url'] ) : $defaults['support_link_url'],
            'support_link_text'    => isset( $settings['support_link_text'] ) ? sanitize_text_field( $settings['support_link_text'] ) : $defaults['support_link_text'],
            'line_url'             => isset( $settings['line_url'] ) ? esc_url_raw( $settings['line_url'] ) : $defaults['line_url'],
            'chat_url'             => isset( $settings['chat_url'] ) ? esc_url_raw( $settings['chat_url'] ) : $defaults['chat_url'],
        ];

        $clean['sku_price_map'] = [];
        if ( ! empty( $settings['sku_price_map'] ) && is_array( $settings['sku_price_map'] ) ) {
            foreach ( $settings['sku_price_map'] as $row ) {
                if ( empty( $row['sku'] ) || empty( $row['price_id'] ) ) {
                    continue;
                }
                $sku      = sanitize_key( $row['sku'] );
                $price_id = sanitize_text_field( $row['price_id'] );
                if ( '' === $sku || '' === $price_id ) {
                    continue;
                }
                $clean['sku_price_map'][] = [
                    'sku'      => $sku,
                    'price_id' => $price_id,
                ];
            }
        }

        $clean['button_groups'] = [];

        if ( isset( $settings['button_groups_text'] ) ) {
            $lines = preg_split( '/\r?\n/', (string) $settings['button_groups_text'] );
            foreach ( $lines as $line ) {
                $line = trim( $line );
                if ( '' === $line || str_starts_with( $line, '#' ) ) {
                    continue;
                }
                $parts = array_map( 'trim', explode( '|', $line ) );
                $parts = array_pad( $parts, 5, '' );
                [ $group_slug, $sku, $label, $qty, $requires_login ] = $parts;

                $group_slug = sanitize_key( $group_slug );
                if ( '' === $group_slug ) {
                    continue;
                }

                if ( ! isset( $clean['button_groups'][ $group_slug ] ) ) {
                    $clean['button_groups'][ $group_slug ] = [];
                }

                $clean['button_groups'][ $group_slug ][] = [
                    'sku'           => sanitize_key( $sku ),
                    'label'         => sanitize_text_field( $label ),
                    'qty'           => max( 1, absint( $qty ) ),
                    'require_login' => (bool) filter_var( $requires_login, FILTER_VALIDATE_BOOLEAN ),
                ];
            }
        } elseif ( ! empty( $settings['button_groups'] ) && is_array( $settings['button_groups'] ) ) {
            foreach ( $settings['button_groups'] as $group_slug => $buttons ) {
                $group_slug = sanitize_key( (string) $group_slug );
                if ( '' === $group_slug ) {
                    continue;
                }
                $clean['button_groups'][ $group_slug ] = [];
                if ( ! is_array( $buttons ) ) {
                    continue;
                }
                foreach ( $buttons as $button ) {
                    if ( empty( $button['sku'] ) ) {
                        continue;
                    }
                    $clean['button_groups'][ $group_slug ][] = [
                        'sku'           => sanitize_key( $button['sku'] ),
                        'label'         => isset( $button['label'] ) ? sanitize_text_field( $button['label'] ) : '',
                        'qty'           => isset( $button['qty'] ) ? max( 1, absint( $button['qty'] ) ) : 1,
                        'require_login' => ! empty( $button['require_login'] ),
                    ];
                }
            }
        }

        if ( empty( $clean['button_groups'] ) ) {
            $clean['button_groups'] = $defaults['button_groups'];
        }

        if ( ! isset( $clean['button_groups'][ $clean['default_button_group'] ] ) ) {
            $clean['default_button_group'] = array_key_first( $clean['button_groups'] );
        }

        return $clean;
    }
}
