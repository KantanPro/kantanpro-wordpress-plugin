/**
 * コスト項目テーブルのJavaScript機能
 * 
 * @package KTPWP
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // デバッグモードを有効化
    window.ktpDebugMode = true;

    // 単価×数量の自動計算
    function calculateAmount(row) {
        const price = parseFloat(row.find('.price').val()) || 0;
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const amount = price * quantity;
        row.find('.amount').val(amount);
        
        // 利益計算を更新
        updateProfitDisplay();
    }

    // 利益表示を更新
    function updateProfitDisplay() {
        let invoiceTotal = 0;
        let costTotal = 0;

        // 請求項目の合計を計算
        $('.invoice-items-table .amount').each(function() {
            invoiceTotal += parseFloat($(this).val()) || 0;
        });

        // コスト項目の合計を計算
        $('.cost-items-table .amount').each(function() {
            costTotal += parseFloat($(this).val()) || 0;
        });

        // 請求項目合計を切り上げ
        const invoiceTotalCeiled = Math.ceil(invoiceTotal);
        
        // コスト項目合計を切り上げ
        const costTotalCeiled = Math.ceil(costTotal);
        
        // 利益計算（切り上げ後の値を使用）
        const profit = invoiceTotalCeiled - costTotalCeiled;
        
        // 利益表示を更新
        const profitDisplay = $('.profit-display');
        if (profitDisplay.length > 0) {
            const profitColor = profit >= 0 ? '#28a745' : '#dc3545';
            profitDisplay.html('利益 : ' + profit.toLocaleString() + '円');
            profitDisplay.css('color', profitColor);
            
            // CSSクラスを更新
            profitDisplay.removeClass('positive negative');
            profitDisplay.addClass(profit >= 0 ? 'positive' : 'negative');
        }

        // コスト項目の合計表示も更新（切り上げ後の値を表示）
        const costTotalDisplay = $('.cost-items-total');
        if (costTotalDisplay.length > 0) {
            costTotalDisplay.html('合計金額 : ' + costTotalCeiled.toLocaleString() + '円');
        }
    }

    // 新しい行を追加（重複防止機能付き）
    function addNewRow(currentRow) {
        // 既に追加処理中の場合はスキップ
        if (window.ktpAddingRow) {
            return;
        }
        
        // 追加処理中フラグを設定
        window.ktpAddingRow = true;
        
        const table = currentRow.closest('table');
        const tbody = table.find('tbody');
        // 仮indexで追加し、追加後にindexを正規化
        const newRowHtml = `
            <tr class="cost-item-row" data-row-id="0">
                <td class="actions-column">
                    <span class="drag-handle" title="ドラッグして並び替え">&#9776;</span>
                    <button type="button" class="btn-add-row" title="行を追加">+</button>
                    <button type="button" class="btn-delete-row" title="行を削除">×</button>
                    <button type="button" class="btn-move-row" title="行を移動">></button>
                </td>
                <td>
                    <input type="text" name="cost_items[9999][product_name]" value="" class="cost-item-input product-name" />
                    <input type="hidden" name="cost_items[9999][id]" value="0" />
                </td>
                <td style="text-align:left;">
                    <input type="number" name="cost_items[9999][price]" value="0" class="cost-item-input price" step="1" min="0" style="text-align:left;" />
                </td>
                <td style="text-align:left;">
                    <input type="number" name="cost_items[9999][quantity]" value="1" class="cost-item-input quantity" step="1" min="0" style="text-align:left;" />
                </td>
                <td>
                    <input type="text" name="cost_items[9999][unit]" value="式" class="cost-item-input unit" />
                </td>
                <td style="text-align:left;">
                    <input type="number" name="cost_items[9999][amount]" value="0" class="cost-item-input amount" step="1" readonly style="text-align:left;" />
                </td>
                <td>
                    <input type="text" name="cost_items[9999][remarks]" value="" class="cost-item-input remarks" />
                </td>
            </tr>
        `;
        currentRow.after(newRowHtml);
        updateRowIndexes(table);
        
        // 追加処理完了後にフラグを削除
        setTimeout(() => {
            window.ktpAddingRow = false;
        }, 200);
    }

    // 行を削除
    function deleteRow(currentRow) {
        const table = currentRow.closest('table');
        const tbody = table.find('tbody');
        
        // 最後の1行は削除しない
        if (tbody.find('tr').length <= 1) {
            alert('最低1行は必要です。');
            return;
        }
        
        if (confirm('この行を削除しますか？')) {
            currentRow.remove();
            updateRowIndexes(table);
        }
    }

    // 行のインデックスを更新
    function updateRowIndexes(table) {
        const tbody = table.find('tbody');
        tbody.find('tr').each(function(index) {
            const row = $(this);
            row.find('input, textarea').each(function() {
                const input = $(this);
                const name = input.attr('name');
                if (name && name.match(/^cost_items\[\d+\]/)) {
                    // 先頭の [数字] 部分だけを置換
                    const newName = name.replace(/^cost_items\[\d+\]/, `cost_items[${index}]`);
                    input.attr('name', newName);
                }
            });
        });
    }

    // 自動追加機能を無効化（[+]ボタンのみで行追加）
    function checkAutoAddRow(currentRow) {
        // 自動追加機能を無効化
        // [+]ボタンクリック時のみ行を追加する仕様に変更
        return;
    }

    // 自動保存機能
    function autoSaveItem(itemType, itemId, fieldName, fieldValue, orderId) {
        // Ajax URLの確認と代替設定
        let ajaxUrl = ajaxurl;
        if (!ajaxUrl) {
            ajaxUrl = '/wp-admin/admin-ajax.php';
            console.warn('ajaxurl not defined, using fallback');
        }
        
        const ajaxData = {
            action: 'ktp_auto_save_item',
            item_type: itemType,
            item_id: itemId,
            field_name: fieldName,
            field_value: fieldValue,
            order_id: orderId,
            nonce: ktp_ajax_nonce || ''
        };
        
        console.log('Cost items - Sending Ajax request:', ajaxData);
        console.log('Ajax URL:', ajaxUrl);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                console.log('Cost items - Ajax response received:', response);
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success) {
                        console.log('Cost auto-saved successfully');
                        // 成功時の視覚的フィードバック（オプション）
                        // showSaveIndicator('saved');
                    } else {
                        console.error('Cost auto-save failed:', result.message);
                    }
                } catch (e) {
                    console.error('Cost auto-save response parse error:', e, 'Raw response:', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('Cost auto-save Ajax error:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
            }
        });
    }

    // 新規レコード作成機能
    function createNewItem(itemType, fieldName, fieldValue, orderId, $row) {
        // Ajax URLの確認と代替設定
        let ajaxUrl = ajaxurl;
        if (!ajaxUrl) {
            ajaxUrl = '/wp-admin/admin-ajax.php';
            if (window.ktpDebugMode) {
                console.warn('ajaxurl not defined, using fallback');
            }
        }
        
        const ajaxData = {
            action: 'ktp_create_new_item',
            item_type: itemType,
            field_name: fieldName,
            field_value: fieldValue,
            order_id: orderId,
            nonce: ktp_ajax_nonce || ''
        };
        
        if (window.ktpDebugMode) {
            console.log('Creating new item:', ajaxData);
        }
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                if (window.ktpDebugMode) {
                    console.log('New item creation response:', response);
                }
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success && result.item_id) {
                        // 新しいIDをhidden inputに設定
                        $row.find('input[name*="[id]"]').val(result.item_id);
                        if (window.ktpDebugMode) {
                            console.log('New item created with ID:', result.item_id);
                        }
                    } else {
                        if (window.ktpDebugMode) {
                            console.error('New item creation failed:', result.message);
                        }
                    }
                } catch (e) {
                    if (window.ktpDebugMode) {
                        console.error('New item creation response parse error:', e, 'Raw response:', response);
                    }
                }
            },
            error: function(xhr, status, error) {
                if (window.ktpDebugMode) {
                    console.error('New item creation Ajax error:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                }
            }
        });
    }

    // ページ読み込み完了時の初期化
    $(document).ready(function() {
        // 並び替え（sortable）有効化
        $('.cost-items-table tbody').sortable({
            handle: '.drag-handle',
            items: '> tr',
            axis: 'y',
            helper: 'clone',
            update: function(event, ui) {
                const table = $(this).closest('table');
                updateRowIndexes(table);
            },
            start: function(event, ui) {
                ui.item.addClass('dragging');
            },
            stop: function(event, ui) {
                ui.item.removeClass('dragging');
            }
        }).disableSelection();
        
        // 単価・数量変更時の金額自動計算
        $(document).on('input', '.cost-items-table .price, .cost-items-table .quantity', function() {
            const row = $(this).closest('tr');
            calculateAmount(row);
            
            // 金額を自動保存（変更直後）
            const itemId = row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            const amount = row.find('.amount').val();
            
            if (itemId && orderId && itemId !== '0') {
                autoSaveItem('cost', itemId, 'amount', amount, orderId);
            }
        });

        // 自動追加機能を無効化（コメントアウト）
        // $(document).on('input change', '.cost-items-table .service-name, .cost-items-table .price, .cost-items-table .quantity', function() {
        //     const row = $(this).closest('tr');
        //     const tbody = row.closest('tbody');
        //     const isFirstRow = tbody.find('tr').first().is(row);
        //     
        //     // 手動で行を追加した直後は自動追加をスキップ
        //     if (row.hasClass('manual-add')) {
        //         return;
        //     }
        //     
        //     // 1行目で実際に値が変更された場合のみ自動追加をチェック
        //     if (isFirstRow) {
        //         // 少し遅延を入れて、連続入力による重複を防ぐ
        //         clearTimeout(row.data('autoAddTimeout'));
        //         const timeoutId = setTimeout(function() {
        //             checkAutoAddRow(row);
        //         }, 300); // 300ms後にチェック
        //         row.data('autoAddTimeout', timeoutId);
        //     }
        // });

        // [+]ボタンで行追加（手動追加のみ）- イベント重複を防ぐ
        $(document).off('click', '.cost-items-table .btn-add-row').on('click', '.cost-items-table .btn-add-row', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            // 既に処理中の場合はスキップ
            if ($(this).hasClass('processing')) {
                return false;
            }
            
            // 処理中フラグを設定
            $(this).addClass('processing');
            
            const currentRow = $(this).closest('tr');
            addNewRow(currentRow);
            
            // 処理完了後にフラグを削除
            setTimeout(() => {
                $(this).removeClass('processing');
            }, 100);
            
            return false;
        });

        // 行削除ボタン - イベント重複を防ぐ
        $(document).off('click', '.cost-items-table .btn-delete-row').on('click', '.cost-items-table .btn-delete-row', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const currentRow = $(this).closest('tr');
            deleteRow(currentRow);
        });

        // 行移動ボタン（将来の拡張用）
        $(document).on('click', '.btn-move-row', function(e) {
            e.preventDefault();
            // TODO: ドラッグ&ドロップ機能を実装
            alert('行移動機能は今後実装予定です。');
        });

        // フォーカス時の入力欄スタイル調整
        $(document).on('focus', '.cost-item-input', function() {
            $(this).addClass('focused');
        });

        $(document).on('blur', '.cost-item-input', function() {
            $(this).removeClass('focused');
        });

        // 商品名フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.product-name', function() {
            const $field = $(this);
            const productName = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
            if (window.ktpDebugMode) {
                console.log('Cost product name auto-save debug:', {
                    productName: productName,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }
            
            // 新規行（ID=0）と既存行の両方を処理（商品名は空でも保存）
            if (orderId) {
                if (itemId === '0') {
                    // 新規行の場合：まず新しいレコードを作成
                    createNewItem('cost', 'product_name', productName, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    autoSaveItem('cost', itemId, 'product_name', productName, orderId);
                }
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost product name auto-save skipped - missing required data');
                }
            }
        });

        // 単価フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.price', function() {
            const $field = $(this);
            const price = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
            // 金額を再計算
            calculateAmount($row);
            
            if (window.ktpDebugMode) {
                console.log('Cost price auto-save debug:', {
                    price: price,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }
            
            // 新規行（ID=0）と既存行の両方を処理（価格は0でも保存）
            if (orderId) {
                if (itemId === '0') {
                    // 新規行の場合：まず新しいレコードを作成
                    createNewItem('cost', 'price', price, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    autoSaveItem('cost', itemId, 'price', price, orderId);
                }
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost price auto-save skipped - missing required data');
                }
            }
        });

        // 数量フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.quantity', function() {
            const $field = $(this);
            const quantity = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
            // 金額を再計算
            calculateAmount($row);
            
            if (window.ktpDebugMode) {
                console.log('Cost quantity auto-save debug:', {
                    quantity: quantity,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }
            
            // 新規行（ID=0）と既存行の両方を処理（数量は0でも保存）
            if (orderId) {
                if (itemId === '0') {
                    // 新規行の場合：まず新しいレコードを作成
                    createNewItem('cost', 'quantity', quantity, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    autoSaveItem('cost', itemId, 'quantity', quantity, orderId);
                }
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost quantity auto-save skipped - missing required data');
                }
            }
        });

        // 単位フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.unit', function() {
            const $field = $(this);
            const unit = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
            if (window.ktpDebugMode) {
                console.log('Cost unit auto-save debug:', {
                    unit: unit,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }
            
            // 新規行（ID=0）と既存行の両方を処理（単位は空でも保存）
            if (orderId) {
                if (itemId === '0') {
                    // 新規行の場合：まず新しいレコードを作成
                    createNewItem('cost', 'unit', unit, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    autoSaveItem('cost', itemId, 'unit', unit, orderId);
                }
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost unit auto-save skipped - missing required data');
                }
            }
        });

        // 備考フィールドのblurイベントで自動保存
        $(document).on('blur', '.cost-item-input.remarks', function() {
            const $field = $(this);
            const remarks = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
            if (window.ktpDebugMode) {
                console.log('Cost remarks auto-save debug:', {
                    remarks: remarks,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }
            
            // 新規行（ID=0）と既存行の両方を処理（備考は空でも保存）
            if (orderId) {
                if (itemId === '0') {
                    // 新規行の場合：まず新しいレコードを作成
                    createNewItem('cost', 'remarks', remarks, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    autoSaveItem('cost', itemId, 'remarks', remarks, orderId);
                }
            } else {
                if (window.ktpDebugMode) {
                    console.log('Cost remarks auto-save skipped - missing required data');
                }
            }
        });

        // 初期状態で既存の行に対して金額計算を実行
        $('.cost-items-table tbody tr').each(function() {
            calculateAmount($(this));
        });
    });

})(jQuery);
