document.addEventListener('DOMContentLoaded', function () {
    // スクロールタイマーを保存する変数（グローバルスコープ）
    window.scrollTimeouts = [];

    // デバッグモードの設定を取得（PHPから渡される）
    window.ktpDebugMode = typeof ktpwpDebugMode !== 'undefined' ? ktpwpDebugMode : false;

    // スクロールタイマーをクリアする関数（グローバルスコープ）
    window.clearScrollTimeouts = function () {
        if (window.ktpDebugMode) {
            console.log('スクロールタイマーをクリア中:', window.scrollTimeouts.length + '個のタイマー');
        }
        window.scrollTimeouts.forEach(function (timeout) {
            clearTimeout(timeout);
        });
        window.scrollTimeouts = [];
        if (window.ktpDebugMode) {
            console.log('スクロールタイマーのクリア完了');
        }
    };

    // 通知バッジを削除（グローバルスコープ）
    window.hideNewMessageNotification = function () {
        var toggleBtn = document.getElementById('staff-chat-toggle-btn');
        if (!toggleBtn) return;

        var badge = toggleBtn.querySelector('.staff-chat-notification-badge');
        if (badge) {
            badge.remove();
            if (window.ktpDebugMode) {
                console.log('通知バッジを削除しました');
            }
        }
    };

    // コスト項目トグル
    var costToggleBtn = document.querySelector('.toggle-cost-items');
    var costContent = document.getElementById('cost-items-content');
    if (costToggleBtn && costContent) {
        // 初期状態を非表示に設定
        costContent.style.display = 'none';
        costToggleBtn.setAttribute('aria-expanded', 'false');

        // 項目数を取得してボタンテキストに追加
        var updateCostButtonText = function () {
            var itemCount = costContent.querySelectorAll('.cost-items-table tbody tr').length || 0;
            var showLabel = costToggleBtn.dataset.showLabel || '表示';
            var hideLabel = costToggleBtn.dataset.hideLabel || '非表示';
            var isExpanded = costToggleBtn.getAttribute('aria-expanded') === 'true';
            costToggleBtn.textContent = (isExpanded ? hideLabel : showLabel) + '（' + itemCount + '項目）';
        };

        costToggleBtn.addEventListener('click', function () {
            var expanded = costToggleBtn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                costContent.style.display = 'none';
                costToggleBtn.setAttribute('aria-expanded', 'false');
            } else {
                costContent.style.display = '';
                costToggleBtn.setAttribute('aria-expanded', 'true');
            }
            updateCostButtonText();
        });

        // 国際化ラベル
        costToggleBtn.dataset.showLabel = costToggleBtn.title = (window.ktpwpCostShowLabel || '表示');
        costToggleBtn.dataset.hideLabel = (window.ktpwpCostHideLabel || '非表示');

        // 初期状態のボタンテキストを設定
        updateCostButtonText();
    }

    // スタッフチャットトグル
    var staffChatToggleBtn = document.querySelector('.toggle-staff-chat');
    var staffChatContent = document.getElementById('staff-chat-content');
    if (staffChatToggleBtn && staffChatContent) {
        // URLパラメータでチャットを開く状態を確認
        var urlParams = new URLSearchParams(window.location.search);
        // デフォルトでは表示状態（chat_open=0が明示的に指定された場合のみ非表示）
        var chatShouldBeOpen = urlParams.get('chat_open') !== '0';
        var messageSent = urlParams.get('message_sent') === '1';

        // チャットを開くのは、デフォルトまたはメッセージ送信直後
        var shouldOpenChat = chatShouldBeOpen;

        // デバッグモードでのみログ出力
        if (window.ktpDebugMode) {
            console.log('Chat parameters:', {
                chat_open: urlParams.get('chat_open'),
                message_sent: urlParams.get('message_sent'),
                shouldOpenChat: shouldOpenChat
            });
        }

        // 自動スクロール関数
        var scrollToBottom = function () {
            // チャットが閉じている場合はスクロールしない
            var chatContent = document.getElementById('staff-chat-content');
            if (!chatContent || chatContent.style.display === 'none') {
                if (window.ktpDebugMode) {
                    console.log('チャットが閉じているためスクロールをスキップ');
                }
                return;
            }

            // チャットトグルボタンの状態もチェック
            var toggleBtn = document.querySelector('.toggle-staff-chat');
            if (toggleBtn && toggleBtn.getAttribute('aria-expanded') !== 'true') {
                if (window.ktpDebugMode) {
                    console.log('チャットトグルが閉じているためスクロールをスキップ');
                }
                return;
            }

            // 既存のスクロールタイマーをクリア
            if (window.ktpDebugMode) {
                console.log('スクロール開始 - 既存タイマーをクリア');
            }
            window.clearScrollTimeouts();

            // まずチャットセクションまでページをスクロール
            var chatSection = document.querySelector('.order_memo_box h4');
            if (chatSection && chatSection.textContent.includes('スタッフチャット')) {
                chatSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // メッセージエリアのスクロール処理
            var scrollMessages = function () {
                // スクロール実行前に再度チャット状態をチェック
                var currentChatContent = document.getElementById('staff-chat-content');
                if (!currentChatContent || currentChatContent.style.display === 'none') {
                    if (window.ktpDebugMode) {
                        console.log('スクロール実行時：チャットが閉じているためスクロールを中止');
                    }
                    return false;
                }

                var messagesContainer = document.getElementById('staff-chat-messages');
                if (messagesContainer) {
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    return true;
                } else {
                    // fallback: staff-chat-contentをスクロール
                    if (currentChatContent) {
                        currentChatContent.scrollTop = currentChatContent.scrollHeight;
                        return true;
                    }
                }
                return false;
            };

            // 複数回試行してスクロール（タイマーIDを保存）
            if (window.ktpDebugMode) {
                console.log('スクロールタイマーを設定中（300ms, 800ms, 1500ms）');
            }
            window.scrollTimeouts.push(setTimeout(function () {
                scrollMessages();
            }, 300));

            window.scrollTimeouts.push(setTimeout(function () {
                scrollMessages();
            }, 800));

            window.scrollTimeouts.push(setTimeout(function () {
                scrollMessages();
            }, 1500));

            if (window.ktpDebugMode) {
                console.log('スクロールタイマー設定完了:', window.scrollTimeouts.length + '個のタイマー');
            }
        };

        // 初期状態を設定（デフォルトで表示、chat_open=0の場合のみ非表示）
        if (shouldOpenChat) {
            if (window.ktpDebugMode) {
                console.log('チャットをデフォルト表示状態で初期化');
            }
            staffChatContent.style.display = 'block';
            staffChatToggleBtn.setAttribute('aria-expanded', 'true');

            // メッセージ送信後（message_sent=1パラメータ）の場合のみ自動スクロール
            if (messageSent) {
                scrollToBottom();

                // スクロール実行後、URLからパラメータを削除
                var newUrl = new URL(window.location);
                newUrl.searchParams.delete('message_sent');
                newUrl.searchParams.delete('chat_open'); // chat_openも削除
                window.history.replaceState({}, '', newUrl);
            }
        } else {
            if (window.ktpDebugMode) {
                console.log('chat_open=0が指定されているため、チャットを閉じた状態で初期化');
            }
            staffChatContent.style.display = 'none';
            staffChatToggleBtn.setAttribute('aria-expanded', 'false');
        }

        // 項目数を取得してボタンテキストに追加
        var updateStaffChatButtonText = function () {
            // 1行目（初期メッセージ）を除外して、2行目以降のメッセージのみをカウント
            var scrollableMessages = staffChatContent.querySelectorAll('.staff-chat-message.scrollable');
            var messageCount = scrollableMessages.length || 0;

            // 空のメッセージ表示（.staff-chat-empty）がある場合は0にする
            var emptyMessage = staffChatContent.querySelector('.staff-chat-empty');
            if (emptyMessage) {
                messageCount = 0;
            }

            var showLabel = staffChatToggleBtn.dataset.showLabel || '表示';
            var hideLabel = staffChatToggleBtn.dataset.hideLabel || '非表示';
            var isExpanded = staffChatToggleBtn.getAttribute('aria-expanded') === 'true';
            staffChatToggleBtn.textContent = (isExpanded ? hideLabel : showLabel) + '（' + messageCount + 'メッセージ）';
        };

        staffChatToggleBtn.addEventListener('click', function () {
            var expanded = staffChatToggleBtn.getAttribute('aria-expanded') === 'true';
            if (expanded) {
                // チャットを閉じる時：スクロール処理を停止
                if (window.ktpDebugMode) {
                    console.log('チャットを閉じる - スクロール処理を停止');
                }
                window.clearScrollTimeouts();
                staffChatContent.style.display = 'none';
                staffChatToggleBtn.setAttribute('aria-expanded', 'false');
            } else {
                if (window.ktpDebugMode) {
                    console.log('チャットを開く');
                }
                staffChatContent.style.display = 'block';
                staffChatToggleBtn.setAttribute('aria-expanded', 'true');

                // チャットを開いた時に通知バッジを非表示
                window.hideNewMessageNotification();

                // 手動でチャットを開いた場合はスクロールしない（メッセージ送信後のみスクロール）
                // scrollToBottom(); // この行をコメントアウト
            }
            updateStaffChatButtonText();
        });

        // 国際化ラベル
        staffChatToggleBtn.dataset.showLabel = (window.ktpwpStaffChatShowLabel || '表示');
        staffChatToggleBtn.dataset.hideLabel = (window.ktpwpStaffChatHideLabel || '非表示');

        // 初期状態のボタンテキストを設定
        updateStaffChatButtonText();

        // ページ読み込み完了後、メッセージ送信直後の場合のみ再度スクロール
        if (shouldOpenChat && messageSent) {
            window.addEventListener('load', function () {
                setTimeout(function () {
                    scrollToBottom();

                    // 最終スクロール実行後、URLからパラメータを削除
                    var newUrl = new URL(window.location);
                    newUrl.searchParams.delete('message_sent');
                    newUrl.searchParams.delete('chat_open');
                    window.history.replaceState({}, '', newUrl);
                }, 1000);
            });
        }
    }

    var tabs = document.querySelectorAll('.tab_item');
    var contents = document.querySelectorAll('.tab_content');

    // URLからタブ名を取得
    var searchParams = new URLSearchParams(window.location.search);
    var currentTab = searchParams.get('tab_name') || 'list';

    // 該当するタブにアクティブクラスを追加
    tabs.forEach(function (tab) {
        var tabHref = tab.querySelector('a').getAttribute('href');
        var tabName = new URLSearchParams(tabHref.split('?')[1]).get('tab_name');

        if (tabName === currentTab) {
            tab.classList.add('active');
        } else {
            tab.classList.remove('active');
        }
    });

    // チャット関連パラメータのクリーンアップ（タブ切り替え時）
    // デフォルトで表示になったので、chat_open=1は不要になった
    var currentParams = new URLSearchParams(window.location.search);
    var hasMessageSent = currentParams.get('message_sent') === '1';
    var hasChatOpen = currentParams.get('chat_open');

    // chat_open=1 または message_sent以外のchat_openパラメータがある場合は削除
    if (hasChatOpen && hasChatOpen !== '0' && !hasMessageSent) {
        if (window.ktpDebugMode) {
            console.log('タブ処理: 不要なchat_openパラメータを削除');
        }
        var cleanUrl = new URL(window.location);
        cleanUrl.searchParams.delete('chat_open');
        window.history.replaceState({}, '', cleanUrl);
    }

    // 旧コード（互換性のために残しておく）
    var defaultTab = document.getElementById('tab-list');
    var defaultContent = document.getElementById('content-list');
    if (defaultTab && defaultContent) {
        defaultTab.classList.add('active');
        defaultContent.classList.add('active');
    }
});

