/**
 * 請求項目テーブルのJavaScript機能
 * 
 * @package KTPWP
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // グローバルスコープに関数を定義
    window.autoSaveItem = function(itemType, itemId, fieldName, fieldValue, orderId) {
        // Ajax URLの確認と代替設定
        let ajaxUrl = ajaxurl;
        if (!ajaxUrl) {
            ajaxUrl = '/wp-admin/admin-ajax.php';
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
        
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success) {
                    } else {
                    }
                } catch (e) {
                }
            },
            error: function(xhr, status, error) {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
            }
        });
    };

    window.createNewItem = function(itemType, fieldName, fieldValue, orderId, $row) {
        // Ajax URLの確認と代替設定
        let ajaxUrl = ajaxurl;
        if (!ajaxUrl) {
            ajaxUrl = '/wp-admin/admin-ajax.php';
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
        
        }
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: ajaxData,
            success: function(response) {
                }
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    if (result.success && result.item_id) {
                        // 新しいIDをhidden inputに設定
                        $row.find('input[name*="[id]"]').val(result.item_id);
                        }
                    } else {
                        }
                    }
                } catch (e) {
                    }
                }
            },
            error: function(xhr, status, error) {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                }
            }
        });
    };

    // 価格×数量の自動計算
    function calculateAmount(row) {
        const price = parseFloat(row.find('.price').val()) || 0;
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const amount = price * quantity;
        row.find('.amount').val(amount);
        
        // 金額を自動保存
        const itemId = row.find('input[name*="[id]"]').val();
        const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
        
        if (itemId && orderId) {
            if (itemId === '0') {
                // 新規行の場合は何もしない（商品名入力時に新規作成される）
            } else {
                // 既存行の場合：金額を自動保存
                window.autoSaveItem('invoice', itemId, 'amount', amount, orderId);
            }
        }
        
        // 請求項目合計と利益表示を更新
        updateTotalAndProfit();
    }

    // 請求項目合計と利益表示を更新
    function updateTotalAndProfit() {
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
        
        // 請求項目の合計表示を更新（切り上げ後の値を表示）
        const invoiceTotalDisplay = $('.invoice-items-total');
        if (invoiceTotalDisplay.length > 0) {
            invoiceTotalDisplay.html('合計金額 : ' + invoiceTotalCeiled.toLocaleString() + '円');
        }

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
    }

    // 新しい行を追加（重複防止機能付き）
    function addNewRow(currentRow) {
        // 既に追加処理中の場合はスキップ
        if (window.ktpAddingInvoiceRow) {
            return;
        }
        
        // 追加処理中フラグを設定
        window.ktpAddingInvoiceRow = true;
        
        const table = currentRow.closest('table');
        const tbody = table.find('tbody');
        // 仮indexで追加し、追加後にindexを正規化
        const newRowHtml = `
            <tr class="invoice-item-row" data-row-id="0">
                <td class="actions-column">
                    <span class="drag-handle" title="ドラッグして並び替え">&#9776;</span>
                    <button type="button" class="btn-add-row" title="行を追加">+</button>
                    <button type="button" class="btn-delete-row" title="行を削除">×</button>
                    <button type="button" class="btn-move-row" title="行を移動">></button>
                </td>
                <td>
                    <input type="text" name="invoice_items[9999][product_name]" value="" class="invoice-item-input product-name" />
                    <input type="hidden" name="invoice_items[9999][id]" value="0" />
                </td>
                <td style="text-align:left;">
                    <input type="number" name="invoice_items[9999][price]" value="0" class="invoice-item-input price" step="1" min="0" style="text-align:left;" />
                </td>
                <td style="text-align:left;">
                    <input type="number" name="invoice_items[9999][quantity]" value="1" class="invoice-item-input quantity" step="1" min="0" style="text-align:left;" />
                </td>
                <td>
                    <input type="text" name="invoice_items[9999][unit]" value="式" class="invoice-item-input unit" />
                </td>
                <td style="text-align:left;">
                    <input type="number" name="invoice_items[9999][amount]" value="0" class="invoice-item-input amount" step="1" readonly style="text-align:left;" />
                </td>
                <td>
                    <input type="text" name="invoice_items[9999][remarks]" value="" class="invoice-item-input remarks" />
                </td>
            </tr>
        `;
        currentRow.after(newRowHtml);
        updateRowIndexes(table);
        
        // 追加処理完了後にフラグを削除
        setTimeout(() => {
            window.ktpAddingInvoiceRow = false;
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
                if (name && name.match(/^invoice_items\[\d+\]/)) {
                    // 先頭の [数字] 部分だけを置換
                    const newName = name.replace(/^invoice_items\[\d+\]/, `invoice_items[${index}]`);
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

    // ページ読み込み完了時の初期化
    $(document).ready(function() {
        // デバッグモードを有効化
        
        // 並び替え（sortable）有効化
        $('.invoice-items-table tbody').sortable({
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
        
        // 価格・数量変更時の金額自動計算
        $(document).on('input', '.invoice-items-table .price, .invoice-items-table .quantity', function() {
            const row = $(this).closest('tr');
            calculateAmount(row);
        });

        // 自動追加機能を無効化（コメントアウト）
        // $(document).on('input', '.invoice-items-table .product-name, .invoice-items-table .price, .invoice-items-table .quantity', function() {
        //     const row = $(this).closest('tr');
        //     const tbody = row.closest('tbody');
        //     const isFirstRow = tbody.find('tr').first().is(row);
        //     
        //     if (isFirstRow) {
        //         checkAutoAddRow(row);
        //     }
        // });

        // [+]ボタンで行追加（手動追加のみ）- イベント重複を防ぐ
        $(document).off('click', '.invoice-items-table .btn-add-row').on('click', '.invoice-items-table .btn-add-row', function(e) {
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
        $(document).off('click', '.invoice-items-table .btn-delete-row').on('click', '.invoice-items-table .btn-delete-row', function(e) {
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
        $(document).on('focus', '.invoice-item-input', function() {
            $(this).addClass('focused');
        });

        $(document).on('blur', '.invoice-item-input', function() {
            $(this).removeClass('focused');
        });

        // サービスフィールドのblurイベントで自動保存
        $(document).on('blur', '.invoice-item-input.product-name', function() {
            const $field = $(this);
            const productName = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
                    productName: productName,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }
            
            // 新規行（ID=0）の場合は新規作成、既存行は更新
            if (productName.trim() !== '' && orderId) {
                if (itemId === '0') {
                    // 新規行の場合：最初にDBに新しいレコードを作成
                    window.createNewItem('invoice', 'product_name', productName, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    window.autoSaveItem('invoice', itemId, 'product_name', productName, orderId);
                }
            } else {
                }
            }
        });

        // 単価フィールドのblurイベントで自動保存
        $(document).on('blur', '.invoice-item-input.price', function() {
            const $field = $(this);
            const price = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
            // 金額を再計算
            calculateAmount($row);
            
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
                    window.createNewItem('invoice', 'price', price, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    window.autoSaveItem('invoice', itemId, 'price', price, orderId);
                }
            } else {
                }
            }
        });

        // 数量フィールドのblurイベントで自動保存
        $(document).on('blur', '.invoice-item-input.quantity', function() {
            const $field = $(this);
            const quantity = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
            // 金額を再計算
            calculateAmount($row);
            
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
                    window.createNewItem('invoice', 'quantity', quantity, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    window.autoSaveItem('invoice', itemId, 'quantity', quantity, orderId);
                }
            } else {
                }
            }
        });

        // 備考フィールドのblurイベントで自動保存
        $(document).on('blur', '.invoice-item-input.remarks', function() {
            const $field = $(this);
            const remarks = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
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
                    window.createNewItem('invoice', 'remarks', remarks, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    window.autoSaveItem('invoice', itemId, 'remarks', remarks, orderId);
                }
            } else {
                }
            }
        });

        // ユニットフィールドのblurイベントで自動保存
        $(document).on('blur', '.invoice-item-input.unit', function() {
            const $field = $(this);
            const unit = $field.val();
            const $row = $field.closest('tr');
            const itemId = $row.find('input[name*="[id]"]').val();
            const orderId = $('input[name="order_id"]').val() || $('#order_id').val();
            
                    unit: unit,
                    itemId: itemId,
                    orderId: orderId,
                    hasNonce: typeof ktp_ajax_nonce !== 'undefined',
                    hasAjaxurl: typeof ajaxurl !== 'undefined'
                });
            }
            
            // 新規行（ID=0）と既存行の両方を処理（ユニットは空でも保存）
            if (orderId) {
                if (itemId === '0') {
                    // 新規行の場合：まず新しいレコードを作成
                    window.createNewItem('invoice', 'unit', unit, orderId, $row);
                } else if (itemId) {
                    // 既存行の場合：通常の更新処理
                    window.autoSaveItem('invoice', itemId, 'unit', unit, orderId);
                }
            } else {
                }
            }
        });

        // 初期状態で既存の行に対して金額計算を実行
        $('.invoice-items-table tbody tr').each(function() {
            calculateAmount($(this));
        });
    });

})(jQuery);
