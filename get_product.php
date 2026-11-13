<?php
// api/get_product.php - Direct barcode lookup for manual barcode entry
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$barcode = trim($_GET['barcode'] ?? '');
if (empty($barcode)) {
    echo json_encode(['success' => false, 'message' => 'Barcode required']);
    exit;
}

$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("SELECT p.id, p.barcode, p.name, p.selling_price, p.cost_price, p.stock_quantity, p.unit, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.barcode = ? AND p.is_active = 1 LIMIT 1");
    $stmt->execute([$barcode]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => "Product with barcode '$barcode' not found."]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
