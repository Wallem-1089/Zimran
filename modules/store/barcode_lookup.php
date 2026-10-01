<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

header('Content-Type: application/json');

if (!$storeTablesReady) {
    http_response_code(503);
    echo json_encode(['success' => false, 'errors' => ['Store tables are not available.']]);
    exit;
}

$barcode = trim((string)($_GET['barcode'] ?? $_POST['barcode'] ?? ''));
$departmentId = (int)($_GET['department_id'] ?? $_POST['department_id'] ?? 0);
$requireStock = (int)($_GET['require_stock'] ?? $_POST['require_stock'] ?? 0) === 1;

if ($barcode === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => ['Barcode is required.']]);
    exit;
}

$item = $storeService->getItemByBarcode($barcode, $currentUser);
if (!$item) {
    http_response_code(404);
    echo json_encode(['success' => false, 'errors' => ['No inventory item matched this barcode.']]);
    exit;
}

$stock = null;
if ($departmentId > 0) {
    $stock = $storeService->getDepartmentBalance((int)$item['id'], $departmentId, $currentUser);
    if ($requireStock && (!$stock || (float)($stock['quantity'] ?? 0) <= 0)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'errors' => ['Item found, but no stock is available in the selected department.']]);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'item' => [
        'id' => (int)$item['id'],
        'item_code' => (string)$item['item_code'],
        'item_name' => (string)$item['item_name'],
        'category' => (string)$item['category'],
        'unit' => (string)$item['unit'],
        'is_active' => (int)$item['is_active'],
        'barcodes' => $item['barcode_list'] ?? [],
    ],
    'stock' => $stock ? [
        'department_id' => (int)$stock['department_id'],
        'department_name' => (string)($stock['department_name'] ?? ''),
        'quantity' => (string)$stock['quantity'],
        'unit' => (string)($stock['unit'] ?? $item['unit']),
    ] : null,
]);
exit;
