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
         = get_option( ANDW_SCT_OPTION_KEY, [] );
        if ( ! is_array(  ) ) {
             = [];
        }

        return wp_parse_args( , self::get_defaults() );
    }

    /**
     * Returns a single setting by key.
     */
    public static function get( string ,  = null ) {
         = self::get_settings();
        return [  ] ?? ;
    }

    /**
     * Updates settings after sanitizing.
     */
    public static function update_settings( array  ) : array {
         = self::sanitize(  );
        update_option( ANDW_SCT_OPTION_KEY, , true );
        return ;
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
            'consent_text'         => __( 'チケット購入にあたり、利用規約とプライバシーポリシーに同意します。', 'andw-sct' ),
            'sku_price_map'        => [],
            'button_groups'        => [
                'default' => [],
            ],
            'default_button_group' => 'default',
            'meeting_form_url'     => '',
            'support_link_url'     => '',
            'support_link_text'    => __( '誤ったメールを受け取った場合はこちらからご連絡ください。', 'andw-sct' ),
            'line_url'             => '',
            'chat_url'             => '',
        ];
    }

    /**
     * Sanitizes the incoming settings payload.
     */
    public static function sanitize( array  ) : array {
         = self::get_defaults();

         = [
            'publishable_key'      => isset( ['publishable_key'] ) ? sanitize_text_field( ['publishable_key'] ) : ['publishable_key'],
            'secret_key'           => isset( ['secret_key'] ) ? sanitize_text_field( ['secret_key'] ) : ['secret_key'],
            'webhook_secret'       => isset( ['webhook_secret'] ) ? sanitize_text_field( ['webhook_secret'] ) : ['webhook_secret'],
            'default_success_url'  => isset( ['default_success_url'] ) ? esc_url_raw( ['default_success_url'] ) : ['default_success_url'],
            'default_cancel_url'   => isset( ['default_cancel_url'] ) ? esc_url_raw( ['default_cancel_url'] ) : ['default_cancel_url'],
            'consent_enabled'      => ! empty( ['consent_enabled'] ),
            'consent_text'         => isset( ['consent_text'] ) ? wp_kses_post( ['consent_text'] ) : ['consent_text'],
            'default_button_group' => isset( ['default_button_group'] ) ? sanitize_key( ['default_button_group'] ) : ['default_button_group'],
            'meeting_form_url'     => isset( ['meeting_form_url'] ) ? esc_url_raw( ['meeting_form_url'] ) : ['meeting_form_url'],
            'support_link_url'     => isset( ['support_link_url'] ) ? esc_url_raw( ['support_link_url'] ) : ['support_link_url'],
            'support_link_text'    => isset( ['support_link_text'] ) ? sanitize_text_field( ['support_link_text'] ) : ['support_link_text'],
            'line_url'             => isset( ['line_url'] ) ? esc_url_raw( ['line_url'] ) : ['line_url'],
            'chat_url'             => isset( ['chat_url'] ) ? esc_url_raw( ['chat_url'] ) : ['chat_url'],
        ];

        ['sku_price_map'] = [];
        if ( ! empty( ['sku_price_map'] ) && is_array( ['sku_price_map'] ) ) {
            foreach ( ['sku_price_map'] as  ) {
                if ( empty( ['sku'] ) || empty( ['price_id'] ) ) {
                    continue;
                }
                      = sanitize_key( ['sku'] );
                 = sanitize_text_field( ['price_id'] );
                if ( '' ===  || '' ===  ) {
                    continue;
                }
                ['sku_price_map'][] = [
                    'sku'      => ,
                    'price_id' => ,
                ];
            }
        }

        ['button_groups'] = [];

        if ( isset( ['button_groups_text'] ) ) {
             = preg_split( '/\r?\n/', (string) ['button_groups_text'] );
            foreach (  as  ) {
                 = trim(  );
                if ( '' ===  || str_starts_with( , '#' ) ) {
                    continue;
                }
                 = array_map( 'trim', explode( '|',  ) );
                if ( count(  ) < 4 ) {
                    continue;
                }
                [ , , ,  ] = array_pad( , 4, '' );
                 = isset( [4] ) ? filter_var( [4], FILTER_VALIDATE_BOOLEAN ) : false;

                 = sanitize_key(  );
                if ( '' ===  ) {
                    continue;
                }

                if ( ! isset( ['button_groups'][  ] ) ) {
                    ['button_groups'][  ] = [];
                }

                ['button_groups'][  ][] = [
                    'sku'           => sanitize_key(  ),
                    'label'         => sanitize_text_field(  ),
                    'qty'           => max( 1, absint(  ) ),
                    'require_login' => (bool) ,
                ];
            }
        } elseif ( ! empty( ['button_groups'] ) && is_array( ['button_groups'] ) ) {
            foreach ( ['button_groups'] as  =>  ) {
                 = sanitize_key( (string)  );
                if ( '' ===  ) {
                    continue;
                }
                ['button_groups'][  ] = [];
                if ( ! is_array(  ) ) {
                    continue;
                }
                foreach (  as  ) {
                    if ( empty( ['sku'] ) ) {
                        continue;
                    }
                    ['button_groups'][  ][] = [
                        'sku'           => sanitize_key( ['sku'] ),
                        'label'         => isset( ['label'] ) ? sanitize_text_field( ['label'] ) : '',
                        'qty'           => isset( ['qty'] ) ? max( 1, absint( ['qty'] ) ) : 1,
                        'require_login' => ! empty( ['require_login'] ),
                    ];
                }
            }
        }

        if ( empty( ['button_groups'] ) ) {
            ['button_groups'] = ['button_groups'];
        }

        if ( ! isset( ['button_groups'][ ['default_button_group'] ] ) ) {
            ['default_button_group'] = array_key_first( ['button_groups'] );
        }

        return ;
    }
}
