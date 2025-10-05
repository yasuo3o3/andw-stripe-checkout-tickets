<?php
/**
 * Front-end shortcodes and assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Frontend {

    private Andw_Sct_Checkout $checkout;

    private Andw_Sct_Registration $registration;

    private bool $localized = false;

    public function __construct( Andw_Sct_Checkout $checkout, Andw_Sct_Registration $registration ) {
        $this->checkout     = $checkout;
        $this->registration = $registration;

        add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );

        add_shortcode( 'andw_checkout', [ $this, 'render_checkout_button' ] );
        add_shortcode( 'andw_ticket_buttons', [ $this, 'render_buttons_group' ] );
        add_shortcode( 'andw_sct_thanks', [ $this, 'render_thanks' ] );
        add_shortcode( 'andw_sct_register', [ $this, 'render_registration_form' ] );
        add_shortcode( 'andw_mypage', [ $this, 'render_mypage' ] );
    }

    public function register_assets() : void {
        wp_register_style( 'andw-sct-front', ANDW_SCT_PLUGIN_URL . 'assets/css/front.css', [], ANDW_SCT_VERSION );
        wp_register_script( 'andw-sct-front', ANDW_SCT_PLUGIN_URL . 'assets/js/front.js', [ 'wp-i18n' ], ANDW_SCT_VERSION, true );
        wp_set_script_translations( 'andw-sct-front', 'andw-stripe-checkout-tickets', ANDW_SCT_PLUGIN_DIR . 'languages' );
    }

    private function enqueue_assets( array $settings ) : void {
        wp_enqueue_style( 'andw-sct-front' );
        wp_enqueue_script( 'andw-sct-front' );

        if ( $this->localized ) {
            return;
        }

        $data = [
            'endpoint'   => $this->checkout->get_endpoint_url( 'create_session' ),
            'nonce'      => wp_create_nonce( Andw_Sct_Checkout::NONCE_ACTION ),
            'isLoggedIn' => is_user_logged_in(),
            'consent'    => [
                'enabled' => ! empty( $settings['consent_enabled'] ),
                'message' => __( '同意欄にチェックを入れてください。', 'andw-stripe-checkout-tickets' ),
            ],
            'messages'   => [
                'genericError'  => __( 'チェックアウトの開始に失敗しました。時間をおいて再試行してください。', 'andw-stripe-checkout-tickets' ),
                'loginRequired' => __( 'ログインが必要です。', 'andw-stripe-checkout-tickets' ),
                'processing'    => __( '処理中...', 'andw-stripe-checkout-tickets' ),
            ],
        ];

        if ( ! empty( $settings['consent_text'] ) ) {
            $data['consent']['message'] = wp_kses_post( $settings['consent_text'] );
        }

        wp_localize_script( 'andw-sct-front', 'andwSctData', $data );
        $this->localized = true;
    }

    public function render_checkout_button( $atts = [] ) : string {
        $atts = shortcode_atts(
            [
                'sku'           => '',
                'qty'           => 1,
                'label'         => __( 'チケットを購入', 'andw-stripe-checkout-tickets' ),
                'require_login' => 'false',
                'success_url'   => '',
                'cancel_url'    => '',
                'case_id'       => '',
            ],
            $atts,
            'andw_checkout'
        );

        $sku = sanitize_key( $atts['sku'] );
        if ( '' === $sku ) {
            return '<p>' . esc_html__( 'SKUを指定してください。', 'andw-stripe-checkout-tickets' ) . '</p>';
        }

        $settings = Andw_Sct_Settings::get_settings();
        $price_id = $this->checkout->get_price_id( $sku );
        if ( ! $price_id ) {
            return '<p>' . esc_html__( '指定されたSKUに対応するPrice IDが設定されていません。', 'andw-stripe-checkout-tickets' ) . '</p>';
        }

        $require_login = filter_var( $atts['require_login'], FILTER_VALIDATE_BOOLEAN );
        if ( $require_login && ! is_user_logged_in() ) {
            $login_url = wp_login_url( $this->get_current_url() );

            $login_message = sprintf(
                /* translators: %s: login URL. */
                __( '購入にはログインが必要です。<a href="%s">ログインはこちら</a>。', 'andw-stripe-checkout-tickets' ),
                esc_url( $login_url )
            );

            return sprintf(
                '<p class="andw-sct-login-required">%s</p>',
                wp_kses_post( $login_message )
            );
        }

        $quantity     = max( 1, absint( $atts['qty'] ) );
        $label        = sanitize_text_field( $atts['label'] );
        $button_label = $label ? $label : __( 'チケットを購入', 'andw-stripe-checkout-tickets' );

        $this->enqueue_assets( $settings );

        $consent_enabled = ! empty( $settings['consent_enabled'] );
        $consent_text    = $settings['consent_text'] ?? '';
        $button_id       = wp_unique_id( 'andw-sct-btn-' );
        $consent_id      = wp_unique_id( 'andw-sct-consent-' );

        ob_start();
        ?>
        <div class="andw-sct-checkout" data-sku="<?php echo esc_attr( $sku ); ?>" data-qty="<?php echo esc_attr( (string) $quantity ); ?>" data-success-url="<?php echo esc_attr( $atts['success_url'] ); ?>" data-cancel-url="<?php echo esc_attr( $atts['cancel_url'] ); ?>" data-case-id="<?php echo esc_attr( $atts['case_id'] ); ?>" data-require-login="<?php echo $require_login ? 'true' : 'false'; ?>">
            <?php if ( $consent_enabled ) : ?>
                <div class="andw-sct-consent">
                    <label for="<?php echo esc_attr( $consent_id ); ?>">
                        <input type="checkbox" id="<?php echo esc_attr( $consent_id ); ?>" class="andw-sct-consent__input" />
                        <span class="andw-sct-consent__label"><?php echo wp_kses_post( $consent_text ); ?></span>
                    </label>
                </div>
            <?php endif; ?>
            <button type="button" id="<?php echo esc_attr( $button_id ); ?>" class="andw-sct-button" data-label="<?php echo esc_attr( $button_label ); ?>"><?php echo esc_html( $button_label ); ?></button>
            <div class="andw-sct-message" aria-live="polite"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_buttons_group( $atts = [] ) : string {
        $settings = Andw_Sct_Settings::get_settings();
        $atts     = shortcode_atts(
            [
                'group' => $settings['default_button_group'] ?? 'default',
            ],
            $atts,
            'andw_ticket_buttons'
        );

        $group_slug = sanitize_key( $atts['group'] );
        $group      = $settings['button_groups'][ $group_slug ] ?? [];
        $group      = apply_filters( 'andw_sct_buttons_render_args', $group, $group_slug );

        if ( empty( $group ) ) {
            return '<p>' . esc_html__( '指定されたグループにボタンが登録されていません。', 'andw-stripe-checkout-tickets' ) . '</p>';
        }

        $html = '<div class="andw-sct-buttons-group" data-group="' . esc_attr( $group_slug ) . '">';
        foreach ( $group as $button ) {
            $label = $button['label'] ?? '';
            $html .= $this->render_checkout_button(
                [
                    'sku'           => $button['sku'] ?? '',
                    'qty'           => $button['qty'] ?? 1,
                    'label'         => $label ?: __( 'チケットを購入', 'andw-stripe-checkout-tickets' ),
                    'require_login' => ! empty( $button['require_login'] ) ? 'true' : 'false',
                ]
            );
        }
        $html .= '</div>';

        return $html;
    }

    public function render_thanks( $atts = [], $content = '' ) : string {
        $session_id = isset( $_GET['session_id'] ) ? sanitize_text_field( wp_unslash( $_GET['session_id'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( '' === $session_id ) {
            return '<p>' . esc_html__( 'セッションIDが指定されていません。', 'andw-stripe-checkout-tickets' ) . '</p>';
        }

        $summary = $this->checkout->get_session_summary( $session_id );
        if ( is_wp_error( $summary ) ) {
            return '<p>' . esc_html__( '詳細情報を取得できませんでした。Stripe側の状況をご確認ください。', 'andw-stripe-checkout-tickets' ) . '</p>';
        }

        $settings   = Andw_Sct_Settings::get_settings();
        $line_items = $summary['line_items'];
        $session    = $summary['session'];

        ob_start();
        ?>
        <div class="andw-sct-thanks">
            <h2><?php esc_html_e( 'ご購入ありがとうございます', 'andw-stripe-checkout-tickets' ); ?></h2>
            <p><?php esc_html_e( 'ご購入頂いた内容は以下の通りです。', 'andw-stripe-checkout-tickets' ); ?></p>
            <ul class="andw-sct-thanks__list">
                <?php foreach ( $line_items as $item ) : ?>
                    <?php
                    $quantity_text = sprintf(
                        /* translators: %d: purchased quantity. */
                        __( '%d点', 'andw-stripe-checkout-tickets' ),
                        $item['quantity']
                    );
                    ?>
                    <li>
                        <span class="andw-sct-thanks__item-name"><?php echo esc_html( $item['description'] ); ?></span>
                        <span class="andw-sct-thanks__item-qty"><?php echo esc_html( $quantity_text ); ?></span>
                        <span class="andw-sct-thanks__item-amount"><?php echo esc_html( $item['amount_display'] ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="andw-sct-thanks__total">
                <?php
                $total_text = sprintf(
                    /* translators: %s: formatted total purchase amount. */
                    __( '合計: %s', 'andw-stripe-checkout-tickets' ),
                    $summary['amount_total_display']
                );
                echo esc_html( $total_text );
                ?>
            </p>
            <?php if ( ! empty( $session['receipt_url'] ) ) : ?>
                <p><a class="andw-sct-button-link" href="<?php echo esc_url( $session['receipt_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Stripeの領収書を開く', 'andw-stripe-checkout-tickets' ); ?></a></p>
            <?php endif; ?>
            <?php if ( ! empty( $settings['meeting_form_url'] ) ) : ?>
                <p><a class="andw-sct-button-link" href="<?php echo esc_url( $settings['meeting_form_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '初回打ち合わせフォームへ', 'andw-stripe-checkout-tickets' ); ?></a></p>
            <?php endif; ?>
            <p><a class="andw-sct-button-link" href="#andw-sct-register"><?php esc_html_e( 'ユーザー登録（30秒で完了）', 'andw-stripe-checkout-tickets' ); ?></a></p>
            <?php if ( ! empty( $settings['support_link_url'] ) ) : ?>
                <p class="andw-sct-thanks__support">
                    <?php echo wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( $settings['support_link_url'] ), esc_html( $settings['support_link_text'] ) ) ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
        do_action( 'andw_sct_thanks_render', $summary );
        return ob_get_clean();
    }

    public function render_registration_form( $atts = [], $content = '' ) : string {
        return $this->registration->render_form( $atts );
    }

    public function render_mypage( $atts = [], $content = '' ) : string {
        return $this->registration->render_mypage();
    }

    private function get_current_url() : string {
        $request_uri_raw = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
        $request_uri     = is_string( $request_uri_raw ) ? wp_kses_bad_protocol( $request_uri_raw, [ 'http', 'https' ] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- URL path requires protocol validation only.
        if ( '' === $request_uri ) {
            return home_url( '/' );
        }

        $validated = wp_validate_redirect( home_url( $request_uri ), home_url( '/' ) );

        return $validated ?: home_url( '/' );
    }
}
