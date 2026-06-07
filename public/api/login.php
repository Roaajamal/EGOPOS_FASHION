<?php
header('Content-Type: application/json');

// بيانات DB
$host = "localhost";
$db   = "medoo";
$user = "medoo";
$pass = "Sst2468@@";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['status'=>'error', 'message'=>'فشل الاتصال بقاعدة البيانات']);
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'الرجاء إدخال اسم المستخدم وكلمة المرور']);
    exit;
}

$stmt = $conn->prepare("SELECT id, password, allow_login, status, business_id FROM users WHERE username=? LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'المستخدم غير موجود']);
    exit;
}

$row = $result->fetch_assoc();
if (!password_verify($password, $row['password']) || !$row['allow_login'] || $row['status'] !== 'active') {
    echo json_encode(['status' => 'error', 'message' => 'كلمة المرور خاطئة أو الحساب غير مفعل']);
    exit;
}

echo json_encode([
    'status' => 'success',
    'business_id' => $row['business_id']
]);
?>
