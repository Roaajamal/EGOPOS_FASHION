<?php
header('Content-Type: application/json');

$host = "localhost";
$db   = "medoo";
$user = "medoo";
$pass = "Sst2468@@";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['status'=>'error','message'=>'فشل الاتصال بقاعدة البيانات']);
    exit;
}

$business_id = $_GET['business_id'] ?? '';

if (!$business_id) {
    echo json_encode(['status' => 'error', 'message' => 'الرجاء إدخال business_id']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        COALESCE(v.sub_sku, p.sku) AS sku,
        IFNULL(b.name, '') AS brand_name,
        IFNULL(c.name, '') AS category_name,
        IFNULL(v.sell_price_inc_tax, 0) AS price_inc_tax,
        IFNULL(p.product_description, '') AS description,
        IFNULL(p.image, '') AS image
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id AND b.business_id = ?
    LEFT JOIN categories c ON p.category_id = c.id AND c.business_id = ?
    LEFT JOIN variations v ON v.product_id = p.id
    WHERE p.business_id = ?
");

$stmt->bind_param("iii", $business_id, $business_id, $business_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode(['status' => 'success', 'products' => $products]);
