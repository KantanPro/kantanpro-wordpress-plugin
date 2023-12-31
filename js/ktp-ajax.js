// document.addEventListener('DOMContentLoaded', function() {
//     var tabs = document.querySelectorAll('#ktp-tabs > div.tab');

//     tabs.forEach(function(tab) {
//         tab.addEventListener('click', function(e) {
//             e.preventDefault();
//             var tabName = tab.getAttribute('data-tab');
//             fetchTabContent(tabName);
//         });
//     });

//     function fetchTabContent(tabName) {
//         fetch(ajaxurl, {
//             method: 'POST',
//             credentials: 'same-origin',
//             headers: {
//                 'Content-Type': 'application/x-www-form-urlencoded'
//             },
//             body: 'action=ktp_fetch_tab_content&tab=' + tabName
//         })
//         .then(response => response.text())
//         .then(html => {
//             document.getElementById('tab-content').innerHTML = html;
//         })
//         .catch(error => console.error('Error:', error));
//     }
// });