// 削除ボタンを押したときの確認ダイアログ（フォームベース削除対応）
function confirmDelete(formElement) {
    if (confirm("本当に削除しますか？この操作は元に戻せません。")) {
        return true; // フォーム送信を続行
    }
    return false; // フォーム送信をキャンセル
}

// 旧式の削除機能（下位互換性のため残す）
function confirmDeleteLegacy(id) {
    console.warn('KTPWP: Legacy delete function used. Please update to form-based deletion.');
    var tab_name = "your_tab_name"; // Replace "your_tab_name" with the actual tab name
    var query_post = "your_query_post"; // Replace "your_query_post" with the actual query post
    if (confirm("Are you sure you want to delete this item?")) {
        window.location.href = "?tab_name=" + tab_name + "&data_id=" + id + "&query_post=" + query_post;
    }
}

// ログアウト時にログイン中のユーザーを表示する
jQuery(document).ready(function ($) {
    $('#logout_link').click(function (e) {
        e.preventDefault();

        $.post(ajaxurl, { action: 'get_logged_in_users' }, function (response) {
            var users = JSON.parse(response);
            var users_html = users.join('、');
            $('.ktp_header').html(users_html);
        });

        window.location.href = $(this).attr('href');
    });
});

// ポップアップ要素を作成
var popupElement = document.createElement('div');
popupElement.id = 'popupElement';
document.body.appendChild(popupElement);

