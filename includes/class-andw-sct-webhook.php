<?php
/**
 * Handles Stripe webhook intake.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Webhook {

    private const TIMESTAMP_TOLERANCE = 300; // 5 minutes.

    public function __construct() {
        add_action( 'init', [ $this, 'maybe_handle_webhook' ], 1 );
    }

    public function maybe_handle_webhook() : void {
        if ( ! isset( $_GET['andw_sct'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
        $action = sanitize_key( wp_unslash( $_GET['andw_sct'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( 'webhook' !== $action ) {
            return;
        }

        $this->handle_request();
        exit;
    }

    private function handle_request() : void {
        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        if ( 'POST' !== strtoupper( $request_method ) ) {
            $this->respond( 405, [ 'message' => 'Method Not Allowed' ] );
        }

        $settings = Andw_Sct_Settings::get_settings();
        $secret   = $settings['webhook_secret'] ?? '';
        if ( ! $secret ) {
            $this->respond( 400, [ 'message' => 'Webhook secret not configured.' ] );
        }

        $payload        = file_get_contents( 'php://input' );
        $signature_raw  = isset( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) ? wp_unslash( $_SERVER['HTTP_STRIPE_SIGNATURE'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Stripe signature needs raw string for verification.
        $signature      = is_string( $signature_raw ) ? $signature_raw : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Stripe signature needs raw string for verification.
        if ( ! $this->verify_signature( $payload, $signature, $secret ) ) {
            $this->respond( 400, [ 'message' => 'Invalid signature.' ] );
        }

        $data = json_decode( $payload, true );
        if ( ! is_array( $data ) || empty( $data['id'] ) ) {
            $this->respond( 400, [ 'message' => 'Malformed payload.' ] );
        }

        $event_id = $data['id'];
        if ( Andw_Sct_Logger::event_exists( $event_id ) ) {
            $this->respond( 200, [ 'status' => 'duplicate' ] );
        }

        if ( 'checkout.session.completed' !== ( $data['type'] ?? '' ) ) {
            Andw_Sct_Logger::insert(
                [
                    'event_id'     => $event_id,
                    'type'         => $data['type'] ?? '',
                    'session_id'   => $data['data']['object']['id'] ?? '',
                    'customer_id'  => $data['data']['object']['customer'] ?? '',
                    'email'        => $data['data']['object']['customer_details']['email'] ?? '',
                    'amount_total' => (int) ( $data['data']['object']['amount_total'] ?? 0 ),
                    'currency'     => strtolower( $data['data']['object']['currency'] ?? 'jpy' ),
                    'created_at'   => gmdate( 'Y-m-d H:i:s', (int) ( $data['created'] ?? time() ) ),
                ]
            );
            $this->respond( 200, [ 'status' => 'ignored' ] );
        }

        $object = $data['data']['object'] ?? [];

        Andw_Sct_Logger::insert(
            [
                'event_id'     => $event_id,
                'type'         => 'checkout.session.completed',
                'session_id'   => $object['id'] ?? '',
                'customer_id'  => $object['customer'] ?? '',
                'email'        => $object['customer_details']['email'] ?? '',
                'amount_total' => (int) ( $object['amount_total'] ?? 0 ),
                'currency'     => strtolower( $object['currency'] ?? 'jpy' ),
                'created_at'   => gmdate( 'Y-m-d H:i:s', (int) ( $object['created'] ?? time() ) ),
            ]
        );

        $this->respond( 200, [ 'status' => 'ok' ] );
    }

    private function verify_signature( string $payload, string $signature, string $secret ) : bool {
        if ( empty( $payload ) || empty( $signature ) ) {
            return false;
        }
        $parts = [];
        foreach ( explode( ',', $signature ) as $segment ) {
            $pair = explode( '=', trim( $segment ), 2 );
            if ( 2 === count( $pair ) ) {
                $parts[ $pair[0] ][] = $pair[1];
            }
        }

        $timestamp = isset( $parts['t'][0] ) ? (int) $parts['t'][0] : 0;
        if ( ! $timestamp || abs( time() - $timestamp ) > self::TIMESTAMP_TOLERANCE ) {
            return false;
        }

        $signed_payload     = $timestamp . '.' . $payload;
        $expected_signature = hash_hmac( 'sha256', $signed_payload, $secret );

        if ( empty( $parts['v1'] ) ) {
            return false;
        }

        foreach ( $parts['v1'] as $candidate ) {
            if ( hash_equals( $expected_signature, $candidate ) ) {
                return true;
            }
        }

        return false;
    }

    private function respond( int $status, array $data ) : void {
        status_header( $status );
        header( 'Content-Type: application/json; charset=utf-8' );
        echo wp_json_encode( $data );
        exit;
    }
}
