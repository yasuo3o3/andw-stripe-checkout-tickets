<?php
/**
 * Front-end shortcodes and assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Andw_Sct_Frontend {

    private Andw_Sct_Checkout ;

    private Andw_Sct_Registration ;

    private bool  = false;

    public function __construct( Andw_Sct_Checkout , Andw_Sct_Registration  ) {
        ->checkout     = ;
        ->registration = ;

        add_action( 'wp_enqueue_scripts', [ , 'register_assets' ] );

        add_shortcode( 'andw_checkout', [ , 'render_checkout_button' ] );
        add_shortcode( 'andw_ticket_buttons', [ , 'render_buttons_group' ] );
        add_shortcode( 'andw_sct_thanks', [ , 'render_thanks' ] );
        add_shortcode( 'andw_sct_register', [ , 'render_registration_form' ] );
        add_shortcode( 'andw_mypage', [ , 'render_mypage' ] );
    }

    public function register_assets() : void {
        wp_register_style( 'andw-sct-front', ANDW_SCT_PLUGIN_URL . 'assets/css/front.css', [], ANDW_SCT_VERSION );
        wp_register_script( 'andw-sct-front', ANDW_SCT_PLUGIN_URL . 'assets/js/front.js', [ 'wp-i18n' ], ANDW_SCT_VERSION, true );
        wp_set_script_translations( 'andw-sct-front', 'andw-sct', ANDW_SCT_PLUGIN_DIR . 'languages' );
    }

    private function enqueue_assets( array  ) : void {
        wp_enqueue_style( 'andw-sct-front' );
        wp_enqueue_script( 'andw-sct-front' );

        if ( ->localized ) {
            return;
        }

         = [
            'endpoint'   => ->checkout->get_endpoint_url( 'create_session' ),
            'nonce'      => wp_create_nonce( Andw_Sct_Checkout::NONCE_ACTION ),
            'isLoggedIn' => is_user_logged_in(),
            'consent'    => [
                'enabled' => ! empty( ['consent_enabled'] ),
                'message' => __( '購入前に同意チェックを入れてください。', 'andw-sct' ),
            ],
            'messages'   => [
                'genericError'  => __( 'チェックアウトの開始に失敗しました。時間を置いて再試行してください。', 'andw-sct' ),
                'loginRequired' => __( 'ログインが必要です。', 'andw-sct' ),
                'processing'    => __( '処理中...', 'andw-sct' ),
            ],
        ];

        wp_localize_script( 'andw-sct-front', 'andwSctData',  );
        ->localized = true;
    }

    public function render_checkout_button(  = [] ) : string {
         = shortcode_atts(
            [
                'sku'           => '',
                'qty'           => 1,
                'label'         => __( 'チケットを購入', 'andw-sct' ),
                'require_login' => 'false',
                'success_url'   => '',
                'cancel_url'    => '',
                'case_id'       => '',
            ],
            ,
            'andw_checkout'
        );

         = sanitize_key( ['sku'] );
        if ( '' ===  ) {
            return '<p>' . esc_html__( 'SKUを指定してください。', 'andw-sct' ) . '</p>';
        }

         = Andw_Sct_Settings::get_settings();
         = ->checkout->get_price_id(  );
        if ( !  ) {
            return '<p>' . esc_html__( '指定されたSKUに対応するPrice IDが設定されていません。', 'andw-sct' ) . '</p>';
        }

         = filter_var( ['require_login'], FILTER_VALIDATE_BOOLEAN );
        if (  && ! is_user_logged_in() ) {
             = wp_login_url( add_query_arg( [] ) );
            return sprintf(
                '<p class="andw-sct-login-required">%s</p>',
                wp_kses_post( sprintf( __( '購入にはログインが必要です。<a href="%s">ログインはこちら</a>。', 'andw-sct' ), esc_url(  ) ) )
            );
        }

           = max( 1, absint( ['qty'] ) );
         = sanitize_text_field( ['label'] );
         =  ?  : __( 'チケットを購入', 'andw-sct' );

        ->enqueue_assets(  );

         = ! empty( ['consent_enabled'] );
            = ['consent_text'] ?? '';
               = wp_unique_id( 'andw-sct-btn-' );
              = wp_unique_id( 'andw-sct-consent-' );

        ob_start();
        ?>
        <div class="andw-sct-checkout" data-sku="<?php echo esc_attr(  ); ?>" data-qty="<?php echo esc_attr( (string)  ); ?>" data-success-url="<?php echo esc_attr( ['success_url'] ); ?>" data-cancel-url="<?php echo esc_attr( ['cancel_url'] ); ?>" data-case-id="<?php echo esc_attr( ['case_id'] ); ?>" data-require-login="<?php echo  ? 'true' : 'false'; ?>">
            <?php if (  ) : ?>
                <div class="andw-sct-consent">
                    <label for="<?php echo esc_attr(  ); ?>">
                        <input type="checkbox" id="<?php echo esc_attr(  ); ?>" class="andw-sct-consent__input" />
                        <span class="andw-sct-consent__label"><?php echo wp_kses_post(  ); ?></span>
                    </label>
                </div>
            <?php endif; ?>
            <button type="button" id="<?php echo esc_attr(  ); ?>" class="andw-sct-button" data-label="<?php echo esc_attr(  ); ?>"><?php echo esc_html(  ); ?></button>
            <div class="andw-sct-message" aria-live="polite"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_buttons_group(  = [] ) : string {
         = Andw_Sct_Settings::get_settings();
             = shortcode_atts(
            [
                'group' => ['default_button_group'] ?? 'default',
            ],
            ,
            'andw_ticket_buttons'
        );

           = sanitize_key( ['group'] );
         = ['button_groups'][  ] ?? [];
         = apply_filters( 'andw_sct_buttons_render_args', ,  );

        if ( empty(  ) ) {
            return '<p>' . esc_html__( '指定されたグループにボタンが登録されていません。', 'andw-sct' ) . '</p>';
        }

         = '<div class="andw-sct-buttons-group" data-group="' . esc_attr(  ) . '">';
        foreach (  as  ) {
                     = ['label'] ?? '';
             = ->render_checkout_button(
                [
                    'sku'           => ['sku'] ?? '',
                    'qty'           => ['qty'] ?? 1,
                    'label'         =>  ?: __( 'チケットを購入', 'andw-sct' ),
                    'require_login' => ! empty( ['require_login'] ) ? 'true' : 'false',
                ]
            );
                     .= ;
        }
         .= '</div>';

        return ;
    }

    public function render_thanks(  = [],  = '' ) : string {
         = isset( ['session_id'] ) ? sanitize_text_field( wp_unslash( ['session_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( '' ===  ) {
            return '<p>' . esc_html__( 'セッションIDが指定されていません。', 'andw-sct' ) . '</p>';
        }

         = ->checkout->get_session_summary(  );
        if ( is_wp_error(  ) ) {
            return '<p>' . esc_html__( '購入情報を取得できませんでした。Stripeからのメールを確認してください。', 'andw-sct' ) . '</p>';
        }

           = Andw_Sct_Settings::get_settings();
         = ['line_items'];
            = ['session'];

        ob_start();
        ?>
        <div class="andw-sct-thanks">
            <h2><?php esc_html_e( 'ご購入ありがとうございます', 'andw-sct' ); ?></h2>
            <p><?php esc_html_e( '購入内容を以下に表示します。', 'andw-sct' ); ?></p>
            <ul class="andw-sct-thanks__list">
                <?php foreach (  as  ) : ?>
                    <li>
                        <span class="andw-sct-thanks__item-name"><?php echo esc_html( ['description'] ); ?></span>
                        <span class="andw-sct-thanks__item-qty"><?php echo esc_html( sprintf( __( '%d個', 'andw-sct' ), ['quantity'] ) ); ?></span>
                        <span class="andw-sct-thanks__item-amount"><?php echo esc_html( ['amount_display'] ); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="andw-sct-thanks__total">
                <?php echo esc_html( sprintf( __( '合計: %s', 'andw-sct' ), ['amount_total_display'] ) ); ?>
            </p>
            <?php if ( ! empty( ['receipt_url'] ) ) : ?>
                <p><a class="andw-sct-button-link" href="<?php echo esc_url( ['receipt_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Stripe領収書を開く', 'andw-sct' ); ?></a></p>
            <?php endif; ?>
            <?php if ( ! empty( ['meeting_form_url'] ) ) : ?>
                <p><a class="andw-sct-button-link" href="<?php echo esc_url( ['meeting_form_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( '打ち合わせフォームへ', 'andw-sct' ); ?></a></p>
            <?php endif; ?>
            <p><a class="andw-sct-button-link" href="#andw-sct-register"><?php esc_html_e( 'ユーザー登録（30秒で完了）', 'andw-sct' ); ?></a></p>
            <?php if ( ! empty( ['support_link_url'] ) ) : ?>
                <p class="andw-sct-thanks__support">
                    <?php echo wp_kses_post( sprintf( '<a href="%s">%s</a>', esc_url( ['support_link_url'] ), esc_html( ['support_link_text'] ) ) ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
        do_action( 'andw_sct_thanks_render',  );
        return ob_get_clean();
    }

    public function render_registration_form(  = [],  = '' ) : string {
        return ->registration->render_form(  );
    }

    public function render_mypage(  = [],  = '' ) : string {
        return ->registration->render_mypage();
    }
}