// ポップアップのスタイルを設定する
popupElement.style.position = 'fixed';
popupElement.style.top = '50%';
popupElement.style.left = '50%';
popupElement.style.transform = 'translate(-50%, -50%)';
popupElement.style.backgroundColor = 'rgba(0,0,0,0.8)';
popupElement.style.color = '#fff';
popupElement.style.padding = '40px';
popupElement.style.zIndex = '1500';
popupElement.style.width = '90%';
popupElement.style.maxWidth = '650px';
popupElement.style.border = '2px solid #444';
popupElement.style.borderRadius = '10px';
popupElement.style.boxShadow = '0 8px 16px rgba(0,0,0,0.2)';
popupElement.style.textAlign = 'center';
popupElement.style.fontFamily = '"Helvetica Neue", Helvetica, Arial, sans-serif';

// 初期状態で非表示に設定
popupElement.style.display = 'none';

// 必要なときに表示する関数を追加
function showPopup(message) {
    popupElement.textContent = message;
    popupElement.style.display = 'block';
}

function hidePopup() {
    popupElement.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
    var activeTab = document.querySelector('.printer button.active');
    if (activeTab) {
        activeTab.style.backgroundColor = '#ccc';
        activeTab.style.color = '#3b3b3b';
    }
});

// ピンク背景の美しいフローティング通知システム
let ktpNotificationContainer = null;

