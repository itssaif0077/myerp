<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/autoload.php';

use App\Core\Database;
use App\Core\Env;

Env::load(__DIR__ . '/../.env');

$pdo = Database::getConnection();

echo "=== Seeding Initial ERP Data ===\n";

// 1. Initial Company
$stmt = $pdo->query("SELECT id FROM companies WHERE id = 1");
if (!$stmt->fetch()) {
    $pdo->exec("INSERT INTO companies (id, name, currency, status) VALUES (1, 'Antigravity Retail Ltd', 'INR', 'active')");
    echo "✓ Company created: Antigravity Retail Ltd\n";
} else {
    echo "✓ Company already exists.\n";
}

// 2. Initial User: saif@gmail.com
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute(['email' => 'saif@gmail.com']);
if (!$stmt->fetch()) {
    $passwordHash = password_hash('saif123', PASSWORD_BCRYPT);
    $ins = $pdo->prepare("
        INSERT INTO users (company_id, name, email, password, role, status)
        VALUES (1, 'Saif', 'saif@gmail.com', :pwd, 'superadmin', 'active')
    ");
    $ins->execute(['pwd' => $passwordHash]);
    echo "✓ User created: saif@gmail.com (password: saif123)\n";
} else {
    echo "✓ User saif@gmail.com already exists.\n";
}

// 3. Quick-Sell Products (Vadapav ₹20, Chai ₹10, Samosa ₹15)
$products = [
    ['name' => 'Vadapav', 'sku' => 'VP-001', 'price' => 20.00, 'cost' => 12.00, 'stock' => 100.0, 'order' => 1],
    ['name' => 'Special Chai', 'sku' => 'CH-001', 'price' => 10.00, 'cost' => 4.00, 'stock' => 200.0, 'order' => 2],
    ['name' => 'Samosa', 'sku' => 'SM-001', 'price' => 15.00, 'cost' => 8.00, 'stock' => 80.0, 'order' => 3],
];

foreach ($products as $p) {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = :name AND company_id = 1");
    $stmt->execute(['name' => $p['name']]);
    if (!$stmt->fetch()) {
        $pStmt = $pdo->prepare("
            INSERT INTO products (company_id, name, sku, category, unit, purchase_price, selling_price, current_stock, is_quick_sell, quick_sell_order, status)
            VALUES (1, :name, :sku, 'Fast Food', 'pcs', :cost, :price, :stock, 1, :order, 'active')
        ");
        $pStmt->execute([
            'name'  => $p['name'],
            'sku'   => $p['sku'],
            'cost'  => $p['cost'],
            'price' => $p['price'],
            'stock' => $p['stock'],
            'order' => $p['order'],
        ]);
        echo "✓ Product created: {$p['name']} (₹{$p['price']})\n";
    }
}

echo "=== Seeding Complete ===\n";
