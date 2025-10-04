<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap andw-sct-admin">
    <h1><?php esc_html_e( 'andW Tickets 設定', 'andw-sct' ); ?></h1>
    <?php settings_errors(); ?>
    <form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
        <?php settings_fields( 'andw_sct_settings' ); ?>
        <h2 class="title"><?php esc_html_e( 'API設定', 'andw-sct' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="andw-sct-publishable-key"><?php esc_html_e( '公開可能キー (Publishable key)', 'andw-sct' ); ?></label></th>
                <td><input type="text" class="regular-text" id="andw-sct-publishable-key" name="andw_sct_settings[publishable_key]" value="<?php echo esc_attr( ['publishable_key'] ); ?>" autocomplete="off" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-secret-key"><?php esc_html_e( 'シークレットキー (Secret key)', 'andw-sct' ); ?></label></th>
                <td><input type="text" class="regular-text" id="andw-sct-secret-key" name="andw_sct_settings[secret_key]" value="<?php echo esc_attr( ['secret_key'] ); ?>" autocomplete="off" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-webhook-secret"><?php esc_html_e( 'Webhook署名シークレット', 'andw-sct' ); ?></label></th>
                <td><input type="text" class="regular-text" id="andw-sct-webhook-secret" name="andw_sct_settings[webhook_secret]" value="<?php echo esc_attr( ['webhook_secret'] ); ?>" autocomplete="off" /></td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e( 'URL既定値', 'andw-sct' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="andw-sct-success-url"><?php esc_html_e( '成功時URL (success_url)', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-success-url" name="andw_sct_settings[default_success_url]" value="<?php echo esc_attr( ['default_success_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-cancel-url"><?php esc_html_e( 'キャンセル時URL (cancel_url)', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-cancel-url" name="andw_sct_settings[default_cancel_url]" value="<?php echo esc_attr( ['default_cancel_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-meeting-url"><?php esc_html_e( '打ち合わせフォームURL', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-meeting-url" name="andw_sct_settings[meeting_form_url]" value="<?php echo esc_attr( ['meeting_form_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-support-url"><?php esc_html_e( 'サポート／誤報告リンクURL', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-support-url" name="andw_sct_settings[support_link_url]" value="<?php echo esc_attr( ['support_link_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-support-text"><?php esc_html_e( 'サポートリンク文言', 'andw-sct' ); ?></label></th>
                <td><input type="text" class="regular-text" id="andw-sct-support-text" name="andw_sct_settings[support_link_text]" value="<?php echo esc_attr( ['support_link_text'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-line-url"><?php esc_html_e( 'LINE導線URL', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-line-url" name="andw_sct_settings[line_url]" value="<?php echo esc_attr( ['line_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-chat-url"><?php esc_html_e( 'チャネルトークURL', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-chat-url" name="andw_sct_settings[chat_url]" value="<?php echo esc_attr( ['chat_url'] ); ?>" /></td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e( 'SKU ↔ PriceID マッピング', 'andw-sct' ); ?></h2>
        <p class="description"><?php esc_html_e( '各行でSKUに対応するStripe Price IDを設定してください。空欄行は無視されます。', 'andw-sct' ); ?></p>
        <table class="widefat andw-sct-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'SKU', 'andw-sct' ); ?></th>
                    <th><?php esc_html_e( 'Price ID', 'andw-sct' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                 = max( count(  ) + 1, 3 );
                for (  = 0;  < ; ++ ) {
                          = [  ]['sku'] ?? '';
                     = [  ]['price_id'] ?? '';
                    ?>
                    <tr>
                        <td><input type="text" name="andw_sct_settings[sku_price_map][<?php echo esc_attr( (string)  ); ?>][sku]" value="<?php echo esc_attr(  ); ?>" /></td>
                        <td><input type="text" name="andw_sct_settings[sku_price_map][<?php echo esc_attr( (string)  ); ?>][price_id]" value="<?php echo esc_attr(  ); ?>" /></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <h2 class="title"><?php esc_html_e( 'ボタン定義（ショートコード）', 'andw-sct' ); ?></h2>
        <p class="description">
            <?php esc_html_e( '1行につき 1 ボタン。形式: group_slug|sku|表示ラベル|数量|require_login(true/false)', 'andw-sct' ); ?><br />
            <?php esc_html_e( '例: default|60m|60分チケットを購入|1|false', 'andw-sct' ); ?>
        </p>
        <textarea class="large-text code" rows="8" name="andw_sct_settings[button_groups_text]"><?php echo esc_textarea(  ); ?></textarea>
        <p>
            <label for="andw-sct-default-group"><?php esc_html_e( 'デフォルトのボタングループ', 'andw-sct' ); ?></label>
            <select id="andw-sct-default-group" name="andw_sct_settings[default_button_group]">
                <?php foreach ( array_keys( ['button_groups'] ?? [] ) as  ) : ?>
                    <option value="<?php echo esc_attr(  ); ?>" <?php selected( ['default_button_group'],  ); ?>><?php echo esc_html(  ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <h2 class="title"><?php esc_html_e( '同意文面', 'andw-sct' ); ?></h2>
        <p>
            <label>
                <input type="checkbox" name="andw_sct_settings[consent_enabled]" value="1" <?php checked( ['consent_enabled'] ); ?> />
                <?php esc_html_e( '同意チェックボックスを表示する', 'andw-sct' ); ?>
            </label>
        </p>
        <textarea name="andw_sct_settings[consent_text]" class="large-text" rows="4"><?php echo esc_textarea( ['consent_text'] ); ?></textarea>

        <h2 class="title"><?php esc_html_e( '動作テスト', 'andw-sct' ); ?></h2>
        <table class="widefat andw-sct-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( '項目', 'andw-sct' ); ?></th>
                    <th><?php esc_html_e( '状態', 'andw-sct' ); ?></th>
                    <th><?php esc_html_e( '補足', 'andw-sct' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (  as  ) : ?>
                    <tr>
                        <td><?php echo esc_html( ['label'] ); ?></td>
                        <td>
                            <?php if ( ['status'] ) : ?>
                                <span class="andw-sct-status andw-sct-status--ok"><?php esc_html_e( 'OK', 'andw-sct' ); ?></span>
                            <?php else : ?>
                                <span class="andw-sct-status andw-sct-status--ng"><?php esc_html_e( '要確認', 'andw-sct' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( ['value'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php submit_button( __( '設定を保存', 'andw-sct' ) ); ?>
    </form>
</div>
