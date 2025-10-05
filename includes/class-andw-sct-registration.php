<?php
/**
 * Handles registration flow linked to Stripe sessions.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Registration {

    public const NONCE_ACTION = 'andw_sct_register';

    private Andw_Sct_Checkout $checkout;

    private array $errors = [];

    private array $session_cache = [];

    private array $last_post = [];

    public function __construct( Andw_Sct_Checkout $checkout ) {
        $this->checkout = $checkout;
        add_action( 'init', [ $this, 'handle_submission' ] );
    }

    public function handle_submission() : void {
        if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
            return;
        }

        if ( empty( $_POST['andw_sct_action'] ) || 'register' !== sanitize_key( wp_unslash( $_POST['andw_sct_action'] ) ) ) {
            return;
        }

        check_admin_referer( self::NONCE_ACTION, 'andw_sct_register_nonce' );

        $session_id = isset( $_POST['andw_sct_session_id'] ) ? sanitize_text_field( wp_unslash( $_POST['andw_sct_session_id'] ) ) : '';
        $password   = isset( $_POST['andw_sct_password'] ) ? (string) wp_unslash( $_POST['andw_sct_password'] ) : '';
        $redirect   = isset( $_POST['andw_sct_redirect'] ) ? esc_url_raw( wp_unslash( $_POST['andw_sct_redirect'] ) ) : '';

        $this->last_post = [
            'session_id' => $session_id,
            'full_name'  => isset( $_POST['andw_sct_full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['andw_sct_full_name'] ) ) : '',
            'company'    => isset( $_POST['andw_sct_company'] ) ? sanitize_text_field( wp_unslash( $_POST['andw_sct_company'] ) ) : '',
            'phone'      => isset( $_POST['andw_sct_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['andw_sct_phone'] ) ) : '',
        ];

        if ( '' === $session_id ) {
            $this->errors[] = __( 'セッションIDが不明です。再度サンクスページからアクセスしてください。', 'andw-stripe-checkout-tickets' );
            return;
        }

        if ( '' === $this->last_post['full_name'] ) {
            $this->errors[] = __( '氏名は必須項目です。', 'andw-stripe-checkout-tickets' );
        }

        if ( '' === trim( $password ) ) {
            $this->errors[] = __( 'パスワードを入力してください。', 'andw-stripe-checkout-tickets' );
        }

        if ( ! empty( $this->errors ) ) {
            return;
        }

        $summary = $this->get_session_data( $session_id );
        if ( is_wp_error( $summary ) ) {
            $this->errors[] = $summary->get_error_message();
            return;
        }

        $email          = $summary['session']['customer_email'] ?? '';
        $customer_id    = $summary['session']['customer'] ?? '';
        $customer_phone = $summary['session']['customer_phone'] ?? '';

        if ( '' === $email ) {
            $this->errors[] = __( 'Stripe側のメールアドレスが確認できませんでした。サポートへご連絡ください。', 'andw-stripe-checkout-tickets' );
            return;
        }

        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            $username = $this->generate_username( $email, $this->last_post['full_name'] );
            $user_id  = wp_insert_user(
                [
                    'user_login'   => $username,
                    'user_email'   => $email,
                    'user_pass'    => $password,
                    'display_name' => $this->last_post['full_name'],
                    'first_name'   => $this->last_post['full_name'],
                    'role'         => 'subscriber',
                ]
            );

            if ( is_wp_error( $user_id ) ) {
                $this->errors[] = $user_id->get_error_message();
                return;
            }

            $user = get_user_by( 'id', $user_id );
        } else {
            $user_id = $user->ID;
            // 既存ユーザーのパスワードは変更しない。
        }

        if ( ! $user instanceof WP_User ) {
            $this->errors[] = __( 'ユーザー情報の取得に失敗しました。', 'andw-stripe-checkout-tickets' );
            return;
        }

        $phone_to_store = $this->last_post['phone'] ?: $customer_phone;

        update_user_meta( $user->ID, 'andw_sct_stripe_customer_id', sanitize_text_field( $customer_id ) );
        update_user_meta( $user->ID, 'andw_sct_company', $this->last_post['company'] );
        update_user_meta( $user->ID, 'andw_sct_phone', $phone_to_store );
        update_user_meta( $user->ID, 'andw_sct_last_session', sanitize_text_field( $session_id ) );

        if ( ! empty( $this->last_post['full_name'] ) ) {
            wp_update_user(
                [
                    'ID'           => $user->ID,
                    'display_name' => $this->last_post['full_name'],
                ]
            );
        }

        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID );

        do_action( 'andw_sct_user_linked', $user->ID, $customer_id );

        $redirect_url = $redirect ?: home_url( '/' );
        $redirect_url = apply_filters( 'andw_sct_registration_redirect', $redirect_url, $user->ID, $summary );

        wp_safe_redirect( $redirect_url );
        exit;
    }

    public function render_form( array $atts = [] ) : string {
        $atts = shortcode_atts(
            [
                'redirect' => '',
            ],
            $atts,
            'andw_sct_register'
        );

        $this->ensure_front_style();

        $session_id = $this->last_post['session_id'] ?? '';
        if ( ! $session_id && isset( $_GET['session_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $session_id = sanitize_text_field( wp_unslash( $_GET['session_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        $session_info = [];
        if ( $session_id ) {
            $session = $this->get_session_data( $session_id );
            if ( ! is_wp_error( $session ) ) {
                $session_info = $session;
            } else {
                $this->errors[] = $session->get_error_message();
            }
        }

        $defaults = [
            'full_name' => $session_info['session']['customer_name'] ?? '',
            'company'   => '',
            'phone'     => $session_info['session']['customer_phone'] ?? '',
        ];
        $values = wp_parse_args( $this->last_post, $defaults );

        $email_display = $session_info['session']['customer_email'] ?? '';

        $redirect_url = $atts['redirect'] ? esc_url_raw( $atts['redirect'] ) : home_url( '/mypage/' );
        if ( ! $redirect_url ) {
            $redirect_url = home_url( '/' );
        }

        ob_start();
        ?>
        <div id="andw-sct-register" class="andw-sct-register">
            <h2><?php esc_html_e( 'ユーザー登録', 'andw-stripe-checkout-tickets' ); ?></h2>
            <?php if ( ! empty( $this->errors ) ) : ?>
                <div class="andw-sct-message andw-sct-message--error">
                    <?php foreach ( $this->errors as $error ) : ?>
                        <p><?php echo esc_html( $error ); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if ( empty( $session_id ) ) : ?>
                <p><?php esc_html_e( 'StripeのセッションIDが確認できませんでした。サンクスページから再アクセスしてください。', 'andw-stripe-checkout-tickets' ); ?></p>
            <?php else : ?>
                <form method="post" class="andw-sct-form">
                    <?php wp_nonce_field( self::NONCE_ACTION, 'andw_sct_register_nonce' ); ?>
                    <input type="hidden" name="andw_sct_action" value="register" />
                    <input type="hidden" name="andw_sct_session_id" value="<?php echo esc_attr( $session_id ); ?>" />
                    <input type="hidden" name="andw_sct_redirect" value="<?php echo esc_attr( $redirect_url ); ?>" />
                    <table class="andw-sct-form__table">
                        <tr>
                            <th><?php esc_html_e( 'ご登録メールアドレス', 'andw-stripe-checkout-tickets' ); ?></th>
                            <td><?php echo esc_html( $email_display ?: __( 'Stripeで入力したメールアドレス', 'andw-stripe-checkout-tickets' ) ); ?></td>
                        </tr>
                        <tr>
                            <th><label for="andw-sct-full-name"><?php esc_html_e( '氏名', 'andw-stripe-checkout-tickets' ); ?>*</label></th>
                            <td><input id="andw-sct-full-name" name="andw_sct_full_name" type="text" value="<?php echo esc_attr( $values['full_name'] ); ?>" required /></td>
                        </tr>
                        <tr>
                            <th><label for="andw-sct-company"><?php esc_html_e( '会社名', 'andw-stripe-checkout-tickets' ); ?></label></th>
                            <td><input id="andw-sct-company" name="andw_sct_company" type="text" value="<?php echo esc_attr( $values['company'] ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="andw-sct-phone"><?php esc_html_e( '電話番号', 'andw-stripe-checkout-tickets' ); ?></label></th>
                            <td><input id="andw-sct-phone" name="andw_sct_phone" type="text" value="<?php echo esc_attr( $values['phone'] ); ?>" /></td>
                        </tr>
                        <tr>
                            <th><label for="andw-sct-password"><?php esc_html_e( 'パスワード', 'andw-stripe-checkout-tickets' ); ?>*</label></th>
                            <td><input id="andw-sct-password" name="andw_sct_password" type="password" required /></td>
                        </tr>
                    </table>
                    <p><button type="submit" class="andw-sct-button"><?php esc_html_e( '登録してマイページへ', 'andw-stripe-checkout-tickets' ); ?></button></p>
                </form>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_mypage() : string {
        $this->ensure_front_style();

        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( add_query_arg( [] ) );
            return sprintf(
                '<p>%s</p>',
                wp_kses_post( sprintf( __( 'ログインが必要です。<a href="%s">こちらからログイン</a>してください。', 'andw-stripe-checkout-tickets' ), esc_url( $login_url ) ) )
            );
        }

        $user     = wp_get_current_user();
        $settings = Andw_Sct_Settings::get_settings();

        $customer_id = get_user_meta( $user->ID, 'andw_sct_stripe_customer_id', true );
        $logs        = [];
        if ( $customer_id ) {
            $logs = Andw_Sct_Logger::get_by_customer( $customer_id );
        }
        if ( empty( $logs ) ) {
            $logs = Andw_Sct_Logger::get_by_email( $user->user_email );
        }

        $buttons_html = do_shortcode( '[andw_ticket_buttons group="' . esc_attr( $settings['default_button_group'] ?? 'default' ) . '"]' );

        ob_start();
        ?>
        <div class="andw-sct-mypage">
            <h2><?php echo esc_html( sprintf( __( '%sさんのマイページ', 'andw-stripe-checkout-tickets' ), $user->display_name ) ); ?></h2>
            <section class="andw-sct-mypage__actions">
                <h3><?php esc_html_e( 'チケット購入', 'andw-stripe-checkout-tickets' ); ?></h3>
                <?php echo $buttons_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </section>
            <section class="andw-sct-mypage__history">
                <h3><?php esc_html_e( '購入履歴', 'andw-stripe-checkout-tickets' ); ?></h3>
                <?php if ( empty( $logs ) ) : ?>
                    <p><?php esc_html_e( '購入履歴はまだありません。', 'andw-stripe-checkout-tickets' ); ?></p>
                <?php else : ?>
                    <table class="andw-sct-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( '日時', 'andw-stripe-checkout-tickets' ); ?></th>
                                <th><?php esc_html_e( '金額', 'andw-stripe-checkout-tickets' ); ?></th>
                                <th><?php esc_html_e( '通貨', 'andw-stripe-checkout-tickets' ); ?></th>
                                <th><?php esc_html_e( 'StripeセッションID', 'andw-stripe-checkout-tickets' ); ?></th>
                                <th><?php esc_html_e( '詳細', 'andw-stripe-checkout-tickets' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $logs as $entry ) : ?>
                                <?php $detail_url = $this->build_detail_url( $entry, $settings ); ?>
                                <tr>
                                    <td><?php echo esc_html( mysql2date( 'Y-m-d H:i', $entry['created_at'] ) ); ?></td>
                                    <td><?php echo esc_html( $this->format_log_amount( $entry ) ); ?></td>
                                    <td><?php echo esc_html( strtoupper( $entry['currency'] ) ); ?></td>
                                    <td><?php echo esc_html( $entry['session_id'] ); ?></td>
                                    <td>
                                        <?php if ( $detail_url ) : ?>
                                            <a href="<?php echo esc_url( $detail_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'サンクスページ', 'andw-stripe-checkout-tickets' ); ?></a>
                                        <?php else : ?>
                                            &mdash;
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>
            <section class="andw-sct-mypage__links">
                <h3><?php esc_html_e( '各種導線', 'andw-stripe-checkout-tickets' ); ?></h3>
                <ul>
                    <?php if ( ! empty( $settings['meeting_form_url'] ) ) : ?>
                        <li><a class="andw-sct-button-link" href="<?php echo esc_url( $settings['meeting_form_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '打ち合わせフォームを開く', 'andw-stripe-checkout-tickets' ); ?></a></li>
                    <?php endif; ?>
                    <?php if ( ! empty( $settings['line_url'] ) ) : ?>
                        <li><a class="andw-sct-button-link" href="<?php echo esc_url( $settings['line_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'LINEで連絡する', 'andw-stripe-checkout-tickets' ); ?></a></li>
                    <?php endif; ?>
                    <?php if ( ! empty( $settings['chat_url'] ) ) : ?>
                        <li><a class="andw-sct-button-link" href="<?php echo esc_url( $settings['chat_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'チャネルトークで相談する', 'andw-stripe-checkout-tickets' ); ?></a></li>
                    <?php endif; ?>
                </ul>
            </section>
        </div>
        <?php
        return ob_get_clean();
    }

    private function ensure_front_style() : void {
        if ( ! wp_style_is( 'andw-sct-front', 'registered' ) ) {
            wp_register_style( 'andw-sct-front', ANDW_SCT_PLUGIN_URL . 'assets/css/front.css', [], ANDW_SCT_VERSION );
        }
        wp_enqueue_style( 'andw-sct-front' );
    }

    private function get_session_data( string $session_id ) {
        if ( isset( $this->session_cache[ $session_id ] ) ) {
            return $this->session_cache[ $session_id ];
        }

        $summary = $this->checkout->get_session_summary( $session_id );
        if ( ! is_wp_error( $summary ) ) {
            $this->session_cache[ $session_id ] = $summary;
        }
        return $summary;
    }

    private function generate_username( string $email, string $fallback ) : string {
        $local_part = strstr( $email, '@', true );
        $base       = sanitize_user( $local_part ?: $email, true );
        if ( ! $base ) {
            $base = sanitize_user( $fallback, true );
        }
        if ( ! $base ) {
            $base = 'andwuser';
        }

        $username = $base;
        $i        = 1;
        while ( username_exists( $username ) ) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }

    private function format_log_amount( array $entry ) : string {
        $currency = strtoupper( $entry['currency'] ?? 'JPY' );
        $amount   = (int) ( $entry['amount_total'] ?? 0 );
        if ( 'JPY' === $currency ) {
            return number_format_i18n( $amount );
        }
        return number_format_i18n( $amount / 100, 2 );
    }

    private function build_detail_url( array $entry, array $settings ) : string {
        if ( empty( $settings['default_success_url'] ) || empty( $entry['session_id'] ) ) {
            return '';
        }
        return add_query_arg( 'session_id', rawurlencode( $entry['session_id'] ), $settings['default_success_url'] );
    }
}

