<?php
// api/search_products.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');
$pdo = getDBConnection();

try {
    if (empty($query)) {
        // Return first 12 active products
        $stmt = $pdo->query("SELECT p.id, p.barcode, p.name, p.selling_price, p.cost_price, p.stock_quantity, p.unit, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 ORDER BY p.name ASC LIMIT 15");
        $products = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT p.id, p.barcode, p.name, p.selling_price, p.cost_price, p.stock_quantity, p.unit, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1 AND (p.barcode LIKE ? OR p.name LIKE ?) ORDER BY p.name ASC LIMIT 20");
        $searchParam = "%$query%";
        $stmt->execute([$searchParam, $searchParam]);
        $products = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($products),
        'products' => $products
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