function createKtpNotificationContainer() {
    if (!ktpNotificationContainer) {
        ktpNotificationContainer = document.createElement('div');
        ktpNotificationContainer.id = 'ktp-notification-container';
        document.body.appendChild(ktpNotificationContainer);
    }
    return ktpNotificationContainer;
}

function showKtpNotification(message, type = 'success', duration = 4000) {
    const container = createKtpNotificationContainer();

    // 通知要素を作成
    const notification = document.createElement('div');
    notification.className = `ktp-floating-notification ${type}`;
    notification.textContent = message;

    // コンテナに追加
    container.appendChild(notification);

    // アニメーション開始
    setTimeout(() => {
        notification.classList.add('show', 'slide-in');
    }, 10);

    // 自動で消える処理
    setTimeout(() => {
        notification.classList.add('slide-out');
        notification.classList.remove('show');

        setTimeout(() => {
            if (container.contains(notification)) {
                container.removeChild(notification);
            }
        }, 300);
    }, duration);

    return notification;
}

// 便利な関数群
function showSuccessNotification(message, duration = 4000) {
    return showKtpNotification(message, 'success', duration);
}

function showErrorNotification(message, duration = 4000) {
    return showKtpNotification(message, 'error', duration);
}

function showWarningNotification(message, duration = 4000) {
    return showKtpNotification(message, 'warning', duration);
}

function showInfoNotification(message, duration = 4000) {
    return showKtpNotification(message, 'info', duration);
}

// ピンクバック通知専用関数（メール送信成功時用）
function showPinkbackNotification(message, duration = 5000) {
    return showKtpNotification(message, 'pinkback', duration);
}

// 既存のalert()やconfirm()を置き換える関数
function ktpAlert(message, type = 'info') {
    showKtpNotification(message, type);
}

function ktpConfirm(message, callback) {
    if (confirm(message)) {
        if (callback) callback();
        return true;
    }
    return false;
}

// グローバル変数として公開（WordPressのPHP側からも使用可能にする）
window.showKtpNotification = showKtpNotification;
window.showSuccessNotification = showSuccessNotification;
window.showErrorNotification = showErrorNotification;
window.showWarningNotification = showWarningNotification;
window.showInfoNotification = showInfoNotification;
window.ktpAlert = ktpAlert;
window.showPinkbackNotification = showPinkbackNotification;

