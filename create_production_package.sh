#!/bin/bash

# KTPWPプラグイン本番用パッケージング
# 2025年6月7日作成

echo "=== KTPWP本番用パッケージの作成を開始 ==="

# 変数設定
SOURCE_DIR="/Users/kantanpro/DevKinsta/public/kantanpro/wp-content/plugins/KTPWP"
DEST_DIR="/Users/kantanpro/Desktop/KTPWP_TEST_UP"
TEMP_DIR="$DEST_DIR/KTPWP_temp"
PACKAGE_NAME="KTPWP_PRODUCTION_$(date +%Y%m%d_%H%M%S).zip"

# 作業ディレクトリの準備
echo "作業ディレクトリを準備中..."
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR/KTPWP"

# 必須ファイルをコピー
echo "必須ファイルをコピー中..."

# メインファイル
cp "$SOURCE_DIR/ktpwp.php" "$TEMP_DIR/KTPWP/"
cp "$SOURCE_DIR/readme.txt" "$TEMP_DIR/KTPWP/"

# includesディレクトリ（本番必須クラスのみ）
mkdir -p "$TEMP_DIR/KTPWP/includes"
cp "$SOURCE_DIR/includes/class-ktpwp.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-loader.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-main.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-ajax.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-assets.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-database.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-security.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-nonce-manager.php" "$TEMP_DIR/KTPWP/includes/"

# UI・管理系クラス
cp "$SOURCE_DIR/includes/class-ktpwp-client-ui.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-client-db.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-order-ui.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-order.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-order-items.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-service-ui.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-service-db.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-staff-chat.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-shortcodes.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-ui-generator.php" "$TEMP_DIR/KTPWP/includes/"

# 設定・管理系
cp "$SOURCE_DIR/includes/class-ktp-settings.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-setting-db.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-setting-ui.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-setting-template.php" "$TEMP_DIR/KTPWP/includes/"

# タブ管理系
cp "$SOURCE_DIR/includes/class-tab-client.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-tab-order.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-tab-service.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-tab-supplier.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-tab-setting.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-tab-report.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-tab-list.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-view-tab.php" "$TEMP_DIR/KTPWP/includes/"

# ユーティリティ系
cp "$SOURCE_DIR/includes/class-image_processor.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-graph-renderer.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-print.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-contact-form.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-supplier-data.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-supplier-security.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-login-error.php" "$TEMP_DIR/KTPWP/includes/"
cp "$SOURCE_DIR/includes/class-ktpwp-redirect.php" "$TEMP_DIR/KTPWP/includes/"

# 管理フォーム
cp "$SOURCE_DIR/includes/ktp-admin-form.php" "$TEMP_DIR/KTPWP/includes/"

# CSSファイル（本番必須のみ）
mkdir -p "$TEMP_DIR/KTPWP/css"
cp "$SOURCE_DIR/css/styles.css" "$TEMP_DIR/KTPWP/css/"
cp "$SOURCE_DIR/css/ktp-admin-settings.css" "$TEMP_DIR/KTPWP/css/"
cp "$SOURCE_DIR/css/ktp-setting-tab.css" "$TEMP_DIR/KTPWP/css/"
cp "$SOURCE_DIR/css/print.css" "$TEMP_DIR/KTPWP/css/"
cp "$SOURCE_DIR/css/progress-select.css" "$TEMP_DIR/KTPWP/css/"

# JavaScriptファイル（本番必須のみ）
mkdir -p "$TEMP_DIR/KTPWP/js"
cp "$SOURCE_DIR/js/ktp-ajax.js" "$TEMP_DIR/KTPWP/js/"
cp "$SOURCE_DIR/js/ktp-js.js" "$TEMP_DIR/KTPWP/js/"
cp "$SOURCE_DIR/js/ktp-view.js" "$TEMP_DIR/KTPWP/js/"
cp "$SOURCE_DIR/js/ktp-media-upload.js" "$TEMP_DIR/KTPWP/js/"
cp "$SOURCE_DIR/js/ktp-progress-selector.js" "$TEMP_DIR/KTPWP/js/"
cp "$SOURCE_DIR/js/progress-select.js" "$TEMP_DIR/KTPWP/js/"
cp "$SOURCE_DIR/js/ktp-cost-items.js" "$TEMP_DIR/KTPWP/js/"
cp "$SOURCE_DIR/js/ktp-invoice-items.js" "$TEMP_DIR/KTPWP/js/"
cp "$SOURCE_DIR/js/ktp-order-inline-projectname.js" "$TEMP_DIR/KTPWP/js/"

# 画像ファイル（デフォルトのみ）
mkdir -p "$TEMP_DIR/KTPWP/images/default"
cp "$SOURCE_DIR/images/default/dummy_graph.png" "$TEMP_DIR/KTPWP/images/default/"
cp "$SOURCE_DIR/images/default/header_bg_image.png" "$TEMP_DIR/KTPWP/images/default/"
cp "$SOURCE_DIR/images/default/icon.png" "$TEMP_DIR/KTPWP/images/default/"
cp "$SOURCE_DIR/images/default/no-image-icon.jpg" "$TEMP_DIR/KTPWP/images/default/"

# uploadディレクトリは空で作成
mkdir -p "$TEMP_DIR/KTPWP/images/upload"

# endpointsディレクトリは空で作成
mkdir -p "$TEMP_DIR/KTPWP/includes/endpoints"

# ZIPファイルの作成
echo "ZIPファイルを作成中..."
cd "$TEMP_DIR"
zip -r "$DEST_DIR/$PACKAGE_NAME" KTPWP/ -x "*.DS_Store*"

# WordPressプラグイン用の正しい構造でもう一度ZIPを作成
echo "WordPress用構造でZIPファイルを再作成中..."
cd "$DEST_DIR"
rm -f "$PACKAGE_NAME"
cd "$TEMP_DIR"
zip -r "$DEST_DIR/$PACKAGE_NAME" KTPWP/ -x "*.DS_Store*"

# 一時ディレクトリの削除
echo "一時ファイルを削除中..."
rm -rf "$TEMP_DIR"

# 結果表示
echo "=== パッケージング完了 ==="
echo "保存先: $DEST_DIR/$PACKAGE_NAME"
echo "ファイルサイズ: $(du -h "$DEST_DIR/$PACKAGE_NAME" | cut -f1)"
echo ""
echo "=== パッケージ内容 ==="
echo "含まれるファイル数: $(unzip -l "$DEST_DIR/$PACKAGE_NAME" | tail -1 | awk '{print $2}')"
echo ""
echo "=== 除外されたファイル ==="
echo "- 全てのtest-*.php ファイル"
echo "- 全てのdebug-*.php ファイル"
echo "- 全ての*.backup ファイル"
echo "- 開発用設定ファイル (.git, .vscode等)"
echo "- ログファイル"
echo "- 一時ファイル"
echo ""
echo "本番環境にアップロード可能です。"
