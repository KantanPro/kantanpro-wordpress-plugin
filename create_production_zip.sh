#!/bin/bash
# 本番用KTPWPプラグインZIPファイル作成スクリプト

# 設定
DEST_DIR="/Users/kantanpro/Desktop/KTPWP_TEST_UP"
PLUGIN_DIR="/Users/kantanpro/Local Sites/kantanpro-local-site/app/public/wp-content/plugins/KTPWP"
TEMP_DIR="/tmp/KTPWP_production"
ZIP_NAME="KTPWP.zip"

echo "🚀 本番用KTPWPプラグインZIPファイルを作成中..."

# 保存先ディレクトリが存在しない場合は作成
mkdir -p "$DEST_DIR"

# 既存のZIPファイルがある場合は削除（上書き）
if [ -f "$DEST_DIR/$ZIP_NAME" ]; then
    echo "📝 既存のZIPファイルを上書きします"
    rm "$DEST_DIR/$ZIP_NAME"
fi

# 一時ディレクトリをクリーン
rm -rf "$TEMP_DIR"
mkdir -p "$TEMP_DIR/KTPWP"

echo "📦 本番用ファイルをコピー中..."

# 本番に必要なファイルのみをコピー（除外ファイルを指定）
rsync -av --progress \
    --exclude='*.git*' \
    --exclude='node_modules' \
    --exclude='*.log' \
    --exclude='*.tmp' \
    --exclude='*.DS_Store' \
    --exclude='__MACOSX' \
    --exclude='thumbs.db' \
    --exclude='*.bak' \
    --exclude='*.backup*' \
    --exclude='*.orig' \
    --exclude='*.rej' \
    --exclude='*.swp' \
    --exclude='*.swo' \
    --exclude='*.sublime-*' \
    --exclude='.vscode' \
    --exclude='.idea' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='composer.json' \
    --exclude='composer.lock' \
    --exclude='gulpfile.js' \
    --exclude='webpack.config.js' \
    --exclude='*.md' \
    --exclude='create_production_zip.sh' \
    --exclude='debug_*.html' \
    --exclude='TEST_*.md' \
    --exclude='*DOCUMENTATION.md' \
    --exclude='*.temp' \
    --exclude='test-*.php' \
    --exclude='Sites/' \
    --exclude='images/upload/*' \
    --exclude='.editorconfig' \
    "$PLUGIN_DIR"/ "$TEMP_DIR/KTPWP/"

# ZIPファイルを作成（macOSの不要ファイルを除外）
echo "🗜️  ZIPファイルを作成中..."
cd "$TEMP_DIR"
zip -r -q "$DEST_DIR/$ZIP_NAME" KTPWP/ -x "*.DS_Store" "*__MACOSX*"

# ファイルサイズを取得
FILE_SIZE=$(ls -lh "$DEST_DIR/$ZIP_NAME" | awk '{print $5}')

# 一時ディレクトリを削除
rm -rf "$TEMP_DIR"

echo "✅ 本番用ZIPファイルが作成されました!"
echo "📍 場所: $DEST_DIR/$ZIP_NAME"
echo "📊 サイズ: $FILE_SIZE"
echo ""
echo "📋 含まれるファイル一覧:"
unzip -l "$DEST_DIR/$ZIP_NAME" | head -20
echo ""
echo "💡 このZIPファイルを本番環境にアップロードできます"