// Staff Chat Functions
document.addEventListener('DOMContentLoaded', function () {
    // スタッフチャットメッセージエリアの自動スクロール
    function scrollToBottom() {
        // チャットが閉じている場合はスクロールしない
        var chatContent = document.getElementById('staff-chat-content');
        if (!chatContent || chatContent.style.display === 'none') {
            if (window.ktpDebugMode) {
                console.log('Staff Chat Functions: チャットが閉じているためスクロールをスキップ');
            }
            return;
        }

        // チャットトグルボタンの状態もチェック
        var toggleBtn = document.querySelector('.toggle-staff-chat');
        if (toggleBtn && toggleBtn.getAttribute('aria-expanded') !== 'true') {
            if (window.ktpDebugMode) {
                console.log('Staff Chat Functions: チャットトグルが閉じているためスクロールをスキップ');
            }
            return;
        }

        var messagesContainer = document.getElementById('staff-chat-messages');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
    }

    // 初期ロード時に最下部にスクロール（チャットが開いている場合のみ）
    setTimeout(function () {
        scrollToBottom();
    }, 100); // 少し遅延させてDOM要素の準備を待つ

    // フォーム送信の処理
    var chatForm = document.getElementById('staff-chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function (e) {
            e.preventDefault(); // デフォルトのフォーム送信を防ぐ

            var messageInput = document.getElementById('staff-chat-input');
            var submitButton = document.getElementById('staff-chat-submit');
            var orderId = document.querySelector('input[name="staff_chat_order_id"]')?.value;

            if (!messageInput || messageInput.value.trim() === '') {
                messageInput.focus();
                return false;
            }

            if (!orderId) {
                console.error('注文IDが見つかりません');
                return false;
            }

            // 送信ボタンを一時的に無効化
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = '送信中...';
            }

            // AJAX でメッセージを送信
            var xhr = new XMLHttpRequest();
            var url = (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.ajax_url) ? ktpwp_ajax.ajax_url :
                (typeof ajaxurl !== 'undefined') ? ajaxurl :
                    window.location.origin + '/wp-admin/admin-ajax.php';
            var params = 'action=send_staff_chat_message&order_id=' + orderId + '&message=' + encodeURIComponent(messageInput.value.trim());

            // デバッグ情報出力
            console.log('スタッフチャット送信:', {
                url: url,
                orderId: orderId,
                message: messageInput.value.trim(),
                hasKtpwpAjax: typeof ktpwp_ajax !== 'undefined',
                hasNonces: typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces,
                hasStaffChatNonce: typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat
            });

            // nonceを追加
            if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat) {
                params += '&_ajax_nonce=' + ktpwp_ajax.nonces.staff_chat;
                console.log('nonce追加:', ktpwp_ajax.nonces.staff_chat);
            } else {
                console.warn('スタッフチャット: nonceが設定されていません - 送信を試行します');
                // nonceがなくても送信を試行（サーバー側でログインチェックに依存）
            }

            xhr.open('POST', url, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

            console.log('送信パラメータ:', params);
            console.log('送信URL:', url);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4) {
                    console.log('AJAX レスポンス:', {
                        status: xhr.status,
                        responseText: xhr.responseText.substring(0, 500) + (xhr.responseText.length > 500 ? '...' : '')
                    });

                    // 送信ボタンを復元
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = '送信';
                    }

                    if (xhr.status === 200) {
                        // 詳細なレスポンス分析
                        console.log('=== レスポンス詳細分析 ===');
                        console.log('Status:', xhr.status);
                        console.log('ContentType:', xhr.getResponseHeader('Content-Type'));
                        console.log('ResponseText Length:', xhr.responseText.length);
                        console.log('ResponseText (first 500 chars):', xhr.responseText.substring(0, 500));
                        console.log('ResponseText (hex first 100 bytes):',
                            Array.from(xhr.responseText.substring(0, 100))
                                .map(c => c.charCodeAt(0).toString(16).padStart(2, '0'))
                                .join(' '));

                        try {
                            var response = JSON.parse(xhr.responseText);
                            console.log('✅ JSON パース成功:', response);

                            if (response.success) {
                                console.log('✅ メッセージ送信成功');
                                // メッセージをクリア
                                messageInput.value = '';
                                updateSubmitButton();

                                // 新しいメッセージを即座に取得
                                setTimeout(pollNewMessages, 100);
                            } else {
                                console.error('❌ メッセージ送信エラー:', response.data);
                                alert('メッセージの送信に失敗しました: ' + (response.data || '不明なエラー'));
                            }
                        } catch (e) {
                            console.error('❌ レスポンス解析エラー:', e.name, ':', e.message);
                            console.error('生レスポンス:', xhr.responseText);
                            console.error('生レスポンス(JSON.stringify):', JSON.stringify(xhr.responseText));
                            console.error('Response Headers:', xhr.getAllResponseHeaders());

                            // より具体的なエラー情報を表示
                            alert('JSON解析エラー: ' + e.message + '\nレスポンス長: ' + xhr.responseText.length);
                        }
                    } else {
                        console.error('HTTP エラー:', xhr.status);
                        alert('サーバーエラーが発生しました');
                    }
                }
            };

            xhr.send(params);
        });
    }

    // テキストエリアのリアルタイム検証
    var messageInput = document.getElementById('staff-chat-input');
    var submitButton = document.getElementById('staff-chat-submit');

    if (messageInput && submitButton) {
        function updateSubmitButton() {
            var hasContent = messageInput.value.trim().length > 0;
            submitButton.disabled = !hasContent;
        }

        messageInput.addEventListener('input', updateSubmitButton);
        messageInput.addEventListener('keydown', function (e) {
            // Ctrl+Enter または Cmd+Enter で送信
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                if (!submitButton.disabled) {
                    // フォームのsubmitイベントをトリガー
                    if (chatForm) {
                        var event = new Event('submit', { bubbles: true, cancelable: true });
                        chatForm.dispatchEvent(event);
                    }
                }
            }
        });

        // 初期状態を設定
        updateSubmitButton();
    }

    // 新しいメッセージが追加された後のスクロール処理
    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                // 新しいメッセージが追加された場合、チャットが開いていればスクロール
                if (window.ktpDebugMode) {
                    console.log('MutationObserver: 新しいメッセージが追加されました');
                }
                scrollToBottom();
            }
        });
    });

    var messagesContainer = document.getElementById('staff-chat-messages');
    if (messagesContainer) {
        observer.observe(messagesContainer, {
            childList: true,
            subtree: true
        });
    }
});

