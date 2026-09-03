<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

$visitId = filter_input(INPUT_GET, 'visit', FILTER_VALIDATE_INT) ?: 0;
if ($visitId <= 0) {
    http_response_code(400);
    exit('Visit is required.');
}

$visit = $visitService->getVisitById($visitId);
if (!$visit) {
    http_response_code(404);
    exit('Encounter not found.');
}

if (!$permissionService->canCreateBillingRequest($currentUser)) {
    http_response_code(403);
    exit('You are not allowed to create billing requests.');
}

if (!$billingTablesReady || !$billingRequestsReady) {
    http_response_code(503);
    exit('Billing request tables are not available yet. Apply Migration 044 to enable this section.');
}

$items = $accountsService->searchItems(['status' => 'active'], $currentUser);
$items = array_values(array_filter($items, static fn (array $item): bool => !empty($item['is_active'])));
$sourceModule = trim((string)($_GET['source_module'] ?? 'General'));
$sourceRecordId = filter_input(INPUT_GET, 'source_record_id', FILTER_VALIDATE_INT) ?: null;
$defaultDescription = trim((string)($_GET['description'] ?? ''));
$enableWritingMode = $permissionService->canUseConsultationHandwriting($currentUser);

$pageTitle = 'Request Billing';
$moduleStylesheet = '/modules/visits/assets/visits.css';
require __DIR__ . '/../../layouts/header.php';
require __DIR__ . '/../../layouts/sidebar.php';
?>
<div class="main-container">
<?php require __DIR__ . '/../../layouts/navbar.php'; ?>
<main class="content">
    <div class="page-header">
        <div>
            <h1>Request Billing</h1>
            <p><?= e((string)$visit['visit_number']) ?> | <?= e(billingDisplayPatientName($visit)) ?></p>
        </div>
        <div class="form-actions">
            <a class="btn-secondary" href="view.php?visit=<?= (int)$visit['id'] ?>">Back to Billing</a>
            <a class="btn-secondary" href="../visits/workspace.php?id=<?= (int)$visit['id'] ?>&tab=billing">Back to Workspace</a>
        </div>
    </div>

    <div class="card">
        <h3>Billing Recommendation</h3>
        <p class="text-muted">This does not create a charge yet. Accounts will review it and choose the official billable item/price.</p>
        <form method="post" action="request_save.php" class="form-grid" <?= $enableWritingMode ? 'data-hms-handwriting-form="1"' : '' ?>>
            <?= csrfField() ?>
            <input type="hidden" name="visit_id" value="<?= (int)$visit['id'] ?>">
            <input type="hidden" name="source_module" value="<?= e($sourceModule !== '' ? $sourceModule : 'General') ?>">
            <?php if ($sourceRecordId): ?>
                <input type="hidden" name="source_record_id" value="<?= (int)$sourceRecordId ?>">
            <?php endif; ?>

            <div class="form-group full-width">
                <?php hmsRenderHandwritingToolbar($enableWritingMode, 'Billing Request Entry Mode'); ?>
                <?php hmsRenderHandwritingTextarea('description', 'Description', $defaultDescription, 5, true, $enableWritingMode); ?>
                <small class="text-muted">Example: Full Blood Count and Malaria Parasite done.</small>
            </div>

            <div class="form-group">
                <label for="suggested_billable_item_id">Suggested Billable Item</label>
                <select id="suggested_billable_item_id" name="suggested_billable_item_id">
                    <option value="">No suggestion</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?= (int)$item['id'] ?>">
                            <?= e((string)$item['item_code']) ?> — <?= e((string)$item['item_name']) ?> (&#8358;<?= e(number_format((float)$item['unit_price'], 2)) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity</label>
                <input id="quantity" name="quantity" type="number" min="0.01" step="0.01" value="1" required>
            </div>

            <div class="form-actions">
                <button class="btn-primary" type="submit">Submit Billing Request</button>
            </div>
        </form>
        <?php hmsRenderHandwritingScript($enableWritingMode); ?>
    </div>
</main>
<?php require __DIR__ . '/../../layouts/footer.php'; ?>
</div>
