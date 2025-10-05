<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap andw-sct-admin">
    <h1><?php esc_html_e( 'andW Tickets �ݒ�', 'andw-sct' ); ?></h1>
    <?php settings_errors(); ?>
    <form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
        <?php settings_fields( 'andw_sct_settings' ); ?>
        <h2 class="title"><?php esc_html_e( 'API�ݒ�', 'andw-sct' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="andw-sct-publishable-key"><?php esc_html_e( '���J�\�L�[ (Publishable key)', 'andw-sct' ); ?></label></th>
                <td><input type="text" class="regular-text" id="andw-sct-publishable-key" name="andw_sct_settings[publishable_key]" value="<?php echo esc_attr( $settings['publishable_key'] ); ?>" autocomplete="off" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-secret-key"><?php esc_html_e( '�V�[�N���b�g�L�[ (Secret key)', 'andw-sct' ); ?></label></th>
                <td><input type="text" class="regular-text" id="andw-sct-secret-key" name="andw_sct_settings[secret_key]" value="<?php echo esc_attr( $settings['secret_key'] ); ?>" autocomplete="off" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-webhook-secret"><?php esc_html_e( 'Webhook�����V�[�N���b�g', 'andw-sct' ); ?></label></th>
                <td><input type="text" class="regular-text" id="andw-sct-webhook-secret" name="andw_sct_settings[webhook_secret]" value="<?php echo esc_attr( $settings['webhook_secret'] ); ?>" autocomplete="off" /></td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e( 'URL����l', 'andw-sct' ); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="andw-sct-success-url"><?php esc_html_e( '������URL (success_url)', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-success-url" name="andw_sct_settings[default_success_url]" value="<?php echo esc_attr( $settings['default_success_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-cancel-url"><?php esc_html_e( '�L�����Z����URL (cancel_url)', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-cancel-url" name="andw_sct_settings[default_cancel_url]" value="<?php echo esc_attr( $settings['default_cancel_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-meeting-url"><?php esc_html_e( '�ł����킹�t�H�[��URL', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-meeting-url" name="andw_sct_settings[meeting_form_url]" value="<?php echo esc_attr( $settings['meeting_form_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-support-url"><?php esc_html_e( '�T�|�[�g�^��񍐃����NURL', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-support-url" name="andw_sct_settings[support_link_url]" value="<?php echo esc_attr( $settings['support_link_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-support-text"><?php esc_html_e( '�T�|�[�g�����N����', 'andw-sct' ); ?></label></th>
                <td><input type="text" class="regular-text" id="andw-sct-support-text" name="andw_sct_settings[support_link_text]" value="<?php echo esc_attr( $settings['support_link_text'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-line-url"><?php esc_html_e( 'LINE����URL', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-line-url" name="andw_sct_settings[line_url]" value="<?php echo esc_attr( $settings['line_url'] ); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="andw-sct-chat-url"><?php esc_html_e( '�`���l���g�[�NURL', 'andw-sct' ); ?></label></th>
                <td><input type="url" class="regular-text" id="andw-sct-chat-url" name="andw_sct_settings[chat_url]" value="<?php echo esc_attr( $settings['chat_url'] ); ?>" /></td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e( 'SKU ? PriceID �}�b�s���O', 'andw-sct' ); ?></h2>
        <p class="description"><?php esc_html_e( '�e�s��SKU�ɑΉ�����Stripe Price ID��ݒ肵�Ă��������B�󗓍s�͖�������܂��B', 'andw-sct' ); ?></p>
        <table class="widefat andw-sct-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'SKU', 'andw-sct' ); ?></th>
                    <th><?php esc_html_e( 'Price ID', 'andw-sct' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rows = max( count( $sku_price_map ) + 1, 3 );
                for ( $i = 0; $i < $rows; ++$i ) {
                    $sku_value   = $sku_price_map[ $i ]['sku'] ?? '';
                    $price_value = $sku_price_map[ $i ]['price_id'] ?? '';
                    ?>
                    <tr>
                        <td><input type="text" name="andw_sct_settings[sku_price_map][<?php echo esc_attr( (string) $i ); ?>][sku]" value="<?php echo esc_attr( $sku_value ); ?>" /></td>
                        <td><input type="text" name="andw_sct_settings[sku_price_map][<?php echo esc_attr( (string) $i ); ?>][price_id]" value="<?php echo esc_attr( $price_value ); ?>" /></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <h2 class="title"><?php esc_html_e( '�{�^����`�i�V���[�g�R�[�h�j', 'andw-sct' ); ?></h2>
        <p class="description">
            <?php esc_html_e( '1�s�ɂ� 1 �{�^���B�`��: group_slug|sku|�\�����x��|����|require_login(true/false)', 'andw-sct' ); ?><br />
            <?php esc_html_e( '��: default|60m|60���`�P�b�g���w��|1|false', 'andw-sct' ); ?>
        </p>
        <textarea class="large-text code" rows="8" name="andw_sct_settings[button_groups_text]"><?php echo esc_textarea( $button_groups_text ); ?></textarea>
        <p>
            <label for="andw-sct-default-group"><?php esc_html_e( '�f�t�H���g�̃{�^���O���[�v', 'andw-sct' ); ?></label>
            <select id="andw-sct-default-group" name="andw_sct_settings[default_button_group]">
                <?php foreach ( array_keys( $settings['button_groups'] ?? [] ) as $group_slug ) : ?>
                    <option value="<?php echo esc_attr( $group_slug ); ?>" <?php selected( $settings['default_button_group'], $group_slug ); ?>><?php echo esc_html( $group_slug ); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <h2 class="title"><?php esc_html_e( '���ӕ���', 'andw-sct' ); ?></h2>
        <p>
            <label>
                <input type="checkbox" name="andw_sct_settings[consent_enabled]" value="1" <?php checked( $settings['consent_enabled'] ); ?> />
                <?php esc_html_e( '���Ӄ`�F�b�N�{�b�N�X��\������', 'andw-sct' ); ?>
            </label>
        </p>
        <textarea name="andw_sct_settings[consent_text]" class="large-text" rows="4"><?php echo esc_textarea( $settings['consent_text'] ); ?></textarea>

        <h2 class="title"><?php esc_html_e( '����e�X�g', 'andw-sct' ); ?></h2>
        <table class="widefat andw-sct-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( '����', 'andw-sct' ); ?></th>
                    <th><?php esc_html_e( '���', 'andw-sct' ); ?></th>
                    <th><?php esc_html_e( '�⑫', 'andw-sct' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $environment_checks as $check ) : ?>
                    <tr>
                        <td><?php echo esc_html( $check['label'] ); ?></td>
                        <td>
                            <?php if ( $check['status'] ) : ?>
                                <span class="andw-sct-status andw-sct-status--ok"><?php esc_html_e( 'OK', 'andw-sct' ); ?></span>
                            <?php else : ?>
                                <span class="andw-sct-status andw-sct-status--ng"><?php esc_html_e( '�v�m�F', 'andw-sct' ); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( $check['value'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php submit_button( __( '�ݒ��ۑ�', 'andw-sct' ) ); ?>
    </form>
</div>
