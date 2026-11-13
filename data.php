<?php
// api/data.php - Unified Local XAMPP/MySQL Backend API Bridge
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apikey');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    $pdo = getDBConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'load_all';

// Read JSON input if sent via fetch POST
$rawInput = file_get_contents('php://input');
$inputData = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $inputData = $decoded;
        if (isset($inputData['action'])) {
            $action = $inputData['action'];
        }
    }
}

try {
    switch ($action) {
        case 'ping':
        case 'status':
            echo json_encode([
                'success' => true,
                'status' => 'connected',
                'database' => DB_NAME,
                'host' => DB_HOST,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;

        case 'load_all':
            // 1. Products
            $stmt = $pdo->query("SELECT id, barcode, name, category_id, supplier_id, cost_price, selling_price, stock_quantity, low_stock_threshold, expiry_date, unit, is_active FROM products ORDER BY id ASC");
            $products = $stmt->fetchAll();

            // 2. Users
            $stmt = $pdo->query("SELECT id, username, full_name, email, role, status FROM app_users ORDER BY id ASC");
            $users = $stmt->fetchAll();

            // 3. Suppliers
            $stmt = $pdo->query("SELECT id, name, contact_person as contact, phone, email, address, status FROM suppliers ORDER BY id ASC");
            $suppliers = $stmt->fetchAll();

            // 4. Employees
            $stmt = $pdo->query("SELECT id, user_id, employee_no, full_name, role_title, contact_no, daily_rate, hourly_rate, monthly_salary, hire_date, status FROM employees ORDER BY id ASC");
            $employees = $stmt->fetchAll();

            // 5. Payroll
            $stmt = $pdo->query("SELECT p.*, e.full_name as employee_name FROM payroll p JOIN employees e ON p.employee_id = e.id ORDER BY p.id DESC");
            $payroll = $stmt->fetchAll();

            // 6. Transactions
            $stmt = $pdo->query("SELECT t.*, u.full_name as cashier_name FROM transactions t LEFT JOIN app_users u ON t.cashier_id = u.id ORDER BY t.id DESC LIMIT 50");
            $transactions = $stmt->fetchAll();

            // 7. System Logs
            $stmt = $pdo->query("SELECT * FROM system_logs ORDER BY id DESC LIMIT 100");
            $logs = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'db' => 'mysql',
                'connected' => true,
                'products' => $products,
                'users' => $users,
                'suppliers' => $suppliers,
                'employees' => $employees,
                'payroll' => $payroll,
                'transactions' => $transactions,
                'logs' => $logs
            ]);
            break;

        case 'update_stock':
            $barcode = $inputData['barcode'] ?? $_POST['barcode'] ?? '';
            $stock = isset($inputData['stock_quantity']) ? intval($inputData['stock_quantity']) : (isset($_POST['stock_quantity']) ? intval($_POST['stock_quantity']) : null);
            $cost = isset($inputData['cost_price']) ? floatval($inputData['cost_price']) : (isset($_POST['cost_price']) ? floatval($_POST['cost_price']) : null);
            $supplierId = isset($inputData['supplier_id']) ? intval($inputData['supplier_id']) : null;

            if (!$barcode || $stock === null) {
                echo json_encode(['success' => false, 'error' => 'Missing barcode or stock_quantity']);
                exit;
            }

            if ($cost !== null && $supplierId !== null) {
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, cost_price = ?, supplier_id = ? WHERE barcode = ?");
                $stmt->execute([$stock, $cost, $supplierId, $barcode]);
            } elseif ($cost !== null) {
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ?, cost_price = ? WHERE barcode = ?");
                $stmt->execute([$stock, $cost, $barcode]);
            } else {
                $stmt = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE barcode = ?");
                $stmt->execute([$stock, $barcode]);
            }

            echo json_encode(['success' => true, 'barcode' => $barcode, 'stock_quantity' => $stock]);
            break;

        case 'save_supplier':
            $name = trim($inputData['name'] ?? $_POST['name'] ?? '');
            $contact = trim($inputData['contact'] ?? $_POST['contact'] ?? '');
            $phone = trim($inputData['phone'] ?? $_POST['phone'] ?? '');
            $email = trim($inputData['email'] ?? $_POST['email'] ?? '');
            $address = trim($inputData['address'] ?? $_POST['address'] ?? '');
            $status = trim($inputData['status'] ?? $_POST['status'] ?? 'active');
            $id = isset($inputData['id']) ? intval($inputData['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE suppliers SET name = ?, contact_person = ?, phone = ?, email = ?, address = ?, status = ? WHERE id = ?");
                $stmt->execute([$name, $contact, $phone, $email, $address, $status, $id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO suppliers (name, contact_person, phone, email, address, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $contact, $phone, $email, $address, $status]);
                $id = $pdo->lastInsertId();
            }

            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'toggle_supplier_status':
            $id = intval($inputData['id'] ?? $_POST['id'] ?? 0);
            $status = trim($inputData['status'] ?? $_POST['status'] ?? '');
            $stmt = $pdo->prepare("UPDATE suppliers SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
            break;

        case 'delete_supplier':
            $id = intval($inputData['id'] ?? $_POST['id'] ?? 0);
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'save_user':
            $id = intval($inputData['id'] ?? $_POST['id'] ?? 0);
            $fullName = trim($inputData['full_name'] ?? $_POST['full_name'] ?? '');
            $username = trim($inputData['username'] ?? $_POST['username'] ?? '');
            $password = trim($inputData['password'] ?? $_POST['password'] ?? '');
            $role = trim($inputData['role'] ?? $_POST['role'] ?? 'cashier');
            $status = trim($inputData['status'] ?? $_POST['status'] ?? 'active');

            if ($id > 0) {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE app_users SET full_name = ?, username = ?, password = ?, role = ?, status = ? WHERE id = ?");
                    $stmt->execute([$fullName, $username, $hashed, $role, $status, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE app_users SET full_name = ?, username = ?, role = ?, status = ? WHERE id = ?");
                    $stmt->execute([$fullName, $username, $role, $status, $id]);
                }
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO app_users (full_name, username, password, role, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$fullName, $username, $hashed, $role, $status]);
                $id = $pdo->lastInsertId();
            }
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'toggle_user_status':
            $id = intval($inputData['id'] ?? $_POST['id'] ?? 0);
            $status = trim($inputData['status'] ?? $_POST['status'] ?? '');
            $stmt = $pdo->prepare("UPDATE app_users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
            break;

        case 'delete_user':
            $id = intval($inputData['id'] ?? $_POST['id'] ?? 0);
            // Protect admin ID 1
            if ($id <= 1) {
                echo json_encode(['success' => false, 'error' => 'Default administrator account cannot be deleted.']);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM app_users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'save_payroll':
            $empId = intval($inputData['employee_id'] ?? $_POST['employee_id'] ?? 0);
            $pStart = $inputData['period_start'] ?? $_POST['period_start'] ?? date('Y-m-01');
            $pEnd = $inputData['period_end'] ?? $_POST['period_end'] ?? date('Y-m-t');
            $pMonth = $inputData['period_month'] ?? $_POST['period_month'] ?? date('F');
            $pYear = intval($inputData['period_year'] ?? $_POST['period_year'] ?? date('Y'));
            $days = floatval($inputData['days_worked'] ?? $_POST['days_worked'] ?? 0);
            $regHours = floatval($inputData['regular_hours'] ?? $_POST['regular_hours'] ?? 0);
            $otHours = floatval($inputData['overtime_hours'] ?? $_POST['overtime_hours'] ?? 0);
            $gross = floatval($inputData['gross_pay'] ?? $_POST['gross_pay'] ?? 0);
            $sss = floatval($inputData['sss'] ?? $_POST['sss'] ?? 0);
            $phil = floatval($inputData['philhealth'] ?? $_POST['philhealth'] ?? 0);
            $pag = floatval($inputData['pagibig'] ?? $_POST['pagibig'] ?? 0);
            $tax = floatval($inputData['withholding_tax'] ?? $_POST['withholding_tax'] ?? 0);
            $totalDeduc = floatval($inputData['total_deductions'] ?? $_POST['total_deductions'] ?? ($sss + $phil + $pag + $tax));
            $net = floatval($inputData['net_pay'] ?? $_POST['net_pay'] ?? ($gross - $totalDeduc));

            $stmt = $pdo->prepare("INSERT INTO payroll (employee_id, period_start, period_end, period_month, period_year, days_worked, regular_hours, overtime_hours, gross_pay, sss_deduction, philhealth_deduction, pagibig_deduction, withholding_tax, total_deductions, net_pay, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$empId, $pStart, $pEnd, $pMonth, $pYear, $days, $regHours, $otHours, $gross, $sss, $phil, $pag, $tax, $totalDeduc, $net]);
            $newId = $pdo->lastInsertId();

            echo json_encode(['success' => true, 'id' => $newId]);
            break;

        case 'save_transaction':
            $invoiceNo = $inputData['invoice_no'] ?? $_POST['invoice_no'] ?? ('INV-' . date('Ymd-His'));
            $custName = $inputData['customer_name'] ?? $_POST['customer_name'] ?? 'Walk-in Customer';
            $subtotal = floatval($inputData['subtotal'] ?? $_POST['subtotal'] ?? 0);
            $taxRate = floatval($inputData['tax_rate'] ?? $_POST['tax_rate'] ?? 12);
            $taxAmount = floatval($inputData['tax_amount'] ?? $_POST['tax_amount'] ?? 0);
            $discType = $inputData['discount_type'] ?? $_POST['discount_type'] ?? 'none';
            $discId = $inputData['discount_id_no'] ?? $_POST['discount_id_no'] ?? null;
            $discRate = floatval($inputData['discount_rate'] ?? $_POST['discount_rate'] ?? 0);
            $discAmount = floatval($inputData['discount_amount'] ?? $_POST['discount_amount'] ?? 0);
            $totalAmount = floatval($inputData['total_amount'] ?? $_POST['total_amount'] ?? 0);
            $payMethod = $inputData['payment_method'] ?? $_POST['payment_method'] ?? 'cash';
            $amtPaid = floatval($inputData['amount_paid'] ?? $_POST['amount_paid'] ?? 0);
            $changeAmt = floatval($inputData['change_amount'] ?? $_POST['change_amount'] ?? 0);
            $payRef = $inputData['payment_reference'] ?? $_POST['payment_reference'] ?? null;

            $stmt = $pdo->prepare("INSERT INTO transactions (invoice_no, cashier_id, customer_name, subtotal, tax_rate, tax_amount, discount_type, discount_id_no, discount_rate, discount_amount, total_amount, payment_method, amount_paid, change_amount, payment_reference, status) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')");
            $stmt->execute([$invoiceNo, $custName, $subtotal, $taxRate, $taxAmount, $discType, $discId, $discRate, $discAmount, $totalAmount, $payMethod, $amtPaid, $changeAmt, $payRef]);
            $txId = $pdo->lastInsertId();

            if (!empty($inputData['items']) && is_array($inputData['items'])) {
                $itemStmt = $pdo->prepare("INSERT INTO transaction_items (transaction_id, product_id, product_name, barcode, unit_price, cost_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($inputData['items'] as $it) {
                    $prodId = intval($it['id'] ?? 1);
                    $prodName = $it['product_name'] ?? $it['name'] ?? '';
                    $barcode = $it['barcode'] ?? '';
                    $unitPrice = floatval($it['unit_price'] ?? $it['price'] ?? 0);
                    $costPrice = floatval($it['cost_price'] ?? 0);
                    $qty = intval($it['quantity'] ?? 1);
                    $itSubtotal = floatval($it['subtotal'] ?? ($unitPrice * $qty));
                    $itemStmt->execute([$txId, $prodId, $prodName, $barcode, $unitPrice, $costPrice, $qty, $itSubtotal]);
                }
            }

            echo json_encode(['success' => true, 'transaction_id' => $txId, 'invoice_no' => $invoiceNo]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
