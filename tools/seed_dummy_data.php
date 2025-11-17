<?php
// seed_dummy_data.php  -- run once
require_once __DIR__ . '/../includes/db_connect.php';

// categories and their subcategories
$data = [
    'Motorbike' => ['Engine Parts', 'Electric Parts', 'Accessories'],
    'Cars' => ['Engine Parts', 'Electric Parts', 'Accessories'],
    'SUVs' => ['Engine Parts', 'Electric Parts', 'Accessories'],
];

try {
    $pdo->beginTransaction();

    // insert categories, subcategories
    $catStmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name=name");
    $subStmt = $pdo->prepare("INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE name=name");

    foreach ($data as $catName => $subs) {
        $slugCat = strtolower(str_replace(' ', '-', $catName));
        $catStmt->execute([$catName, $slugCat]);
        $catId = $pdo->lastInsertId();
        if ($catId == 0) {
            // category existed, fetch id
            $row = $pdo->query("SELECT id FROM categories WHERE name = " . $pdo->quote($catName))->fetch();
            $catId = $row['id'];
        }

        foreach ($subs as $subName) {
            $slugSub = strtolower(str_replace(' ', '-', $subName));
            $subStmt->execute([$catId, $subName, $slugSub]);
            $subId = $pdo->lastInsertId();
            if ($subId == 0) {
                $row = $pdo->prepare("SELECT id FROM subcategories WHERE category_id = ? AND name = ?");
                $row->execute([$catId, $subName]);
                $subId = $row->fetchColumn();
            }

            // create 5 dummy products for each subcategory
            $prodStmt = $pdo->prepare("INSERT INTO products (category_id, subcategory_id, sku, name, description, price, market_price, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            for ($i=1; $i<=5; $i++) {
                $sku = strtoupper(substr($catName,0,2).substr($subName,0,2)) . sprintf('%03d', $i);
                $name = "$catName - $subName Item $i";
                $desc = "High-quality $subName for $catName. Item #$i. Compatible and tested.";
                $price = rand(500, 15000);
                $market_price = $price + rand(100, 1500);
                $stock = rand(5, 50);
                $image = "assets/images/sample-{$catId}-{$subId}-{$i}.jpg"; // placeholder
                $prodStmt->execute([$catId, $subId, $sku, $name, $desc, $price, $market_price, $stock, $image]);
            }
        }
    }

    $pdo->commit();
    echo "Seeding completed. Categories, subcategories, and products inserted successfully.";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Seeding failed: " . $e->getMessage();
}
