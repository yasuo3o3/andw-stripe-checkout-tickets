# andW Stripe Checkout Tickets

WordPress 6.5+ 向け Stripe Checkout 連携プラグインです。チケット販売を Stripe Checkout で行い、サンクスページからのユーザー登録とマイページ導線までを提供します（WooCommerce 不使用）。

## 必要要件
- PHP 8.1 以上
- WordPress 6.5 以上
- Stripe アカウント（Checkout、Webhook を利用）

## インストール
1. プラグインフォルダ `andw-stripe-checkout-tickets/` を `wp-content/plugins/` 配下に配置します。
2. 管理画面からプラグインを有効化します。
3. 有効化後に管理メニュー「andW Tickets」が追加されます。

## 初期設定
1. **API 設定**: Publishable / Secret / Webhook シークレットを入力します。
2. **URL 既定値**: Stripe success/cancel の既定 URL、打ち合わせフォーム、サポートリンク、LINE/チャネルトーク導線を登録します。
3. **SKU ↔ PriceID マッピング**: Stripe の Price ID を SKU ごとに設定します（数量はショートコード側で指定）。
4. **ボタン定義**: `group|sku|label|qty|require_login` 形式で 1 行 1 ボタンを記述します。`default` グループはショートコード未指定時に使用されます。
5. **同意文面**: 必要に応じてチェックボックスと文面を設定します。
6. 「動作テスト」セクションで環境要件を確認します。

## ショートコード
- `andw_checkout` – 単一ボタン。
  - 例: `[andw_checkout sku="60m" qty="1" label="60分プランを購入" require_login="false"]`
  - 任意属性: `success_url`, `cancel_url`, `case_id`
- `andw_ticket_buttons` – グループ化されたボタン群。
  - 例: `[andw_ticket_buttons group="default"]`
- `andw_sct_thanks` – サンクスページ本体。URL クエリ `session_id` を受け取り Stripe から明細を取得します。
- `andw_sct_register` – Stripe セッションと紐づく登録フォーム。`redirect="/mypage/"` で登録後の遷移先を指定できます。
- `andw_mypage` – マイページ雛形（購入ボタン群・履歴・各種リンクを表示）。

## Stripe Webhook
- エンドポイント: `https://{site}/?andw_sct=webhook`
- イベント: `checkout.session.completed`
- 署名検証: 管理画面で登録した Webhook Secret を使用。失敗時は 400 を返します。
- ログ: 独自テーブル `wp_andw_sct_logs` にイベントを保存します（event_id, session_id, amount_total, currency, created_at）。

## 推奨ページ構成
1. **購入導線ページ** – `andw_ticket_buttons` を配置。
2. **サンクスページ** – 固定ページに `andw_sct_thanks` を配置し、成功 URL で指定。
3. **登録ページ** – `andw_sct_register` を配置（`redirect` 属性でマイページ URL 指定）。
4. **マイページ** – `andw_mypage` を配置。

## セキュリティと動作
- すべてのフロントエンド要求は WordPress nonce で保護されています。
- 成功 URL / キャンセル URL の上書きは同一ドメインに限定されます。
- Stripe Webhook は署名検証とタイムスタンプ検証（±5 分）を行います。
- ユーザー登録時に Stripe セッションを再取得し、メールアドレスと `customer_id` を紐付けます。

## テストチェックリスト
- 未ログイン状態で購入ボタン→Checkout→サンクス表示
- サンクスページから登録フォームへ遷移し、新規ユーザーが作成されること
- ログイン済みで購入した場合、Checkout metadata に `wp_user_id` が入ること
- SKU 未設定・不正値で API が 400 を返すこと
- Webhook 署名不一致時に 400 を返し、ログが追加されないこと

## 補足
- プラグイン削除時はオプション・独自テーブル・`andw_sct_stripe_customer_id` メタを削除します。
- `docs/` 以下のドキュメントは配布対象外です。
