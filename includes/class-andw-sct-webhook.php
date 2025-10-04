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
        add_action( 'init', [ , 'maybe_handle_webhook' ], 1 );
    }

    public function maybe_handle_webhook() : void {
        if ( ! isset( ['andw_sct'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }
         = sanitize_key( wp_unslash( ['andw_sct'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( 'webhook' !==  ) {
            return;
        }

        ->handle_request();
        exit;
    }

    private function handle_request() : void {
        if ( 'POST' !== strtoupper( ['REQUEST_METHOD'] ?? '' ) ) {
            ->respond( 405, [ 'message' => 'Method Not Allowed' ] );
        }

         = Andw_Sct_Settings::get_settings();
           = ['webhook_secret'];
        if ( !  ) {
            ->respond( 400, [ 'message' => 'Webhook secret not configured.' ] );
        }

           = file_get_contents( 'php://input' );
         = ['HTTP_STRIPE_SIGNATURE'] ?? '';
        if ( ! ->verify_signature( , ,  ) ) {
            ->respond( 400, [ 'message' => 'Invalid signature.' ] );
        }

         = json_decode( , true );
        if ( ! is_array(  ) || empty( ['id'] ) ) {
            ->respond( 400, [ 'message' => 'Malformed payload.' ] );
        }

         = ['id'];
        if ( Andw_Sct_Logger::event_exists(  ) ) {
            ->respond( 200, [ 'status' => 'duplicate' ] );
        }

        if ( 'checkout.session.completed' !== ( ['type'] ?? '' ) ) {
            Andw_Sct_Logger::insert(
                [
                    'event_id'    => ,
                    'type'        => ['type'] ?? '',
                    'session_id'  => ['data']['object']['id'] ?? '',
                    'customer_id' => ['data']['object']['customer'] ?? '',
                    'email'       => ['data']['object']['customer_details']['email'] ?? '',
                    'amount_total'=> (int) ( ['data']['object']['amount_total'] ?? 0 ),
                    'currency'    => strtolower( ['data']['object']['currency'] ?? 'jpy' ),
                    'created_at'  => gmdate( 'Y-m-d H:i:s', (int) ( ['created'] ?? time() ) ),
                ]
            );
            ->respond( 200, [ 'status' => 'ignored' ] );
        }

         = ['data']['object'] ?? [];

        Andw_Sct_Logger::insert(
            [
                'event_id'    => ,
                'type'        => 'checkout.session.completed',
                'session_id'  => ['id'] ?? '',
                'customer_id' => ['customer'] ?? '',
                'email'       => ['customer_details']['email'] ?? '',
                'amount_total'=> (int) ( ['amount_total'] ?? 0 ),
                'currency'    => strtolower( ['currency'] ?? 'jpy' ),
                'created_at'  => gmdate( 'Y-m-d H:i:s', (int) ( ['created'] ?? time() ) ),
            ]
        );

        ->respond( 200, [ 'status' => 'ok' ] );
    }

    private function verify_signature( string , string , string  ) : bool {
        if ( empty(  ) || empty(  ) ) {
            return false;
        }
         = [];
        foreach ( explode( ',',  ) as  ) {
             = explode( '=', trim(  ), 2 );
            if ( 2 === count(  ) ) {
                [ [0] ][] = [1];
            }
        }

         = isset( ['t'][0] ) ? (int) ['t'][0] : 0;
        if ( !  || abs( time() -  ) > self::TIMESTAMP_TOLERANCE ) {
            return false;
        }

         =  . '.' . ;
               = hash_hmac( 'sha256', ,  );

        if ( empty( ['v1'] ) ) {
            return false;
        }

        foreach ( ['v1'] as  ) {
            if ( hash_equals( ,  ) ) {
                return true;
            }
        }

        return false;
    }

    private function respond( int , array  ) : void {
        status_header(  );
        header( 'Content-Type: application/json; charset=utf-8' );
        echo wp_json_encode(  );
        exit;
    }
}
