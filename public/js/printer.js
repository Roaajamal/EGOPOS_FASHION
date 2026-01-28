// printer.js

var socket = null;
var socket_host = 'wss://127.0.0.1:6443'; // استخدم wss للـ HTTPS

// === تهيئة اتصال WebSocket مع QZ Tray ===
function initializeSocket() {
    if (!socket) {
        socket = new WebSocket(socket_host);
        socket.onopen = function() { console.log("QZ Tray connected"); };
        socket.onclose = function() { 
            socket = null; 
            console.log("QZ Tray disconnected"); 
        };
        socket.onerror = function(e) { console.error("QZ Tray socket error", e); };
    }
}

// === تحويل ArrayBuffer إلى Base64 ===
function arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

// === دالة الطباعة المباشرة ===
function printPDFDirect(pdfUrl, printerName = "POS Printer") {
    fetch(pdfUrl)
        .then(res => res.blob())
        .then(blob => blob.arrayBuffer())
        .then(arrayBuffer => {
            const config = qz.configs.create(printerName);
            const data = [{
                type: 'pdf',
                format: 'base64',
                data: arrayBufferToBase64(arrayBuffer)
            }];
            return qz.print(config, data);
        })
        .then(() => console.log("Print job sent successfully"))
        .catch(err => console.error("Print failed: ", err));
}

// === مثال: استدعاء الطباعة بعد حفظ الفاتورة ===
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة QZ Tray فور تحميل الصفحة
    initializeSocket();

    // افترض أن الزر يحتوي على ID: save_invoice_btn
    const printBtn = document.getElementById('save_invoice_btn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            const invoiceId = this.dataset.invoiceId;
            // استدعاء API للحصول على رابط PDF
            fetch(`/invoice/print-direct/${invoiceId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.pdf_url) {
                        printPDFDirect(data.pdf_url, "POS Printer");
                    } else {
                        toastr.warning(data.message || "خطأ في تحميل الفاتورة");
                    }
                })
                .catch(err => console.error("Error fetching invoice PDF:", err));
        });
    }
});