// スタッフチャット関連の処理
if (document.getElementById('staff-chat-messages')) {
// TODO: ポーリング機能は将来的に実装予定
// 現在はサーバー側のAJAXエンドポイントが未実装のため、一時的に無効化

/*
// 最後のメッセージタイムスタンプを保持
var lastMessageTime = null;
var isPollingActive = false;

// 最新メッセージをポーリングで取得
function pollNewMessages() {
    if (window.ktpDebugMode) {
        console.log('🔄 pollNewMessages 実行開始:', new Date().toLocaleTimeString());
    }

    if (isPollingActive) {
        if (window.ktpDebugMode) {
            console.log('⏳ pollNewMessages: 既にポーリング中のためスキップ');
        }
        return; // 既にポーリング中の場合はスキップ
    }

    // チャットが閉じている場合はポーリングしない
    var chatContent = document.getElementById('staff-chat-content');
    if (!chatContent || chatContent.style.display === 'none') {
        if (window.ktpDebugMode) {
            console.log('💤 pollNewMessages: チャットが閉じているためスキップ');
        }
        return;
    }

    var orderId = document.querySelector('input[name="staff_chat_order_id"]')?.value;
    if (!orderId) {
        if (window.ktpDebugMode) {
            console.log('❌ pollNewMessages: 注文IDが見つかりません');
        }
        return;
    }

    if (window.ktpDebugMode) {
        console.log('📡 pollNewMessages: リクエスト準備中', {
            orderId: orderId,
            lastMessageTime: lastMessageTime
        });
    }

    isPollingActive = true;

    // 最後のメッセージ時刻を取得
    var lastMessageElement = document.querySelector('.staff-chat-message:last-child [data-timestamp]');
    if (lastMessageElement) {
        lastMessageTime = lastMessageElement.getAttribute('data-timestamp');
    }

    // AJAX リクエスト（簡易版 - 実際の実装では WordPress AJAX API を使用）
    var xhr = new XMLHttpRequest();
    var url = ajaxurl || window.location.href; // WordPress AJAX URL

    // ajaxurl が未定義の場合は警告を出力
    if (typeof ajaxurl === 'undefined') {
        console.warn('⚠️ pollNewMessages: ajaxurl が未定義です。WordPress Ajax が正しく設定されていない可能性があります');
        if (window.ktpDebugMode) {
            console.warn('fallback URL を使用:', url);
        }
    }

    var params = 'action=get_latest_staff_chat&order_id=' + orderId;
    if (lastMessageTime) {
        params += '&last_time=' + encodeURIComponent(lastMessageTime);
    }

    // nonceを追加
    if (typeof ktpwp_ajax !== 'undefined' && ktpwp_ajax.nonces && ktpwp_ajax.nonces.staff_chat) {
        params += '&_ajax_nonce=' + ktpwp_ajax.nonces.staff_chat;
    } else {
        // nonce が見つからない場合はエラーログを出力してリクエストを中止
        console.error('❌ pollNewMessages: スタッフチャット用のnonceが見つかりません');
        if (window.ktpDebugMode) {
            console.error('利用可能なktpwp_ajax:', typeof ktpwp_ajax !== 'undefined' ? ktpwp_ajax : 'undefined');
        }
        isPollingActive = false;
        return;
    }

    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            isPollingActive = false;

            if (window.ktpDebugMode) {
                console.log('🔄 pollNewMessages レスポンス受信:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseLength: xhr.responseText.length,
                    headers: xhr.getAllResponseHeaders()
                });
            }

            if (xhr.status === 200) {
                try {
                    if (window.ktpDebugMode) {
                        console.log('📥 pollNewMessages レスポンス内容:', xhr.responseText.substring(0, 500) + (xhr.responseText.length > 500 ? '...' : ''));
                    }

                    // レスポンステキストが空の場合の処理
                    if (!xhr.responseText || xhr.responseText.trim() === '') {
                        console.error('❌ pollNewMessages: 空のレスポンスを受信');
                        return;
                    }

                    // WordPressの典型的なエラーレスポンス（"0"）の検出
                    if (xhr.responseText.trim() === '0') {
                        console.error('❌ pollNewMessages: WordPress Ajaxエラー（"0"）を受信 - ハンドラーが見つからないか、nonceが無効');
                        return;
                    }

                    // レスポンスがJSONで始まっていない場合の警告
                    const trimmedResponse = xhr.responseText.trim();
                    if (!trimmedResponse.startsWith('{') && !trimmedResponse.startsWith('[')) {
                        console.error('❌ pollNewMessages: レスポンスがJSONではありません');
                        console.error('レスポンスの開始:', trimmedResponse.substring(0, 100));
                        if (window.ktpDebugMode) {
                            alert('pollNewMessages: 無効なレスポンス形式\n開始: ' + trimmedResponse.substring(0, 100));
                        }
                        return;
                    }

                    var response = JSON.parse(xhr.responseText);

                    if (window.ktpDebugMode) {
                        console.log('✅ pollNewMessages 解析済みレスポンス:', response);
                    }

                    if (response.success && response.data && response.data.length > 0) {
                        // 新しいメッセージをDOMに追加
                        appendNewMessages(response.data);
                        scrollToBottom();

                        // チャットが閉じている場合は通知バッジを表示
                        var chatContent = document.getElementById('staff-chat-content');
                        if (chatContent && chatContent.style.display === 'none') {
                            showNewMessageNotification(response.data.length);
                        }
                    } else if (response.success === false) {
                        // サーバーサイドエラーの場合
                        console.warn('⚠️ pollNewMessages サーバーエラー:', response.data || 'Unknown error');
                    }
                    // response.success が true で data が空の場合は新しいメッセージがないので正常

                } catch (e) {
                    console.error('❌ pollNewMessages JSON解析エラー:', e.name, ':', e.message);
                    console.error('Response Text:', xhr.responseText);
                    console.error('Response Length:', xhr.responseText.length);
                    console.error('Response Headers:', xhr.getAllResponseHeaders());

                    // レスポンステキストの詳細分析
                    if (xhr.responseText) {
                        console.error('Response First 200 chars:', xhr.responseText.substring(0, 200));
                        console.error('Response Last 200 chars:', xhr.responseText.substring(Math.max(0, xhr.responseText.length - 200)));

                        // 制御文字の検出
                        var controlChars = xhr.responseText.match(/[\x00-\x1F\x7F]/g);
                        if (controlChars) {
                            console.error('Control characters found:', controlChars.map(function(c) { return '0x' + c.charCodeAt(0).toString(16); }));
                        }

                        // HTMLタグの検出（WordPressのエラーページやプラグイン干渉）
                        if (xhr.responseText.includes('<html>') || xhr.responseText.includes('<!DOCTYPE')) {
                            console.error('⚠️ レスポンスにHTMLが含まれています - WordPressエラーページまたはプラグイン干渉の可能性');
                        }
                    }

                    // ユーザーへの詳細なエラー表示は本番環境では控える
                    if (window.ktpDebugMode) {
                        alert('pollNewMessages JSON解析エラー: ' + e.message + '\nレスポンス長: ' + xhr.responseText.length + '\n最初の100文字: ' + xhr.responseText.substring(0, 100));
                    }
                }
            } else {
                console.error('❌ pollNewMessages HTTPエラー:', xhr.status, xhr.statusText);
                console.error('Response Text:', xhr.responseText);
            }
        }
    };

    xhr.send(params);
}

// 新しいメッセージをDOMに追加（スクロール可能エリアのみ）
function appendNewMessages(messages) {
    var messagesContainer = document.getElementById('staff-chat-messages');
    if (!messagesContainer) return;

    messages.forEach(function(message) {
        // 初期メッセージ（第1行目）はスキップ（固定ヘッダーで既に表示済み）
        if (message.is_initial === '1' || message.is_initial === 1) {
            return;
        }

        // 2行目以降のメッセージのみ追加
        var messageDiv = document.createElement('div');
        messageDiv.className = 'staff-chat-message scrollable';

        var formattedTime = new Date(message.created_at).toLocaleString('ja-JP');

        messageDiv.innerHTML =
            '<div class="staff-chat-message-header">' +
            '<span class="staff-chat-avatar-wrapper">' +
            '<img src="" alt="' + escapeHtml(message.user_display_name) + '" class="staff-chat-wp-avatar" width="24" height="24">' +
            '</span>' +
            '<span class="staff-chat-user-name">' + escapeHtml(message.user_display_name) + '</span>' +
            '<span class="staff-chat-timestamp" data-timestamp="' + message.created_at + '">' + formattedTime + '</span>' +
            '</div>' +
            '<div class="staff-chat-content">' + escapeHtml(message.message).replace(/\n/g, '<br>') + '</div>';

        messagesContainer.appendChild(messageDiv);
    });
}

// HTMLエスケープ関数
function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 5秒ごとに新しいメッセージをポーリング
if (window.ktpDebugMode) {
    console.log('🔄 pollNewMessages タイマー開始 (5秒間隔)');
}
setInterval(pollNewMessages, 5000);
}

// 新しいメッセージ通知バッジ機能
// 新しいメッセージ通知バッジを表示
function showNewMessageNotification(newMessageCount) {
var toggleBtn = document.getElementById('staff-chat-toggle-btn');
if (!toggleBtn) return;

var existingBadge = toggleBtn.querySelector('.staff-chat-notification-badge');
var existingCount = toggleBtn.querySelector('.staff-chat-message-count');

if (!existingBadge) {
    // 通知バッジを作成
    var badge = document.createElement('span');
    badge.className = 'staff-chat-notification-badge';
    badge.textContent = '●';
    badge.style.color = '#ff4444';
    badge.style.marginLeft = '8px';
    badge.style.fontSize = '16px';
    badge.style.animation = 'pulse 1s infinite';

    // メッセージカウント表示がある場合はその前に、ない場合は最後に追加
    if (existingCount) {
        toggleBtn.insertBefore(badge, existingCount);
    } else {
        toggleBtn.appendChild(badge);
    }
}

// メッセージカウントも更新
if (existingCount) {
    var currentCount = parseInt(existingCount.textContent.replace(/[()]/g, '')) || 0;
    existingCount.textContent = '(' + (currentCount + newMessageCount) + ')';
}
}

// ページ離脱時のクリーンアップ処理
window.addEventListener('beforeunload', function () {
// スクロールタイマーをクリア
if (window.clearScrollTimeouts) {
    window.clearScrollTimeouts();
}
});

// タブ切り替え時（ページが非表示になった時）のクリーンアップ処理
document.addEventListener('visibilitychange', function () {
if (document.hidden && window.clearScrollTimeouts) {
    window.clearScrollTimeouts();
}
});

// グローバルスコープで通知を表示する関数
window.showSuccessNotification = function (message) {
// 通知要素を作成
var notification = document.createElement('div');
notification.className = 'success-notification';
notification.textContent = message;

// 通知を画面に追加
document.body.appendChild(notification);

// 数秒後に通知を削除
setTimeout(function () {
    notification.remove();
}, 3000);
};

// DOMContentLoaded イベントで showSuccessNotification を呼び出す
window.addEventListener('DOMContentLoaded', function () {
if (typeof showSuccessNotification === 'function') {
    console.log('showSuccessNotification is loaded and ready to use.');
} else {
    console.error('showSuccessNotification is not defined.');
}
});
