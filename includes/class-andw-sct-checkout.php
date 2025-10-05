<?php
/**
 * Checkout session creation and endpoint handling.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Checkout {

    public const NONCE_ACTION = 'andw_sct_create_session';

    public function __construct() {
        add_action( 'init', [ $this, 'handle_requests' ] );
    }

    /**
     * Entry point for custom endpoints.
     */
    public function handle_requests() : void {
        if ( ! isset( $_GET['andw_sct'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $action = sanitize_key( wp_unslash( $_GET['andw_sct'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ( 'create_session' === $action ) {
            $this->handle_create_session();
        }
    }

    /**
     * Handles session creation requests.
     */
    protected function handle_create_session() : void {
        if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            wp_send_json_error( [ 'message' => __( '不正なリクエストです。', 'andw-stripe-checkout-tickets' ) ], 405 );
        }

        $nonce = $_SERVER['HTTP_X_ANDW_SCT_NONCE'] ?? '';
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), self::NONCE_ACTION ) ) {
            wp_send_json_error( [ 'message' => __( 'セッションが無効です。再度ページを読み込んでください。', 'andw-stripe-checkout-tickets' ) ], 403 );
        }

        $raw_body = file_get_contents( 'php://input' );
        $payload  = json_decode( $raw_body, true );
        if ( ! is_array( $payload ) ) {
            wp_send_json_error( [ 'message' => __( '入力が正しくありません。', 'andw-stripe-checkout-tickets' ) ], 400 );
        }

        $args = [
            'sku'         => isset( $payload['sku'] ) ? sanitize_key( $payload['sku'] ) : '',
            'qty'         => isset( $payload['qty'] ) ? max( 1, absint( $payload['qty'] ) ) : 1,
            'label'       => isset( $payload['label'] ) ? sanitize_text_field( $payload['label'] ) : '',
            'success_url' => isset( $payload['success_url'] ) ? esc_url_raw( $payload['success_url'] ) : '',
            'cancel_url'  => isset( $payload['cancel_url'] ) ? esc_url_raw( $payload['cancel_url'] ) : '',
            'case_id'     => isset( $payload['case_id'] ) ? sanitize_text_field( $payload['case_id'] ) : '',
        ];

        if ( empty( $args['sku'] ) ) {
            wp_send_json_error( [ 'message' => __( 'SKUが指定されていません。', 'andw-stripe-checkout-tickets' ) ], 400 );
        }

        $args = apply_filters( 'andw_sct_before_session_create', $args );

        $result = $this->create_session( $args );
        if ( is_wp_error( $result ) ) {
            $code    = $result->get_error_code();
            $status  = is_numeric( $code ) ? (int) $code : 400;
            $message = wp_strip_all_tags( $result->get_error_message() );
            wp_send_json_error( [ 'message' => $message ], $status );
        }

        do_action( 'andw_sct_after_session_create', $result );

        wp_send_json_success(
            [
                'url' => $result['url'],
            ]
        );
    }

    /**
     * Returns the Price ID configured for a SKU.
     */
    public function get_price_id( string $sku ) : string {
        $settings = Andw_Sct_Settings::get_settings();
        foreach ( $settings['sku_price_map'] as $pair ) {
            if ( $pair['sku'] === $sku ) {
                return $pair['price_id'];
            }
        }
        return '';
    }

    /**
     * Resolves endpoint URLs.
     */
    public function get_endpoint_url( string $action ) : string {
        return add_query_arg( 'andw_sct', $action, home_url( '/' ) );
    }

    /**
     * Calls Stripe to create a checkout session.
     */
    public function create_session( array $args ) {
        $settings = Andw_Sct_Settings::get_settings();
        $secret   = $settings['secret_key'];
        if ( empty( $secret ) ) {
            return new WP_Error( '400', __( 'Stripeシークレットキーが設定されていません。', 'andw-stripe-checkout-tickets' ) );
        }

        $price_id = $this->get_price_id( $args['sku'] );
        if ( ! $price_id ) {
            return new WP_Error( '400', __( '有効なSKUではありません。', 'andw-stripe-checkout-tickets' ) );
        }

        $success_url = $this->prepare_redirect_url( $args['success_url'], $settings['default_success_url'] );
        if ( ! $success_url ) {
            return new WP_Error( '400', __( '成功URLの構成に失敗しました。', 'andw-stripe-checkout-tickets' ) );
        }

        $cancel_url = $this->prepare_redirect_url( $args['cancel_url'], $settings['default_cancel_url'] );
        if ( ! $cancel_url ) {
            return new WP_Error( '400', __( 'キャンセルURLの構成に失敗しました。', 'andw-stripe-checkout-tickets' ) );
        }

        $metadata = [
            'consent' => '1',
        ];

        if ( is_user_logged_in() ) {
            $metadata['wp_user_id'] = (string) get_current_user_id();
        }

        if ( ! empty( $args['case_id'] ) ) {
            $metadata['tentative_case_id'] = $args['case_id'];
        }

        $payload = [
            'mode'                    => 'payment',
            'line_items'              => [
                [
                    'price'    => $price_id,
                    'quantity' => $args['qty'],
                ],
            ],
            'success_url'             => add_query_arg( 'session_id', '{CHECKOUT_SESSION_ID}', $success_url ),
            'cancel_url'              => $cancel_url,
            'metadata'                => $metadata,
            'allow_promotion_codes'   => false,
            'phone_number_collection' => [
                'enabled' => true,
            ],
            'name_collection'         => [
                'enabled' => true,
            ],
            'customer_creation'       => 'if_required',
            'locale'                  => 'ja',
            'custom_fields'           => [
                [
                    'key'    => 'company_name',
                    'label'  => [
                        'type'   => 'custom',
                        'custom' => __( '会社名', 'andw-stripe-checkout-tickets' ),
                    ],
                    'type'    => 'text',
                    'text'    => [
                        'maximum_length' => 120,
                    ],
                    'optional' => true,
                ],
            ],
        ];

        $payload = apply_filters( 'andw_sct_session_payload', $payload, $args );

        $response = wp_remote_post(
            'https://api.stripe.com/v1/checkout/sessions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret,
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ],
                'body'    => $this->build_query( $payload ),
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( 'andw-sct: Stripe session create error: ' . $response->get_error_message() );
            return new WP_Error( '500', __( 'Stripeとの通信に失敗しました。', 'andw-stripe-checkout-tickets' ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code || empty( $data['url'] ) ) {
            $message = $data['error']['message'] ?? __( 'Stripeがエラーを返しました。', 'andw-stripe-checkout-tickets' );
            error_log( 'andw-sct: Stripe session create failed: ' . $body );
            return new WP_Error( (string) $code, esc_html( $message ) );
        }

        return $data;
    }

    /**
     * Retrieves and normalizes session data for display.
     */
    public function get_session_summary( string $session_id ) {
        $settings = Andw_Sct_Settings::get_settings();
        $secret   = $settings['secret_key'];
        if ( empty( $secret ) ) {
            return new WP_Error( '400', __( 'Stripeシークレットキーが未設定です。', 'andw-stripe-checkout-tickets' ) );
        }

        $url = sprintf(
            'https://api.stripe.com/v1/checkout/sessions/%s?expand[]=line_items&expand[]=payment_intent',
            rawurlencode( $session_id )
        );

        $response = wp_remote_get(
            $url,
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $secret,
                ],
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( 'andw-sct: Stripe session fetch error: ' . $response->get_error_message() );
            return new WP_Error( '500', __( 'セッション情報の取得に失敗しました。', 'andw-stripe-checkout-tickets' ) );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 !== $code || empty( $data['id'] ) ) {
            error_log( 'andw-sct: Stripe session fetch failed: ' . $body );
            return new WP_Error( (string) $code, __( 'セッション情報の取得でエラーが発生しました。', 'andw-stripe-checkout-tickets' ) );
        }

        $line_items = [];
        if ( ! empty( $data['line_items']['data'] ) && is_array( $data['line_items']['data'] ) ) {
            foreach ( $data['line_items']['data'] as $item ) {
                $line_items[] = [
                    'description'    => $item['description'] ?? '',
                    'quantity'       => $item['quantity'] ?? 1,
                    'amount_display' => $this->format_amount( $item['amount_total'] ?? 0, $item['currency'] ?? ( $data['currency'] ?? 'jpy' ) ),
                ];
            }
        }

        $receipt_url       = '';
        $payment_intent_id = '';
        if ( ! empty( $data['payment_intent']['id'] ) ) {
            $payment_intent_id = $data['payment_intent']['id'];
        }
        if ( ! empty( $data['payment_intent']['charges']['data'][0]['receipt_url'] ) ) {
            $receipt_url = $data['payment_intent']['charges']['data'][0]['receipt_url'];
        }

        $session_summary = [
            'session'              => [
                'id'             => $data['id'],
                'customer'       => $data['customer'] ?? '',
                'customer_email' => $data['customer_details']['email'] ?? '',
                'customer_name'  => $data['customer_details']['name'] ?? '',
                'customer_phone' => $data['customer_details']['phone'] ?? '',
                'receipt_url'    => $receipt_url,
                'payment_intent' => $payment_intent_id,
                'metadata'       => is_array( $data['metadata'] ?? null ) ? $data['metadata'] : [],
            ],
            'line_items'           => $line_items,
            'amount_total_display' => $this->format_amount( $data['amount_total'] ?? 0, $data['currency'] ?? 'jpy' ),
        ];

        return $session_summary;
    }

    private function format_amount( $amount, string $currency ) : string {
        $amount   = (int) $amount;
        $currency = strtoupper( $currency );
        if ( 'JPY' === $currency ) {
            return sprintf( '%s %s', $currency, number_format_i18n( $amount ) );
        }

        return sprintf( '%s %s', $currency, number_format_i18n( $amount / 100, 2 ) );
    }

    private function prepare_redirect_url( string $candidate, string $default ) : string {
        $candidate = $candidate ?: $default;
        if ( ! $candidate ) {
            return '';
        }
        $candidate      = esc_url_raw( $candidate );
        $site_host      = wp_parse_url( home_url(), PHP_URL_HOST );
        $candidate_host = wp_parse_url( $candidate, PHP_URL_HOST );
        if ( $candidate_host && $site_host && strtolower( (string) $candidate_host ) !== strtolower( (string) $site_host ) ) {
            return $default ? esc_url_raw( $default ) : '';
        }
        return $candidate;
    }

    private function build_query( array $data, string $prefix = '' ) : string {
        $segments = [];
        foreach ( $data as $key => $value ) {
            $new_key = $prefix ? $prefix . '[' . $key . ']' : $key;
            if ( is_array( $value ) ) {
                $segments[] = $this->build_query( $value, $new_key );
            } else {
                $segments[] = rawurlencode( $new_key ) . '=' . rawurlencode( (string) $value );
            }
        }
        return implode( '&', array_filter( $segments ) );
    }
}





