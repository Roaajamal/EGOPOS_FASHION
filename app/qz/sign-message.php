<?php
// بما أن المفتاح في نفس المجلد، نستخدم المسار الحالي
$privateKeyPath = __DIR__ . '/private.pem'; 

if (isset($_GET['request'])) {
    $data = $_GET['request'];
    if (file_exists($privateKeyPath)) {
        $key = file_get_contents($privateKeyPath);
        $privateKey = openssl_get_privatekey($key);
        openssl_sign($data, $signature, $privateKey, "sha256");
        header("Content-Type: text/plain");
        echo base64_encode($signature);
    } else {
        echo "Error: Private key not found at " . $privateKeyPath;
    }
}
?>